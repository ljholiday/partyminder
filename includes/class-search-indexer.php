<?php
/**
 * Search Indexer
 * Handles indexing content for search functionality
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PartyMinder_Search_Indexer {

	/**
	 * Upsert a search index entry
	 */
	public static function upsert_search_entry( $data ) {
		global $wpdb;
		
		$search_table = $wpdb->prefix . 'partyminder_search';
		
		$defaults = array(
			'entity_type'     => '',
			'entity_id'       => 0,
			'title'           => '',
			'content'         => '',
			'url'             => '',
			'owner_user_id'   => 0,
			'community_id'    => 0,
			'event_id'        => 0,
			'visibility_scope' => 'public',
			'last_activity_at' => current_time( 'mysql' ),
		);
		
		$entry_data = wp_parse_args( $data, $defaults );
		
		// Sanitize content
		$entry_data['title'] = wp_strip_all_tags( $entry_data['title'] );
		$entry_data['content'] = wp_strip_all_tags( $entry_data['content'] );
		
		// Check if entry already exists
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $search_table WHERE entity_type = %s AND entity_id = %d",
			$entry_data['entity_type'],
			$entry_data['entity_id']
		) );
		
		if ( $exists ) {
			// Update existing entry
			$wpdb->update(
				$search_table,
				$entry_data,
				array( 'id' => $exists ),
				array( '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new entry
			$wpdb->insert( $search_table, $entry_data );
		}
	}
	
	/**
	 * Remove a search index entry
	 */
	public static function remove_search_entry( $entity_type, $entity_id ) {
		global $wpdb;
		
		$search_table = $wpdb->prefix . 'partyminder_search';
		
		$wpdb->delete(
			$search_table,
			array(
				'entity_type' => $entity_type,
				'entity_id'   => intval( $entity_id ),
			),
			array( '%s', '%d' )
		);
	}
	
	/**
	 * Index an event
	 */
	public static function index_event( $event ) {
		$event_data = array(
			'entity_type'     => 'event',
			'entity_id'       => intval( $event->id ),
			'title'           => $event->title,
			'content'         => $event->description . ' ' . $event->venue,
			'url'             => home_url( '/events/' . $event->slug ),
			'owner_user_id'   => intval( $event->author_id ),
			'community_id'    => intval( $event->community_id ),
			'event_id'        => intval( $event->id ),
			'visibility_scope' => $event->privacy === 'private' ? 'private' : 'public',
			'last_activity_at' => $event->created_at,
		);
		
		self::upsert_search_entry( $event_data );
	}
	
	/**
	 * Index a community
	 */
	public static function index_community( $community ) {
		$community_data = array(
			'entity_type'     => 'community',
			'entity_id'       => intval( $community->id ),
			'title'           => $community->name,
			'content'         => $community->description,
			'url'             => home_url( '/communities/' . $community->slug ),
			'owner_user_id'   => intval( $community->creator_user_id ),
			'community_id'    => intval( $community->id ),
			'event_id'        => 0,
			'visibility_scope' => $community->visibility === 'private' ? 'private' : 'public',
			'last_activity_at' => $community->created_at,
		);
		
		self::upsert_search_entry( $community_data );
	}
	
	/**
	 * Index a conversation
	 */
	public static function index_conversation( $conversation ) {
		$conversation_data = array(
			'entity_type'     => 'conversation',
			'entity_id'       => intval( $conversation->id ),
			'title'           => $conversation->title,
			'content'         => $conversation->content,
			'url'             => home_url( '/conversations/' . $conversation->slug ),
			'owner_user_id'   => intval( $conversation->author_id ),
			'community_id'    => intval( $conversation->community_id ),
			'event_id'        => intval( $conversation->event_id ),
			'visibility_scope' => $conversation->privacy === 'private' ? 'private' : 'public',
			'last_activity_at' => $conversation->created_at,
		);
		
		self::upsert_search_entry( $conversation_data );
	}
	
	/**
	 * Index a member profile
	 */
	public static function index_member( $user_id, $profile_data = null ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		
		if ( ! $profile_data ) {
			require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-profile-manager.php';
			$profile_data = PartyMinder_Profile_Manager::get_user_profile( $user_id );
		}
		
		$member_data = array(
			'entity_type'     => 'member',
			'entity_id'       => intval( $user_id ),
			'title'           => $profile_data['display_name'] ?: $user->display_name,
			'content'         => $profile_data['bio'] . ' ' . $profile_data['location'],
			'url'             => home_url( '/profile/' . $user_id ),
			'owner_user_id'   => intval( $user_id ),
			'community_id'    => 0,
			'event_id'        => 0,
			'visibility_scope' => 'public', // For MVP, make member profiles searchable
			'last_activity_at' => current_time( 'mysql' ),
		);
		
		self::upsert_search_entry( $member_data );
	}
}