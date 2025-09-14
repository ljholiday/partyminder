<?php

class PartyMinder_Conversation_Ajax_Handler {

	private $conversation_manager;
	private $event_manager;
	private $community_manager;

	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'wp_ajax_partyminder_create_conversation', array( $this, 'ajax_create_conversation' ) );
		add_action( 'wp_ajax_nopriv_partyminder_create_conversation', array( $this, 'ajax_create_conversation' ) );
		add_action( 'wp_ajax_partyminder_update_conversation', array( $this, 'ajax_update_conversation' ) );
		add_action( 'wp_ajax_partyminder_delete_conversation', array( $this, 'ajax_delete_conversation' ) );
		add_action( 'wp_ajax_partyminder_add_reply', array( $this, 'ajax_add_reply' ) );
		add_action( 'wp_ajax_nopriv_partyminder_add_reply', array( $this, 'ajax_add_reply' ) );
		add_action( 'wp_ajax_partyminder_update_reply', array( $this, 'ajax_update_reply' ) );
		add_action( 'wp_ajax_partyminder_delete_reply', array( $this, 'ajax_delete_reply' ) );
		add_action( 'wp_ajax_partyminder_get_conversations', array( $this, 'ajax_get_conversations' ) );
		add_action( 'wp_ajax_nopriv_partyminder_get_conversations', array( $this, 'ajax_get_conversations' ) );
	}

	private function get_conversation_manager() {
		if ( ! $this->conversation_manager ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
			$this->conversation_manager = new PartyMinder_Conversation_Manager();
		}
		return $this->conversation_manager;
	}

	private function get_event_manager() {
		if ( ! $this->event_manager ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
			$this->event_manager = new PartyMinder_Event_Manager();
		}
		return $this->event_manager;
	}

	private function get_community_manager() {
		if ( ! $this->community_manager ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
			$this->community_manager = new PartyMinder_Community_Manager();
		}
		return $this->community_manager;
	}

	public function ajax_create_conversation() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		$current_user = wp_get_current_user();
		$user_email   = '';
		$user_name    = '';
		$user_id      = 0;

		if ( is_user_logged_in() ) {
			$user_email = $current_user->user_email;
			$user_name  = $current_user->display_name;
			$user_id    = $current_user->ID;
		} else {
			$user_email = sanitize_email( $_POST['guest_email'] ?? '' );
			$user_name  = sanitize_text_field( $_POST['guest_name'] ?? '' );
			if ( empty( $user_email ) || empty( $user_name ) ) {
				wp_send_json_error( __( 'Please provide your name and email to start a conversation.', 'partyminder' ) );
			}
		}

		$event_id     = intval( $_POST['event_id'] ?? 0 );
		$community_id = intval( $_POST['community_id'] ?? 0 );
		$title        = sanitize_text_field( $_POST['title'] ?? '' );
		$content      = $_POST['content'] ?? '';

		if ( empty( $title ) || empty( $content ) ) {
			wp_send_json_error( __( 'Please fill in all required fields.', 'partyminder' ) );
		}

		// Step 4: If no community selected, default to author's personal community
		if ( ! $community_id && $user_id && PartyMinder_Feature_Flags::is_general_convo_default_to_personal_enabled() ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-personal-community-service.php';
			$personal_community = PartyMinder_Personal_Community_Service::get_personal_community_for_user( $user_id );
			if ( $personal_community ) {
				$community_id = $personal_community->id;
			}
		}

		$conversation_manager = $this->get_conversation_manager();

		$conversation_data = array(
			'event_id'     => $event_id ?: null,
			'community_id' => $community_id ?: null,
			'title'        => $title,
			'content'      => $content,
			'author_id'    => $user_id,
			'author_name'  => $user_name,
			'author_email' => $user_email,
		);

		$conversation_id = $conversation_manager->create_conversation( $conversation_data );

		if ( $conversation_id ) {
			// Handle cover image upload
			if ( isset( $_FILES['cover_image'] ) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK ) {
				$upload_result = $this->handle_cover_image_upload( $_FILES['cover_image'], $conversation_id );
				if ( is_wp_error( $upload_result ) ) {
					// Log error but don't fail the conversation creation
					error_log( 'Cover image upload failed: ' . $upload_result->get_error_message() );
				}
			}

			// Get the created conversation for URL generation
			$conversation = $conversation_manager->get_conversation_by_id( $conversation_id );
			
			$success_data = array(
				'conversation_id' => $conversation_id,
				'message'         => __( 'Conversation started successfully!', 'partyminder' ),
			);

			if ( $event_id ) {
				$event_manager = $this->get_event_manager();
				$event         = $event_manager->get_event( $event_id );
				if ( $event ) {
					$success_data['redirect_url'] = home_url( '/events/' . $event->slug );
					$success_data['message']      = __( 'Event conversation created successfully!', 'partyminder' );
				}
			} elseif ( $community_id && ! empty( $_POST['community_id'] ) ) {
				// Only redirect to community if community was explicitly selected in the form
				$community_manager = $this->get_community_manager();
				$community         = $community_manager->get_community( $community_id );
				if ( $community ) {
					$success_data['redirect_url'] = home_url( '/communities/' . $community->slug );
					$success_data['message']      = __( 'Community conversation created successfully!', 'partyminder' );
				}
			} else {
				// For general conversations (including those auto-assigned to personal community), redirect to the conversation
				if ( $conversation ) {
					$success_data['redirect_url'] = home_url( '/conversations/' . $conversation->slug );
				}
			}

			// Store success data in transient for non-AJAX fallback
			$transient_key = 'partyminder_conversation_created_' . ( is_user_logged_in() ? get_current_user_id() : session_id() );
			set_transient( $transient_key, array(
				'id' => $conversation_id,
				'url' => $success_data['redirect_url'] ?? '',
				'message' => $success_data['message']
			), 300 ); // 5 minutes

			wp_send_json_success( $success_data );
		} else {
			wp_send_json_error( __( 'Failed to create conversation. Please try again.', 'partyminder' ) );
		}
	}

	public function ajax_add_reply() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		$current_user = wp_get_current_user();
		$user_email   = '';
		$user_name    = '';
		$user_id      = 0;

		if ( is_user_logged_in() ) {
			$user_email = $current_user->user_email;
			$user_name  = $current_user->display_name;
			$user_id    = $current_user->ID;
		} else {
			$user_email = sanitize_email( $_POST['guest_email'] ?? '' );
			$user_name  = sanitize_text_field( $_POST['guest_name'] ?? '' );
			if ( empty( $user_email ) || empty( $user_name ) ) {
				wp_send_json_error( __( 'Please provide your name and email to reply.', 'partyminder' ) );
			}
		}

		$conversation_id = intval( $_POST['conversation_id'] ?? 0 );
		$parent_reply_id = intval( $_POST['parent_reply_id'] ?? 0 ) ?: null;
		$content         = $_POST['content'] ?? '';

		if ( empty( $conversation_id ) || empty( $content ) ) {
			wp_send_json_error( __( 'Please provide a message to reply.', 'partyminder' ) );
		}

		// Handle file attachments if present
		if ( isset( $_FILES['attachments'] ) && is_array( $_FILES['attachments']['name'] ) ) {
			$upload_overrides = array( 'test_form' => false );
			
			for ( $i = 0; $i < count( $_FILES['attachments']['name'] ); $i++ ) {
				if ( $_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK ) {
					// Prepare individual file array for wp_handle_upload
					$file = array(
						'name'     => $_FILES['attachments']['name'][$i],
						'type'     => $_FILES['attachments']['type'][$i],
						'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
						'error'    => $_FILES['attachments']['error'][$i],
						'size'     => $_FILES['attachments']['size'][$i]
					);
					
					$uploaded_file = wp_handle_upload( $file, $upload_overrides );
					
					if ( ! isset( $uploaded_file['error'] ) ) {
						$attachment_url = $uploaded_file['url'];
						
						// Add image to content if it's an image file
						if ( strpos( $file['type'], 'image/' ) === 0 ) {
							$content .= "\n\n<img src=\"" . esc_url( $attachment_url ) . "\" alt=\"Attached image\" style=\"max-width: 100%; height: auto; border-radius: 0.375rem;\">";
						} else {
							// For non-images, add as a download link
							$filename = sanitize_file_name( $file['name'] );
							$content .= "\n\n<a href=\"" . esc_url( $attachment_url ) . "\" target=\"_blank\">📎 " . esc_html( $filename ) . "</a>";
						}
					}
				}
			}
		}

		$conversation_manager = $this->get_conversation_manager();

		// Step 5: Handle reply join flow before posting
		if ( $user_id ) {
			$join_result = $this->handle_reply_join_flow( $conversation_id, $user_id );
			if ( is_wp_error( $join_result ) ) {
				wp_send_json_error( $join_result->get_error_message() );
			}
		}

		$reply_data = array(
			'content'         => $content,
			'author_id'       => $user_id,
			'author_name'     => $user_name,
			'author_email'    => $user_email,
			'parent_reply_id' => $parent_reply_id,
		);

		$reply_id = $conversation_manager->add_reply( $conversation_id, $reply_data );

		if ( $reply_id ) {
			wp_send_json_success(
				array(
					'reply_id' => $reply_id,
					'message'  => __( 'Reply added successfully!', 'partyminder' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to add reply. Please try again.', 'partyminder' ) );
		}
	}

	/**
	 * Handle delete reply AJAX request
	 */
	public function ajax_delete_reply() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'partyminder_nonce' ) ) {
			wp_send_json_error( __( 'Security verification failed.', 'partyminder' ) );
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in to delete replies.', 'partyminder' ) );
		}

		$reply_id = intval( $_POST['reply_id'] ?? 0 );
		if ( ! $reply_id ) {
			wp_send_json_error( __( 'Invalid reply ID.', 'partyminder' ) );
		}

		$conversation_manager = $this->get_conversation_manager();
		$result = $conversation_manager->delete_reply( $reply_id );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message' => __( 'Reply deleted successfully.', 'partyminder' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to delete reply. You may not have permission to delete this reply.', 'partyminder' ) );
		}
	}

	/**
	 * Handle get conversations by circle AJAX request
	 */
	public function ajax_get_conversations() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'partyminder_nonce' ) ) {
			wp_send_json_error( __( 'Security verification failed.', 'partyminder' ) );
		}

		$circle = sanitize_text_field( $_POST['circle'] ?? 'inner' );
		$filter = sanitize_text_field( $_POST['filter'] ?? '' );
		$topic_slug = sanitize_title( $_POST['topic_slug'] ?? '' );
		$page = max( 1, intval( $_POST['page'] ?? 1 ) );
		$per_page = 20;

		// Validate circle
		$allowed_circles = array( 'inner', 'trusted', 'extended' );
		if ( ! in_array( $circle, $allowed_circles ) ) {
			$circle = 'inner';
		}

		// Validate filter
		$allowed_filters = array( '', 'events', 'communities' );
		if ( ! in_array( $filter, $allowed_filters ) ) {
			$filter = '';
		}

		$conversation_manager = $this->get_conversation_manager();
		$current_user_id = get_current_user_id();

		// Handle different filter types
		if ( $filter === 'events' ) {
			// Get event conversations
			$conversations = $conversation_manager->get_event_conversations( null, $per_page, ( $page - 1 ) * $per_page );
			// Get total count for pagination
			global $wpdb;
			$conversations_table = $wpdb->prefix . 'partyminder_conversations';
			$total_conversations = $wpdb->get_var( "SELECT COUNT(*) FROM $conversations_table WHERE event_id IS NOT NULL" );
		} elseif ( $filter === 'communities' ) {
			// Get community conversations
			$conversations = $conversation_manager->get_community_conversations( null, $per_page, ( $page - 1 ) * $per_page );
			// Get total count for pagination
			global $wpdb;
			$conversations_table = $wpdb->prefix . 'partyminder_conversations';
			$total_conversations = $wpdb->get_var( "SELECT COUNT(*) FROM $conversations_table WHERE community_id IS NOT NULL" );
		} else {
			// Use new ConversationFeed with circles integration
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-feed.php';
			$opts = array(
				'topic_slug' => $topic_slug,
				'page' => $page,
				'per_page' => $per_page
			);
			$feed_result = PartyMinder_Conversation_Feed::list( $current_user_id, $circle, $opts );
			$conversations = $feed_result['conversations'];
			$total_conversations = $feed_result['meta']['total'];
		}
		$total_pages = ceil( $total_conversations / $per_page );
		$has_more = $page < $total_pages;

		// Render the list using the partial
		ob_start();
		include PARTYMINDER_PLUGIN_DIR . 'templates/partials/conversations-list.php';
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html' => $html,
			'meta' => array(
				'count' => $total_conversations,
				'page' => $page,
				'has_more' => $has_more,
				'circle' => $circle,
				'filter' => $filter
			)
		) );
	}

	public function ajax_update_conversation() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in to edit conversations.', 'partyminder' ) );
		}

		$conversation_id = intval( $_POST['conversation_id'] ?? 0 );
		$title = sanitize_text_field( $_POST['title'] ?? '' );
		$content = wp_kses_post( $_POST['content'] ?? '' );
		$privacy = sanitize_text_field( $_POST['privacy'] ?? 'public' );

		if ( ! $conversation_id || ! $title || ! $content ) {
			wp_send_json_error( __( 'All fields are required.', 'partyminder' ) );
		}

		$conversation_manager = $this->get_conversation_manager();
		$conversation = $conversation_manager->get_conversation_by_id( $conversation_id );

		if ( ! $conversation ) {
			wp_send_json_error( __( 'Conversation not found.', 'partyminder' ) );
		}

		// Check permissions
		$current_user = wp_get_current_user();
		$can_edit = ( $current_user->ID == $conversation->author_id ) || current_user_can( 'manage_options' );

		if ( ! $can_edit ) {
			wp_send_json_error( __( 'You do not have permission to edit this conversation.', 'partyminder' ) );
		}

		// Handle cover image upload
		if ( isset( $_FILES['cover_image'] ) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK ) {
			$upload_result = $this->handle_cover_image_upload( $_FILES['cover_image'], $conversation_id );
			if ( is_wp_error( $upload_result ) ) {
				wp_send_json_error( $upload_result->get_error_message() );
			}
		}

		// Handle cover image removal
		if ( isset( $_POST['remove_cover_image'] ) && $_POST['remove_cover_image'] === '1' ) {
			global $wpdb;
			$conversations_table = $wpdb->prefix . 'partyminder_conversations';
			$wpdb->update(
				$conversations_table,
				array( 'featured_image' => '' ),
				array( 'id' => $conversation_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		$update_data = array(
			'title' => $title,
			'content' => $content,
		);

		// Only update privacy for standalone conversations
		if ( ! $conversation->event_id && ! $conversation->community_id ) {
			$update_data['privacy'] = $privacy;
		}

		$result = $conversation_manager->update_conversation( $conversation_id, $update_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Store success message
		set_transient( 'partyminder_conversation_updated_' . get_current_user_id(), array(
			'conversation_id' => $conversation_id,
			'message' => __( 'Conversation updated successfully!', 'partyminder' )
		), 300 );

		// Get updated conversation data to return new slug
		$updated_conversation = $conversation_manager->get_conversation_by_id( $conversation_id );
		
		wp_send_json_success( array(
			'message' => __( 'Conversation updated successfully!', 'partyminder' ),
			'conversation_id' => $conversation_id,
			'slug' => $updated_conversation->slug
		) );
	}

	public function ajax_delete_conversation() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in to delete conversations.', 'partyminder' ) );
		}

		$conversation_id = intval( $_POST['conversation_id'] ?? 0 );

		if ( ! $conversation_id ) {
			wp_send_json_error( __( 'Conversation ID is required.', 'partyminder' ) );
		}

		$conversation_manager = $this->get_conversation_manager();
		$conversation = $conversation_manager->get_conversation_by_id( $conversation_id );

		if ( ! $conversation ) {
			wp_send_json_error( __( 'Conversation not found.', 'partyminder' ) );
		}

		// Check permissions
		$current_user = wp_get_current_user();
		$can_delete = ( $current_user->ID == $conversation->author_id ) || current_user_can( 'manage_options' );

		if ( ! $can_delete ) {
			wp_send_json_error( __( 'You do not have permission to delete this conversation.', 'partyminder' ) );
		}

		$result = $conversation_manager->delete_conversation( $conversation_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array(
			'message' => __( 'Conversation deleted successfully!', 'partyminder' )
		) );
	}

	private function handle_cover_image_upload( $file, $conversation_id ) {
		// Validate file
		$validation_result = PartyMinder_Settings::validate_uploaded_file( $file );
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Handle upload
		$upload_overrides = array( 'test_form' => false );
		$uploaded_file = wp_handle_upload( $file, $upload_overrides );

		if ( isset( $uploaded_file['error'] ) ) {
			return new WP_Error( 'upload_error', $uploaded_file['error'] );
		}

		// Update conversation with cover image
		global $wpdb;
		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$wpdb->update(
			$conversations_table,
			array( 'featured_image' => $uploaded_file['url'] ),
			array( 'id' => $conversation_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $uploaded_file['url'];
	}

	/**
	 * Handle reply join flow logic
	 * Step 5: Auto-join, pending approval, or access request based on community settings
	 */
	private function handle_reply_join_flow( $conversation_id, $user_id ) {
		// Step 5: Spam protection - rate limit join attempts
		$join_attempts_key = 'partyminder_join_attempts_' . $user_id;
		$recent_attempts = get_transient( $join_attempts_key );
		
		if ( $recent_attempts && $recent_attempts >= 5 ) {
			return new WP_Error( 'rate_limited', __( 'Too many join attempts. Please wait before trying again.', 'partyminder' ) );
		}
		
		// Get the conversation to find its community
		$conversation_manager = $this->get_conversation_manager();
		$conversation = $conversation_manager->get_conversation( $conversation_id );
		
		if ( ! $conversation || ! $conversation->community_id ) {
			// No community - allow reply (general conversation)
			return true;
		}
		
		// Check if user is already a member
		$community_manager = $this->get_community_manager();
		$member_role = $community_manager->get_member_role( $conversation->community_id, $user_id );
		
		if ( $member_role && $member_role !== 'blocked' ) {
			// User is already a member - allow reply
			return true;
		}
		
		// Get community details
		$community = $community_manager->get_community( $conversation->community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}
		
		// Handle based on community visibility and settings
		switch ( $community->visibility ) {
			case 'public':
				// Public community - auto-join if allowed
				if ( $community_manager->allows_auto_join_on_reply( $conversation->community_id ) ) {
					// Track join attempt for spam protection
					$this->track_join_attempt( $user_id );
					
					$join_result = $community_manager->join_community( $conversation->community_id, $user_id );
					if ( is_wp_error( $join_result ) ) {
						return new WP_Error( 'auto_join_failed', __( 'Failed to join community', 'partyminder' ) );
					}
					return true;
				} else {
					return new WP_Error( 'membership_required', __( 'You must be a member to reply in this community', 'partyminder' ) );
				}
				break;
				
			case 'private':
				// Private community - provide contact info for access request
				$creator_user = get_user_by( 'id', $community->creator_id );
				$contact_info = $creator_user ? $creator_user->display_name : __( 'the community administrator', 'partyminder' );

				$message = sprintf(
					__( 'This is a private community. To request access, contact %s or email %s', 'partyminder' ),
					$contact_info,
					get_option( 'admin_email' )
				);

				return new WP_Error( 'access_restricted', $message );
				break;
		}
		
		return new WP_Error( 'unknown_visibility', __( 'Unknown community visibility setting', 'partyminder' ) );
	}

	/**
	 * Track join attempts for spam protection
	 * Step 5: Prevent abuse of auto-join feature
	 */
	private function track_join_attempt( $user_id ) {
		$join_attempts_key = 'partyminder_join_attempts_' . $user_id;
		$recent_attempts = get_transient( $join_attempts_key );
		$attempts = $recent_attempts ? (int) $recent_attempts + 1 : 1;
		
		// Track attempts for 1 hour
		set_transient( $join_attempts_key, $attempts, HOUR_IN_SECONDS );
	}

	public function ajax_update_reply() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in to edit replies.', 'partyminder' ) );
		}

		$reply_id = intval( $_POST['reply_id'] ?? 0 );
		$content = sanitize_textarea_field( $_POST['content'] ?? '' );

		if ( ! $reply_id || empty( $content ) ) {
			wp_send_json_error( __( 'Reply ID and content are required.', 'partyminder' ) );
		}

		$conversation_manager = $this->get_conversation_manager();
		
		// Get reply to check ownership
		$reply = $conversation_manager->get_reply( $reply_id );
		if ( ! $reply ) {
			wp_send_json_error( __( 'Reply not found.', 'partyminder' ) );
		}

		$current_user = wp_get_current_user();
		
		// Check if user owns this reply
		if ( $reply->author_id != $current_user->ID ) {
			wp_send_json_error( __( 'You can only edit your own replies.', 'partyminder' ) );
		}

		// Get preserved images from original content (excluding removed ones)
		$original_content = $reply->content;
		$preserved_images = '';
		
		// Extract images from original content
		preg_match_all( '/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $original_content, $image_matches );
		if ( ! empty( $image_matches[0] ) ) {
			$removed_images = array();
			if ( isset( $_POST['removed_images'] ) ) {
				$removed_images = json_decode( stripslashes( $_POST['removed_images'] ), true );
				if ( ! is_array( $removed_images ) ) {
					$removed_images = array();
				}
			}
			
			foreach ( $image_matches[0] as $i => $img_tag ) {
				$img_src = $image_matches[1][$i];
				if ( ! in_array( $img_src, $removed_images ) ) {
					$preserved_images .= "\n\n" . $img_tag;
				}
			}
		}
		
		// Start with new text content and add preserved images
		$content = $content . $preserved_images;

		// Handle file attachments if present
		if ( isset( $_FILES['attachments'] ) && is_array( $_FILES['attachments']['name'] ) ) {
			$upload_overrides = array( 'test_form' => false );
			
			for ( $i = 0; $i < count( $_FILES['attachments']['name'] ); $i++ ) {
				if ( $_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK ) {
					// Prepare individual file array for wp_handle_upload
					$file = array(
						'name'     => $_FILES['attachments']['name'][$i],
						'type'     => $_FILES['attachments']['type'][$i],
						'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
						'error'    => $_FILES['attachments']['error'][$i],
						'size'     => $_FILES['attachments']['size'][$i]
					);
					
					$uploaded_file = wp_handle_upload( $file, $upload_overrides );
					
					if ( ! isset( $uploaded_file['error'] ) ) {
						$attachment_url = $uploaded_file['url'];
						
						// Add image to content if it's an image file
						if ( strpos( $file['type'], 'image/' ) === 0 ) {
							$content .= "\n\n<img src=\"" . esc_url( $attachment_url ) . "\" alt=\"Attached image\" style=\"max-width: 100%; height: auto; border-radius: 0.375rem;\">";
						} else {
							// For non-images, add as a download link
							$filename = sanitize_file_name( $file['name'] );
							$content .= "\n\n<a href=\"" . esc_url( $attachment_url ) . "\" target=\"_blank\">📎 " . esc_html( $filename ) . "</a>";
						}
					}
				}
			}
		}

		// Update the reply
		$result = $conversation_manager->update_reply( $reply_id, array( 'content' => $content ) );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array(
			'message' => __( 'Reply updated successfully.', 'partyminder' ),
			'content' => $content
		) );
	}

}
