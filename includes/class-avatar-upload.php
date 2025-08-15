<?php

/**
 * Avatar and Cover Image Upload Handler
 * Standard implementation following WordPress best practices
 */
class PartyMinder_Avatar_Upload {

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
		$pm_dir = $upload_dir['basedir'] . '/partyminder/' . $type . 's/';
		$pm_url = $upload_dir['baseurl'] . '/partyminder/' . $type . 's/';

		if ( ! wp_mkdir_p( $pm_dir ) ) {
			return array( 'success' => false, 'error' => 'Cannot create directory' );
		}

		// Generate filename
		$user_id = get_current_user_id();
		$extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$filename = $type . '-' . $user_id . '-' . time() . '.' . strtolower( $extension );
		
		// Move file
		if ( move_uploaded_file( $file['tmp_name'], $pm_dir . $filename ) ) {
			return array(
				'success' => true,
				'url' => $pm_url . $filename,
				'path' => $pm_dir . $filename
			);
		}

		return array( 'success' => false, 'error' => 'Upload failed' );
	}
}