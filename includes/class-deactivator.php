<?php

class PartyMinder_Deactivator {
    
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation flag
        update_option('partyminder_deactivated_date', current_time('mysql'));
        delete_option('partyminder_activated');
    }
    
    private static function clear_scheduled_events() {
        // Clear recurring tasks
        wp_clear_scheduled_hook('partyminder_daily_cleanup');
        wp_clear_scheduled_hook('partyminder_send_reminders');
        
        // Clear event-specific scheduled tasks
        global $wpdb;
        $events_table = $wpdb->prefix . 'partyminder_events';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$events_table'") == $events_table) {
            $events = $wpdb->get_results("SELECT post_id FROM $events_table");
            
            foreach ($events as $event) {
                wp_clear_scheduled_hook('partyminder_event_reminder_' . $event->post_id);
                wp_clear_scheduled_hook('partyminder_event_followup_' . $event->post_id);
            }
        }
    }
    
    public static function uninstall() {
        // Only run if user has confirmed data deletion
        if (!get_option('partyminder_delete_data_on_uninstall', false)) {
            return;
        }
        
        global $wpdb;
        
        // Drop custom tables
        $tables = array(
            $wpdb->prefix . 'partyminder_events',
            $wpdb->prefix . 'partyminder_guests',
            $wpdb->prefix . 'partyminder_ai_interactions'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Delete pages that are PartyMinder events
        $event_pages = $wpdb->get_results(
            "SELECT p.ID FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = 'page' 
             AND pm.meta_key = '_partyminder_event' 
             AND pm.meta_value = 'true'"
        );
        
        foreach ($event_pages as $page) {
            wp_delete_post($page->ID, true);
        }
        
        // Clean up post meta
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT id FROM {$wpdb->posts})");
        
        // Delete created pages
        self::delete_pages();
        
        // Delete plugin options
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'partyminder_%'");
    }
    
    private static function delete_pages() {
        $page_keys = array('events', 'create-event', 'my-events', 'edit-event');
        
        foreach ($page_keys as $key) {
            $page_id = get_option('partyminder_page_' . $key);
            if ($page_id) {
                // Force delete the page (bypass trash)
                wp_delete_post($page_id, true);
                delete_option('partyminder_page_' . $key);
            }
        }
    }
}