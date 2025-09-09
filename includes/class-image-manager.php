<?php

/**
 * PartyMinder Image Manager
 * Centralized image upload and management functionality
 */
class PartyMinder_Image_Manager {

	const ALLOWED_TYPES = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );

	// Image dimensions for different types
	const PROFILE_IMAGE_MAX_WIDTH  = 400;
	const PROFILE_IMAGE_MAX_HEIGHT = 400;
	const COVER_IMAGE_MAX_WIDTH    = 1200;
	const COVER_IMAGE_MAX_HEIGHT   = 400;
	const POST_IMAGE_MAX_WIDTH     = 800;
	const POST_IMAGE_MAX_HEIGHT    = 600;

	/**
	 * Handle image upload
	 */
	public static function handle_image_upload( $file, $image_type, $entity_id, $entity_type = 'user', $event_id = null ) {
		// Validate file
		$validation = self::validate_image_file( $file, $image_type );
		if ( ! $validation['success'] ) {
			return $validation;
		}

		// Set up upload directory
		$upload_info = self::get_upload_directory( $entity_type, $event_id );
		if ( ! $upload_info['success'] ) {
			return $upload_info;
		}

		// Generate unique filename
		$filename  = self::generate_filename( $file, $image_type, $entity_id, $entity_type );
		$file_path = $upload_info['dir'] . $filename;
		$file_url  = $upload_info['url'] . $filename;

		// Process and save image
		$result = self::process_and_save_image( $file, $file_path, $image_type );
		if ( ! $result['success'] ) {
			return $result;
		}

		return array(
			'success'  => true,
			'url'      => $file_url,
			'path'     => $file_path,
			'filename' => $filename,
		);
	}

	/**
	 * Validate uploaded image file using centralized validation
	 */
	private static function validate_image_file( $file, $image_type ) {
		$validation_result = PartyMinder_Settings::validate_uploaded_file( $file );
		
		if ( is_wp_error( $validation_result ) ) {
			return array(
				'success' => false,
				'error'   => $validation_result->get_error_message(),
			);
		}

		return array( 'success' => true );
	}

	/**
	 * Get upload directory for entity type
	 */
	private static function get_upload_directory( $entity_type, $event_id = null ) {
		$upload_dir = wp_upload_dir();
		
		// Handle post images for events
		if ( $entity_type === 'post' && $event_id ) {
			$partyminder_dir = $upload_dir['basedir'] . '/partyminder/events/' . $event_id . '/posts/';
			$partyminder_url = $upload_dir['baseurl'] . '/partyminder/events/' . $event_id . '/posts/';
		} elseif ( $entity_type === 'conversation' && $event_id ) {
			// Handle conversation photos in conversation-specific directories
			$partyminder_dir = $upload_dir['basedir'] . '/partyminder/conversations/' . $event_id . '/';
			$partyminder_url = $upload_dir['baseurl'] . '/partyminder/conversations/' . $event_id . '/';
		} else {
			$partyminder_dir = $upload_dir['basedir'] . '/partyminder/' . $entity_type . 's/';
			$partyminder_url = $upload_dir['baseurl'] . '/partyminder/' . $entity_type . 's/';
		}

		// Create directory if it doesn't exist
		if ( ! file_exists( $partyminder_dir ) ) {
			if ( ! wp_mkdir_p( $partyminder_dir ) ) {
				return array(
					'success' => false,
					'error'   => __( 'Failed to create upload directory.', 'partyminder' ),
				);
			}
		}

		return array(
			'success' => true,
			'dir'     => $partyminder_dir,
			'url'     => $partyminder_url,
		);
	}

	/**
	 * Generate unique filename
	 */
	private static function generate_filename( $file, $image_type, $entity_id, $entity_type ) {
		$file_info = pathinfo( $file['name'] );
		$extension = strtolower( $file_info['extension'] );

		// Convert to jpg for consistency if needed
		if ( $extension === 'jpeg' ) {
			$extension = 'jpg';
		}

		// For post images, include a random component for multiple images
		if ( $image_type === 'post' ) {
			return $entity_type . '-' . $entity_id . '-' . $image_type . '-' . time() . '-' . wp_generate_password( 8, false ) . '.' . $extension;
		}

		return $entity_type . '-' . $entity_id . '-' . $image_type . '-' . time() . '.' . $extension;
	}

	/**
	 * Process and save image with resizing using WordPress media APIs
	 */
	private static function process_and_save_image( $file, $file_path, $image_type ) {
		// Get max dimensions based on image type
		switch ( $image_type ) {
			case 'cover':
				$max_width  = self::COVER_IMAGE_MAX_WIDTH;
				$max_height = self::COVER_IMAGE_MAX_HEIGHT;
				break;
			case 'post':
				$max_width  = self::POST_IMAGE_MAX_WIDTH;
				$max_height = self::POST_IMAGE_MAX_HEIGHT;
				break;
			default:
				$max_width  = self::PROFILE_IMAGE_MAX_WIDTH;
				$max_height = self::PROFILE_IMAGE_MAX_HEIGHT;
				break;
		}

		// Use WordPress image editor for processing
		$image_editor = wp_get_image_editor( $file['tmp_name'] );
		
		if ( is_wp_error( $image_editor ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to process image.', 'partyminder' ),
			);
		}

		// Get current dimensions
		$size = $image_editor->get_size();
		
		// Only resize if image is larger than max dimensions
		if ( $size['width'] > $max_width || $size['height'] > $max_height ) {
			$resize_result = $image_editor->resize( $max_width, $max_height );
			
			if ( is_wp_error( $resize_result ) ) {
				return array(
					'success' => false,
					'error'   => __( 'Failed to resize image.', 'partyminder' ),
				);
			}
		}

		// Save the processed image
		$save_result = $image_editor->save( $file_path );
		
		if ( is_wp_error( $save_result ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to save processed image.', 'partyminder' ),
			);
		}

		return array( 'success' => true );
	}


	/**
	 * Delete image file
	 */
	public static function delete_image( $image_url ) {
		if ( empty( $image_url ) ) {
			return true;
		}

		// Convert URL to file path
		$upload_dir = wp_upload_dir();
		$file_path  = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url );

		if ( file_exists( $file_path ) ) {
			return unlink( $file_path );
		}

		return true;
	}

	/**
	 * Get image dimensions
	 */
	public static function get_image_dimensions( $image_url ) {
		if ( empty( $image_url ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$file_path  = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url );

		if ( file_exists( $file_path ) ) {
			return getimagesize( $file_path );
		}

		return false;
	}

	/**
	 * Save post image metadata to database
	 */
	public static function save_post_image_metadata( $event_id, $user_id, $upload_result, $caption = '', $alt_text = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'partyminder_post_images';

		// Get image dimensions
		$image_info = getimagesize( $upload_result['path'] );
		$width      = $image_info ? $image_info[0] : 0;
		$height     = $image_info ? $image_info[1] : 0;

		$result = $wpdb->insert(
			$table_name,
			array(
				'event_id'          => $event_id,
				'user_id'           => $user_id,
				'filename'          => $upload_result['filename'],
				'original_filename' => sanitize_text_field( $_FILES['post_image']['name'] ?? $upload_result['filename'] ),
				'file_url'          => $upload_result['url'],
				'file_path'         => $upload_result['path'],
				'file_size'         => filesize( $upload_result['path'] ),
				'mime_type'         => wp_check_filetype( $upload_result['path'] )['type'],
				'width'             => $width,
				'height'            => $height,
				'caption'           => sanitize_textarea_field( $caption ),
				'alt_text'          => sanitize_text_field( $alt_text ),
				'sort_order'        => self::get_next_sort_order( $event_id ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get next sort order for event post images
	 */
	private static function get_next_sort_order( $event_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'partyminder_post_images';
		$max_order  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(sort_order) FROM $table_name WHERE event_id = %d",
				$event_id
			)
		);

		return $max_order ? $max_order + 1 : 1;
	}

	/**
	 * Get post images for an event
	 */
	public static function get_event_post_images( $event_id, $limit = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'partyminder_post_images';
		$limit_sql  = $limit ? $wpdb->prepare( ' LIMIT %d', $limit ) : '';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE event_id = %d ORDER BY sort_order ASC, created_at ASC" . $limit_sql,
				$event_id
			)
		);
	}

	/**
	 * Delete post image
	 */
	public static function delete_post_image( $image_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'partyminder_post_images';

		// Get image data before deleting
		$image = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d",
				$image_id
			)
		);

		if ( ! $image ) {
			return false;
		}

		// Delete file from filesystem
		if ( file_exists( $image->file_path ) ) {
			unlink( $image->file_path );
		}

		// Delete from database
		return $wpdb->delete(
			$table_name,
			array( 'id' => $image_id ),
			array( '%d' )
		);
	}

	/**
	 * Update post image metadata
	 */
	public static function update_post_image( $image_id, $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'partyminder_post_images';

		$allowed_fields = array( 'caption', 'alt_text', 'sort_order', 'is_featured' );
		$update_data    = array();
		$update_format  = array();

		foreach ( $data as $field => $value ) {
			if ( in_array( $field, $allowed_fields ) ) {
				$update_data[ $field ] = $value;
				$update_format[]       = in_array( $field, array( 'sort_order', 'is_featured' ) ) ? '%d' : '%s';
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		return $wpdb->update(
			$table_name,
			$update_data,
			array( 'id' => $image_id ),
			$update_format,
			array( '%d' )
		);
	}
}
