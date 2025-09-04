<?php

class PartyMinder_Activator {

	public static function activate() {
		global $wpdb;

		// Create custom tables
		self::create_tables();

		// Run database migrations
		self::run_database_migrations();

		// Set default options
		self::set_default_options();

		// Create dedicated pages for shortcode usage
		self::create_pages();

		// Flush rewrite rules only on activation
		flush_rewrite_rules();

		// Backfill personal communities for existing users
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-personal-community-service.php';
		$created_count = PartyMinder_Personal_Community_Service::backfill_existing_users();
		if ( $created_count > 0 ) {
			error_log( "PartyMinder: Created $created_count personal communities during activation" );
		}

		// Index existing content for search
		self::index_existing_content();

		// Set activation flag
		update_option( 'partyminder_activated', true );
		update_option( 'partyminder_activation_date', current_time( 'mysql' ) );
	}

	/**
	 * Main table creation method - calls individual table methods
	 */
	private static function create_tables() {
		// Core event system tables
		self::create_events_table();
		self::create_guests_table();
		self::create_event_invitations_table();
		self::create_event_rsvps_table();

		// Conversation system tables
		self::create_conversations_table();
		self::create_conversation_replies_table();
		self::create_conversation_follows_table();

		// Community system tables
		self::create_communities_tables();

		// User and media tables
		self::create_user_profiles_table();
		self::create_post_images_table();

		// AI tracking table
		self::create_ai_interactions_table();

		// Search index table
		self::create_search_table();


		// Upgrade existing installations
		self::upgrade_database_schema();
	}

	/**
	 * Events table - Main events data
	 */
	private static function create_events_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
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
            privacy varchar(20) DEFAULT 'public',
            event_status varchar(20) DEFAULT 'active',
            author_id bigint(20) UNSIGNED DEFAULT 1,
            community_id mediumint(9) DEFAULT NULL,
            featured_image varchar(255) DEFAULT '',
            meta_title varchar(255) DEFAULT '',
            meta_description text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY event_date (event_date),
            KEY event_status (event_status),
            KEY author_id (author_id),
            KEY privacy (privacy),
            KEY community_id (community_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $events_sql );
	}

	/**
	 * Guests table - Event attendees/RSVPs (includes migrations)
	 */
	private static function create_guests_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$guests_table = $wpdb->prefix . 'partyminder_guests';

		$guests_sql = "CREATE TABLE $guests_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            rsvp_token varchar(255) DEFAULT '',
            temporary_guest_id varchar(32) DEFAULT '',
            converted_user_id bigint(20) UNSIGNED DEFAULT NULL,
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
            KEY rsvp_token (rsvp_token),
            KEY temporary_guest_id (temporary_guest_id),
            KEY converted_user_id (converted_user_id),
            UNIQUE KEY unique_guest_event (event_id, email)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $guests_sql );
	}

	/**
	 * Event invitations table - Invitation tracking
	 */
	private static function create_event_invitations_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$invitations_table = $wpdb->prefix . 'partyminder_event_invitations';

		$invitations_sql = "CREATE TABLE $invitations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            invited_by_user_id bigint(20) UNSIGNED NOT NULL,
            invited_email varchar(100) NOT NULL,
            invitation_token varchar(32) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            responded_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY invited_by_user_id (invited_by_user_id),
            KEY invited_email (invited_email),
            KEY invitation_token (invitation_token),
            KEY status (status)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $invitations_sql );
	}

	/**
	 * Event RSVPs table - Modern RSVP flow (separate from guests)
	 */
	private static function create_event_rsvps_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$rsvps_table = $wpdb->prefix . 'partyminder_event_rsvps';

		$rsvps_sql = "CREATE TABLE $rsvps_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT '',
            dietary_restrictions text,
            accessibility_needs text,
            plus_one tinyint(1) DEFAULT 0,
            plus_one_name varchar(100) DEFAULT '',
            plus_one_dietary text DEFAULT '',
            notes text DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            invitation_token varchar(255) DEFAULT '',
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY email (email),
            KEY status (status),
            KEY user_id (user_id),
            KEY invitation_token (invitation_token)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $rsvps_sql );
	}

	/**
	 * Conversation topics table - Topic/category system
	 */
	private static function create_conversation_topics_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $topics_sql );
	}

	/**
	 * Conversations table - Discussion threads
	 */
	private static function create_conversations_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$conversations_table = $wpdb->prefix . 'partyminder_conversations';

		$conversations_sql = "CREATE TABLE $conversations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) DEFAULT NULL,
            community_id mediumint(9) DEFAULT NULL,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            content longtext NOT NULL,
            author_id bigint(20) UNSIGNED NOT NULL,
            author_name varchar(100) NOT NULL,
            author_email varchar(100) NOT NULL,
            privacy varchar(20) DEFAULT 'public',
            is_pinned tinyint(1) DEFAULT 0,
            is_locked tinyint(1) DEFAULT 0,
            reply_count int(11) DEFAULT 0,
            last_reply_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_reply_author varchar(100) DEFAULT '',
            featured_image varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY community_id (community_id),
            KEY author_id (author_id),
            KEY is_pinned (is_pinned),
            KEY community_created (community_id, created_at),
            KEY last_reply_date (last_reply_date),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $conversations_sql );
	}

	/**
	 * Conversation replies table
	 */
	private static function create_conversation_replies_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
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
            KEY created_at (created_at),
            KEY conversation_created (conversation_id, created_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $replies_sql );
	}

	/**
	 * Conversation follows table - Subscriptions
	 */
	private static function create_conversation_follows_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $follows_sql );
	}

	/**
	 * AI interactions table - Cost and usage tracking
	 */
	private static function create_ai_interactions_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $ai_sql );
	}

	/**
	 * User profiles table - Extended user data
	 */
	private static function create_user_profiles_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$profiles_table = $wpdb->prefix . 'partyminder_user_profiles';

		$profiles_sql = "CREATE TABLE $profiles_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            display_name varchar(255) DEFAULT '',
            bio text DEFAULT '',
            location varchar(255) DEFAULT '',
            profile_image varchar(255) DEFAULT '',
            cover_image varchar(255) DEFAULT '',
            avatar_source varchar(20) DEFAULT 'gravatar',
            website_url varchar(255) DEFAULT '',
            social_links longtext DEFAULT '',
            hosting_preferences longtext DEFAULT '',
            notification_preferences longtext DEFAULT '',
            privacy_settings longtext DEFAULT '',
            events_hosted int(11) DEFAULT 0,
            events_attended int(11) DEFAULT 0,
            host_rating decimal(3,2) DEFAULT 0.00,
            host_reviews_count int(11) DEFAULT 0,
            available_times longtext DEFAULT '',
            dietary_restrictions text,
            accessibility_needs text,
            is_verified tinyint(1) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            last_active datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY is_active (is_active),
            KEY is_verified (is_verified)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $profiles_sql );
	}

	/**
	 * Post images table - Event/conversation image attachments
	 */
	private static function create_post_images_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$images_table = $wpdb->prefix . 'partyminder_post_images';

		$images_sql = "CREATE TABLE $images_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            image_url varchar(500) NOT NULL,
            thumbnail_url varchar(500) DEFAULT '',
            alt_text varchar(255) DEFAULT '',
            caption text DEFAULT '',
            display_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY display_order (display_order)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $images_sql );
	}

	/**
	 * Communities system tables - All community-related tables
	 */
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
            personal_owner_user_id bigint(20) UNSIGNED DEFAULT NULL,
            visibility enum('public','private') NOT NULL DEFAULT 'public',
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
            KEY creator_id (creator_id),
            KEY personal_owner_user_id (personal_owner_user_id),
            KEY visibility (visibility),
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
            status enum('active','pending','blocked') NOT NULL DEFAULT 'active',
            at_protocol_did varchar(255) DEFAULT '',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_seen_at datetime DEFAULT CURRENT_TIMESTAMP,
            invitation_data longtext DEFAULT '',
            PRIMARY KEY (id),
            KEY community_id (community_id),
            KEY user_id (user_id),
            KEY email (email),
            KEY status (status),
            UNIQUE KEY unique_membership (community_id, user_id)
        ) $charset_collate;";

		// Community events table
		$community_events_table = $wpdb->prefix . 'partyminder_community_events';
		$community_events_sql = "CREATE TABLE $community_events_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            community_id mediumint(9) NOT NULL,
            event_id mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY community_id (community_id),
            KEY event_id (event_id),
            UNIQUE KEY unique_community_event (community_id, event_id)
        ) $charset_collate;";

		// Community invitations table
		$community_invitations_table = $wpdb->prefix . 'partyminder_community_invitations';
		$community_invitations_sql = "CREATE TABLE $community_invitations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            community_id mediumint(9) NOT NULL,
            invited_by_member_id mediumint(9) NOT NULL,
            invited_email varchar(100) NOT NULL,
            invitation_token varchar(255) NOT NULL,
            message text DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            responded_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY community_id (community_id),
            KEY invited_by_member_id (invited_by_member_id),
            KEY invited_email (invited_email),
            KEY invitation_token (invitation_token),
            KEY status (status)
        ) $charset_collate;";

		// Member identities table (AT Protocol integration)
		$member_identities_table = $wpdb->prefix . 'partyminder_member_identities';
		$member_identities_sql = "CREATE TABLE $member_identities_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            email varchar(100) NOT NULL,
            did varchar(255) DEFAULT '',
            handle varchar(255) DEFAULT '',
            access_jwt text DEFAULT '',
            refresh_jwt text DEFAULT '',
            pds_url varchar(255) DEFAULT '',
            profile_data longtext DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY email (email),
            KEY did (did),
            KEY handle (handle)
        ) $charset_collate;";

		// AT Protocol sync log table
		$sync_log_table = $wpdb->prefix . 'partyminder_at_protocol_sync_log';
		$sync_log_sql = "CREATE TABLE $sync_log_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            entity_type varchar(50) NOT NULL,
            entity_id mediumint(9) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            action varchar(50) NOT NULL,
            at_uri varchar(255) DEFAULT '',
            success tinyint(1) DEFAULT 0,
            error_message text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY success (success)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $communities_sql );
		dbDelta( $members_sql );
		dbDelta( $community_events_sql );
		dbDelta( $community_invitations_sql );
		dbDelta( $member_identities_sql );
		dbDelta( $sync_log_sql );
	}

	private static function run_database_migrations() {
		global $wpdb;
		
		// Check if community_id column exists in events table
		$events_table = $wpdb->prefix . 'partyminder_events';
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM $events_table LIKE %s",
				'community_id'
			)
		);

		if ( empty( $column_exists ) ) {
			// Add community_id column to events table
			$wpdb->query( "ALTER TABLE $events_table ADD COLUMN community_id mediumint(9) DEFAULT NULL AFTER author_id" );
			$wpdb->query( "ALTER TABLE $events_table ADD INDEX community_id (community_id)" );
		}

		// Add anonymous RSVP support fields to guests table
		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$rsvp_token_column = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM $guests_table LIKE %s",
				'rsvp_token'
			)
		);

		if ( empty( $rsvp_token_column ) ) {
			$wpdb->query( "ALTER TABLE $guests_table ADD COLUMN rsvp_token varchar(255) DEFAULT '' AFTER id" );
			$wpdb->query( "ALTER TABLE $guests_table ADD COLUMN temporary_guest_id varchar(32) DEFAULT '' AFTER rsvp_token" );
			$wpdb->query( "ALTER TABLE $guests_table ADD COLUMN converted_user_id bigint(20) UNSIGNED DEFAULT NULL AFTER temporary_guest_id" );
			$wpdb->query( "ALTER TABLE $guests_table ADD INDEX rsvp_token (rsvp_token)" );
			$wpdb->query( "ALTER TABLE $guests_table ADD INDEX temporary_guest_id (temporary_guest_id)" );
			$wpdb->query( "ALTER TABLE $guests_table ADD INDEX converted_user_id (converted_user_id)" );
			
			// Debug: Log the migration
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'RSVP Debug - Database migration completed. Added rsvp_token, temporary_guest_id, converted_user_id columns to ' . $guests_table );
			}
		} else {
			// Debug: Log that migration was skipped
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'RSVP Debug - Database migration skipped. rsvp_token column already exists in ' . $guests_table );
			}
		}

		// Run other existing migrations
		try {
			self::upgrade_database_schema();
		} catch ( Exception $e ) {
			error_log( 'PartyMinder: Database schema upgrade failed: ' . $e->getMessage() );
		}
		
		// TODO: Fix member status schema mismatch (disabled until activation is stable)
		
		// Run privacy inheritance migration
		try {
			self::migrate_privacy_inheritance();
		} catch ( Exception $e ) {
			error_log( 'PartyMinder: Privacy migration failed: ' . $e->getMessage() );
		}
	}

	private static function set_default_options() {
		// Plugin settings
		add_option( 'partyminder_version', PARTYMINDER_VERSION );

		// AI Configuration
		add_option( 'partyminder_ai_provider', 'openai' );
		add_option( 'partyminder_ai_api_key', '' );
		add_option( 'partyminder_ai_model', 'gpt-4' );
		add_option( 'partyminder_ai_cost_limit_monthly', 50 );

		// Email Settings
		add_option( 'partyminder_email_from_name', get_bloginfo( 'name' ) );
		add_option( 'partyminder_email_from_address', get_option( 'admin_email' ) );

		// Feature Settings
		add_option( 'partyminder_enable_public_events', true );
		add_option( 'partyminder_demo_mode', true );
		add_option( 'partyminder_track_analytics', true );

		// Communities Feature Flags - ENABLED BY DEFAULT FOR SOCIAL NETWORK
		add_option( 'partyminder_enable_communities', true );
		add_option( 'partyminder_enable_at_protocol', false );
		add_option( 'partyminder_communities_require_approval', true );
		add_option( 'partyminder_max_communities_per_user', 10 );

		// Circles Implementation Feature Flags - Step 1 complete, enable schema
		add_option( 'partyminder_circles_schema', true );
		// Step 2 complete, enable personal communities for new users
		add_option( 'partyminder_personal_community_new_users', true );
		// Step 3 complete, enable personal communities backfill
		add_option( 'partyminder_personal_community_backfill', true );
		// Step 4 complete, enable general conversations default to personal
		add_option( 'partyminder_general_convo_default_to_personal', true );
		// Step 5 complete, enable reply join flow
		add_option( 'partyminder_reply_join_flow', true );
		// Step 6 complete, enable circles resolver
		add_option( 'partyminder_circles_resolver', true );
		// Step 7 complete, enable conversation feed by circle
		add_option( 'partyminder_convo_feed_by_circle', true );

		// UI options (removed color customization - now uses WordPress theme colors)
		add_option( 'partyminder_show_avatars', true );
		add_option( 'partyminder_use_featured_images', true );

		// Security options
		add_option( 'partyminder_allow_anonymous_rsvps', true );
		add_option( 'partyminder_anonymous_rsvp_timeout', 7 * DAY_IN_SECONDS );
		add_option( 'partyminder_rsvp_time_limit', 2 * HOUR_IN_SECONDS );
		add_option( 'partyminder_auto_approve_rsvps', false );

		// Content options
		add_option( 'partyminder_events_per_page', 20 );
		add_option( 'partyminder_conversations_per_page', 15 );
		add_option( 'partyminder_show_event_counts', true );
		add_option( 'partyminder_enable_event_sharing', true );
	}

	private static function create_pages() {
		$pages = array(
			'dashboard'                => array(
				'title'       => __( 'Dashboard', 'partyminder' ),
				'content'     => '[partyminder_dashboard]',
				'slug'        => 'dashboard',
				'description' => __( 'Your events dashboard and activity feed', 'partyminder' ),
			),
			'events'                   => array(
				'title'       => __( 'Events', 'partyminder' ),
				'content'     => '[partyminder_events]',
				'slug'        => 'events',
				'description' => __( 'Browse and discover upcoming events', 'partyminder' ),
			),
			'create-event'             => array(
				'title'       => __( 'Create Event', 'partyminder' ),
				'content'     => '[partyminder_create_event]',
				'slug'        => 'create-event',
				'description' => __( 'Create and manage your own events', 'partyminder' ),
			),
			'create-community-event'   => array(
				'title'       => __( 'Create Community Event', 'partyminder' ),
				'content'     => '[partyminder_create_community_event]',
				'slug'        => 'create-community-event',
				'description' => __( 'Create an event for your community', 'partyminder' ),
			),
			'my-events'                => array(
				'title'       => __( 'My Events', 'partyminder' ),
				'content'     => '[partyminder_my_events]',
				'slug'        => 'my-events',
				'description' => __( 'View and manage your created events and RSVPs', 'partyminder' ),
			),
			'edit-event'               => array(
				'title'       => __( 'Edit Event', 'partyminder' ),
				'content'     => '[partyminder_edit_event]',
				'slug'        => 'edit-event',
				'description' => __( 'Edit your event details and settings', 'partyminder' ),
			),
			'create-conversation'      => array(
				'title'       => __( 'Start Conversation', 'partyminder' ),
				'content'     => '[partyminder_create_conversation]',
				'slug'        => 'create-conversation',
				'description' => __( 'Start a new discussion topic', 'partyminder' ),
			),
			'conversations'            => array(
				'title'       => __( 'Conversations', 'partyminder' ),
				'content'     => '[partyminder_conversations]',
				'slug'        => 'conversations',
				'description' => __( 'Join discussions about events and parties', 'partyminder' ),
			),
			'communities'              => array(
				'title'       => __( 'Communities', 'partyminder' ),
				'content'     => '[partyminder_communities]',
				'slug'        => 'communities',
				'description' => __( 'Discover and join communities', 'partyminder' ),
			),
			'my-communities'           => array(
				'title'       => __( 'My Communities', 'partyminder' ),
				'content'     => '[partyminder_my_communities]',
				'slug'        => 'my-communities',
				'description' => __( 'Manage your community memberships', 'partyminder' ),
			),
			'create-community'         => array(
				'title'       => __( 'Create Community', 'partyminder' ),
				'content'     => '[partyminder_create_community]',
				'slug'        => 'create-community',
				'description' => __( 'Start your own community', 'partyminder' ),
			),
			'manage-community'         => array(
				'title'       => __( 'Manage Community', 'partyminder' ),
				'content'     => '[partyminder_manage_community]',
				'slug'        => 'manage-community',
				'description' => __( 'Manage your community settings and members', 'partyminder' ),
			),
			'profile'                  => array(
				'title'       => __( 'Profile', 'partyminder' ),
				'content'     => '[partyminder_profile]',
				'slug'        => 'profile',
				'description' => __( 'View and edit your profile', 'partyminder' ),
			),
			'login'                    => array(
				'title'       => __( 'Login', 'partyminder' ),
				'content'     => '[partyminder_login]',
				'slug'        => 'login',
				'description' => __( 'Login to your account', 'partyminder' ),
			),
		);

		foreach ( $pages as $page_key => $page_data ) {
			// Check if page already exists
			$page_id = get_option( 'partyminder_page_' . $page_key );
			$existing_page = $page_id ? get_post( $page_id ) : null;
			
			if ( ! $existing_page || $existing_page->post_status !== 'publish' ) {
				$page_args = array(
					'post_title'     => $page_data['title'],
					'post_content'   => $page_data['content'],
					'post_name'      => $page_data['slug'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				);

				$page_id = wp_insert_post( $page_args );

				if ( $page_id && ! is_wp_error( $page_id ) ) {
					// Store the page ID option
					update_option( 'partyminder_page_' . $page_key, $page_id );

					// Add page meta
					update_post_meta( $page_id, '_partyminder_page_type', $page_key );
					update_post_meta( $page_id, '_partyminder_description', $page_data['description'] );

					// Special handling for certain page types
					switch ( $page_key ) {
						case 'edit-event':
							update_post_meta( $page_id, '_partyminder_requires_login', true );
							break;
					}
				}
			}
		}
	}

	private static function create_default_conversation_topics() {
		global $wpdb;

		$topics_table = $wpdb->prefix . 'partyminder_conversation_topics';

		// Check if we already have topics
		$existing_count = $wpdb->get_var( "SELECT COUNT(*) FROM $topics_table" );
		if ( $existing_count > 0 ) {
			return;
		}

		$default_topics = array(
			array(
				'name'        => __( 'General Discussion', 'partyminder' ),
				'slug'        => 'general',
				'description' => __( 'General party planning and event discussions', 'partyminder' ),
				'sort_order'  => 1,
			),
			array(
				'name'        => __( 'Event Planning', 'partyminder' ),
				'slug'        => 'planning',
				'description' => __( 'Tips, ideas, and help for planning amazing events', 'partyminder' ),
				'sort_order'  => 2,
			),
			array(
				'name'        => __( 'Food & Drinks', 'partyminder' ),
				'slug'        => 'food-drinks',
				'description' => __( 'Recipes, catering ideas, and beverage recommendations', 'partyminder' ),
				'sort_order'  => 3,
			),
			array(
				'name'        => __( 'Venues & Locations', 'partyminder' ),
				'slug'        => 'venues',
				'description' => __( 'Venue recommendations and location discussions', 'partyminder' ),
				'sort_order'  => 4,
			),
			array(
				'name'        => __( 'Entertainment', 'partyminder' ),
				'slug'        => 'entertainment',
				'description' => __( 'Music, games, activities, and entertainment ideas', 'partyminder' ),
				'sort_order'  => 5,
			),
		);

		foreach ( $default_topics as $topic ) {
			$wpdb->insert(
				$topics_table,
				$topic,
				array( '%s', '%s', '%s', '%d' )
			);
		}
	}

	private static function upgrade_database_schema() {
		// Existing upgrade logic stays the same...
		global $wpdb;

		// Add featured_image column to conversations if missing
		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM $conversations_table LIKE %s",
				'featured_image'
			)
		);

		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE $conversations_table ADD COLUMN featured_image varchar(255) DEFAULT '' AFTER last_reply_author" );
		}

		// Add community_id index if missing
		$events_table = $wpdb->prefix . 'partyminder_events';
		$index_exists = $wpdb->get_results( "SHOW INDEX FROM $events_table WHERE Key_name = 'community_id'" );
		if ( empty( $index_exists ) ) {
			$wpdb->query( "ALTER TABLE $events_table ADD INDEX community_id (community_id)" );
		}
	}

	private static function migrate_privacy_inheritance() {
		global $wpdb;

		// Update conversations to inherit privacy from their parent event/community
		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$events_table = $wpdb->prefix . 'partyminder_events';
		$communities_table = $wpdb->prefix . 'partyminder_communities';

		// Inherit privacy from events
		$wpdb->query( "
			UPDATE $conversations_table c
			JOIN $events_table e ON c.event_id = e.id
			SET c.privacy = e.privacy
			WHERE c.event_id IS NOT NULL AND c.privacy = 'public'
		" );

		// Inherit privacy from communities
		$wpdb->query( "
			UPDATE $conversations_table c
			JOIN $communities_table com ON c.community_id = com.id
			SET c.privacy = com.visibility
			WHERE c.community_id IS NOT NULL AND c.privacy = 'public'
		" );
	}

	/**
	 * Search index table
	 */
	private static function create_search_table() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		$search_table = $wpdb->prefix . 'partyminder_search';
		
		$search_sql = "CREATE TABLE $search_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			entity_type varchar(20) NOT NULL,
			entity_id bigint(20) UNSIGNED NOT NULL,
			title varchar(255) NOT NULL,
			content mediumtext NOT NULL,
			url varchar(255) NOT NULL,
			owner_user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			community_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			event_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			visibility_scope varchar(20) NOT NULL DEFAULT 'public',
			last_activity_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY entity_unique (entity_type, entity_id),
			KEY entity_type_idx (entity_type),
			KEY community_idx (community_id),
			KEY event_idx (event_id),
			KEY visibility_idx (visibility_scope),
			KEY owner_idx (owner_user_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $search_sql );
		
		// Add FULLTEXT index after table creation for better compatibility
		$wpdb->query( "ALTER TABLE $search_table ADD FULLTEXT KEY ft_search (title, content)" );
	}

	/**
	 * Index existing content for search functionality
	 * Called during plugin activation to populate search table
	 */
	private static function index_existing_content() {
		// Only run if we have the search indexer available
		if ( ! class_exists( 'PartyMinder_Search_Indexer' ) ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-search-indexer.php';
		}

		if ( ! class_exists( 'PartyMinder_Search_Indexer_Init' ) ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-search-indexer-init.php';
		}

		try {
			// Use a reasonable timeout to prevent activation failures
			set_time_limit( 300 ); // 5 minutes max
			
			// Index all existing content
			$indexed_count = PartyMinder_Search_Indexer_Init::index_all_content();
			
			// Log success for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "PartyMinder: Search indexing completed during activation. Indexed $indexed_count items." );
			}
			
		} catch ( Exception $e ) {
			// Don't let search indexing break plugin activation
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PartyMinder: Search indexing failed during activation: ' . $e->getMessage() );
			}
		}
	}

}