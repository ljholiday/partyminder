<?php
/**
 * Snapshot Test Example - Email Template Output
 */

namespace PartyMinder\Tests\Snapshot;

use PHPUnit\Framework\TestCase;

class EmailTemplateTest extends TestCase {
    
    private $fixtures_dir;
    
    public function setUp(): void {
        parent::setUp();
        $this->fixtures_dir = dirname(__DIR__) . '/fixtures/email-templates/';
    }
    
    public function test_invitation_email_html_output() {
        // Mock event data
        $event = (object) [
            'id' => 123,
            'title' => 'Summer BBQ Party',
            'description' => 'Join us for a fun BBQ in the backyard with great food and friends!',
            'event_date' => '2024-06-15 18:00:00',
            'venue_info' => '123 Main Street, Anytown, USA',
            'slug' => 'summer-bbq-party'
        ];
        
        // Mock template data
        $template_data = [
            'event' => $event,
            'invitation_url' => 'http://example.org/events/join?invitation=test-token-123&event=123',
            'rsvp_yes_url' => 'http://example.org/events/join?invitation=test-token-123&event=123&quick_rsvp=attending',
            'rsvp_maybe_url' => 'http://example.org/events/join?invitation=test-token-123&event=123&quick_rsvp=maybe',
            'rsvp_no_url' => 'http://example.org/events/join?invitation=test-token-123&event=123&quick_rsvp=not_attending',
            'host_name' => 'John Doe',
            'personal_message' => 'Hope you can make it! It\'s going to be a great time.',
            'invited_email' => 'guest@example.com'
        ];
        
        // Generate HTML (we'll need to include the actual method)
        $html_output = $this->generate_test_invitation_email_html($template_data);
        
        // Load expected fixture
        $fixture_file = $this->fixtures_dir . 'invitation-email-expected.html';
        
        if (!file_exists($fixture_file)) {
            // Create fixture on first run
            $this->create_fixture($fixture_file, $html_output);
            $this->markTestSkipped('Created fixture file. Run test again to compare.');
        }
        
        $expected_html = file_get_contents($fixture_file);
        
        // Normalize whitespace for comparison
        $expected_normalized = $this->normalize_html($expected_html);
        $actual_normalized = $this->normalize_html($html_output);
        
        $this->assertEquals(
            $expected_normalized,
            $actual_normalized,
            'Email template HTML output has changed. Review changes and update fixture if intentional.'
        );
    }
    
    public function test_rsvp_confirmation_email_output() {
        $template_data = [
            'guest_name' => 'Jane Smith',
            'event_title' => 'Summer BBQ Party',
            'event_date' => '2024-06-15 18:00:00',
            'venue_info' => '123 Main Street, Anytown, USA',
            'rsvp_status' => 'attending',
            'host_name' => 'John Doe',
            'event_url' => 'http://example.org/events/summer-bbq-party'
        ];
        
        $html_output = $this->generate_test_rsvp_confirmation_email($template_data);
        
        $fixture_file = $this->fixtures_dir . 'rsvp-confirmation-expected.html';
        
        if (!file_exists($fixture_file)) {
            $this->create_fixture($fixture_file, $html_output);
            $this->markTestSkipped('Created fixture file. Run test again to compare.');
        }
        
        $expected_html = file_get_contents($fixture_file);
        
        $this->assertEquals(
            $this->normalize_html($expected_html),
            $this->normalize_html($html_output),
            'RSVP confirmation email template has changed unexpectedly.'
        );
    }
    
    /**
     * Generate test invitation email HTML (simplified version of actual method)
     */
    private function generate_test_invitation_email_html($data) {
        $event = $data['event'];
        $event_date = date('F j, Y', strtotime($event->event_date));
        $event_time = date('g:i A', strtotime($event->event_date));
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$event->title} - Event Invitation</title>
</head>
<body style="margin: 0; padding: 20px; background: #f7fafc; font-family: Arial, sans-serif;">
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">You're Invited!</h1>
            <p style="margin: 10px 0 0 0; font-size: 18px;">{$event->title}</p>
        </div>
        <div style="background: #ffffff; padding: 30px 20px;">
            <p style="font-size: 18px; margin-top: 0;">Hi there!</p>
            <p><strong>{$data['host_name']}</strong> has invited you to their event.</p>
            
            <div style="background: #f8f9ff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h2 style="margin-top: 0; color: #4a5568;">{$event->title}</h2>
                <p><strong>When:</strong> {$event_date} at {$event_time}</p>
                <p><strong>Where:</strong> {$event->venue_info}</p>
                <p><strong>Details:</strong> {$event->description}</p>
                
                <div style="background: white; border-left: 4px solid #667eea; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0;"><strong>Personal message from {$data['host_name']}:</strong></p>
                    <p style="margin: 10px 0 0 0; font-style: italic;">"{$data['personal_message']}"</p>
                </div>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <p style="font-size: 18px; font-weight: bold; margin-bottom: 20px;">Can you make it?</p>
                <a href="{$data['rsvp_yes_url']}" style="display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 8px;">Yes, I'll be there!</a>
                <a href="{$data['rsvp_maybe_url']}" style="display: inline-block; background: #e2e8f0; color: #4a5568; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 8px;">Maybe</a>
                <a href="{$data['rsvp_no_url']}" style="display: inline-block; background: #f56565; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 8px;">Can't make it</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Generate test RSVP confirmation email
     */
    private function generate_test_rsvp_confirmation_email($data) {
        $status_messages = [
            'attending' => 'Great! We\'re excited to see you there!',
            'maybe' => 'Thanks for letting us know. Hope you can make it!',
            'not_attending' => 'Sorry you can\'t make it. Maybe next time!'
        ];
        
        $message = $status_messages[$data['rsvp_status']] ?? 'Thanks for your response.';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>RSVP Confirmed - {$data['event_title']}</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h1>RSVP Confirmed!</h1>
        <p>Hi {$data['guest_name']},</p>
        <p>{$message}</p>
        <p><strong>Event:</strong> {$data['event_title']}</p>
        <p><strong>Date:</strong> {$data['event_date']}</p>
        <p><strong>Your Response:</strong> {$data['rsvp_status']}</p>
        <p>View event details: <a href="{$data['event_url']}">{$data['event_title']}</a></p>
        <p>Thanks,<br>{$data['host_name']}</p>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Normalize HTML for comparison (remove extra whitespace, etc.)
     */
    private function normalize_html($html) {
        // Remove extra whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        // Normalize line endings
        $html = str_replace(["\r\n", "\r"], "\n", $html);
        return trim($html);
    }
    
    /**
     * Create fixture file
     */
    private function create_fixture($file_path, $content) {
        $dir = dirname($file_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file_path, $content);
    }
}