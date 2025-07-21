<?php

class PartyMinder_Event_Manager {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_data'));
        // Column management hooks no longer needed since we're using pages now
    }
    
    public function create_event($event_data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($event_data['title']) || empty($event_data['event_date'])) {
            return new WP_Error('missing_data', __('Event title and date are required', 'partyminder'));
        }
        
        // Create WordPress page
        $post_data = array(
            'post_title' => sanitize_text_field($event_data['title']),
            'post_content' => wp_kses_post($event_data['description'] ?? ''),
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id() ?: 1,
            'meta_input' => array(
                '_partyminder_event' => 'true',
                '_partyminder_event_type' => 'single_event'
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Insert extended event data
        $events_table = $wpdb->prefix . 'partyminder_events';
        $result = $wpdb->insert(
            $events_table,
            array(
                'post_id' => $post_id,
                'event_date' => sanitize_text_field($event_data['event_date']),
                'event_time' => sanitize_text_field($event_data['event_time'] ?? ''),
                'guest_limit' => intval($event_data['guest_limit'] ?? 0),
                'venue_info' => sanitize_text_field($event_data['venue'] ?? ''),
                'host_email' => sanitize_email($event_data['host_email'] ?? ''),
                'host_notes' => wp_kses_post($event_data['host_notes'] ?? ''),
                'event_status' => 'active'
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_delete_post($post_id, true);
            $error_msg = $wpdb->last_error ? $wpdb->last_error : __('Failed to create event data', 'partyminder');
            return new WP_Error('creation_failed', $error_msg);
        }
        
        return $post_id;
    }
    
    public function get_event($event_id) {
        global $wpdb;
        
        $post = get_post($event_id);
        if (!$post || $post->post_type !== 'page') {
            return null;
        }
        
        // Check if this page is a PartyMinder event
        if (!get_post_meta($event_id, '_partyminder_event', true)) {
            return null;
        }
        
        $events_table = $wpdb->prefix . 'partyminder_events';
        $event_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $events_table WHERE post_id = %d",
            $event_id
        ));
        
        if (!$event_data) {
            return null;
        }
        
        // Combine data
        $event = new stdClass();
        $event->ID = $post->ID;
        $event->title = $post->post_title;
        $event->description = $post->post_content;
        $event->excerpt = $post->post_excerpt;
        $event->status = $post->post_status;
        $event->author_id = $post->post_author;
        $event->created_date = $post->post_date;
        
        // Extended data
        $event->event_date = $event_data->event_date;
        $event->event_time = $event_data->event_time;
        $event->guest_limit = $event_data->guest_limit;
        $event->venue_info = $event_data->venue_info;
        $event->host_email = $event_data->host_email;
        $event->host_notes = $event_data->host_notes;
        $event->ai_plan = $event_data->ai_plan;
        $event->event_status = $event_data->event_status;
        
        // Get guest stats
        $event->guest_stats = $this->get_guest_stats($event_id);
        
        return $event;
    }
    
    public function migrate_events_to_pages() {
        global $wpdb;
        
        // Find all party_event posts
        $party_events = get_posts(array(
            'post_type' => 'party_event',
            'post_status' => 'any',
            'numberposts' => -1
        ));
        
        $migrated = 0;
        
        foreach ($party_events as $event_post) {
            // Convert to page
            $wpdb->update(
                $wpdb->posts,
                array('post_type' => 'page'),
                array('ID' => $event_post->ID),
                array('%s'),
                array('%d')
            );
            
            // Add PartyMinder meta
            update_post_meta($event_post->ID, '_partyminder_event', 'true');
            update_post_meta($event_post->ID, '_partyminder_event_type', 'single_event');
            
            $migrated++;
        }
        
        // Clean up rewrite rules
        flush_rewrite_rules();
        
        return $migrated;
    }
    
    public function get_upcoming_events($limit = 10) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'partyminder_events';
        $posts_table = $wpdb->posts;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID FROM $posts_table p 
             INNER JOIN $events_table e ON p.ID = e.post_id 
             INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
             WHERE p.post_type = 'page' 
             AND p.post_status = 'publish' 
             AND pm.meta_key = '_partyminder_event'
             AND pm.meta_value = 'true'
             AND e.event_date >= CURDATE()
             AND e.event_status = 'active'
             ORDER BY e.event_date ASC 
             LIMIT %d",
            $limit
        ));
        
        $events = array();
        foreach ($results as $result) {
            $events[] = $this->get_event($result->ID);
        }
        
        return $events;
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
        add_meta_box(
            'partyminder_event_details',
            __('Event Details', 'partyminder'),
            array($this, 'event_details_meta_box'),
            'page',
            'normal',
            'high'
        );
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
    
    // Admin columns
    public function add_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['event_date'] = __('Event Date', 'partyminder');
        $new_columns['guests'] = __('Guests', 'partyminder');
        $new_columns['venue'] = __('Venue', 'partyminder');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    public function populate_columns($column, $post_id) {
        $event = $this->get_event($post_id);
        if (!$event) return;
        
        switch ($column) {
            case 'event_date':
                if ($event->event_date) {
                    echo date('M j, Y g:i A', strtotime($event->event_date));
                }
                break;
                
            case 'guests':
                printf('%d confirmed', $event->guest_stats->confirmed);
                if ($event->guest_limit > 0) {
                    printf(' / %d max', $event->guest_limit);
                }
                break;
                
            case 'venue':
                echo esc_html($event->venue_info);
                break;
        }
    }
    
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