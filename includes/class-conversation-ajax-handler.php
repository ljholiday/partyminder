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
			} elseif ( $community_id ) {
				$community_manager = $this->get_community_manager();
				$community         = $community_manager->get_community( $community_id );
				if ( $community ) {
					$success_data['redirect_url'] = home_url( '/communities/' . $community->slug );
					$success_data['message']      = __( 'Community conversation created successfully!', 'partyminder' );
				}
			} else {
				// For general conversations, redirect to the new conversation page
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

		$conversation_manager = $this->get_conversation_manager();

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

		$circle = sanitize_text_field( $_POST['circle'] ?? 'close' );
		$filter = sanitize_text_field( $_POST['filter'] ?? '' );
		$topic_slug = sanitize_title( $_POST['topic_slug'] ?? '' );
		$page = max( 1, intval( $_POST['page'] ?? 1 ) );
		$per_page = 20;

		// Validate circle
		$allowed_circles = array( 'close', 'trusted', 'extended' );
		if ( ! in_array( $circle, $allowed_circles ) ) {
			$circle = 'close';
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
			// Use circle scope filtering
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-circle-scope.php';
			$scope = PartyMinder_Circle_Scope::resolve_conversation_scope( $current_user_id, $circle );
			$conversations = $conversation_manager->get_conversations_by_scope( $scope, $topic_slug, $page, $per_page );
			$total_conversations = $conversation_manager->get_conversations_count_by_scope( $scope, $topic_slug );
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

		wp_send_json_success( array(
			'message' => __( 'Conversation updated successfully!', 'partyminder' ),
			'conversation_id' => $conversation_id
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
		$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $file['type'], $allowed_types ) ) {
			return new WP_Error( 'invalid_file_type', __( 'Only JPG, PNG, GIF, and WebP images are allowed.', 'partyminder' ) );
		}

		if ( $file['size'] > 5 * 1024 * 1024 ) { // 5MB limit
			return new WP_Error( 'file_too_large', __( 'File size must be less than 5MB.', 'partyminder' ) );
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

}
