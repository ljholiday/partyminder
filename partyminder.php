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
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PARTYMINDER_VERSION', '1.0.0');
define('PARTYMINDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PARTYMINDER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PARTYMINDER_PLUGIN_FILE', __FILE__);

// Load activation/deactivation classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-activator.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-deactivator.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('PartyMinder_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('PartyMinder_Deactivator', 'deactivate'));

// Disable WordPress magic quotes for cleaner data handling
add_action('init', function() {
    if (function_exists('wp_magic_quotes')) {
        // Remove magic quotes from all input data
        $_GET = array_map('stripslashes_deep', $_GET);
        $_POST = array_map('stripslashes_deep', $_POST);
        $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    }
}, 1);

// Initialize plugin
add_action('plugins_loaded', array('PartyMinder', 'get_instance'));

/**
 * Main PartyMinder Class
 */
class PartyMinder {
    
    private static $instance = null;
    private $event_manager;
    private $guest_manager;
    private $ai_assistant;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-ai-assistant.php';
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-admin.php';
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-feature-flags.php';
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-profile-manager.php';
        
        // Load communities features only if enabled
        if (PartyMinder_Feature_Flags::is_communities_enabled()) {
            require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
            require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-member-identity-manager.php';
        }
        
        // Load AT Protocol features only if enabled
        if (PartyMinder_Feature_Flags::is_at_protocol_enabled()) {
            require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-bluesky-client.php';
            require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-at-protocol-manager.php';
        }
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Template system - theme integration - run earlier
        add_action('wp', array($this, 'handle_custom_pages'), 5);
        add_action('template_redirect', array($this, 'handle_form_submissions'));
        
        // Handle individual event pages through content injection
        add_action('wp', array($this, 'detect_individual_event_page'), 5);
        
        // No longer need post meta suppression - using pages now
        
        // Register shortcodes early
        add_action('wp_loaded', array($this, 'register_shortcodes'));
        
        // Migration code removed - not needed for new installations
        
        // Add simple rewrite rule for individual events on the events page
        add_action('init', array($this, 'add_event_rewrite_rules'));
        
        // Theme integration hooks
        add_filter('body_class', array($this, 'add_body_classes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_page_specific_assets'));
        
        // SEO and structured data
        add_action('wp_head', array($this, 'add_structured_data'));
        add_filter('document_title_parts', array($this, 'modify_page_titles'));
        
        // AJAX handlers
        add_action('wp_ajax_partyminder_create_event', array($this, 'ajax_create_event'));
        add_action('wp_ajax_nopriv_partyminder_create_event', array($this, 'ajax_create_event'));
        add_action('wp_ajax_partyminder_update_event', array($this, 'ajax_update_event'));
        add_action('wp_ajax_nopriv_partyminder_update_event', array($this, 'ajax_update_event'));
        add_action('wp_ajax_partyminder_rsvp', array($this, 'ajax_rsvp'));
        add_action('wp_ajax_nopriv_partyminder_rsvp', array($this, 'ajax_rsvp'));
        add_action('wp_ajax_partyminder_create_conversation', array($this, 'ajax_create_conversation'));
        add_action('wp_ajax_nopriv_partyminder_create_conversation', array($this, 'ajax_create_conversation'));
        add_action('wp_ajax_partyminder_add_reply', array($this, 'ajax_add_reply'));
        add_action('wp_ajax_nopriv_partyminder_add_reply', array($this, 'ajax_add_reply'));
        add_action('wp_ajax_partyminder_generate_ai_plan', array($this, 'ajax_generate_ai_plan'));
        
        // Communities AJAX handlers (only when feature enabled)
        if (PartyMinder_Feature_Flags::is_communities_enabled()) {
            add_action('wp_ajax_partyminder_join_community', array($this, 'ajax_join_community'));
            add_action('wp_ajax_nopriv_partyminder_join_community', array($this, 'ajax_join_community'));
            add_action('wp_ajax_partyminder_leave_community', array($this, 'ajax_leave_community'));
            add_action('wp_ajax_partyminder_create_community', array($this, 'ajax_create_community'));
            add_action('wp_ajax_partyminder_update_community', array($this, 'ajax_update_community'));
            add_action('wp_ajax_partyminder_get_community_members', array($this, 'ajax_get_community_members'));
            add_action('wp_ajax_partyminder_update_member_role', array($this, 'ajax_update_member_role'));
            add_action('wp_ajax_partyminder_remove_member', array($this, 'ajax_remove_member'));
            add_action('wp_ajax_partyminder_send_invitation', array($this, 'ajax_send_invitation'));
            add_action('wp_ajax_partyminder_get_community_invitations', array($this, 'ajax_get_community_invitations'));
            add_action('wp_ajax_partyminder_cancel_invitation', array($this, 'ajax_cancel_invitation'));
            
            // Event invitation AJAX handlers
            add_action('wp_ajax_partyminder_send_event_invitation', array($this, 'ajax_send_event_invitation'));
            add_action('wp_ajax_partyminder_get_event_invitations', array($this, 'ajax_get_event_invitations'));
            add_action('wp_ajax_partyminder_cancel_event_invitation', array($this, 'ajax_cancel_event_invitation'));
            add_action('wp_ajax_partyminder_get_event_stats', array($this, 'ajax_get_event_stats'));
            add_action('wp_ajax_partyminder_get_event_guests', array($this, 'ajax_get_event_guests'));
            add_action('wp_ajax_partyminder_delete_event', array($this, 'ajax_delete_event'));
        }
        
        // Admin AJAX handlers (always available for admins)
        if (is_admin()) {
            add_action('wp_ajax_partyminder_admin_delete_event', array($this, 'ajax_admin_delete_event'));
        }
        
        // Smart login override - frontend only, preserves wp-admin access
        add_filter('wp_login_url', array($this, 'smart_login_url'), 10, 2);
    }
    
    public function register_shortcodes() {
        // Shortcodes
        add_shortcode('partyminder_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('partyminder_event_form', array($this, 'event_form_shortcode'));
        add_shortcode('partyminder_event_edit_form', array($this, 'event_edit_form_shortcode'));
        add_shortcode('partyminder_rsvp_form', array($this, 'rsvp_form_shortcode'));
        add_shortcode('partyminder_events_list', array($this, 'events_list_shortcode'));
        add_shortcode('partyminder_my_events', array($this, 'my_events_shortcode'));
        add_shortcode('partyminder_conversations', array($this, 'conversations_shortcode'));
        add_shortcode('partyminder_profile', array($this, 'profile_shortcode'));
        add_shortcode('partyminder_login', array($this, 'login_shortcode'));
        
        // Communities shortcode (only if feature enabled)
        if (PartyMinder_Feature_Flags::is_communities_enabled()) {
            add_shortcode('partyminder_communities', array($this, 'communities_shortcode'));
        }
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('partyminder', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize managers
        $this->event_manager = new PartyMinder_Event_Manager();
        $this->guest_manager = new PartyMinder_Guest_Manager();
        $this->conversation_manager = new PartyMinder_Conversation_Manager();
        $this->ai_assistant = new PartyMinder_AI_Assistant();
        
        // Initialize communities managers only if features are enabled
        if (PartyMinder_Feature_Flags::is_communities_enabled()) {
            $this->community_manager = new PartyMinder_Community_Manager();
            $this->member_identity_manager = new PartyMinder_Member_Identity_Manager();
        }
        
        // Initialize AT Protocol manager only if feature is enabled
        if (PartyMinder_Feature_Flags::is_at_protocol_enabled()) {
            $this->at_protocol_manager = new PartyMinder_AT_Protocol_Manager();
        }
        
        
        // Initialize admin
        if (is_admin()) {
            new PartyMinder_Admin();
        }
    }
    
    
    
    public function enqueue_public_scripts() {
        wp_enqueue_style('partyminder', PARTYMINDER_PLUGIN_URL . 'assets/css/partyminder.css', array(), PARTYMINDER_VERSION);
        
        // Add dynamic colors from admin settings
        $primary_color = get_option('partyminder_primary_color', '#667eea');
        $secondary_color = get_option('partyminder_secondary_color', '#764ba2');
        
        $custom_css = ":root {
            --pm-primary: {$primary_color} !important;
            --pm-secondary: {$secondary_color} !important;
        }";
        
        wp_add_inline_style('partyminder', $custom_css);
        
        wp_enqueue_script('partyminder-public', PARTYMINDER_PLUGIN_URL . 'assets/js/public.js', array('jquery'), PARTYMINDER_VERSION, true);
        
        $current_user = wp_get_current_user();
        wp_localize_script('partyminder-public', 'partyminder_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('partyminder_nonce'),
            'community_nonce' => wp_create_nonce('partyminder_community_action'),
            'event_nonce' => wp_create_nonce('partyminder_event_action'),
            'at_protocol_nonce' => wp_create_nonce('partyminder_at_protocol'),
            'current_user' => array(
                'id' => $current_user->ID,
                'name' => $current_user->display_name,
                'email' => $current_user->user_email
            ),
            'features' => PartyMinder_Feature_Flags::get_feature_status_for_js(),
            'strings' => array(
                'loading' => __('Loading...', 'partyminder'),
                'error' => __('An error occurred. Please try again.', 'partyminder'),
                'success' => __('Success!', 'partyminder'),
                'confirm_join' => __('Are you sure you want to join this community?', 'partyminder'),
                'confirm_leave' => __('Are you sure you want to leave this community?', 'partyminder')
            )
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'partyminder') !== false) {
            wp_enqueue_style('partyminder-admin', PARTYMINDER_PLUGIN_URL . 'assets/css/partyminder.css', array(), PARTYMINDER_VERSION);
            
            // Add dynamic colors from admin settings
            $primary_color = get_option('partyminder_primary_color', '#667eea');
            $secondary_color = get_option('partyminder_secondary_color', '#764ba2');
            
            $custom_css = ":root {
                --pm-primary: {$primary_color} !important;
                --pm-secondary: {$secondary_color} !important;
            }";
            
            wp_add_inline_style('partyminder-admin', $custom_css);
            
            wp_enqueue_script('partyminder-admin', PARTYMINDER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PARTYMINDER_VERSION, true);
            
            wp_localize_script('partyminder-admin', 'partyminder_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('partyminder_admin_nonce'),
                'event_nonce' => wp_create_nonce('partyminder_event_action')
            ));
        }
    }
    
    // AJAX Handlers
    public function ajax_create_event() {
        // Use the correct nonce from the form
        check_ajax_referer('create_partyminder_event', 'partyminder_event_nonce');
        
        // Validate required fields
        $form_errors = array();
        if (empty($_POST['event_title'])) {
            $form_errors[] = __('Event title is required.', 'partyminder');
        }
        if (empty($_POST['event_date'])) {
            $form_errors[] = __('Event date is required.', 'partyminder');
        }
        if (empty($_POST['host_email'])) {
            $form_errors[] = __('Host email is required.', 'partyminder');
        }
        
        if (!empty($form_errors)) {
            wp_send_json_error(implode(' ', $form_errors));
        }
        
        $event_data = array(
            'title' => sanitize_text_field(wp_unslash($_POST['event_title'])),
            'description' => wp_kses_post(wp_unslash($_POST['event_description'])),
            'event_date' => sanitize_text_field($_POST['event_date']),
            'venue' => sanitize_text_field($_POST['venue_info']),
            'guest_limit' => intval($_POST['guest_limit']),
            'host_email' => sanitize_email($_POST['host_email']),
            'host_notes' => wp_kses_post(wp_unslash($_POST['host_notes']))
        );
        
        // Ensure event manager is available
        if (!$this->event_manager) {
            $this->load_dependencies();
            $this->event_manager = new PartyMinder_Event_Manager();
        }
        
        $event_id = $this->event_manager->create_event($event_data);
        
        if (!is_wp_error($event_id)) {
            // Get the created event to get its slug
            $created_event = $this->event_manager->get_event($event_id);
            
            // Set transient for success message display
            $creation_data = array(
                'event_id' => $event_id,
                'event_url' => home_url('/events/' . $created_event->slug),
                'event_title' => $created_event->title
            );
            set_transient('partyminder_event_created_' . get_current_user_id(), $creation_data, 300); // 5 minutes
            
            wp_send_json_success(array(
                'event_id' => $event_id,
                'message' => __('Event created successfully!', 'partyminder'),
                'event_url' => home_url('/events/' . $created_event->slug)
            ));
        } else {
            wp_send_json_error($event_id->get_error_message());
        }
    }
    
    public function ajax_update_event() {
        // Use the correct nonce from the form
        check_ajax_referer('edit_partyminder_event', 'partyminder_edit_event_nonce');
        
        // Get event ID
        $event_id = intval($_POST['event_id']);
        if (!$event_id) {
            wp_send_json_error(__('Event ID is required.', 'partyminder'));
        }
        
        // Load event manager
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        $event_manager = new PartyMinder_Event_Manager();
        
        // Get the event to check permissions
        $event = $event_manager->get_event($event_id);
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
        }
        
        // Check permissions - only event creator or admin can edit
        $current_user = wp_get_current_user();
        $can_edit = false;
        
        if (current_user_can('edit_posts') || 
            (is_user_logged_in() && $current_user->ID == $event->author_id) ||
            ($current_user->user_email == $event->host_email)) {
            $can_edit = true;
        }
        
        if (!$can_edit) {
            wp_send_json_error(__('You do not have permission to edit this event.', 'partyminder'));
        }
        
        // Validate required fields
        $form_errors = array();
        if (empty($_POST['event_title'])) {
            $form_errors[] = __('Event title is required.', 'partyminder');
        }
        if (empty($_POST['event_date'])) {
            $form_errors[] = __('Event date is required.', 'partyminder');
        }
        if (empty($_POST['host_email'])) {
            $form_errors[] = __('Host email is required.', 'partyminder');
        }
        
        if (!empty($form_errors)) {
            wp_send_json_error(implode(' ', $form_errors));
        }
        
        // Prepare update data
        $event_data = array(
            'title' => sanitize_text_field(wp_unslash($_POST['event_title'])),
            'description' => wp_kses_post(wp_unslash($_POST['event_description'])),
            'event_date' => sanitize_text_field($_POST['event_date']),
            'venue' => sanitize_text_field($_POST['venue_info']),
            'guest_limit' => intval($_POST['guest_limit']),
            'host_email' => sanitize_email($_POST['host_email']),
            'host_notes' => wp_kses_post(wp_unslash($_POST['host_notes']))
        );
        
        // Update the event
        $result = $event_manager->update_event($event_id, $event_data);
        
        if (!is_wp_error($result)) {
            // Get the updated event to get its slug
            $updated_event = $event_manager->get_event($event_id);
            wp_send_json_success(array(
                'event_id' => $event_id,
                'message' => __('Event updated successfully!', 'partyminder'),
                'event_url' => home_url('/events/' . $updated_event->slug)
            ));
        } else {
            wp_send_json_error($result->get_error_message());
        }
    }
    
    public function ajax_rsvp() {
        check_ajax_referer('partyminder_nonce', 'nonce');
        
        $rsvp_data = array(
            'event_id' => intval($_POST['event_id']),
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'status' => sanitize_text_field($_POST['status']),
            'dietary' => sanitize_text_field($_POST['dietary']),
            'notes' => sanitize_text_field($_POST['notes'])
        );
        
        $result = $this->guest_manager->process_rsvp($rsvp_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function ajax_generate_ai_plan() {
        check_ajax_referer('partyminder_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'partyminder'));
        }
        
        $event_type = sanitize_text_field($_POST['event_type']);
        $guest_count = intval($_POST['guest_count']);
        $dietary = sanitize_text_field($_POST['dietary']);
        $budget = sanitize_text_field($_POST['budget']);
        
        $plan = $this->ai_assistant->generate_plan($event_type, $guest_count, $dietary, $budget);
        
        wp_send_json_success($plan);
    }
    
    public function ajax_create_conversation() {
        check_ajax_referer('partyminder_nonce', 'nonce');
        
        // Get current user info
        $current_user = wp_get_current_user();
        $user_email = '';
        $user_name = '';
        $user_id = 0;
        
        if (is_user_logged_in()) {
            $user_email = $current_user->user_email;
            $user_name = $current_user->display_name;
            $user_id = $current_user->ID;
        } else {
            // Allow non-logged-in users if they provide email and name
            $user_email = sanitize_email($_POST['guest_email'] ?? '');
            $user_name = sanitize_text_field($_POST['guest_name'] ?? '');
            if (empty($user_email) || empty($user_name)) {
                wp_send_json_error(__('Please provide your name and email to start a conversation.', 'partyminder'));
            }
        }
        
        // Validate input
        $topic_id = intval($_POST['topic_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $content = wp_kses_post($_POST['content'] ?? '');
        
        if (empty($topic_id) || empty($title) || empty($content)) {
            wp_send_json_error(__('Please fill in all required fields.', 'partyminder'));
        }
        
        // Create conversation
        if (!$this->conversation_manager) {
            $this->load_dependencies();
            $this->conversation_manager = new PartyMinder_Conversation_Manager();
        }
        
        $conversation_data = array(
            'topic_id' => $topic_id,
            'title' => $title,
            'content' => $content,
            'author_id' => $user_id,
            'author_name' => $user_name,
            'author_email' => $user_email
        );
        
        $conversation_id = $this->conversation_manager->create_conversation($conversation_data);
        
        if ($conversation_id) {
            wp_send_json_success(array(
                'conversation_id' => $conversation_id,
                'message' => __('Conversation started successfully!', 'partyminder')
            ));
        } else {
            wp_send_json_error(__('Failed to create conversation. Please try again.', 'partyminder'));
        }
    }
    
    public function ajax_add_reply() {
        check_ajax_referer('partyminder_nonce', 'nonce');
        
        // Get current user info
        $current_user = wp_get_current_user();
        $user_email = '';
        $user_name = '';
        $user_id = 0;
        
        if (is_user_logged_in()) {
            $user_email = $current_user->user_email;
            $user_name = $current_user->display_name;
            $user_id = $current_user->ID;
        } else {
            // Allow non-logged-in users if they provide email and name
            $user_email = sanitize_email($_POST['guest_email'] ?? '');
            $user_name = sanitize_text_field($_POST['guest_name'] ?? '');
            if (empty($user_email) || empty($user_name)) {
                wp_send_json_error(__('Please provide your name and email to reply.', 'partyminder'));
            }
        }
        
        // Validate input
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $parent_reply_id = intval($_POST['parent_reply_id'] ?? 0) ?: null;
        $content = wp_kses_post($_POST['content'] ?? '');
        
        if (empty($conversation_id) || empty($content)) {
            wp_send_json_error(__('Please provide a message to reply.', 'partyminder'));
        }
        
        // Add reply
        if (!$this->conversation_manager) {
            $this->load_dependencies();
            $this->conversation_manager = new PartyMinder_Conversation_Manager();
        }
        
        $reply_data = array(
            'content' => $content,
            'author_id' => $user_id,
            'author_name' => $user_name,
            'author_email' => $user_email,
            'parent_reply_id' => $parent_reply_id
        );
        
        $reply_id = $this->conversation_manager->add_reply($conversation_id, $reply_data);
        
        if ($reply_id) {
            wp_send_json_success(array(
                'reply_id' => $reply_id,
                'message' => __('Reply added successfully!', 'partyminder')
            ));
        } else {
            wp_send_json_error(__('Failed to add reply. Please try again.', 'partyminder'));
        }
    }
    
    // Communities AJAX Methods
    public function ajax_join_community() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to join a community.', 'partyminder'));
            return;
        }
        
        $community_id = intval($_POST['community_id']);
        $current_user = wp_get_current_user();
        
        if (!$community_id) {
            wp_send_json_error(__('Invalid community.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        
        // Check if community exists
        $community = $community_manager->get_community($community_id);
        if (!$community) {
            wp_send_json_error(__('Community not found.', 'partyminder'));
            return;
        }
        
        // Check if already a member
        if ($community_manager->is_member($community_id, $current_user->ID)) {
            wp_send_json_error(__('You are already a member of this community.', 'partyminder'));
            return;
        }
        
        // Add member
        $member_data = array(
            'user_id' => $current_user->ID,
            'email' => $current_user->user_email,
            'display_name' => $current_user->display_name,
            'role' => 'member'
        );
        
        $result = $community_manager->add_member($community_id, $member_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(__('Welcome to %s!', 'partyminder'), $community->name),
                'redirect_url' => home_url('/communities/' . $community->slug)
            ));
        } else {
            wp_send_json_error(__('Failed to join community. Please try again.', 'partyminder'));
        }
    }
    
    public function ajax_leave_community() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $community_id = intval($_POST['community_id']);
        $current_user = wp_get_current_user();
        
        if (!$community_id) {
            wp_send_json_error(__('Invalid community.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        
        // Check if user is a member
        if (!$community_manager->is_member($community_id, $current_user->ID)) {
            wp_send_json_error(__('You are not a member of this community.', 'partyminder'));
            return;
        }
        
        // Check if user is the only admin
        $user_role = $community_manager->get_member_role($community_id, $current_user->ID);
        if ($user_role === 'admin') {
            $admin_count = $community_manager->get_admin_count($community_id);
            if ($admin_count <= 1) {
                wp_send_json_error(__('You cannot leave as you are the only admin. Please promote another member first.', 'partyminder'));
                return;
            }
        }
        
        $result = $community_manager->remove_member($community_id, $current_user->ID);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('You have left the community.', 'partyminder'),
                'redirect_url' => PartyMinder::get_communities_url()
            ));
        } else {
            wp_send_json_error(__('Failed to leave community. Please try again.', 'partyminder'));
        }
    }
    
    public function ajax_create_community() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to create a community.', 'partyminder'));
            return;
        }
        
        if (!PartyMinder_Feature_Flags::can_user_create_community()) {
            wp_send_json_error(__('You do not have permission to create communities.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        $current_user = wp_get_current_user();
        
        // Validate input
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $type = sanitize_text_field($_POST['type']);
        $privacy = sanitize_text_field($_POST['privacy']);
        
        if (empty($name)) {
            wp_send_json_error(__('Community name is required.', 'partyminder'));
            return;
        }
        
        if (strlen($name) < 3 || strlen($name) > 100) {
            wp_send_json_error(__('Community name must be between 3 and 100 characters.', 'partyminder'));
            return;
        }
        
        $valid_types = array('standard', 'work', 'faith', 'family', 'hobby');
        if (!in_array($type, $valid_types)) {
            $type = 'standard';
        }
        
        $valid_privacy = array('public', 'private');
        if (!in_array($privacy, $valid_privacy)) {
            $privacy = 'public';
        }
        
        $community_data = array(
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'privacy' => $privacy,
            'creator_id' => $current_user->ID,
            'creator_email' => $current_user->user_email
        );
        
        $community_id = $community_manager->create_community($community_data);
        
        if (is_wp_error($community_id)) {
            wp_send_json_error($community_id->get_error_message());
            return;
        }
        
        if ($community_id) {
            $community = $community_manager->get_community($community_id);
            if ($community) {
                wp_send_json_success(array(
                    'message' => sprintf(__('Community "%s" created successfully!', 'partyminder'), $name),
                    'community_id' => $community_id,
                    'redirect_url' => home_url('/communities/' . $community->slug)
                ));
            } else {
                wp_send_json_error(__('Community created but could not retrieve details.', 'partyminder'));
            }
        } else {
            wp_send_json_error(__('Failed to create community. Please try again.', 'partyminder'));
        }
    }
    
    public function ajax_update_community() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $community_id = intval($_POST['community_id']);
        $current_user = wp_get_current_user();
        
        if (!$community_id) {
            wp_send_json_error(__('Invalid community.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        
        // Check if user is admin of this community
        $user_role = $community_manager->get_member_role($community_id, $current_user->ID);
        if ($user_role !== 'admin') {
            wp_send_json_error(__('You do not have permission to manage this community.', 'partyminder'));
            return;
        }
        
        // Update community settings
        $update_data = array();
        
        if (isset($_POST['description'])) {
            $update_data['description'] = wp_kses_post($_POST['description']);
        }
        
        if (isset($_POST['privacy']) && in_array($_POST['privacy'], array('public', 'private'))) {
            $update_data['privacy'] = sanitize_text_field($_POST['privacy']);
        }
        
        if (empty($update_data)) {
            wp_send_json_error(__('No data to update.', 'partyminder'));
            return;
        }
        
        $result = $community_manager->update_community($community_id, $update_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Community settings updated successfully!', 'partyminder')
            ));
        } else {
            wp_send_json_error(__('Failed to update community settings.', 'partyminder'));
        }
    }
    
    public function ajax_get_community_members() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $community_id = intval($_POST['community_id']);
        $current_user = wp_get_current_user();
        
        if (!$community_id) {
            wp_send_json_error(__('Invalid community.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        
        // Check if user is member of this community
        if (!$community_manager->is_member($community_id, $current_user->ID)) {
            wp_send_json_error(__('You must be a member to view the member list.', 'partyminder'));
            return;
        }
        
        $members = $community_manager->get_community_members($community_id);
        $user_role = $community_manager->get_member_role($community_id, $current_user->ID);
        
        wp_send_json_success(array(
            'members' => $members,
            'user_role' => $user_role,
            'can_manage' => $user_role === 'admin'
        ));
    }
    
    public function ajax_update_member_role() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $community_id = intval($_POST['community_id']);
        $member_user_id = intval($_POST['member_user_id']);
        $new_role = sanitize_text_field($_POST['new_role']);
        $current_user = wp_get_current_user();
        
        if (!$community_id || !$member_user_id || !$new_role) {
            wp_send_json_error(__('Missing required data.', 'partyminder'));
            return;
        }
        
        if (!in_array($new_role, array('admin', 'moderator', 'member'))) {
            wp_send_json_error(__('Invalid role.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        
        // Check if current user is admin
        $user_role = $community_manager->get_member_role($community_id, $current_user->ID);
        if ($user_role !== 'admin') {
            wp_send_json_error(__('Only administrators can change member roles.', 'partyminder'));
            return;
        }
        
        // Don't allow changing your own role if you're the only admin
        if ($member_user_id == $current_user->ID && $new_role !== 'admin') {
            $admin_count = $community_manager->get_admin_count($community_id);
            if ($admin_count <= 1) {
                wp_send_json_error(__('You cannot remove your admin role as you are the only administrator.', 'partyminder'));
                return;
            }
        }
        
        $result = $community_manager->update_member_role($community_id, $member_user_id, $new_role);
        
        if ($result) {
            $member = get_user_by('id', $member_user_id);
            wp_send_json_success(array(
                'message' => sprintf(__('%s role updated to %s', 'partyminder'), $member->display_name, $new_role)
            ));
        } else {
            wp_send_json_error(__('Failed to update member role.', 'partyminder'));
        }
    }
    
    public function ajax_remove_member() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $community_id = intval($_POST['community_id']);
        $member_user_id = intval($_POST['member_user_id']);
        $current_user = wp_get_current_user();
        
        if (!$community_id || !$member_user_id) {
            wp_send_json_error(__('Missing required data.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        
        // Check if current user is admin
        $user_role = $community_manager->get_member_role($community_id, $current_user->ID);
        if ($user_role !== 'admin') {
            wp_send_json_error(__('Only administrators can remove members.', 'partyminder'));
            return;
        }
        
        // Don't allow removing yourself if you're the only admin
        if ($member_user_id == $current_user->ID) {
            $admin_count = $community_manager->get_admin_count($community_id);
            if ($admin_count <= 1) {
                wp_send_json_error(__('You cannot remove yourself as you are the only administrator.', 'partyminder'));
                return;
            }
        }
        
        // Get member ID from the community members table
        global $wpdb;
        $members_table = $wpdb->prefix . 'partyminder_community_members';
        $member = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $members_table WHERE community_id = %d AND user_id = %d AND status = 'active'",
            $community_id,
            $member_user_id
        ));
        
        if (!$member) {
            wp_send_json_error(__('Member not found.', 'partyminder'));
            return;
        }
        
        $result = $community_manager->remove_member($community_id, $member->id);
        
        if ($result) {
            $member = get_user_by('id', $member_user_id);
            wp_send_json_success(array(
                'message' => sprintf(__('%s has been removed from the community', 'partyminder'), $member->display_name)
            ));
        } else {
            wp_send_json_error(__('Failed to remove member.', 'partyminder'));
        }
    }
    
    public function ajax_send_invitation() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $community_id = intval($_POST['community_id']);
        $email = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (!$community_id || !$email) {
            wp_send_json_error(__('Missing required data.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        $result = $community_manager->send_invitation($community_id, $email, $message);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        $message = $result['email_sent'] 
            ? __('Invitation sent successfully!', 'partyminder')
            : __('Invitation created but email delivery failed. Please contact the user directly.', 'partyminder');
            
        wp_send_json_success(array(
            'message' => $message,
            'invitation_id' => $result['invitation_id']
        ));
    }
    
    public function ajax_get_community_invitations() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $community_id = intval($_POST['community_id']);
        
        if (!$community_id) {
            wp_send_json_error(__('Missing community ID.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        
        // Check if user is admin
        $current_user = wp_get_current_user();
        $user_role = $community_manager->get_member_role($community_id, $current_user->ID);
        if ($user_role !== 'admin') {
            wp_send_json_error(__('Only administrators can view invitations.', 'partyminder'));
            return;
        }
        
        $invitations = $community_manager->get_community_invitations($community_id);
        
        if (is_wp_error($invitations)) {
            wp_send_json_error($invitations->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'invitations' => $invitations
        ));
    }
    
    public function ajax_cancel_invitation() {
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            wp_send_json_error(__('Communities feature is disabled.', 'partyminder'));
            return;
        }
        
        check_ajax_referer('partyminder_community_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $community_id = intval($_POST['community_id']);
        $invitation_id = intval($_POST['invitation_id']);
        
        if (!$community_id || !$invitation_id) {
            wp_send_json_error(__('Missing required data.', 'partyminder'));
            return;
        }
        
        $community_manager = new PartyMinder_Community_Manager();
        $result = $community_manager->cancel_invitation($community_id, $invitation_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Invitation cancelled successfully.', 'partyminder')
        ));
    }
    
    public function ajax_send_event_invitation() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        $email = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (!$event_id || !$email) {
            wp_send_json_error(__('Missing required data.', 'partyminder'));
            return;
        }
        
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        $event_manager = new PartyMinder_Event_Manager();
        $result = $event_manager->send_event_invitation($event_id, $email, $message);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        $message = $result['email_sent'] 
            ? __('Invitation sent successfully!', 'partyminder')
            : __('Invitation created but email delivery failed. Please contact the guest directly.', 'partyminder');
            
        wp_send_json_success(array(
            'message' => $message,
            'invitation_id' => $result['invitation_id']
        ));
    }
    
    public function ajax_get_event_invitations() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        
        if (!$event_id) {
            wp_send_json_error(__('Missing event ID.', 'partyminder'));
            return;
        }
        
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        $event_manager = new PartyMinder_Event_Manager();
        
        // Check if user is event host
        $event = $event_manager->get_event($event_id);
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($event->author_id != $current_user->ID && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Only the event host can view invitations.', 'partyminder'));
            return;
        }
        
        $invitations = $event_manager->get_event_invitations($event_id);
        
        if (is_wp_error($invitations)) {
            wp_send_json_error($invitations->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'invitations' => $invitations
        ));
    }
    
    public function ajax_cancel_event_invitation() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        $invitation_id = intval($_POST['invitation_id']);
        
        if (!$event_id || !$invitation_id) {
            wp_send_json_error(__('Missing required data.', 'partyminder'));
            return;
        }
        
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        $event_manager = new PartyMinder_Event_Manager();
        $result = $event_manager->cancel_event_invitation($event_id, $invitation_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Invitation cancelled successfully.', 'partyminder')
        ));
    }
    
    public function ajax_get_event_stats() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        
        if (!$event_id) {
            wp_send_json_error(__('Missing event ID.', 'partyminder'));
            return;
        }
        
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        $event_manager = new PartyMinder_Event_Manager();
        
        // Check if user is event host
        $event = $event_manager->get_event($event_id);
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($event->author_id != $current_user->ID && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Only the event host can view statistics.', 'partyminder'));
            return;
        }
        
        global $wpdb;
        $guests_table = $wpdb->prefix . 'partyminder_guests';
        $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
        
        // Get RSVP stats
        $total_rsvps = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $guests_table WHERE event_id = %d",
            $event_id
        ));
        
        $attending_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $guests_table WHERE event_id = %d AND status = 'attending'",
            $event_id
        ));
        
        $pending_invites = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $invitations_table WHERE event_id = %d AND status = 'pending' AND expires_at > NOW()",
            $event_id
        ));
        
        wp_send_json_success(array(
            'total_rsvps' => (int) $total_rsvps,
            'attending_count' => (int) $attending_count,
            'pending_invites' => (int) $pending_invites,
            'edit_url' => self::get_edit_event_url($event_id)
        ));
    }
    
    public function ajax_get_event_guests() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        
        if (!$event_id) {
            wp_send_json_error(__('Missing event ID.', 'partyminder'));
            return;
        }
        
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        $event_manager = new PartyMinder_Event_Manager();
        
        // Check if user is event host
        $event = $event_manager->get_event($event_id);
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($event->author_id != $current_user->ID && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Only the event host can view the guest list.', 'partyminder'));
            return;
        }
        
        global $wpdb;
        $guests_table = $wpdb->prefix . 'partyminder_guests';
        
        $guests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $guests_table WHERE event_id = %d ORDER BY rsvp_date DESC",
            $event_id
        ));
        
        wp_send_json_success(array(
            'guests' => $guests ?: array()
        ));
    }
    
    // Shortcodes
    public function dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts);
        
        // Check if we're on the dedicated page
        $on_dedicated_page = $this->is_on_dedicated_page('dashboard');
        
        // If on dedicated page, content injection handles everything
        if ($on_dedicated_page) {
            return '';
        }
        
        // Otherwise, provide simplified embedded version
        ob_start();
        echo '<div class="partyminder-shortcode-wrapper">';
        echo '<h3>' . __('PartyMinder Dashboard', 'partyminder') . '</h3>';
        echo '<p>' . __('Your central hub for managing events, conversations, and connections.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_dashboard_url()) . '" class="pm-button">' . __('Go to Dashboard', 'partyminder') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function event_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Create Your Event', 'partyminder')
        ), $atts);
        
        // Check if we're on the dedicated page
        $on_dedicated_page = $this->is_on_dedicated_page('create-event');
        
        // If on dedicated page, content injection handles everything
        if ($on_dedicated_page) {
            return '';
        }
        
        // Otherwise, provide simplified embedded version with link to full page
        ob_start();
        echo '<div class="partyminder-shortcode-wrapper">';
        echo '<h3>' . esc_html($atts['title']) . '</h3>';
        echo '<p>' . __('Create and manage your events with our full-featured event creation tool.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_create_event_url()) . '" class="pm-button">' . __('Create Event', 'partyminder') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function rsvp_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'event_id' => get_the_ID()
        ), $atts);
        
        ob_start();
        include PARTYMINDER_PLUGIN_DIR . 'templates/rsvp-form.php';
        return ob_get_clean();
    }
    
    public function events_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'show_past' => false
        ), $atts);
        
        // Check if we're on the dedicated page
        $on_dedicated_page = $this->is_on_dedicated_page('events');
        
        // If on dedicated page, content injection handles everything
        if ($on_dedicated_page) {
            return '';
        }
        
        // Otherwise, provide simplified embedded version
        ob_start();
        echo '<div class="partyminder-shortcode-wrapper">';
        echo '<h3>' . __('Upcoming Events', 'partyminder') . '</h3>';
        echo '<p>' . __('Check out all upcoming events and RSVP to join the fun!', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_events_page_url()) . '" class="pm-button">' . __('View All Events', 'partyminder') . '</a>';
        echo '<a href="' . esc_url(self::get_create_event_url()) . '" class="pm-button success">' . __('Create Event', 'partyminder') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function my_events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_past' => false
        ), $atts);
        
        // Check if we're on the dedicated page
        $on_dedicated_page = $this->is_on_dedicated_page('my-events');
        
        // If on dedicated page, content injection handles everything
        if ($on_dedicated_page) {
            return '';
        }
        
        // Otherwise, provide simplified embedded version
        ob_start();
        echo '<div class="partyminder-shortcode-wrapper">';
        echo '<h3>' . __('My Events Dashboard', 'partyminder') . '</h3>';
        echo '<p>' . __('Manage your created events and RSVPs in one convenient place.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_my_events_url()) . '" class="pm-button">' . __('View My Events', 'partyminder') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function conversations_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts);
        
        // Check if we're on the dedicated page
        $on_dedicated_page = $this->is_on_dedicated_page('conversations');
        
        // If on dedicated page, content injection handles everything
        if ($on_dedicated_page) {
            return '';
        }
        
        // Otherwise, provide simplified embedded version
        ob_start();
        echo '<div class="partyminder-shortcode-wrapper">';
        echo '<h3>' . __('Community Conversations', 'partyminder') . '</h3>';
        echo '<p>' . __('Connect with fellow hosts and guests, share tips, and plan amazing gatherings together.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_conversations_url()) . '" class="pm-button">' . __('Join Conversations', 'partyminder') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function communities_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts);
        
        // Check if we're on the dedicated page
        $on_dedicated_page = $this->is_on_dedicated_page('communities');
        
        // If on dedicated page, content injection handles everything
        if ($on_dedicated_page) {
            return '';
        }
        
        // Check if communities are enabled
        if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
            return '<div class="partyminder-shortcode-wrapper warning"><p>' . __('Communities feature is not enabled.', 'partyminder') . '</p></div>';
        }
        
        // Otherwise, provide simplified embedded version
        ob_start();
        echo '<div class="partyminder-shortcode-wrapper">';
        echo '<h3>' . __('Communities', 'partyminder') . '</h3>';
        echo '<p>' . __('Join communities of fellow hosts and guests to plan events together.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_communities_url()) . '" class="pm-button">' . __('Browse Communities', 'partyminder') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function event_edit_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'event_id' => intval($_GET['event_id'] ?? 0)
        ), $atts);
        
        // Check if we're on the dedicated page
        $on_dedicated_page = $this->is_on_dedicated_page('edit-event');
        
        // If on dedicated page, content injection handles everything
        if ($on_dedicated_page) {
            return '';
        }
        
        // Otherwise, provide simplified embedded version
        $event_id = $atts['event_id'];
        if ($event_id) {
            ob_start();
            echo '<div class="partyminder-shortcode-wrapper">';
            echo '<h3>' . __('Edit Event', 'partyminder') . '</h3>';
            echo '<p>' . __('Use our full-featured editor to update your event details.', 'partyminder') . '</p>';
            echo '<a href="' . esc_url(self::get_edit_event_url($event_id)) . '" class="pm-button">' . __('Edit Event', 'partyminder') . '</a>';
            echo '</div>';
            return ob_get_clean();
        }
        
        return '<div class="partyminder-shortcode-wrapper warning"><p>' . __('Event ID required for editing.', 'partyminder') . '</p></div>';
    }
    
    public function profile_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user' => get_current_user_id()
        ), $atts);
        
        // Check if we're on the dedicated page
        $on_dedicated_page = $this->is_on_dedicated_page('profile');
        
        // If on dedicated page, content injection handles everything
        if ($on_dedicated_page) {
            return '';
        }
        
        // Otherwise, provide simplified embedded version
        ob_start();
        echo '<div class="partyminder-shortcode-wrapper">';
        echo '<h3>' . __('My Profile', 'partyminder') . '</h3>';
        echo '<p>' . __('Manage your PartyMinder profile, preferences, and hosting reputation.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_profile_url()) . '" class="pm-button">' . __('View Profile', 'partyminder') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function login_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts);
        
        // Check if we're on the dedicated page
        $on_dedicated_page = $this->is_on_dedicated_page('login');
        
        // If on dedicated page, content injection handles everything
        if ($on_dedicated_page) {
            return '';
        }
        
        // Otherwise, provide simplified embedded version
        ob_start();
        echo '<div class="partyminder-shortcode-wrapper">';
        echo '<h3>' . __('PartyMinder Login', 'partyminder') . '</h3>';
        echo '<p>' . __('Sign in to access all features and manage your events.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_login_url()) . '" class="pm-button">' . __('Sign In', 'partyminder') . '</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function add_event_rewrite_rules() {
        // Add rewrite rule to catch individual events and route them to the events page
        add_rewrite_rule('^events/join/?$', 'index.php?pagename=events&event_action=join', 'top');
        add_rewrite_rule('^events/([^/]+)/?$', 'index.php?pagename=events&event_slug=$matches[1]', 'top');
        
        // Add conversation routing
        add_rewrite_rule('^conversations/([^/]+)/?$', 'index.php?pagename=conversations&conversation_topic=$matches[1]', 'top');
        add_rewrite_rule('^conversations/([^/]+)/([^/]+)/?$', 'index.php?pagename=conversations&conversation_topic=$matches[1]&conversation_slug=$matches[2]', 'top');
        
        // Add community routing (only if communities enabled)
        if (PartyMinder_Feature_Flags::is_communities_enabled()) {
            add_rewrite_rule('^communities/?$', 'index.php?pagename=communities', 'top');
            add_rewrite_rule('^communities/join/?$', 'index.php?pagename=communities&community_action=join', 'top');
            add_rewrite_rule('^communities/([^/]+)/?$', 'index.php?pagename=communities&community_slug=$matches[1]', 'top');
            add_rewrite_rule('^communities/([^/]+)/events/?$', 'index.php?pagename=communities&community_slug=$matches[1]&community_view=events', 'top');
            add_rewrite_rule('^communities/([^/]+)/members/?$', 'index.php?pagename=communities&community_slug=$matches[1]&community_view=members', 'top');
        }
        
        // Add query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'event_slug';
            $vars[] = 'event_action';
            $vars[] = 'conversation_topic';
            $vars[] = 'conversation_slug';
            $vars[] = 'community_slug';
            $vars[] = 'community_view';
            $vars[] = 'community_action';
            return $vars;
        });
    }
    
    public function handle_page_routing() {
        global $wp_query;
        
        // Get the current page
        $current_page = get_queried_object();
        
        if (!is_page() || !$current_page) {
            return;
        }
        
        // Check if this is one of our dedicated pages
        $page_keys = array('events', 'create-event', 'my-events', 'edit-event');
        $current_page_key = null;
        
        foreach ($page_keys as $key) {
            $page_id = get_option('partyminder_page_' . $key);
            if ($page_id && $current_page->ID == $page_id) {
                $current_page_key = $key;
                break;
            }
        }
        
        if (!$current_page_key) {
            return;
        }
        
        // Handle page-specific logic
        switch ($current_page_key) {
            case 'edit-event':
                // Ensure event_id is available for edit page
                if (!get_query_var('event_id') && isset($_GET['event_id'])) {
                    set_query_var('event_id', intval($_GET['event_id']));
                }
                break;
                
            case 'my-events':
                // Check if user should see this page
                if (!is_user_logged_in() && !isset($_GET['email'])) {
                    // Optionally redirect to login or show guest access form
                }
                break;
        }
        
        // Set page-specific variables for templates
        set_query_var('partyminder_page', $current_page_key);
    }
    
    public static function get_page_url($page_key, $args = array()) {
        $page_id = get_option('partyminder_page_' . $page_key);
        if (!$page_id) {
            return home_url('/' . $page_key . '/');
        }
        
        $url = get_permalink($page_id);
        
        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }
        
        return $url;
    }
    
    public static function get_dashboard_url() {
        return self::get_page_url('dashboard');
    }
    
    public static function get_events_page_url() {
        return self::get_page_url('events');
    }
    
    public static function get_create_event_url() {
        return self::get_page_url('create-event');
    }
    
    public static function get_my_events_url() {
        return self::get_page_url('my-events');
    }
    
    public static function get_edit_event_url($event_id) {
        return self::get_page_url('edit-event', array('event_id' => $event_id));
    }
    
    public static function get_conversations_url() {
        return self::get_page_url('conversations');
    }
    
    public static function get_login_url() {
        return self::get_page_url('login');
    }
    
    public static function get_logout_url($redirect = '') {
        if (empty($redirect)) {
            $redirect = home_url();
        }
        return wp_logout_url($redirect);
    }
    
    public static function get_communities_url() {
        return self::get_page_url('communities');
    }
    
    public static function get_profile_url($user_id = null) {
        if ($user_id && $user_id !== get_current_user_id()) {
            return self::get_page_url('profile', array('user' => $user_id));
        }
        return self::get_page_url('profile');
    }
    
    private function is_on_dedicated_page($page_key) {
        global $post;
        
        if (!is_page() || !$post) {
            return false;
        }
        
        // Check by page meta for the new system
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type === $page_key) {
            return true;
        }
        
        // Fallback: Check by stored page ID (for backward compatibility)
        $page_id = get_option('partyminder_page_' . $page_key);
        return $page_id && $post->ID == $page_id;
    }
    
    public function handle_form_submissions() {
        // Handle frontend event form submission
        if (isset($_POST['partyminder_create_event'])) {
            
            if (wp_verify_nonce($_POST['partyminder_event_nonce'], 'create_partyminder_event')) {
                // Nonce verified, proceed with form processing
            } else {
                return;
            }
            
            $form_errors = array();
            
            // Validate required fields
            if (empty($_POST['event_title'])) {
                $form_errors[] = __('Event title is required.', 'partyminder');
            }
            if (empty($_POST['event_date'])) {
                $form_errors[] = __('Event date is required.', 'partyminder');
            }
            if (empty($_POST['host_email'])) {
                $form_errors[] = __('Host email is required.', 'partyminder');
            }
            
            // If no errors, create the event
            if (empty($form_errors)) {
                $event_data = array(
                    'title' => sanitize_text_field(wp_unslash($_POST['event_title'])),
                    'description' => wp_kses_post(wp_unslash($_POST['event_description'])),
                    'event_date' => sanitize_text_field($_POST['event_date']),
                    'venue' => sanitize_text_field($_POST['venue_info']),
                    'guest_limit' => intval($_POST['guest_limit']),
                    'host_email' => sanitize_email($_POST['host_email']),
                    'host_notes' => wp_kses_post(wp_unslash($_POST['host_notes']))
                );
                
                $event_id = $this->create_event_via_form($event_data);
                
                if (!is_wp_error($event_id)) {
                    // Store success data in session or transient for the template
                    $created_event = $this->event_manager->get_event($event_id);
                    set_transient('partyminder_event_created_' . get_current_user_id(), array(
                        'event_id' => $event_id,
                        'event_url' => home_url('/events/' . $created_event->slug)
                    ), 60);
                    
                    // Redirect to prevent resubmission
                    wp_redirect(add_query_arg('partyminder_created', 1, self::get_create_event_url()));
                    exit;
                } else {
                    // Store error for template
                    set_transient('partyminder_form_errors_' . get_current_user_id(), array($event_id->get_error_message()), 60);
                }
            } else {
                // Store validation errors for template
                set_transient('partyminder_form_errors_' . get_current_user_id(), $form_errors, 60);
            }
        }
    }
    
    public function create_event_via_form($event_data) {
        // Ensure dependencies are loaded
        if (!$this->event_manager) {
            $this->load_dependencies();
            $this->event_manager = new PartyMinder_Event_Manager();
        }
        
        if (!$this->event_manager) {
            return new WP_Error('manager_not_loaded', __('Event manager not available', 'partyminder'));
        }
        
        return $this->event_manager->create_event($event_data);
    }
    
    public function handle_custom_pages() {
        global $wp_query, $post;
        
        // Check if this is an event page
        $event_slug = get_query_var('partyminder_event_slug');
        
        if ($event_slug) {
            // Load event from custom table
            if (!$this->event_manager) {
                $this->load_dependencies();
                $this->event_manager = new PartyMinder_Event_Manager();
            }
            
            $event = $this->event_manager->get_event_by_slug($event_slug);
            
            if (!$event) {
                // Event not found - show 404
                $wp_query->set_404();
                status_header(404);
                return;
            }
            
            // Create a complete post object for WordPress template compatibility
            $fake_post_data = array(
                'ID' => 999999 + $event->id, // Unique ID that won't conflict
                'post_author' => $event->author_id,
                'post_date' => $event->created_at,
                'post_date_gmt' => get_gmt_from_date($event->created_at),
                'post_content' => $event->description,
                'post_title' => $event->title,
                'post_excerpt' => $event->excerpt,
                'post_status' => 'publish',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_password' => '',
                'post_name' => $event->slug,
                'post_modified' => $event->updated_at,
                'post_modified_gmt' => get_gmt_from_date($event->updated_at),
                'post_parent' => 0,
                'guid' => home_url('/events/' . $event->slug),
                'menu_order' => 0,
                'post_type' => 'page',  // Use 'page' to avoid post type issues
                'post_mime_type' => '',
                'comment_count' => 0,
                'filter' => 'raw',
                'post_content_filtered' => '',
                'to_ping' => '',
                'pinged' => ''
            );
            
            // Create proper WP_Post object
            $fake_post = new WP_Post((object) $fake_post_data);
            
            // Set up WordPress query for single event page
            $wp_query->is_page = false;
            $wp_query->is_singular = true;
            $wp_query->is_single = true;
            $wp_query->is_home = false;
            $wp_query->is_archive = false;
            $wp_query->is_category = false;
            $wp_query->is_404 = false;
            
            // Set the fake post as current
            $wp_query->post = $fake_post;
            $wp_query->posts = array($fake_post);
            $wp_query->queried_object = $fake_post;
            $wp_query->queried_object_id = $fake_post->ID;
            $wp_query->found_posts = 1;
            $wp_query->post_count = 1;
            $wp_query->current_post = -1;
            $wp_query->in_the_loop = false;
            
            // Set global post
            $post = $fake_post;
            $GLOBALS['post'] = $fake_post;
            
            // Store event data for template use
            $GLOBALS['partyminder_current_event'] = $event;
            
            // Add template filters for theme integration
            add_filter('template_include', array($this, 'event_template_include'));
            add_filter('single_template', array($this, 'event_single_template'));
            
            // Prevent WordPress from trying to load post data from database
            add_filter('get_post_metadata', array($this, 'prevent_fake_post_meta'), 10, 4);
            
            // Additional safeguards for WordPress core functions
            add_filter('get_post', array($this, 'ensure_fake_post_exists'), 10, 2);
            add_filter('posts_results', array($this, 'ensure_posts_array'), 10, 2);
            
            // Override WordPress globals early to prevent null post issues
            add_action('wp', array($this, 'ensure_global_post_setup'), 1);
            
            // Add to head for SEO
            add_action('wp_head', array($this, 'add_event_seo_tags'));
            return;
        }
        
        if (!is_page() || !$post) {
            return;
        }
        
        // Check if this is one of our custom pages
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        
        if (!$page_type) {
            return;
        }
        
        // Set up content injection based on page type
        switch ($page_type) {
            case 'dashboard':
                add_filter('the_content', array($this, 'inject_dashboard_content'));
                add_filter('body_class', array($this, 'add_dashboard_body_class'));
                break;
                
            case 'events':
                add_filter('the_content', array($this, 'inject_events_content'));
                add_filter('body_class', array($this, 'add_events_body_class'));
                break;
                
            case 'create-event':
                add_filter('the_content', array($this, 'inject_create_event_content'));
                add_filter('body_class', array($this, 'add_create_event_body_class'));
                break;
                
            case 'my-events':
                add_filter('the_content', array($this, 'inject_my_events_content'));
                add_filter('body_class', array($this, 'add_my_events_body_class'));
                break;
                
            case 'edit-event':
                // Handle event ID parameter
                if (!get_query_var('event_id') && isset($_GET['event_id'])) {
                    set_query_var('event_id', intval($_GET['event_id']));
                }
                add_filter('the_content', array($this, 'inject_edit_event_content'));
                add_filter('body_class', array($this, 'add_edit_event_body_class'));
                break;
                
            case 'profile':
                // Handle user parameter for viewing other profiles
                if (!get_query_var('user') && isset($_GET['user'])) {
                    set_query_var('user', intval($_GET['user']));
                }
                add_filter('the_content', array($this, 'inject_profile_content'));
                add_filter('body_class', array($this, 'add_profile_body_class'));
                break;
                
            case 'login':
                add_filter('the_content', array($this, 'inject_login_content'));
                add_filter('body_class', array($this, 'add_login_body_class'));
                break;
                
            case 'conversations':
                // Handle conversation routing
                $topic_slug = get_query_var('conversation_topic');
                $conversation_slug = get_query_var('conversation_slug');
                
                if ($conversation_slug) {
                    // Individual conversation view
                    add_filter('the_content', array($this, 'inject_single_conversation_content'));
                    add_filter('body_class', array($this, 'add_single_conversation_body_class'));
                } elseif ($topic_slug) {
                    // Topic view
                    add_filter('the_content', array($this, 'inject_topic_conversations_content'));
                    add_filter('body_class', array($this, 'add_topic_conversations_body_class'));
                } else {
                    // Main conversations page
                    add_filter('the_content', array($this, 'inject_conversations_content'));
                    add_filter('body_class', array($this, 'add_conversations_body_class'));
                }
                break;
                
            case 'communities':
                // Handle community routing (only if feature enabled)
                if (PartyMinder_Feature_Flags::is_communities_enabled()) {
                    $community_slug = get_query_var('community_slug');
                    $community_view = get_query_var('community_view');
                    
                    if ($community_slug && $community_view === 'members') {
                        // Community members view
                        add_filter('the_content', array($this, 'inject_community_members_content'));
                        add_filter('body_class', array($this, 'add_community_members_body_class'));
                    } elseif ($community_slug && $community_view === 'events') {
                        // Community events view
                        add_filter('the_content', array($this, 'inject_community_events_content'));
                        add_filter('body_class', array($this, 'add_community_events_body_class'));
                    } elseif ($community_slug) {
                        // Individual community view
                        add_filter('the_content', array($this, 'inject_single_community_content'));
                        add_filter('body_class', array($this, 'add_single_community_body_class'));
                    } else {
                        // Main communities page
                        add_filter('the_content', array($this, 'inject_communities_content'));
                        add_filter('body_class', array($this, 'add_communities_body_class'));
                    }
                } else {
                    // Feature disabled - show disabled message
                    add_filter('the_content', array($this, 'inject_communities_disabled_content'));
                }
                break;
        }
    }
    
    
    public function add_event_seo_tags() {
        if (!isset($GLOBALS['partyminder_current_event'])) {
            return;
        }
        
        $event = $GLOBALS['partyminder_current_event'];
        
        echo '<title>' . esc_html($event->meta_title ?: $event->title) . '</title>' . "\n";
        echo '<meta name="description" content="' . esc_attr($event->meta_description ?: $event->excerpt) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($event->title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($event->excerpt) . '">' . "\n";
        echo '<meta property="og:type" content="event">' . "\n";
        echo '<meta property="event:start_time" content="' . esc_attr($event->event_date) . '">' . "\n";
        
        if ($event->venue_info) {
            echo '<meta property="event:location" content="' . esc_attr($event->venue_info) . '">' . "\n";
        }
    }
    
    // Removed all post metadata suppression - no longer needed with pages
    
    
    public function add_structured_data() {
        global $post;
        
        // Add structured data for PartyMinder custom event pages
        if (isset($GLOBALS['partyminder_current_event'])) {
            $event = $GLOBALS['partyminder_current_event'];
            
            if ($event) {
                $structured_data = array(
                    '@context' => 'https://schema.org',
                    '@type' => 'Event',
                    'name' => $event->title,
                    'description' => $event->description ?: $event->excerpt,
                    'startDate' => date('c', strtotime($event->event_date)),
                    'url' => home_url('/events/' . $event->slug),
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                    'organizer' => array(
                        '@type' => 'Person',
                        'email' => $event->host_email
                    )
                );
                
                if ($event->venue_info) {
                    $structured_data['location'] = array(
                        '@type' => 'Place',
                        'name' => $event->venue_info
                    );
                }
                
                if ($event->guest_stats) {
                    $structured_data['maximumAttendeeCapacity'] = $event->guest_limit ?: 100;
                    $structured_data['attendeeCount'] = $event->guest_stats->confirmed;
                }
                
                echo '<script type="application/ld+json">' . wp_json_encode($structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
            }
        }
        
        // Add structured data for events pages
        if (is_page()) {
            $page_keys = array('events', 'create-event', 'my-events');
            
            foreach ($page_keys as $key) {
                $page_id = get_option('partyminder_page_' . $key);
                if ($page_id && $post->ID == $page_id && $key === 'events') {
                    // Add breadcrumb and website structured data for events page
                    $structured_data = array(
                        '@context' => 'https://schema.org',
                        '@type' => 'CollectionPage',
                        'name' => get_the_title(),
                        'description' => 'Discover and RSVP to exciting events in your area.',
                        'url' => get_permalink(),
                        'breadcrumb' => array(
                            '@type' => 'BreadcrumbList',
                            'itemListElement' => array(
                                array(
                                    '@type' => 'ListItem',
                                    'position' => 1,
                                    'name' => 'Home',
                                    'item' => home_url()
                                ),
                                array(
                                    '@type' => 'ListItem',
                                    'position' => 2,
                                    'name' => 'Events',
                                    'item' => get_permalink()
                                )
                            )
                        )
                    );
                    
                    echo '<script type="application/ld+json">' . wp_json_encode($structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
                    break;
                }
            }
        }
    }
    
    public function modify_page_titles($title_parts) {
        global $post;
        
        // Handle custom event page titles
        if (isset($GLOBALS['partyminder_current_event'])) {
            $event = $GLOBALS['partyminder_current_event'];
            $title_parts['title'] = $event->meta_title ?: $event->title;
            return $title_parts;
        }
        
        if (!is_page() || !$post) {
            return $title_parts;
        }
        
        // Modify titles for our dedicated pages
        $page_keys = array('events', 'create-event', 'my-events', 'edit-event');
        
        foreach ($page_keys as $key) {
            $page_id = get_option('partyminder_page_' . $key);
            if ($page_id && $post->ID == $page_id) {
                switch ($key) {
                    case 'events':
                        $title_parts['title'] = __('Upcoming Events - Find Amazing Parties Near You', 'partyminder');
                        break;
                    case 'create-event':
                        $title_parts['title'] = __('Create Your Event - Host an Amazing Party', 'partyminder');
                        break;
                    case 'my-events':
                        if (is_user_logged_in()) {
                            $user = wp_get_current_user();
                            $title_parts['title'] = sprintf(__('%s\'s Events Dashboard', 'partyminder'), $user->display_name);
                        } else {
                            $title_parts['title'] = __('My Events Dashboard', 'partyminder');
                        }
                        break;
                    case 'edit-event':
                        if (isset($_GET['event_id'])) {
                            require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
                            $event_manager = new PartyMinder_Event_Manager();
                            $event = $event_manager->get_event(intval($_GET['event_id']));
                            if ($event) {
                                $title_parts['title'] = sprintf(__('Edit %s - Update Event Details', 'partyminder'), $event->title);
                            } else {
                                $title_parts['title'] = __('Edit Event - Update Event Details', 'partyminder');
                            }
                        } else {
                            $title_parts['title'] = __('Edit Event - Update Event Details', 'partyminder');
                        }
                        break;
                }
                break;
            }
        }
        
        return $title_parts;
    }
    
    public function event_template_include($template) {
        if (isset($GLOBALS['partyminder_current_event'])) {
            // Try to find a theme template first
            $theme_templates = array(
                'single-partyminder_event.php',
                'single-event.php', 
                'partyminder-event.php',
                'event.php'
            );
            
            $theme_template = locate_template($theme_templates);
            if ($theme_template) {
                return $theme_template;
            }
            
            // Fall back to our plugin template
            $plugin_template = PARTYMINDER_PLUGIN_DIR . 'templates/single-event.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    public function event_single_template($template) {
        return $this->event_template_include($template);
    }
    
    public function prevent_fake_post_meta($value, $object_id, $meta_key, $single) {
        // Prevent meta queries for our fake post IDs
        if ($object_id >= 999999 && isset($GLOBALS['partyminder_current_event'])) {
            return array(); // Return empty array to prevent database queries
        }
        return $value;
    }
    
    public function ensure_fake_post_exists($post, $post_id) {
        // If WordPress is looking for our fake post, return the global post
        if ($post_id >= 999999 && isset($GLOBALS['post']) && $GLOBALS['post']->ID == $post_id) {
            return $GLOBALS['post'];
        }
        return $post;
    }
    
    public function ensure_posts_array($posts, $query) {
        // Ensure we have posts in the array for our event pages
        if (isset($GLOBALS['partyminder_current_event']) && empty($posts) && isset($GLOBALS['post'])) {
            return array($GLOBALS['post']);
        }
        return $posts;
    }
    
    public function ensure_global_post_setup() {
        // Make sure our fake post is properly set up everywhere WordPress might look
        if (isset($GLOBALS['partyminder_current_event']) && isset($GLOBALS['post'])) {
            global $wp_query;
            
            // Ensure the post is converted to a proper WP_Post object
            if (!($GLOBALS['post'] instanceof WP_Post)) {
                $GLOBALS['post'] = new WP_Post($GLOBALS['post']);
            }
            
            // Ensure query objects are properly set
            if ($wp_query->post && !($wp_query->post instanceof WP_Post)) {
                $wp_query->post = new WP_Post($wp_query->post);
            }
            
            if (isset($wp_query->posts[0]) && !($wp_query->posts[0] instanceof WP_Post)) {
                $wp_query->posts[0] = new WP_Post($wp_query->posts[0]);
            }
            
            if ($wp_query->queried_object && !($wp_query->queried_object instanceof WP_Post)) {
                $wp_query->queried_object = new WP_Post($wp_query->queried_object);
            }
        }
    }
    
    public function detect_individual_event_page() {
        // Check if we're on the events page with an event slug
        if (is_page() && get_post_field('post_name') === 'events') {
            $event_slug = get_query_var('event_slug');
            
            if (!empty($event_slug)) {
                // This is an individual event URL
                $event = $this->get_event_by_slug($event_slug);
                
                if ($event) {
                    // Set up global event data for content injection
                    $GLOBALS['partyminder_current_event'] = $event;
                    $GLOBALS['partyminder_is_single_event'] = true;
                    
                    // Modify the page title and content
                    add_filter('the_title', array($this, 'inject_event_title'), 10, 2);
                    add_filter('the_content', array($this, 'inject_event_content'), 10);
                    add_filter('wp_title', array($this, 'inject_event_wp_title'), 10, 3);
                    add_filter('document_title_parts', array($this, 'inject_event_document_title'));
                } else {
                    // Event not found - show 404
                    global $wp_query;
                    $wp_query->set_404();
                    status_header(404);
                }
            }
        }
    }
    
    public function inject_event_title($title, $id = null) {
        if (isset($GLOBALS['partyminder_is_single_event']) && 
            isset($GLOBALS['partyminder_current_event']) && 
            ($id === get_the_ID() || is_null($id))) {
            return $GLOBALS['partyminder_current_event']->title;
        }
        return $title;
    }
    
    public function inject_event_content($content) {
        if (isset($GLOBALS['partyminder_is_single_event']) && in_the_loop() && is_main_query()) {
            ob_start();
            include PARTYMINDER_PLUGIN_DIR . 'templates/single-event-content.php';
            return ob_get_clean();
        }
        return $content;
    }
    
    public function inject_event_wp_title($title, $sep, $seplocation) {
        if (isset($GLOBALS['partyminder_is_single_event']) && isset($GLOBALS['partyminder_current_event'])) {
            return $GLOBALS['partyminder_current_event']->title . ' ' . $sep . ' ' . get_bloginfo('name');
        }
        return $title;
    }
    
    public function inject_event_document_title($title_parts) {
        if (isset($GLOBALS['partyminder_is_single_event']) && isset($GLOBALS['partyminder_current_event'])) {
            $title_parts['title'] = $GLOBALS['partyminder_current_event']->title;
        }
        return $title_parts;
    }
    
    private function get_event_by_slug($slug) {
        global $wpdb;
        $events_table = $wpdb->prefix . 'partyminder_events';
        
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $events_table WHERE slug = %s AND event_status = 'active'",
            $slug
        ));
        
        if ($event) {
            // Add guest stats
            require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
            $event_manager = new PartyMinder_Event_Manager();
            $event->guest_stats = $event_manager->get_guest_stats($event->id);
        }
        
        return $event;
    }
    
    /**
     * Inject events page content
     */
    public function inject_events_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'events') {
            return $content;
        }
        
        // Check if this is an event invitation acceptance page
        $event_action = get_query_var('event_action');
        if ($event_action === 'join') {
            ob_start();
            echo '<div class="partyminder-content partyminder-events-join-page">';
            include PARTYMINDER_PLUGIN_DIR . 'templates/event-invitation-accept.php';
            echo '</div>';
            return ob_get_clean();
        }
        
        ob_start();
        
        // Use theme-friendly wrapper
        echo '<div class="partyminder-content partyminder-events-page">';
        
        // Include the events template without get_header/get_footer
        $atts = array(
            'limit' => 10,
            'show_past' => false
        );
        include PARTYMINDER_PLUGIN_DIR . 'templates/events-list-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Inject create event content
     */
    public function inject_create_event_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'create-event') {
            return $content;
        }
        
        ob_start();
        
        echo '<div class="partyminder-content partyminder-create-event-page">';
        
        // Include create event template
        include PARTYMINDER_PLUGIN_DIR . 'templates/create-event-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Inject dashboard content
     */
    public function inject_dashboard_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'dashboard') {
            return $content;
        }
        
        ob_start();
        
        echo '<div class="partyminder-content partyminder-dashboard-page">';
        
        // Include dashboard template
        $atts = array();
        include PARTYMINDER_PLUGIN_DIR . 'templates/dashboard-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Inject my events content
     */
    public function inject_my_events_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'my-events') {
            return $content;
        }
        
        ob_start();
        
        echo '<div class="partyminder-content partyminder-my-events-page">';
        
        // Include my events template
        $atts = array('show_past' => false);
        include PARTYMINDER_PLUGIN_DIR . 'templates/my-events-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Inject edit event content
     */
    public function inject_edit_event_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'edit-event') {
            return $content;
        }
        
        ob_start();
        
        echo '<div class="partyminder-content partyminder-edit-event-page">';
        
        // Include edit event template
        include PARTYMINDER_PLUGIN_DIR . 'templates/edit-event-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Inject login content
     */
    public function inject_login_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'login') {
            return $content;
        }
        
        ob_start();
        
        echo '<div class="partyminder-content partyminder-login-page">';
        
        // Include login template
        $atts = array();
        include PARTYMINDER_PLUGIN_DIR . 'templates/login-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Inject profile content
     */
    public function inject_profile_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'profile') {
            return $content;
        }
        
        ob_start();
        
        echo '<div class="partyminder-content partyminder-profile-page">';
        
        // Include profile template
        include PARTYMINDER_PLUGIN_DIR . 'templates/profile-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Inject conversations content
     */
    public function inject_conversations_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'conversations') {
            return $content;
        }
        
        ob_start();
        
        echo '<div class="partyminder-content partyminder-conversations-page">';
        
        // Include conversations template
        include PARTYMINDER_PLUGIN_DIR . 'templates/conversations-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Inject topic conversations content
     */
    public function inject_topic_conversations_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'conversations') {
            return $content;
        }
        
        ob_start();
        
        echo '<div class="partyminder-content partyminder-topic-conversations-page">';
        
        // Include topic conversations template
        include PARTYMINDER_PLUGIN_DIR . 'templates/topic-conversations-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Inject single conversation content
     */
    public function inject_single_conversation_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'conversations') {
            return $content;
        }
        
        ob_start();
        
        echo '<div class="partyminder-content partyminder-single-conversation-page">';
        
        // Include single conversation template
        include PARTYMINDER_PLUGIN_DIR . 'templates/single-conversation-content.php';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Add body classes for PartyMinder pages
     */
    public function add_body_classes($classes) {
        global $post;
        
        if (!is_page() || !$post) {
            return $classes;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        
        if ($page_type) {
            $classes[] = 'partyminder-page';
            $classes[] = 'partyminder-' . $page_type . '-page';
            
            // Add responsive class
            $classes[] = 'partyminder-responsive';
            
            // Add theme compatibility class
            $theme = get_template();
            $classes[] = 'partyminder-theme-' . sanitize_html_class($theme);
        }
        
        return $classes;
    }
    
    /**
     * Add specific body classes for events page
     */
    public function add_dashboard_body_class($classes) {
        $classes[] = 'partyminder-dashboard';
        return $classes;
    }
    
    public function add_events_body_class($classes) {
        $classes[] = 'partyminder-events-listing';
        return $classes;
    }
    
    /**
     * Add specific body classes for create event page
     */
    public function add_create_event_body_class($classes) {
        $classes[] = 'partyminder-event-creation';
        return $classes;
    }
    
    /**
     * Add specific body classes for my events page
     */
    public function add_my_events_body_class($classes) {
        $classes[] = 'partyminder-user-dashboard';
        return $classes;
    }
    
    /**
     * Add specific body classes for edit event page
     */
    public function add_edit_event_body_class($classes) {
        $classes[] = 'partyminder-event-editing';
        return $classes;
    }
    
    /**
     * Add specific body classes for login page
     */
    public function add_login_body_class($classes) {
        $classes[] = 'partyminder-login';
        return $classes;
    }
    
    /**
     * Add specific body classes for profile page
     */
    public function add_profile_body_class($classes) {
        $classes[] = 'partyminder-profile';
        $user_id = get_query_var('user', get_current_user_id());
        if ($user_id && $user_id !== get_current_user_id()) {
            $classes[] = 'partyminder-profile-view';
        } else {
            $classes[] = 'partyminder-profile-edit';
        }
        return $classes;
    }
    
    /**
     * Add specific body classes for conversations page
     */
    public function add_conversations_body_class($classes) {
        $classes[] = 'partyminder-conversations';
        return $classes;
    }
    
    /**
     * Add specific body classes for topic conversations page
     */
    public function add_topic_conversations_body_class($classes) {
        $classes[] = 'partyminder-conversations';
        $classes[] = 'partyminder-topic-conversations';
        return $classes;
    }
    
    /**
     * Add specific body classes for single conversation page
     */
    public function add_single_conversation_body_class($classes) {
        $classes[] = 'partyminder-conversations';
        $classes[] = 'partyminder-single-conversation';
        return $classes;
    }
    
    /**
     * Inject communities content (main page)
     */
    public function inject_communities_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'communities') {
            return $content;
        }
        
        // Check if this is an invitation acceptance page
        $community_action = get_query_var('community_action');
        if ($community_action === 'join') {
            ob_start();
            echo '<div class="partyminder-content partyminder-communities-join-page">';
            include PARTYMINDER_PLUGIN_DIR . 'templates/community-invitation-accept.php';
            echo '</div>';
            return ob_get_clean();
        }
        
        ob_start();
        echo '<div class="partyminder-content partyminder-communities-page">';
        include PARTYMINDER_PLUGIN_DIR . 'templates/communities-content.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Inject communities disabled content
     */
    public function inject_communities_disabled_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        ob_start();
        echo '<div class="partyminder-content partyminder-communities-disabled">';
        echo '<h2>' . __('Communities Feature Not Available', 'partyminder') . '</h2>';
        echo '<p>' . __('The communities feature is currently disabled. Please check back later.', 'partyminder') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Inject single community content
     */
    public function inject_single_community_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'communities') {
            return $content;
        }
        
        ob_start();
        echo '<div class="partyminder-content partyminder-single-community-page">';
        include PARTYMINDER_PLUGIN_DIR . 'templates/single-community-content.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Inject community members content
     */
    public function inject_community_members_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'communities') {
            return $content;
        }
        
        ob_start();
        echo '<div class="partyminder-content partyminder-community-members-page">';
        include PARTYMINDER_PLUGIN_DIR . 'templates/community-members-content.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Inject community events content
     */
    public function inject_community_events_content($content) {
        global $post;
        
        if (!is_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        if ($page_type !== 'communities') {
            return $content;
        }
        
        ob_start();
        echo '<div class="partyminder-content partyminder-community-events-page">';
        include PARTYMINDER_PLUGIN_DIR . 'templates/community-events-content.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Add body classes for communities pages
     */
    public function add_communities_body_class($classes) {
        $classes[] = 'partyminder-communities-listing';
        return $classes;
    }
    
    public function add_single_community_body_class($classes) {
        $classes[] = 'partyminder-communities';
        $classes[] = 'partyminder-single-community';
        return $classes;
    }
    
    public function add_community_members_body_class($classes) {
        $classes[] = 'partyminder-communities';
        $classes[] = 'partyminder-community-members';
        return $classes;
    }
    
    public function add_community_events_body_class($classes) {
        $classes[] = 'partyminder-communities';
        $classes[] = 'partyminder-community-events';
        return $classes;
    }
    
    /**
     * Enqueue page-specific assets
     */
    public function enqueue_page_specific_assets() {
        global $post;
        
        if (!is_page() || !$post) {
            return;
        }
        
        $page_type = get_post_meta($post->ID, '_partyminder_page_type', true);
        
        if ($page_type) {
            // Add theme compatibility CSS
            wp_enqueue_style(
                'partyminder-theme-integration',
                PARTYMINDER_PLUGIN_URL . 'assets/css/theme-integration.css',
                array(),
                PARTYMINDER_VERSION
            );
            
            // Add page-specific CSS
            if (file_exists(PARTYMINDER_PLUGIN_DIR . 'assets/css/page-' . $page_type . '.css')) {
                wp_enqueue_style(
                    'partyminder-page-' . $page_type,
                    PARTYMINDER_PLUGIN_URL . 'assets/css/page-' . $page_type . '.css',
                    array('partyminder-theme-integration'),
                    PARTYMINDER_VERSION
                );
            }
            
            // Add page-specific JavaScript
            if ($page_type === 'conversations') {
                wp_enqueue_script(
                    'partyminder-conversations',
                    PARTYMINDER_PLUGIN_URL . 'assets/js/conversations.js',
                    array('jquery', 'partyminder-public'),
                    PARTYMINDER_VERSION,
                    true
                );
            }
        }
    }
    
    public function ajax_delete_event() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        
        if (!$event_id) {
            wp_send_json_error(__('Missing event ID.', 'partyminder'));
            return;
        }
        
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        $event_manager = new PartyMinder_Event_Manager();
        
        // Delete the event (includes permission checking)
        $result = $event_manager->delete_event($event_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Event deleted successfully.', 'partyminder'),
            'redirect_url' => home_url('/my-events')
        ));
    }
    
    public function ajax_admin_delete_event() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!current_user_can('delete_others_posts')) {
            wp_send_json_error(__('You do not have permission to delete events.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        
        if (!$event_id) {
            wp_send_json_error(__('Missing event ID.', 'partyminder'));
            return;
        }
        
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        $event_manager = new PartyMinder_Event_Manager();
        
        // Delete the event (will bypass permission checking since user is admin)
        $result = $event_manager->delete_event($event_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Event deleted successfully.', 'partyminder')
        ));
    }
    
    /**
     * Redirect WordPress login to custom login page
     */
    public function redirect_to_custom_login() {
        // Don't redirect if we're already on our custom login page
        if (strpos($_SERVER['REQUEST_URI'], '/login') !== false) {
            return;
        }
        
        // Don't redirect admin login, AJAX requests, or direct wp-admin access
        if (is_admin() || defined('DOING_AJAX')) {
            return;
        }
        
        // Don't redirect if accessing wp-login.php directly for admin access
        if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false && 
            isset($_GET['redirect_to']) && 
            strpos($_GET['redirect_to'], 'wp-admin') !== false) {
            return;
        }
        
        // Don't redirect if it's a logout action
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            return;
        }
        
        // Redirect to our custom login page
        $custom_login_url = self::get_login_url();
        
        // Preserve redirect_to parameter
        if (isset($_GET['redirect_to'])) {
            $custom_login_url = add_query_arg('redirect_to', urlencode($_GET['redirect_to']), $custom_login_url);
        }
        
        wp_redirect($custom_login_url);
        exit;
    }
    
    /**
     * Override wp_login_url to use custom login page
     */
    public function smart_login_url($login_url, $redirect) {
        // Always use default WordPress login for admin-related requests
        if (is_admin() || 
            (is_string($redirect) && strpos($redirect, 'wp-admin') !== false) ||
            (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'wp-admin') !== false) ||
            (isset($_GET['redirect_to']) && strpos($_GET['redirect_to'], 'wp-admin') !== false)) {
            return $login_url;
        }
        
        // Use custom login page for frontend requests
        $custom_login_url = self::get_login_url();
        
        if ($redirect) {
            $custom_login_url = add_query_arg('redirect_to', urlencode($redirect), $custom_login_url);
        }
        
        return $custom_login_url;
    }
    
}