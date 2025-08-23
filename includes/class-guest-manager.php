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

	/**
	 * Create anonymous RSVP invitation with token for RSVP flow
	 */
	public function create_rsvp_invitation( $event_id, $email, $temporary_guest_id = '' ) {
		global $wpdb;

		// Generate secure token
		$rsvp_token = wp_generate_password( 32, false );
		
		if ( empty( $temporary_guest_id ) ) {
			$temporary_guest_id = wp_generate_uuid4();
		}

		$guests_table = $wpdb->prefix . 'partyminder_guests';

		// Check if invitation already exists
		$existing_guest = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE event_id = %d AND email = %s",
				$event_id,
				$email
			)
		);

		if ( $existing_guest ) {
			// Update token for existing guest
			$wpdb->update(
				$guests_table,
				array( 
					'rsvp_token' => $rsvp_token,
					'temporary_guest_id' => $temporary_guest_id
				),
				array( 'id' => $existing_guest->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// Create new anonymous guest record
			$wpdb->insert(
				$guests_table,
				array(
					'rsvp_token' => $rsvp_token,
					'temporary_guest_id' => $temporary_guest_id,
					'event_id' => $event_id,
					'email' => $email,
					'name' => '', // Will be filled during RSVP
					'status' => 'pending',
					'rsvp_date' => current_time( 'mysql' )
				),
				array( '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
			);
		}

		return array(
			'token' => $rsvp_token,
			'url' => add_query_arg( array( 'token' => $rsvp_token ), home_url( '/events/join' ) )
		);
	}

	/**
	 * Process anonymous RSVP via token (for email button clicks)
	 */
	public function process_anonymous_rsvp( $rsvp_token, $status, $guest_data = array() ) {
		global $wpdb;

		// Validate status
		$valid_statuses = array( 'confirmed', 'declined', 'maybe' );
		if ( ! in_array( $status, $valid_statuses ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid RSVP status', 'partyminder' ),
			);
		}

		$guests_table = $wpdb->prefix . 'partyminder_guests';

		// Find guest by token
		$guest = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE rsvp_token = %s",
				$rsvp_token
			)
		);

		if ( ! $guest ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid or expired RSVP link', 'partyminder' ),
			);
		}

		// Update RSVP
		$update_data = array(
			'status' => $status,
			'rsvp_date' => current_time( 'mysql' )
		);

		// Add guest data if provided (name, dietary restrictions, etc.)
		if ( ! empty( $guest_data['name'] ) ) {
			$update_data['name'] = sanitize_text_field( $guest_data['name'] );
		}
		if ( ! empty( $guest_data['dietary'] ) ) {
			$update_data['dietary_restrictions'] = sanitize_text_field( $guest_data['dietary'] );
		}
		if ( ! empty( $guest_data['notes'] ) ) {
			$update_data['notes'] = sanitize_text_field( $guest_data['notes'] );
		}

		$result = $wpdb->update(
			$guests_table,
			$update_data,
			array( 'id' => $guest->id ),
			null,
			array( '%d' )
		);

		if ( $result === false ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to update RSVP', 'partyminder' ),
			);
		}

		return array(
			'success' => true,
			'message' => $this->get_rsvp_success_message( $status ),
			'guest_id' => $guest->id,
			'event_id' => $guest->event_id,
		);
	}

	/**
	 * Get guest by RSVP token
	 */
	public function get_guest_by_token( $rsvp_token ) {
		global $wpdb;

		$guests_table = $wpdb->prefix . 'partyminder_guests';
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE rsvp_token = %s",
				$rsvp_token
			)
		);
	}

	/**
	 * Convert anonymous guest to registered user account
	 */
	public function convert_guest_to_user( $guest_id, $user_data ) {
		global $wpdb;

		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$guest = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE id = %d",
				$guest_id
			)
		);

		if ( ! $guest ) {
			return new WP_Error( 'guest_not_found', __( 'Guest not found', 'partyminder' ) );
		}

		// Check if user already exists with this email
		$existing_user = get_user_by( 'email', $guest->email );
		if ( $existing_user ) {
			// Link existing user
			$user_id = $existing_user->ID;
		} else {
			// Create new user account
			$user_id = wp_create_user( 
				$guest->email, 
				wp_generate_password(), 
				$guest->email 
			);

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			// Update user profile
			wp_update_user( array(
				'ID' => $user_id,
				'display_name' => $guest->name ?: $user_data['name'],
				'first_name' => $guest->name ?: $user_data['name']
			) );
		}

		// Update guest record with converted user ID
		$wpdb->update(
			$guests_table,
			array( 'converted_user_id' => $user_id ),
			array( 'id' => $guest_id ),
			array( '%d' ),
			array( '%d' )
		);

		// Create/update user profile with dietary preferences
		if ( class_exists( 'PartyMinder_Profile_Manager' ) ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-profile-manager.php';
			$profile_manager = new PartyMinder_Profile_Manager();
			
			$profile_data = array(
				'dietary_restrictions' => $guest->dietary_restrictions
			);
			
			if ( ! empty( $user_data ) ) {
				$profile_data = array_merge( $profile_data, $user_data );
			}
			
			$profile_manager->update_user_profile( $user_id, $profile_data );
		}

		return $user_id;
	}

	/**
	 * Send anonymous RSVP invitation email with quick action buttons
	 */
	public function send_rsvp_invitation( $event_id, $email, $host_name = '', $personal_message = '' ) {
		// Create the invitation
		$invitation_data = $this->create_rsvp_invitation( $event_id, $email );
		$rsvp_token = $invitation_data['token'];
		$invitation_url = $invitation_data['url'];

		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
		$event_manager = new PartyMinder_Event_Manager();
		$event = $event_manager->get_event( $event_id );

		if ( ! $event ) {
			return false;
		}

		// Create quick RSVP URLs
		$rsvp_yes_url = add_query_arg( array( 'response' => 'confirmed' ), $invitation_url );
		$rsvp_maybe_url = add_query_arg( array( 'response' => 'maybe' ), $invitation_url );
		$rsvp_no_url = add_query_arg( array( 'response' => 'declined' ), $invitation_url );

		$event_date = date( 'F j, Y', strtotime( $event->event_date ) );
		$event_time = date( 'g:i A', strtotime( $event->event_date ) );
		$event_day = date( 'l', strtotime( $event->event_date ) );

		$site_name = get_bloginfo( 'name' );
		$host_name = $host_name ?: $site_name;

		$subject = sprintf( __( 'You\'re invited: %s', 'partyminder' ), $event->title );

		// Create inline CSS for better email client compatibility
		$styles = array(
			'container' => 'max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.6; color: #333;',
			'header' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;',
			'body' => 'background: #ffffff; padding: 30px 20px;',
			'event_card' => 'background: #f8f9ff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0;',
			'btn_primary' => 'display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 8px 8px 8px 0;',
			'btn_secondary' => 'display: inline-block; background: #e2e8f0; color: #4a5568; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 8px 8px 8px 0;',
			'btn_danger' => 'display: inline-block; background: #f56565; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 8px 8px 8px 0;',
			'footer' => 'background: #f7fafc; color: #718096; padding: 20px; text-align: center; font-size: 12px;',
		);

		ob_start();
		?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $subject ); ?></title>
</head>
<body style="margin: 0; padding: 20px; background-color: #f7fafc;">
	<div style="<?php echo $styles['container']; ?>">
		<!-- Header -->
		<div style="<?php echo $styles['header']; ?>">
			<h1 style="margin: 0; font-size: 24px;">You're Invited!</h1>
			<p style="margin: 10px 0 0 0; opacity: 0.9;"><?php echo esc_html( $host_name ); ?> has invited you to an event</p>
		</div>

		<!-- Body -->
		<div style="<?php echo $styles['body']; ?>">
			<?php if ( $personal_message ) : ?>
				<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-bottom: 20px;">
					<strong>Personal message from <?php echo esc_html( $host_name ); ?>:</strong><br>
					<em><?php echo esc_html( $personal_message ); ?></em>
				</div>
			<?php endif; ?>

			<!-- Event Details -->
			<div style="<?php echo $styles['event_card']; ?>">
				<h2 style="margin: 0 0 15px 0; color: #2d3748; font-size: 20px;"><?php echo esc_html( $event->title ); ?></h2>
				
				<div style="margin-bottom: 10px;">
					<strong>📅 When:</strong> <?php echo $event_day; ?>, <?php echo $event_date; ?> at <?php echo $event_time; ?>
				</div>
				
				<?php if ( $event->venue_info ) : ?>
				<div style="margin-bottom: 10px;">
					<strong>📍 Where:</strong> <?php echo esc_html( $event->venue_info ); ?>
				</div>
				<?php endif; ?>
				
				<?php if ( $event->description ) : ?>
				<div style="margin-top: 15px;">
					<strong>About:</strong><br>
					<?php echo esc_html( wp_trim_words( $event->description, 25 ) ); ?>
				</div>
				<?php endif; ?>
			</div>

			<!-- Quick RSVP Buttons -->
			<div style="text-align: center; margin: 30px 0;">
				<h3 style="color: #2d3748; margin-bottom: 15px;">Quick RSVP:</h3>
				<div>
					<a href="<?php echo esc_url( $rsvp_yes_url ); ?>" style="<?php echo $styles['btn_primary']; ?>">
						✅ Yes, I'll be there!
					</a>
					<a href="<?php echo esc_url( $rsvp_maybe_url ); ?>" style="<?php echo $styles['btn_secondary']; ?>">
						🤔 Maybe
					</a>
					<a href="<?php echo esc_url( $rsvp_no_url ); ?>" style="<?php echo $styles['btn_danger']; ?>">
						❌ Can't make it
					</a>
				</div>
				<p style="margin-top: 20px; font-size: 14px; color: #718096;">
					Or <a href="<?php echo esc_url( $invitation_url ); ?>" style="color: #667eea;">click here to RSVP with more details</a>
				</p>
			</div>

			<!-- About PartyMinder -->
			<div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
				<p style="color: #718096; font-size: 14px; margin: 0;">
					This invitation was sent through <strong>PartyMinder</strong> - making event planning simple and social.
				</p>
				<p style="margin: 10px 0 0 0;">
					<a href="<?php echo home_url(); ?>" style="color: #667eea; font-size: 12px;">Learn more about PartyMinder</a>
				</p>
			</div>
		</div>

		<!-- Footer -->
		<div style="<?php echo $styles['footer']; ?>">
			<p style="margin: 0;">Having trouble with the buttons? Copy and paste this link: <?php echo esc_url( $invitation_url ); ?></p>
			<p style="margin: 10px 0 0 0;">
				© <?php echo date('Y'); ?> <?php echo esc_html( $site_name ); ?>. All rights reserved.
			</p>
		</div>
	</div>
</body>
</html>
		<?php
		$message = ob_get_clean();

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_option( 'partyminder_email_from_name', get_bloginfo( 'name' ) ) . ' <' . get_option( 'partyminder_email_from_address', get_option( 'admin_email' ) ) . '>',
		);

		$sent = wp_mail( $email, $subject, $message, $headers );

		if ( $sent ) {
			// Log successful invitation
			do_action( 'partyminder_rsvp_invitation_sent', $event_id, $email, $rsvp_token );
		}

		return $sent;
	}
}
