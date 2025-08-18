<?php

class PartyMinder_Conversation_Manager {

	public function __construct() {
		// Constructor can be used for initialization if needed
	}

	/**
	 * Get recent conversations across all types
	 */
	public function get_recent_conversations( $limit = 10, $exclude_event_conversations = false, $exclude_community_conversations = false ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$events_table = $wpdb->prefix . 'partyminder_events';
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
		
		$event_clause        = $exclude_event_conversations ? 'AND c.event_id IS NULL' : '';
		$community_clause    = $exclude_community_conversations ? 'AND c.community_id IS NULL' : '';
		$current_user_id = get_current_user_id();
		
		// Build privacy filter for conversations
		$privacy_filter = $this->build_conversation_privacy_filter( $current_user_id );

		return $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT c.*
            FROM $conversations_table c
            LEFT JOIN $events_table e ON c.event_id = e.id
            LEFT JOIN $communities_table cm ON c.community_id = cm.id
            WHERE ($privacy_filter) $event_clause $community_clause
            ORDER BY c.last_reply_date DESC
            LIMIT %d
        ",
				$limit
			)
		);
	}

	/**
	 * Get event-related conversations
	 */
	public function get_event_conversations( $event_id = null, $limit = 10 ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$events_table        = $wpdb->prefix . 'partyminder_events';

		$where_clause   = $event_id ? 'WHERE c.event_id = %d' : 'WHERE c.event_id IS NOT NULL';
		$prepare_values = $event_id ? array( $event_id, $limit ) : array( $limit );

		return $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT DISTINCT c.*, e.title as event_title, e.slug as event_slug, e.event_date
            FROM $conversations_table c
            LEFT JOIN $events_table e ON c.event_id = e.id
            $where_clause
            ORDER BY c.last_reply_date DESC
            LIMIT %d
        ",
				...$prepare_values
			)
		);
	}

	/**
	 * Get community-related conversations
	 */
	public function get_community_conversations( $community_id = null, $limit = 10 ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$communities_table   = $wpdb->prefix . 'partyminder_communities';

		$where_clause   = $community_id ? 'WHERE c.community_id = %d' : 'WHERE c.community_id IS NOT NULL';
		$prepare_values = $community_id ? array( $community_id, $limit ) : array( $limit );

		return $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT DISTINCT c.*, cm.name as community_name, cm.slug as community_slug
            FROM $conversations_table c
            LEFT JOIN $communities_table cm ON c.community_id = cm.id
            $where_clause
            ORDER BY c.last_reply_date DESC
            LIMIT %d
        ",
				...$prepare_values
			)
		);
	}

	/**
	 * Get general conversations (not tied to events or communities)
	 */
	public function get_general_conversations( $limit = 10 ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$current_user_id = get_current_user_id();
		
		// Build privacy filter for conversations
		$privacy_filter = $this->build_conversation_privacy_filter( $current_user_id );

		return $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT c.*
            FROM $conversations_table c
            WHERE c.event_id IS NULL AND c.community_id IS NULL
            AND ($privacy_filter)
            ORDER BY c.last_reply_date DESC
            LIMIT %d
        ",
				$limit
			)
		);
	}

	/**
	 * Create a new conversation
	 */
	public function create_conversation( $data ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';

		// Generate slug from title
		$slug = $this->generate_conversation_slug( $data['title'] );

		$result = $wpdb->insert(
			$conversations_table,
			array(
				'event_id'          => $data['event_id'] ?? null,
				'community_id'      => $data['community_id'] ?? null,
				'title'             => sanitize_text_field( $data['title'] ),
				'slug'              => $slug,
				'content'           => wp_kses_post( $data['content'] ),
				'author_id'         => $data['author_id'],
				'author_name'       => sanitize_text_field( $data['author_name'] ),
				'author_email'      => sanitize_email( $data['author_email'] ),
				'privacy'           => $this->validate_conversation_privacy( $data['privacy'] ?? 'public', $data ),
				'is_pinned'         => $data['is_pinned'] ?? 0,
				'created_at'        => current_time( 'mysql' ),
				'last_reply_date'   => current_time( 'mysql' ),
				'last_reply_author' => sanitize_text_field( $data['author_name'] ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( $result === false ) {
			return false;
		}

		$conversation_id = $wpdb->insert_id;

		// Auto-follow the conversation creator
		$this->follow_conversation( $conversation_id, $data['author_id'], $data['author_email'] );

		return $conversation_id;
	}

	/**
	 * Add a reply to a conversation
	 */
	public function add_reply( $conversation_id, $data ) {
		global $wpdb;

		$replies_table       = $wpdb->prefix . 'partyminder_conversation_replies';
		$conversations_table = $wpdb->prefix . 'partyminder_conversations';

		// Calculate depth level
		$depth = 0;
		if ( ! empty( $data['parent_reply_id'] ) ) {
			$parent = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT depth_level FROM $replies_table WHERE id = %d",
					$data['parent_reply_id']
				)
			);
			$depth  = $parent ? ( $parent->depth_level + 1 ) : 0;
			$depth  = min( $depth, 5 ); // Max depth of 5 levels
		}

		// Insert reply
		$result = $wpdb->insert(
			$replies_table,
			array(
				'conversation_id' => $conversation_id,
				'parent_reply_id' => $data['parent_reply_id'] ?? null,
				'content'         => wp_kses_post( $data['content'] ),
				'author_id'       => $data['author_id'],
				'author_name'     => sanitize_text_field( $data['author_name'] ),
				'author_email'    => sanitize_email( $data['author_email'] ),
				'depth_level'     => $depth,
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s' )
		);

		if ( $result === false ) {
			return false;
		}

		// Update conversation reply count and last reply info
		$wpdb->update(
			$conversations_table,
			array(
				'reply_count'       => $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM $replies_table WHERE conversation_id = %d",
						$conversation_id
					)
				),
				'last_reply_date'   => current_time( 'mysql' ),
				'last_reply_author' => sanitize_text_field( $data['author_name'] ),
			),
			array( 'id' => $conversation_id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);

		$reply_id = $wpdb->insert_id;

		// Auto-follow the conversation for reply author
		$this->follow_conversation( $conversation_id, $data['author_id'], $data['author_email'] );

		return $reply_id;
	}

	/**
	 * Get conversation by ID or slug
	 */
	public function get_conversation( $identifier, $by_slug = false ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$field               = $by_slug ? 'slug' : 'id';

		$conversation = $wpdb->get_row(
			$wpdb->prepare(
				"
            SELECT c.*
            FROM $conversations_table c
            WHERE c.$field = %s
        ",
				$identifier
			)
		);

		if ( $conversation && $by_slug === false ) {
			// Get replies if getting by ID
			$conversation->replies = $this->get_conversation_replies( $conversation->id );
		}

		return $conversation;
	}

	/**
	 * Get replies for a conversation
	 */
	public function get_conversation_replies( $conversation_id ) {
		global $wpdb;

		$replies_table = $wpdb->prefix . 'partyminder_conversation_replies';

		return $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT * FROM $replies_table 
            WHERE conversation_id = %d 
            ORDER BY created_at ASC
        ",
				$conversation_id
			)
		);
	}

	/**
	 * Follow a conversation
	 */
	public function follow_conversation( $conversation_id, $user_id, $email ) {
		global $wpdb;

		$follows_table = $wpdb->prefix . 'partyminder_conversation_follows';

		// Check if already following
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"
            SELECT id FROM $follows_table 
            WHERE conversation_id = %d AND user_id = %d AND email = %s
        ",
				$conversation_id,
				$user_id,
				$email
			)
		);

		if ( $existing ) {
			return $existing; // Already following
		}

		$result = $wpdb->insert(
			$follows_table,
			array(
				'conversation_id'        => $conversation_id,
				'user_id'                => $user_id,
				'email'                  => $email,
				'last_read_at'           => current_time( 'mysql' ),
				'notification_frequency' => 'immediate',
				'created_at'             => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Unfollow a conversation
	 */
	public function unfollow_conversation( $conversation_id, $user_id, $email ) {
		global $wpdb;

		$follows_table = $wpdb->prefix . 'partyminder_conversation_follows';

		return $wpdb->delete(
			$follows_table,
			array(
				'conversation_id' => $conversation_id,
				'user_id'         => $user_id,
				'email'           => $email,
			),
			array( '%d', '%d', '%s' )
		);
	}

	/**
	 * Check if user is following a conversation
	 */
	public function is_following( $conversation_id, $user_id, $email ) {
		global $wpdb;

		$follows_table = $wpdb->prefix . 'partyminder_conversation_follows';

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"
            SELECT id FROM $follows_table 
            WHERE conversation_id = %d AND user_id = %d AND email = %s
        ",
				$conversation_id,
				$user_id,
				$email
			)
		);
	}

	/**
	 * Generate unique slug for conversation
	 */
	private function generate_conversation_slug( $title ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$base_slug           = sanitize_title( $title );
		$slug                = $base_slug;
		$counter             = 1;

		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $conversations_table WHERE slug = %s", $slug ) ) ) {
			$slug = $base_slug . '-' . $counter;
			++$counter;
		}

		return $slug;
	}

	/**
	 * Get conversation statistics
	 */
	public function get_stats() {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$replies_table       = $wpdb->prefix . 'partyminder_conversation_replies';
		$follows_table       = $wpdb->prefix . 'partyminder_conversation_follows';

		$stats                       = new stdClass();
		$stats->total_conversations  = $wpdb->get_var( "SELECT COUNT(*) FROM $conversations_table" );
		$stats->total_replies        = $wpdb->get_var( "SELECT COUNT(*) FROM $replies_table" );
		$stats->total_follows        = $wpdb->get_var( "SELECT COUNT(*) FROM $follows_table" );
		$stats->active_conversations = $wpdb->get_var(
			"
            SELECT COUNT(*) FROM $conversations_table 
            WHERE last_reply_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        "
		);

		return $stats;
	}

	/**
	 * Auto-create event conversation when event is created
	 */
	public function create_event_conversation( $event_id, $event_data ) {
		$conversation_data = array(
			'event_id'     => $event_id,
			'title'        => sprintf( __( 'Planning: %s', 'partyminder' ), $event_data['title'] ),
			'content'      => sprintf(
				__( 'Let\'s plan an amazing %s together! Share ideas, coordinate details, and help make this event unforgettable.', 'partyminder' ),
				$event_data['title']
			),
			'author_id'    => $event_data['author_id'],
			'author_name'  => $event_data['author_name'],
			'author_email' => $event_data['author_email'],
		);

		return $this->create_conversation( $conversation_data );
	}

	/**
	 * Auto-create community conversation when community is created
	 */
	public function create_community_conversation( $community_id, $community_data ) {
		$conversation_data = array(
			'community_id' => $community_id,
			'title'        => sprintf( __( 'Welcome to %s!', 'partyminder' ), $community_data['name'] ),
			'content'      => sprintf(
				__( 'Welcome to the %s community! This is our gathering place to connect, share experiences, and plan amazing events together. Please introduce yourself and let us know what brings you here!', 'partyminder' ),
				$community_data['name']
			),
			'author_id'    => $community_data['creator_id'],
			'author_name'  => $community_data['creator_name'],
			'author_email' => $community_data['creator_email'],
			'is_pinned'    => 1, // Pin the welcome conversation
		);

		return $this->create_conversation( $conversation_data );
	}

	/**
	 * Build privacy filter for conversations based on parent event/community privacy
	 */
	private function build_conversation_privacy_filter( $current_user_id ) {
		global $wpdb;
		
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
		
		if ( ! $current_user_id || ! is_user_logged_in() ) {
			// Non-logged in users can only see conversations from public events/communities
			return "(
				(c.event_id IS NULL AND c.community_id IS NULL) OR
				(c.event_id IS NOT NULL AND e.privacy = 'public') OR
				(c.community_id IS NOT NULL AND cm.privacy = 'public')
			)";
		}
		
		$current_user = wp_get_current_user();
		$user_email = $current_user->user_email;
		
		return "(
			(c.event_id IS NULL AND c.community_id IS NULL AND (
				c.privacy = 'public' OR
				c.author_id = $current_user_id OR
				(c.privacy = 'friends' AND c.author_id = $current_user_id) OR
				(c.privacy = 'members' AND $current_user_id > 0)
			)) OR
			(c.event_id IS NOT NULL AND (
				e.privacy = 'public' OR
				e.author_id = $current_user_id OR
				(e.privacy = 'friends' AND e.author_id = $current_user_id) OR
				(e.privacy = 'community' AND EXISTS(
					SELECT 1 FROM $members_table cm1, $members_table cm2 
					WHERE cm1.user_id = e.author_id AND cm2.user_id = $current_user_id 
					AND cm1.community_id = cm2.community_id 
					AND cm1.status = 'active' AND cm2.status = 'active'
				)) OR
				(e.privacy = 'private' AND EXISTS(
					SELECT 1 FROM $guests_table g 
					WHERE g.event_id = e.id AND g.email = '$user_email'
				)) OR
				(e.privacy = 'private' AND EXISTS(
					SELECT 1 FROM $invitations_table i 
					WHERE i.event_id = e.id AND i.invited_email = '$user_email' 
					AND i.status = 'pending' AND i.expires_at > NOW()
				))
			)) OR
			(c.community_id IS NOT NULL AND (
				cm.privacy = 'public' OR
				cm.creator_id = $current_user_id OR
				(cm.privacy = 'friends' AND EXISTS(
					SELECT 1 FROM $members_table cm1, $members_table cm2 
					WHERE cm1.user_id = cm.creator_id AND cm2.user_id = $current_user_id 
					AND cm1.community_id = cm2.community_id 
					AND cm1.status = 'active' AND cm2.status = 'active'
				)) OR
				EXISTS(
					SELECT 1 FROM $members_table m 
					WHERE m.community_id = cm.id AND m.user_id = $current_user_id 
					AND m.status = 'active'
				)
			))
		)";
	}

	/**
	 * Validate conversation privacy setting
	 */
	private function validate_conversation_privacy( $privacy, $data ) {
		// If conversation is tied to an event or community, it inherits their privacy
		if ( ! empty( $data['event_id'] ) || ! empty( $data['community_id'] ) ) {
			return 'inherit'; // Special value indicating inherited privacy
		}
		
		$allowed_privacy_settings = array( 'public', 'friends', 'members' );
		
		$privacy = sanitize_text_field( $privacy );
		
		if ( ! in_array( $privacy, $allowed_privacy_settings ) ) {
			return 'public'; // Default to public if invalid
		}
		
		return $privacy;
	}
}