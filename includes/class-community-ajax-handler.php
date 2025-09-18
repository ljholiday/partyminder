<?php

class PartyMinder_Community_Ajax_Handler {

	private $community_manager;

	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'wp_ajax_partyminder_join_community', array( $this, 'ajax_join_community' ) );
		add_action( 'wp_ajax_nopriv_partyminder_join_community', array( $this, 'ajax_join_community' ) );
		add_action( 'wp_ajax_partyminder_leave_community', array( $this, 'ajax_leave_community' ) );
		add_action( 'wp_ajax_partyminder_create_community', array( $this, 'ajax_create_community' ) );
		add_action( 'wp_ajax_partyminder_update_community', array( $this, 'ajax_update_community' ) );
		add_action( 'wp_ajax_partyminder_get_community_members', array( $this, 'ajax_get_community_members' ) );
		add_action( 'wp_ajax_partyminder_update_member_role', array( $this, 'ajax_update_member_role' ) );
		add_action( 'wp_ajax_partyminder_remove_member', array( $this, 'ajax_remove_member' ) );
		add_action( 'wp_ajax_partyminder_send_invitation', array( $this, 'ajax_send_invitation' ) );
		add_action( 'wp_ajax_partyminder_get_community_invitations', array( $this, 'ajax_get_community_invitations' ) );
		add_action( 'wp_ajax_partyminder_cancel_invitation', array( $this, 'ajax_cancel_invitation' ) );
		add_action( 'wp_ajax_partyminder_accept_invitation', array( $this, 'ajax_accept_invitation' ) );
		add_action( 'wp_ajax_nopriv_partyminder_accept_invitation', array( $this, 'ajax_accept_invitation' ) );
		add_action( 'wp_ajax_partyminder_load_community_invitation_form', array( $this, 'ajax_load_community_invitation_form' ) );
		add_action( 'wp_ajax_nopriv_partyminder_load_community_invitation_form', array( $this, 'ajax_load_community_invitation_form' ) );
		add_action( 'wp_ajax_partyminder_accept_community_invitation', array( $this, 'ajax_accept_community_invitation' ) );
		add_action( 'wp_ajax_nopriv_partyminder_accept_community_invitation', array( $this, 'ajax_accept_community_invitation' ) );
		add_action( 'wp_ajax_partyminder_load_community_join_form', array( $this, 'ajax_load_community_join_form' ) );
		add_action( 'wp_ajax_nopriv_partyminder_load_community_join_form', array( $this, 'ajax_load_community_join_form' ) );
	}

	private function get_community_manager() {
		if ( ! $this->community_manager ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
			$this->community_manager = new PartyMinder_Community_Manager();
		}
		return $this->community_manager;
	}

	public function ajax_join_community() {
		check_ajax_referer( 'partyminder_community_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in to join a community.', 'partyminder' ) );
			return;
		}

		$community_id = intval( $_POST['community_id'] );
		$current_user = wp_get_current_user();

		if ( ! $community_id ) {
			wp_send_json_error( __( 'Invalid community.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();

		$community = $community_manager->get_community( $community_id );
		if ( ! $community ) {
			wp_send_json_error( __( 'Community not found.', 'partyminder' ) );
			return;
		}

		if ( $community_manager->is_member( $community_id, $current_user->ID ) ) {
			wp_send_json_error( __( 'You are already a member of this community.', 'partyminder' ) );
			return;
		}

		$member_data = array(
			'user_id'      => $current_user->ID,
			'email'        => $current_user->user_email,
			'display_name' => $current_user->display_name,
			'role'         => 'member',
		);

		$result = $community_manager->add_member( $community_id, $member_data );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message'      => sprintf( __( 'Welcome to %s!', 'partyminder' ), $community->name ),
					'redirect_url' => home_url( '/communities/' . $community->slug ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to join community. Please try again.', 'partyminder' ) );
		}
	}

	public function ajax_leave_community() {
		check_ajax_referer( 'partyminder_community_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$community_id = intval( $_POST['community_id'] );
		$current_user = wp_get_current_user();

		if ( ! $community_id ) {
			wp_send_json_error( __( 'Invalid community.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();

		if ( ! $community_manager->is_member( $community_id, $current_user->ID ) ) {
			wp_send_json_error( __( 'You are not a member of this community.', 'partyminder' ) );
			return;
		}

		$user_role = $community_manager->get_member_role( $community_id, $current_user->ID );
		if ( $user_role === 'admin' ) {
			$admin_count = $community_manager->get_admin_count( $community_id );
			if ( $admin_count <= 1 ) {
				wp_send_json_error( __( 'You cannot leave as you are the only admin. Please promote another member first.', 'partyminder' ) );
				return;
			}
		}

		$result = $community_manager->remove_member( $community_id, $current_user->ID );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message'      => __( 'You have left the community.', 'partyminder' ),
					'redirect_url' => PartyMinder::get_communities_url(),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to leave community. Please try again.', 'partyminder' ) );
		}
	}

	public function ajax_create_community() {
		check_ajax_referer( 'create_partyminder_community', 'partyminder_community_nonce' );

		$form_errors = array();
		if ( empty( $_POST['name'] ) ) {
			$form_errors[] = __( 'Community name is required.', 'partyminder' );
		}

		if ( ! empty( $form_errors ) ) {
			wp_send_json_error( implode( ' ', $form_errors ) );
		}

		$community_data = array(
			'name'        => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
			'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
			'visibility'  => sanitize_text_field( $_POST['visibility'] ?? 'public' ),
		);

		$community_manager = $this->get_community_manager();
		$community_id      = $community_manager->create_community( $community_data );

		if ( ! is_wp_error( $community_id ) ) {
			// Handle cover image upload
			if ( isset( $_FILES['cover_image'] ) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK ) {
				$upload_result = $this->handle_cover_image_upload( $_FILES['cover_image'], $community_id );
				if ( is_wp_error( $upload_result ) ) {
					// Log error but don't fail the community creation
					error_log( 'Cover image upload failed: ' . $upload_result->get_error_message() );
				}
			}
			$created_community = $community_manager->get_community( $community_id );

			$creation_data = array(
				'community_id'   => $community_id,
				'community_url'  => home_url( '/communities/' . $created_community->slug ),
				'community_name' => $created_community->name,
			);
			set_transient( 'partyminder_community_created_' . get_current_user_id(), $creation_data, 300 );

			wp_send_json_success(
				array(
					'community_id'  => $community_id,
					'message'       => __( 'Community created successfully!', 'partyminder' ),
					'community_url' => home_url( '/communities/' . $created_community->slug ),
				)
			);
		} else {
			wp_send_json_error( $community_id->get_error_message() );
		}
	}

	public function ajax_update_community() {
		check_ajax_referer( 'partyminder_community_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$community_id = intval( $_POST['community_id'] );
		if ( ! $community_id ) {
			wp_send_json_error( __( 'Community ID is required.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();
		$community         = $community_manager->get_community( $community_id );
		if ( ! $community ) {
			wp_send_json_error( __( 'Community not found.', 'partyminder' ) );
			return;
		}

		$current_user = wp_get_current_user();
		$user_role    = $community_manager->get_member_role( $community_id, $current_user->ID );

		if ( $user_role !== 'admin' && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to update this community.', 'partyminder' ) );
			return;
		}

		$form_errors = array();
		if ( empty( $_POST['name'] ) ) {
			$form_errors[] = __( 'Community name is required.', 'partyminder' );
		}

		if ( ! empty( $form_errors ) ) {
			wp_send_json_error( implode( ' ', $form_errors ) );
			return;
		}

		$community_data = array(
			'id'          => $community_id,
			'name'        => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
			'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
			'visibility'  => sanitize_text_field( $_POST['visibility'] ?? 'public' ),
		);

		$result = $community_manager->update_community( $community_data );

		if ( $result !== false ) {
			$updated_community = $community_manager->get_community( $community_id );
			wp_send_json_success(
				array(
					'message'       => __( 'Community updated successfully!', 'partyminder' ),
					'community_url' => home_url( '/communities/' . $updated_community->slug ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to update community. Please try again.', 'partyminder' ) );
		}
	}

	public function ajax_get_community_members() {
		check_ajax_referer( 'partyminder_community_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$community_id = intval( $_POST['community_id'] );
		if ( ! $community_id ) {
			wp_send_json_error( __( 'Community ID is required.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();
		$current_user      = wp_get_current_user();

		if ( ! $community_manager->is_member( $community_id, $current_user->ID ) ) {
			wp_send_json_error( __( 'You must be a member to view the member list.', 'partyminder' ) );
			return;
		}

		$members   = $community_manager->get_community_members( $community_id );
		$user_role = $community_manager->get_member_role( $community_id, $current_user->ID );
		
		// Generate HTML for each member using our member display function
		$members_html = '';
		if ( ! empty( $members ) ) {
			$members_html = '<div class="pm-grid pm-grid-2 pm-gap">';
			foreach ( $members as $member ) {
				$bio_text = ! empty( $member->bio ) ? 
					( strlen( $member->bio ) > 80 ? substr( $member->bio, 0, 80 ) . '...' : $member->bio ) : 
					__( 'Community member', 'partyminder' );
				
				$members_html .= '<div class="pm-section" data-member-id="' . esc_attr( $member->id ) . '">';
				$members_html .= '<div class="pm-flex pm-flex-between" style="align-items: flex-start;">';
				$members_html .= '<div class="pm-member-info" style="flex: 1; min-width: 0;">';
				$members_html .= PartyMinder_Member_Display::get_member_display( $member->user_id, array( 'avatar_size' => 40 ) );
				$members_html .= '<div class="pm-text-muted pm-text-sm pm-mt-1">' . esc_html( $bio_text ) . '</div>';
				$members_html .= '</div>';
				$members_html .= '<div class="pm-ml-4" style="flex-shrink: 0;">';
				$members_html .= '<div class="pm-mb-2"><span class="pm-badge pm-badge-' . ( $member->role === 'admin' ? 'primary' : 'secondary' ) . '">' . esc_html( $member->role ) . '</span></div>';
				$members_html .= '<div><button class="pm-btn pm-btn-danger pm-btn-sm remove-btn" data-member-id="' . esc_attr( $member->id ) . '" data-member-name="' . esc_attr( $member->display_name ?: $member->email ) . '">' . __( 'Remove', 'partyminder' ) . '</button></div>';
				$members_html .= '</div>';
				$members_html .= '</div>';
				$members_html .= '</div>';
			}
			$members_html .= '</div>';
		}

		wp_send_json_success(
			array(
				'members'      => $members,
				'members_html' => $members_html,
				'user_role'    => $user_role,
				'can_manage'   => ( $user_role === 'admin' || current_user_can( 'manage_options' ) ),
			)
		);
	}

	public function ajax_update_member_role() {
		check_ajax_referer( 'partyminder_community_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$community_id = intval( $_POST['community_id'] );
		$member_id    = intval( $_POST['member_id'] );
		$new_role     = sanitize_text_field( $_POST['role'] );

		if ( ! $community_id || ! $member_id || ! $new_role ) {
			wp_send_json_error( __( 'All fields are required.', 'partyminder' ) );
			return;
		}

		if ( ! in_array( $new_role, array( 'member', 'moderator', 'admin' ) ) ) {
			wp_send_json_error( __( 'Invalid role.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();
		$current_user      = wp_get_current_user();
		$user_role         = $community_manager->get_member_role( $community_id, $current_user->ID );

		if ( $user_role !== 'admin' && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to change member roles.', 'partyminder' ) );
			return;
		}

		$result = $community_manager->update_member_role( $community_id, $member_id, $new_role );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message' => __( 'Member role updated successfully.', 'partyminder' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to update member role.', 'partyminder' ) );
		}
	}

	public function ajax_remove_member() {
		check_ajax_referer( 'partyminder_community_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$community_id = intval( $_POST['community_id'] );
		$member_id    = intval( $_POST['member_id'] );

		if ( ! $community_id || ! $member_id ) {
			wp_send_json_error( __( 'Community ID and member ID are required.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();
		$current_user      = wp_get_current_user();
		$user_role         = $community_manager->get_member_role( $community_id, $current_user->ID );

		if ( $user_role !== 'admin' && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to remove members.', 'partyminder' ) );
			return;
		}

		$member_role = $community_manager->get_member_role( $community_id, $member_id );
		if ( $member_role === 'admin' ) {
			$admin_count = $community_manager->get_admin_count( $community_id );
			if ( $admin_count <= 1 ) {
				wp_send_json_error( __( 'Cannot remove the only admin. Please promote another member first.', 'partyminder' ) );
				return;
			}
		}

		$result = $community_manager->remove_member( $community_id, $member_id );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message' => __( 'Member removed successfully.', 'partyminder' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to remove member.', 'partyminder' ) );
		}
	}

	public function ajax_send_invitation() {
		check_ajax_referer( 'partyminder_community_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$community_id = intval( $_POST['community_id'] );
		$email        = sanitize_email( $_POST['email'] );

		if ( ! $community_id || ! $email ) {
			wp_send_json_error( __( 'Community ID and email are required.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();
		$current_user      = wp_get_current_user();
		$user_role         = $community_manager->get_member_role( $community_id, $current_user->ID );

		if ( ! in_array( $user_role, array( 'admin', 'moderator' ) ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to send invitations.', 'partyminder' ) );
			return;
		}

		$community = $community_manager->get_community( $community_id );
		if ( ! $community ) {
			wp_send_json_error( __( 'Community not found.', 'partyminder' ) );
			return;
		}

		global $wpdb;
		$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $invitations_table WHERE community_id = %d AND invited_email = %s AND status = 'pending'",
				$community_id,
				$email
			)
		);

		if ( $existing ) {
			wp_send_json_error( __( 'This email has already been invited.', 'partyminder' ) );
			return;
		}

		if ( $community_manager->is_member_by_email( $community_id, $email ) ) {
			wp_send_json_error( __( 'This email is already a member of the community.', 'partyminder' ) );
			return;
		}

		$invitation_token = wp_generate_uuid4();
		$expires_at       = date( 'Y-m-d H:i:s', strtotime( '+7 days' ) );
		$result           = $wpdb->insert(
			$invitations_table,
			array(
				'community_id'           => $community_id,
				'invited_by_member_id'   => $current_user->ID,
				'invited_email'          => $email,
				'invitation_token'       => $invitation_token,
				'status'                 => 'pending',
				'expires_at'             => $expires_at,
				'created_at'             => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result === false ) {
			wp_send_json_error( __( 'Failed to create invitation.', 'partyminder' ) );
			return;
		}

		$invitation_url = home_url( '/communities/join?token=' . $invitation_token );

		$subject = sprintf( __( 'You\'re invited to join %s!', 'partyminder' ), $community->name );
		
		// Create HTML email
		$message = $this->create_invitation_email_html( $community, $invitation_url, $email );
		
		// Set content type to HTML
		add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
		
		$sent = wp_mail( $email, $subject, $message );
		
		// Reset content type
		remove_filter( 'wp_mail_content_type', function() { return 'text/html'; } );

		if ( $sent ) {
			wp_send_json_success(
				array(
					'message' => __( 'Invitation sent successfully!', 'partyminder' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to send invitation email.', 'partyminder' ) );
		}
	}

	public function ajax_get_community_invitations() {
		check_ajax_referer( 'partyminder_community_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$community_id = intval( $_POST['community_id'] );
		if ( ! $community_id ) {
			wp_send_json_error( __( 'Community ID is required.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();
		$current_user      = wp_get_current_user();
		$user_role         = $community_manager->get_member_role( $community_id, $current_user->ID );

		if ( ! in_array( $user_role, array( 'admin', 'moderator' ) ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to view invitations.', 'partyminder' ) );
			return;
		}

		global $wpdb;
		$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';

		$invitations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ci.*, u.display_name as invited_by_name 
             FROM $invitations_table ci 
             LEFT JOIN {$wpdb->users} u ON ci.invited_by_member_id = u.ID 
             WHERE ci.community_id = %d 
             ORDER BY ci.created_at DESC",
				$community_id
			)
		);

		wp_send_json_success(
			array(
				'invitations' => $invitations,
			)
		);
	}

	public function ajax_cancel_invitation() {
		check_ajax_referer( 'partyminder_community_action', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in.', 'partyminder' ) );
			return;
		}

		$invitation_id = intval( $_POST['invitation_id'] );
		if ( ! $invitation_id ) {
			wp_send_json_error( __( 'Invitation ID is required.', 'partyminder' ) );
			return;
		}

		global $wpdb;
		$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';

		$invitation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $invitations_table WHERE id = %d",
				$invitation_id
			)
		);

		if ( ! $invitation ) {
			wp_send_json_error( __( 'Invitation not found.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();
		$current_user      = wp_get_current_user();
		$user_role         = $community_manager->get_member_role( $invitation->community_id, $current_user->ID );

		if ( ! in_array( $user_role, array( 'admin', 'moderator' ) ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to cancel invitations.', 'partyminder' ) );
			return;
		}

		$result = $wpdb->delete(
			$invitations_table,
			array( 'id' => $invitation_id ),
			array( '%d' )
		);

		if ( $result !== false ) {
			wp_send_json_success(
				array(
					'message' => __( 'Invitation cancelled successfully.', 'partyminder' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to cancel invitation.', 'partyminder' ) );
		}
	}

	private function handle_cover_image_upload( $file, $community_id ) {
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
			// Update community with the image URL
			global $wpdb;
			$communities_table = $wpdb->prefix . 'partyminder_communities';
			$wpdb->update(
				$communities_table,
				array( 'featured_image' => $uploaded_file['url'] ),
				array( 'id' => $community_id ),
				array( '%s' ),
				array( '%d' )
			);

			return $uploaded_file;
		} else {
			return new WP_Error( 'upload_failed', __( 'File upload failed.', 'partyminder' ) );
		}
	}

	public function ajax_accept_invitation() {
		check_ajax_referer( 'partyminder_accept_invitation', 'nonce' );

		$token = sanitize_text_field( $_POST['token'] ?? '' );
		$community_id = intval( $_POST['community_id'] ?? 0 );

		if ( ! $token || ! $community_id ) {
			wp_send_json_error( __( 'Invalid invitation parameters.', 'partyminder' ) );
			return;
		}

		// Find the invitation
		global $wpdb;
		$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';
		$invitation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $invitations_table WHERE invitation_token = %s AND community_id = %d AND status = 'pending' AND expires_at > NOW()",
				$token,
				$community_id
			)
		);

		if ( ! $invitation ) {
			wp_send_json_error( __( 'Invalid or expired invitation.', 'partyminder' ) );
			return;
		}

		$community_manager = $this->get_community_manager();
		$community = $community_manager->get_community( $community_id );
		if ( ! $community ) {
			wp_send_json_error( __( 'Community not found.', 'partyminder' ) );
			return;
		}

		// Handle both logged-in and non-logged-in users
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			
			// Check if the logged-in user's email matches the invitation
			if ( $current_user->user_email !== $invitation->invited_email ) {
				wp_send_json_error( sprintf( __( 'This invitation is for %s. Please log in with that account.', 'partyminder' ), $invitation->invited_email ) );
				return;
			}

			// Check if already a member
			if ( $community_manager->is_member( $community_id, $current_user->ID ) ) {
				wp_send_json_error( __( 'You are already a member of this community.', 'partyminder' ) );
				return;
			}

			// Add user as member
			$member_data = array(
				'user_id'      => $current_user->ID,
				'email'        => $current_user->user_email,
				'display_name' => $current_user->display_name,
				'role'         => 'member',
			);

			$result = $community_manager->add_member( $community_id, $member_data );
		} else {
			wp_send_json_error( __( 'Please log in to accept this invitation.', 'partyminder' ) );
			return;
		}

		if ( $result ) {
			// Mark invitation as accepted
			$wpdb->update(
				$invitations_table,
				array( 'status' => 'accepted', 'accepted_at' => current_time( 'mysql' ) ),
				array( 'id' => $invitation->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			wp_send_json_success(
				array(
					'message' => sprintf( __( 'Welcome to %s!', 'partyminder' ), $community->name ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to join community. Please try again.', 'partyminder' ) );
		}
	}

	/**
	 * Create HTML email for community invitation
	 * REUSES event email template structure and styling
	 */
	private function create_invitation_email_html( $community, $invitation_url, $email ) {
		$site_name = get_bloginfo( 'name' );
		$site_url = home_url();
		$primary_color = get_option( 'partyminder_primary_color', '#667eea' );

		// REUSE event email data structure
		$user_exists = email_exists( $email );
		$signup_url = wp_registration_url();
		$accept_url = add_query_arg( array( 'action' => 'accept' ), $invitation_url );
		
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo esc_html( sprintf( __( 'Invitation to join %s', 'partyminder' ), $community->name ) ); ?></title>
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f8fafc; }
				.container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
				.header { background: linear-gradient(135deg, <?php echo esc_attr( $primary_color ); ?>, #764ba2); padding: 40px 30px; text-align: center; }
				.header h1 { color: white; margin: 0; font-size: 28px; font-weight: 600; }
				.content { padding: 40px 30px; }
				.community-info { background: #f8fafc; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid <?php echo esc_attr( $primary_color ); ?>; }
				.community-name { font-size: 20px; font-weight: 600; color: #2d3748; margin: 0 0 10px 0; }
				.community-description { color: #4a5568; line-height: 1.6; margin: 0; }
				.button-container { text-align: center; margin: 35px 0; }
				.accept-button { display: inline-block; background: <?php echo esc_attr( $primary_color ); ?>; color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; transition: all 0.2s ease; }
				.accept-button:hover { background: #5a67d8; transform: translateY(-1px); }
				.signup-section { background: #edf2f7; padding: 25px; border-radius: 8px; margin: 25px 0; text-align: center; }
				.signup-button { display: inline-block; background: #48bb78; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500; margin-top: 15px; }
				.footer { background: #2d3748; color: #a0aec0; padding: 25px 30px; text-align: center; font-size: 14px; }
				.footer a { color: #90cdf4; text-decoration: none; }
				@media (max-width: 600px) {
					.content, .header { padding: 25px 20px; }
					.header h1 { font-size: 24px; }
				}
			</style>
		</head>
		<body>
			<div class="container">
				<!-- Header -->
				<div class="header">
					<h1><?php _e( 'You\'re Invited!', 'partyminder' ); ?></h1>
				</div>

				<!-- Content -->
				<div class="content">
					<p><?php printf( __( 'Hello! You\'ve been invited to join the <strong>%s</strong> community on %s.', 'partyminder' ), esc_html( $community->name ), esc_html( $site_name ) ); ?></p>

					<!-- Community Info -->
					<div class="community-info">
						<div class="community-name"><?php echo esc_html( $community->name ); ?></div>
						<?php if ( $community->description ) : ?>
							<div class="community-description"><?php echo nl2br( esc_html( $community->description ) ); ?></div>
						<?php endif; ?>

						<div style="margin: 15px 0;">
							<span style="background: #e2e8f0; padding: 4px 12px; border-radius: 16px; font-size: 14px; color: #4a5568;">
								<?php printf( __( '%d members', 'partyminder' ), $community->member_count ); ?>
							</span>
							<span style="background: #bee3f8; padding: 4px 12px; border-radius: 16px; font-size: 14px; color: #2c5282; margin-left: 8px;">
								<?php echo esc_html( ucfirst( $community->visibility ) ); ?>
							</span>
						</div>
					</div>

					<!-- Member benefits section (REUSE event pattern) -->
					<div style="background: #edf2f7; padding: 20px; border-radius: 8px; margin: 20px 0;">
						<h3 style="margin-top: 0; color: #2d3748;"><?php _e( 'What you\'ll get:', 'partyminder' ); ?></h3>
						<ul style="color: #4a5568; line-height: 1.8; padding-left: 20px;">
							<li><?php _e( 'Connect with like-minded community members', 'partyminder' ); ?></li>
							<li><?php _e( 'Get invited to exclusive community events', 'partyminder' ); ?></li>
							<li><?php _e( 'Participate in community discussions', 'partyminder' ); ?></li>
							<li><?php _e( 'Access member-only content and resources', 'partyminder' ); ?></li>
						</ul>
					</div>

					<!-- CTA Buttons (REUSE event CTA structure) -->
					<div style="text-align: center; margin: 35px 0;">
						<a href="<?php echo esc_url( $accept_url ); ?>" class="accept-button">
							<?php _e( 'Join Community', 'partyminder' ); ?>
						</a>
						<p style="margin-top: 20px; font-size: 14px; color: #718096;">
							<?php _e( 'Or', 'partyminder' ); ?>
							<a href="<?php echo esc_url( $invitation_url ); ?>" style="color: #667eea;">
								<?php _e( 'view invitation details', 'partyminder' ); ?>
							</a>
						</p>
					</div>

					<?php if ( ! $user_exists ) : ?>
					<!-- Signup Section for Non-Members -->
					<div class="signup-section">
						<h3 style="margin: 0 0 10px 0; color: #2d3748;"><?php _e( 'New to our community?', 'partyminder' ); ?></h3>
						<p style="margin: 0 0 15px 0; color: #4a5568;"><?php printf( __( 'Create your free account first, then accept your invitation to join %s.', 'partyminder' ), esc_html( $community->name ) ); ?></p>
						<a href="<?php echo esc_url( add_query_arg( 'redirect_to', urlencode( $invitation_url ), $signup_url ) ); ?>" class="signup-button">
							<?php _e( 'Create Free Account', 'partyminder' ); ?>
						</a>
					</div>
					<?php endif; ?>

					<p style="color: #4a5568; font-size: 14px; line-height: 1.6; margin-top: 30px;">
						<?php _e( 'This invitation will expire in 7 days. If you have any questions, feel free to contact us.', 'partyminder' ); ?>
					</p>
				</div>

				<!-- Footer -->
				<div class="footer">
					<p><?php printf( __( 'This invitation was sent from %s', 'partyminder' ), '<a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a>' ); ?></p>
					<p><?php printf( __( 'If you don\'t want to receive these emails, you can <a href="%s">unsubscribe here</a>.', 'partyminder' ), '#' ); ?></p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Load community invitation form (REUSES event RSVP form loading pattern)
	 */
	public function ajax_load_community_invitation_form() {
		check_ajax_referer( 'partyminder_community_invitation', 'nonce' );

		$token = sanitize_text_field( $_POST['token'] ?? '' );
		if ( ! $token ) {
			wp_send_json_error( __( 'No invitation token provided.', 'partyminder' ) );
			return;
		}

		// REUSE event pattern: get invitation and validate
		$community_manager = $this->get_community_manager();
		$invitation = $community_manager->get_invitation_by_token( $token );

		if ( ! $invitation ) {
			wp_send_json_error( __( 'Invalid invitation token.', 'partyminder' ) );
			return;
		}

		// REUSE event template loading pattern
		ob_start();
		include PARTYMINDER_PLUGIN_DIR . 'templates/forms/modal-community-invitation-form.php';
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html' => $html,
			'invitation' => $invitation
		) );
	}

	/**
	 * Accept community invitation (REUSES event acceptance pattern)
	 * Communities require login - no guest memberships like events
	 */
	public function ajax_accept_community_invitation() {
		check_ajax_referer( 'partyminder_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You must be logged in to join communities. Please login or create an account first.', 'partyminder' ) );
			return;
		}

		$token = sanitize_text_field( $_POST['invitation_token'] ?? '' );
		$community_id = intval( $_POST['community_id'] ?? 0 );
		$member_name = sanitize_text_field( $_POST['member_name'] ?? '' );
		$member_email = sanitize_email( $_POST['member_email'] ?? '' );
		$member_bio = sanitize_textarea_field( $_POST['member_bio'] ?? '' );

		// Validate required fields - token is only required for invitation-based joins
		if ( empty( trim( $member_name ) ) || empty( trim( $member_email ) ) ) {
			wp_send_json_error( __( 'All required fields must be filled.', 'partyminder' ) );
			return;
		}

		if ( ! $community_id ) {
			wp_send_json_error( __( 'Community ID is required.', 'partyminder' ) );
			return;
		}

		$current_user = wp_get_current_user();
		$community_manager = $this->get_community_manager();

		if ( $token ) {
			// Token-based invitation
			$member_data = array(
				'bio' => $member_bio,
				'display_name' => $member_name,
			);

			$result = $community_manager->accept_community_invitation( $token, $current_user->ID, $member_data );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
				return;
			}

			$invitation = $community_manager->get_invitation_by_token( $token );
			$community = $community_manager->get_community( $invitation->community_id );
		} else {
			// Generic community join (no token)
			$community = $community_manager->get_community( $community_id );
			if ( ! $community ) {
				wp_send_json_error( __( 'Community not found.', 'partyminder' ) );
				return;
			}

			// Check if already a member
			if ( $community_manager->is_member( $community_id, $current_user->ID ) ) {
				wp_send_json_error( __( 'You are already a member of this community.', 'partyminder' ) );
				return;
			}

			// Add user as member
			$member_data = array(
				'user_id'      => $current_user->ID,
				'email'        => $member_email,
				'display_name' => $member_name,
				'bio'          => $member_bio,
				'role'         => 'member',
			);

			$result = $community_manager->add_member( $community_id, $member_data );

			if ( ! $result ) {
				wp_send_json_error( __( 'Failed to join community. Please try again.', 'partyminder' ) );
				return;
			}
		}

		wp_send_json_success( array(
			'message' => sprintf( __( 'Welcome to %s!', 'partyminder' ), $community->name ),
			'redirect_url' => home_url( '/communities/' . $community->slug )
		) );
	}

	/**
	 * Load community join form for generic join links (REUSES invitation form pattern)
	 */
	public function ajax_load_community_join_form() {
		check_ajax_referer( 'partyminder_community_invitation', 'nonce' );

		$community_id = intval( $_POST['community_id'] ?? 0 );
		if ( ! $community_id ) {
			wp_send_json_error( __( 'No community ID provided.', 'partyminder' ) );
			return;
		}

		// Get community details
		$community_manager = $this->get_community_manager();
		$community = $community_manager->get_community( $community_id );

		if ( ! $community ) {
			wp_send_json_error( __( 'Community not found.', 'partyminder' ) );
			return;
		}

		// REUSE the existing invitation form template but without token
		ob_start();
		$token = null; // No token for generic joins
		$GLOBALS['partyminder_current_community'] = $community; // Make community available to template
		include PARTYMINDER_PLUGIN_DIR . 'templates/forms/modal-community-invitation-form.php';
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html' => $html,
			'community' => $community
		) );
	}
}
