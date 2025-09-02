<?php

/**
 * Conversation Feed - Step 7 Implementation
 * 
 * Build filtered conversation feeds based on circles and permission gates
 */
class PartyMinder_Conversation_Feed {

	/**
	 * Get conversation feed list for a viewer
	 * 
	 * @param int $viewer_id The user ID viewing the feed
	 * @param string $circle Circle filter: 'inner', 'trusted', 'extended', or 'all'
	 * @param array $opts Options: page, per_page, include_general, etc.
	 * @return array Feed data with conversations, pagination, and metadata
	 */
	public static function list( $viewer_id, $circle = 'all', $opts = array() ) {

		$viewer_id = intval( $viewer_id );
		if ( ! $viewer_id ) {
			return self::get_empty_feed( 'Invalid viewer' );
		}

		// Parse options
		$options = wp_parse_args( $opts, array(
			'page' => 1,
			'per_page' => 20,
			'include_general' => true,
			'include_event_conversations' => true
		) );

		$start_time = microtime( true );

		// Get circles data
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-circles-resolver.php';
		$circles = PartyMinder_Circles_Resolver::creator_sets( $viewer_id );

		// Get creator IDs based on circle filter
		$creator_ids = self::get_creator_ids_for_circle( $circles, $circle );
		
		if ( empty( $creator_ids ) ) {
			return self::get_empty_feed( 'No creators in selected circle' );
		}

		// Build and execute the feed query
		$feed_data = self::execute_feed_query( $viewer_id, $creator_ids, $options );
		
		// Add circle classification to each conversation
		$feed_data['conversations'] = self::add_visibility_markers( 
			$feed_data['conversations'], 
			$circles, 
			$viewer_id 
		);

		// Add performance metrics
		$calculation_time = microtime( true ) - $start_time;
		$feed_data['meta']['performance'] = array(
			'calculation_time' => round( $calculation_time * 1000, 2 ) . 'ms',
			'circle' => $circle,
			'creator_count' => count( $creator_ids ),
			'circles_cache' => isset( $circles['metrics'] ) ? 'hit' : 'miss'
		);

		return $feed_data;
	}

	/**
	 * Get creator IDs for the specified circle
	 */
	private static function get_creator_ids_for_circle( $circles, $circle ) {
		switch ( $circle ) {
			case 'inner':
				return $circles['inner']['creators'];
			case 'trusted':
				return array_unique( array_merge( 
					$circles['inner']['creators'], 
					$circles['trusted']['creators'] 
				) );
			case 'extended':
				return array_unique( array_merge( 
					$circles['inner']['creators'], 
					$circles['trusted']['creators'], 
					$circles['extended']['creators'] 
				) );
			case 'all':
			default:
				return array_unique( array_merge( 
					$circles['inner']['creators'], 
					$circles['trusted']['creators'], 
					$circles['extended']['creators'] 
				) );
		}
	}

	/**
	 * Execute the main feed query with permission gates
	 */
	private static function execute_feed_query( $viewer_id, $creator_ids, $options ) {
		global $wpdb;

		$conversations_table = $wpdb->prefix . 'partyminder_conversations';
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		$replies_table = $wpdb->prefix . 'partyminder_conversation_replies';
		$events_table = $wpdb->prefix . 'partyminder_events';

		// Build creator filter
		$creator_placeholders = implode( ',', array_fill( 0, count( $creator_ids ), '%d' ) );

		// Build permission gates subquery
		$permission_gates = "
			(
				-- Public communities
				(com.visibility = 'public')
				OR
				-- User is a member of the community
				EXISTS (
					SELECT 1 FROM $members_table mem 
					WHERE mem.community_id = com.id 
					AND mem.user_id = %d 
					AND mem.status = 'active'
				)
				OR
				-- General conversations (no community)
				(conv.community_id IS NULL)
			)
		";

		// Simplified main query - will add reply data in a second query for performance
		$query = "
			SELECT 
				conv.*,
				com.name as community_name,
				com.slug as community_slug,
				com.visibility as community_visibility,
				com.creator_id as community_creator_id,
				com.personal_owner_user_id,
				ev.title as event_title,
				ev.slug as event_slug,
				conv.last_reply_date as latest_activity
			FROM $conversations_table conv
			LEFT JOIN $communities_table com ON conv.community_id = com.id
			LEFT JOIN $events_table ev ON conv.event_id = ev.id
			WHERE 
				-- Filter by creator circles
				(
					(com.creator_id IN ($creator_placeholders))
					OR 
					-- Include general conversations from creators in circles
					(conv.community_id IS NULL AND conv.author_id IN ($creator_placeholders))
				)
				AND
				-- Apply permission gates
				$permission_gates
			ORDER BY COALESCE(conv.last_reply_date, conv.created_at) DESC
			LIMIT %d OFFSET %d
		";

		// Calculate offset
		$offset = ( $options['page'] - 1 ) * $options['per_page'];

		// Prepare parameters
		$params = array_merge(
			$creator_ids, // For community creators
			$creator_ids, // For general conversation authors
			array( $viewer_id ), // For permission gates
			array( $options['per_page'], $offset ) // For pagination
		);

		// Execute query
		$conversations = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );

		// Get total count for pagination
		$count_query = "
			SELECT COUNT(DISTINCT conv.id)
			FROM $conversations_table conv
			LEFT JOIN $communities_table com ON conv.community_id = com.id
			WHERE 
				(
					(com.creator_id IN ($creator_placeholders))
					OR 
					(conv.community_id IS NULL AND conv.author_id IN ($creator_placeholders))
				)
				AND
				$permission_gates
		";

		$total_count = $wpdb->get_var( $wpdb->prepare( 
			$count_query, 
			array_merge( $creator_ids, $creator_ids, array( $viewer_id ) )
		) );

		return array(
			'conversations' => $conversations ?: array(),
			'meta' => array(
				'page' => $options['page'],
				'per_page' => $options['per_page'],
				'total' => intval( $total_count ),
				'total_pages' => ceil( $total_count / $options['per_page'] ),
				'has_more' => ( $options['page'] * $options['per_page'] ) < $total_count
			)
		);
	}

	/**
	 * Add "why visible" markers for each conversation
	 */
	private static function add_visibility_markers( $conversations, $circles, $viewer_id ) {
		foreach ( $conversations as &$conversation ) {
			$conversation->why_visible = self::determine_visibility_reason(
				$conversation,
				$circles,
				$viewer_id
			);
		}
		return $conversations;
	}

	/**
	 * Determine why a conversation is visible to the viewer
	 */
	private static function determine_visibility_reason( $conversation, $circles, $viewer_id ) {
		// Check if it's the viewer's own content
		if ( $conversation->author_id == $viewer_id ) {
			return array(
				'reason' => 'own_content',
				'circle' => null,
				'description' => 'Your own conversation'
			);
		}

		// Check community visibility
		if ( $conversation->community_id ) {
			// Determine which circle the community creator belongs to
			$community_creator = intval( $conversation->community_creator_id );
			$circle = null;
			
			if ( in_array( $community_creator, $circles['inner']['creators'] ) ) {
				$circle = 'inner';
			} elseif ( in_array( $community_creator, $circles['trusted']['creators'] ) ) {
				$circle = 'trusted';
			} elseif ( in_array( $community_creator, $circles['extended']['creators'] ) ) {
				$circle = 'extended';
			}

			if ( $conversation->community_visibility === 'public' ) {
				return array(
					'reason' => 'public_community',
					'circle' => $circle,
					'description' => $circle ? "Public community from {$circle} circle" : 'Public community'
				);
			} else {
				return array(
					'reason' => 'member_access',
					'circle' => $circle,
					'description' => $circle ? "Member of {$circle} circle community" : 'Community member'
				);
			}
		} else {
			// General conversation - determine author's circle
			$author_id = intval( $conversation->author_id );
			$circle = null;
			
			if ( in_array( $author_id, $circles['inner']['creators'] ) ) {
				$circle = 'inner';
			} elseif ( in_array( $author_id, $circles['trusted']['creators'] ) ) {
				$circle = 'trusted';
			} elseif ( in_array( $author_id, $circles['extended']['creators'] ) ) {
				$circle = 'extended';
			}

			return array(
				'reason' => 'general_conversation',
				'circle' => $circle,
				'description' => $circle ? "General conversation from {$circle} circle" : 'General conversation'
			);
		}
	}

	/**
	 * Get empty feed structure
	 */
	private static function get_empty_feed( $reason = '' ) {
		return array(
			'conversations' => array(),
			'meta' => array(
				'page' => 1,
				'per_page' => 20,
				'total' => 0,
				'total_pages' => 0,
				'has_more' => false,
				'empty_reason' => $reason
			)
		);
	}
}