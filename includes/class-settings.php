<?php

class PartyMinder_Settings {

	/**
	 * Get maximum file size in bytes
	 *
	 * @return int File size limit in bytes
	 */
	public static function get_max_file_size() {
		$size_mb = get_option( 'partyminder_max_file_size_mb', 5 );
		return $size_mb * 1024 * 1024;
	}

	/**
	 * Get maximum file size in megabytes
	 *
	 * @return int File size limit in MB
	 */
	public static function get_max_file_size_mb() {
		return get_option( 'partyminder_max_file_size_mb', 5 );
	}

	/**
	 * Get file size limit description for user display
	 *
	 * @return string Description text
	 */
	public static function get_file_size_description() {
		$size_mb = self::get_max_file_size_mb();
		return sprintf( __( 'JPG, PNG, GIF, WebP up to %dMB each', 'partyminder' ), $size_mb );
	}

	/**
	 * Get file size limit error message
	 *
	 * @return string Error message
	 */
	public static function get_file_size_error_message() {
		$size_mb = self::get_max_file_size_mb();
		return sprintf( __( 'File size must be less than %dMB.', 'partyminder' ), $size_mb );
	}

	/**
	 * Validate uploaded file against PartyMinder requirements
	 *
	 * @param array $file File array from $_FILES
	 * @return WP_Error|true True on success, WP_Error on failure
	 */
	public static function validate_uploaded_file( $file ) {
		// Check for upload errors
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'upload_error', __( 'File upload error occurred.', 'partyminder' ) );
		}

		// Check file size
		if ( $file['size'] > self::get_max_file_size() ) {
			return new WP_Error( 'file_too_large', self::get_file_size_error_message() );
		}

		// Check file type
		$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $file['type'], $allowed_types ) ) {
			return new WP_Error( 'invalid_file_type', __( 'Only JPG, PNG, GIF, and WebP images are allowed.', 'partyminder' ) );
		}

		// Additional validation for image type
		$image_info = getimagesize( $file['tmp_name'] );
		if ( $image_info === false ) {
			return new WP_Error( 'invalid_image', __( 'Invalid image file.', 'partyminder' ) );
		}

		return true;
	}

}