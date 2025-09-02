<?php

class PartyMinder_Personal_Community_Service {

	/**
	 * Create a personal community for a user
	 * Per the plan: personal_owner_user_id = $user_id, creator_user_id = $user_id, 
	 * visibility = 'public' (default), slug like @username or pc_{user_id}
	 */
	public static function create_for_user( $user_id ) {
		global $wpdb;
		
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		// Check if user already has a personal community
		$existing = self::get_personal_community_for_user( $user_id );
		if ( $existing ) {
			return $existing->id;
		}

		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$members_table = $wpdb->prefix . 'partyminder_community_members';

		// Get user's AT Protocol data from member_identities table
		$identities_table = $wpdb->prefix . 'partyminder_member_identities';
		$identity = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT at_protocol_did, at_protocol_handle FROM $identities_table WHERE user_id = %d",
				$user_id
			)
		);

		// Create community with personal_owner_user_id set
		$community_data = array(
			'name' => $user->display_name . "'s Personal Community",
			'slug' => 'pc_' . $user_id,
			'description' => 'Personal social feed for ' . $user->display_name,
			'type' => 'personal',
			'personal_owner_user_id' => $user_id,
			'visibility' => 'public',
			'creator_id' => $user_id,
			'creator_email' => $user->user_email,
			'at_protocol_did' => $identity ? $identity->at_protocol_did : null,
			'at_protocol_handle' => $identity ? $identity->at_protocol_handle : null,
			'is_active' => 1,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' )
		);

		$result = $wpdb->insert( $communities_table, $community_data );
		
		if ( ! $result ) {
			return false;
		}

		$community_id = $wpdb->insert_id;

		// Add creator as member with 'active' status (per our corrected schema)
		$member_data = array(
			'community_id' => $community_id,
			'user_id' => $user_id,
			'email' => $user->user_email,
			'display_name' => $user->display_name,
			'role' => 'admin', // Owner of their personal community
			'status' => 'active', // Using correct 'active' status
			'joined_at' => current_time( 'mysql' ),
			'last_seen_at' => current_time( 'mysql' )
		);

		$wpdb->insert( $members_table, $member_data );

		return $community_id;
	}

	/**
	 * Get the personal community for a user
	 */
	public static function get_personal_community_for_user( $user_id ) {
		global $wpdb;
		
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		
		return $wpdb->get_row( 
			$wpdb->prepare(
				"SELECT * FROM $communities_table 
				 WHERE personal_owner_user_id = %d AND is_active = 1",
				$user_id
			)
		);
	}

	/**
	 * Check if a community is a personal community
	 */
	public static function is_personal_community( $community_id ) {
		global $wpdb;
		
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		
		$community = $wpdb->get_row( 
			$wpdb->prepare(
				"SELECT personal_owner_user_id FROM $communities_table 
				 WHERE id = %d AND is_active = 1",
				$community_id
			)
		);

		return $community && $community->personal_owner_user_id;
	}

	/**
	 * Backfill personal communities for existing users (idempotent)
	 * Per the plan: rate limit batches to avoid lock pressure
	 */
	public static function backfill_existing_users( $batch_size = 50 ) {
		$users = get_users( array(
			'number' => $batch_size,
			'fields' => 'ID'
		) );

		$created = 0;
		foreach ( $users as $user_id ) {
			$community_id = self::create_for_user( $user_id );
			if ( $community_id ) {
				$created++;
			}
		}

		return $created;
	}
}