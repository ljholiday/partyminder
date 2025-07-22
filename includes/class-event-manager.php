<?php

class PartyMinder_Event_Manager {
    
    public function __construct() {
        // No more WordPress post/page hooks needed - pure custom table system
    }
    
    public function create_event($event_data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($event_data['title']) || empty($event_data['event_date'])) {
            return new WP_Error('missing_data', __('Event title and date are required', 'partyminder'));
        }
        
        // Generate unique slug
        $slug = $this->generate_unique_slug($event_data['title']);
        
        // Create WordPress post first
        $post_data = array(
            'post_title' => sanitize_text_field($event_data['title']),
            'post_content' => wp_kses_post($event_data['description'] ?? ''),
            'post_excerpt' => wp_trim_words(wp_kses_post($event_data['description'] ?? ''), 25),
            'post_status' => 'publish',
            'post_type' => 'partyminder_event',
            'post_name' => $slug,
            'post_author' => get_current_user_id() ?: 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Add custom post meta
        update_post_meta($post_id, '_partyminder_event_date', sanitize_text_field($event_data['event_date']));
        update_post_meta($post_id, '_partyminder_event_time', sanitize_text_field($event_data['event_time'] ?? ''));
        update_post_meta($post_id, '_partyminder_guest_limit', intval($event_data['guest_limit'] ?? 0));
        update_post_meta($post_id, '_partyminder_venue_info', sanitize_text_field($event_data['venue'] ?? ''));
        update_post_meta($post_id, '_partyminder_host_email', sanitize_email($event_data['host_email'] ?? ''));
        update_post_meta($post_id, '_partyminder_host_notes', wp_kses_post($event_data['host_notes'] ?? ''));
        
        // Insert event data to custom table linked to post
        $events_table = $wpdb->prefix . 'partyminder_events';
        $result = $wpdb->insert(
            $events_table,
            array(
                'post_id' => $post_id,
                'title' => sanitize_text_field($event_data['title']),
                'slug' => $slug,
                'description' => wp_kses_post($event_data['description'] ?? ''),
                'excerpt' => wp_trim_words(wp_kses_post($event_data['description'] ?? ''), 25),
                'event_date' => sanitize_text_field($event_data['event_date']),
                'event_time' => sanitize_text_field($event_data['event_time'] ?? ''),
                'guest_limit' => intval($event_data['guest_limit'] ?? 0),
                'venue_info' => sanitize_text_field($event_data['venue'] ?? ''),
                'host_email' => sanitize_email($event_data['host_email'] ?? ''),
                'host_notes' => wp_kses_post($event_data['host_notes'] ?? ''),
                'event_status' => 'active',
                'author_id' => get_current_user_id() ?: 1,
                'meta_title' => sanitize_text_field($event_data['title']),
                'meta_description' => wp_trim_words(wp_kses_post($event_data['description'] ?? ''), 20)
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            // If custom table insert fails, delete the post
            wp_delete_post($post_id, true);
            $error_msg = $wpdb->last_error ? $wpdb->last_error : __('Failed to create event', 'partyminder');
            return new WP_Error('creation_failed', $error_msg);
        }
        
        return $wpdb->insert_id;
    }
    
    private function generate_unique_slug($title) {
        global $wpdb;
        
        $base_slug = sanitize_title($title);
        $slug = $base_slug;
        $counter = 1;
        
        $events_table = $wpdb->prefix . 'partyminder_events';
        
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $events_table WHERE slug = %s", $slug))) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    public function get_event($event_id) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'partyminder_events';
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $events_table WHERE id = %d",
            $event_id
        ));
        
        if (!$event) {
            return null;
        }
        
        // If event has a linked post, get WordPress post data
        if ($event->post_id) {
            $post = get_post($event->post_id);
            if ($post) {
                // Add post properties to event object
                $event->post_author = $post->post_author;
                $event->post_date = $post->post_date;
                $event->post_status = $post->post_status;
                $event->comment_count = $post->comment_count;
                $event->post_type = $post->post_type;
            }
        }
        
        // Get guest stats
        $event->guest_stats = $this->get_guest_stats($event_id);
        
        return $event;
    }
    
    public function get_event_by_slug($slug) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'partyminder_events';
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $events_table WHERE slug = %s AND event_status = 'active'",
            $slug
        ));
        
        if (!$event) {
            return null;
        }
        
        // If event has a linked post, get WordPress post data
        if ($event->post_id) {
            $post = get_post($event->post_id);
            if ($post) {
                // Add post properties to event object
                $event->post_author = $post->post_author;
                $event->post_date = $post->post_date;
                $event->post_status = $post->post_status;
                $event->comment_count = $post->comment_count;
                $event->post_type = $post->post_type;
            }
        }
        
        // Get guest stats
        $event->guest_stats = $this->get_guest_stats($event->id);
        
        return $event;
    }
    
    
    public function get_upcoming_events($limit = 10) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'partyminder_events';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $events_table 
             WHERE event_date >= CURDATE()
             AND event_status = 'active'
             ORDER BY event_date ASC 
             LIMIT %d",
            $limit
        ));
        
        // Add guest stats to each event
        foreach ($results as $event) {
            $event->guest_stats = $this->get_guest_stats($event->id);
        }
        
        return $results;
    }
    
    public function get_guest_stats($event_id) {
        global $wpdb;
        
        $guests_table = $wpdb->prefix . 'partyminder_guests';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'maybe' THEN 1 ELSE 0 END) as maybe
            FROM $guests_table WHERE event_id = %d",
            $event_id
        ));
        
        return $stats ?: (object) array('total' => 0, 'confirmed' => 0, 'declined' => 0, 'pending' => 0, 'maybe' => 0);
    }
    
    
    public function update_event($event_id, $event_data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($event_data['title']) || empty($event_data['event_date'])) {
            return new WP_Error('missing_data', __('Event title and date are required', 'partyminder'));
        }
        
        // Get current event
        $current_event = $this->get_event($event_id);
        if (!$current_event) {
            return new WP_Error('event_not_found', __('Event not found', 'partyminder'));
        }
        
        // Generate unique slug if title changed
        $slug = $current_event->slug;
        if ($current_event->title !== $event_data['title']) {
            $slug = $this->generate_unique_slug($event_data['title']);
        }
        
        // Update WordPress post if it exists
        if ($current_event->post_id) {
            $post_data = array(
                'ID' => $current_event->post_id,
                'post_title' => sanitize_text_field($event_data['title']),
                'post_content' => wp_kses_post($event_data['description'] ?? ''),
                'post_excerpt' => wp_trim_words(wp_kses_post($event_data['description'] ?? ''), 25),
                'post_name' => $slug
            );
            
            $post_result = wp_update_post($post_data);
            
            if (is_wp_error($post_result)) {
                return $post_result;
            }
            
            // Update post meta
            update_post_meta($current_event->post_id, '_partyminder_event_date', sanitize_text_field($event_data['event_date']));
            update_post_meta($current_event->post_id, '_partyminder_event_time', sanitize_text_field($event_data['event_time'] ?? ''));
            update_post_meta($current_event->post_id, '_partyminder_guest_limit', intval($event_data['guest_limit'] ?? 0));
            update_post_meta($current_event->post_id, '_partyminder_venue_info', sanitize_text_field($event_data['venue'] ?? ''));
            update_post_meta($current_event->post_id, '_partyminder_host_email', sanitize_email($event_data['host_email'] ?? ''));
            update_post_meta($current_event->post_id, '_partyminder_host_notes', wp_kses_post($event_data['host_notes'] ?? ''));
        }
        
        // Update event data in custom table
        $events_table = $wpdb->prefix . 'partyminder_events';
        $update_data = array(
            'title' => sanitize_text_field($event_data['title']),
            'slug' => $slug,
            'description' => wp_kses_post($event_data['description'] ?? ''),
            'excerpt' => wp_trim_words(wp_kses_post($event_data['description'] ?? ''), 25),
            'event_date' => sanitize_text_field($event_data['event_date']),
            'event_time' => sanitize_text_field($event_data['event_time'] ?? ''),
            'guest_limit' => intval($event_data['guest_limit'] ?? 0),
            'venue_info' => sanitize_text_field($event_data['venue'] ?? ''),
            'host_email' => sanitize_email($event_data['host_email'] ?? ''),
            'host_notes' => wp_kses_post($event_data['host_notes'] ?? ''),
            'meta_title' => sanitize_text_field($event_data['title']),
            'meta_description' => wp_trim_words(wp_kses_post($event_data['description'] ?? ''), 20)
        );
        
        $result = $wpdb->update(
            $events_table,
            $update_data,
            array('id' => $event_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update event data', 'partyminder'));
        }
        
        return $event_id;
    }
    
    public function migrate_existing_events() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'partyminder_events';
        
        // Get all events without linked posts
        $events = $wpdb->get_results(
            "SELECT * FROM $events_table WHERE post_id IS NULL OR post_id = 0"
        );
        
        $migrated_count = 0;
        
        foreach ($events as $event) {
            // Create WordPress post for this event
            $post_data = array(
                'post_title' => $event->title,
                'post_content' => $event->description,
                'post_excerpt' => $event->excerpt,
                'post_status' => 'publish',
                'post_type' => 'partyminder_event',
                'post_name' => $event->slug,
                'post_author' => $event->author_id ?: 1,
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_date' => $event->created_at,
                'post_modified' => $event->updated_at
            );
            
            $post_id = wp_insert_post($post_data);
            
            if (!is_wp_error($post_id)) {
                // Add custom post meta
                update_post_meta($post_id, '_partyminder_event_date', $event->event_date);
                update_post_meta($post_id, '_partyminder_event_time', $event->event_time);
                update_post_meta($post_id, '_partyminder_guest_limit', $event->guest_limit);
                update_post_meta($post_id, '_partyminder_venue_info', $event->venue_info);
                update_post_meta($post_id, '_partyminder_host_email', $event->host_email);
                update_post_meta($post_id, '_partyminder_host_notes', $event->host_notes);
                
                // Update the event record with the post ID
                $wpdb->update(
                    $events_table,
                    array('post_id' => $post_id),
                    array('id' => $event->id),
                    array('%d'),
                    array('%d')
                );
                
                $migrated_count++;
            }
        }
        
        return $migrated_count;
    }
    
}