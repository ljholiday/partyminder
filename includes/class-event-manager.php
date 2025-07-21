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
    
    private function get_guest_stats($event_id) {
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
    
    // Admin meta boxes
    public function add_meta_boxes() {
        global $post;
        
        // Only add meta box to PartyMinder event pages
        if ($post && get_post_meta($post->ID, '_partyminder_event', true)) {
            add_meta_box(
                'partyminder_event_details',
                __('Event Details', 'partyminder'),
                array($this, 'event_details_meta_box'),
                'page',
                'normal',
                'high'
            );
        }
    }
    
    public function event_details_meta_box($post) {
        wp_nonce_field('partyminder_event_meta', 'partyminder_event_nonce');
        
        $event = $this->get_event($post->ID);
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="event_date">' . __('Event Date & Time', 'partyminder') . '</label></th>';
        echo '<td>';
        $datetime = $event ? date('Y-m-d\TH:i', strtotime($event->event_date)) : '';
        echo '<input type="datetime-local" id="event_date" name="event_date" value="' . esc_attr($datetime) . '" style="width: 100%;" />';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="guest_limit">' . __('Guest Limit', 'partyminder') . '</label></th>';
        echo '<td>';
        echo '<input type="number" id="guest_limit" name="guest_limit" value="' . esc_attr($event->guest_limit ?? '') . '" min="0" />';
        echo '<p class="description">' . __('Leave 0 for unlimited guests', 'partyminder') . '</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="venue_info">' . __('Venue', 'partyminder') . '</label></th>';
        echo '<td>';
        echo '<input type="text" id="venue_info" name="venue_info" value="' . esc_attr($event->venue_info ?? '') . '" style="width: 100%;" />';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="host_email">' . __('Host Email', 'partyminder') . '</label></th>';
        echo '<td>';
        echo '<input type="email" id="host_email" name="host_email" value="' . esc_attr($event->host_email ?? '') . '" style="width: 100%;" />';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="host_notes">' . __('Host Notes', 'partyminder') . '</label></th>';
        echo '<td>';
        echo '<textarea id="host_notes" name="host_notes" rows="3" style="width: 100%;">' . esc_textarea($event->host_notes ?? '') . '</textarea>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        
        if ($event && $event->guest_stats->total > 0) {
            echo '<h4>' . __('RSVP Summary', 'partyminder') . '</h4>';
            echo '<p>';
            printf(__('Confirmed: %d | Pending: %d | Declined: %d', 'partyminder'), 
                $event->guest_stats->confirmed,
                $event->guest_stats->pending,
                $event->guest_stats->declined
            );
            echo '</p>';
        }
    }
    
    public function save_meta_data($post_id) {
        if (!isset($_POST['partyminder_event_nonce']) || 
            !wp_verify_nonce($_POST['partyminder_event_nonce'], 'partyminder_event_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        global $wpdb;
        $events_table = $wpdb->prefix . 'partyminder_events';
        
        $update_data = array();
        
        if (isset($_POST['event_date'])) {
            $update_data['event_date'] = sanitize_text_field($_POST['event_date']);
        }
        if (isset($_POST['guest_limit'])) {
            $update_data['guest_limit'] = intval($_POST['guest_limit']);
        }
        if (isset($_POST['venue_info'])) {
            $update_data['venue_info'] = sanitize_text_field($_POST['venue_info']);
        }
        if (isset($_POST['host_email'])) {
            $update_data['host_email'] = sanitize_email($_POST['host_email']);
        }
        if (isset($_POST['host_notes'])) {
            $update_data['host_notes'] = wp_kses_post($_POST['host_notes']);
        }
        
        if (!empty($update_data)) {
            $wpdb->update(
                $events_table,
                $update_data,
                array('post_id' => $post_id),
                null,
                array('%d')
            );
        }
    }
    
    // Admin columns no longer needed since we use pages now
    
    public function update_event($event_id, $event_data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($event_data['title']) || empty($event_data['event_date'])) {
            return new WP_Error('missing_data', __('Event title and date are required', 'partyminder'));
        }
        
        // Update WordPress post
        $post_data = array(
            'ID' => $event_id,
            'post_title' => sanitize_text_field($event_data['title']),
            'post_content' => wp_kses_post($event_data['description'] ?? '')
        );
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Update extended event data
        $events_table = $wpdb->prefix . 'partyminder_events';
        $update_data = array(
            'event_date' => sanitize_text_field($event_data['event_date']),
            'event_time' => sanitize_text_field($event_data['event_time'] ?? ''),
            'guest_limit' => intval($event_data['guest_limit'] ?? 0),
            'venue_info' => sanitize_text_field($event_data['venue'] ?? ''),
            'host_email' => sanitize_email($event_data['host_email'] ?? ''),
            'host_notes' => wp_kses_post($event_data['host_notes'] ?? '')
        );
        
        $result = $wpdb->update(
            $events_table,
            $update_data,
            array('post_id' => $event_id),
            array('%s', '%s', '%d', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update event data', 'partyminder'));
        }
        
        return $event_id;
    }
    
}