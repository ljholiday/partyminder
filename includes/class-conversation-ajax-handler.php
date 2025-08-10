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

		$topic_id     = intval( $_POST['topic_id'] ?? 0 );
		$event_id     = intval( $_POST['event_id'] ?? 0 );
		$community_id = intval( $_POST['community_id'] ?? 0 );
		$title        = sanitize_text_field( $_POST['title'] ?? '' );
		$content      = wp_kses_post( $_POST['content'] ?? '' );

		if ( $event_id && ! $topic_id ) {
			$conversation_manager = $this->get_conversation_manager();
			$party_planning_topic = $conversation_manager->get_topic_by_slug( 'party-planning' );
			if ( $party_planning_topic ) {
				$topic_id = $party_planning_topic->id;
			}
		}

		if ( empty( $topic_id ) || empty( $title ) || empty( $content ) ) {
			wp_send_json_error( __( 'Please fill in all required fields.', 'partyminder' ) );
		}

		$conversation_manager = $this->get_conversation_manager();

		$conversation_data = array(
			'topic_id'     => $topic_id,
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
		$content         = wp_kses_post( $_POST['content'] ?? '' );

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
}
