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
        
        // Event invitations table
        self::create_event_invitations_table();
        
        // User profiles table
        self::create_user_profiles_table();
        
        // Communities and AT Protocol tables (safe to create even if features disabled)
        self::create_communities_tables();
        
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
        
        // Communities Feature Flags - DISABLED BY DEFAULT FOR SAFE DEPLOYMENT
        add_option('partyminder_enable_communities', false);
        add_option('partyminder_enable_at_protocol', false);
        add_option('partyminder_communities_require_approval', true);
        add_option('partyminder_max_communities_per_user', 10);
        
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
            ),
            'communities' => array(
                'title' => __('Communities', 'partyminder'),
                'content' => '[partyminder_communities]',
                'slug' => 'communities',
                'description' => __('Join communities of fellow hosts and guests to plan events together.', 'partyminder')
            ),
            'profile' => array(
                'title' => __('My Profile', 'partyminder'),
                'content' => '[partyminder_profile]',
                'slug' => 'profile',
                'description' => __('Manage your PartyMinder profile, preferences, and hosting reputation.', 'partyminder')
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
                        case 'communities':
                            update_post_meta($page_id, '_yoast_wpseo_title', __('Communities - Join Groups & Plan Together', 'partyminder'));
                            update_post_meta($page_id, '_yoast_wpseo_metadesc', __('Join communities of fellow hosts and guests. Create work, family, or hobby groups to plan events together with shared interests.', 'partyminder'));
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
    
    private static function create_user_profiles_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // User profiles table
        $profiles_table = $wpdb->prefix . 'partyminder_user_profiles';
        $profiles_sql = "CREATE TABLE $profiles_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            display_name varchar(255) DEFAULT '',
            bio text DEFAULT '',
            location varchar(255) DEFAULT '',
            profile_image varchar(255) DEFAULT '',
            website_url varchar(255) DEFAULT '',
            social_links longtext DEFAULT '',
            hosting_preferences longtext DEFAULT '',
            notification_preferences longtext DEFAULT '',
            privacy_settings longtext DEFAULT '',
            events_hosted int(11) DEFAULT 0,
            events_attended int(11) DEFAULT 0,
            host_rating decimal(3,2) DEFAULT 0.00,
            host_reviews_count int(11) DEFAULT 0,
            favorite_event_types longtext DEFAULT '',
            available_times longtext DEFAULT '',
            dietary_restrictions text DEFAULT '',
            accessibility_needs text DEFAULT '',
            is_verified tinyint(1) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            last_active datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY display_name (display_name),
            KEY location (location),
            KEY is_verified (is_verified),
            KEY is_active (is_active),
            KEY last_active (last_active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($profiles_sql);
    }

    private static function create_communities_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Communities table
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        $communities_sql = "CREATE TABLE $communities_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            type varchar(50) DEFAULT 'standard',
            privacy varchar(20) DEFAULT 'public',
            member_count int(11) DEFAULT 0,
            event_count int(11) DEFAULT 0,
            creator_id bigint(20) UNSIGNED NOT NULL,
            creator_email varchar(100) NOT NULL,
            featured_image varchar(255) DEFAULT '',
            settings longtext DEFAULT '',
            at_protocol_did varchar(255) DEFAULT '',
            at_protocol_handle varchar(255) DEFAULT '',
            at_protocol_data longtext DEFAULT '',
            is_active tinyint(1) DEFAULT 1,
            requires_approval tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY at_protocol_did (at_protocol_did),
            KEY creator_id (creator_id),
            KEY privacy (privacy),
            KEY type (type),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Community members table
        $members_table = $wpdb->prefix . 'partyminder_community_members';
        $members_sql = "CREATE TABLE $members_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            community_id mediumint(9) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            email varchar(100) NOT NULL,
            display_name varchar(100) NOT NULL,
            role varchar(50) DEFAULT 'member',
            permissions longtext DEFAULT '',
            status varchar(20) DEFAULT 'active',
            at_protocol_did varchar(255) DEFAULT '',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_seen_at datetime DEFAULT CURRENT_TIMESTAMP,
            invitation_data longtext DEFAULT '',
            PRIMARY KEY (id),
            KEY community_id (community_id),
            KEY user_id (user_id),
            KEY email (email),
            KEY role (role),
            KEY status (status),
            KEY at_protocol_did (at_protocol_did),
            UNIQUE KEY unique_member (community_id, user_id, email)
        ) $charset_collate;";
        
        // Community events table (extends regular events with community context)
        $community_events_table = $wpdb->prefix . 'partyminder_community_events';
        $community_events_sql = "CREATE TABLE $community_events_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            community_id mediumint(9) NOT NULL,
            event_id mediumint(9) NOT NULL,
            organizer_member_id mediumint(9) NOT NULL,
            visibility varchar(20) DEFAULT 'community',
            member_permissions varchar(50) DEFAULT 'view_rsvp',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY community_id (community_id),
            KEY event_id (event_id),
            KEY organizer_member_id (organizer_member_id),
            KEY visibility (visibility),
            UNIQUE KEY unique_community_event (community_id, event_id)
        ) $charset_collate;";
        
        // Member identity table (for AT Protocol DIDs and cross-site identity)
        $identities_table = $wpdb->prefix . 'partyminder_member_identities';
        $identities_sql = "CREATE TABLE $identities_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            email varchar(100) NOT NULL,
            display_name varchar(100) NOT NULL,
            at_protocol_did varchar(255) NOT NULL,
            at_protocol_handle varchar(255) DEFAULT '',
            at_protocol_pds varchar(255) DEFAULT '',
            at_protocol_data longtext DEFAULT '',
            public_key longtext DEFAULT '',
            private_key_encrypted longtext DEFAULT '',
            cross_site_data longtext DEFAULT '',
            is_verified tinyint(1) DEFAULT 0,
            last_sync_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY email (email),
            UNIQUE KEY at_protocol_did (at_protocol_did),
            KEY at_protocol_handle (at_protocol_handle),
            KEY is_verified (is_verified)
        ) $charset_collate;";
        
        // Community invitations table
        $invitations_table = $wpdb->prefix . 'partyminder_community_invitations';
        $invitations_sql = "CREATE TABLE $invitations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            community_id mediumint(9) NOT NULL,
            invited_by_member_id mediumint(9) NOT NULL,
            invited_email varchar(100) NOT NULL,
            invited_user_id bigint(20) UNSIGNED DEFAULT NULL,
            invitation_token varchar(64) NOT NULL,
            message text DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            expires_at datetime NOT NULL,
            responded_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY community_id (community_id),
            KEY invited_by_member_id (invited_by_member_id),
            KEY invited_email (invited_email),
            KEY invited_user_id (invited_user_id),
            KEY status (status),
            KEY expires_at (expires_at),
            UNIQUE KEY invitation_token (invitation_token)
        ) $charset_collate;";
        
        // AT Protocol sync log (for tracking federation state)
        $sync_log_table = $wpdb->prefix . 'partyminder_at_protocol_sync';
        $sync_log_sql = "CREATE TABLE $sync_log_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            entity_type varchar(50) NOT NULL,
            entity_id mediumint(9) NOT NULL,
            sync_type varchar(50) NOT NULL,
            at_protocol_uri varchar(255) DEFAULT '',
            sync_status varchar(20) DEFAULT 'pending',
            sync_data longtext DEFAULT '',
            error_message text DEFAULT '',
            attempts int(11) DEFAULT 0,
            last_attempt_at datetime DEFAULT NULL,
            synced_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id),
            KEY sync_type (sync_type),
            KEY sync_status (sync_status),
            KEY at_protocol_uri (at_protocol_uri)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($communities_sql);
        dbDelta($members_sql);
        dbDelta($community_events_sql);
        dbDelta($identities_sql);
        dbDelta($invitations_sql);
        dbDelta($sync_log_sql);
    }
    
    private static function create_event_invitations_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Event invitations table
        $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
        $invitations_sql = "CREATE TABLE $invitations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            invited_by_user_id bigint(20) UNSIGNED NOT NULL,
            invited_email varchar(100) NOT NULL,
            invited_user_id bigint(20) UNSIGNED DEFAULT NULL,
            invitation_token varchar(64) NOT NULL,
            message text DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            expires_at datetime NOT NULL,
            responded_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY invited_by_user_id (invited_by_user_id),
            KEY invited_email (invited_email),
            KEY invited_user_id (invited_user_id),
            KEY status (status),
            KEY expires_at (expires_at),
            UNIQUE KEY invitation_token (invitation_token),
            UNIQUE KEY unique_event_invitation (event_id, invited_email, status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($invitations_sql);
    }
}