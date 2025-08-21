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
			'privacy'     => sanitize_text_field( $_POST['privacy'] ?? 'public' ),
		);

		$community_manager = $this->get_community_manager();
		$community_id      = $community_manager->create_community( $community_data );

		if ( ! is_wp_error( $community_id ) ) {
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
			'privacy'     => sanitize_text_field( $_POST['privacy'] ?? 'public' ),
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

		wp_send_json_success(
			array(
				'members'    => $members,
				'user_role'  => $user_role,
				'can_manage' => ( $user_role === 'admin' || current_user_can( 'manage_options' ) ),
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

		$invitation_url = add_query_arg(
			array(
				'invitation' => $invitation_token,
				'community'  => $community_id,
			),
			home_url( '/communities/' . $community->slug )
		);

		$subject = sprintf( __( 'Invitation to join %s', 'partyminder' ), $community->name );
		$message = sprintf(
			__( "You've been invited to join the %1\$s community!\n\nCommunity Description:\n%2\$s\n\nAccept your invitation here: %3\$s", 'partyminder' ),
			$community->name,
			$community->description,
			$invitation_url
		);

		$sent = wp_mail( $email, $subject, $message );

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

		$invitation_id = sanitize_text_field( $_POST['invitation_id'] );
		if ( ! $invitation_id ) {
			wp_send_json_error( __( 'Invitation ID is required.', 'partyminder' ) );
			return;
		}

		global $wpdb;
		$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';

		$invitation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $invitations_table WHERE invitation_id = %s",
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
			array( 'invitation_id' => $invitation_id ),
			array( '%s' )
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
}
