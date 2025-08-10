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
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-image-upload-component.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-profile-manager.php';

		// AJAX handler classes
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-ajax-handler.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-ajax-handler.php';
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-ajax-handler.php';

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

		// SEO and structured data
		add_action( 'wp_head', array( $this, 'add_structured_data' ) );

		// Keep only essential AJAX handlers that aren't moved to dedicated classes
		add_action( 'wp_ajax_partyminder_rsvp', array( $this, 'ajax_rsvp' ) );
		add_action( 'wp_ajax_nopriv_partyminder_rsvp', array( $this, 'ajax_rsvp' ) );
		add_action( 'wp_ajax_partyminder_generate_ai_plan', array( $this, 'ajax_generate_ai_plan' ) );

		// Image upload AJAX handler
		add_action( 'wp_ajax_partyminder_upload_image', array( 'PartyMinder_Image_Upload_Component', 'handle_ajax_upload' ) );
		add_action( 'wp_ajax_nopriv_partyminder_upload_image', array( 'PartyMinder_Image_Upload_Component', 'handle_ajax_upload' ) );
		// All AJAX handlers are now handled by dedicated handler classes

		// Smart login override - frontend only, preserves wp-admin access
		add_filter( 'wp_login_url', array( $this, 'smart_login_url' ), 10, 2 );
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
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'partyminder_admin_nonce' ),
					'event_nonce' => wp_create_nonce( 'partyminder_event_action' ),
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

		$event_type  = sanitize_text_field( $_POST['event_type'] );
		$guest_count = intval( $_POST['guest_count'] );
		$dietary     = sanitize_text_field( $_POST['dietary'] );
		$budget      = sanitize_text_field( $_POST['budget'] );

		$plan = $this->ai_assistant->generate_plan( $event_type, $guest_count, $dietary, $budget );

		wp_send_json_success( $plan );
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

			$form_errors = array();

			// Validate required fields
			if ( empty( $_POST['event_title'] ) ) {
				$form_errors[] = __( 'Event title is required.', 'partyminder' );
			}
			if ( empty( $_POST['event_date'] ) ) {
				$form_errors[] = __( 'Event date is required.', 'partyminder' );
			}
			if ( empty( $_POST['host_email'] ) ) {
				$form_errors[] = __( 'Host email is required.', 'partyminder' );
			}

			// If no errors, create the event
			if ( empty( $form_errors ) ) {
				$event_data = array(
					'title'       => sanitize_text_field( wp_unslash( $_POST['event_title'] ) ),
					'description' => wp_kses_post( wp_unslash( $_POST['event_description'] ) ),
					'event_date'  => sanitize_text_field( $_POST['event_date'] ),
					'venue'       => sanitize_text_field( $_POST['venue_info'] ),
					'guest_limit' => intval( $_POST['guest_limit'] ),
					'host_email'  => sanitize_email( $_POST['host_email'] ),
					'host_notes'  => wp_kses_post( wp_unslash( $_POST['host_notes'] ) ),
				);

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
			$page_keys = array( 'events', 'create-event', 'my-events' );

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
		$page_keys = array( 'events', 'create-event', 'my-events', 'edit-event', 'create-conversation', 'create-community' );

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

			// Add page-specific JavaScript
			if ( $page_type === 'conversations' ) {
				wp_enqueue_script(
					'partyminder-conversations',
					PARTYMINDER_PLUGIN_URL . 'assets/js/conversations.js',
					array( 'jquery', 'partyminder-public' ),
					PARTYMINDER_VERSION,
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
}
