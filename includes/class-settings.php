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

}