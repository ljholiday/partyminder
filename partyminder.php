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
        add_action('template_redirect', array($this, 'handle_form_submissions'));
        add_filter('single_template', array($this, 'load_event_template'));
        
        // Register shortcodes early
        add_action('wp_loaded', array($this, 'register_shortcodes'));
        
        // AJAX handlers
        add_action('wp_ajax_partyminder_create_event', array($this, 'ajax_create_event'));
        add_action('wp_ajax_nopriv_partyminder_create_event', array($this, 'ajax_create_event'));
        add_action('wp_ajax_partyminder_rsvp', array($this, 'ajax_rsvp'));
        add_action('wp_ajax_nopriv_partyminder_rsvp', array($this, 'ajax_rsvp'));
        add_action('wp_ajax_partyminder_generate_ai_plan', array($this, 'ajax_generate_ai_plan'));
    }
    
    public function register_shortcodes() {
        // Shortcodes
        add_shortcode('partyminder_event_form', array($this, 'event_form_shortcode'));
        add_shortcode('partyminder_rsvp_form', array($this, 'rsvp_form_shortcode'));
        add_shortcode('partyminder_events_list', array($this, 'events_list_shortcode'));
        add_shortcode('partyminder_my_events', array($this, 'my_events_shortcode'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('partyminder', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Create custom post types
        $this->register_post_types();
        
        // Initialize managers
        $this->event_manager = new PartyMinder_Event_Manager();
        $this->guest_manager = new PartyMinder_Guest_Manager();
        $this->ai_assistant = new PartyMinder_AI_Assistant();
        
        // Initialize admin
        if (is_admin()) {
            new PartyMinder_Admin();
        }
    }
    
    public function register_post_types() {
        // Party Event Post Type
        register_post_type('party_event', array(
            'labels' => array(
                'name' => __('Party Events', 'partyminder'),
                'singular_name' => __('Party Event', 'partyminder'),
                'add_new' => __('Add New Event', 'partyminder'),
                'add_new_item' => __('Add New Party Event', 'partyminder'),
                'edit_item' => __('Edit Event', 'partyminder'),
                'new_item' => __('New Event', 'partyminder'),
                'view_item' => __('View Event', 'partyminder'),
                'search_items' => __('Search Events', 'partyminder'),
                'not_found' => __('No events found', 'partyminder'),
                'not_found_in_trash' => __('No events found in trash', 'partyminder')
            ),
            'public' => true,
            'has_archive' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-calendar-alt',
            'rewrite' => array('slug' => 'events'),
            'show_in_rest' => true,
            'menu_position' => 20,
            'capability_type' => 'post'
        ));
    }
    
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
        
        ob_start();
        include PARTYMINDER_PLUGIN_DIR . 'templates/event-form.php';
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
        
        ob_start();
        include PARTYMINDER_PLUGIN_DIR . 'templates/events-list.php';
        return ob_get_clean();
    }
    
    public function my_events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_past' => false
        ), $atts);
        
        ob_start();
        include PARTYMINDER_PLUGIN_DIR . 'templates/my-events.php';
        return ob_get_clean();
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
                    wp_redirect(add_query_arg('partyminder_created', 1, wp_get_referer()));
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
    
    public function load_event_template($template) {
        global $post;
        
        if ($post && $post->post_type == 'party_event') {
            $plugin_template = PARTYMINDER_PLUGIN_DIR . 'templates/single-event.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
}