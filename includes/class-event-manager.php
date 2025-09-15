<?php

class PartyMinder_Event_Manager {

	public function __construct() {
		// No more WordPress post/page hooks needed - pure custom table system
	}

	public function create_event( $event_data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $event_data['title'] ) || empty( $event_data['event_date'] ) ) {
			return new WP_Error( 'missing_data', __( 'Event title and date are required', 'partyminder' ) );
		}

		// Generate unique slug
		$slug = $this->generate_unique_slug( $event_data['title'] );

		// Determine privacy based on inheritance model
		$privacy = $this->determine_event_privacy( $event_data );
		if ( is_wp_error( $privacy ) ) {
			return $privacy;
		}

		// Insert event data directly to custom table - no WordPress posts
		$events_table = $wpdb->prefix . 'partyminder_events';
		$result       = $wpdb->insert(
			$events_table,
			array(
				'title'            => sanitize_text_field( wp_unslash( $event_data['title'] ) ),
				'slug'             => $slug,
				'description'      => wp_kses_post( wp_unslash( $event_data['description'] ?? '' ) ),
				'excerpt'          => wp_trim_words( wp_kses_post( wp_unslash( $event_data['description'] ?? '' ) ), 25 ),
				'event_date'       => sanitize_text_field( $event_data['event_date'] ),
				'event_time'       => sanitize_text_field( $event_data['event_time'] ?? '' ),
				'guest_limit'      => intval( $event_data['guest_limit'] ?? 0 ),
				'venue_info'       => sanitize_text_field( $event_data['venue'] ?? '' ),
				'host_email'       => sanitize_email( $event_data['host_email'] ?? '' ),
				'host_notes'       => wp_kses_post( wp_unslash( $event_data['host_notes'] ?? '' ) ),
				'privacy'          => $privacy,
				'event_status'     => 'active',
				'author_id'        => get_current_user_id() ?: 1,
				'community_id'     => intval( $event_data['community_id'] ?? 0 ),
				'meta_title'       => sanitize_text_field( wp_unslash( $event_data['title'] ) ),
				'meta_description' => wp_trim_words( wp_kses_post( wp_unslash( $event_data['description'] ?? '' ) ), 20 ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( $result === false ) {
			$error_msg = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to create event', 'partyminder' );
			return new WP_Error( 'creation_failed', $error_msg );
		}

		$event_id = $wpdb->insert_id;

		// Update profile stats for event creation
		if ( class_exists( 'PartyMinder_Profile_Manager' ) ) {
			$author_id = intval( $event_data['author_id'] ?? get_current_user_id() );
			PartyMinder_Profile_Manager::increment_events_hosted( $author_id );
		}

		return $event_id;
	}

	private function generate_unique_slug( $title ) {
		global $wpdb;

		$base_slug = sanitize_title( $title );
		$slug      = $base_slug;
		$counter   = 1;

		$events_table = $wpdb->prefix . 'partyminder_events';

		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $events_table WHERE slug = %s", $slug ) ) ) {
			$slug = $base_slug . '-' . $counter;
			++$counter;
		}

		return $slug;
	}

	public function get_event( $event_id ) {
		global $wpdb;

		$events_table = $wpdb->prefix . 'partyminder_events';
		$event        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $events_table WHERE id = %d",
				$event_id
			)
		);

		if ( ! $event ) {
			return null;
		}

		// Get guest stats
		$event->guest_stats = $this->get_guest_stats( $event_id );

		return $event;
	}

	public function get_event_by_slug( $slug ) {
		global $wpdb;

		$events_table = $wpdb->prefix . 'partyminder_events';
		$event        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $events_table WHERE slug = %s AND event_status = 'active'",
				$slug
			)
		);

		if ( ! $event ) {
			return null;
		}

		// Get guest stats
		$event->guest_stats = $this->get_guest_stats( $event->id );

		return $event;
	}


	/**
	 * Check if the current user can view an event based on privacy settings
	 */
	public function can_user_view_event( $event ) {
		if ( ! $event ) {
			return false;
		}

		// Public events can be viewed by anyone
		if ( $event->privacy === 'public' ) {
			return true;
		}

		// Private events can only be viewed by the creator
		$current_user_id = get_current_user_id();
		if ( $current_user_id && $event->author_id == $current_user_id ) {
			return true;
		}

		// Check if current user is an invited guest (RSVP'd)
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user_email   = $current_user->user_email;

			global $wpdb;
			$guests_table = $wpdb->prefix . 'partyminder_guests';

			$guest_record = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $guests_table 
                 WHERE event_id = %d AND email = %s",
					$event->id,
					$user_email
				)
			);

			if ( $guest_record ) {
				return true;
			}

			// Also check if user has a pending invitation
			$guests_table = $wpdb->prefix . 'partyminder_guests';
			$invitation_record = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $guests_table 
                 WHERE event_id = %d AND email = %s AND status = 'pending' AND rsvp_token != ''",
					$event->id,
					$user_email
				)
			);

			if ( $invitation_record ) {
				return true;
			}
		}

		return false;
	}

	public function get_upcoming_events( $limit = 10 ) {
		global $wpdb;

		$events_table = $wpdb->prefix . 'partyminder_events';
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$current_user_id = get_current_user_id();

		// Enhanced privacy logic that respects inheritance
		if ( $current_user_id && is_user_logged_in() ) {
			// For logged-in users: show ALL events they have permission to view including:
			// 1. Public events, 2. Their own events, 3. Events they've RSVP'd to,
			// 4. Events from public communities, 5. Events from communities they belong to
			$query = "SELECT DISTINCT e.* FROM $events_table e
					 LEFT JOIN $communities_table c ON e.community_id = c.id
					 LEFT JOIN $guests_table g ON e.id = g.event_id AND g.email = (SELECT user_email FROM {$wpdb->users} WHERE ID = %d)
	                 WHERE e.event_status = 'active'
	                 AND (
						e.author_id = %d OR
						g.event_id IS NOT NULL OR
						((e.community_id IS NULL OR e.community_id = 0) AND e.privacy = 'public') OR
						(e.community_id IS NOT NULL AND e.community_id != 0 AND (
							c.visibility = 'public' OR 
							c.creator_id = %d OR
							EXISTS(
								SELECT 1 FROM $members_table m 
								WHERE m.community_id = c.id AND m.user_id = %d 
								AND m.status = 'active'
							)
						))
					)
	                 ORDER BY e.event_date ASC 
	                 LIMIT %d";

			$results = $wpdb->get_results( $wpdb->prepare( $query, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $limit ) );
		} else {
			// Not logged in: only show public events and events from public communities
			$query = "SELECT DISTINCT e.* FROM $events_table e
					 LEFT JOIN $communities_table c ON e.community_id = c.id
	                 WHERE e.event_status = 'active'
	                 AND (
						((e.community_id IS NULL OR e.community_id = 0) AND e.privacy = 'public') OR
						(e.community_id IS NOT NULL AND e.community_id != 0 AND c.visibility = 'public')
					)
	                 ORDER BY e.event_date ASC 
	                 LIMIT %d";

			$results = $wpdb->get_results( $wpdb->prepare( $query, $limit ) );
		}

		// Add guest stats to each event
		foreach ( $results as $event ) {
			$event->guest_stats = $this->get_guest_stats( $event->id );
		}

		return $results;
	}

	public function get_guest_stats( $event_id ) {
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
		$guest_manager = new PartyMinder_Guest_Manager();
		return $guest_manager->get_guest_stats( $event_id );
	}


	public function update_event( $event_id, $event_data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $event_data['title'] ) || empty( $event_data['event_date'] ) ) {
			return new WP_Error( 'missing_data', __( 'Event title and date are required', 'partyminder' ) );
		}

		// Get current event
		$current_event = $this->get_event( $event_id );
		if ( ! $current_event ) {
			return new WP_Error( 'event_not_found', __( 'Event not found', 'partyminder' ) );
		}

		// Check permissions - only event host (author) can update events
		$current_user = wp_get_current_user();
		if ( $current_event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_Error( 'permission_denied', __( 'Only the event host can update this event', 'partyminder' ) );
		}

		// Generate unique slug if title changed
		$slug = $current_event->slug;
		if ( $current_event->title !== $event_data['title'] ) {
			$slug = $this->generate_unique_slug( $event_data['title'] );
		}

		// Update event data in custom table only
		$events_table = $wpdb->prefix . 'partyminder_events';
		$update_data  = array(
			'title'            => sanitize_text_field( wp_unslash( $event_data['title'] ) ),
			'slug'             => $slug,
			'description'      => wp_kses_post( wp_unslash( $event_data['description'] ?? '' ) ),
			'excerpt'          => wp_trim_words( wp_kses_post( $event_data['description'] ?? '' ), 25 ),
			'event_date'       => sanitize_text_field( $event_data['event_date'] ),
			'event_time'       => sanitize_text_field( $event_data['event_time'] ?? '' ),
			'guest_limit'      => intval( $event_data['guest_limit'] ?? 0 ),
			'venue_info'       => sanitize_text_field( $event_data['venue'] ?? '' ),
			'host_email'       => sanitize_email( $event_data['host_email'] ?? '' ),
			'host_notes'       => wp_kses_post( wp_unslash( $event_data['host_notes'] ?? '' ) ),
			'privacy'          => $this->validate_privacy_setting( $event_data['privacy'] ?? 'public' ),
			'meta_title'       => sanitize_text_field( wp_unslash( $event_data['title'] ) ),
			'meta_description' => wp_trim_words( wp_kses_post( wp_unslash( $event_data['description'] ?? '' ) ), 20 ),
		);

		$result = $wpdb->update(
			$events_table,
			$update_data,
			array( 'id' => $event_id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to update event data', 'partyminder' ) );
		}

		return $event_id;
	}

	/**
	 * Send invitation to attend event
	 */
	public function send_event_invitation( $event_id, $email, $message = '' ) {
		global $wpdb;

		// Get event
		$event = $this->get_event( $event_id );
		if ( ! $event ) {
			return new WP_Error( 'event_not_found', __( 'Event not found', 'partyminder' ) );
		}

		// Check permissions - only event host can send invitations
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in', 'partyminder' ) );
		}

		if ( $event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_Error( 'permission_denied', __( 'Only the event host can send invitations', 'partyminder' ) );
		}

		// Validate email
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Please provide a valid email address', 'partyminder' ) );
		}

		// Check if user is already RSVP'd
		$guests_table  = $wpdb->prefix . 'partyminder_guests';
		$existing_rsvp = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE event_id = %d AND email = %s",
				$event_id,
				$email
			)
		);

		if ( $existing_rsvp ) {
			return new WP_Error( 'already_rsvpd', __( 'This person has already RSVP\'d to the event', 'partyminder' ) );
		}

		// Check for existing pending invitation
		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$existing_invitation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table 
             WHERE event_id = %d AND email = %s AND status = 'pending' AND rsvp_token != ''",
				$event_id,
				$email
			)
		);

		if ( $existing_invitation ) {
			return new WP_Error( 'invitation_exists', __( 'A pending invitation already exists for this email', 'partyminder' ) );
		}

		// Generate invitation token
		$token = wp_generate_password( 32, false );

		// Set expiration (event date or 30 days, whichever is sooner)
		$event_date  = strtotime( $event->event_date );
		$thirty_days = strtotime( '+30 days' );
		$expires_at  = date( 'Y-m-d H:i:s', min( $event_date, $thirty_days ) );

		// Insert invitation
		$result = $wpdb->insert(
			$invitations_table,
			array(
				'event_id'           => $event_id,
				'invited_by_user_id' => $current_user->ID,
				'invited_email'      => $email,
				'invited_user_id'    => get_user_by( 'email', $email ) ? get_user_by( 'email', $email )->ID : null,
				'invitation_token'   => $token,
				'message'            => wp_kses_post( $message ),
				'status'             => 'pending',
				'expires_at'         => $expires_at,
				'created_at'         => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result === false ) {
			$error_msg = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to create invitation', 'partyminder' );
			return new WP_Error( 'invitation_failed', $error_msg );
		}

		$invitation_id = $wpdb->insert_id;

		// Send invitation email
		$email_sent = $this->send_event_invitation_email( $event, $current_user, $email, $token, $message );

		if ( is_wp_error( $email_sent ) ) {
			// Log email error but don't fail the invitation
			error_log( 'PartyMinder: Failed to send event invitation email: ' . $email_sent->get_error_message() );
		}

		return array(
			'invitation_id' => $invitation_id,
			'token'         => $token,
			'expires_at'    => $expires_at,
			'email_sent'    => ! is_wp_error( $email_sent ),
		);
	}

	/**
	 * Send event invitation email
	 */
	private function send_event_invitation_email( $event, $inviter, $email, $token, $message = '' ) {
		$site_name      = get_bloginfo( 'name' );
		$invitation_url = add_query_arg(
			array( 'token' => $token ),
			home_url( '/events/' . $event->slug )
		);

		$subject = sprintf( __( '[%1$s] You\'re invited to %2$s', 'partyminder' ), $site_name, $event->title );

		$event_date = date( 'F j, Y', strtotime( $event->event_date ) );
		$event_time = $event->event_time ? ' at ' . $event->event_time : '';

		$email_message = sprintf(
			__(
				'Hello!

%1$s has invited you to attend "%2$s" on %3$s%4$s.

%5$s

%6$s

To RSVP for this event, click the link below:
%7$s

This invitation will expire on %8$s.

If you don\'t want to attend this event, you can safely ignore this email.

Best regards,
The %9$s Team',
				'partyminder'
			),
			$inviter->display_name,
			$event->title,
			$event_date,
			$event_time,
			$event->description ? "Event Details:\n" . wp_strip_all_tags( $event->description ) . "\n" : '',
			$message ? "\nPersonal message from " . $inviter->display_name . ":\n" . $message . "\n" : '',
			$invitation_url,
			date( 'F j, Y', strtotime( $event->expires_at ?? '+30 days' ) ),
			$site_name
		);

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . get_option( 'partyminder_email_from_name', $site_name ) . ' <' . get_option( 'partyminder_email_from_address', get_option( 'admin_email' ) ) . '>',
		);

		$sent = wp_mail( $email, $subject, $email_message, $headers );

		if ( ! $sent ) {
			return new WP_Error( 'email_failed', __( 'Failed to send invitation email', 'partyminder' ) );
		}

		return true;
	}

	/**
	 * Get pending invitations for an event
	 */
	public function get_event_invitations( $event_id, $limit = 20, $offset = 0 ) {
		global $wpdb;

		// Get event
		$event = $this->get_event( $event_id );
		if ( ! $event ) {
			return new WP_Error( 'event_not_found', __( 'Event not found', 'partyminder' ) );
		}

		$guests_table = $wpdb->prefix . 'partyminder_guests';

		$invitations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $guests_table 
             WHERE event_id = %d AND status = 'pending' AND rsvp_token != ''
             ORDER BY rsvp_date DESC
             LIMIT %d OFFSET %d",
				$event_id,
				$limit,
				$offset
			)
		);

		return $invitations ?: array();
	}

	/**
	 * Cancel event invitation
	 */
	public function cancel_event_invitation( $event_id, $invitation_id ) {
		global $wpdb;

		// Get event
		$event = $this->get_event( $event_id );
		if ( ! $event ) {
			return new WP_Error( 'event_not_found', __( 'Event not found', 'partyminder' ) );
		}

		// Check permissions - only event host can cancel invitations
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in', 'partyminder' ) );
		}

		if ( $event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_Error( 'permission_denied', __( 'Only the event host can cancel invitations', 'partyminder' ) );
		}

		// Delete invitation record (cancelling invitation)
		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$result = $wpdb->delete(
			$guests_table,
			array(
				'id'       => $invitation_id,
				'event_id' => $event_id,
				'status'   => 'pending',
			),
			array( '%d', '%d', '%s' )
		);

		if ( $result === false ) {
			$error_msg = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to cancel invitation', 'partyminder' );
			return new WP_Error( 'cancel_failed', $error_msg );
		}

		if ( $result === 0 ) {
			return new WP_Error( 'invitation_not_found', __( 'Invitation not found or already processed', 'partyminder' ) );
		}

		return true;
	}

	/**
	 * Get event invitation by token
	 */
	public function get_invitation_by_token( $token ) {
		global $wpdb;

		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$events_table = $wpdb->prefix . 'partyminder_events';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT g.*, e.title as event_title, e.slug as event_slug, e.description as event_description,
                    e.event_date, e.venue_info
             FROM $guests_table g
             LEFT JOIN $events_table e ON g.event_id = e.id
             WHERE g.rsvp_token = %s",
				$token
			)
		);
	}

	/**
	 * Accept event invitation (creates RSVP)
	 */
	public function accept_event_invitation( $token, $user_id, $guest_data = array() ) {
		global $wpdb;

		// Get invitation
		$invitation = $this->get_invitation_by_token( $token );
		if ( ! $invitation ) {
			return new WP_Error( 'invitation_not_found', __( 'Invitation not found', 'partyminder' ) );
		}

		if ( $invitation->status !== 'pending' ) {
			return new WP_Error( 'invitation_processed', __( 'This invitation has already been processed', 'partyminder' ) );
		}

		if ( strtotime( $invitation->expires_at ) < time() ) {
			return new WP_Error( 'invitation_expired', __( 'This invitation has expired', 'partyminder' ) );
		}

		// Check if user already RSVP'd
		$guests_table  = $wpdb->prefix . 'partyminder_guests';
		$existing_rsvp = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE event_id = %d AND email = %s",
				$invitation->event_id,
				$invitation->invited_email
			)
		);

		if ( $existing_rsvp ) {
			return new WP_Error( 'already_rsvpd', __( 'You have already RSVP\'d to this event', 'partyminder' ) );
		}

		// Get user info
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'user_not_found', __( 'User not found', 'partyminder' ) );
		}

		// Create RSVP
		$rsvp_data = array_merge(
			array(
				'event_id'  => $invitation->event_id,
				'name'      => $user->display_name,
				'email'     => $invitation->invited_email,
				'status'    => 'attending',
				'rsvp_date' => current_time( 'mysql' ),
			),
			$guest_data
		);

		$rsvp_result = $wpdb->insert(
			$guests_table,
			$rsvp_data,
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		if ( $rsvp_result === false ) {
			return new WP_Error( 'rsvp_failed', __( 'Failed to create RSVP', 'partyminder' ) );
		}

		// Invitation acceptance is handled by updating the guest record status
		// (RSVP creation above already updates the status)

		return $wpdb->insert_id;
	}

	/**
	 * Delete event and all related data
	 */
	public function delete_event( $event_id ) {
		global $wpdb;

		// Get event first to check if it exists
		$event = $this->get_event( $event_id );
		if ( ! $event ) {
			return new WP_Error( 'event_not_found', __( 'Event not found', 'partyminder' ) );
		}

		// Check permissions - only event creator or admin can delete
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in', 'partyminder' ) );
		}

		if ( $event->author_id != $current_user->ID && ! current_user_can( 'delete_others_posts' ) ) {
			return new WP_Error( 'permission_denied', __( 'Only the event host or admin can delete this event', 'partyminder' ) );
		}

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Delete related data first (to maintain referential integrity)

			// 1. Delete guest RSVPs and invitations
			$guests_table = $wpdb->prefix . 'partyminder_guests';
			$wpdb->delete( $guests_table, array( 'event_id' => $event_id ), array( '%d' ) );

			// 2. Delete event conversations (if conversation feature is enabled)
			if ( class_exists( 'PartyMinder_Conversation_Manager' ) ) {
				$conversations_table = $wpdb->prefix . 'partyminder_conversations';
				$wpdb->delete( $conversations_table, array( 'event_id' => $event_id ), array( '%d' ) );
			}

			// 3. Finally, delete the event itself
			$events_table = $wpdb->prefix . 'partyminder_events';
			$result       = $wpdb->delete( $events_table, array( 'id' => $event_id ), array( '%d' ) );

			if ( $result === false ) {
				throw new Exception( __( 'Failed to delete event', 'partyminder' ) );
			}

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
	 * Get events created by a specific user for sidebar display
	 */
	public function get_user_events( $user_id, $limit = 6 ) {
		global $wpdb;

		$events_table = $wpdb->prefix . 'partyminder_events';

		$events = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT e.id, e.title, e.slug, e.event_date, e.event_time, e.venue_info
				FROM $events_table e
				WHERE e.author_id = %d AND e.event_status = 'active'
				ORDER BY e.event_date ASC
				LIMIT %d
			",
				$user_id,
				$limit
			)
		);

		// Add guest stats to each event
		foreach ( $events as $event ) {
			$event->guest_stats = $this->get_guest_stats( $event->id );
		}

		return $events;
	}

	/**
	 * Get events for a specific community (respecting privacy inheritance)
	 */
	public function get_community_events( $community_id, $limit = 20 ) {
		global $wpdb;
		$events_table = $wpdb->prefix . 'partyminder_events';
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		$current_user_id = get_current_user_id();

		// Get community to check privacy
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
		$community_manager = new PartyMinder_Community_Manager();
		$community = $community_manager->get_community( $community_id );
		
		if ( ! $community ) {
			return array();
		}

		// Community events inherit community privacy, so check community access
		$can_access_community = false;
		
		if ( $community->visibility === 'public' ) {
			$can_access_community = true;
		} elseif ( $current_user_id && is_user_logged_in() ) {
			// Check if user is community member or creator
			if ( $community->creator_id == $current_user_id || 
				 $community_manager->is_member( $community_id, $current_user_id ) ) {
				$can_access_community = true;
			}
		}
		
		if ( ! $can_access_community ) {
			return array(); // User cannot access this community's events
		}

		// Since we've verified community access, show all community events
		// (they inherit the community's privacy setting)
		$query = "SELECT DISTINCT e.* FROM $events_table e
				 WHERE e.event_status = 'active'
				 AND e.community_id = %d
				 ORDER BY e.event_date DESC 
				 LIMIT %d";

		$events = $wpdb->get_results(
			$wpdb->prepare( $query, $community_id, $limit )
		);

		if ( ! $events ) {
			return array();
		}

		// Add guest stats to each event
		foreach ( $events as $event ) {
			$event->guest_stats = $this->get_guest_stats( $event->id );
		}

		return $events;
	}

	/**
	 * Determine event privacy based on inheritance model
	 */
	private function determine_event_privacy( $event_data ) {
		$community_id = intval( $event_data['community_id'] ?? 0 );
		
		// For community events, inherit privacy from community
		if ( $community_id ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
			$community_manager = new PartyMinder_Community_Manager();
			$community = $community_manager->get_community( $community_id );
			
			if ( ! $community ) {
				return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
			}
			
			// Community events inherit community privacy
			return $community->privacy;
		}
		
		// For non-community events, use provided privacy or default to public
		return $this->validate_privacy_setting( $event_data['privacy'] ?? 'public' );
	}

	/**
	 * Validate event privacy settings and inheritance
	 */
	public function validate_event_privacy_inheritance( $event_data ) {
		$community_id = intval( $event_data['community_id'] ?? 0 );
		$provided_privacy = $event_data['privacy'] ?? null;
		
		if ( $community_id && $provided_privacy ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
			$community_manager = new PartyMinder_Community_Manager();
			$community = $community_manager->get_community( $community_id );
			
			if ( $community && $community->visibility !== $provided_privacy ) {
				return new WP_Error( 
					'privacy_mismatch', 
					sprintf( 
						__( 'Event privacy must match community privacy (%s)', 'partyminder' ),
						$community->privacy
					)
				);
			}
		}
		
		return true;
	}

	/**
	 * Get the effective privacy for an event (resolving inheritance)
	 */
	public function get_event_privacy( $event ) {
		// If event is part of a community, inherit community privacy
		if ( $event->community_id ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
			$community_manager = new PartyMinder_Community_Manager();
			$community = $community_manager->get_community( $event->community_id );
			
			if ( $community ) {
				return $community->privacy;
			}
		}
		
		return $event->privacy;
	}

	/**
	 * Validate privacy setting for events
	 */
	private function validate_privacy_setting( $privacy ) {
		$allowed_privacy_settings = array( 'public', 'private' );
		
		$privacy = sanitize_text_field( $privacy );
		
		if ( ! in_array( $privacy, $allowed_privacy_settings ) ) {
			return 'public'; // Default to public if invalid
		}
		
		return $privacy;
	}

}
