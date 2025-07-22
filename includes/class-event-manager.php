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
        
        // Insert event data directly to custom table
        $events_table = $wpdb->prefix . 'partyminder_events';
        $result = $wpdb->insert(
            $events_table,
            array(
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
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
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
        
        // Generate unique slug if title changed
        $current_event = $this->get_event($event_id);
        $slug = $current_event->slug;
        if ($current_event->title !== $event_data['title']) {
            $slug = $this->generate_unique_slug($event_data['title']);
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
    
}