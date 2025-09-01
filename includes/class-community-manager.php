<?php

/**
 * PartyMinder Community Manager
 *
 * Handles community creation, membership, and management
 * Follows the same patterns as Event Manager and Conversation Manager
 */
class PartyMinder_Community_Manager {

	public function __construct() {
		// No WordPress hooks needed - pure custom table system
	}

	/**
	 * Create a new community
	 */
	public function create_community( $community_data ) {
		global $wpdb;

		// Check user permissions
		if ( ! PartyMinder_Feature_Flags::can_user_create_community() ) {
			return new WP_Error( 'permission_denied', __( 'You cannot create communities', 'partyminder' ) );
		}

		// Validate required fields
		if ( empty( $community_data['name'] ) ) {
			return new WP_Error( 'missing_data', __( 'Community name is required', 'partyminder' ) );
		}

		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in to create a community', 'partyminder' ) );
		}

		// Generate unique slug
		$slug = $this->generate_unique_slug( $community_data['name'] );

		// Generate AT Protocol DID if AT Protocol is enabled
		$at_protocol_did = '';
		if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) {
			$at_protocol_did = $this->generate_community_did( $slug );
		}

		// Insert community data
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$result            = $wpdb->insert(
			$communities_table,
			array(
				'name'              => sanitize_text_field( $community_data['name'] ),
				'slug'              => $slug,
				'description'       => wp_kses_post( wp_unslash( $community_data['description'] ?? '' ) ),
				'privacy'           => $this->validate_privacy_setting( $community_data['privacy'] ?? 'public' ),
				'creator_id'        => $current_user->ID,
				'creator_email'     => $current_user->user_email,
				'settings'          => wp_json_encode( $community_data['settings'] ?? array() ),
				'at_protocol_did'   => $at_protocol_did,
				'requires_approval' => (bool) ( $community_data['requires_approval'] ?? PartyMinder_Feature_Flags::communities_require_approval() ),
				'is_active'         => 1,
				'member_count'      => 1, // Creator is the first member
				'created_at'        => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		if ( $result === false ) {
			$error_msg = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to create community', 'partyminder' );
			error_log( 'PartyMinder Community Creation Error: ' . $error_msg );
			error_log( 'PartyMinder Community Data: ' . print_r( $community_data, true ) );
			return new WP_Error( 'creation_failed', $error_msg );
		}

		$community_id = $wpdb->insert_id;

		// Add creator as admin member
		$member_result = $this->add_member(
			$community_id,
			array(
				'user_id'      => $current_user->ID,
				'email'        => $current_user->user_email,
				'display_name' => $current_user->display_name,
				'role'         => 'admin',
				'status'       => 'active',
			),
			true
		); // Skip permission checks for creator

		if ( is_wp_error( $member_result ) ) {
			error_log( 'PartyMinder: Failed to add creator as member: ' . $member_result->get_error_message() );
			// Don't fail community creation if member addition fails, just log it
		}

		// Generate member DID if AT Protocol is enabled
		if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) {
			try {
				$this->ensure_member_has_did( $current_user->ID, $current_user->user_email );
			} catch ( Exception $e ) {
				error_log( 'PartyMinder: Failed to generate member DID: ' . $e->getMessage() );
				// Don't fail community creation if DID generation fails
			}
		}

		// Auto-create welcome conversation for the community
		require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
		$conversation_manager      = new PartyMinder_Conversation_Manager();
		$welcome_conversation_data = array(
			'name'          => $community_data['name'],
			'creator_id'    => $current_user->ID,
			'creator_name'  => $current_user->display_name ?: $current_user->user_login,
			'creator_email' => $current_user->user_email,
		);
		$conversation_manager->create_community_conversation( $community_id, $welcome_conversation_data );

		return $community_id;
	}

	/**
	 * Get community by ID
	 */
	public function get_community( $community_id ) {
		global $wpdb;

		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$community         = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $communities_table WHERE id = %d AND is_active = 1",
				$community_id
			)
		);

		if ( ! $community ) {
			return null;
		}

		// Parse JSON settings
		$community->settings = json_decode( $community->settings ?: '{}', true );

		return $community;
	}

	/**
	 * Get community by slug
	 */
	public function get_community_by_slug( $slug ) {
		global $wpdb;

		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$community         = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $communities_table WHERE slug = %s AND is_active = 1",
				$slug
			)
		);

		if ( ! $community ) {
			return null;
		}

		// Parse JSON settings
		$community->settings = json_decode( $community->settings ?: '{}', true );

		return $community;
	}

	/**
	 * Get communities for a user
	 */
	public function get_user_communities( $user_id, $limit = 20 ) {
		global $wpdb;

		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$members_table     = $wpdb->prefix . 'partyminder_community_members';

		$communities = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*, m.role, m.joined_at, m.status as member_status
             FROM $communities_table c
             INNER JOIN $members_table m ON c.id = m.community_id
             WHERE m.user_id = %d AND (m.status = 'member' OR m.status = '') AND c.is_active = 1
             ORDER BY m.joined_at DESC
             LIMIT %d",
				$user_id,
				$limit
			)
		);

		return $communities ?: array();
	}

	/**
	 * Get discoverable communities based on user's access level
	 */
	public function get_public_communities( $limit = 20, $offset = 0 ) {
		global $wpdb;

		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$current_user_id = get_current_user_id();
		
		// Build privacy clause - Enhanced privacy with friends and private options
		$privacy_clause = "c.privacy = 'public'";
		if ( $current_user_id && is_user_logged_in() ) {
			$members_table = $wpdb->prefix . 'partyminder_community_members';
			
			$privacy_clause = "(c.privacy = 'public' OR 
                              c.creator_id = $current_user_id OR
                              (c.privacy = 'friends' AND EXISTS(
                                  SELECT 1 FROM $members_table cm1, $members_table cm2 
                                  WHERE cm1.user_id = c.creator_id AND cm2.user_id = $current_user_id 
                                  AND cm1.community_id = cm2.community_id 
                                  AND cm1.status = 'active' AND cm2.status = 'active'
                              )))";
		}

		$communities = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $communities_table c
             WHERE ($privacy_clause) AND c.is_active = 1
             ORDER BY c.member_count DESC, c.created_at DESC
             LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);

		return $communities ?: array();
	}

	/**
	 * Add member to community
	 */
	public function add_member( $community_id, $member_data, $skip_permission_check = false ) {
		global $wpdb;

		if ( ! $skip_permission_check ) {

			// Check if user can join communities
			if ( ! PartyMinder_Feature_Flags::can_user_join_community( $member_data['user_id'] ) ) {
				return new WP_Error( 'permission_denied', __( 'You cannot join communities', 'partyminder' ) );
			}
		}

		// Get community
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}

		// Check if already a member
		$members_table   = $wpdb->prefix . 'partyminder_community_members';
		$existing_member = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $members_table WHERE community_id = %d AND user_id = %d",
				$community_id,
				$member_data['user_id']
			)
		);

		if ( $existing_member ) {
			if ( $existing_member->status === 'active' ) {
				return new WP_Error( 'already_member', __( 'User is already a member', 'partyminder' ) );
			} else {
				// Reactivate existing membership
				$wpdb->update(
					$members_table,
					array(
						'status'    => 'active',
						'joined_at' => current_time( 'mysql' ),
					),
					array( 'id' => $existing_member->id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
				return $existing_member->id;
			}
		}

		// Generate member DID if AT Protocol is enabled
		$member_did = '';
		if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) {
			$member_did = $this->ensure_member_has_did( $member_data['user_id'], $member_data['email'] );
		}

		// Insert member
		$result = $wpdb->insert(
			$members_table,
			array(
				'community_id'    => $community_id,
				'user_id'         => $member_data['user_id'],
				'email'           => sanitize_email( $member_data['email'] ),
				'display_name'    => sanitize_text_field( $member_data['display_name'] ),
				'role'            => sanitize_text_field( $member_data['role'] ?? 'member' ),
				'permissions'     => wp_json_encode( $member_data['permissions'] ?? array() ),
				'status'          => sanitize_text_field( $member_data['status'] ?? 'active' ),
				'at_protocol_did' => $member_did,
				'joined_at'       => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result === false ) {
			$error_msg = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to add member', 'partyminder' );
			return new WP_Error( 'add_member_failed', $error_msg );
		}

		// Update member count
		$this->update_member_count( $community_id );

		return $wpdb->insert_id;
	}


	/**
	 * Generate unique slug for community
	 */
	private function generate_unique_slug( $name ) {
		global $wpdb;

		$base_slug = sanitize_title( $name );
		$slug      = $base_slug;
		$counter   = 1;

		$communities_table = $wpdb->prefix . 'partyminder_communities';

		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $communities_table WHERE slug = %s", $slug ) ) ) {
			$slug = $base_slug . '-' . $counter;
			++$counter;
		}

		return $slug;
	}

	/**
	 * Generate AT Protocol DID for community
	 */
	private function generate_community_did( $slug ) {
		// Generate a deterministic DID based on the community slug
		// Format: did:partyminder:community:{hash}
		$hash = substr( md5( 'community:' . $slug . ':' . time() ), 0, 16 );
		return 'did:partyminder:community:' . $hash;
	}

	/**
	 * Ensure member has AT Protocol DID
	 */
	private function ensure_member_has_did( $user_id, $email ) {
		global $wpdb;

		$identities_table = $wpdb->prefix . 'partyminder_member_identities';

		// Check if user already has a DID
		$existing_identity = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $identities_table WHERE user_id = %d",
				$user_id
			)
		);

		if ( $existing_identity ) {
			return $existing_identity->at_protocol_did;
		}

		// Generate new DID for user
		$user_hash = substr( md5( 'user:' . $user_id . ':' . $email . ':' . time() ), 0, 16 );
		$did       = 'did:partyminder:user:' . $user_hash;

		// Create identity record
		$user         = get_user_by( 'id', $user_id );
		$display_name = $user ? $user->display_name : 'User';

		$wpdb->insert(
			$identities_table,
			array(
				'user_id'         => $user_id,
				'email'           => $email,
				'display_name'    => $display_name,
				'at_protocol_did' => $did,
				'is_verified'     => 0,
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return $did;
	}

	/**
	 * Update community member count
	 */
	private function update_member_count( $community_id ) {
		global $wpdb;

		$members_table     = $wpdb->prefix . 'partyminder_community_members';
		$communities_table = $wpdb->prefix . 'partyminder_communities';

		$member_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $members_table WHERE community_id = %d AND status = 'active'",
				$community_id
			)
		);

		$wpdb->update(
			$communities_table,
			array( 'member_count' => $member_count ),
			array( 'id' => $community_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Check if user is member of community
	 */
	public function is_member( $community_id, $user_id ) {
		global $wpdb;

		$members_table = $wpdb->prefix . 'partyminder_community_members';

		$member = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $members_table WHERE community_id = %d AND user_id = %d AND status = 'active'",
				$community_id,
				$user_id
			)
		);

		return $member !== null;
	}

	/**
	 * Check if email is already a member of community
	 */
	public function is_member_by_email( $community_id, $email ) {
		global $wpdb;

		$members_table = $wpdb->prefix . 'partyminder_community_members';

		$member = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $members_table WHERE community_id = %d AND email = %s AND status = 'active'",
				$community_id,
				$email
			)
		);

		return $member !== null;
	}

	/**
	 * Get member role in community
	 */
	public function get_member_role( $community_id, $user_id ) {
		global $wpdb;

		$members_table = $wpdb->prefix . 'partyminder_community_members';

		$role = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT role FROM $members_table WHERE community_id = %d AND user_id = %d AND status = 'active'",
				$community_id,
				$user_id
			)
		);

		return $role ?: null;
	}

	/**
	 * Update community settings
	 */
	public function update_community( $community_id, $update_data ) {
		global $wpdb;

		// Get community
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}

		// Check permissions - only admins can update community settings
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in', 'partyminder' ) );
		}

		$user_role = $this->get_member_role( $community_id, $current_user->ID );
		if ( $user_role !== 'admin' ) {
			return new WP_Error( 'permission_denied', __( 'Only community admins can update settings', 'partyminder' ) );
		}

		// Prepare update data
		$allowed_fields = array( 'description', 'privacy', 'featured_image', 'settings' );
		$update_values  = array();
		$update_formats = array();

		foreach ( $allowed_fields as $field ) {
			if ( isset( $update_data[ $field ] ) ) {
				switch ( $field ) {
					case 'description':
						$update_values[ $field ] = wp_kses_post( $update_data[ $field ] );
						$update_formats[]        = '%s';
						break;
					case 'privacy':
						$allowed_privacy = array( 'public', 'private' );
						if ( in_array( $update_data[ $field ], $allowed_privacy ) ) {
							$update_values[ $field ] = $update_data[ $field ];
							$update_formats[]        = '%s';
						}
						break;
					case 'featured_image':
						$update_values[ $field ] = esc_url_raw( $update_data[ $field ] );
						$update_formats[]        = '%s';
						break;
					case 'settings':
						$update_values[ $field ] = wp_json_encode( $update_data[ $field ] );
						$update_formats[]        = '%s';
						break;
				}
			}
		}

		if ( empty( $update_values ) ) {
			return new WP_Error( 'no_data', __( 'No valid update data provided', 'partyminder' ) );
		}

		// Add updated timestamp
		$update_values['updated_at'] = current_time( 'mysql' );
		$update_formats[]            = '%s';

		// Update community
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$result            = $wpdb->update(
			$communities_table,
			$update_values,
			array( 'id' => $community_id ),
			$update_formats,
			array( '%d' )
		);

		if ( $result === false ) {
			$error_msg = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to update community', 'partyminder' );
			return new WP_Error( 'update_failed', $error_msg );
		}

		return true;
	}

	/**
	 * Get community members with pagination
	 */
	public function get_community_members( $community_id, $limit = 20, $offset = 0 ) {
		global $wpdb;

		// Get community
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}

		$members_table = $wpdb->prefix . 'partyminder_community_members';
		$users_table   = $wpdb->users;

		$members = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.*, u.user_login, u.user_nicename, u.user_registered
             FROM $members_table m
             LEFT JOIN $users_table u ON m.user_id = u.ID
             WHERE m.community_id = %d AND m.status = 'active'
             ORDER BY m.role = 'admin' DESC, m.joined_at ASC
             LIMIT %d OFFSET %d",
				$community_id,
				$limit,
				$offset
			)
		);

		return $members ?: array();
	}

	/**
	 * Get admin count for a community
	 */
	public function get_admin_count( $community_id ) {
		global $wpdb;

		$members_table = $wpdb->prefix . 'partyminder_community_members';

		$admin_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $members_table WHERE community_id = %d AND role = 'admin' AND status = 'active'",
				$community_id
			)
		);

		return (int) $admin_count;
	}

	/**
	 * Update member role
	 */
	public function update_member_role( $community_id, $member_id, $new_role ) {
		global $wpdb;

		// Get community
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}

		// Check permissions - only admins can change roles
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in', 'partyminder' ) );
		}

		$user_role = $this->get_member_role( $community_id, $current_user->ID );
		if ( $user_role !== 'admin' ) {
			return new WP_Error( 'permission_denied', __( 'Only community admins can change member roles', 'partyminder' ) );
		}

		// Validate new role
		$allowed_roles = array( 'admin', 'member' );
		if ( ! in_array( $new_role, $allowed_roles ) ) {
			return new WP_Error( 'invalid_role', __( 'Invalid role specified', 'partyminder' ) );
		}

		// Get member info
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		$member        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $members_table WHERE id = %d AND community_id = %d AND status = 'active'",
				$member_id,
				$community_id
			)
		);

		if ( ! $member ) {
			return new WP_Error( 'member_not_found', __( 'Member not found', 'partyminder' ) );
		}

		// Don't allow demoting the last admin
		if ( $member->role === 'admin' && $new_role !== 'admin' ) {
			$admin_count = $this->get_admin_count( $community_id );
			if ( $admin_count <= 1 ) {
				return new WP_Error( 'last_admin', __( 'Cannot demote the last admin. Promote another member to admin first.', 'partyminder' ) );
			}
		}

		// Update member role
		$result = $wpdb->update(
			$members_table,
			array( 'role' => $new_role ),
			array(
				'id'           => $member_id,
				'community_id' => $community_id,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);

		if ( $result === false ) {
			$error_msg = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to update member role', 'partyminder' );
			return new WP_Error( 'update_failed', $error_msg );
		}

		return true;
	}

	/**
	 * Remove member from community
	 */
	public function remove_member( $community_id, $member_id ) {
		global $wpdb;

		// Get community
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}

		// Check permissions - only admins can remove members
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in', 'partyminder' ) );
		}

		$user_role = $this->get_member_role( $community_id, $current_user->ID );
		if ( $user_role !== 'admin' ) {
			return new WP_Error( 'permission_denied', __( 'Only community admins can remove members', 'partyminder' ) );
		}

		// Get member info
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		$member        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $members_table WHERE id = %d AND community_id = %d AND status = 'active'",
				$member_id,
				$community_id
			)
		);

		if ( ! $member ) {
			return new WP_Error( 'member_not_found', __( 'Member not found', 'partyminder' ) );
		}

		// Don't allow removing the last admin
		if ( $member->role === 'admin' ) {
			$admin_count = $this->get_admin_count( $community_id );
			if ( $admin_count <= 1 ) {
				return new WP_Error( 'last_admin', __( 'Cannot remove the last admin. Promote another member to admin first.', 'partyminder' ) );
			}
		}

		// Don't allow self-removal if you're the only admin
		if ( $member->user_id == $current_user->ID && $member->role === 'admin' ) {
			$admin_count = $this->get_admin_count( $community_id );
			if ( $admin_count <= 1 ) {
				return new WP_Error( 'self_removal_blocked', __( 'You cannot remove yourself as the only admin. Promote another member to admin first.', 'partyminder' ) );
			}
		}

		// Update member status to inactive instead of deleting (preserves history)
		$result = $wpdb->update(
			$members_table,
			array(
				'status'       => 'removed',
				'last_seen_at' => current_time( 'mysql' ),
			),
			array(
				'id'           => $member_id,
				'community_id' => $community_id,
			),
			array( '%s', '%s' ),
			array( '%d', '%d' )
		);

		if ( $result === false ) {
			$error_msg = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to remove member', 'partyminder' );
			return new WP_Error( 'removal_failed', $error_msg );
		}

		// Update member count
		$this->update_member_count( $community_id );

		return true;
	}

	/**
	 * Send invitation to join community
	 */
	public function send_invitation( $community_id, $email, $message = '' ) {
		global $wpdb;

		// Get community
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}

		// Check permissions - only admins can send invitations
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in', 'partyminder' ) );
		}

		$user_role = $this->get_member_role( $community_id, $current_user->ID );
		if ( $user_role !== 'admin' ) {
			return new WP_Error( 'permission_denied', __( 'Only community admins can send invitations', 'partyminder' ) );
		}

		// Validate email
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Please provide a valid email address', 'partyminder' ) );
		}

		// Check if user is already a member
		$existing_user = get_user_by( 'email', $email );
		if ( $existing_user ) {
			$is_member = $this->is_member( $community_id, $existing_user->ID );
			if ( $is_member ) {
				return new WP_Error( 'already_member', __( 'This user is already a member of the community', 'partyminder' ) );
			}
		}

		// Check for existing pending invitation
		$invitations_table   = $wpdb->prefix . 'partyminder_community_invitations';
		$existing_invitation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $invitations_table 
             WHERE community_id = %d AND invited_email = %s AND status = 'pending' AND expires_at > NOW()",
				$community_id,
				$email
			)
		);

		if ( $existing_invitation ) {
			return new WP_Error( 'invitation_exists', __( 'A pending invitation already exists for this email', 'partyminder' ) );
		}

		// Get inviter member info
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		$inviter       = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $members_table WHERE community_id = %d AND user_id = %d AND status = 'active'",
				$community_id,
				$current_user->ID
			)
		);

		if ( ! $inviter ) {
			return new WP_Error( 'inviter_not_found', __( 'Inviter membership not found', 'partyminder' ) );
		}

		// Generate invitation token
		$token = wp_generate_password( 32, false );

		// Set expiration (7 days from now)
		$expires_at = date( 'Y-m-d H:i:s', strtotime( '+7 days' ) );

		// Insert invitation
		$result = $wpdb->insert(
			$invitations_table,
			array(
				'community_id'         => $community_id,
				'invited_by_member_id' => $inviter->id,
				'invited_email'        => $email,
				'invited_user_id'      => $existing_user ? $existing_user->ID : null,
				'invitation_token'     => $token,
				'message'              => wp_kses_post( $message ),
				'status'               => 'pending',
				'expires_at'           => $expires_at,
				'created_at'           => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result === false ) {
			$error_msg = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to create invitation', 'partyminder' );
			return new WP_Error( 'invitation_failed', $error_msg );
		}

		$invitation_id = $wpdb->insert_id;

		// Send invitation email
		$email_sent = $this->send_invitation_email( $community, $inviter, $email, $token, $message );

		if ( is_wp_error( $email_sent ) ) {
			// Log email error but don't fail the invitation
			error_log( 'PartyMinder: Failed to send invitation email: ' . $email_sent->get_error_message() );
		}

		return array(
			'invitation_id' => $invitation_id,
			'token'         => $token,
			'expires_at'    => $expires_at,
			'email_sent'    => ! is_wp_error( $email_sent ),
		);
	}

	/**
	 * Send invitation email
	 */
	private function send_invitation_email( $community, $inviter, $email, $token, $message = '' ) {
		$site_name      = get_bloginfo( 'name' );
		$invitation_url = home_url( '/communities/join?token=' . $token );

		$subject = sprintf( __( '[%1$s] You\'ve been invited to join %2$s', 'partyminder' ), $site_name, $community->name );

		$email_message = sprintf(
			__(
				'Hello!

%1$s has invited you to join the "%2$s" community on %3$s.

%4$s

To accept this invitation, click the link below:
%5$s

This invitation will expire in 7 days.

If you don\'t want to join this community, you can safely ignore this email.

Best regards,
The %6$s Team',
				'partyminder'
			),
			$inviter->display_name,
			$community->name,
			$site_name,
			$message ? "\nPersonal message: " . $message . "\n" : '',
			$invitation_url,
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
	 * Get pending invitations for a community
	 */
	public function get_community_invitations( $community_id, $limit = 20, $offset = 0 ) {
		global $wpdb;

		// Get community
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}

		$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';
		$members_table     = $wpdb->prefix . 'partyminder_community_members';

		$invitations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT i.*, m.display_name as inviter_name
             FROM $invitations_table i
             LEFT JOIN $members_table m ON i.invited_by_member_id = m.id
             WHERE i.community_id = %d AND i.status = 'pending'
             ORDER BY i.created_at DESC
             LIMIT %d OFFSET %d",
				$community_id,
				$limit,
				$offset
			)
		);

		return $invitations ?: array();
	}

	/**
	 * Cancel invitation
	 */
	public function cancel_invitation( $community_id, $invitation_id ) {
		global $wpdb;

		// Get community
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}

		// Check permissions - only admins can cancel invitations
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in', 'partyminder' ) );
		}

		$user_role = $this->get_member_role( $community_id, $current_user->ID );
		if ( $user_role !== 'admin' ) {
			return new WP_Error( 'permission_denied', __( 'Only community admins can cancel invitations', 'partyminder' ) );
		}

		// Update invitation status
		$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';
		$result            = $wpdb->update(
			$invitations_table,
			array(
				'status'       => 'cancelled',
				'responded_at' => current_time( 'mysql' ),
			),
			array(
				'id'           => $invitation_id,
				'community_id' => $community_id,
				'status'       => 'pending',
			),
			array( '%s', '%s' ),
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
	 * Validate privacy setting for communities
	 */
	private function validate_privacy_setting( $privacy ) {
		$allowed_privacy_settings = array( 'public', 'friends', 'private' );
		
		$privacy = sanitize_text_field( $privacy );
		
		if ( ! in_array( $privacy, $allowed_privacy_settings ) ) {
			return 'public'; // Default to public if invalid
		}
		
		return $privacy;
	}

	/**
	 * Delete a community and all associated data
	 */
	public function delete_community( $community_id ) {
		global $wpdb;

		$community_id = intval( $community_id );
		if ( ! $community_id ) {
			return new WP_Error( 'invalid_community', __( 'Invalid community ID', 'partyminder' ) );
		}

		// Check if community exists
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return new WP_Error( 'community_not_found', __( 'Community not found', 'partyminder' ) );
		}

		// Check if current user is admin of this community
		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return new WP_Error( 'user_required', __( 'You must be logged in', 'partyminder' ) );
		}

		$user_role = $this->get_member_role( $community_id, $current_user->ID );
		if ( $user_role !== 'admin' ) {
			return new WP_Error( 'permission_denied', __( 'You must be a community admin to delete this community', 'partyminder' ) );
		}

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Delete community members
			$members_table = $wpdb->prefix . 'partyminder_community_members';
			$wpdb->delete( $members_table, array( 'community_id' => $community_id ), array( '%d' ) );

			// Delete community invitations
			$invitations_table = $wpdb->prefix . 'partyminder_community_invitations';
			$wpdb->delete( $invitations_table, array( 'community_id' => $community_id ), array( '%d' ) );

			// Update events to remove community association
			$events_table = $wpdb->prefix . 'partyminder_events';
			$wpdb->update(
				$events_table,
				array( 'community_id' => null ),
				array( 'community_id' => $community_id ),
				array( '%s' ),
				array( '%d' )
			);

			// Update conversations to remove community association
			$conversations_table = $wpdb->prefix . 'partyminder_conversations';
			$wpdb->update(
				$conversations_table,
				array( 'community_id' => null ),
				array( 'community_id' => $community_id ),
				array( '%s' ),
				array( '%d' )
			);

			// Finally, delete the community itself
			$communities_table = $wpdb->prefix . 'partyminder_communities';
			$deleted = $wpdb->delete( $communities_table, array( 'id' => $community_id ), array( '%d' ) );

			if ( $deleted === false ) {
				throw new Exception( __( 'Failed to delete community record', 'partyminder' ) );
			}

			// Commit transaction
			$wpdb->query( 'COMMIT' );

			// Clear any cached data
			wp_cache_delete( 'community_' . $community_id, 'partyminder' );

			return true;

		} catch ( Exception $e ) {
			// Rollback transaction on error
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'deletion_failed', $e->getMessage() );
		}
	}

	/**
	 * Get community setting value
	 * Step 5: Reply join flow settings
	 */
	public function get_community_setting( $community_id, $setting_key, $default = null ) {
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return $default;
		}
		
		$settings = is_array( $community->settings ) ? $community->settings : array();
		return isset( $settings[ $setting_key ] ) ? $settings[ $setting_key ] : $default;
	}

	/**
	 * Set community setting value
	 * Step 5: Reply join flow settings
	 */
	public function set_community_setting( $community_id, $setting_key, $value ) {
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return false;
		}
		
		$settings = is_array( $community->settings ) ? $community->settings : array();
		$settings[ $setting_key ] = $value;
		
		return $this->update_community( $community_id, array( 'settings' => $settings ) );
	}

	/**
	 * Check if a community allows auto-join on reply
	 * Step 5: Reply join flow - defaults to true for public personal communities
	 */
	public function allows_auto_join_on_reply( $community_id ) {
		if ( ! PartyMinder_Feature_Flags::is_reply_join_flow_enabled() ) {
			return false;
		}
		
		$community = $this->get_community( $community_id );
		if ( ! $community ) {
			return false;
		}
		
		// Default to true for public personal communities, false for others
		$default = ( $community->visibility === 'public' && ! empty( $community->personal_owner_user_id ) );
		
		return $this->get_community_setting( $community_id, 'allow_auto_join_on_reply', $default );
	}

	/**
	 * Auto-join a user to a community
	 * Step 5: Reply join flow - for public communities
	 */
	public function join_community( $community_id, $user_id ) {
		// Check if user is already a member
		$existing_role = $this->get_member_role( $community_id, $user_id );
		if ( $existing_role ) {
			return true; // Already a member
		}

		// Get user info
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'invalid_user', __( 'Invalid user', 'partyminder' ) );
		}

		// Add as active member
		$member_data = array(
			'user_id'      => $user_id,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'role'         => 'member',
			'status'       => 'active'
		);

		return $this->add_member( $community_id, $member_data, true );
	}

	/**
	 * Request to join a community (for followers-only communities)
	 * Step 5: Reply join flow - creates pending membership
	 */
	public function request_to_join_community( $community_id, $user_id ) {
		// Check if user already has a request or membership
		$existing_role = $this->get_member_role( $community_id, $user_id );
		if ( $existing_role ) {
			if ( $existing_role === 'pending' ) {
				return new WP_Error( 'request_pending', __( 'Your request to join is already pending', 'partyminder' ) );
			}
			return true; // Already a member
		}

		// Get user info
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'invalid_user', __( 'Invalid user', 'partyminder' ) );
		}

		// Add as pending member
		$member_data = array(
			'user_id'      => $user_id,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'role'         => 'member',
			'status'       => 'pending'
		);

		$result = $this->add_member( $community_id, $member_data, true );
		
		if ( $result && ! is_wp_error( $result ) ) {
			// TODO: Send notification to community admins about pending request
			return true;
		}
		
		return $result;
	}
}
