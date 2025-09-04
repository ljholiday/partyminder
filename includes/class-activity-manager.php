<?php

class PartyMinder_Activity_Manager {

	public function __construct() {
		// Constructor can be used for initialization if needed
	}

	/**
	 * Get user's recent activity across all systems
	 */
	public function get_user_activity( $user_id, $limit = 10, $types = array() ) {
		$activities = array();

		// Default to all activity types if none specified
		if ( empty( $types ) ) {
			$types = array( 'events', 'conversations', 'replies', 'rsvps' );
		}

		// Get event activities
		if ( in_array( 'events', $types ) ) {
			$activities = array_merge( $activities, $this->get_event_activities( $user_id ) );
		}

		// Get RSVP activities
		if ( in_array( 'rsvps', $types ) ) {
			$activities = array_merge( $activities, $this->get_rsvp_activities( $user_id ) );
		}

		// Get conversation activities
		if ( in_array( 'conversations', $types ) ) {
			$activities = array_merge( $activities, $this->get_conversation_activities( $user_id ) );
		}

		// Get reply activities
		if ( in_array( 'replies', $types ) ) {
			$activities = array_merge( $activities, $this->get_reply_activities( $user_id ) );
		}

		// Sort by activity date (most recent first)
		usort(
			$activities,
			function ( $a, $b ) {
				return strtotime( $b->activity_date ) - strtotime( $a->activity_date );
			}
		);

		// Return limited results
		return array_slice( $activities, 0, $limit );
	}

	/**
	 * Get community-wide recent activity (for dashboard, etc.)
	 */
	public function get_community_activity( $limit = 10, $types = array() ) {
		global $wpdb;
		$activities = array();

		// Default to public activity types
		if ( empty( $types ) ) {
			$types = array( 'events', 'conversations' );
		}

		// Get recent events created
		if ( in_array( 'events', $types ) ) {
			$events_table = $wpdb->prefix . 'partyminder_events';
			$events       = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT e.*, u.display_name as author_name,
                        'event_created' as activity_type, e.created_at as activity_date
                 FROM $events_table e
                 LEFT JOIN {$wpdb->prefix}users u ON e.author_id = u.ID
                 WHERE e.event_status = 'active'
                 ORDER BY e.created_at DESC
                 LIMIT %d",
					5
				)
			);
			$activities   = array_merge( $activities, $events );
		}

		// Get recent conversations
		if ( in_array( 'conversations', $types ) ) {
			$conversations_table = $wpdb->prefix . 'partyminder_conversations';

			$conversations = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT c.*,
                        'conversation_created' as activity_type, c.created_at as activity_date
                 FROM $conversations_table c
                 WHERE c.event_id IS NULL
                 ORDER BY c.created_at DESC
                 LIMIT %d",
					5
				)
			);
			$activities    = array_merge( $activities, $conversations );
		}

		// Sort and return
		usort(
			$activities,
			function ( $a, $b ) {
				return strtotime( $b->activity_date ) - strtotime( $a->activity_date );
			}
		);

		return array_slice( $activities, 0, $limit );
	}

	/**
	 * Get events created by user
	 */
	private function get_event_activities( $user_id ) {
		global $wpdb;
		$events_table = $wpdb->prefix . 'partyminder_events';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, 'event_created' as activity_type, e.created_at as activity_date 
             FROM $events_table e 
             WHERE e.author_id = %d AND e.event_status = 'active'
             ORDER BY e.created_at DESC 
             LIMIT 5",
				$user_id
			)
		);
	}

	/**
	 * Get RSVP activities by user
	 */
	private function get_rsvp_activities( $user_id ) {
		global $wpdb;
		$events_table = $wpdb->prefix . 'partyminder_events';
		$guests_table = $wpdb->prefix . 'partyminder_guests';

		// Get user email for RSVP lookup
		$user_data = get_userdata( $user_id );
		if ( ! $user_data ) {
			return array();
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, g.rsvp_status, 'event_rsvp' as activity_type, g.created_at as activity_date
             FROM $events_table e
             INNER JOIN $guests_table g ON e.id = g.event_id
             WHERE g.email = %s AND e.event_status = 'active' AND e.author_id != %d
             ORDER BY g.created_at DESC
             LIMIT 5",
				$user_data->user_email,
				$user_id
			)
		);
	}

	/**
	 * Get conversations started by user
	 */
	private function get_conversation_activities( $user_id ) {
		global $wpdb;
		$conversations_table = $wpdb->prefix . 'partyminder_conversations';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*,
                    'conversation_created' as activity_type, c.created_at as activity_date
             FROM $conversations_table c
             WHERE c.author_id = %d
             ORDER BY c.created_at DESC
             LIMIT 5",
				$user_id
			)
		);
	}

	/**
	 * Get replies made by user
	 */
	private function get_reply_activities( $user_id ) {
		global $wpdb;
		$replies_table       = $wpdb->prefix . 'partyminder_conversation_replies';
		$conversations_table = $wpdb->prefix . 'partyminder_conversations';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, c.title as conversation_title, c.slug as conversation_slug, 
                    'conversation_reply' as activity_type, r.created_at as activity_date
             FROM $replies_table r
             INNER JOIN $conversations_table c ON r.conversation_id = c.id
             WHERE r.author_id = %d
             ORDER BY r.created_at DESC
             LIMIT 5",
				$user_id
			)
		);
	}

	/**
	 * Get activity icon for display
	 */
	public function get_activity_icon( $activity ) {
		switch ( $activity->activity_type ) {
			case 'event_created':
				return '🎨';
			case 'event_rsvp':
				if ( isset( $activity->rsvp_status ) ) {
					return $activity->rsvp_status === 'yes' ? '✅' :
							( $activity->rsvp_status === 'maybe' ? '❓' : '❌' );
				}
				return '📅';
			case 'conversation_created':
				return '💬';
			case 'conversation_reply':
				return '💭';
			default:
				return '📝';
		}
	}

	/**
	 * Get activity description for display
	 */
	public function get_activity_description( $activity ) {
		switch ( $activity->activity_type ) {
			case 'event_created':
				return __( 'Created event', 'partyminder' );
			case 'event_rsvp':
				switch ( $activity->rsvp_status ) {
					case 'yes':
						return __( 'RSVP\'d Yes to', 'partyminder' );
					case 'maybe':
						return __( 'RSVP\'d Maybe to', 'partyminder' );
					case 'no':
						return __( 'RSVP\'d No to', 'partyminder' );
				}
				return __( 'RSVP\'d to', 'partyminder' );
			case 'conversation_created':
				return __( 'Started conversation', 'partyminder' );
			case 'conversation_reply':
				return __( 'Replied to', 'partyminder' );
			default:
				return __( 'Activity', 'partyminder' );
		}
	}

	/**
	 * Get activity link URL
	 */
	public function get_activity_link( $activity ) {
		switch ( $activity->activity_type ) {
			case 'event_created':
			case 'event_rsvp':
				return home_url( '/events/' . $activity->slug );
			case 'conversation_created':
				return home_url( '/conversations/' . $activity->slug );
			case 'conversation_reply':
				return home_url( '/conversations/' . $activity->conversation_slug );
			default:
				return '#';
		}
	}

	/**
	 * Get activity title for display
	 */
	public function get_activity_title( $activity ) {
		switch ( $activity->activity_type ) {
			case 'event_created':
			case 'event_rsvp':
				return $activity->title;
			case 'conversation_created':
				return $activity->title;
			case 'conversation_reply':
				return $activity->conversation_title;
			default:
				return __( 'Activity', 'partyminder' );
		}
	}

	/**
	 * Get activity metadata for display
	 */
	public function get_activity_metadata( $activity ) {
		switch ( $activity->activity_type ) {
			case 'event_created':
			case 'event_rsvp':
				return '📅 ' . date( 'M j, Y', strtotime( $activity->event_date ) );
			case 'conversation_created':
			case 'conversation_reply':
				return '💬 in ' . esc_html( $activity->topic_name );
			default:
				return '';
		}
	}
}
