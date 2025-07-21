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
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-ai-assistant.php';
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-admin.php';
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Template system - theme integration
        add_action('template_redirect', array($this, 'handle_custom_pages'));
        add_action('template_redirect', array($this, 'handle_form_submissions'));
        add_filter('the_content', array($this, 'inject_event_content'));
        
        // No longer need post meta suppression - using pages now
        
        // Register shortcodes early
        add_action('wp_loaded', array($this, 'register_shortcodes'));
        
        // Temporary migration trigger
        add_action('init', array($this, 'handle_migration'));
        
        // Page routing and URL handling
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        
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
        add_action('wp_ajax_partyminder_generate_ai_plan', array($this, 'ajax_generate_ai_plan'));
    }
    
    public function register_shortcodes() {
        // Shortcodes
        add_shortcode('partyminder_event_form', array($this, 'event_form_shortcode'));
        add_shortcode('partyminder_event_edit_form', array($this, 'event_edit_form_shortcode'));
        add_shortcode('partyminder_rsvp_form', array($this, 'rsvp_form_shortcode'));
        add_shortcode('partyminder_events_list', array($this, 'events_list_shortcode'));
        add_shortcode('partyminder_my_events', array($this, 'my_events_shortcode'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('partyminder', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // No longer registering custom post types - using pages now
        
        // Initialize managers
        $this->event_manager = new PartyMinder_Event_Manager();
        $this->guest_manager = new PartyMinder_Guest_Manager();
        $this->ai_assistant = new PartyMinder_AI_Assistant();
        
        // Initialize admin
        if (is_admin()) {
            new PartyMinder_Admin();
        }
    }
    
    // Removed custom post type - now using regular pages
    
    public function enqueue_public_scripts() {
        wp_enqueue_style('partyminder-public', PARTYMINDER_PLUGIN_URL . 'assets/css/public.css', array(), PARTYMINDER_VERSION);
        wp_enqueue_script('partyminder-public', PARTYMINDER_PLUGIN_URL . 'assets/js/public.js', array('jquery'), PARTYMINDER_VERSION, true);
        
        wp_localize_script('partyminder-public', 'partyminder_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('partyminder_nonce'),
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
            $this->register_post_types();
            $this->event_manager = new PartyMinder_Event_Manager();
        }
        
        $event_id = $this->event_manager->create_event($event_data);
        
        if (!is_wp_error($event_id)) {
            wp_send_json_success(array(
                'event_id' => $event_id,
                'message' => __('Event created successfully!', 'partyminder'),
                'event_url' => get_permalink($event_id)
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
            (is_user_logged_in() && $current_user->ID == $event->post_author) ||
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
            'ID' => $event_id,
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
            wp_send_json_success(array(
                'event_id' => $event_id,
                'message' => __('Event updated successfully!', 'partyminder'),
                'event_url' => get_permalink($event_id)
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
    
    public function add_rewrite_rules() {
        // Add rewrite rules for clean URLs - now using pages instead of custom post type
        add_rewrite_rule('^events/([0-9]+)/?$', 'index.php?page_id=$matches[1]', 'top');
        add_rewrite_rule('^events/([^/]+)/?$', 'index.php?pagename=$matches[1]', 'top');
        add_rewrite_rule('^edit-event/([0-9]+)/?$', 'index.php?pagename=edit-event&event_id=$matches[1]', 'top');
        
        // Force flush rewrite rules if needed
        if (get_option('partyminder_flush_rules')) {
            flush_rewrite_rules();
            delete_option('partyminder_flush_rules');
        }
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'event_id';
        $vars[] = 'action';
        $vars[] = 'pm_success';
        $vars[] = 'pm_error';
        return $vars;
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
                    set_transient('partyminder_event_created_' . get_current_user_id(), array(
                        'event_id' => $event_id,
                        'event_url' => get_permalink($event_id)
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
            $this->register_post_types(); // Ensure post type is registered
            $this->event_manager = new PartyMinder_Event_Manager();
        }
        
        if (!$this->event_manager) {
            return new WP_Error('manager_not_loaded', __('Event manager not available', 'partyminder'));
        }
        
        return $this->event_manager->create_event($event_data);
    }
    
    public function inject_event_content($content) {
        global $post;
        
        // Only on single pages that are PartyMinder events
        if (!is_page() || !$post) {
            return $content;
        }
        
        // Check if this is a PartyMinder event page
        if (!get_post_meta($post->ID, '_partyminder_event', true)) {
            return $content;
        }
        
        // Only inject on the main query and avoid infinite loops
        if (!is_main_query() || is_admin()) {
            return $content;
        }
        
        // Load the single event content
        ob_start();
        include PARTYMINDER_PLUGIN_DIR . 'templates/single-event-content.php';
        $event_content = ob_get_clean();
        
        // Return the event content instead of the original post content
        return $event_content;
    }
    
    // Removed all post metadata suppression - no longer needed with pages
    
    public function handle_migration() {
        // Run migration if requested
        if (isset($_GET['partyminder_migrate']) && $_GET['partyminder_migrate'] == '1' && current_user_can('manage_options')) {
            require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
            $event_manager = new PartyMinder_Event_Manager();
            $migrated = $event_manager->migrate_events_to_pages();
            
            wp_die("Migration complete! Converted $migrated events from posts to pages. <a href='" . home_url() . "'>Return to site</a>");
        }
    }
    
    public function add_structured_data() {
        global $post;
        
        // Add structured data for PartyMinder event pages
        if (is_page() && $post && get_post_meta($post->ID, '_partyminder_event', true)) {
            require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
            $event_manager = new PartyMinder_Event_Manager();
            $event = $event_manager->get_event($post->ID);
            
            if ($event) {
                $structured_data = array(
                    '@context' => 'https://schema.org',
                    '@type' => 'Event',
                    'name' => $event->title,
                    'description' => $event->description ?: $event->excerpt,
                    'startDate' => date('c', strtotime($event->event_date)),
                    'url' => get_permalink($event->ID),
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
    
    /**
     * Handle custom pages with theme integration
     */
    public function handle_custom_pages() {
        global $post;
        
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
        }
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
        }
    }
    
}