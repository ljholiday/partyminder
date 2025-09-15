<?php

class PartyMinder_Event_Ajax_Handler {

	private $event_manager;

	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'wp_ajax_partyminder_create_event', array( $this, 'ajax_create_event' ) );
		add_action( 'wp_ajax_nopriv_partyminder_create_event', array( $this, 'ajax_create_event' ) );
		add_action( 'wp_ajax_partyminder_create_community_event', array( $this, 'ajax_create_community_event' ) );
		add_action( 'wp_ajax_nopriv_partyminder_create_community_event', array( $this, 'ajax_create_community_event' ) );
		add_action( 'wp_ajax_partyminder_update_event', array( $this, 'ajax_update_event' ) );
		add_action( 'wp_ajax_nopriv_partyminder_update_event', array( $this, 'ajax_update_event' ) );
		add_action( 'wp_ajax_partyminder_get_event_conversations', array( $this, 'ajax_get_event_conversations' ) );
		add_action( 'wp_ajax_nopriv_partyminder_get_event_conversations', array( $this, 'ajax_get_event_conversations' ) );
		add_action( 'wp_ajax_partyminder_send_event_invitation', array( $this, 'ajax_send_event_invitation' ) );
		add_action( 'wp_ajax_partyminder_get_event_invitations', array( $this, 'ajax_get_event_invitations' ) );
		add_action( 'wp_ajax_partyminder_cancel_event_invitation', array( $this, 'ajax_cancel_event_invitation' ) );
		add_action( 'wp_ajax_partyminder_get_event_stats', array( $this, 'ajax_get_event_stats' ) );
		add_action( 'wp_ajax_partyminder_get_event_guests', array( $this, 'ajax_get_event_guests' ) );
		add_action( 'wp_ajax_partyminder_delete_event', array( $this, 'ajax_delete_event' ) );

		if ( is_admin() ) {
			add_action( 'wp_ajax_partyminder_admin_delete_event', array( $this, 'ajax_admin_delete_event' ) );
		}
	}

	private function get_event_manager() {
		if ( ! $this->event_manager ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
			$this->event_manager = new PartyMinder_Event_Manager();
		}
		return $this->event_manager;
	}

	public function ajax_create_event() {
		check_ajax_referer( 'create_partyminder_event', 'partyminder_event_nonce' );

		$form_errors = array();
		// Load form handler
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-form-handler.php';
		
		// Validate form data
		$form_errors = PartyMinder_Event_Form_Handler::validate_event_form( $_POST );

		if ( ! empty( $form_errors ) ) {
			wp_send_json_error( implode( ' ', $form_errors ) );
		}

		$event_data = PartyMinder_Event_Form_Handler::process_event_form_data( $_POST );
		

		$event_manager = $this->get_event_manager();
		$event_id      = $event_manager->create_event( $event_data );

		if ( ! is_wp_error( $event_id ) ) {
			// Handle cover image upload
			if ( isset( $_FILES['cover_image'] ) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK ) {
				$upload_result = $this->handle_cover_image_upload( $_FILES['cover_image'], $event_id );
				if ( is_wp_error( $upload_result ) ) {
					// Log error but don't fail the event creation
					error_log( 'Cover image upload failed: ' . $upload_result->get_error_message() );
				}
			}
			$created_event = $event_manager->get_event( $event_id );

			$creation_data = array(
				'event_id'    => $event_id,
				'event_url'   => home_url( '/events/' . $created_event->slug ),
				'event_title' => $created_event->title,
			);
			set_transient( 'partyminder_event_created_' . get_current_user_id(), $creation_data, 300 );

			wp_send_json_success(
				array(
					'event_id'  => $event_id,
					'message'   => __( 'Event created successfully!', 'partyminder' ),
					'event_url' => home_url( '/events/' . $created_event->slug ),
				)
			);
		} else {
			wp_send_json_error( $event_id->get_error_message() );
		}
	}

	public function ajax_update_event() {
		check_ajax_referer( 'edit_partyminder_event', 'partyminder_edit_event_nonce' );

		$event_id = intval( $_POST['event_id'] );
		if ( ! $event_id ) {
			wp_send_json_error( __( 'Event ID is required.', 'partyminder' ) );
		}

		$event_manager = $this->get_event_manager();
		$event         = $event_manager->get_event( $event_id );
		if ( ! $event ) {
			wp_send_json_error( __( 'Event not found.', 'partyminder' ) );
		}

		$current_user = wp_get_current_user();
		$can_edit     = false;

		if ( current_user_can( 'edit_posts' ) ||
			( is_user_logged_in() && $current_user->ID == $event->author_id ) ||
			( $current_user->user_email == $event->host_email ) ) {
			$can_edit = true;
		}

		if ( ! $can_edit ) {
			wp_send_json_error( __( 'You do not have permission to edit this event.', 'partyminder' ) );
		}

		$form_errors = array();
		// Load form handler
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-form-handler.php';
		
		// Validate form data
		$form_errors = PartyMinder_Event_Form_Handler::validate_event_form( $_POST );

		if ( ! empty( $form_errors ) ) {
			wp_send_json_error( implode( ' ', $form_errors ) );
		}

		$event_data = PartyMinder_Event_Form_Handler::process_event_form_data( $_POST );

		$result = $event_manager->update_event( $event_id, $event_data );

		if ( $result !== false ) {
			// Handle cover image upload
			if ( isset( $_FILES['cover_image'] ) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK ) {
				$upload_result = $this->handle_cover_image_upload( $_FILES['cover_image'], $event_id );
				if ( is_wp_error( $upload_result ) ) {
					// Log error but don't fail the event update
					error_log( 'Cover image upload failed: ' . $upload_result->get_error_message() );
				}
			}

			// Handle cover image removal
			if ( isset( $_POST['remove_cover_image'] ) && $_POST['remove_cover_image'] === '1' ) {
				global $wpdb;
				$events_table = $wpdb->prefix . 'partyminder_events';
				$wpdb->update(
					$events_table,
					array( 'featured_image' => '' ),
					array( 'id' => $event_id ),
					array( '%s' ),
					array( '%d' )
				);
			}
			$updated_event = $event_manager->get_event( $event_id );
			wp_send_json_success(
				array(
					'message'   => __( 'Event updated successfully!', 'partyminder' ),
					'event_url' => home_url( '/events/' . $updated_event->slug ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to update event. Please try again.', 'partyminder' ) );
		}
	}

	public function ajax_get_event_conversations() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		$event_id = intval( $_POST['event_id'] );
		if ( ! $event_id ) {
			wp_send_json_error( __( 'Event ID is required.', 'partyminder' ) );
			return;
		}

		$current_user = wp_get_current_user();
		$user_id      = 0;

		if ( is_user_logged_in() ) {
			$user_email = $current_user->user_email;
			$user_name  = $current_user->display_name;
			$user_id    = $current_user->ID;
		} else {
			$user_email = sanitize_email( $_POST['guest_email'] );
			$user_name  = sanitize_text_field( $_POST['guest_name'] );

			if ( empty( $user_email ) || empty( $user_name ) ) {
				wp_send_json_error( __( 'Email and name are required for guest access.', 'partyminder' ) );
				return;
			}
		}

		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
		$conversation_manager = new PartyMinder_Conversation_Manager();
		$conversations        = $conversation_manager->get_event_conversations( $event_id );

		wp_send_json_success(
			array(
				'conversations' => $conversations,
				'user_email'    => $user_email,
				'user_name'     => $user_name,
				'user_id'       => $user_id,
			)
		);
	}

	public function ajax_send_event_invitation() {
		check_ajax_referer( 'partyminder_event_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$event_id = intval( $_POST['event_id'] );
		$email    = sanitize_email( $_POST['email'] );
		$message  = sanitize_textarea_field( $_POST['message'] ?? '' );

		if ( ! $event_id || ! $email ) {
			wp_send_json_error( __( 'Event ID and email are required.', 'partyminder' ) );
			return;
		}

		$event_manager = $this->get_event_manager();
		$event         = $event_manager->get_event( $event_id );

		if ( ! $event ) {
			wp_send_json_error( __( 'Event not found.', 'partyminder' ) );
			return;
		}

		$current_user = wp_get_current_user();
		if ( $event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( __( 'Only the event host can send invitations.', 'partyminder' ) );
			return;
		}

		// Check if guest already exists
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
		$guest_manager = new PartyMinder_Guest_Manager();
		
		global $wpdb;
		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$existing_guest = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE event_id = %d AND email = %s",
				$event_id,
				$email
			)
		);

		if ( $existing_guest && $existing_guest->status !== 'declined' ) {
			wp_send_json_error( __( 'This email has already been invited.', 'partyminder' ) );
			return;
		}

		// Send RSVP invitation using Guest Manager
		$result = $guest_manager->send_rsvp_invitation( 
			$event_id, 
			$email, 
			$current_user->display_name, 
			$message 
		);

		if ( $result['success'] ) {
			$message = __( 'RSVP invitation created successfully!', 'partyminder' );
			if ( ! $result['email_sent'] ) {
				$message .= ' ' . __( 'Note: Email delivery may have failed.', 'partyminder' );
			}
			
			wp_send_json_success(
				array(
					'message' => $message,
					'invitation_url' => $result['url']
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to create invitation.', 'partyminder' ) );
		}
	}

	public function ajax_get_event_invitations() {
		check_ajax_referer( 'partyminder_event_action', 'nonce' );

		$event_id = intval( $_POST['event_id'] );
		if ( ! $event_id ) {
			wp_send_json_error( __( 'Event ID is required.', 'partyminder' ) );
			return;
		}

		$event_manager = $this->get_event_manager();
		$event         = $event_manager->get_event( $event_id );

		if ( ! $event ) {
			wp_send_json_error( __( 'Event not found.', 'partyminder' ) );
			return;
		}

		$current_user = wp_get_current_user();
		if ( $event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( __( 'Only the event host can view invitations.', 'partyminder' ) );
			return;
		}

		// Get all guests for this event (invitation system uses same table as RSVP system)
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
		$guest_manager = new PartyMinder_Guest_Manager();
		
		global $wpdb;
		$guests_table = $wpdb->prefix . 'partyminder_guests';
		$guests = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $guests_table 
				 WHERE event_id = %d AND rsvp_token != '' 
				 ORDER BY rsvp_date DESC",
				$event_id
			)
		);

		// Add invitation URLs to each guest - all use event page with modal
		foreach ( $guests as &$guest ) {
			if ( ! empty( $guest->rsvp_token ) ) {
				// Token-based URL for existing RSVPs (pre-fills modal)
				$guest->invitation_url = add_query_arg(
					array( 'token' => $guest->rsvp_token ),
					home_url( '/events/' . $event->slug )
				);
			} else {
				// RSVP URL for new invitations (auto-opens modal)
				$guest->invitation_url = add_query_arg(
					array( 'rsvp' => '1' ),
					home_url( '/events/' . $event->slug )
				);
			}
		}

		// Generate HTML for invitations list
		$html = '';
		if ( empty( $guests ) ) {
			$html = '<div class="pm-text-center pm-text-muted">' . __( 'No RSVP invitations sent yet.', 'partyminder' ) . '</div>';
		} else {
			foreach ( $guests as $guest ) {
				$status_class = '';
				$status_text = '';
				switch ( $guest->status ) {
					case 'confirmed':
						$status_class = 'success';
						$status_text = __( 'Confirmed', 'partyminder' );
						break;
					case 'declined':
						$status_class = 'danger';
						$status_text = __( 'Declined', 'partyminder' );
						break;
					case 'maybe':
						$status_class = 'warning';
						$status_text = __( 'Maybe', 'partyminder' );
						break;
					default:
						$status_class = 'secondary';
						$status_text = __( 'Pending', 'partyminder' );
				}

				$html .= '<div class="pm-flex pm-flex-between pm-p-4 pm-mb-4">';
				$html .= '<div class="pm-flex-1">';
				$html .= '<div class="pm-flex pm-gap-4">';
				$html .= '<strong>' . esc_html( $guest->email ) . '</strong>';
				$html .= '<span class="pm-badge pm-badge-' . $status_class . '">' . esc_html( $status_text ) . '</span>';
				$html .= '</div>';
				
				if ( ! empty( $guest->name ) ) {
					$html .= '<div class="pm-text-muted pm-mt-2">' . esc_html( $guest->name ) . '</div>';
				}
				
				$html .= '<div class="pm-text-muted pm-mt-2">';
				$html .= sprintf(
					__( 'Invited on %s', 'partyminder' ),
					date( 'M j, Y', strtotime( $guest->rsvp_date ) )
				);
				$html .= '</div>';
				
				if ( ! empty( $guest->dietary_restrictions ) ) {
					$html .= '<div class="pm-text-muted pm-mt-2"><strong>Dietary:</strong> ' . esc_html( $guest->dietary_restrictions ) . '</div>';
				}
				
				if ( ! empty( $guest->notes ) ) {
					$html .= '<div class="pm-text-muted pm-mt-2"><em>"' . esc_html( $guest->notes ) . '"</em></div>';
				}
				
				$html .= '</div>';
				
				if ( $guest->status === 'pending' ) {
					$html .= '<div class="pm-flex pm-flex-column pm-gap-4" style="align-items: stretch; min-height: 80px;">';
					$html .= '<button type="button" class="pm-btn pm-btn-secondary" onclick="copyInvitationUrl(\'' . esc_js( $guest->invitation_url ) . '\')">' . __( 'Copy Link', 'partyminder' ) . '</button>';
					$html .= '<button type="button" class="pm-btn pm-btn-danger cancel-event-invitation" data-invitation-id="' . esc_attr( $guest->id ) . '">' . __( 'Remove', 'partyminder' ) . '</button>';
					$html .= '</div>';
				} else {
					$html .= '<div class="pm-flex pm-flex-column pm-gap-4" style="align-items: stretch; min-height: 80px;">';
					$html .= '<button type="button" class="pm-btn pm-btn-secondary" onclick="copyInvitationUrl(\'' . esc_js( $guest->invitation_url ) . '\')">' . __( 'Copy Link', 'partyminder' ) . '</button>';
					$html .= '</div>';
				}
				
				$html .= '</div>';
			}
		}

		wp_send_json_success(
			array(
				'invitations' => $guests,
				'html'        => $html,
			)
		);
	}

	public function ajax_cancel_event_invitation() {
		check_ajax_referer( 'partyminder_event_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$guest_id = intval( $_POST['invitation_id'] );
		if ( ! $guest_id ) {
			wp_send_json_error( __( 'Guest ID is required.', 'partyminder' ) );
			return;
		}

		global $wpdb;
		$guests_table = $wpdb->prefix . 'partyminder_guests';

		$guest = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE id = %d",
				$guest_id
			)
		);

		if ( ! $guest ) {
			wp_send_json_error( __( 'Guest invitation not found.', 'partyminder' ) );
			return;
		}

		$event_manager = $this->get_event_manager();
		$event         = $event_manager->get_event( $guest->event_id );

		if ( ! $event ) {
			wp_send_json_error( __( 'Event not found.', 'partyminder' ) );
			return;
		}

		$current_user = wp_get_current_user();
		if ( $event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( __( 'Only the event host can remove guests.', 'partyminder' ) );
			return;
		}

		$result = $wpdb->delete(
			$guests_table,
			array( 'id' => $guest_id ),
			array( '%d' )
		);

		if ( $result !== false ) {
			wp_send_json_success(
				array(
					'message' => __( 'Guest removed successfully.', 'partyminder' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to remove guest.', 'partyminder' ) );
		}
	}

	public function ajax_get_event_stats() {
		check_ajax_referer( 'partyminder_event_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$event_id = intval( $_POST['event_id'] );
		if ( ! $event_id ) {
			wp_send_json_error( __( 'Event ID is required.', 'partyminder' ) );
			return;
		}

		$event_manager = $this->get_event_manager();
		$event         = $event_manager->get_event( $event_id );

		if ( ! $event ) {
			wp_send_json_error( __( 'Event not found.', 'partyminder' ) );
			return;
		}

		$current_user = wp_get_current_user();
		if ( $event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( __( 'Only the event host can view statistics.', 'partyminder' ) );
			return;
		}

		global $wpdb;
		$guests_table = $wpdb->prefix . 'partyminder_guests';

		$stats = array(
			'total_rsvps'      => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $guests_table WHERE event_id = %d",
					$event_id
				)
			),
			'attending'        => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $guests_table WHERE event_id = %d AND status = 'confirmed'",
					$event_id
				)
			),
			'not_attending'    => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $guests_table WHERE event_id = %d AND status = 'declined'",
					$event_id
				)
			),
			'maybe'            => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $guests_table WHERE event_id = %d AND status = 'maybe'",
					$event_id
				)
			),
			'invitations_sent' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $guests_table WHERE event_id = %d AND rsvp_token != ''",
					$event_id
				)
			),
		);

		wp_send_json_success( $stats );
	}

	public function ajax_get_event_guests() {
		check_ajax_referer( 'partyminder_event_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$event_id = intval( $_POST['event_id'] );
		if ( ! $event_id ) {
			wp_send_json_error( __( 'Event ID is required.', 'partyminder' ) );
			return;
		}

		$event_manager = $this->get_event_manager();
		$event         = $event_manager->get_event( $event_id );

		if ( ! $event ) {
			wp_send_json_error( __( 'Event not found.', 'partyminder' ) );
			return;
		}

		$current_user = wp_get_current_user();
		if ( $event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( __( 'Only the event host can view the guest list.', 'partyminder' ) );
			return;
		}

		global $wpdb;
		$guests_table = $wpdb->prefix . 'partyminder_guests';

		$guests = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $guests_table WHERE event_id = %d ORDER BY rsvp_date DESC",
				$event_id
			)
		);

		wp_send_json_success(
			array(
				'guests' => $guests,
			)
		);
	}

	public function ajax_delete_event() {
		check_ajax_referer( 'partyminder_event_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$event_id = intval( $_POST['event_id'] );
		if ( ! $event_id ) {
			wp_send_json_error( __( 'Event ID is required.', 'partyminder' ) );
			return;
		}

		$event_manager = $this->get_event_manager();
		$event         = $event_manager->get_event( $event_id );

		if ( ! $event ) {
			wp_send_json_error( __( 'Event not found.', 'partyminder' ) );
			return;
		}

		$current_user = wp_get_current_user();
		if ( $event->author_id != $current_user->ID && ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( __( 'You do not have permission to delete this event.', 'partyminder' ) );
			return;
		}

		$result = $event_manager->delete_event( $event_id );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message'      => __( 'Event deleted successfully.', 'partyminder' ),
					'redirect_url' => home_url( '/my-events' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to delete event.', 'partyminder' ) );
		}
	}

	public function ajax_admin_delete_event() {
		check_ajax_referer( 'partyminder_event_action', 'nonce' );

		if ( ! current_user_can( 'delete_others_posts' ) ) {
			wp_send_json_error( __( 'You do not have permission to delete events.', 'partyminder' ) );
			return;
		}

		$event_id = intval( $_POST['event_id'] );
		if ( ! $event_id ) {
			wp_send_json_error( __( 'Event ID is required.', 'partyminder' ) );
			return;
		}

		$event_manager = $this->get_event_manager();
		$result        = $event_manager->delete_event( $event_id );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message' => __( 'Event deleted successfully.', 'partyminder' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to delete event.', 'partyminder' ) );
		}
	}

	/**
	 * Generate HTML email template for event invitations
	 */
	private static function generate_invitation_email_html( $data ) {
		$event            = $data['event'];
		$invitation_url   = $data['invitation_url'];
		$rsvp_yes_url     = $data['rsvp_yes_url'];
		$rsvp_maybe_url   = $data['rsvp_maybe_url'];
		$rsvp_no_url      = $data['rsvp_no_url'];
		$host_name        = $data['host_name'];
		$personal_message = $data['personal_message'];
		$invited_email    = $data['invited_email'];

		$event_date = date( 'F j, Y', strtotime( $event->event_date ) );
		$event_time = date( 'g:i A', strtotime( $event->event_date ) );
		$event_day  = date( 'l', strtotime( $event->event_date ) );

		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url();

		// Create inline CSS for better email client compatibility
		$styles = array(
			'container'     => 'max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.6; color: #333;',
			'header'        => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;',
			'body'          => 'background: #ffffff; padding: 30px 20px;',
			'event_card'    => 'background: #f8f9ff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0;',
			'btn_primary'   => 'display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 8px 8px 8px 0;',
			'btn_secondary' => 'display: inline-block; background: #e2e8f0; color: #4a5568; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 8px 8px 8px 0;',
			'btn_danger'    => 'display: inline-block; background: #f56565; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 8px 8px 8px 0;',
			'footer'        => 'background: #f7fafc; color: #718096; padding: 20px; text-align: center; font-size: 12px;',
		);

		ob_start();
		?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $event->title ); ?> - Event Invitation</title>
</head>
<body style="margin: 0; padding: 20px; background: #f7fafc;">
	<div style="<?php echo $styles['container']; ?>">
		<!-- Header -->
		<div style="<?php echo $styles['header']; ?>">
			<h1 style="margin: 0; font-size: 28px;">You're Invited!</h1>
			<p style="margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;"><?php echo esc_html( $event->title ); ?></p>
		</div>
		
		<!-- Main Content -->
		<div style="<?php echo $styles['body']; ?>">
			<p style="font-size: 18px; margin-top: 0;">Hi there!</p>
			
			<p><strong><?php echo esc_html( $host_name ); ?></strong> has invited you to their event. Here are all the details:</p>
			
			<!-- Event Details Card -->
			<div style="<?php echo $styles['event_card']; ?>">
				<h2 style="margin-top: 0; color: #4a5568;"><?php echo esc_html( $event->title ); ?></h2>
				
				<div style="margin: 15px 0;">
					<p style="margin: 5px 0;"><strong>When:</strong> <?php echo $event_day; ?>, <?php echo $event_date; ?> at <?php echo $event_time; ?></p>
					<?php if ( $event->venue_info ) : ?>
					<p style="margin: 5px 0;"><strong>Where:</strong> <?php echo esc_html( $event->venue_info ); ?></p>
					<?php endif; ?>
					<?php if ( $event->description ) : ?>
					<p style="margin: 15px 0 5px 0;"><strong>Details:</strong></p>
					<p style="margin: 5px 0;"><?php echo nl2br( esc_html( $event->description ) ); ?></p>
					<?php endif; ?>
				</div>
				
				<?php if ( $personal_message ) : ?>
				<div style="background: white; border-left: 4px solid #667eea; padding: 15px; margin: 15px 0;">
					<p style="margin: 0;"><strong>Personal message from <?php echo esc_html( $host_name ); ?>:</strong></p>
					<p style="margin: 10px 0 0 0; font-style: italic;">"<?php echo esc_html( $personal_message ); ?>"</p>
				</div>
				<?php endif; ?>
			</div>
			
			<!-- RSVP Buttons -->
			<div style="text-align: center; margin: 30px 0;">
				<p style="font-size: 18px; font-weight: bold; margin-bottom: 20px;">Can you make it?</p>
				<div>
					<a href="<?php echo esc_url( $rsvp_yes_url ); ?>" style="<?php echo $styles['btn_primary']; ?>">
						Yes, I'll be there!
					</a>
					<a href="<?php echo esc_url( $rsvp_maybe_url ); ?>" style="<?php echo $styles['btn_secondary']; ?>">
						Maybe
					</a>
					<a href="<?php echo esc_url( $rsvp_no_url ); ?>" style="<?php echo $styles['btn_danger']; ?>">
						Can't make it
					</a>
				</div>
				<p style="margin-top: 20px; font-size: 14px; color: #718096;">
					Or <a href="<?php echo esc_url( $invitation_url ); ?>" style="color: #667eea;">click here to RSVP with more details</a>
				</p>
			</div>
			
			<!-- Host Contact -->
			<div style="border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 30px;">
				<p style="font-size: 14px; color: #718096;">
					Questions about the event? Just reply to this email to reach <?php echo esc_html( $host_name ); ?> directly.
				</p>
			</div>
		</div>
		
		<!-- Footer -->
		<div style="<?php echo $styles['footer']; ?>">
			<p>This invitation was sent through <a href="<?php echo esc_url( $site_url ); ?>" style="color: #667eea;"><?php echo esc_html( $site_name ); ?></a></p>
			<p>If you can't click the buttons above, copy and paste this link: <br><?php echo esc_url( $invitation_url ); ?></p>
		</div>
	</div>
</body>
</html>
		<?php
		return ob_get_clean();
	}

	public function ajax_create_community_event() {
		check_ajax_referer( 'create_partyminder_community_event', 'partyminder_community_event_nonce' );

		$form_errors = array();
		if ( empty( $_POST['event_title'] ) ) {
			$form_errors[] = __( 'Event title is required.', 'partyminder' );
		}
		if ( empty( $_POST['event_date'] ) ) {
			$form_errors[] = __( 'Event date is required.', 'partyminder' );
		}
		if ( empty( $_POST['host_email'] ) ) {
			$form_errors[] = __( 'Host email is required.', 'partyminder' );
		}
		if ( empty( $_POST['community_id'] ) ) {
			$form_errors[] = __( 'Community ID is required.', 'partyminder' );
		}

		if ( ! empty( $form_errors ) ) {
			wp_send_json_error( implode( ' ', $form_errors ) );
		}

		// Verify user is a member of the community
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
		$community_manager = new PartyMinder_Community_Manager();
		$community_id = intval( $_POST['community_id'] );
		
		if ( ! $community_manager->is_member( $community_id, get_current_user_id() ) ) {
			wp_send_json_error( __( 'You must be a member of the community to create events.', 'partyminder' ) );
		}

		$event_data = array(
			'title'        => sanitize_text_field( wp_unslash( $_POST['event_title'] ) ),
			'description'  => wp_kses_post( wp_unslash( $_POST['event_description'] ) ),
			'event_date'   => sanitize_text_field( $_POST['event_date'] ),
			'venue'        => sanitize_text_field( $_POST['venue_info'] ),
			'guest_limit'  => intval( $_POST['guest_limit'] ),
			'host_email'   => sanitize_email( $_POST['host_email'] ),
			'host_notes'   => wp_kses_post( wp_unslash( $_POST['host_notes'] ) ),
			'community_id' => $community_id,
			// Privacy will be inherited from community - no need to pass it
		);

		$event_manager = $this->get_event_manager();
		$event_id      = $event_manager->create_event( $event_data );

		if ( ! is_wp_error( $event_id ) ) {
			$created_event = $event_manager->get_event( $event_id );

			$creation_data = array(
				'event_id'    => $event_id,
				'event_url'   => home_url( '/events/' . $created_event->slug ),
				'event_title' => $created_event->title,
			);
			set_transient( 'partyminder_community_event_created_' . get_current_user_id(), $creation_data, 300 );

			wp_send_json_success(
				array(
					'event_id'  => $event_id,
					'message'   => __( 'Community event created successfully!', 'partyminder' ),
					'event_url' => home_url( '/events/' . $created_event->slug ),
				)
			);
		} else {
			wp_send_json_error( $event_id->get_error_message() );
		}
	}

	private function handle_cover_image_upload( $file, $event_id ) {
		// Validate file
		$validation_result = PartyMinder_Settings::validate_uploaded_file( $file );
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Use WordPress built-in upload handling
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$uploaded_file = wp_handle_upload( $file, array( 'test_form' => false ) );

		if ( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
			// Update event with the image URL
			global $wpdb;
			$events_table = $wpdb->prefix . 'partyminder_events';
			$wpdb->update(
				$events_table,
				array( 'featured_image' => $uploaded_file['url'] ),
				array( 'id' => $event_id ),
				array( '%s' ),
				array( '%d' )
			);

			return $uploaded_file;
		} else {
			return new WP_Error( 'upload_failed', __( 'File upload failed.', 'partyminder' ) );
		}
	}
}