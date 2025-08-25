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
		
		$event_clause        = $exclude_event_conversations ? 'AND c.event_id IS NULL' : '';
		$community_clause    = $exclude_community_conversations ? 'AND c.community_id IS NULL' : '';
		$current_user_id = get_current_user_id();
		
		// Build privacy filter for conversations
		$privacy_filter = $this->build_conversation_privacy_filter( $current_user_id );

		return $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT c.*, e.title as event_title, e.slug as event_slug, cm.name as community_name, cm.slug as community_slug
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
            SELECT c.*, NULL as event_title, NULL as event_slug, NULL as community_name, NULL as community_slug
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
				'content'           => $data['content'],
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
				'content'         => $data['content'],
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
		
		if ( ! $current_user_id || ! is_user_logged_in() ) {
			// Non-logged in users can only see conversations from public events/communities
			return "(
				(c.event_id IS NULL AND c.community_id IS NULL AND c.privacy = 'public') OR
				(c.event_id IS NOT NULL AND e.privacy = 'public') OR
				(c.community_id IS NOT NULL AND cm.privacy = 'public')
			)";
		}
		
		$current_user = wp_get_current_user();
		$user_email = $current_user->user_email;
		
		return "(
			(c.event_id IS NULL AND c.community_id IS NULL AND (
				c.privacy = 'public' OR
				c.author_id = $current_user_id
			)) OR
			(c.event_id IS NOT NULL AND (
				e.privacy = 'public' OR
				e.author_id = $current_user_id OR
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
				EXISTS(
					SELECT 1 FROM $members_table m 
					WHERE m.community_id = cm.id AND m.user_id = $current_user_id 
					AND m.status = 'active'
				)
			))
		)";
	}

	/**
	 * Validate conversation privacy setting and implement inheritance
	 */
	private function validate_conversation_privacy( $privacy, $data ) {
		// If conversation is tied to an event or community, inherit their privacy
		if ( ! empty( $data['event_id'] ) ) {
			return $this->get_event_privacy( $data['event_id'] );
		}
		
		if ( ! empty( $data['community_id'] ) ) {
			return $this->get_community_privacy( $data['community_id'] );
		}
		
		// For standalone conversations, validate the provided privacy
		$allowed_privacy_settings = array( 'public', 'friends', 'members' );
		
		$privacy = sanitize_text_field( $privacy );
		
		if ( ! in_array( $privacy, $allowed_privacy_settings ) ) {
			return 'public'; // Default to public if invalid
		}
		
		return $privacy;
	}

	/**
	 * Get effective privacy for an event
	 */
	private function get_event_privacy( $event_id ) {
		global $wpdb;
		
		$events_table = $wpdb->prefix . 'partyminder_events';
		$event = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT privacy, community_id FROM $events_table WHERE id = %d",
				$event_id
			)
		);
		
		if ( ! $event ) {
			return 'public';
		}
		
		// If event is part of a community, it inherits community privacy
		if ( $event->community_id ) {
			return $this->get_community_privacy( $event->community_id );
		}
		
		return $event->privacy;
	}

	/**
	 * Get effective privacy for a community
	 */
	private function get_community_privacy( $community_id ) {
		global $wpdb;
		
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$privacy = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT privacy FROM $communities_table WHERE id = %d",
				$community_id
			)
		);
		
		return $privacy ?: 'public';
	}

	/**
	 * Get the effective privacy for a conversation (resolving inheritance)
	 */
	public function get_conversation_privacy( $conversation ) {
		if ( $conversation->event_id ) {
			return $this->get_event_privacy( $conversation->event_id );
		}
		
		if ( $conversation->community_id ) {
			return $this->get_community_privacy( $conversation->community_id );
		}
		
		return $conversation->privacy;
	}

	/**
	 * Get conversations created by a specific user
	 */
	public function get_user_conversations( $user_id, $limit = 10 ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';

		return $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT c.*, e.title as event_title, e.slug as event_slug, cm.name as community_name, cm.slug as community_slug
            FROM $conversations_table c
            LEFT JOIN {$wpdb->prefix}partyminder_events e ON c.event_id = e.id
            LEFT JOIN {$wpdb->prefix}partyminder_communities cm ON c.community_id = cm.id
            WHERE c.author_id = %d
            ORDER BY c.created_at DESC
            LIMIT %d
        ",
				$user_id,
				$limit
			)
		);
	}

	/**
	 * Generate a contextual display title for conversations
	 * @param object $conversation The conversation object
	 * @param bool $show_context Whether to show event/community context (default: true)
	 */
	public function get_display_title( $conversation, $show_context = true ) {
		$title = $conversation->title;
		
		if ( ! $show_context ) {
			return $title;
		}
		
		if ( ! empty( $conversation->event_title ) ) {
			return $conversation->event_title . ': ' . $title;
		}
		
		if ( ! empty( $conversation->community_name ) ) {
			return $conversation->community_name . ': ' . $title;
		}
		
		return $title;
	}

	/**
	 * Get conversations filtered by circle scope
	 */
	public function get_conversations_by_scope( $scope, $topic_slug = '', $page = 1, $per_page = 20 ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$events_table = $wpdb->prefix . 'partyminder_events';
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		
		$offset = ( $page - 1 ) * $per_page;
		
		// Build WHERE clause for scope filtering
		$where_conditions = array();
		
		// Include conversations by users in scope
		if ( ! empty( $scope['users'] ) ) {
			$user_ids_in = implode( ',', array_map( 'intval', $scope['users'] ) );
			$where_conditions[] = "c.author_id IN ($user_ids_in)";
		}
		
		// Include conversations in communities in scope
		if ( ! empty( $scope['communities'] ) ) {
			$community_ids_in = implode( ',', array_map( 'intval', $scope['communities'] ) );
			$where_conditions[] = "c.community_id IN ($community_ids_in)";
		}
		
		// If no scope conditions, return empty (shouldn't happen)
		if ( empty( $where_conditions ) ) {
			return array();
		}
		
		$where_clause = '(' . implode( ' OR ', $where_conditions ) . ')';
		
		// Add topic filter if specified
		if ( $topic_slug ) {
			// For now, we don't have topic filtering implemented
			// This would be added when topic system is built
		}
		
		$query = $wpdb->prepare(
			"SELECT c.*, e.title as event_title, e.slug as event_slug, cm.name as community_name, cm.slug as community_slug
			 FROM $conversations_table c
			 LEFT JOIN $events_table e ON c.event_id = e.id
			 LEFT JOIN $communities_table cm ON c.community_id = cm.id
			 WHERE $where_clause
			 ORDER BY c.last_reply_date DESC
			 LIMIT %d OFFSET %d",
			$per_page,
			$offset
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * Get count of conversations in scope
	 */
	public function get_conversations_count_by_scope( $scope, $topic_slug = '' ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		
		// Build WHERE clause for scope filtering
		$where_conditions = array();
		
		// Include conversations by users in scope
		if ( ! empty( $scope['users'] ) ) {
			$user_ids_in = implode( ',', array_map( 'intval', $scope['users'] ) );
			$where_conditions[] = "author_id IN ($user_ids_in)";
		}
		
		// Include conversations in communities in scope
		if ( ! empty( $scope['communities'] ) ) {
			$community_ids_in = implode( ',', array_map( 'intval', $scope['communities'] ) );
			$where_conditions[] = "community_id IN ($community_ids_in)";
		}
		
		// If no scope conditions, return 0
		if ( empty( $where_conditions ) ) {
			return 0;
		}
		
		$where_clause = '(' . implode( ' OR ', $where_conditions ) . ')';
		
		// Add topic filter if specified
		if ( $topic_slug ) {
			// For now, we don't have topic filtering implemented
		}
		
		return intval( $wpdb->get_var(
			"SELECT COUNT(*) FROM $conversations_table WHERE $where_clause"
		) );
	}

	/**
	 * Process content for URL embeds using new pm_embed system
	 */
	public function process_content_embeds( $content ) {
		if ( empty( $content ) ) {
			return '';
		}

		// Store original content for URL detection
		$original_content = $content;
		
		// First apply wpautop to handle paragraphs
		$content = wpautop( $content );
		
		// Check for URLs and add embed cards
		$url = pm_first_url_in_text( $original_content );
		if ( $url ) {
			$embed = pm_build_embed_from_url( $url );
			if ( $embed ) {
				// Add the embed card after the content
				$content .= pm_render_embed_card( $embed );
			}
		}
		
		// If no custom embed was added, try WordPress's built-in embed functionality
		if ( ! $embed && $url ) {
			global $wp_embed;
			if ( isset( $wp_embed ) && is_object( $wp_embed ) ) {
				$content = $wp_embed->autoembed( $content );
				$content = $wp_embed->run_shortcode( $content );
			}
		}
		
		// Sanitize content but allow embeds
		$allowed_html = wp_kses_allowed_html( 'post' );
		
		// Add iframe support for WordPress embeds
		$allowed_html['iframe'] = array(
			'src' => true,
			'width' => true,
			'height' => true,
			'frameborder' => true,
			'allowfullscreen' => true,
			'allow' => true,
			'referrerpolicy' => true,
			'title' => true,
			'class' => true,
			'sandbox' => true,
			'security' => true,
			'style' => true,
			'marginwidth' => true,
			'marginheight' => true,
			'scrolling' => true,
			'data-secret' => true,
		);
		
		// Add blockquote support for embeds
		$allowed_html['blockquote']['class'] = true;
		$allowed_html['blockquote']['data-secret'] = true;
		
		// Add custom embed HTML support
		$allowed_html['div']['class'] = true;
		$allowed_html['div']['data-pm-source'] = true;
		$allowed_html['img']['loading'] = true;
		$allowed_html['img']['decoding'] = true;
		$allowed_html['img']['src'] = true;
		$allowed_html['img']['alt'] = true;
		$allowed_html['a']['target'] = true;
		$allowed_html['a']['rel'] = true;
		
		return wp_kses( $content, $allowed_html );
	}



	/**
	 * Delete a reply from a conversation
	 */
	public function delete_reply( $reply_id ) {
		global $wpdb;

		$replies_table = $wpdb->prefix . 'partyminder_conversation_replies';
		$conversations_table = $wpdb->prefix . 'partyminder_conversations';

		// Get reply data first for permission checking and conversation updates
		$reply = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $replies_table WHERE id = %d",
				$reply_id
			)
		);

		if ( ! $reply ) {
			return false; // Reply not found
		}

		// Check permissions
		$current_user = wp_get_current_user();
		if ( ! is_user_logged_in() ) {
			return false; // Must be logged in
		}

		// User can delete if they are the author or an admin
		$can_delete = ( $current_user->ID == $reply->author_id ) || current_user_can( 'manage_options' );
		if ( ! $can_delete ) {
			return false; // Not authorized
		}

		// Delete the reply
		$result = $wpdb->delete(
			$replies_table,
			array( 'id' => $reply_id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return false; // Delete failed
		}

		// Update conversation reply count and last reply info
		$conversation_id = $reply->conversation_id;
		
		// Get updated reply count
		$new_reply_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $replies_table WHERE conversation_id = %d",
				$conversation_id
			)
		);

		// Get last reply info
		$last_reply = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT created_at, author_name FROM $replies_table 
				 WHERE conversation_id = %d 
				 ORDER BY created_at DESC 
				 LIMIT 1",
				$conversation_id
			)
		);

		// Update conversation
		if ( $last_reply ) {
			// There are still replies
			$wpdb->update(
				$conversations_table,
				array(
					'reply_count' => $new_reply_count,
					'last_reply_date' => $last_reply->created_at,
					'last_reply_author' => $last_reply->author_name,
				),
				array( 'id' => $conversation_id ),
				array( '%d', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// No more replies, use conversation creation date
			$conversation = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT created_at, author_name FROM $conversations_table WHERE id = %d",
					$conversation_id
				)
			);
			
			$wpdb->update(
				$conversations_table,
				array(
					'reply_count' => 0,
					'last_reply_date' => $conversation->created_at,
					'last_reply_author' => $conversation->author_name,
				),
				array( 'id' => $conversation_id ),
				array( '%d', '%s', '%s' ),
				array( '%d' )
			);
		}

		return true;
	}

	/**
	 * Get conversation by ID
	 */
	public function get_conversation_by_id( $conversation_id ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $conversations_table WHERE id = %d",
				$conversation_id
			)
		);
	}

	/**
	 * Update conversation
	 */
	public function update_conversation( $conversation_id, $update_data ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';

		// Validate required fields
		if ( empty( $update_data['title'] ) || empty( $update_data['content'] ) ) {
			return new WP_Error( 'missing_data', __( 'Title and content are required', 'partyminder' ) );
		}

		// Prepare update data
		$data = array(
			'title' => sanitize_text_field( $update_data['title'] ),
			'content' => wp_kses_post( $update_data['content'] ),
		);

		$formats = array( '%s', '%s' );

		// Only update privacy if provided (for standalone conversations)
		if ( isset( $update_data['privacy'] ) ) {
			$data['privacy'] = $this->validate_privacy_setting( $update_data['privacy'] );
			$formats[] = '%s';
		}

		$result = $wpdb->update(
			$conversations_table,
			$data,
			array( 'id' => $conversation_id ),
			$formats,
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'update_failed', __( 'Failed to update conversation', 'partyminder' ) );
		}

		return $conversation_id;
	}

	/**
	 * Delete conversation and all related data
	 */
	public function delete_conversation( $conversation_id ) {
		global $wpdb;

		// Get conversation first
		$conversation = $this->get_conversation_by_id( $conversation_id );
		if ( ! $conversation ) {
			return new WP_Error( 'conversation_not_found', __( 'Conversation not found', 'partyminder' ) );
		}

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$replies_table = $wpdb->prefix . 'partyminder_conversation_replies';

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Delete all replies first
			$wpdb->delete( $replies_table, array( 'conversation_id' => $conversation_id ), array( '%d' ) );

			// Delete conversation followers (if table exists)
			$followers_table = $wpdb->prefix . 'partyminder_conversation_followers';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$followers_table'" ) == $followers_table ) {
				$wpdb->delete( $followers_table, array( 'conversation_id' => $conversation_id ), array( '%d' ) );
			}

			// Delete the conversation itself
			$result = $wpdb->delete( $conversations_table, array( 'id' => $conversation_id ), array( '%d' ) );

			if ( $result === false ) {
				throw new Exception( __( 'Failed to delete conversation', 'partyminder' ) );
			}

			// Delete any cover image meta
			delete_post_meta( $conversation_id, 'cover_image' );

			// Commit transaction
			$wpdb->query( 'COMMIT' );

			return true;

		} catch ( Exception $e ) {
			// Rollback transaction on error
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'deletion_failed', $e->getMessage() );
		}
	}

	/**
	 * Validate privacy setting for standalone conversations
	 */
	private function validate_privacy_setting( $privacy ) {
		$allowed_privacy_settings = array( 'public', 'friends', 'members' );
		
		$privacy = sanitize_text_field( $privacy );
		
		if ( ! in_array( $privacy, $allowed_privacy_settings ) ) {
			return 'public'; // Default to public if invalid
		}
		
		return $privacy;
	}
}