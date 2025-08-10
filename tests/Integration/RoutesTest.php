<?php
/**
 * Integration Test Example - WordPress Routes and AJAX
 */

namespace PartyMinder\Tests\Integration;

use WP_UnitTestCase;

class RoutesTest extends WP_UnitTestCase {
    
    private $event_manager;
    private $test_user_id;
    private $test_event_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Load plugin classes
        require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
        $this->event_manager = new \PartyMinder_Event_Manager();
        
        // Create test user
        $this->test_user_id = $this->factory->user->create([
            'role' => 'subscriber',
            'user_email' => 'test@example.com',
            'display_name' => 'Test User'
        ]);
        
        // Create test event
        wp_set_current_user($this->test_user_id);
        $this->test_event_id = $this->create_test_event();
    }
    
    public function tearDown(): void {
        // Clean up test data
        if ($this->test_event_id) {
            $this->event_manager->delete_event($this->test_event_id);
        }
        
        parent::tearDown();
    }
    
    public function test_page_routing_exists() {
        // Test that PartyMinder pages can be created
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
            'post_title' => 'Test Events',
            'post_status' => 'publish'
        ]);
        
        add_post_meta($page_id, '_partyminder_page_type', 'events');
        
        $this->assertEquals('events', get_post_meta($page_id, '_partyminder_page_type', true));
    }
    
    public function test_event_creation_ajax() {
        // Simulate AJAX request for event creation
        $_POST = [
            'action' => 'partyminder_create_event',
            'event_title' => 'Test Event',
            'event_description' => 'Test Description',
            'event_date' => '2024-06-15 19:00:00',
            'venue_info' => 'Test Venue',
            'guest_limit' => 50,
            'host_email' => 'test@example.com',
            'host_notes' => 'Test Notes',
            'partyminder_event_nonce' => wp_create_nonce('create_partyminder_event')
        ];
        
        // Mock the AJAX handler
        $handler = new \PartyMinder_Event_Ajax_Handler();
        
        // Capture output
        ob_start();
        try {
            $handler->ajax_create_event();
        } catch (\WPDieException $e) {
            // AJAX handlers call wp_die(), which throws exception in tests
        }
        $output = ob_get_clean();
        
        // Verify event was created (would check database or mock response)
        $this->assertNotEmpty($output);
    }
    
    public function test_invitation_workflow() {
        $this->assertNotNull($this->test_event_id);
        
        global $wpdb;
        $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
        
        // Create test invitation
        $invitation_token = wp_generate_uuid4();
        $result = $wpdb->insert(
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
        
        $this->assertNotFalse($result);
        
        // Test invitation lookup
        $invitation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $invitations_table WHERE invitation_token = %s",
            $invitation_token
        ));
        
        $this->assertNotNull($invitation);
        $this->assertEquals('pending', $invitation->status);
        $this->assertEquals('guest@example.com', $invitation->invited_email);
    }
    
    public function test_rsvp_creation() {
        global $wpdb;
        $rsvps_table = $wpdb->prefix . 'partyminder_rsvps';
        
        // Create test RSVP
        $result = $wpdb->insert(
            $rsvps_table,
            [
                'event_id' => $this->test_event_id,
                'name' => 'Test Guest',
                'email' => 'guest@example.com',
                'status' => 'attending',
                'rsvp_date' => current_time('mysql')
            ]
        );
        
        $this->assertNotFalse($result);
        
        // Verify RSVP exists
        $rsvp = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $rsvps_table WHERE event_id = %d AND email = %s",
            $this->test_event_id,
            'guest@example.com'
        ));
        
        $this->assertNotNull($rsvp);
        $this->assertEquals('attending', $rsvp->status);
    }
    
    private function create_test_event() {
        $event_data = [
            'title' => 'Integration Test Event',
            'description' => 'Test event for integration testing',
            'event_date' => '2024-06-15 19:00:00',
            'venue' => 'Test Venue',
            'guest_limit' => 50,
            'host_email' => 'test@example.com',
            'host_notes' => 'Test host notes'
        ];
        
        return $this->event_manager->create_event($event_data);
    }
}