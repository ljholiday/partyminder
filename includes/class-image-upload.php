<?php

/**
 * Image Upload Handler
 * Handles all image uploads: avatars, covers, event photos, conversation photos
 * Standard implementation following WordPress best practices
 */
class PartyMinder_Image_Upload {

	/**
	 * Handle AJAX avatar upload
	 */
	public static function handle_avatar_upload() {
		check_ajax_referer( 'partyminder_avatar_upload', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Not logged in' );
		}

		if ( ! isset( $_FILES['avatar'] ) ) {
			wp_send_json_error( 'No file uploaded' );
		}

		$result = self::process_upload( $_FILES['avatar'], 'avatar' );
		
		if ( $result['success'] ) {
			wp_send_json_success( array(
				'url' => $result['url'],
				'message' => 'Avatar uploaded successfully'
			) );
		} else {
			wp_send_json_error( $result['error'] );
		}
	}

	/**
	 * Handle AJAX cover upload
	 */
	public static function handle_cover_upload() {
		check_ajax_referer( 'partyminder_cover_upload', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Not logged in' );
		}

		if ( ! isset( $_FILES['cover'] ) ) {
			wp_send_json_error( 'No file uploaded' );
		}

		$result = self::process_upload( $_FILES['cover'], 'cover' );
		
		if ( $result['success'] ) {
			wp_send_json_success( array(
				'url' => $result['url'],
				'message' => 'Cover image uploaded successfully'
			) );
		} else {
			wp_send_json_error( $result['error'] );
		}
	}

	/**
	 * Handle AJAX event photo upload
	 */
	public static function handle_event_photo_upload() {
		check_ajax_referer( 'partyminder_event_photo_upload', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Not logged in' );
		}

		if ( ! isset( $_FILES['event_photo'] ) ) {
			wp_send_json_error( 'No file uploaded' );
		}

		$event_id = intval( $_POST['event_id'] ?? 0 );
		if ( ! $event_id ) {
			wp_send_json_error( 'Event ID required' );
		}

		$result = self::process_upload( $_FILES['event_photo'], 'events/' . $event_id );
		
		if ( $result['success'] ) {
			wp_send_json_success( array(
				'url' => $result['url'],
				'message' => 'Event photo uploaded successfully'
			) );
		} else {
			wp_send_json_error( $result['error'] );
		}
	}

	/**
	 * Handle AJAX conversation photo upload
	 */
	public static function handle_conversation_photo_upload() {
		check_ajax_referer( 'partyminder_conversation_photo_upload', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Not logged in' );
		}

		if ( ! isset( $_FILES['conversation_photo'] ) ) {
			wp_send_json_error( 'No file uploaded' );
		}

		$conversation_id = intval( $_POST['conversation_id'] ?? 0 );
		if ( ! $conversation_id ) {
			wp_send_json_error( 'Conversation ID required' );
		}

		$result = self::process_upload( $_FILES['conversation_photo'], 'conversations/' . $conversation_id );
		
		if ( $result['success'] ) {
			wp_send_json_success( array(
				'url' => $result['url'],
				'message' => 'Photo uploaded successfully'
			) );
		} else {
			wp_send_json_error( $result['error'] );
		}
	}

	/**
	 * Process image upload
	 */
	private static function process_upload( $file, $type ) {
		// Validate file
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return array( 'success' => false, 'error' => 'Upload error' );
		}

		if ( $file['size'] > 5 * 1024 * 1024 ) {
			return array( 'success' => false, 'error' => 'File too large (5MB max)' );
		}

		$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $file['type'], $allowed_types ) ) {
			return array( 'success' => false, 'error' => 'Invalid file type' );
		}

		// Create upload directory
		$upload_dir = wp_upload_dir();
		
		// Handle different path structures for events and conversations vs simple types
		if ( strpos( $type, '/' ) !== false ) {
			// For paths like 'events/123' or 'conversations/456'
			$pm_dir = $upload_dir['basedir'] . '/partyminder/' . $type . '/';
			$pm_url = $upload_dir['baseurl'] . '/partyminder/' . $type . '/';
		} else {
			// For simple types like 'avatar' or 'cover'
			$pm_dir = $upload_dir['basedir'] . '/partyminder/' . $type . 's/';
			$pm_url = $upload_dir['baseurl'] . '/partyminder/' . $type . 's/';
		}

		if ( ! wp_mkdir_p( $pm_dir ) ) {
			return array( 'success' => false, 'error' => 'Cannot create directory' );
		}

		// Generate filename
		$user_id = get_current_user_id();
		$extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
		
		// Clean the type for filename (remove slashes)
		$clean_type = str_replace( '/', '-', $type );
		$filename = $clean_type . '-' . $user_id . '-' . time() . '.' . strtolower( $extension );
		
		// Move file
		if ( move_uploaded_file( $file['tmp_name'], $pm_dir . $filename ) ) {
			// Use WordPress image editor to create scaled versions
			$image_editor = wp_get_image_editor( $pm_dir . $filename );
			if ( ! is_wp_error( $image_editor ) ) {
				// Get original dimensions
				$original_size = $image_editor->get_size();
				
				// Create a medium size (max 800px width, maintaining aspect ratio)
				$medium_filename = str_replace( '.' . strtolower( $extension ), '-medium.' . strtolower( $extension ), $filename );
				$image_editor->resize( 800, null, false );
				$image_editor->save( $pm_dir . $medium_filename );
				
				// Reset and create thumbnail (300x300, cropped)
				$image_editor = wp_get_image_editor( $pm_dir . $filename );
				if ( ! is_wp_error( $image_editor ) ) {
					$thumb_filename = str_replace( '.' . strtolower( $extension ), '-thumb.' . strtolower( $extension ), $filename );
					$image_editor->resize( 300, 300, true );
					$image_editor->save( $pm_dir . $thumb_filename );
				}
			}
			
			return array(
				'success' => true,
				'url' => $pm_url . $filename,
				'path' => $pm_dir . $filename,
				'medium_url' => $pm_url . ( isset( $medium_filename ) ? $medium_filename : $filename ),
				'thumb_url' => $pm_url . ( isset( $thumb_filename ) ? $thumb_filename : $filename )
			);
		}

		return array( 'success' => false, 'error' => 'Upload failed' );
	}

	/**
	 * Get uploaded photos for an event
	 */
	public static function get_event_photos( $event_id ) {
		$upload_dir = wp_upload_dir();
		$photos_dir = $upload_dir['basedir'] . '/partyminder/events/' . $event_id . '/';
		$photos_url = $upload_dir['baseurl'] . '/partyminder/events/' . $event_id . '/';
		
		if ( ! is_dir( $photos_dir ) ) {
			return array();
		}
		
		$photos = array();
		$files = glob( $photos_dir . '*' );
		
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				$filename = basename( $file );
				// Skip the generated medium and thumb versions in the main list
				if ( strpos( $filename, '-medium.' ) !== false || strpos( $filename, '-thumb.' ) !== false ) {
					continue;
				}
				
				$extension = pathinfo( $filename, PATHINFO_EXTENSION );
				$base_filename = str_replace( '.' . $extension, '', $filename );
				$medium_filename = $base_filename . '-medium.' . $extension;
				$thumb_filename = $base_filename . '-thumb.' . $extension;
				
				$photos[] = array(
					'url' => $photos_url . $filename,
					'medium_url' => file_exists( $photos_dir . $medium_filename ) ? $photos_url . $medium_filename : $photos_url . $filename,
					'thumb_url' => file_exists( $photos_dir . $thumb_filename ) ? $photos_url . $thumb_filename : $photos_url . $filename,
					'filename' => $filename,
					'uploaded' => filemtime( $file )
				);
			}
		}
		
		// Sort by upload time (newest first)
		usort( $photos, function( $a, $b ) {
			return $b['uploaded'] - $a['uploaded'];
		} );
		
		return $photos;
	}

	/**
	 * Get uploaded photos for a conversation
	 */
	public static function get_conversation_photos( $conversation_id ) {
		$upload_dir = wp_upload_dir();
		$photos_dir = $upload_dir['basedir'] . '/partyminder/conversations/' . $conversation_id . '/';
		$photos_url = $upload_dir['baseurl'] . '/partyminder/conversations/' . $conversation_id . '/';
		
		if ( ! is_dir( $photos_dir ) ) {
			return array();
		}
		
		$photos = array();
		$files = glob( $photos_dir . '*' );
		
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				$filename = basename( $file );
				// Skip the generated medium and thumb versions in the main list
				if ( strpos( $filename, '-medium.' ) !== false || strpos( $filename, '-thumb.' ) !== false ) {
					continue;
				}
				
				$extension = pathinfo( $filename, PATHINFO_EXTENSION );
				$base_filename = str_replace( '.' . $extension, '', $filename );
				$medium_filename = $base_filename . '-medium.' . $extension;
				$thumb_filename = $base_filename . '-thumb.' . $extension;
				
				$photos[] = array(
					'url' => $photos_url . $filename,
					'medium_url' => file_exists( $photos_dir . $medium_filename ) ? $photos_url . $medium_filename : $photos_url . $filename,
					'thumb_url' => file_exists( $photos_dir . $thumb_filename ) ? $photos_url . $thumb_filename : $photos_url . $filename,
					'filename' => $filename,
					'uploaded' => filemtime( $file )
				);
			}
		}
		
		// Sort by upload time (newest first)
		usort( $photos, function( $a, $b ) {
			return $b['uploaded'] - $a['uploaded'];
		} );
		
		return $photos;
	}
}