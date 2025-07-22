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
    }
    
    public function register_shortcodes() {
        // Shortcodes
        add_shortcode('partyminder_event_form', array($this, 'event_form_shortcode'));
        add_shortcode('partyminder_event_edit_form', array($this, 'event_edit_form_shortcode'));
        add_shortcode('partyminder_rsvp_form', array($this, 'rsvp_form_shortcode'));
        add_shortcode('partyminder_events_list', array($this, 'events_list_shortcode'));
        add_shortcode('partyminder_my_events', array($this, 'my_events_shortcode'));
        add_shortcode('partyminder_conversations', array($this, 'conversations_shortcode'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('partyminder', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize managers
        $this->event_manager = new PartyMinder_Event_Manager();
        $this->guest_manager = new PartyMinder_Guest_Manager();
        $this->conversation_manager = new PartyMinder_Conversation_Manager();
        $this->ai_assistant = new PartyMinder_AI_Assistant();
        
        
        // Initialize admin
        if (is_admin()) {
            new PartyMinder_Admin();
        }
    }
    
    
    
    public function enqueue_public_scripts() {
        wp_enqueue_style('partyminder-public', PARTYMINDER_PLUGIN_URL . 'assets/css/public.css', array(), PARTYMINDER_VERSION);
        wp_enqueue_script('partyminder-public', PARTYMINDER_PLUGIN_URL . 'assets/js/public.js', array('jquery'), PARTYMINDER_VERSION, true);
        
        $current_user = wp_get_current_user();
        wp_localize_script('partyminder-public', 'partyminder_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('partyminder_nonce'),
            'current_user' => array(
                'id' => $current_user->ID,
                'name' => $current_user->display_name,
                'email' => $current_user->user_email
            ),
            'strings' => array(
                'loading' => __('Loading...', 'partyminder'),
                'error' => __('An error occurred. Please try again.', 'partyminder'),
                'success' => __('Success!', 'partyminder')
            )
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'partyminder') !== false) {
            wp_enqueue_style('partyminder-admin', PARTYMINDER_PLUGIN_URL . 'assets/css/admin.css', array(), PARTYMINDER_VERSION);
            wp_enqueue_script('partyminder-admin', PARTYMINDER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PARTYMINDER_VERSION, true);
            
            wp_localize_script('partyminder-admin', 'partyminder_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('partyminder_admin_nonce')
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
            'title' => sanitize_text_field($_POST['event_title']),
            'description' => wp_kses_post($_POST['event_description']),
            'event_date' => sanitize_text_field($_POST['event_date']),
            'venue' => sanitize_text_field($_POST['venue_info']),
            'guest_limit' => intval($_POST['guest_limit']),
            'host_email' => sanitize_email($_POST['host_email']),
            'host_notes' => wp_kses_post($_POST['host_notes'])
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
            'title' => sanitize_text_field($_POST['event_title']),
            'description' => wp_kses_post($_POST['event_description']),
            'event_date' => sanitize_text_field($_POST['event_date']),
            'venue' => sanitize_text_field($_POST['venue_info']),
            'guest_limit' => intval($_POST['guest_limit']),
            'host_email' => sanitize_email($_POST['host_email']),
            'host_notes' => wp_kses_post($_POST['host_notes'])
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
    
    // Shortcodes
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
        echo '<div class="partyminder-shortcode-wrapper" style="padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px; margin: 20px 0;">';
        echo '<h3>' . esc_html($atts['title']) . '</h3>';
        echo '<p>' . __('Create and manage your events with our full-featured event creation tool.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_create_event_url()) . '" class="pm-button" style="background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">' . __('Create Event', 'partyminder') . '</a>';
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
        echo '<div class="partyminder-shortcode-wrapper" style="padding: 20px; background: #f9f9f9; border-radius: 8px; margin: 20px 0;">';
        echo '<h3>' . __('Upcoming Events', 'partyminder') . '</h3>';
        echo '<p>' . __('Check out all upcoming events and RSVP to join the fun!', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_events_page_url()) . '" class="pm-button" style="background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; margin-right: 10px;">' . __('View All Events', 'partyminder') . '</a>';
        echo '<a href="' . esc_url(self::get_create_event_url()) . '" class="pm-button" style="background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">' . __('Create Event', 'partyminder') . '</a>';
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
        echo '<div class="partyminder-shortcode-wrapper" style="padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px; margin: 20px 0;">';
        echo '<h3>' . __('My Events Dashboard', 'partyminder') . '</h3>';
        echo '<p>' . __('Manage your created events and RSVPs in one convenient place.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_my_events_url()) . '" class="pm-button" style="background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">' . __('View My Events', 'partyminder') . '</a>';
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
        echo '<div class="partyminder-shortcode-wrapper" style="padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px; margin: 20px 0;">';
        echo '<h3>' . __('Community Conversations', 'partyminder') . '</h3>';
        echo '<p>' . __('Connect with fellow hosts and guests, share tips, and plan amazing gatherings together.', 'partyminder') . '</p>';
        echo '<a href="' . esc_url(self::get_conversations_url()) . '" class="pm-button" style="background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">' . __('Join Conversations', 'partyminder') . '</a>';
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
            echo '<div class="partyminder-shortcode-wrapper" style="padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px; margin: 20px 0;">';
            echo '<h3>' . __('Edit Event', 'partyminder') . '</h3>';
            echo '<p>' . __('Use our full-featured editor to update your event details.', 'partyminder') . '</p>';
            echo '<a href="' . esc_url(self::get_edit_event_url($event_id)) . '" class="pm-button" style="background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">' . __('Edit Event', 'partyminder') . '</a>';
            echo '</div>';
            return ob_get_clean();
        }
        
        return '<div class="partyminder-shortcode-wrapper" style="padding: 20px; text-align: center; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; margin: 20px 0;"><p>' . __('Event ID required for editing.', 'partyminder') . '</p></div>';
    }
    
    public function add_event_rewrite_rules() {
        // Add rewrite rule to catch individual events and route them to the events page
        add_rewrite_rule('^events/([^/]+)/?$', 'index.php?pagename=events&event_slug=$matches[1]', 'top');
        
        // Add conversation routing
        add_rewrite_rule('^conversations/([^/]+)/?$', 'index.php?pagename=conversations&conversation_topic=$matches[1]', 'top');
        add_rewrite_rule('^conversations/([^/]+)/([^/]+)/?$', 'index.php?pagename=conversations&conversation_topic=$matches[1]&conversation_slug=$matches[2]', 'top');
        
        // Add query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'event_slug';
            $vars[] = 'conversation_topic';
            $vars[] = 'conversation_slug';
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
                    'title' => sanitize_text_field($_POST['event_title']),
                    'description' => wp_kses_post($_POST['event_description']),
                    'event_date' => sanitize_text_field($_POST['event_date']),
                    'venue' => sanitize_text_field($_POST['venue_info']),
                    'guest_limit' => intval($_POST['guest_limit']),
                    'host_email' => sanitize_email($_POST['host_email']),
                    'host_notes' => wp_kses_post($_POST['host_notes'])
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
    
}