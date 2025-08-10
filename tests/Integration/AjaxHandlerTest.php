<?php
/**
 * Integration Test for AJAX Handlers
 */

namespace PartyMinder\Tests\Integration;

use WP_UnitTestCase;

class AjaxHandlerTest extends WP_UnitTestCase {
    
    private $event_ajax_handler;
    private $test_user_id;
    private $test_event_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Load AJAX handler
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-ajax-handler.php';
        $this->event_ajax_handler = new \PartyMinder_Event_Ajax_Handler();
        
        // Create test user
        $this->test_user_id = $this->factory->user->create([
            'role' => 'subscriber',
            'user_email' => 'testhost@example.com',
            'display_name' => 'Test Host'
        ]);
        
        // Create test event
        wp_set_current_user($this->test_user_id);
        $this->test_event_id = $this->create_test_event();
    }
    
    public function tearDown(): void {
        // Clean up test data
        global $wpdb;
        if ($this->test_event_id) {
            $wpdb->delete(
                $wpdb->prefix . 'partyminder_events',
                ['id' => $this->test_event_id],
                ['%d']
            );
        }
        
        parent::tearDown();
    }
    
    public function test_send_invitation_ajax_requires_login() {
        // Log out user
        wp_set_current_user(0);
        
        $_POST = [
            'action' => 'partyminder_send_event_invitation',
            'event_id' => $this->test_event_id,
            'email' => 'guest@example.com',
            'message' => 'Test invitation',
            'nonce' => wp_create_nonce('partyminder_event_action')
        ];
        
        // Capture JSON response
        $this->expectException('WPDieException');
        $this->event_ajax_handler->ajax_send_event_invitation();
    }
    
    public function test_send_invitation_ajax_validates_nonce() {
        wp_set_current_user($this->test_user_id);
        
        $_POST = [
            'action' => 'partyminder_send_event_invitation',
            'event_id' => $this->test_event_id,
            'email' => 'guest@example.com',
            'message' => 'Test invitation',
            'nonce' => 'invalid_nonce'
        ];
        
        $this->expectException('WPDieException');
        $this->event_ajax_handler->ajax_send_event_invitation();
    }
    
    public function test_send_invitation_ajax_validates_required_fields() {
        wp_set_current_user($this->test_user_id);
        
        // Missing email
        $_POST = [
            'action' => 'partyminder_send_event_invitation',
            'event_id' => $this->test_event_id,
            'message' => 'Test invitation',
            'nonce' => wp_create_nonce('partyminder_event_action')
        ];
        
        ob_start();
        try {
            $this->event_ajax_handler->ajax_send_event_invitation();
        } catch (\WPDieException $e) {
            // Expected for AJAX handlers
        }
        $output = ob_get_clean();
        
        $this->assertStringContainsString('error', $output);
    }
    
    public function test_get_event_invitations_returns_html() {
        wp_set_current_user($this->test_user_id);
        
        // First create an invitation
        global $wpdb;
        $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
        
        $wpdb->insert(
            $invitations_table,
            [
                'event_id' => $this->test_event_id,
                'invited_by_user_id' => $this->test_user_id,
                'invited_email' => 'guest@example.com',
                'invitation_token' => wp_generate_uuid4(),
                'message' => 'Test invitation',
                'status' => 'pending',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'created_at' => current_time('mysql')
            ]
        );
        
        $_POST = [
            'action' => 'partyminder_get_event_invitations',
            'event_id' => $this->test_event_id,
            'nonce' => wp_create_nonce('partyminder_event_action')
        ];
        
        ob_start();
        try {
            $this->event_ajax_handler->ajax_get_event_invitations();
        } catch (\WPDieException $e) {
            // Expected for AJAX handlers
        }
        $output = ob_get_clean();
        
        // Should contain JSON response with HTML
        $this->assertStringContainsString('guest@example.com', $output);
        $this->assertStringContainsString('Copy Link', $output);
        $this->assertStringContainsString('Cancel', $output);
    }
    
    public function test_cancel_invitation_removes_invitation() {
        wp_set_current_user($this->test_user_id);
        
        global $wpdb;
        $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
        
        $invitation_token = wp_generate_uuid4();
        $wpdb->insert(
            $invitations_table,
            [
                'event_id' => $this->test_event_id,
                'invited_by_user_id' => $this->test_user_id,
                'invited_email' => 'guest@example.com',
                'invitation_token' => $invitation_token,
                'message' => 'Test invitation',
                'status' => 'pending',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'created_at' => current_time('mysql')
            ]
        );
        
        $_POST = [
            'action' => 'partyminder_cancel_event_invitation',
            'event_id' => $this->test_event_id,
            'invitation_id' => $invitation_token,
            'nonce' => wp_create_nonce('partyminder_event_action')
        ];
        
        ob_start();
        try {
            $this->event_ajax_handler->ajax_cancel_event_invitation();
        } catch (\WPDieException $e) {
            // Expected for AJAX handlers
        }
        $output = ob_get_clean();
        
        // Verify invitation was deleted
        $invitation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $invitations_table WHERE invitation_token = %s",
            $invitation_token
        ));
        
        $this->assertNull($invitation);
        $this->assertStringContainsString('success', $output);
    }
    
    private function create_test_event() {
        global $wpdb;
        $events_table = $wpdb->prefix . 'partyminder_events';
        
        $result = $wpdb->insert(
            $events_table,
            [
                'title' => 'Integration Test Event',
                'description' => 'Test event for integration testing',
                'event_date' => '2024-06-15 19:00:00',
                'venue_info' => 'Test Venue',
                'guest_limit' => 50,
                'host_email' => 'testhost@example.com',
                'host_notes' => 'Test host notes',
                'slug' => 'integration-test-event',
                'author_id' => $this->test_user_id,
                'event_status' => 'active',
                'privacy' => 'public',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
}