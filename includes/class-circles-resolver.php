<?php

/**
 * Circles Resolver - Step 6 Implementation
 * 
 * Resolves user circles based on community membership relationships:
 * - Inner: Communities created by the viewer
 * - Trusted: Communities created by members of Inner communities
 * - Extended: Communities created by members of Trusted communities
 */
class PartyMinder_Circles_Resolver {

	/**
	 * Cache TTL in seconds (60-120s as specified)
	 */
	const CACHE_TTL = 90;

	/**
	 * Maximum recursion depth to prevent cycles
	 */
	const MAX_DEPTH = 10;

	/**
	 * Get creator sets for a viewer
	 * 
	 * @param int $viewer_id The user ID to resolve circles for
	 * @return array Array with 'inner', 'trusted', 'extended' community and creator sets
	 */
	public static function creator_sets( $viewer_id ) {
		if ( ! PartyMinder_Feature_Flags::is_circles_resolver_enabled() ) {
			return self::get_empty_sets();
		}

		$viewer_id = intval( $viewer_id );
		if ( ! $viewer_id ) {
			return self::get_empty_sets();
		}

		// Check cache first
		$cache_key = 'partyminder_circles_' . $viewer_id;
		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		// Calculate circles
		$start_time = microtime( true );
		$circles = self::calculate_circles( $viewer_id );
		$calculation_time = microtime( true ) - $start_time;

		// Add metrics
		$circles['metrics'] = array(
			'calculation_time' => round( $calculation_time * 1000, 2 ) . 'ms',
			'inner_communities' => count( $circles['inner']['communities'] ),
			'trusted_communities' => count( $circles['trusted']['communities'] ),
			'extended_communities' => count( $circles['extended']['communities'] ),
			'total_creators' => count( array_unique( array_merge(
				$circles['inner']['creators'],
				$circles['trusted']['creators'],
				$circles['extended']['creators']
			) ) ),
			'cached_at' => current_time( 'mysql' )
		);

		// Cache the results
		set_transient( $cache_key, $circles, self::CACHE_TTL );

		return $circles;
	}

	/**
	 * Calculate circles for a viewer
	 */
	private static function calculate_circles( $viewer_id ) {
		global $wpdb;
		
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$members_table = $wpdb->prefix . 'partyminder_community_members';

		// Step 1: Inner communities (created by viewer)
		$inner_communities = self::get_communities_created_by( $viewer_id );
		$inner_creators = array( $viewer_id );

		// Step 2: Trusted communities 
		// Get all members of inner communities
		$trusted_member_ids = self::get_members_of_communities( $inner_communities );
		// Get communities created by those members
		$trusted_communities = self::get_communities_created_by_users( $trusted_member_ids );
		$trusted_creators = self::get_creators_of_communities( $trusted_communities );

		// Step 3: Extended communities
		// Get all members of trusted communities
		$extended_member_ids = self::get_members_of_communities( $trusted_communities );
		// Get communities created by those members
		$extended_communities = self::get_communities_created_by_users( $extended_member_ids );
		$extended_creators = self::get_creators_of_communities( $extended_communities );

		// Deduplicate and ensure proper typing
		$inner_communities = array_unique( array_map( 'intval', $inner_communities ) );
		$trusted_communities = array_unique( array_map( 'intval', $trusted_communities ) );
		$extended_communities = array_unique( array_map( 'intval', $extended_communities ) );
		
		$inner_creators = array_unique( array_map( 'intval', $inner_creators ) );
		$trusted_creators = array_unique( array_map( 'intval', $trusted_creators ) );
		$extended_creators = array_unique( array_map( 'intval', $extended_creators ) );

		return array(
			'inner' => array(
				'communities' => $inner_communities,
				'creators' => $inner_creators
			),
			'trusted' => array(
				'communities' => $trusted_communities,
				'creators' => $trusted_creators
			),
			'extended' => array(
				'communities' => $extended_communities,
				'creators' => $extended_creators
			)
		);
	}

	/**
	 * Get communities created by a specific user
	 */
	private static function get_communities_created_by( $user_id ) {
		global $wpdb;
		
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM $communities_table 
			 WHERE creator_id = %d AND is_active = 1",
			$user_id
		) );
	}

	/**
	 * Get communities created by multiple users
	 */
	private static function get_communities_created_by_users( $user_ids ) {
		if ( empty( $user_ids ) ) {
			return array();
		}

		global $wpdb;
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM $communities_table 
			 WHERE creator_id IN ($placeholders) AND is_active = 1",
			...$user_ids
		) );
	}

	/**
	 * Get all active members of given communities
	 */
	private static function get_members_of_communities( $community_ids ) {
		if ( empty( $community_ids ) ) {
			return array();
		}

		global $wpdb;
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		
		$placeholders = implode( ',', array_fill( 0, count( $community_ids ), '%d' ) );
		
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT user_id FROM $members_table 
			 WHERE community_id IN ($placeholders) AND status = 'active'",
			...$community_ids
		) );
	}

	/**
	 * Get creators of given communities
	 */
	private static function get_creators_of_communities( $community_ids ) {
		if ( empty( $community_ids ) ) {
			return array();
		}

		global $wpdb;
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		
		$placeholders = implode( ',', array_fill( 0, count( $community_ids ), '%d' ) );
		
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT creator_id FROM $communities_table 
			 WHERE id IN ($placeholders) AND is_active = 1",
			...$community_ids
		) );
	}

	/**
	 * Get empty sets structure
	 */
	private static function get_empty_sets() {
		return array(
			'inner' => array( 'communities' => array(), 'creators' => array() ),
			'trusted' => array( 'communities' => array(), 'creators' => array() ),
			'extended' => array( 'communities' => array(), 'creators' => array() ),
			'metrics' => array(
				'calculation_time' => '0ms',
				'inner_communities' => 0,
				'trusted_communities' => 0,
				'extended_communities' => 0,
				'total_creators' => 0,
				'cached_at' => current_time( 'mysql' )
			)
		);
	}

	/**
	 * Clear cache for a specific user
	 */
	public static function clear_cache( $user_id ) {
		$cache_key = 'partyminder_circles_' . intval( $user_id );
		delete_transient( $cache_key );
	}

	/**
	 * Clear all circles cache (for debugging)
	 */
	public static function clear_all_cache() {
		global $wpdb;
		
		// Delete all transients starting with our prefix
		$wpdb->query( 
			"DELETE FROM $wpdb->options 
			 WHERE option_name LIKE '_transient_partyminder_circles_%' 
			 OR option_name LIKE '_transient_timeout_partyminder_circles_%'"
		);
	}
}