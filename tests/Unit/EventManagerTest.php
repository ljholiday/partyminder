<?php
/**
 * Unit Test for Event Manager Helper Methods
 */

namespace PartyMinder\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class EventManagerTest extends TestCase {
    
    use MockeryPHPUnitIntegration;
    
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = \Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    
    public function test_event_data_validation() {
        $valid_data = [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'event_date' => '2024-06-15 19:00:00',
            'venue' => 'Test Venue',
            'guest_limit' => 50,
            'host_email' => 'test@example.com',
            'host_notes' => 'Test Notes'
        ];
        
        $this->assertTrue($this->validate_event_data($valid_data));
    }
    
    public function test_event_data_validation_fails_with_missing_title() {
        $invalid_data = [
            'description' => 'Test Description',
            'event_date' => '2024-06-15 19:00:00',
            'venue' => 'Test Venue',
            'guest_limit' => 50,
            'host_email' => 'test@example.com'
        ];
        
        $this->assertFalse($this->validate_event_data($invalid_data));
    }
    
    public function test_event_data_validation_fails_with_invalid_email() {
        $invalid_data = [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'event_date' => '2024-06-15 19:00:00',
            'venue' => 'Test Venue',
            'guest_limit' => 50,
            'host_email' => 'invalid-email',
            'host_notes' => 'Test Notes'
        ];
        
        $this->assertFalse($this->validate_event_data($invalid_data));
    }
    
    public function test_event_slug_generation() {
        $title = 'My Amazing Summer BBQ Party!';
        $expected_slug = 'my-amazing-summer-bbq-party';
        
        $this->assertEquals($expected_slug, $this->generate_event_slug($title));
    }
    
    public function test_event_status_classification() {
        // Test with current time as 2024-01-15 14:30:00
        $past_event = '2024-01-14 19:00:00';
        $current_event = '2024-01-15 19:00:00';
        $future_event = '2024-01-16 19:00:00';
        
        $current_time = '2024-01-15 14:30:00';
        
        $this->assertEquals('past', $this->get_event_status($past_event, $current_time));
        $this->assertEquals('today', $this->get_event_status($current_event, $current_time));
        $this->assertEquals('upcoming', $this->get_event_status($future_event, $current_time));
    }
    
    public function test_guest_limit_validation() {
        // Test unlimited events (0 or null)
        $this->assertTrue($this->is_guest_limit_valid(10, 0));
        $this->assertTrue($this->is_guest_limit_valid(100, null));
        
        // Test limited events
        $this->assertTrue($this->is_guest_limit_valid(5, 10));
        $this->assertFalse($this->is_guest_limit_valid(15, 10));
        $this->assertTrue($this->is_guest_limit_valid(10, 10)); // Exactly at limit
    }
    
    /**
     * Helper methods (would be in actual Event Manager class)
     */
    private function validate_event_data($data) {
        $required_fields = ['title', 'event_date', 'host_email'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        // Validate email
        if (!filter_var($data['host_email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Validate date
        if (strtotime($data['event_date']) === false) {
            return false;
        }
        
        return true;
    }
    
    private function generate_event_slug($title) {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
    
    private function get_event_status($event_date, $current_time = null) {
        if ($current_time === null) {
            $current_time = current_time('mysql');
        }
        
        $event_timestamp = strtotime($event_date);
        $current_timestamp = strtotime($current_time);
        $event_day = date('Y-m-d', $event_timestamp);
        $current_day = date('Y-m-d', $current_timestamp);
        
        if ($event_day === $current_day) {
            return 'today';
        } elseif ($event_timestamp < $current_timestamp) {
            return 'past';
        } else {
            return 'upcoming';
        }
    }
    
    private function is_guest_limit_valid($current_guests, $limit) {
        // Unlimited events
        if ($limit === 0 || $limit === null) {
            return true;
        }
        
        return $current_guests <= $limit;
    }
}