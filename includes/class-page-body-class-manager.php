<?php

class PartyMinder_Page_Body_Class_Manager {

	public function __construct() {
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );
	}

	public function add_body_classes( $classes ) {
		$classes[] = 'partyminder';
		return $classes;
	}

	public function add_dashboard_body_class( $classes ) {
		$classes[] = 'partyminder-dashboard';
		return $classes;
	}

	public function add_events_body_class( $classes ) {
		$classes[] = 'partyminder-events-listing';
		return $classes;
	}

	public function add_create_event_body_class( $classes ) {
		$classes[] = 'partyminder-event-creation';
		return $classes;
	}

	public function add_my_events_body_class( $classes ) {
		$classes[] = 'partyminder-user-dashboard';
		return $classes;
	}

	public function add_edit_event_body_class( $classes ) {
		$classes[] = 'partyminder-event-editing';
		return $classes;
	}

	public function add_manage_event_body_class( $classes ) {
		$classes[] = 'partyminder-event-management';
		return $classes;
	}

	public function add_login_body_class( $classes ) {
		$classes[] = 'partyminder-login';
		return $classes;
	}

	public function add_profile_body_class( $classes ) {
		$classes[] = 'partyminder-profile';
		$user_id   = get_query_var( 'user', get_current_user_id() );
		if ( $user_id && $user_id !== get_current_user_id() ) {
			$classes[] = 'partyminder-profile-view';
		} else {
			$classes[] = 'partyminder-profile-own';
		}
		return $classes;
	}

	public function add_conversations_body_class( $classes ) {
		$classes[] = 'partyminder-conversations-listing';
		return $classes;
	}


	public function add_single_conversation_body_class( $classes ) {
		$classes[] = 'partyminder-conversations';
		$classes[] = 'partyminder-single-conversation';
		return $classes;
	}

	public function add_create_conversation_body_class( $classes ) {
		$classes[] = 'partyminder-conversations';
		$classes[] = 'partyminder-conversation-creation';

		$event_id     = get_query_var( 'event_id' );
		$community_id = get_query_var( 'community_id' );

		if ( $event_id ) {
			$classes[] = 'partyminder-event-conversation';
		}
		if ( $community_id ) {
			$classes[] = 'partyminder-community-conversation';
		}

		return $classes;
	}

	public function add_communities_body_class( $classes ) {
		$classes[] = 'partyminder-communities';
		return $classes;
	}

	public function add_my_communities_body_class( $classes ) {
		$classes[] = 'partyminder-communities';
		$classes[] = 'partyminder-my-communities';
		return $classes;
	}

	public function add_single_community_body_class( $classes ) {
		$classes[] = 'partyminder-communities';
		$classes[] = 'partyminder-single-community';

		$community_slug = get_query_var( 'community_slug' );
		if ( $community_slug ) {
			$classes[] = 'partyminder-community-' . sanitize_html_class( $community_slug );
		}

		return $classes;
	}


	public function add_community_events_body_class( $classes ) {
		$classes[] = 'partyminder-communities';
		$classes[] = 'partyminder-community-events';
		return $classes;
	}

	public function add_community_conversations_body_class( $classes ) {
		$classes[] = 'partyminder-communities';
		$classes[] = 'partyminder-community-conversations';
		return $classes;
	}

	public function add_manage_community_body_class( $classes ) {
		$classes[] = 'partyminder-communities';
		$classes[] = 'partyminder-community-management';
		return $classes;
	}

	public function add_create_community_body_class( $classes ) {
		$classes[] = 'partyminder-communities';
		$classes[] = 'partyminder-community-creation';
		return $classes;
	}

	public function add_create_group_body_class( $classes ) {
		$classes[] = 'partyminder-groups';
		$classes[] = 'partyminder-group-creation';
		return $classes;
	}
}
