<?php

class PartyMinder_Page_Content_Injector {

	public function __construct() {
		// This class will be instantiated and methods called by the main plugin class
	}

	private function should_inject_content( $page_type ) {
		global $post;

		if ( ! is_page() || ! in_the_loop() || ! is_main_query() ) {
			return false;
		}

		$current_page_type = get_post_meta( $post->ID, '_partyminder_page_type', true );
		return $current_page_type === $page_type;
	}

	public function inject_dashboard_content( $content ) {
		if ( ! $this->should_inject_content( 'dashboard' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-dashboard-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/dashboard-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_events_content( $content ) {
		if ( ! $this->should_inject_content( 'events' ) ) {
			return $content;
		}

		$event_action = get_query_var( 'event_action' );
		if ( $event_action === 'join' ) {
			ob_start();
			echo '<div class="partyminder-content partyminder-events-join-page">';
			
			// Check if using new token-based RSVP system or old invitation system
			$rsvp_token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
			$invitation_token = isset( $_GET['invitation'] ) ? sanitize_text_field( $_GET['invitation'] ) : '';
			
			if ( $rsvp_token ) {
				// New token-based RSVP landing page
				include PARTYMINDER_PLUGIN_DIR . 'templates/rsvp-landing-content.php';
			} else {
				// Legacy invitation-based RSVP
				include PARTYMINDER_PLUGIN_DIR . 'templates/event-rsvp-guest.php';
			}
			
			echo '</div>';
			return ob_get_clean();
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-events-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/events-unified-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_create_event_content( $content ) {
		if ( ! $this->should_inject_content( 'create-event' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-create-event-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/create-event-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_create_community_event_content( $content ) {
		if ( ! $this->should_inject_content( 'create-community-event' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-create-community-event-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/create-community-event-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_my_events_content( $content ) {
		if ( ! $this->should_inject_content( 'my-events' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-my-events-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/events-unified-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_edit_event_content( $content ) {
		if ( ! $this->should_inject_content( 'edit-event' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-edit-event-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/edit-event-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_manage_event_content( $content ) {
		if ( ! $this->should_inject_content( 'manage-event' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-manage-event-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/manage-event-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_create_conversation_content( $content ) {
		if ( ! $this->should_inject_content( 'create-conversation' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-create-conversation-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/create-conversation-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_create_community_content( $content ) {
		if ( ! $this->should_inject_content( 'create-community' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-create-community-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/create-community-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_create_group_content( $content ) {
		if ( ! $this->should_inject_content( 'create-group' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-create-group-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/create-group-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_profile_content( $content ) {
		if ( ! $this->should_inject_content( 'profile' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-profile-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/profile-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_login_content( $content ) {
		if ( ! $this->should_inject_content( 'login' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-login-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/login-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_conversations_content( $content ) {
		if ( ! $this->should_inject_content( 'conversations' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-conversations-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/conversations.php';
		echo '</div>';
		return ob_get_clean();
	}


	public function inject_single_conversation_content( $content ) {
		if ( ! $this->should_inject_content( 'conversations' ) ) {
			return $content;
		}

		$conversation_action = get_query_var( 'conversation_action' );
		
		ob_start();
		if ( $conversation_action === 'edit' ) {
			echo '<div class="partyminder-content partyminder-edit-conversation-page">';
			include PARTYMINDER_PLUGIN_DIR . 'templates/edit-conversation-content.php';
		} else {
			echo '<div class="partyminder-content partyminder-single-conversation-page">';
			include PARTYMINDER_PLUGIN_DIR . 'templates/single-conversation-content.php';
		}
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_communities_content( $content ) {
		if ( ! $this->should_inject_content( 'communities' ) ) {
			return $content;
		}

		if ( ! PartyMinder_Feature_Flags::is_communities_enabled() ) {
			return $this->inject_communities_disabled_content( $content );
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-communities-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/communities-unified-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_communities_disabled_content( $content ) {
		ob_start();
		echo '<div class="partyminder-content partyminder-communities-disabled-page">';
		echo '<div class="pm-card">';
		echo '<div class="pm-card-body pm-text-center">';
		echo '<h2>' . __( 'Communities Feature Not Available', 'partyminder' ) . '</h2>';
		echo '<p>' . __( 'The communities feature is not currently enabled on this site.', 'partyminder' ) . '</p>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_my_communities_content( $content ) {
		// Redirect to unified communities page
		return $this->inject_communities_content( $content );
	}

	public function inject_single_community_content( $content ) {
		if ( ! $this->should_inject_content( 'communities' ) ) {
			return $content;
		}

		// Set global variable for single community page (similar to single event)
		$GLOBALS['partyminder_is_single_community'] = true;

		ob_start();
		echo '<div class="partyminder-content partyminder-single-community-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/single-community-content.php';
		echo '</div>';
		return ob_get_clean();
	}


	public function inject_community_events_content( $content ) {
		if ( ! $this->should_inject_content( 'communities' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-community-events-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/community-events-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_community_conversations_content( $content ) {
		if ( ! $this->should_inject_content( 'communities' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-community-conversations-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/community-conversations-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_manage_community_content( $content ) {
		if ( ! $this->should_inject_content( 'manage-community' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-manage-community-page">';
		include PARTYMINDER_PLUGIN_DIR . 'templates/manage-community-content.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function inject_event_content( $content ) {
		if ( isset( $GLOBALS['partyminder_is_single_event'] ) && in_the_loop() && is_main_query() ) {
			ob_start();
			echo '<div class="partyminder-content partyminder-single-event-page">';
			include PARTYMINDER_PLUGIN_DIR . 'templates/single-event-content.php';
			echo '</div>';
			return ob_get_clean();
		}
		return $content;
	}

	public function inject_community_invitation_content( $content ) {
		if ( ! $this->should_inject_content( 'communities' ) ) {
			return $content;
		}

		ob_start();
		echo '<div class="partyminder-content partyminder-community-invitation-page">';

		// Check for token parameter
		$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';

		if ( $token ) {
			// Token-based invitation landing page
			include PARTYMINDER_PLUGIN_DIR . 'templates/community-invitation-accept.php';
		} else {
			// No token - redirect to communities page
			wp_safe_redirect( home_url( '/communities/' ) );
			exit;
		}

		echo '</div>';
		return ob_get_clean();
	}
}
