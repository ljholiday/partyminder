<?php

/**
 * PartyMinder Circle Scope Resolver
 * 
 * Implements the three circles of trust:
 * - Close Circle: User's own communities (direct members)
 * - Trusted Circle: Close + members of those communities' other communities  
 * - Extended Circle: Trusted + members of those communities' other communities
 */
class PartyMinder_Circle_Scope {

	/**
	 * Resolve conversation scope for a user and circle level
	 * 
	 * @param int $user_id The user to resolve scope for
	 * @param string $circle The circle level: 'close', 'trusted', 'extended'
	 * @return array Array with 'users' and 'communities' that are in scope
	 */
	public static function resolve_conversation_scope( $user_id, $circle ) {
		global $wpdb;
		
		if ( ! $user_id || ! is_user_logged_in() ) {
			// Non-logged users only see public content
			return self::get_public_scope();
		}

		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$members_table = $wpdb->prefix . 'partyminder_community_members';
		
		$scope = array(
			'users' => array( $user_id ), // Always include self
			'communities' => array(),
		);

		switch ( $circle ) {
			case 'close':
				$scope = self::get_close_circle_scope( $user_id );
				break;
			case 'trusted':
				$scope = self::get_trusted_circle_scope( $user_id );
				break;
			case 'extended':
				$scope = self::get_extended_circle_scope( $user_id );
				break;
			default:
				$scope = self::get_close_circle_scope( $user_id );
		}

		return $scope;
	}

	/**
	 * Get Close Circle scope
	 * User's own communities and their direct members
	 */
	private static function get_close_circle_scope( $user_id ) {
		global $wpdb;
		
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$members_table = $wpdb->prefix . 'partyminder_community_members';

		// Get user's communities
		$user_communities = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT community_id FROM $members_table 
				 WHERE user_id = %d AND status = 'active'",
				$user_id
			)
		);

		$scope_users = array( $user_id );
		$scope_communities = $user_communities;

		if ( ! empty( $user_communities ) ) {
			$community_ids_in = implode( ',', array_map( 'intval', $user_communities ) );
			
			// Get all members of user's communities
			$close_circle_users = $wpdb->get_col(
				"SELECT DISTINCT user_id FROM $members_table 
				 WHERE community_id IN ($community_ids_in) AND status = 'active'"
			);

			$scope_users = array_unique( array_merge( $scope_users, $close_circle_users ) );
		}

		return array(
			'users' => $scope_users,
			'communities' => $scope_communities,
		);
	}

	/**
	 * Get Trusted Circle scope  
	 * Close circle + members of those communities' other communities
	 */
	private static function get_trusted_circle_scope( $user_id ) {
		global $wpdb;
		
		$members_table = $wpdb->prefix . 'partyminder_community_members';

		// Start with close circle
		$close_scope = self::get_close_circle_scope( $user_id );
		$scope_users = $close_scope['users'];
		$scope_communities = $close_scope['communities'];

		if ( ! empty( $close_scope['users'] ) ) {
			$user_ids_in = implode( ',', array_map( 'intval', $close_scope['users'] ) );
			
			// Get all communities that close circle members belong to
			$trusted_communities = $wpdb->get_col(
				"SELECT DISTINCT community_id FROM $members_table 
				 WHERE user_id IN ($user_ids_in) AND status = 'active'"
			);

			$scope_communities = array_unique( array_merge( $scope_communities, $trusted_communities ) );

			if ( ! empty( $trusted_communities ) ) {
				$community_ids_in = implode( ',', array_map( 'intval', $trusted_communities ) );
				
				// Get all members of trusted communities
				$trusted_users = $wpdb->get_col(
					"SELECT DISTINCT user_id FROM $members_table 
					 WHERE community_id IN ($community_ids_in) AND status = 'active'"
				);

				$scope_users = array_unique( array_merge( $scope_users, $trusted_users ) );
			}
		}

		return array(
			'users' => $scope_users,
			'communities' => $scope_communities,
		);
	}

	/**
	 * Get Extended Circle scope
	 * Trusted circle + members of those communities' other communities
	 */
	private static function get_extended_circle_scope( $user_id ) {
		global $wpdb;
		
		$members_table = $wpdb->prefix . 'partyminder_community_members';

		// Start with trusted circle
		$trusted_scope = self::get_trusted_circle_scope( $user_id );
		$scope_users = $trusted_scope['users'];
		$scope_communities = $trusted_scope['communities'];

		if ( ! empty( $trusted_scope['users'] ) ) {
			$user_ids_in = implode( ',', array_map( 'intval', $trusted_scope['users'] ) );
			
			// Get all communities that trusted circle members belong to
			$extended_communities = $wpdb->get_col(
				"SELECT DISTINCT community_id FROM $members_table 
				 WHERE user_id IN ($user_ids_in) AND status = 'active'"
			);

			$scope_communities = array_unique( array_merge( $scope_communities, $extended_communities ) );

			if ( ! empty( $extended_communities ) ) {
				$community_ids_in = implode( ',', array_map( 'intval', $extended_communities ) );
				
				// Get all members of extended communities
				$extended_users = $wpdb->get_col(
					"SELECT DISTINCT user_id FROM $members_table 
					 WHERE community_id IN ($community_ids_in) AND status = 'active'"
				);

				$scope_users = array_unique( array_merge( $scope_users, $extended_users ) );
			}
		}

		return array(
			'users' => $scope_users,
			'communities' => $scope_communities,
		);
	}

	/**
	 * Get public scope for non-logged users
	 */
	private static function get_public_scope() {
		global $wpdb;
		
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		
		// Get all public communities
		$public_communities = $wpdb->get_col(
			"SELECT id FROM $communities_table 
			 WHERE privacy = 'public' AND is_active = 1"
		);

		return array(
			'users' => array(), // No specific users for public scope
			'communities' => $public_communities,
		);
	}

	/**
	 * Check if a conversation is in scope for a user and circle
	 */
	public static function is_conversation_in_scope( $conversation, $user_id, $circle ) {
		$scope = self::resolve_conversation_scope( $user_id, $circle );
		
		// Check if conversation author is in scope
		if ( in_array( $conversation->author_id, $scope['users'] ) ) {
			return true;
		}
		
		// Check if conversation community is in scope
		if ( $conversation->community_id && in_array( $conversation->community_id, $scope['communities'] ) ) {
			return true;
		}
		
		// For public conversations, check if they're in public scope
		if ( empty( $conversation->community_id ) && empty( $conversation->event_id ) ) {
			return true; // General conversations are visible to all circles
		}
		
		return false;
	}
}