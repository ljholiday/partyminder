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

		$user_id = get_current_user_id();
		
		// Use the unified Image Manager system
		$upload_result = PartyMinder_Image_Manager::handle_image_upload( $_FILES['avatar'], 'profile', $user_id, 'user' );
		
		if ( $upload_result['success'] ) {
			// Get current profile and delete old image
			$current_profile = PartyMinder_Profile_Manager::get_user_profile( $user_id );
			if ( ! empty( $current_profile['profile_image'] ) ) {
				PartyMinder_Image_Manager::delete_image( $current_profile['profile_image'] );
			}
			
			// Save to profile using Profile Manager and set avatar source to custom
			$profile_update = PartyMinder_Profile_Manager::update_profile( $user_id, array( 
				'profile_image' => $upload_result['url'],
				'avatar_source' => 'custom'
			) );
			
			if ( $profile_update['success'] ) {
				wp_send_json_success( array(
					'url' => $upload_result['url'],
					'message' => 'Avatar uploaded successfully'
				) );
			} else {
				// Clean up uploaded file if profile save failed
				PartyMinder_Image_Manager::delete_image( $upload_result['url'] );
				wp_send_json_error( 'Failed to save avatar to profile' );
			}
		} else {
			wp_send_json_error( $upload_result['error'] );
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

		$user_id = get_current_user_id();
		
		// Use the unified Image Manager system
		$upload_result = PartyMinder_Image_Manager::handle_image_upload( $_FILES['cover'], 'cover', $user_id, 'user' );
		
		if ( $upload_result['success'] ) {
			// Get current profile and delete old image
			$current_profile = PartyMinder_Profile_Manager::get_user_profile( $user_id );
			if ( ! empty( $current_profile['cover_image'] ) ) {
				PartyMinder_Image_Manager::delete_image( $current_profile['cover_image'] );
			}
			
			// Save to profile using Profile Manager
			$profile_update = PartyMinder_Profile_Manager::update_profile( $user_id, array( 'cover_image' => $upload_result['url'] ) );
			
			if ( $profile_update['success'] ) {
				wp_send_json_success( array(
					'url' => $upload_result['url'],
					'message' => 'Cover image uploaded successfully'
				) );
			} else {
				// Clean up uploaded file if profile save failed
				PartyMinder_Image_Manager::delete_image( $upload_result['url'] );
				wp_send_json_error( 'Failed to save cover image to profile' );
			}
		} else {
			wp_send_json_error( $upload_result['error'] );
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

		$user_id = get_current_user_id();
		
		// Use the unified Image Manager system
		$upload_result = PartyMinder_Image_Manager::handle_image_upload( $_FILES['event_photo'], 'post', $user_id, 'post', $event_id );
		
		if ( $upload_result['success'] ) {
			// Save image metadata to database
			$image_id = PartyMinder_Image_Manager::save_post_image_metadata( 
				$event_id, 
				$user_id, 
				$upload_result,
				sanitize_textarea_field( $_POST['caption'] ?? '' ),
				sanitize_text_field( $_POST['alt_text'] ?? '' )
			);
			
			if ( $image_id ) {
				wp_send_json_success( array(
					'url' => $upload_result['url'],
					'image_id' => $image_id,
					'message' => 'Event photo uploaded successfully'
				) );
			} else {
				// Clean up uploaded file if database save failed
				PartyMinder_Image_Manager::delete_image( $upload_result['url'] );
				wp_send_json_error( 'Failed to save image metadata' );
			}
		} else {
			wp_send_json_error( $upload_result['error'] );
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

		$user_id = get_current_user_id();
		
		// Use the unified Image Manager system for conversation uploads
		$upload_result = PartyMinder_Image_Manager::handle_image_upload( $_FILES['conversation_photo'], 'post', $user_id, 'conversation', $conversation_id );
		
		if ( $upload_result['success'] ) {
			wp_send_json_success( array(
				'url' => $upload_result['url'],
				'message' => 'Photo uploaded successfully'
			) );
		} else {
			wp_send_json_error( $upload_result['error'] );
		}
	}

	/**
	 * Handle AJAX community cover upload
	 */
	public static function handle_community_cover_upload() {
		check_ajax_referer( 'partyminder_community_cover_upload', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Not logged in' );
		}

		if ( ! isset( $_FILES['community_cover'] ) ) {
			wp_send_json_error( 'No file uploaded' );
		}

		$community_id = intval( $_POST['community_id'] ?? 0 );
		if ( ! $community_id ) {
			wp_send_json_error( 'Community ID required' );
		}

		$user_id = get_current_user_id();
		
		// Use the unified Image Manager system
		$upload_result = PartyMinder_Image_Manager::handle_image_upload( $_FILES['community_cover'], 'cover', $community_id, 'community' );
		
		if ( $upload_result['success'] ) {
			wp_send_json_success( array(
				'url' => $upload_result['url'],
				'message' => 'Community cover uploaded successfully'
			) );
		} else {
			wp_send_json_error( $upload_result['error'] );
		}
	}

	/**
	 * Handle AJAX event cover upload
	 */
	public static function handle_event_cover_upload() {
		check_ajax_referer( 'partyminder_event_cover_upload', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Not logged in' );
		}

		if ( ! isset( $_FILES['event_cover'] ) ) {
			wp_send_json_error( 'No file uploaded' );
		}

		$event_id = intval( $_POST['event_id'] ?? 0 );
		if ( ! $event_id ) {
			wp_send_json_error( 'Event ID required' );
		}

		$user_id = get_current_user_id();
		
		// Use the unified Image Manager system
		$upload_result = PartyMinder_Image_Manager::handle_image_upload( $_FILES['event_cover'], 'cover', $event_id, 'event' );
		
		if ( $upload_result['success'] ) {
			wp_send_json_success( array(
				'url' => $upload_result['url'],
				'message' => 'Event cover uploaded successfully'
			) );
		} else {
			wp_send_json_error( $upload_result['error'] );
		}
	}

	/**
	 * Get uploaded photos for an event
	 * Uses Image Manager's database-based system for event post images
	 */
	public static function get_event_photos( $event_id ) {
		return PartyMinder_Image_Manager::get_event_post_images( $event_id );
	}

	/**
	 * Get uploaded photos for a conversation
	 * Placeholder for future conversation image database integration
	 */
	public static function get_conversation_photos( $conversation_id ) {
		// For now, use filesystem-based approach for conversations
		// TODO: Implement conversation image database table similar to post images
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
				$photos[] = array(
					'url' => $photos_url . $filename,
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