<?php
/**
 * One-time search indexing initialization
 * Populates the search table with existing content
 */

class PartyMinder_Search_Indexer_Init {
    
    /**
     * Index all existing content
     */
    public static function index_all_content() {
        global $wpdb;
        
        $indexed_count = 0;
        
        // Index events
        $indexed_count += self::index_all_events();
        
        // Index communities  
        $indexed_count += self::index_all_communities();
        
        // Index conversations
        $indexed_count += self::index_all_conversations();
        
        // Index members
        $indexed_count += self::index_all_members();
        
        return $indexed_count;
    }
    
    /**
     * Index all events
     */
    private static function index_all_events() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'partyminder_events';
        $events = $wpdb->get_results("SELECT * FROM $events_table WHERE event_status = 'active'");
        
        $count = 0;
        foreach ($events as $event) {
            PartyMinder_Search_Indexer::index_event($event);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Index all communities
     */
    private static function index_all_communities() {
        global $wpdb;
        
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        $communities = $wpdb->get_results("SELECT * FROM $communities_table WHERE status = 'active'");
        
        $count = 0;
        foreach ($communities as $community) {
            PartyMinder_Search_Indexer::index_community($community);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Index all conversations
     */
    private static function index_all_conversations() {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        $conversations = $wpdb->get_results("SELECT * FROM $conversations_table");
        
        $count = 0;
        foreach ($conversations as $conversation) {
            PartyMinder_Search_Indexer::index_conversation($conversation);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Index all members
     */
    private static function index_all_members() {
        global $wpdb;
        
        $users = get_users(array('number' => 1000)); // Limit to prevent timeout
        
        $count = 0;
        foreach ($users as $user) {
            PartyMinder_Search_Indexer::index_member($user);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Clear all search index data
     */
    public static function clear_search_index() {
        global $wpdb;
        
        $search_table = $wpdb->prefix . 'partyminder_search';
        return $wpdb->query("DELETE FROM $search_table");
    }
}