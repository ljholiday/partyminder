<?php

class PartyMinder_Activator {

    public static function activate() {
        global $wpdb;

        // Create custom tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules only on activation
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('partyminder_activated', true);
        update_option('partyminder_activation_date', current_time('mysql'));
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Events table for extended event data
        $events_table = $wpdb->prefix . 'partyminder_events';
        $events_sql = "CREATE TABLE $events_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            event_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            event_time varchar(20) DEFAULT '',
            guest_limit int(11) DEFAULT 0,
            venue_info text,
            host_email varchar(100) DEFAULT '',
            host_notes text,
            ai_plan longtext,
            event_status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY event_date (event_date),
            KEY event_status (event_status)
        ) $charset_collate;";

        // Guests table for RSVP management
        $guests_table = $wpdb->prefix . 'partyminder_guests';
        $guests_sql = "CREATE TABLE $guests_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            dietary_restrictions text,
            plus_one tinyint(1) DEFAULT 0,
            plus_one_name varchar(100) DEFAULT '',
            notes text,
            rsvp_date datetime DEFAULT CURRENT_TIMESTAMP,
            reminder_sent tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY email (email),
            KEY status (status),
            UNIQUE KEY unique_guest_event (event_id, email)
        ) $charset_collate;";

        // AI interactions table for cost tracking
        $ai_table = $wpdb->prefix . 'partyminder_ai_interactions';
        $ai_sql = "CREATE TABLE $ai_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            event_id mediumint(9) DEFAULT NULL,
            interaction_type varchar(50) NOT NULL,
            prompt_text text,
            response_text longtext,
            tokens_used int(11) DEFAULT 0,
            cost_cents int(11) DEFAULT 0,
            provider varchar(20) DEFAULT 'openai',
            model varchar(50) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_id (event_id),
            KEY interaction_type (interaction_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($events_sql);
        dbDelta($guests_sql);
        dbDelta($ai_sql);
    }

    private static function set_default_options() {
        // Plugin settings
        add_option('partyminder_version', PARTYMINDER_VERSION);
        
        // AI Configuration
        add_option('partyminder_ai_provider', 'openai');
        add_option('partyminder_ai_api_key', '');
        add_option('partyminder_ai_model', 'gpt-4');
        add_option('partyminder_ai_cost_limit_monthly', 50);
        
        // Email Settings
        add_option('partyminder_email_from_name', get_bloginfo('name'));
        add_option('partyminder_email_from_address', get_option('admin_email'));
        
        // Feature Settings
        add_option('partyminder_enable_public_events', true);
        add_option('partyminder_demo_mode', true);
        add_option('partyminder_track_analytics', true);
        
        // Styling options
        add_option('partyminder_primary_color', '#667eea');
        add_option('partyminder_secondary_color', '#764ba2');
        add_option('partyminder_button_style', 'rounded');
        add_option('partyminder_form_layout', 'card');
    }
}