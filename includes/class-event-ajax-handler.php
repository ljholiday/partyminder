<?php

class PartyMinder_Event_Ajax_Handler {
    
    private $event_manager;
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_partyminder_create_event', array($this, 'ajax_create_event'));
        add_action('wp_ajax_nopriv_partyminder_create_event', array($this, 'ajax_create_event'));
        add_action('wp_ajax_partyminder_update_event', array($this, 'ajax_update_event'));
        add_action('wp_ajax_nopriv_partyminder_update_event', array($this, 'ajax_update_event'));
        add_action('wp_ajax_partyminder_get_event_conversations', array($this, 'ajax_get_event_conversations'));
        add_action('wp_ajax_nopriv_partyminder_get_event_conversations', array($this, 'ajax_get_event_conversations'));
        add_action('wp_ajax_partyminder_send_event_invitation', array($this, 'ajax_send_event_invitation'));
        add_action('wp_ajax_partyminder_get_event_invitations', array($this, 'ajax_get_event_invitations'));
        add_action('wp_ajax_partyminder_cancel_event_invitation', array($this, 'ajax_cancel_event_invitation'));
        add_action('wp_ajax_partyminder_get_event_stats', array($this, 'ajax_get_event_stats'));
        add_action('wp_ajax_partyminder_get_event_guests', array($this, 'ajax_get_event_guests'));
        add_action('wp_ajax_partyminder_delete_event', array($this, 'ajax_delete_event'));
        
        if (is_admin()) {
            add_action('wp_ajax_partyminder_admin_delete_event', array($this, 'ajax_admin_delete_event'));
        }
    }
    
    private function get_event_manager() {
        if (!$this->event_manager) {
            require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
            $this->event_manager = new PartyMinder_Event_Manager();
        }
        return $this->event_manager;
    }
    
    public function ajax_create_event() {
        check_ajax_referer('create_partyminder_event', 'partyminder_event_nonce');
        
        $form_errors = array();
        if (empty($_POST['event_title'])) {
            $form_errors[] = __('Event title is required.', 'partyminder');
        }
        if (empty($_POST['event_date'])) {
            $form_errors[] = __('Event date is required.', 'partyminder');
        }
        if (empty($_POST['host_email'])) {
            $form_errors[] = __('Host email is required.', 'partyminder');
        }
        
        if (!empty($form_errors)) {
            wp_send_json_error(implode(' ', $form_errors));
        }
        
        $event_data = array(
            'title' => sanitize_text_field(wp_unslash($_POST['event_title'])),
            'description' => wp_kses_post(wp_unslash($_POST['event_description'])),
            'event_date' => sanitize_text_field($_POST['event_date']),
            'venue' => sanitize_text_field($_POST['venue_info']),
            'guest_limit' => intval($_POST['guest_limit']),
            'host_email' => sanitize_email($_POST['host_email']),
            'host_notes' => wp_kses_post(wp_unslash($_POST['host_notes']))
        );
        
        $event_manager = $this->get_event_manager();
        $event_id = $event_manager->create_event($event_data);
        
        if (!is_wp_error($event_id)) {
            $created_event = $event_manager->get_event($event_id);
            
            $creation_data = array(
                'event_id' => $event_id,
                'event_url' => home_url('/events/' . $created_event->slug),
                'event_title' => $created_event->title
            );
            set_transient('partyminder_event_created_' . get_current_user_id(), $creation_data, 300);
            
            wp_send_json_success(array(
                'event_id' => $event_id,
                'message' => __('Event created successfully!', 'partyminder'),
                'event_url' => home_url('/events/' . $created_event->slug)
            ));
        } else {
            wp_send_json_error($event_id->get_error_message());
        }
    }
    
    public function ajax_update_event() {
        check_ajax_referer('edit_partyminder_event', 'partyminder_edit_event_nonce');
        
        $event_id = intval($_POST['event_id']);
        if (!$event_id) {
            wp_send_json_error(__('Event ID is required.', 'partyminder'));
        }
        
        $event_manager = $this->get_event_manager();
        $event = $event_manager->get_event($event_id);
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
        }
        
        $current_user = wp_get_current_user();
        $can_edit = false;
        
        if (current_user_can('edit_posts') || 
            (is_user_logged_in() && $current_user->ID == $event->author_id) ||
            ($current_user->user_email == $event->host_email)) {
            $can_edit = true;
        }
        
        if (!$can_edit) {
            wp_send_json_error(__('You do not have permission to edit this event.', 'partyminder'));
        }
        
        $form_errors = array();
        if (empty($_POST['event_title'])) {
            $form_errors[] = __('Event title is required.', 'partyminder');
        }
        if (empty($_POST['event_date'])) {
            $form_errors[] = __('Event date is required.', 'partyminder');
        }
        if (empty($_POST['host_email'])) {
            $form_errors[] = __('Host email is required.', 'partyminder');
        }
        
        if (!empty($form_errors)) {
            wp_send_json_error(implode(' ', $form_errors));
        }
        
        $event_data = array(
            'id' => $event_id,
            'title' => sanitize_text_field(wp_unslash($_POST['event_title'])),
            'description' => wp_kses_post(wp_unslash($_POST['event_description'])),
            'event_date' => sanitize_text_field($_POST['event_date']),
            'venue' => sanitize_text_field($_POST['venue_info']),
            'guest_limit' => intval($_POST['guest_limit']),
            'host_email' => sanitize_email($_POST['host_email']),
            'host_notes' => wp_kses_post(wp_unslash($_POST['host_notes']))
        );
        
        $result = $event_manager->update_event($event_data);
        
        if ($result !== false) {
            $updated_event = $event_manager->get_event($event_id);
            wp_send_json_success(array(
                'message' => __('Event updated successfully!', 'partyminder'),
                'event_url' => home_url('/events/' . $updated_event->slug)
            ));
        } else {
            wp_send_json_error(__('Failed to update event. Please try again.', 'partyminder'));
        }
    }
    
    public function ajax_get_event_conversations() {
        check_ajax_referer('partyminder_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id']);
        if (!$event_id) {
            wp_send_json_error(__('Event ID is required.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        $user_id = 0;
        
        if (is_user_logged_in()) {
            $user_email = $current_user->user_email;
            $user_name = $current_user->display_name;
            $user_id = $current_user->ID;
        } else {
            $user_email = sanitize_email($_POST['guest_email']);
            $user_name = sanitize_text_field($_POST['guest_name']);
            
            if (empty($user_email) || empty($user_name)) {
                wp_send_json_error(__('Email and name are required for guest access.', 'partyminder'));
                return;
            }
        }
        
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
        $conversation_manager = new PartyMinder_Conversation_Manager();
        $conversations = $conversation_manager->get_event_conversations($event_id);
        
        wp_send_json_success(array(
            'conversations' => $conversations,
            'user_email' => $user_email,
            'user_name' => $user_name,
            'user_id' => $user_id
        ));
    }
    
    public function ajax_send_event_invitation() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        $email = sanitize_email($_POST['email']);
        
        if (!$event_id || !$email) {
            wp_send_json_error(__('Event ID and email are required.', 'partyminder'));
            return;
        }
        
        $event_manager = $this->get_event_manager();
        $event = $event_manager->get_event($event_id);
        
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($event->author_id != $current_user->ID && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Only the event host can send invitations.', 'partyminder'));
            return;
        }
        
        global $wpdb;
        $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $invitations_table WHERE event_id = %d AND invited_email = %s",
            $event_id,
            $email
        ));
        
        if ($existing) {
            wp_send_json_error(__('This email has already been invited.', 'partyminder'));
            return;
        }
        
        $invitation_token = wp_generate_uuid4();
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $result = $wpdb->insert(
            $invitations_table,
            array(
                'event_id' => $event_id,
                'invited_by_user_id' => $current_user->ID,
                'invited_email' => $email,
                'invitation_token' => $invitation_token,
                'message' => $message,
                'status' => 'pending',
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(__('Failed to create invitation.', 'partyminder'));
            return;
        }
        
        $invitation_url = add_query_arg(
            array(
                'invitation' => $invitation_token,
                'event' => $event_id
            ),
            home_url('/events/' . $event->slug)
        );
        
        $subject = sprintf(__('Invitation to %s', 'partyminder'), $event->title);
        $message = sprintf(
            __("You've been invited to %s!\n\nEvent Details:\n%s\n\nDate: %s\nVenue: %s\n\nRSVP here: %s", 'partyminder'),
            $event->title,
            $event->description,
            $event->event_date,
            $event->venue,
            $invitation_url
        );
        
        $sent = wp_mail($email, $subject, $message);
        
        if ($sent) {
            wp_send_json_success(array(
                'message' => __('Invitation sent successfully!', 'partyminder')
            ));
        } else {
            wp_send_json_error(__('Failed to send invitation email.', 'partyminder'));
        }
    }
    
    public function ajax_get_event_invitations() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        $event_id = intval($_POST['event_id']);
        if (!$event_id) {
            wp_send_json_error(__('Event ID is required.', 'partyminder'));
            return;
        }
        
        $event_manager = $this->get_event_manager();
        $event = $event_manager->get_event($event_id);
        
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($event->author_id != $current_user->ID && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Only the event host can view invitations.', 'partyminder'));
            return;
        }
        
        global $wpdb;
        $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
        
        $invitations = $wpdb->get_results($wpdb->prepare(
            "SELECT ei.*, u.display_name as invited_by_name 
             FROM $invitations_table ei 
             LEFT JOIN {$wpdb->users} u ON ei.invited_by_user_id = u.ID 
             WHERE ei.event_id = %d 
             ORDER BY ei.created_at DESC",
            $event_id
        ));
        
        foreach ($invitations as &$invitation) {
            $invitation->invitation_url = add_query_arg(
                array(
                    'invitation' => $invitation->invitation_token,
                    'event' => $event_id
                ),
                home_url('/events/' . $event->slug)
            );
        }
        
        // Generate HTML for invitations list
        $html = '';
        if (empty($invitations)) {
            $html = '<div class="pm-text-center pm-text-muted">' . __('No pending invitations.', 'partyminder') . '</div>';
        } else {
            foreach ($invitations as $invitation) {
                $html .= '<div class="pm-flex pm-flex-between pm-p-4 pm-mb-4 pm-border-bottom">';
                $html .= '<div class="pm-flex-1">';
                $html .= '<div class="pm-flex pm-gap">';
                $html .= '<strong>' . esc_html($invitation->invited_email) . '</strong>';
                $html .= '<span class="pm-badge pm-badge-' . ($invitation->status === 'pending' ? 'warning' : 'success') . '">' . esc_html(ucfirst($invitation->status)) . '</span>';
                $html .= '</div>';
                $html .= '<div class="pm-text-muted pm-mt-2">';
                $html .= sprintf(__('Invited by %s on %s', 'partyminder'), 
                    esc_html($invitation->invited_by_name ?? 'Unknown'),
                    date('M j, Y', strtotime($invitation->created_at))
                );
                $html .= '</div>';
                if (!empty($invitation->message)) {
                    $html .= '<div class="pm-text-muted pm-mt-2"><em>"' . esc_html($invitation->message) . '"</em></div>';
                }
                $html .= '</div>';
                if ($invitation->status === 'pending') {
                    $html .= '<div class="pm-flex pm-gap">';
                    $html .= '<button type="button" class="pm-btn pm-btn-sm pm-btn-secondary" onclick="copyInvitationUrl(\'' . esc_js($invitation->invitation_url) . '\')">' . __('Copy Link', 'partyminder') . '</button>';
                    $html .= '<button type="button" class="pm-btn pm-btn-sm pm-btn-danger cancel-event-invitation" data-invitation-id="' . esc_attr($invitation->invitation_token) . '">' . __('Cancel', 'partyminder') . '</button>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
        }
        
        wp_send_json_success(array(
            'invitations' => $invitations,
            'html' => $html
        ));
    }
    
    public function ajax_cancel_event_invitation() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $invitation_token = sanitize_text_field($_POST['invitation_id']);
        if (!$invitation_token) {
            wp_send_json_error(__('Invitation ID is required.', 'partyminder'));
            return;
        }
        
        global $wpdb;
        $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
        
        $invitation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $invitations_table WHERE invitation_token = %s",
            $invitation_token
        ));
        
        if (!$invitation) {
            wp_send_json_error(__('Invitation not found.', 'partyminder'));
            return;
        }
        
        $event_manager = $this->get_event_manager();
        $event = $event_manager->get_event($invitation->event_id);
        
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($event->author_id != $current_user->ID && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Only the event host can cancel invitations.', 'partyminder'));
            return;
        }
        
        $result = $wpdb->delete(
            $invitations_table,
            array('invitation_token' => $invitation_token),
            array('%s')
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Invitation cancelled successfully.', 'partyminder')
            ));
        } else {
            wp_send_json_error(__('Failed to cancel invitation.', 'partyminder'));
        }
    }
    
    public function ajax_get_event_stats() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        if (!$event_id) {
            wp_send_json_error(__('Event ID is required.', 'partyminder'));
            return;
        }
        
        $event_manager = $this->get_event_manager();
        $event = $event_manager->get_event($event_id);
        
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($event->author_id != $current_user->ID && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Only the event host can view statistics.', 'partyminder'));
            return;
        }
        
        global $wpdb;
        $rsvps_table = $wpdb->prefix . 'partyminder_rsvps';
        $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
        
        $stats = array(
            'total_rsvps' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $rsvps_table WHERE event_id = %d",
                $event_id
            )),
            'attending' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $rsvps_table WHERE event_id = %d AND status = 'attending'",
                $event_id
            )),
            'not_attending' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $rsvps_table WHERE event_id = %d AND status = 'not_attending'",
                $event_id
            )),
            'maybe' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $rsvps_table WHERE event_id = %d AND status = 'maybe'",
                $event_id
            )),
            'invitations_sent' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $invitations_table WHERE event_id = %d",
                $event_id
            ))
        );
        
        wp_send_json_success($stats);
    }
    
    public function ajax_get_event_guests() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        if (!$event_id) {
            wp_send_json_error(__('Event ID is required.', 'partyminder'));
            return;
        }
        
        $event_manager = $this->get_event_manager();
        $event = $event_manager->get_event($event_id);
        
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($event->author_id != $current_user->ID && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('Only the event host can view the guest list.', 'partyminder'));
            return;
        }
        
        global $wpdb;
        $rsvps_table = $wpdb->prefix . 'partyminder_rsvps';
        
        $guests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $rsvps_table WHERE event_id = %d ORDER BY created_at DESC",
            $event_id
        ));
        
        wp_send_json_success(array(
            'guests' => $guests
        ));
    }
    
    public function ajax_delete_event() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        if (!$event_id) {
            wp_send_json_error(__('Event ID is required.', 'partyminder'));
            return;
        }
        
        $event_manager = $this->get_event_manager();
        $event = $event_manager->get_event($event_id);
        
        if (!$event) {
            wp_send_json_error(__('Event not found.', 'partyminder'));
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($event->author_id != $current_user->ID && !current_user_can('edit_others_posts')) {
            wp_send_json_error(__('You do not have permission to delete this event.', 'partyminder'));
            return;
        }
        
        $result = $event_manager->delete_event($event_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Event deleted successfully.', 'partyminder'),
                'redirect_url' => home_url('/my-events')
            ));
        } else {
            wp_send_json_error(__('Failed to delete event.', 'partyminder'));
        }
    }
    
    public function ajax_admin_delete_event() {
        check_ajax_referer('partyminder_event_action', 'nonce');
        
        if (!current_user_can('delete_others_posts')) {
            wp_send_json_error(__('You do not have permission to delete events.', 'partyminder'));
            return;
        }
        
        $event_id = intval($_POST['event_id']);
        if (!$event_id) {
            wp_send_json_error(__('Event ID is required.', 'partyminder'));
            return;
        }
        
        $event_manager = $this->get_event_manager();
        $result = $event_manager->delete_event($event_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Event deleted successfully.', 'partyminder')
            ));
        } else {
            wp_send_json_error(__('Failed to delete event.', 'partyminder'));
        }
    }
}