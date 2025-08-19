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
		add_action( 'wp_ajax_partyminder_add_reply', array( $this, 'ajax_add_reply' ) );
		add_action( 'wp_ajax_nopriv_partyminder_add_reply', array( $this, 'ajax_add_reply' ) );
		add_action( 'wp_ajax_partyminder_delete_reply', array( $this, 'ajax_delete_reply' ) );
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
			}

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
		error_log( "PartyMinder: ajax_delete_reply called" );
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'partyminder_nonce' ) ) {
			error_log( "PartyMinder: Ajax delete - nonce verification failed" );
			wp_send_json_error( __( 'Security verification failed.', 'partyminder' ) );
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			error_log( "PartyMinder: Ajax delete - user not logged in" );
			wp_send_json_error( __( 'You must be logged in to delete replies.', 'partyminder' ) );
		}

		$reply_id = intval( $_POST['reply_id'] ?? 0 );
		if ( ! $reply_id ) {
			error_log( "PartyMinder: Ajax delete - invalid reply ID" );
			wp_send_json_error( __( 'Invalid reply ID.', 'partyminder' ) );
		}

		error_log( "PartyMinder: Ajax delete proceeding with reply ID: $reply_id" );
		
		$conversation_manager = $this->get_conversation_manager();
		$result = $conversation_manager->delete_reply( $reply_id );

		if ( $result ) {
			error_log( "PartyMinder: Ajax delete SUCCESS" );
			wp_send_json_success(
				array(
					'message' => __( 'Reply deleted successfully.', 'partyminder' ),
				)
			);
		} else {
			error_log( "PartyMinder: Ajax delete FAILED - sending error response" );
			wp_send_json_error( __( 'Failed to delete reply. You may not have permission to delete this reply.', 'partyminder' ) );
		}
	}
}
