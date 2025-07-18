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
        
        // Delete custom post types
        $wpdb->delete($wpdb->posts, array('post_type' => 'party_event'));
        
        // Clean up post meta
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT id FROM {$wpdb->posts})");
        
        // Delete plugin options
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'partyminder_%'");
    }
}