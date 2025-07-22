<?php

class PartyMinder_Activator {

    public static function activate() {
        global $wpdb;

        // Create custom tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create dedicated pages for shortcode usage
        self::create_pages();
        
        // Flush rewrite rules only on activation
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('partyminder_activated', true);
        update_option('partyminder_activation_date', current_time('mysql'));
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Events table - pure custom table, no WordPress posts
        $events_table = $wpdb->prefix . 'partyminder_events';
        $events_sql = "CREATE TABLE $events_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description longtext,
            excerpt text,
            event_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            event_time varchar(20) DEFAULT '',
            guest_limit int(11) DEFAULT 0,
            venue_info text,
            host_email varchar(100) DEFAULT '',
            host_notes text,
            ai_plan longtext,
            event_status varchar(20) DEFAULT 'active',
            author_id bigint(20) UNSIGNED DEFAULT 1,
            featured_image varchar(255) DEFAULT '',
            meta_title varchar(255) DEFAULT '',
            meta_description text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY event_date (event_date),
            KEY event_status (event_status),
            KEY author_id (author_id)
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

        // Conversation topics table
        $topics_table = $wpdb->prefix . 'partyminder_conversation_topics';
        $topics_sql = "CREATE TABLE $topics_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            icon varchar(10) DEFAULT '',
            sort_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY sort_order (sort_order),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Conversations table
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        $conversations_sql = "CREATE TABLE $conversations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            topic_id mediumint(9) NOT NULL,
            event_id mediumint(9) DEFAULT NULL,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            content longtext NOT NULL,
            author_id bigint(20) UNSIGNED NOT NULL,
            author_name varchar(100) NOT NULL,
            author_email varchar(100) NOT NULL,
            is_pinned tinyint(1) DEFAULT 0,
            is_locked tinyint(1) DEFAULT 0,
            reply_count int(11) DEFAULT 0,
            last_reply_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_reply_author varchar(100) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY topic_id (topic_id),
            KEY event_id (event_id),
            KEY author_id (author_id),
            KEY is_pinned (is_pinned),
            KEY last_reply_date (last_reply_date),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Conversation replies table
        $replies_table = $wpdb->prefix . 'partyminder_conversation_replies';
        $replies_sql = "CREATE TABLE $replies_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            conversation_id mediumint(9) NOT NULL,
            parent_reply_id mediumint(9) DEFAULT NULL,
            content longtext NOT NULL,
            author_id bigint(20) UNSIGNED NOT NULL,
            author_name varchar(100) NOT NULL,
            author_email varchar(100) NOT NULL,
            depth_level int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY parent_reply_id (parent_reply_id),
            KEY author_id (author_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Conversation follows table
        $follows_table = $wpdb->prefix . 'partyminder_conversation_follows';
        $follows_sql = "CREATE TABLE $follows_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            conversation_id mediumint(9) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            email varchar(100) NOT NULL,
            last_read_at datetime DEFAULT CURRENT_TIMESTAMP,
            notification_frequency varchar(20) DEFAULT 'immediate',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY user_id (user_id),
            KEY email (email),
            UNIQUE KEY unique_follow (conversation_id, user_id, email)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($events_sql);
        dbDelta($guests_sql);
        dbDelta($ai_sql);
        dbDelta($topics_sql);
        dbDelta($conversations_sql);
        dbDelta($replies_sql);
        dbDelta($follows_sql);
        
        // Create default conversation topics
        self::create_default_conversation_topics();
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
    
    private static function create_pages() {
        $pages = array(
            'events' => array(
                'title' => __('Events', 'partyminder'),
                'content' => '[partyminder_events_list]',
                'slug' => 'events',
                'description' => __('Discover and RSVP to exciting events in your area.', 'partyminder')
            ),
            'create-event' => array(
                'title' => __('Create Event', 'partyminder'),
                'content' => '[partyminder_event_form]',
                'slug' => 'create-event',
                'description' => __('Plan and host your perfect event with our easy-to-use event creation tools.', 'partyminder')
            ),
            'my-events' => array(
                'title' => __('My Events', 'partyminder'),
                'content' => '[partyminder_my_events]',
                'slug' => 'my-events',
                'description' => __('View and manage all your created events and RSVPs in one convenient dashboard.', 'partyminder')
            ),
            'edit-event' => array(
                'title' => __('Edit Event', 'partyminder'),
                'content' => '[partyminder_event_edit_form]',
                'slug' => 'edit-event',
                'description' => __('Update your event details, manage guest lists, and edit event information.', 'partyminder')
            ),
            'conversations' => array(
                'title' => __('Community Conversations', 'partyminder'),
                'content' => '[partyminder_conversations]',
                'slug' => 'conversations',
                'description' => __('Connect, share tips, and plan amazing gatherings with the community.', 'partyminder')
            )
        );
        
        foreach ($pages as $key => $page) {
            // Check if page already exists
            $page_id = get_option('partyminder_page_' . $key);
            $existing_page = $page_id ? get_post($page_id) : null;
            
            if (!$existing_page || $existing_page->post_status !== 'publish') {
                // Create the page with shortcode content for theme integration
                $page_content = $page['content'];
                
                // Add introductory content for better theme integration
                if ($key === 'events') {
                    $page_content = '<p>' . $page['description'] . '</p>' . "\n\n" . $page_content;
                } elseif ($key === 'create-event') {
                    $page_content = '<p>' . $page['description'] . '</p>' . "\n\n" . $page_content;
                }
                
                $page_data = array(
                    'post_title' => $page['title'],
                    'post_content' => $page_content,
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $page['slug'],
                    'post_author' => 1,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_excerpt' => $page['description'],
                    'meta_input' => array(
                        '_partyminder_page' => $key,
                        '_partyminder_page_type' => $key,
                        // Remove custom page template - let theme handle it
                    )
                );
                
                $page_id = wp_insert_post($page_data);
                
                if (!is_wp_error($page_id)) {
                    update_option('partyminder_page_' . $key, $page_id);
                    
                    // Set SEO-friendly meta data
                    switch ($key) {
                        case 'events':
                            update_post_meta($page_id, '_yoast_wpseo_title', __('Upcoming Events - Find Amazing Parties Near You', 'partyminder'));
                            update_post_meta($page_id, '_yoast_wpseo_metadesc', __('Discover and RSVP to exciting events in your area. Join the community and never miss a great party!', 'partyminder'));
                            break;
                        case 'create-event':
                            update_post_meta($page_id, '_yoast_wpseo_title', __('Create Your Event - Host an Amazing Party', 'partyminder'));
                            update_post_meta($page_id, '_yoast_wpseo_metadesc', __('Plan and host your perfect event with our easy-to-use event creation tools. Invite guests and manage RSVPs effortlessly.', 'partyminder'));
                            break;
                        case 'my-events':
                            update_post_meta($page_id, '_yoast_wpseo_title', __('My Events Dashboard - Manage Your Events', 'partyminder'));
                            update_post_meta($page_id, '_yoast_wpseo_metadesc', __('View and manage all your created events and RSVPs in one convenient dashboard.', 'partyminder'));
                            break;
                        case 'edit-event':
                            update_post_meta($page_id, '_yoast_wpseo_title', __('Edit Event - Update Your Event Details', 'partyminder'));
                            update_post_meta($page_id, '_yoast_wpseo_metadesc', __('Update your event details, manage guest lists, and edit event information.', 'partyminder'));
                            update_post_meta($page_id, '_yoast_wpseo_meta-robots-noindex', '1'); // Don't index edit pages
                            break;
                        case 'conversations':
                            update_post_meta($page_id, '_yoast_wpseo_title', __('Community Conversations - Connect & Share', 'partyminder'));
                            update_post_meta($page_id, '_yoast_wpseo_metadesc', __('Join community conversations about hosting tips, recipes, party planning and more. Connect with fellow party hosts and guests.', 'partyminder'));
                            break;
                    }
                }
            }
        }
    }
    
    private static function create_default_conversation_topics() {
        global $wpdb;
        
        $topics_table = $wpdb->prefix . 'partyminder_conversation_topics';
        
        // Check if topics already exist
        $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $topics_table");
        if ($existing_count > 0) {
            return; // Topics already exist
        }
        
        $default_topics = array(
            array(
                'name' => __('Welcome & Introductions', 'partyminder'),
                'slug' => 'welcome-introductions',
                'description' => __('Introduce yourself to the community and welcome new members.', 'partyminder'),
                'icon' => '👋',
                'sort_order' => 10
            ),
            array(
                'name' => __('Hosting Tips & Questions', 'partyminder'),
                'slug' => 'hosting-tips',
                'description' => __('Share hosting wisdom and get help with your hosting challenges.', 'partyminder'),
                'icon' => '🍽️',
                'sort_order' => 20
            ),
            array(
                'name' => __('Recipes & Food Ideas', 'partyminder'),
                'slug' => 'recipes-food',
                'description' => __('Share your favorite party recipes and discover new food ideas.', 'partyminder'),
                'icon' => '🍳',
                'sort_order' => 30
            ),
            array(
                'name' => __('Party Planning & Themes', 'partyminder'),
                'slug' => 'party-planning',
                'description' => __('Brainstorm creative party themes and planning strategies.', 'partyminder'),
                'icon' => '🎨',
                'sort_order' => 40
            ),
            array(
                'name' => __('Venue & Setup Ideas', 'partyminder'),
                'slug' => 'venue-setup',
                'description' => __('Share venue recommendations and setup inspiration.', 'partyminder'),
                'icon' => '🏠',
                'sort_order' => 50
            ),
            array(
                'name' => __('General Community Chat', 'partyminder'),
                'slug' => 'general-chat',
                'description' => __('Casual conversations and community discussions.', 'partyminder'),
                'icon' => '💡',
                'sort_order' => 60
            )
        );
        
        foreach ($default_topics as $topic) {
            $wpdb->insert(
                $topics_table,
                array(
                    'name' => $topic['name'],
                    'slug' => $topic['slug'],
                    'description' => $topic['description'],
                    'icon' => $topic['icon'],
                    'sort_order' => $topic['sort_order'],
                    'is_active' => 1,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%d', '%d', '%s')
            );
        }
    }
}