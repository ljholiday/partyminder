<?php
/**
 * Unit Test Example - Date Formatting Helpers
 */

namespace PartyMinder\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class DateFormatTest extends TestCase {
    
    use MockeryPHPUnitIntegration;
    
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions used in date formatting
        Monkey\Functions\when('current_time')->alias(function($type = 'mysql') {
            return $type === 'mysql' ? '2024-01-15 14:30:00' : 1705332600;
        });
        
        Monkey\Functions\when('human_time_diff')->alias(function($from, $to = null) {
            if ($to === null) {
                $to = time();
            }
            $diff = abs($to - $from);
            if ($diff < 3600) {
                return floor($diff / 60) . ' minutes';
            }
            return floor($diff / 3600) . ' hours';
        });
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    
    public function test_event_date_formatting() {
        // Test basic date formatting
        $event_date = '2024-01-15 19:00:00';
        $formatted = date('F j, Y \\a\\t g:i A', strtotime($event_date));
        
        $this->assertEquals('January 15, 2024 at 7:00 PM', $formatted);
    }
    
    public function test_relative_time_calculation() {
        // Mock current time as Jan 15, 2024 2:30 PM
        $event_time = strtotime('2024-01-15 14:00:00'); // 30 minutes ago
        $current_time = strtotime('2024-01-15 14:30:00');
        
        $relative = human_time_diff($event_time, $current_time);
        $this->assertEquals('30 minutes', $relative);
    }
    
    public function test_event_status_classification() {
        // Test helper function for classifying event timing
        $this->assertTrue($this->is_event_today('2024-01-15 19:00:00'));
        $this->assertFalse($this->is_event_today('2024-01-16 19:00:00'));
        $this->assertTrue($this->is_event_past('2024-01-14 19:00:00'));
        $this->assertFalse($this->is_event_past('2024-01-16 19:00:00'));
    }
    
    public function test_invitation_expiration() {
        // Test invitation expiration logic
        $created_date = '2024-01-15 14:30:00';
        $expires_date = date('Y-m-d H:i:s', strtotime($created_date . ' +7 days'));
        
        $this->assertEquals('2024-01-22 14:30:00', $expires_date);
        
        // Test if invitation is expired
        $current = '2024-01-20 14:30:00';
        $this->assertFalse(strtotime($expires_date) < strtotime($current));
        
        $current = '2024-01-25 14:30:00';
        $this->assertTrue(strtotime($expires_date) < strtotime($current));
    }
    
    /**
     * Helper function to test event timing
     */
    private function is_event_today($event_date) {
        $event_day = date('Y-m-d', strtotime($event_date));
        $today = date('Y-m-d', strtotime('2024-01-15')); // Mock today
        return $event_day === $today;
    }
    
    private function is_event_past($event_date) {
        $event_timestamp = strtotime($event_date);
        $current_timestamp = strtotime('2024-01-15 14:30:00'); // Mock current time
        return $event_timestamp < $current_timestamp;
    }
}