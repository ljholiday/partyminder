<?php

/**
 * PartyMinder Member Identity Manager
 *
 * Handles AT Protocol DIDs and cross-site member identity
 */
class PartyMinder_Member_Identity_Manager {

	public function __construct() {
		// Hook into user registration to create DIDs
		if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) {
			add_action( 'user_register', array( $this, 'create_member_identity' ) );
			add_action( 'wp_login', array( $this, 'ensure_member_identity' ), 10, 2 );
		}
	}

	/**
	 * Create member identity when user registers
	 */
	public function create_member_identity( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		return $this->ensure_identity_exists( $user_id, $user->user_email, $user->display_name );
	}

	/**
	 * Ensure member identity exists on login
	 */
	public function ensure_member_identity( $user_login, $user ) {
		if ( ! $user || ! $user->ID ) {
			return false;
		}

		return $this->ensure_identity_exists( $user->ID, $user->user_email, $user->display_name );
	}

	/**
	 * Ensure identity record exists for user
	 */
	public function ensure_identity_exists( $user_id, $email, $display_name = '' ) {
		global $wpdb;

		if ( ! PartyMinder_Feature_Flags::is_at_protocol_enabled() ) {
			return false;
		}

		$identities_table = $wpdb->prefix . 'partyminder_member_identities';

		// Validate that the WordPress user actually exists first
		$wp_user = get_user_by( 'id', $user_id );
		if ( ! $wp_user ) {
			error_log( '[PartyMinder] Cannot create member identity for non-existent user ID: ' . $user_id );
			return false;
		}

		// Check if identity already exists
		$existing_identity = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $identities_table WHERE user_id = %d",
				$user_id
			)
		);

		if ( $existing_identity ) {
			// Update display name if changed
			if ( $existing_identity->display_name !== $wp_user->display_name ) {
				$wpdb->update(
					$identities_table,
					array(
						'display_name' => sanitize_text_field( $wp_user->display_name ),
						'updated_at'   => current_time( 'mysql' ),
					),
					array( 'user_id' => $user_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
			}
			return $existing_identity->at_protocol_did;
		}

		// Generate new DID and handle using WordPress user data
		$did    = $this->generate_member_did( $user_id, $email );
		$handle = $this->generate_member_handle( $user_id, $wp_user->display_name );

		// Create identity record
		$result = $wpdb->insert(
			$identities_table,
			array(
				'user_id'            => $user_id,
				'email'              => sanitize_email( $email ),
				'display_name'       => sanitize_text_field( $wp_user->display_name ),
				'at_protocol_did'    => $did,
				'at_protocol_handle' => $handle,
				'pds_url'            => $this->get_default_pds(),
				'profile_data'       => wp_json_encode( $this->get_default_at_protocol_data() ),
				'is_verified'        => 0,
				'created_at'         => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( $result === false ) {
			error_log( '[PartyMinder] Failed to create member identity for user ' . $user_id . ': ' . $wpdb->last_error );
			return false;
		}

		// Log DID creation
		error_log( '[PartyMinder] Created AT Protocol DID for user ' . $user_id . ': ' . $did );

		return $did;
	}

	/**
	 * Get member identity by user ID
	 */
	public function get_member_identity( $user_id ) {
		global $wpdb;

		$identities_table = $wpdb->prefix . 'partyminder_member_identities';

		$identity = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $identities_table WHERE user_id = %d",
				$user_id
			)
		);

		if ( $identity ) {
			$identity->at_protocol_data = json_decode( $identity->profile_data ?: '{}', true );
		}

		return $identity;
	}

	/**
	 * Get member identity by DID
	 */
	public function get_member_identity_by_did( $did ) {
		global $wpdb;

		$identities_table = $wpdb->prefix . 'partyminder_member_identities';

		$identity = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $identities_table WHERE at_protocol_did = %s",
				$did
			)
		);

		if ( $identity ) {
			$identity->at_protocol_data = json_decode( $identity->profile_data ?: '{}', true );
		}

		return $identity;
	}

	/**
	 * Get member identity by email
	 */
	public function get_member_identity_by_email( $email ) {
		global $wpdb;

		$identities_table = $wpdb->prefix . 'partyminder_member_identities';

		$identity = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $identities_table WHERE email = %s",
				$email
			)
		);

		if ( $identity ) {
			$identity->at_protocol_data = json_decode( $identity->profile_data ?: '{}', true );
		}

		return $identity;
	}

	/**
	 * Update member identity AT Protocol data
	 */
	public function update_at_protocol_data( $user_id, $at_protocol_data ) {
		global $wpdb;

		$identities_table = $wpdb->prefix . 'partyminder_member_identities';

		$result = $wpdb->update(
			$identities_table,
			array(
				'profile_data' => wp_json_encode( $at_protocol_data ),
				'last_sync_at' => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'user_id' => $user_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Mark identity as verified
	 */
	public function verify_identity( $user_id ) {
		global $wpdb;

		$identities_table = $wpdb->prefix . 'partyminder_member_identities';

		$result = $wpdb->update(
			$identities_table,
			array(
				'is_verified' => 1,
				'updated_at'  => current_time( 'mysql' ),
			),
			array( 'user_id' => $user_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( $result ) {
			error_log( '[PartyMinder] Verified AT Protocol identity for user ' . $user_id );
		}

		return $result !== false;
	}

	/**
	 * Get all identities for sync
	 */
	public function get_identities_for_sync( $limit = 50 ) {
		global $wpdb;

		$identities_table = $wpdb->prefix . 'partyminder_member_identities';

		// Get identities that haven't been synced in the last 24 hours
		$identities = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $identities_table 
             WHERE last_sync_at IS NULL OR last_sync_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY created_at ASC
             LIMIT %d",
				$limit
			)
		);

		return $identities ?: array();
	}

	/**
	 * Generate member DID
	 */
	private function generate_member_did( $user_id, $email ) {
		// Generate a deterministic but unique DID
		$hash = substr( md5( 'user:' . $user_id . ':' . $email . ':' . wp_salt( 'auth' ) ), 0, 16 );
		return 'did:partyminder:user:' . $hash;
	}

	/**
	 * Generate member handle
	 */
	private function generate_member_handle( $user_id, $display_name ) {
		// Create a handle based on display name and user ID
		$base_handle = sanitize_title( $display_name );
		$base_handle = preg_replace( '/[^a-z0-9\-]/', '', $base_handle );

		if ( empty( $base_handle ) ) {
			$base_handle = 'user';
		}

		return $base_handle . '.' . $user_id . '.partyminder.social';
	}

	/**
	 * Get default PDS (Personal Data Server)
	 */
	private function get_default_pds() {
		// For now, use PartyMinder's own PDS
		return get_option( 'partyminder_at_protocol_pds', 'pds.partyminder.social' );
	}

	/**
	 * Get default AT Protocol data structure
	 */
	private function get_default_at_protocol_data() {
		return array(
			'profile'     => array(
				'displayName' => '',
				'description' => '',
				'avatar'      => '',
				'banner'      => '',
			),
			'preferences' => array(
				'public_profile'  => true,
				'discoverable'    => true,
				'cross_site_sync' => true,
			),
			'sync_status' => array(
				'profile_synced'     => false,
				'connections_synced' => false,
				'last_error'         => null,
			),
		);
	}

	/**
	 * Get member stats for admin
	 */
	public function get_member_stats() {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}

		$identities_table = $wpdb->prefix . 'partyminder_member_identities';

		$total_identities    = $wpdb->get_var( "SELECT COUNT(*) FROM $identities_table" );
		$verified_identities = $wpdb->get_var( "SELECT COUNT(*) FROM $identities_table WHERE is_verified = 1" );
		$synced_identities   = $wpdb->get_var( "SELECT COUNT(*) FROM $identities_table WHERE last_sync_at IS NOT NULL" );

		return array(
			'total_identities'    => (int) $total_identities,
			'verified_identities' => (int) $verified_identities,
			'synced_identities'   => (int) $synced_identities,
			'sync_pending'        => (int) ( $total_identities - $synced_identities ),
		);
	}

	/**
	 * Bulk create identities for existing users (admin function)
	 */
	public function bulk_create_identities_for_existing_users() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$users = get_users(
			array(
				'fields' => array( 'ID', 'user_email', 'display_name' ),
				'number' => -1,
			)
		);

		$created_count = 0;

		foreach ( $users as $user ) {
			$existing_identity = $this->get_member_identity( $user->ID );
			if ( ! $existing_identity ) {
				$did = $this->ensure_identity_exists( $user->ID, $user->user_email, $user->display_name );
				if ( $did ) {
					++$created_count;
				}
			}
		}

		error_log( '[PartyMinder] Bulk created ' . $created_count . ' member identities' );

		return $created_count;
	}
}
