<?php

/**
 * PartyMinder Image Manager
 * Centralized image upload and management functionality
 */
class PartyMinder_Image_Manager {

	const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
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
	 * Validate uploaded image file
	 */
	private static function validate_image_file( $file, $image_type ) {
		// Check for upload errors
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return array(
				'success' => false,
				'error'   => __( 'File upload error occurred.', 'partyminder' ),
			);
		}

		// Check file size
		if ( $file['size'] > self::MAX_FILE_SIZE ) {
			return array(
				'success' => false,
				'error'   => __( 'Image must be smaller than 5MB.', 'partyminder' ),
			);
		}

		// Check file type
		if ( ! in_array( $file['type'], self::ALLOWED_TYPES ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Image must be JPG, PNG, GIF, or WebP format.', 'partyminder' ),
			);
		}

		// Additional validation for image type
		$image_info = getimagesize( $file['tmp_name'] );
		if ( $image_info === false ) {
			return array(
				'success' => false,
				'error'   => __( 'Invalid image file.', 'partyminder' ),
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
	 * Process and save image with resizing
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

		// Load image
		$image_info       = getimagesize( $file['tmp_name'] );
		$image_type_const = $image_info[2];

		switch ( $image_type_const ) {
			case IMAGETYPE_JPEG:
				$source_image = imagecreatefromjpeg( $file['tmp_name'] );
				break;
			case IMAGETYPE_PNG:
				$source_image = imagecreatefrompng( $file['tmp_name'] );
				break;
			case IMAGETYPE_GIF:
				$source_image = imagecreatefromgif( $file['tmp_name'] );
				break;
			case IMAGETYPE_WEBP:
				$source_image = imagecreatefromwebp( $file['tmp_name'] );
				break;
			default:
				return array(
					'success' => false,
					'error'   => __( 'Unsupported image format.', 'partyminder' ),
				);
		}

		if ( ! $source_image ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to process image.', 'partyminder' ),
			);
		}

		// Get original dimensions
		$orig_width  = imagesx( $source_image );
		$orig_height = imagesy( $source_image );

		// Calculate new dimensions
		$new_dimensions = self::calculate_resize_dimensions( $orig_width, $orig_height, $max_width, $max_height );

		// Create resized image
		$resized_image = imagecreatetruecolor( $new_dimensions['width'], $new_dimensions['height'] );

		// Preserve transparency for PNG and GIF
		if ( $image_type_const === IMAGETYPE_PNG || $image_type_const === IMAGETYPE_GIF ) {
			imagealphablending( $resized_image, false );
			imagesavealpha( $resized_image, true );
			$transparent = imagecolorallocatealpha( $resized_image, 255, 255, 255, 127 );
			imagefilledrectangle( $resized_image, 0, 0, $new_dimensions['width'], $new_dimensions['height'], $transparent );
		}

		// Resize image
		imagecopyresampled(
			$resized_image,
			$source_image,
			0,
			0,
			0,
			0,
			$new_dimensions['width'],
			$new_dimensions['height'],
			$orig_width,
			$orig_height
		);

		// Save image
		$save_result = false;
		switch ( $image_type_const ) {
			case IMAGETYPE_JPEG:
				$save_result = imagejpeg( $resized_image, $file_path, 90 );
				break;
			case IMAGETYPE_PNG:
				$save_result = imagepng( $resized_image, $file_path, 8 );
				break;
			case IMAGETYPE_GIF:
				$save_result = imagegif( $resized_image, $file_path );
				break;
			case IMAGETYPE_WEBP:
				$save_result = imagewebp( $resized_image, $file_path, 90 );
				break;
		}

		// Clean up memory
		imagedestroy( $source_image );
		imagedestroy( $resized_image );

		if ( ! $save_result ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to save processed image.', 'partyminder' ),
			);
		}

		return array( 'success' => true );
	}

	/**
	 * Calculate resize dimensions maintaining aspect ratio
	 */
	private static function calculate_resize_dimensions( $orig_width, $orig_height, $max_width, $max_height ) {
		$ratio = min( $max_width / $orig_width, $max_height / $orig_height );

		return array(
			'width'  => round( $orig_width * $ratio ),
			'height' => round( $orig_height * $ratio ),
		);
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
