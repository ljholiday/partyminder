<?php

class PartyMinder_Guest_Manager {

	public function process_rsvp( $rsvp_data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $rsvp_data['event_id'] ) || empty( $rsvp_data['name'] ) || empty( $rsvp_data['email'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Event ID, name, and email are required', 'partyminder' ),
			);
		}

		// Validate email
		if ( ! is_email( $rsvp_data['email'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please provide a valid email address', 'partyminder' ),
			);
		}

		// Validate status
		$valid_statuses = array( 'confirmed', 'declined', 'maybe', 'pending' );
		if ( ! in_array( $rsvp_data['status'], $valid_statuses ) ) {
			$rsvp_data['status'] = 'pending';
		}

		$guests_table = $wpdb->prefix . 'partyminder_guests';

		// Check for existing guest
		$existing_guest = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE event_id = %d AND email = %s",
				$rsvp_data['event_id'],
				$rsvp_data['email']
			)
		);

		if ( $existing_guest ) {
			// Update existing RSVP
			$update_data = array(
				'name'                 => sanitize_text_field( $rsvp_data['name'] ),
				'status'               => sanitize_text_field( $rsvp_data['status'] ),
				'dietary_restrictions' => sanitize_text_field( $rsvp_data['dietary'] ?? '' ),
				'notes'                => sanitize_text_field( $rsvp_data['notes'] ?? '' ),
				'rsvp_date'            => current_time( 'mysql' ),
			);

			$result = $wpdb->update(
				$guests_table,
				$update_data,
				array( 'id' => $existing_guest->id ),
				null,
				array( '%d' )
			);

			$guest_id = $existing_guest->id;
		} else {
			// Create new guest record
			$result = $wpdb->insert(
				$guests_table,
				array(
					'event_id'             => intval( $rsvp_data['event_id'] ),
					'name'                 => sanitize_text_field( $rsvp_data['name'] ),
					'email'                => sanitize_email( $rsvp_data['email'] ),
					'status'               => sanitize_text_field( $rsvp_data['status'] ),
					'dietary_restrictions' => sanitize_text_field( $rsvp_data['dietary'] ?? '' ),
					'notes'                => sanitize_text_field( $rsvp_data['notes'] ?? '' ),
					'rsvp_date'            => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			$guest_id = $wpdb->insert_id;
		}

		if ( $result !== false ) {
			// Send confirmation email
			$this->send_rsvp_confirmation( $guest_id, $rsvp_data['event_id'], $rsvp_data['status'] );

			// Update profile stats for confirmed RSVP
			if ( $rsvp_data['status'] === 'confirmed' && class_exists( 'PartyMinder_Profile_Manager' ) ) {
				// Get user ID from email if they're a registered user
				$user = get_user_by( 'email', $rsvp_data['email'] );
				if ( $user ) {
					PartyMinder_Profile_Manager::increment_events_attended( $user->ID );
				}
			}

			return array(
				'success'  => true,
				'message'  => $this->get_rsvp_success_message( $rsvp_data['status'] ),
				'guest_id' => $guest_id,
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to process RSVP. Please try again.', 'partyminder' ),
			);
		}
	}

	public function get_event_guests( $event_id, $status = null ) {
		global $wpdb;

		$guests_table = $wpdb->prefix . 'partyminder_guests';

		$sql    = "SELECT * FROM $guests_table WHERE event_id = %d";
		$params = array( $event_id );

		if ( $status ) {
			$sql     .= ' AND status = %s';
			$params[] = $status;
		}

		$sql .= ' ORDER BY rsvp_date DESC';

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public function get_guest_stats( $event_id ) {
		global $wpdb;

		$guests_table = $wpdb->prefix . 'partyminder_guests';

		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
                SUM(CASE WHEN status = 'maybe' THEN 1 ELSE 0 END) as maybe,
                SUM(CASE WHEN status IN ('pending') THEN 1 ELSE 0 END) as pending
            FROM $guests_table WHERE event_id = %d",
				$event_id
			)
		);

		return $stats ?: (object) array(
			'total'     => 0,
			'confirmed' => 0,
			'declined'  => 0,
			'maybe'     => 0,
			'pending'   => 0,
		);
	}

	public function send_invitation( $guest_id, $event_id ) {
		$guest         = $this->get_guest( $guest_id );
		$event_manager = new PartyMinder_Event_Manager();
		$event         = $event_manager->get_event( $event_id );

		if ( ! $guest || ! $event ) {
			return false;
		}

		$subject = sprintf( __( 'You\'re invited to %s', 'partyminder' ), $event->title );

		$rsvp_link = add_query_arg(
			array(
				'event_id'    => $event_id,
				'guest_email' => $guest->email,
			),
			get_permalink( $event_id )
		);

		$message = sprintf(
			__( "Hi %1\$s,\n\nYou're invited to: %2\$s\n\nWhen: %3\$s\nWhere: %4\$s\n\n%5\$s\n\nPlease RSVP: %6\$s\n\nBest regards,\n%7\$s", 'partyminder' ),
			$guest->name,
			$event->title,
			date( 'F j, Y \a\t g:i A', strtotime( $event->event_date ) ),
			$event->venue_info,
			strip_tags( $event->description ),
			$rsvp_link,
			$event->host_email ?: get_bloginfo( 'name' )
		);

		$headers = array(
			'From: ' . get_option( 'partyminder_email_from_name', get_bloginfo( 'name' ) ) . ' <' . get_option( 'partyminder_email_from_address', get_option( 'admin_email' ) ) . '>',
		);

		return wp_mail( $guest->email, $subject, $message, $headers );
	}

	private function send_rsvp_confirmation( $guest_id, $event_id, $status ) {
		$guest         = $this->get_guest( $guest_id );
		$event_manager = new PartyMinder_Event_Manager();
		$event         = $event_manager->get_event( $event_id );

		if ( ! $guest || ! $event ) {
			return false;
		}

		$subject = sprintf( __( 'RSVP Confirmation for %s', 'partyminder' ), $event->title );

		$status_messages = array(
			'confirmed' => __( 'Thank you for confirming! We\'re excited to see you there.', 'partyminder' ),
			'declined'  => __( 'Thank you for letting us know. We\'ll miss you!', 'partyminder' ),
			'maybe'     => __( 'Thank you for your response. Please confirm when you can.', 'partyminder' ),
			'pending'   => __( 'We received your RSVP. Please confirm when you can.', 'partyminder' ),
		);

		$message = sprintf(
			__( "Hi %1\$s,\n\n%2\$s\n\nEvent: %3\$s\nDate: %4\$s\nYour Status: %5\$s\n\nBest regards,\n%6\$s", 'partyminder' ),
			$guest->name,
			$status_messages[ $status ] ?? '',
			$event->title,
			date( 'F j, Y \a\t g:i A', strtotime( $event->event_date ) ),
			ucfirst( $status ),
			$event->host_email ?: get_bloginfo( 'name' )
		);

		$headers = array(
			'From: ' . get_option( 'partyminder_email_from_name', get_bloginfo( 'name' ) ) . ' <' . get_option( 'partyminder_email_from_address', get_option( 'admin_email' ) ) . '>',
		);

		return wp_mail( $guest->email, $subject, $message, $headers );
	}

	private function get_guest( $guest_id ) {
		global $wpdb;

		$guests_table = $wpdb->prefix . 'partyminder_guests';
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE id = %d",
				$guest_id
			)
		);
	}

	private function get_rsvp_success_message( $status ) {
		$messages = array(
			'confirmed' => __( 'Thank you for confirming! We\'re excited to see you.', 'partyminder' ),
			'declined'  => __( 'Thank you for letting us know.', 'partyminder' ),
			'maybe'     => __( 'Thank you! Please confirm when you can.', 'partyminder' ),
			'pending'   => __( 'RSVP received. Please confirm when possible.', 'partyminder' ),
		);

		return $messages[ $status ] ?? __( 'RSVP updated successfully.', 'partyminder' );
	}
}
