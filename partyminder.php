<?php
/**
 * Plugin Name: PartyMinder
 * Plugin URI: https://partyminder.com
 * Description: AI-powered social event planning with federated networking. Create dinner parties and social events with intelligent assistance.
 * Version: 1.0.0
 * Author: PartyMinder Team
 * Author URI: https://partyminder.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: partyminder
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: true
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'PARTYMINDER_VERSION', '1.0.0' );
define( 'PARTYMINDER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PARTYMINDER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PARTYMINDER_PLUGIN_FILE', __FILE__ );

// Load activation/deactivation classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-activator.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-personal-community-service.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/pm_embed.php';

// Activation and deactivation hooks
register_activation_hook( __FILE__, array( 'PartyMinder_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PartyMinder_Deactivator', 'deactivate' ) );

// Disable WordPress magic quotes for cleaner data handling
add_action(
	'init',
	function () {
		if ( function_exists( 'wp_magic_quotes' ) ) {
			// Remove magic quotes from all input data
			$_GET     = array_map( 'stripslashes_deep', $_GET );
			$_POST    = array_map( 'stripslashes_deep', $_POST );
			$_COOKIE  = array_map( 'stripslashes_deep', $_COOKIE );
			$_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );
		}
	},
	1
);

// Initialize plugin
add_action( 'plugins_loaded', array( 'PartyMinder', 'get_instance' ) );

/**
 * Main PartyMinder Class
 */
class PartyMinder {

	private static $instance = null;
	private $event_manager;
	private $guest_manager;
	private $ai_assistant;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies() {
		// Core domain managers
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-member-identity-manager.php';

		// Infrastructure classes
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-ai-assistant.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-admin.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-feature-flags.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-image-manager.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-image-upload.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-profile-manager.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-member-display.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-search-indexer.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-search-api.php';

		// AJAX handler classes
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-ajax-handler.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-ajax-handler.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-ajax-handler.php';
		
		// Circle scope resolver
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-circle-scope.php';

		// Page management classes
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-page-content-injector.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-page-body-class-manager.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-page-router.php';

		// Load AT Protocol features only if enabled
		if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-bluesky-client.php';
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-at-protocol-manager.php';
		}

		$this->init_handlers();
		
		// Initialize search API
		new PartyMinder_Search_API();
	}

	private function init_handlers() {
		// Initialize AJAX handlers
		$this->event_ajax_handler        = new PartyMinder_Event_Ajax_Handler();
		$this->community_ajax_handler    = new PartyMinder_Community_Ajax_Handler();
		$this->conversation_ajax_handler = new PartyMinder_Conversation_Ajax_Handler();

		// Initialize page management
		$this->content_injector   = new PartyMinder_Page_Content_Injector();
		$this->body_class_manager = new PartyMinder_Page_Body_Class_Manager();
		$this->page_router        = new PartyMinder_Page_Router( $this->content_injector, $this->body_class_manager );
	}

	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Template system - theme integration - run earlier
		add_action( 'wp', array( $this->page_router, 'handle_page_routing' ), 5 );
		add_action( 'template_redirect', array( $this, 'handle_form_submissions' ) );

		// Individual event page routing is now handled by Page Router

		// No longer need post meta suppression - using pages now

		// Register shortcodes early
		add_action( 'wp_loaded', array( $this, 'register_shortcodes' ) );

		// Migration code removed - not needed for new installations

		// Theme integration hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_page_specific_assets' ) );
		
		// Personal community creation for new users (Step 2 of circles plan)
		add_action( 'user_register', array( $this, 'create_personal_community_for_new_user' ) );

		// SEO and structured data
		add_action( 'wp_head', array( $this, 'add_structured_data' ) );

		// Keep only essential AJAX handlers that aren't moved to dedicated classes
		add_action( 'wp_ajax_partyminder_rsvp', array( $this, 'ajax_rsvp' ) );
		add_action( 'wp_ajax_nopriv_partyminder_rsvp', array( $this, 'ajax_rsvp' ) );
		add_action( 'wp_ajax_partyminder_generate_ai_plan', array( $this, 'ajax_generate_ai_plan' ) );
		add_action( 'wp_ajax_partyminder_load_more_events', array( $this, 'ajax_load_more_events' ) );
		add_action( 'wp_ajax_nopriv_partyminder_load_more_events', array( $this, 'ajax_load_more_events' ) );
		add_action( 'wp_ajax_partyminder_newsletter_signup', array( $this, 'ajax_newsletter_signup' ) );
		add_action( 'wp_ajax_nopriv_partyminder_newsletter_signup', array( $this, 'ajax_newsletter_signup' ) );
		add_action( 'wp_ajax_partyminder_process_rsvp_landing', array( $this, 'ajax_process_rsvp_landing' ) );
		add_action( 'wp_ajax_nopriv_partyminder_process_rsvp_landing', array( $this, 'ajax_process_rsvp_landing' ) );
		add_action( 'wp_ajax_partyminder_reindex_search', array( $this, 'ajax_reindex_search' ) );
		add_action( 'wp_ajax_get_mobile_menu_content', array( $this, 'ajax_get_mobile_menu_content' ) );
		add_action( 'wp_ajax_nopriv_get_mobile_menu_content', array( $this, 'ajax_get_mobile_menu_content' ) );

		// Image upload AJAX handlers
		add_action( 'wp_ajax_partyminder_avatar_upload', array( 'PartyMinder_Image_Upload', 'handle_avatar_upload' ) );
		add_action( 'wp_ajax_partyminder_cover_upload', array( 'PartyMinder_Image_Upload', 'handle_cover_upload' ) );
		add_action( 'wp_ajax_partyminder_conversation_photo_upload', array( 'PartyMinder_Image_Upload', 'handle_conversation_photo_upload' ) );
		add_action( 'wp_ajax_partyminder_event_photo_upload', array( 'PartyMinder_Image_Upload', 'handle_event_photo_upload' ) );
		add_action( 'wp_ajax_partyminder_event_cover_upload', array( 'PartyMinder_Image_Upload', 'handle_event_cover_upload' ) );
		add_action( 'wp_ajax_partyminder_community_cover_upload', array( 'PartyMinder_Image_Upload', 'handle_community_cover_upload' ) );
		// All AJAX handlers are now handled by dedicated handler classes

		// Smart login override - frontend only, preserves wp-admin access
		add_filter( 'wp_login_url', array( $this, 'smart_login_url' ), 10, 2 );

		// WordPress Avatar Integration
		add_filter( 'get_avatar_url', array( $this, 'get_partyminder_avatar_url' ), 10, 3 );
		add_filter( 'get_avatar', array( $this, 'get_partyminder_avatar' ), 10, 6 );
	}

	public function register_shortcodes() {
		// Shortcodes
		add_shortcode( 'partyminder_dashboard', array( $this, 'dashboard_shortcode' ) );
		add_shortcode( 'partyminder_event_form', array( $this, 'event_form_shortcode' ) );
		add_shortcode( 'partyminder_event_edit_form', array( $this, 'event_edit_form_shortcode' ) );
		add_shortcode( 'partyminder_rsvp_form', array( $this, 'rsvp_form_shortcode' ) );
		add_shortcode( 'partyminder_events_list', array( $this, 'events_list_shortcode' ) );
		add_shortcode( 'partyminder_my_events', array( $this, 'my_events_shortcode' ) );
		add_shortcode( 'partyminder_conversations', array( $this, 'conversations_shortcode' ) );
		add_shortcode( 'partyminder_profile', array( $this, 'profile_shortcode' ) );
		add_shortcode( 'partyminder_login', array( $this, 'login_shortcode' ) );

		// Communities shortcode
		add_shortcode( 'partyminder_communities', array( $this, 'communities_shortcode' ) );
		add_shortcode( 'partyminder_manage_community', array( $this, 'manage_community_shortcode' ) );
		add_shortcode( 'partyminder_create_community', array( $this, 'create_community_shortcode' ) );
	}

	public function init() {
		// Load text domain
		load_plugin_textdomain( 'partyminder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Add responsive embed support for oEmbed
		add_theme_support( 'responsive-embeds' );
		
		// Add custom oEmbed provider for all URLs using a different approach
		add_filter( 'embed_maybe_make_link', array( $this, 'maybe_custom_embed' ), 10, 2 );

		// Initialize managers
		$this->event_manager        = new PartyMinder_Event_Manager();
		$this->guest_manager        = new PartyMinder_Guest_Manager();
		$this->conversation_manager = new PartyMinder_Conversation_Manager();
		$this->ai_assistant         = new PartyMinder_AI_Assistant();

		// Initialize communities managers
		$this->community_manager       = new PartyMinder_Community_Manager();
		$this->member_identity_manager = new PartyMinder_Member_Identity_Manager();

		// Initialize AT Protocol manager only if feature is enabled
		if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) {
			$this->at_protocol_manager = new PartyMinder_AT_Protocol_Manager();
		}

		// Initialize admin
		if ( is_admin() ) {
			new PartyMinder_Admin();
		}
	}



	public function enqueue_public_scripts() {
		wp_enqueue_style( 'partyminder', PARTYMINDER_PLUGIN_URL . 'assets/css/partyminder.css', array(), PARTYMINDER_VERSION . '-' . filemtime( PARTYMINDER_PLUGIN_DIR . 'assets/css/partyminder.css' ) );

		// Add dynamic colors from admin settings
		$primary_color   = get_option( 'partyminder_primary_color', '#667eea' );
		$secondary_color = get_option( 'partyminder_secondary_color', '#764ba2' );

		$custom_css = ":root {
            --pm-primary: {$primary_color} !important;
            --pm-secondary: {$secondary_color} !important;
        }";

		wp_add_inline_style( 'partyminder', $custom_css );

		wp_enqueue_script( 'partyminder-public', PARTYMINDER_PLUGIN_URL . 'assets/js/public.js', array( 'jquery' ), PARTYMINDER_VERSION, true );
		wp_enqueue_script( 'partyminder-mobile-menu', PARTYMINDER_PLUGIN_URL . 'assets/js/mobile-menu.js', array(), PARTYMINDER_VERSION, true );
		wp_enqueue_script( 'partyminder-search', PARTYMINDER_PLUGIN_URL . 'assets/js/search.js', array( 'jquery' ), PARTYMINDER_VERSION, true );

		$current_user = wp_get_current_user();
		wp_localize_script(
			'partyminder-public',
			'partyminder_ajax',
			array(
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'partyminder_nonce' ),
				'community_nonce'   => wp_create_nonce( 'partyminder_community_action' ),
				'event_nonce'       => wp_create_nonce( 'partyminder_event_action' ),
				'at_protocol_nonce' => wp_create_nonce( 'partyminder_at_protocol' ),
				'avatar_upload_nonce' => wp_create_nonce( 'partyminder_avatar_upload' ),
				'cover_upload_nonce' => wp_create_nonce( 'partyminder_cover_upload' ),
				'event_photo_upload_nonce' => wp_create_nonce( 'partyminder_event_photo_upload' ),
				'event_cover_upload_nonce' => wp_create_nonce( 'partyminder_event_cover_upload' ),
				'community_cover_upload_nonce' => wp_create_nonce( 'partyminder_community_cover_upload' ),
				'conversation_photo_upload_nonce' => wp_create_nonce( 'partyminder_conversation_photo_upload' ),
				'current_user'      => array(
					'id'    => $current_user->ID,
					'name'  => $current_user->display_name,
					'email' => $current_user->user_email,
				),
				'features'          => PartyMinder_Feature_Flags::get_feature_status_for_js(),
				'strings'           => array(
					'loading'       => __( 'Loading...', 'partyminder' ),
					'error'         => __( 'An error occurred. Please try again.', 'partyminder' ),
					'success'       => __( 'Success!', 'partyminder' ),
					'confirm_join'  => __( 'Are you sure you want to join this community?', 'partyminder' ),
					'confirm_leave' => __( 'Are you sure you want to leave this community?', 'partyminder' ),
				),
			)
		);
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'partyminder' ) !== false ) {
			wp_enqueue_style( 'partyminder-admin', PARTYMINDER_PLUGIN_URL . 'assets/css/partyminder.css', array(), PARTYMINDER_VERSION . '-' . filemtime( PARTYMINDER_PLUGIN_DIR . 'assets/css/partyminder.css' ) );

			// Add dynamic colors from admin settings
			$primary_color   = get_option( 'partyminder_primary_color', '#667eea' );
			$secondary_color = get_option( 'partyminder_secondary_color', '#764ba2' );

			$custom_css = ":root {
                --pm-primary: {$primary_color} !important;
                --pm-secondary: {$secondary_color} !important;
            }";

			wp_add_inline_style( 'partyminder-admin', $custom_css );

			wp_enqueue_script( 'partyminder-admin', PARTYMINDER_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), PARTYMINDER_VERSION, true );

			wp_localize_script(
				'partyminder-admin',
				'partyminder_admin',
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'partyminder_admin_nonce' ),
					'event_nonce'     => wp_create_nonce( 'partyminder_event_action' ),
					'community_nonce' => wp_create_nonce( 'partyminder_community_action' ),
				)
			);
		}
	}

	// AJAX Handlers - Event-related methods moved to Event_Ajax_Handler class

	public function ajax_rsvp() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		$rsvp_data = array(
			'event_id' => intval( $_POST['event_id'] ),
			'name'     => sanitize_text_field( $_POST['name'] ),
			'email'    => sanitize_email( $_POST['email'] ),
			'status'   => sanitize_text_field( $_POST['status'] ),
			'dietary'  => sanitize_text_field( $_POST['dietary'] ),
			'notes'    => sanitize_text_field( $_POST['notes'] ),
		);

		$result = $this->guest_manager->process_rsvp( $rsvp_data );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	public function ajax_generate_ai_plan() {
		check_ajax_referer( 'partyminder_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'partyminder' ) );
		}

		$event_title = sanitize_text_field( $_POST['event_title'] );
		$guest_count = intval( $_POST['guest_count'] );
		$dietary     = sanitize_text_field( $_POST['dietary'] );
		$budget      = sanitize_text_field( $_POST['budget'] );

		$plan = $this->ai_assistant->generate_plan( $event_title, $guest_count, $dietary, $budget );

		wp_send_json_success( $plan );
	}

	public function ajax_load_more_events() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		$page = intval( $_POST['page'] ?? 1 );
		$limit = intval( $_POST['limit'] ?? 10 );

		if ( ! $this->event_manager ) {
			$this->event_manager = new PartyMinder_Event_Manager();
		}

		$events = $this->event_manager->get_events( array(
			'page' => $page,
			'limit' => $limit,
			'status' => 'upcoming'
		) );

		ob_start();
		if ( ! empty( $events ) ) {
			foreach ( $events as $event ) {
				include PARTYMINDER_PLUGIN_DIR . 'templates/event-card.php';
			}
		}
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html' => $html,
			'has_more' => count( $events ) >= $limit
		) );
	}

	public function ajax_newsletter_signup() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		$email = sanitize_email( $_POST['email'] ?? '' );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'partyminder' ) );
		}

		// Simple implementation - just store in WordPress options for now
		$subscribers = get_option( 'partyminder_newsletter_subscribers', array() );
		
		if ( in_array( $email, $subscribers ) ) {
			wp_send_json_error( __( 'This email is already subscribed.', 'partyminder' ) );
		}

		$subscribers[] = $email;
		update_option( 'partyminder_newsletter_subscribers', $subscribers );

		wp_send_json_success( __( 'Thank you for subscribing!', 'partyminder' ) );
	}

	public function ajax_force_database_migration() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-activator.php';
		PartyMinder_Activator::activate();
		
		wp_send_json_success( 'Database migration completed' );
	}

	public function ajax_reindex_search() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-search-indexer-init.php';
		
		// Clear existing search index
		PartyMinder_Search_Indexer_Init::clear_search_index();
		
		// Reindex all content
		$indexed_count = PartyMinder_Search_Indexer_Init::index_all_content();
		
		wp_send_json_success( "Search index rebuilt. Indexed $indexed_count items." );
	}

	public function ajax_get_mobile_menu_content() {
		// No nonce verification needed for non-sensitive mobile menu content
		
		// Start output buffering to capture the template output
		ob_start();
		
		// Include the mobile menu content template
		include PARTYMINDER_PLUGIN_DIR . 'templates/partials/mobile-menu-content.php';
		
		// Get the buffered content and clean the buffer
		$mobile_content = ob_get_clean();
		
		// Return just the HTML content without JSON wrapper
		echo $mobile_content;
		wp_die();
	}

	public function ajax_process_rsvp_landing() {
		check_ajax_referer( 'partyminder_event_nonce', 'nonce' );

		// Get form data
		$rsvp_token = sanitize_text_field( $_POST['rsvp_token'] ?? '' );
		$event_id = intval( $_POST['event_id'] ?? 0 );
		$rsvp_status = sanitize_text_field( $_POST['rsvp_status'] ?? '' );
		$guest_name = sanitize_text_field( $_POST['guest_name'] ?? '' );
		$dietary_restrictions = sanitize_text_field( $_POST['dietary_restrictions'] ?? '' );
		$plus_one = intval( $_POST['plus_one'] ?? 0 );
		$plus_one_name = sanitize_text_field( $_POST['plus_one_name'] ?? '' );
		$guest_notes = sanitize_text_field( $_POST['guest_notes'] ?? '' );
		$create_account = intval( $_POST['create_account'] ?? 0 );

		if ( empty( $rsvp_token ) || empty( $event_id ) ) {
			wp_send_json_error( __( 'Invalid RSVP data', 'partyminder' ) );
		}

		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
		$guest_manager = new PartyMinder_Guest_Manager();

		// Get guest by token
		$guest = $guest_manager->get_guest_by_token( $rsvp_token );
		if ( ! $guest ) {
			wp_send_json_error( __( 'Invalid or expired RSVP link', 'partyminder' ) );
		}

		// Prepare guest data
		$guest_data = array();
		if ( ! empty( $guest_name ) ) {
			$guest_data['name'] = $guest_name;
		}
		if ( ! empty( $dietary_restrictions ) ) {
			$guest_data['dietary'] = $dietary_restrictions;
		}
		if ( ! empty( $guest_notes ) ) {
			$guest_data['notes'] = $guest_notes;
		}

		// Process RSVP
		if ( ! empty( $rsvp_status ) ) {
			$result = $guest_manager->process_anonymous_rsvp( $rsvp_token, $rsvp_status, $guest_data );
			if ( ! $result['success'] ) {
				wp_send_json_error( $result['message'] );
			}
		} else {
			// Update existing guest data without changing status
			global $wpdb;
			$guests_table = $wpdb->prefix . 'partyminder_guests';
			
			$update_data = array();
			if ( ! empty( $guest_name ) ) {
				$update_data['name'] = $guest_name;
			}
			if ( ! empty( $dietary_restrictions ) ) {
				$update_data['dietary_restrictions'] = $dietary_restrictions;
			}
			if ( ! empty( $guest_notes ) ) {
				$update_data['notes'] = $guest_notes;
			}
			$update_data['plus_one'] = $plus_one;
			if ( ! empty( $plus_one_name ) ) {
				$update_data['plus_one_name'] = $plus_one_name;
			}

			$wpdb->update(
				$guests_table,
				$update_data,
				array( 'id' => $guest->id ),
				null,
				array( '%d' )
			);
		}

		// Handle account creation if requested
		$account_created = false;
		if ( $create_account && ! $guest->converted_user_id ) {
			$user_id = $guest_manager->convert_guest_to_user( $guest->id, $guest_data );
			if ( ! is_wp_error( $user_id ) ) {
				$account_created = true;
			}
		}

		$response_data = array(
			'message' => __( 'RSVP updated successfully!', 'partyminder' ),
			'account_created' => $account_created
		);

		if ( $account_created ) {
			$response_data['message'] .= ' ' . __( 'Your PartyMinder account has been created!', 'partyminder' );
		}

		wp_send_json_success( $response_data );
	}

	// All other AJAX methods moved to dedicated handler classes


	// Shortcodes
	public function dashboard_shortcode( $atts ) {
		$atts = shortcode_atts( array(), $atts );

		// Check if we're on the dedicated page
		$on_dedicated_page = $this->is_on_dedicated_page( 'dashboard' );

		// If on dedicated page, content injection handles everything
		if ( $on_dedicated_page ) {
			return '';
		}

		// Otherwise, provide simplified embedded version
		ob_start();
		echo '<div class="partyminder-shortcode-wrapper">';
		echo '<h3>' . __( 'PartyMinder Dashboard', 'partyminder' ) . '</h3>';
		echo '<p>' . __( 'Your central hub for managing events, conversations, and connections.', 'partyminder' ) . '</p>';
		echo '<a href="' . esc_url( self::get_dashboard_url() ) . '" class="pm-button">' . __( 'Go to Dashboard', 'partyminder' ) . '</a>';
		echo '</div>';
		return ob_get_clean();
	}

	public function event_form_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title' => __( 'Create Your Event', 'partyminder' ),
			),
			$atts
		);

		// Check if we're on the dedicated page
		$on_dedicated_page = $this->is_on_dedicated_page( 'create-event' );

		// If on dedicated page, content injection handles everything
		if ( $on_dedicated_page ) {
			return '';
		}

		// Otherwise, provide simplified embedded version with link to full page
		ob_start();
		echo '<div class="partyminder-shortcode-wrapper">';
		echo '<h3>' . esc_html( $atts['title'] ) . '</h3>';
		echo '<p>' . __( 'Create and manage your events with our full-featured event creation tool.', 'partyminder' ) . '</p>';
		echo '<a href="' . esc_url( self::get_create_event_url() ) . '" class="pm-button">' . __( 'Create Event', 'partyminder' ) . '</a>';
		echo '</div>';
		return ob_get_clean();
	}

	public function rsvp_form_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
			),
			$atts
		);

		ob_start();
		include PARTYMINDER_PLUGIN_DIR . 'templates/rsvp-form.php';
		return ob_get_clean();
	}

	public function events_list_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'     => 10,
				'show_past' => false,
			),
			$atts
		);

		// Check if we're on the dedicated page
		$on_dedicated_page = $this->is_on_dedicated_page( 'events' );

		// If on dedicated page, content injection handles everything
		if ( $on_dedicated_page ) {
			return '';
		}

		// Otherwise, provide simplified embedded version
		ob_start();
		echo '<div class="partyminder-shortcode-wrapper">';
		echo '<h3>' . __( 'Upcoming Events', 'partyminder' ) . '</h3>';
		echo '<p>' . __( 'Check out all upcoming events and RSVP to join the fun!', 'partyminder' ) . '</p>';
		echo '<a href="' . esc_url( self::get_events_page_url() ) . '" class="pm-button">' . __( 'View All Events', 'partyminder' ) . '</a>';
		echo '<a href="' . esc_url( self::get_create_event_url() ) . '" class="pm-button success">' . __( 'Create Event', 'partyminder' ) . '</a>';
		echo '</div>';
		return ob_get_clean();
	}

	public function my_events_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'show_past' => false,
			),
			$atts
		);

		// Check if we're on the dedicated page
		$on_dedicated_page = $this->is_on_dedicated_page( 'my-events' );

		// If on dedicated page, content injection handles everything
		if ( $on_dedicated_page ) {
			return '';
		}

		// Otherwise, provide simplified embedded version
		ob_start();
		echo '<div class="partyminder-shortcode-wrapper">';
		echo '<h3>' . __( 'My Events Dashboard', 'partyminder' ) . '</h3>';
		echo '<p>' . __( 'Manage your created events and RSVPs in one convenient place.', 'partyminder' ) . '</p>';
		echo '<a href="' . esc_url( self::get_my_events_url() ) . '" class="pm-button">' . __( 'View My Events', 'partyminder' ) . '</a>';
		echo '</div>';
		return ob_get_clean();
	}

	public function conversations_shortcode( $atts ) {
		$atts = shortcode_atts( array(), $atts );

		// Check if we're on the dedicated page
		$on_dedicated_page = $this->is_on_dedicated_page( 'conversations' );

		// If on dedicated page, content injection handles everything
		if ( $on_dedicated_page ) {
			return '';
		}

		// Otherwise, provide simplified embedded version
		ob_start();
		echo '<div class="partyminder-shortcode-wrapper">';
		echo '<h3>' . __( 'Community Conversations', 'partyminder' ) . '</h3>';
		echo '<p>' . __( 'Connect with fellow hosts and guests, share tips, and plan amazing gatherings together.', 'partyminder' ) . '</p>';
		echo '<a href="' . esc_url( self::get_conversations_url() ) . '" class="pm-button">' . __( 'Join Conversations', 'partyminder' ) . '</a>';
		echo '</div>';
		return ob_get_clean();
	}

	public function communities_shortcode( $atts ) {
		$atts = shortcode_atts( array(), $atts );

		// Check if we're on the dedicated page
		$on_dedicated_page = $this->is_on_dedicated_page( 'communities' );

		// If on dedicated page, content injection handles everything
		if ( $on_dedicated_page ) {
			return '';
		}

		// Otherwise, provide simplified embedded version
		ob_start();
		echo '<div class="partyminder-shortcode-wrapper">';
		echo '<h3>' . __( 'Communities', 'partyminder' ) . '</h3>';
		echo '<p>' . __( 'Join communities of fellow hosts and guests to plan events together.', 'partyminder' ) . '</p>';
		echo '<a href="' . esc_url( self::get_communities_url() ) . '" class="pm-button">' . __( 'Browse Communities', 'partyminder' ) . '</a>';
		echo '</div>';
		return ob_get_clean();
	}

	public function event_edit_form_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'event_id' => intval( $_GET['event_id'] ?? 0 ),
			),
			$atts
		);

		// Check if we're on the dedicated page
		$on_dedicated_page = $this->is_on_dedicated_page( 'edit-event' );

		// If on dedicated page, content injection handles everything
		if ( $on_dedicated_page ) {
			return '';
		}

		// Otherwise, provide simplified embedded version
		$event_id = $atts['event_id'];
		if ( $event_id ) {
			ob_start();
			echo '<div class="partyminder-shortcode-wrapper">';
			echo '<h3>' . __( 'Edit Event', 'partyminder' ) . '</h3>';
			echo '<p>' . __( 'Use our full-featured editor to update your event details.', 'partyminder' ) . '</p>';
			echo '<a href="' . esc_url( self::get_edit_event_url( $event_id ) ) . '" class="pm-button">' . __( 'Edit Event', 'partyminder' ) . '</a>';
			echo '</div>';
			return ob_get_clean();
		}

		return '<div class="partyminder-shortcode-wrapper warning"><p>' . __( 'Event ID required for editing.', 'partyminder' ) . '</p></div>';
	}

	public function profile_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'user' => get_current_user_id(),
			),
			$atts
		);

		// Check if we're on the dedicated page
		$on_dedicated_page = $this->is_on_dedicated_page( 'profile' );

		// If on dedicated page, content injection handles everything
		if ( $on_dedicated_page ) {
			return '';
		}

		// Otherwise, provide simplified embedded version
		ob_start();
		echo '<div class="partyminder-shortcode-wrapper">';
		echo '<h3>' . __( 'My Profile', 'partyminder' ) . '</h3>';
		echo '<p>' . __( 'Manage your PartyMinder profile, preferences, and hosting reputation.', 'partyminder' ) . '</p>';
		echo '<a href="' . esc_url( self::get_profile_url() ) . '" class="pm-button">' . __( 'View Profile', 'partyminder' ) . '</a>';
		echo '</div>';
		return ob_get_clean();
	}

	public function login_shortcode( $atts ) {
		$atts = shortcode_atts( array(), $atts );

		// Check if we're on the dedicated page
		$on_dedicated_page = $this->is_on_dedicated_page( 'login' );

		// If on dedicated page, content injection handles everything
		if ( $on_dedicated_page ) {
			return '';
		}

		// Otherwise, provide simplified embedded version
		ob_start();
		echo '<div class="partyminder-shortcode-wrapper">';
		echo '<h3>' . __( 'PartyMinder Login', 'partyminder' ) . '</h3>';
		echo '<p>' . __( 'Sign in to access all features and manage your events.', 'partyminder' ) . '</p>';
		echo '<a href="' . esc_url( self::get_login_url() ) . '" class="pm-button">' . __( 'Sign In', 'partyminder' ) . '</a>';
		echo '</div>';
		return ob_get_clean();
	}

	// Routing methods moved to Page_Router class


	public static function get_page_url( $page_key, $args = array() ) {
		$page_id = get_option( 'partyminder_page_' . $page_key );
		if ( ! $page_id ) {
			return home_url( '/' . $page_key . '/' );
		}

		$url = get_permalink( $page_id );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	public static function get_dashboard_url() {
		return self::get_page_url( 'dashboard' );
	}

	public static function get_events_page_url() {
		return self::get_page_url( 'events' );
	}

	public static function get_create_event_url() {
		return self::get_page_url( 'create-event' );
	}

	public static function get_create_community_event_url() {
		return self::get_page_url( 'create-community-event' );
	}

	public static function get_my_events_url() {
		return self::get_page_url( 'my-events' );
	}

	public static function get_edit_event_url( $event_id ) {
		return self::get_page_url( 'edit-event', array( 'event_id' => $event_id ) );
	}

	public static function get_conversations_url() {
		return self::get_page_url( 'conversations' );
	}

	public static function get_login_url() {
		return self::get_page_url( 'login' );
	}

	public static function get_logout_url( $redirect = '' ) {
		if ( empty( $redirect ) ) {
			$redirect = home_url();
		}
		return wp_logout_url( $redirect );
	}

	public static function get_communities_url() {
		return self::get_page_url( 'communities' );
	}

	public static function get_create_community_url() {
		return self::get_page_url( 'create-community' );
	}

	public static function get_community_url( $community_slug ) {
		return home_url( '/communities/' . $community_slug . '/' );
	}

	public static function get_profile_url( $user_id = null ) {
		if ( $user_id && $user_id !== get_current_user_id() ) {
			return self::get_page_url( 'profile', array( 'user' => $user_id ) );
		}
		return self::get_page_url( 'profile' );
	}

	public static function get_create_conversation_url() {
		return self::get_page_url( 'create-conversation' );
	}

	/**
	 * Render user avatar with profile link
	 * 
	 * @param int $user_id WordPress user ID
	 * @param string $user_name Display name
	 * @param string $size Size: 'sm', 'md', 'lg' (default: 'md')
	 * @param bool $link Whether to make it clickable (default: true)
	 * @param string $class Additional CSS classes
	 */
	public static function render_avatar( $user_id, $user_name, $size = 'md', $link = true, $class = '' ) {
		include PARTYMINDER_PLUGIN_DIR . 'templates/partials/avatar.php';
	}

	private function is_on_dedicated_page( $page_key ) {
		global $post;

		if ( ! is_page() || ! $post ) {
			return false;
		}

		// Check by page meta for the new system
		$page_type = get_post_meta( $post->ID, '_partyminder_page_type', true );
		if ( $page_type === $page_key ) {
			return true;
		}

		// Fallback: Check by stored page ID (for backward compatibility)
		$page_id = get_option( 'partyminder_page_' . $page_key );
		return $page_id && $post->ID == $page_id;
	}

	public function handle_form_submissions() {
		// Handle frontend event form submission
		if ( isset( $_POST['partyminder_create_event'] ) ) {

			if ( wp_verify_nonce( $_POST['partyminder_event_nonce'], 'create_partyminder_event' ) ) {
				// Nonce verified, proceed with form processing
			} else {
				return;
			}

			// Load form handler
			require_once __DIR__ . '/includes/class-event-form-handler.php';
			
			// Validate form data
			$form_errors = PartyMinder_Event_Form_Handler::validate_event_form( $_POST );

			// If no errors, create the event
			if ( empty( $form_errors ) ) {
				$event_data = PartyMinder_Event_Form_Handler::process_event_form_data( $_POST );

				$event_id = $this->create_event_via_form( $event_data );

				if ( ! is_wp_error( $event_id ) ) {
					// Store success data in session or transient for the template
					$created_event = $this->event_manager->get_event( $event_id );
					set_transient(
						'partyminder_event_created_' . get_current_user_id(),
						array(
							'event_id'  => $event_id,
							'event_url' => home_url( '/events/' . $created_event->slug ),
						),
						60
					);

					// Redirect to prevent resubmission
					wp_redirect( add_query_arg( 'partyminder_created', 1, self::get_create_event_url() ) );
					exit;
				} else {
					// Store error for template
					set_transient( 'partyminder_form_errors_' . get_current_user_id(), array( $event_id->get_error_message() ), 60 );
				}
			} else {
				// Store validation errors for template
				set_transient( 'partyminder_form_errors_' . get_current_user_id(), $form_errors, 60 );
			}
		}
	}

	public function create_event_via_form( $event_data ) {
		// Ensure dependencies are loaded
		if ( ! $this->event_manager ) {
			$this->load_dependencies();
			$this->event_manager = new PartyMinder_Event_Manager();
		}

		if ( ! $this->event_manager ) {
			return new WP_Error( 'manager_not_loaded', __( 'Event manager not available', 'partyminder' ) );
		}

		return $this->event_manager->create_event( $event_data );
	}



	public function add_event_seo_tags() {
		if ( ! isset( $GLOBALS['partyminder_current_event'] ) ) {
			return;
		}

		$event = $GLOBALS['partyminder_current_event'];

		echo '<title>' . esc_html( $event->meta_title ?: $event->title ) . '</title>' . "\n";
		echo '<meta name="description" content="' . esc_attr( $event->meta_description ?: $event->excerpt ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $event->title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $event->excerpt ) . '">' . "\n";
		echo '<meta property="og:type" content="event">' . "\n";
		echo '<meta property="event:start_time" content="' . esc_attr( $event->event_date ) . '">' . "\n";

		if ( $event->venue_info ) {
			echo '<meta property="event:location" content="' . esc_attr( $event->venue_info ) . '">' . "\n";
		}
	}

	// Removed all post metadata suppression - no longer needed with pages


	public function add_structured_data() {
		global $post;

		// Add structured data for PartyMinder custom event pages
		if ( isset( $GLOBALS['partyminder_current_event'] ) ) {
			$event = $GLOBALS['partyminder_current_event'];

			if ( $event ) {
				$structured_data = array(
					'@context'            => 'https://schema.org',
					'@type'               => 'Event',
					'name'                => $event->title,
					'description'         => $event->description ?: $event->excerpt,
					'startDate'           => date( 'c', strtotime( $event->event_date ) ),
					'url'                 => home_url( '/events/' . $event->slug ),
					'eventStatus'         => 'https://schema.org/EventScheduled',
					'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
					'organizer'           => array(
						'@type' => 'Person',
						'email' => $event->host_email,
					),
				);

				if ( $event->venue_info ) {
					$structured_data['location'] = array(
						'@type' => 'Place',
						'name'  => $event->venue_info,
					);
				}

				if ( $event->guest_stats ) {
					$structured_data['maximumAttendeeCapacity'] = $event->guest_limit ?: 100;
					$structured_data['attendeeCount']           = $event->guest_stats->confirmed;
				}

				echo '<script type="application/ld+json">' . wp_json_encode( $structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
			}
		}

		// Add structured data for events pages
		if ( is_page() ) {
			$page_keys = array( 'events', 'create-event', 'create-community-event', 'my-events' );

			foreach ( $page_keys as $key ) {
				$page_id = get_option( 'partyminder_page_' . $key );
				if ( $page_id && $post->ID == $page_id && $key === 'events' ) {
					// Add breadcrumb and website structured data for events page
					$structured_data = array(
						'@context'    => 'https://schema.org',
						'@type'       => 'CollectionPage',
						'name'        => get_the_title(),
						'description' => 'Discover and RSVP to exciting events in your area.',
						'url'         => get_permalink(),
						'breadcrumb'  => array(
							'@type'           => 'BreadcrumbList',
							'itemListElement' => array(
								array(
									'@type'    => 'ListItem',
									'position' => 1,
									'name'     => 'Home',
									'item'     => home_url(),
								),
								array(
									'@type'    => 'ListItem',
									'position' => 2,
									'name'     => 'Events',
									'item'     => get_permalink(),
								),
							),
						),
					);

					echo '<script type="application/ld+json">' . wp_json_encode( $structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
					break;
				}
			}
		}
	}

	public function modify_page_titles( $title_parts ) {
		global $post;

		// Handle custom event page titles
		if ( isset( $GLOBALS['partyminder_current_event'] ) ) {
			$event                = $GLOBALS['partyminder_current_event'];
			$title_parts['title'] = $event->meta_title ?: $event->title;
			return $title_parts;
		}

		if ( ! is_page() || ! $post ) {
			return $title_parts;
		}

		// Modify titles for our dedicated pages
		$page_keys = array( 'events', 'create-event', 'create-community-event', 'my-events', 'edit-event', 'create-conversation', 'create-community' );

		foreach ( $page_keys as $key ) {
			$page_id = get_option( 'partyminder_page_' . $key );
			if ( $page_id && $post->ID == $page_id ) {
				switch ( $key ) {
					case 'events':
						$title_parts['title'] = __( 'Upcoming Events - Find Amazing Parties Near You', 'partyminder' );
						break;
					case 'create-event':
						$title_parts['title'] = __( 'Create Your Event - Host an Amazing Party', 'partyminder' );
						break;
					case 'create-community-event':
						$title_parts['title'] = __( 'Create Community Event - Plan Together', 'partyminder' );
						break;
					case 'my-events':
						if ( is_user_logged_in() ) {
							$user                 = wp_get_current_user();
							$title_parts['title'] = sprintf( __( '%s\'s Events Dashboard', 'partyminder' ), $user->display_name );
						} else {
							$title_parts['title'] = __( 'My Events Dashboard', 'partyminder' );
						}
						break;
					case 'edit-event':
						if ( isset( $_GET['event_id'] ) ) {
							require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
							$event_manager = new PartyMinder_Event_Manager();
							$event         = $event_manager->get_event( intval( $_GET['event_id'] ) );
							if ( $event ) {
								$title_parts['title'] = sprintf( __( 'Edit %s - Update Event Details', 'partyminder' ), $event->title );
							} else {
								$title_parts['title'] = __( 'Edit Event - Update Event Details', 'partyminder' );
							}
						} else {
							$title_parts['title'] = __( 'Edit Event - Update Event Details', 'partyminder' );
						}
						break;
					case 'create-community':
						$title_parts['title'] = __( 'Create New Community - Build Your Community', 'partyminder' );
						break;
				}
				break;
			}
		}

		return $title_parts;
	}

	public function event_template_include( $template ) {
		if ( isset( $GLOBALS['partyminder_current_event'] ) ) {
			// Try to find a theme template first
			$theme_templates = array(
				'single-partyminder_event.php',
				'single-event.php',
				'partyminder-event.php',
				'event.php',
			);

			$theme_template = locate_template( $theme_templates );
			if ( $theme_template ) {
				return $theme_template;
			}
		}

		return $template;
	}

	public function event_single_template( $template ) {
		return $this->event_template_include( $template );
	}

	public function prevent_fake_post_meta( $value, $object_id, $meta_key, $single ) {
		// Prevent meta queries for our fake post IDs
		if ( $object_id >= 999999 && isset( $GLOBALS['partyminder_current_event'] ) ) {
			return array(); // Return empty array to prevent database queries
		}
		return $value;
	}

	public function ensure_fake_post_exists( $post, $post_id ) {
		// If WordPress is looking for our fake post, return the global post
		if ( $post_id >= 999999 && isset( $GLOBALS['post'] ) && $GLOBALS['post']->ID == $post_id ) {
			return $GLOBALS['post'];
		}
		return $post;
	}

	public function ensure_posts_array( $posts, $query ) {
		// Ensure we have posts in the array for our event pages
		if ( isset( $GLOBALS['partyminder_current_event'] ) && empty( $posts ) && isset( $GLOBALS['post'] ) ) {
			return array( $GLOBALS['post'] );
		}
		return $posts;
	}

	public function ensure_global_post_setup() {
		// Make sure our fake post is properly set up everywhere WordPress might look
		if ( isset( $GLOBALS['partyminder_current_event'] ) && isset( $GLOBALS['post'] ) ) {
			global $wp_query;

			// Ensure the post is converted to a proper WP_Post object
			if ( ! ( $GLOBALS['post'] instanceof WP_Post ) ) {
				$GLOBALS['post'] = new WP_Post( $GLOBALS['post'] );
			}

			// Ensure query objects are properly set
			if ( $wp_query->post && ! ( $wp_query->post instanceof WP_Post ) ) {
				$wp_query->post = new WP_Post( $wp_query->post );
			}

			if ( isset( $wp_query->posts[0] ) && ! ( $wp_query->posts[0] instanceof WP_Post ) ) {
				$wp_query->posts[0] = new WP_Post( $wp_query->posts[0] );
			}

			if ( $wp_query->queried_object && ! ( $wp_query->queried_object instanceof WP_Post ) ) {
				$wp_query->queried_object = new WP_Post( $wp_query->queried_object );
			}
		}
	}

	// Individual event page detection and content injection moved to Page Router and Page Content Injector classes


	// inject_events_content method moved to Page_Content_Injector class

	// inject_create_event_content method moved to Page_Content_Injector class

	// inject_dashboard_content method moved to Page_Content_Injector class

	// inject_my_events_content method moved to Page_Content_Injector class

	// inject_edit_event_content method moved to Page_Content_Injector class

	// inject_login_content method moved to Page_Content_Injector class

	// inject_profile_content method moved to Page_Content_Injector class

	// inject_conversations_content method moved to Page_Content_Injector class

	// inject_topic_conversations_content method moved to Page_Content_Injector class

	// inject_single_conversation_content method moved to Page_Content_Injector class

	// inject_create_conversation_content method moved to Page_Content_Injector class

	// Body class methods moved to Page_Body_Class_Manager class

	/**
	 * Enqueue page-specific assets
	 */
	public function enqueue_page_specific_assets() {
		global $post;

		if ( ! is_page() || ! $post ) {
			return;
		}

		$page_type = get_post_meta( $post->ID, '_partyminder_page_type', true );

		if ( $page_type ) {
			// Add Flatpickr for event forms with enhanced date picker
			if ( in_array( $page_type, array( 'create-event', 'edit-event', 'create-community-event' ) ) ) {
				wp_enqueue_style( 'flatpickr', PARTYMINDER_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.css', array(), '4.6.13' );
				wp_enqueue_script( 'flatpickr', PARTYMINDER_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.js', array(), '4.6.13', true );
				wp_enqueue_script( 'partyminder-enhanced-date-picker', PARTYMINDER_PLUGIN_URL . 'assets/js/enhanced-date-picker.js', array( 'jquery', 'flatpickr' ), PARTYMINDER_VERSION, true );
			}

			// Add page-specific JavaScript
			if ( $page_type === 'conversations' || $page_type === 'dashboard' ) {
				wp_enqueue_script(
					'partyminder-conversations',
					PARTYMINDER_PLUGIN_URL . 'assets/js/conversations.js',
					array( 'jquery', 'partyminder-public' ),
					PARTYMINDER_VERSION,
					true
				);
				wp_enqueue_script(
					'partyminder-conversations-circles',
					PARTYMINDER_PLUGIN_URL . 'assets/js/conversations-circles.js',
					array( 'jquery', 'partyminder-public' ),
					PARTYMINDER_VERSION,
					true
				);
			}

			// Add Flatpickr for event creation/editing
			if ( $page_type === 'create-event' || $page_type === 'edit-event' ) {
				wp_enqueue_style(
					'flatpickr',
					PARTYMINDER_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.css',
					array(),
					'4.6.13'
				);
				wp_enqueue_script(
					'flatpickr',
					PARTYMINDER_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.js',
					array(),
					'4.6.13',
					true
				);
				wp_enqueue_script(
					'partyminder-flatpickr-init',
					PARTYMINDER_PLUGIN_URL . 'assets/js/flatpickr-event-form.js',
					array( 'jquery', 'flatpickr' ),
					'1.0.0',
					true
				);
			}
		}
	}

	public function ajax_delete_event() {
		check_ajax_referer( 'partyminder_event_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$event_id = intval( $_POST['event_id'] );

		if ( ! $event_id ) {
			wp_send_json_error( __( 'Missing event ID.', 'partyminder' ) );
			return;
		}

		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
		$event_manager = new PartyMinder_Event_Manager();

		// Delete the event (includes permission checking)
		$result = $event_manager->delete_event( $event_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
			return;
		}

		wp_send_json_success(
			array(
				'message'      => __( 'Event deleted successfully.', 'partyminder' ),
				'redirect_url' => home_url( '/my-events' ),
			)
		);
	}

	public function ajax_admin_delete_event() {
		check_ajax_referer( 'partyminder_event_action', 'nonce' );

		if ( ! current_user_can( 'delete_others_posts' ) ) {
			wp_send_json_error( __( 'You do not have permission to delete events.', 'partyminder' ) );
			return;
		}

		$event_id = intval( $_POST['event_id'] );

		if ( ! $event_id ) {
			wp_send_json_error( __( 'Missing event ID.', 'partyminder' ) );
			return;
		}

		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
		$event_manager = new PartyMinder_Event_Manager();

		// Delete the event (will bypass permission checking since user is admin)
		$result = $event_manager->delete_event( $event_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
			return;
		}

		wp_send_json_success(
			array(
				'message' => __( 'Event deleted successfully.', 'partyminder' ),
			)
		);
	}

	/**
	 * Redirect WordPress login to custom login page
	 */
	public function redirect_to_custom_login() {
		// Don't redirect if we're already on our custom login page
		if ( strpos( $_SERVER['REQUEST_URI'], '/login' ) !== false ) {
			return;
		}

		// Don't redirect admin login, AJAX requests, or direct wp-admin access
		if ( is_admin() || defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Don't redirect if accessing wp-login.php directly for admin access
		if ( strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false &&
			isset( $_GET['redirect_to'] ) &&
			strpos( $_GET['redirect_to'], 'wp-admin' ) !== false ) {
			return;
		}

		// Don't redirect if it's a logout action
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'logout' ) {
			return;
		}

		// Redirect to our custom login page
		$custom_login_url = self::get_login_url();

		// Preserve redirect_to parameter
		if ( isset( $_GET['redirect_to'] ) ) {
			$custom_login_url = add_query_arg( 'redirect_to', urlencode( $_GET['redirect_to'] ), $custom_login_url );
		}

		wp_redirect( $custom_login_url );
		exit;
	}

	/**
	 * Override wp_login_url to use custom login page
	 */
	public function smart_login_url( $login_url, $redirect ) {
		// Always use default WordPress login for admin-related requests
		if ( is_admin() ||
			( is_string( $redirect ) && strpos( $redirect, 'wp-admin' ) !== false ) ||
			( isset( $_SERVER['HTTP_REFERER'] ) && strpos( $_SERVER['HTTP_REFERER'], 'wp-admin' ) !== false ) ||
			( isset( $_GET['redirect_to'] ) && strpos( $_GET['redirect_to'], 'wp-admin' ) !== false ) ) {
			return $login_url;
		}

		// Use custom login page for frontend requests
		$custom_login_url = self::get_login_url();

		if ( $redirect ) {
			$custom_login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $custom_login_url );
		}

		return $custom_login_url;
	}

	/**
	 * Maybe create custom embed when WordPress would otherwise make a link
	 * This runs when WordPress gives up on oEmbed and is about to make a plain link
	 */
	public function maybe_custom_embed( $output, $url ) {
		// Debug: Only process ljholiday.com URLs for now
		if ( strpos( $url, 'ljholiday.com' ) === false ) {
			return $output;
		}
		
		// Get metadata for the URL
		$metadata = PartyMinder_URL_Preview::get_url_metadata( $url );
		
		if ( $metadata ) {
			// Very simple test - just return the title with some basic formatting
			return '<div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;"><strong>' . esc_html( $metadata['title'] ) . '</strong><br><em>' . esc_html( $url ) . '</em></div>';
		}
		
		// Return the original output (likely a link) if we can't get metadata
		return $output;
	}

	/**
	 * Override WordPress get_avatar_url to use PartyMinder custom avatars
	 * 
	 * @param string $url The URL of the avatar
	 * @param mixed $id_or_email User ID, email, or user object
	 * @param array $args Arguments for the avatar
	 * @return string Avatar URL
	 */
	public function get_partyminder_avatar_url( $url, $id_or_email, $args ) {
		$user_id = $this->get_user_id_from_avatar_data( $id_or_email );
		
		if ( ! $user_id ) {
			return $url; // Return original URL if we can't determine user ID
		}
		
		// Get user's profile data
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-profile-manager.php';
		$profile = PartyMinder_Profile_Manager::get_user_profile( $user_id );
		
		// Check if user has a custom avatar and prefers to use it
		if ( ! empty( $profile['profile_image'] ) && $profile['avatar_source'] === 'custom' ) {
			return $profile['profile_image'];
		}
		
		return $url; // Return original URL (Gravatar or default)
	}

	/**
	 * Override WordPress get_avatar to use PartyMinder custom avatars
	 * 
	 * @param string $avatar The avatar HTML
	 * @param mixed $id_or_email User ID, email, or user object  
	 * @param int $size Avatar size
	 * @param string $default Default avatar type
	 * @param string $alt Alt text
	 * @param array $args Additional arguments
	 * @return string Avatar HTML
	 */
	public function get_partyminder_avatar( $avatar, $id_or_email, $size, $default, $alt, $args ) {
		$user_id = $this->get_user_id_from_avatar_data( $id_or_email );
		
		if ( ! $user_id ) {
			return $avatar; // Return original avatar if we can't determine user ID
		}
		
		// Get user's profile data
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-profile-manager.php';
		$profile = PartyMinder_Profile_Manager::get_user_profile( $user_id );
		
		// Check if user has a custom avatar and prefers to use it
		if ( ! empty( $profile['profile_image'] ) && $profile['avatar_source'] === 'custom' ) {
			$avatar_url = $profile['profile_image'];
			$class = isset( $args['class'] ) ? $args['class'] : '';
			$class = is_array( $class ) ? implode( ' ', $class ) : $class;
			
			return sprintf( 
				'<img alt="%s" src="%s" class="avatar avatar-%d %s" height="%d" width="%d" />',
				esc_attr( $alt ),
				esc_url( $avatar_url ),
				(int) $size,
				esc_attr( $class ),
				(int) $size,
				(int) $size
			);
		}
		
		return $avatar; // Return original avatar (Gravatar or default)
	}

	/**
	 * Helper function to extract user ID from various avatar data types
	 * 
	 * @param mixed $id_or_email User ID, email, or user object
	 * @return int|false User ID or false if not found
	 */
	private function get_user_id_from_avatar_data( $id_or_email ) {
		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}
		
		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			return $user ? $user->ID : false;
		}
		
		if ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
			return (int) $id_or_email->user_id;
		}
		
		if ( is_object( $id_or_email ) && isset( $id_or_email->ID ) ) {
			return (int) $id_or_email->ID;
		}
		
		return false;
	}

	/**
	 * Create personal community for new user registration
	 * Per Step 2 of the circles implementation plan
	 */
	public function create_personal_community_for_new_user( $user_id ) {
		// Only create if communities feature is enabled
		if ( ! PartyMinder_Feature_Flags::is_communities_enabled() ) {
			return;
		}

		$community_id = PartyMinder_Personal_Community_Service::create_for_user( $user_id );
		
		if ( $community_id && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "PartyMinder: Created personal community (ID: $community_id) for new user $user_id" );
		}
	}
}
