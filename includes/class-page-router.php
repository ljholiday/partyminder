<?php

class PartyMinder_Page_Router {

	private $content_injector;
	private $body_class_manager;

	public function __construct( $content_injector, $body_class_manager ) {
		$this->content_injector   = $content_injector;
		$this->body_class_manager = $body_class_manager;
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'wp', array( $this, 'handle_page_routing' ) );
		add_filter( 'wp_title', array( $this, 'modify_page_titles' ), 10, 3 );
		add_filter( 'document_title_parts', array( $this, 'modify_document_title_parts' ) );
	}

	public function add_rewrite_rules() {
		// Event routing
		add_rewrite_rule( '^events/join/?$', 'index.php?pagename=events&event_action=join', 'top' );
		add_rewrite_rule( '^events/([^/]+)/?$', 'index.php?pagename=events&event_slug=$matches[1]', 'top' );

		// Conversation routing
		add_rewrite_rule( '^conversations/([^/]+)/?$', 'index.php?pagename=conversations&conversation_slug=$matches[1]', 'top' );

		// Community routing
		add_rewrite_rule( '^communities/?$', 'index.php?pagename=communities', 'top' );
		add_rewrite_rule( '^communities/join/?$', 'index.php?pagename=communities&community_action=join', 'top' );
		add_rewrite_rule( '^communities/([^/]+)/?$', 'index.php?pagename=communities&community_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^communities/([^/]+)/conversations/?$', 'index.php?pagename=communities&community_slug=$matches[1]&community_view=conversations', 'top' );
		add_rewrite_rule( '^communities/([^/]+)/events/?$', 'index.php?pagename=communities&community_slug=$matches[1]&community_view=events', 'top' );
		add_rewrite_rule( '^communities/([^/]+)/members/?$', 'index.php?pagename=communities&community_slug=$matches[1]&community_view=members', 'top' );

		// Direct page routing
		add_rewrite_rule( '^manage-community/?$', 'index.php?pagename=manage-community', 'top' );
		add_rewrite_rule( '^create-community/?$', 'index.php?pagename=create-community', 'top' );
		add_rewrite_rule( '^create-conversation/?$', 'index.php?pagename=create-conversation', 'top' );

		// Add query vars
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'event_slug';
		$vars[] = 'event_action';
		$vars[] = 'event_id';
		$vars[] = 'conversation_slug';
		$vars[] = 'community_slug';
		$vars[] = 'community_view';
		$vars[] = 'community_action';
		$vars[] = 'user';
		return $vars;
	}

	public function handle_page_routing() {
		global $wp_query;

		$current_page = get_queried_object();

		if ( ! is_page() || ! $current_page ) {
			return;
		}

		$page_keys        = array( 'dashboard', 'events', 'create-event', 'my-events', 'edit-event', 'create-conversation', 'create-community', 'create-group', 'conversations', 'communities', 'profile', 'login', 'manage-community' );
		$current_page_key = null;

		foreach ( $page_keys as $key ) {
			$page_id = get_option( 'partyminder_page_' . $key );
			if ( $page_id && $current_page->ID == $page_id ) {
				$current_page_key = $key;
				break;
			}
		}

		if ( ! $current_page_key ) {
			return;
		}

		// Handle specific page routing
		switch ( $current_page_key ) {
			case 'edit-event':
				if ( ! get_query_var( 'event_id' ) && isset( $_GET['event_id'] ) ) {
					set_query_var( 'event_id', intval( $_GET['event_id'] ) );
				}
				break;

			case 'my-events':
				if ( ! is_user_logged_in() && ! isset( $_GET['email'] ) ) {
					// Could redirect to login or show guest access form
				}
				break;
		}

		$this->setup_page_hooks( $current_page_key );

		// Handle single event routing
		$event_slug = get_query_var( 'event_slug' );
		if ( $event_slug && $current_page_key === 'events' ) {
			$this->handle_single_event_routing( $event_slug );
		}
	}

	private function setup_page_hooks( $page_type ) {
		switch ( $page_type ) {
			case 'dashboard':
				add_filter( 'the_content', array( $this->content_injector, 'inject_dashboard_content' ) );
				add_filter( 'body_class', array( $this->body_class_manager, 'add_dashboard_body_class' ) );
				break;

			case 'events':
				add_filter( 'the_content', array( $this->content_injector, 'inject_events_content' ) );
				add_filter( 'body_class', array( $this->body_class_manager, 'add_events_body_class' ) );
				break;

			case 'create-event':
				add_filter( 'the_content', array( $this->content_injector, 'inject_create_event_content' ) );
				add_filter( 'body_class', array( $this->body_class_manager, 'add_create_event_body_class' ) );
				break;

			case 'my-events':
				add_filter( 'the_content', array( $this->content_injector, 'inject_my_events_content' ) );
				add_filter( 'body_class', array( $this->body_class_manager, 'add_my_events_body_class' ) );
				break;

			case 'edit-event':
				if ( ! get_query_var( 'event_id' ) && isset( $_GET['event_id'] ) ) {
					set_query_var( 'event_id', intval( $_GET['event_id'] ) );
				}
				add_filter( 'the_content', array( $this->content_injector, 'inject_edit_event_content' ) );
				add_filter( 'body_class', array( $this->body_class_manager, 'add_edit_event_body_class' ) );
				break;

			case 'create-conversation':
				add_filter( 'the_content', array( $this->content_injector, 'inject_create_conversation_content' ) );
				add_filter( 'body_class', array( $this->body_class_manager, 'add_create_conversation_body_class' ) );
				break;

			case 'create-community':
				if ( PartyMinder_Feature_Flags::is_communities_enabled() ) {
					add_filter( 'the_content', array( $this->content_injector, 'inject_create_community_content' ) );
					add_filter( 'body_class', array( $this->body_class_manager, 'add_create_community_body_class' ) );
				}
				break;

			case 'create-group':
				add_filter( 'the_content', array( $this->content_injector, 'inject_create_group_content' ) );
				add_filter( 'body_class', array( $this->body_class_manager, 'add_create_group_body_class' ) );
				break;

			case 'profile':
				if ( ! get_query_var( 'user' ) && isset( $_GET['user'] ) ) {
					set_query_var( 'user', intval( $_GET['user'] ) );
				}
				add_filter( 'the_content', array( $this->content_injector, 'inject_profile_content' ) );
				add_filter( 'body_class', array( $this->body_class_manager, 'add_profile_body_class' ) );
				break;

			case 'login':
				add_filter( 'the_content', array( $this->content_injector, 'inject_login_content' ) );
				add_filter( 'body_class', array( $this->body_class_manager, 'add_login_body_class' ) );
				break;

			case 'conversations':
				$this->handle_conversation_routing();
				break;

			case 'communities':
				$this->handle_community_routing();
				break;

			case 'manage-community':
				if ( PartyMinder_Feature_Flags::is_communities_enabled() ) {
					if ( ! get_query_var( 'community_id' ) && isset( $_GET['community_id'] ) ) {
						set_query_var( 'community_id', intval( $_GET['community_id'] ) );
					}
					add_filter( 'the_content', array( $this->content_injector, 'inject_manage_community_content' ) );
					add_filter( 'body_class', array( $this->body_class_manager, 'add_manage_community_body_class' ) );
				}
				break;
		}
	}

	private function handle_conversation_routing() {
		$conversation_slug = get_query_var( 'conversation_slug' );

		if ( $conversation_slug ) {
			add_filter( 'the_content', array( $this->content_injector, 'inject_single_conversation_content' ) );
			add_filter( 'body_class', array( $this->body_class_manager, 'add_single_conversation_body_class' ) );
		} else {
			add_filter( 'the_content', array( $this->content_injector, 'inject_conversations_content' ) );
			add_filter( 'body_class', array( $this->body_class_manager, 'add_conversations_body_class' ) );
		}
	}

	private function handle_community_routing() {
		$community_slug = get_query_var( 'community_slug' );
		$community_view = get_query_var( 'community_view' );

		if ( $community_slug && $community_view === 'conversations' ) {
			add_filter( 'the_content', array( $this->content_injector, 'inject_community_conversations_content' ) );
			add_filter( 'body_class', array( $this->body_class_manager, 'add_community_conversations_body_class' ) );
		} elseif ( $community_slug && $community_view === 'members' ) {
			add_filter( 'the_content', array( $this->content_injector, 'inject_community_members_content' ) );
			add_filter( 'body_class', array( $this->body_class_manager, 'add_community_members_body_class' ) );
		} elseif ( $community_slug && $community_view === 'events' ) {
			add_filter( 'the_content', array( $this->content_injector, 'inject_community_events_content' ) );
			add_filter( 'body_class', array( $this->body_class_manager, 'add_community_events_body_class' ) );
		} elseif ( $community_slug ) {
			add_filter( 'the_content', array( $this->content_injector, 'inject_single_community_content' ) );
			add_filter( 'body_class', array( $this->body_class_manager, 'add_single_community_body_class' ) );
		} else {
			add_filter( 'the_content', array( $this->content_injector, 'inject_communities_content' ) );
			add_filter( 'body_class', array( $this->body_class_manager, 'add_communities_body_class' ) );
		}
	}

	private function handle_single_event_routing( $event_slug ) {
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
		$event_manager = new PartyMinder_Event_Manager();
		$event         = $event_manager->get_event_by_slug( $event_slug );

		if ( $event ) {
			$GLOBALS['partyminder_current_event']   = $event;
			$GLOBALS['partyminder_is_single_event'] = true;

			add_filter( 'the_content', array( $this->content_injector, 'inject_event_content' ), 10 );
			add_filter( 'wp_title', array( $this, 'inject_event_wp_title' ), 10, 3 );
			add_filter( 'document_title_parts', array( $this, 'inject_event_document_title' ) );
		}
	}

	public function modify_page_titles( $title, $sep, $seplocation ) {
		if ( ! is_page() ) {
			return $title;
		}

		global $post;
		$page_type = get_post_meta( $post->ID, '_partyminder_page_type', true );

		if ( ! $page_type ) {
			return $title;
		}

		return $this->get_custom_title_for_page( $page_type, $title, $sep, $seplocation );
	}

	public function modify_document_title_parts( $title_parts ) {
		if ( ! is_page() ) {
			return $title_parts;
		}

		global $post;
		$page_type = get_post_meta( $post->ID, '_partyminder_page_type', true );

		if ( ! $page_type ) {
			return $title_parts;
		}

		switch ( $page_type ) {
			case 'events':
				$title_parts['title'] = __( 'Upcoming Events - Find Amazing Parties Near You', 'partyminder' );
				break;
			case 'create-event':
				$title_parts['title'] = __( 'Create Your Event - Host an Amazing Party', 'partyminder' );
				break;
			case 'my-events':
				if ( is_user_logged_in() ) {
					$user                 = wp_get_current_user();
					$title_parts['title'] = sprintf( __( '%s\'s Events Dashboard', 'partyminder' ), $user->display_name );
				} else {
					$title_parts['title'] = __( 'My Events Dashboard', 'partyminder' );
				}
				break;
			case 'create-community':
				$title_parts['title'] = __( 'Create New Community - Build Your Community', 'partyminder' );
				break;
		}

		return $title_parts;
	}

	private function get_custom_title_for_page( $page_type, $title, $sep, $seplocation ) {
		switch ( $page_type ) {
			case 'events':
				return __( 'Upcoming Events', 'partyminder' );
			case 'create-event':
				return __( 'Create Event', 'partyminder' );
			case 'my-events':
				return __( 'My Events', 'partyminder' );
			case 'create-community':
				return __( 'Create Community', 'partyminder' );
			default:
				return $title;
		}
	}

	public function inject_event_wp_title( $title, $sep, $seplocation ) {
		if ( isset( $GLOBALS['partyminder_is_single_event'] ) && $GLOBALS['partyminder_is_single_event'] ) {
			$event = $GLOBALS['partyminder_current_event'] ?? null;
			if ( $event ) {
				return $event->title . ' ' . $sep . ' ' . get_bloginfo( 'name' );
			}
		}
		return $title;
	}

	public function inject_event_document_title( $title_parts ) {
		if ( isset( $GLOBALS['partyminder_is_single_event'] ) && $GLOBALS['partyminder_is_single_event'] ) {
			$event = $GLOBALS['partyminder_current_event'] ?? null;
			if ( $event ) {
				$title_parts['title'] = $event->title;
			}
		}
		return $title_parts;
	}
}
