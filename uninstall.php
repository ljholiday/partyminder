<?php
/**
 * PartyMinder Uninstall Handler
 * 
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It handles cleanup of all plugin data if the user has opted to delete data.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Only proceed if user has explicitly opted to delete data
if (!get_option('partyminder_delete_data_on_uninstall', false)) {
    return;
}

global $wpdb;

try {
    // Start transaction for safety
    $wpdb->query('START TRANSACTION');

    // 1. Delete custom tables
    $tables_to_delete = array(
        $wpdb->prefix . 'partyminder_events',
        $wpdb->prefix . 'partyminder_guests', 
        $wpdb->prefix . 'partyminder_ai_interactions'
    );

    foreach ($tables_to_delete as $table) {
        $wpdb->query("DROP TABLE IF EXISTS `$table`");
    }

    // 2. Delete custom post type posts and their meta
    $post_types = array('party_event');
    
    foreach ($post_types as $post_type) {
        // Get all posts of this type
        $posts = get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids'
        ));

        // Delete each post and its meta
        foreach ($posts as $post_id) {
            // Delete post meta
            $wpdb->delete($wpdb->postmeta, array('post_id' => $post_id));
            
            // Delete the post
            $wpdb->delete($wpdb->posts, array('ID' => $post_id));
            
            // Delete any comments on the post
            $wpdb->delete($wpdb->comments, array('comment_post_ID' => $post_id));
        }
    }

    // 3. Clean up orphaned post meta
    $wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.ID IS NULL");

    // 4. Delete all plugin options
    $option_patterns = array(
        'partyminder_%'
    );

    foreach ($option_patterns as $pattern) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $pattern
        ));
    }

    // 5. Delete plugin-specific user meta (if any)
    $user_meta_keys = array(
        'partyminder_user_preferences',
        'partyminder_notification_settings'
    );

    foreach ($user_meta_keys as $meta_key) {
        delete_metadata('user', 0, $meta_key, '', true);
    }

    // 6. Clear any scheduled events
    $scheduled_hooks = array(
        'partyminder_daily_cleanup',
        'partyminder_send_reminders',
        'partyminder_backup_data'
    );

    foreach ($scheduled_hooks as $hook) {
        wp_clear_scheduled_hook($hook);
    }

    // Clear any event-specific scheduled hooks
    $events = $wpdb->get_results("SELECT DISTINCT post_id FROM {$wpdb->prefix}partyminder_events");
    foreach ($events as $event) {
        wp_clear_scheduled_hook('partyminder_event_reminder_' . $event->post_id);
        wp_clear_scheduled_hook('partyminder_event_followup_' . $event->post_id);
    }

    // 7. Delete any uploaded files in plugin directory
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/partyminder/';
    
    if (is_dir($plugin_upload_dir)) {
        // Recursively delete plugin upload directory
        function partyminder_delete_directory($dir) {
            if (!is_dir($dir)) {
                return false;
            }
            
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    partyminder_delete_directory($path);
                } else {
                    unlink($path);
                }
            }
            return rmdir($dir);
        }
        
        partyminder_delete_directory($plugin_upload_dir);
    }

    // 8. Clean up any transients
    $transient_patterns = array(
        '%partyminder_%'
    );

    foreach ($transient_patterns as $pattern) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . $pattern,
            '_transient_timeout_' . $pattern
        ));
    }

    // 9. Clear object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }

    // 10. Log the uninstall for debugging (optional)
    error_log('PartyMinder plugin uninstalled and all data removed at ' . current_time('mysql'));

    // Commit transaction
    $wpdb->query('COMMIT');

} catch (Exception $e) {
    // Rollback on error
    $wpdb->query('ROLLBACK');
    
    // Log the error
    error_log('PartyMinder uninstall failed: ' . $e->getMessage());
}

// Final cleanup - remove the uninstall flag itself
delete_option('partyminder_delete_data_on_uninstall');