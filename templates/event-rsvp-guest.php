<?php
/**
 * Event RSVP Guest Template
 * Dedicated RSVP page for invited guests (works without login)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get invitation token from URL
$invitation_token = isset($_GET['invitation']) ? sanitize_text_field($_GET['invitation']) : '';
$event_id = isset($_GET['event']) ? intval($_GET['event']) : 0;
$quick_rsvp = isset($_GET['quick_rsvp']) ? sanitize_text_field($_GET['quick_rsvp']) : '';

if (!$invitation_token || !$event_id) {
    wp_redirect(home_url('/events'));
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';

$event_manager = new PartyMinder_Event_Manager();

// Verify invitation token
global $wpdb;
$invitations_table = $wpdb->prefix . 'partyminder_event_invitations';

$invitation = $wpdb->get_row($wpdb->prepare(
    "SELECT ei.*, e.title, e.slug, e.description, e.event_date, e.venue_info, e.host_email,
            u.display_name as invited_by_name
     FROM $invitations_table ei
     LEFT JOIN {$wpdb->prefix}partyminder_events e ON ei.event_id = e.id
     LEFT JOIN {$wpdb->users} u ON ei.invited_by_user_id = u.ID
     WHERE ei.invitation_token = %s AND ei.event_id = %d AND ei.status IN ('pending', 'accepted')
     AND ei.expires_at > %s",
    $invitation_token,
    $event_id,
    current_time('mysql')
));

if (!$invitation) {
    $page_title = __('Invitation Not Found', 'partyminder');
    $main_content = '<div class="pm-text-center pm-p-4"><h2>' . __('Invitation Not Found', 'partyminder') . '</h2><p>' . __('This invitation link is invalid or has expired.', 'partyminder') . '</p><a href="' . home_url('/events') . '" class="pm-btn">' . __('Browse Events', 'partyminder') . '</a></div>';
    $sidebar_content = '';
    include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
    return;
}

// Check if already RSVPed
$existing_rsvp = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}partyminder_rsvps 
     WHERE event_id = %d AND email = %s",
    $event_id,
    $invitation->invited_email
));

// Handle quick RSVP from email button click
if ($quick_rsvp && in_array($quick_rsvp, array('attending', 'maybe', 'not_attending'))) {
    $guest_name = $existing_rsvp ? $existing_rsvp->name : 'Guest';
    
    if ($existing_rsvp) {
        // Update existing RSVP
        $result = $wpdb->update(
            $wpdb->prefix . 'partyminder_rsvps',
            array(
                'status' => $quick_rsvp,
                'rsvp_date' => current_time('mysql')
            ),
            array('id' => $existing_rsvp->id),
            array('%s', '%s'),
            array('%d')
        );
    } else {
        // Create new quick RSVP
        $result = $wpdb->insert(
            $wpdb->prefix . 'partyminder_rsvps',
            array(
                'event_id' => $event_id,
                'name' => $guest_name,
                'email' => $invitation->invited_email,
                'status' => $quick_rsvp,
                'invitation_token' => $invitation_token,
                'rsvp_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    if ($result !== false) {
        // Update invitation status
        $wpdb->update(
            $invitations_table,
            array(
                'status' => 'accepted',
                'responded_at' => current_time('mysql')
            ),
            array('invitation_token' => $invitation_token),
            array('%s', '%s'),
            array('%s')
        );
        
        // Send notification to host
        $host_email = $invitation->host_email;
        $status_text = array(
            'attending' => __('will be attending', 'partyminder'),
            'not_attending' => __('cannot attend', 'partyminder'),
            'maybe' => __('might attend', 'partyminder')
        );
        
        $subject = sprintf(__('[%s] Quick RSVP from %s', 'partyminder'), $invitation->title, $guest_name);
        $message = sprintf(
            __("%s has quickly responded to your invitation for \"%s\".\n\nResponse: %s %s\n\nEvent: %s\nDate: %s\n\nView full details: %s", 'partyminder'),
            $guest_name,
            $invitation->title,
            $guest_name,
            $status_text[$quick_rsvp] ?? $quick_rsvp,
            $invitation->title,
            date('F j, Y \a\t g:i A', strtotime($invitation->event_date)),
            home_url('/events/' . $invitation->slug)
        );
        
        wp_mail($host_email, $subject, $message);
        
        // Redirect to show success with the quick response
        $redirect_url = add_query_arg(array(
            'invitation' => $invitation_token,
            'event' => $event_id,
            'quick_response' => $quick_rsvp
        ), home_url('/events/join'));
        wp_redirect($redirect_url);
        exit;
    }
}

// Check for quick response confirmation
$quick_response = isset($_GET['quick_response']) ? sanitize_text_field($_GET['quick_response']) : '';

// Handle RSVP submission
$rsvp_submitted = false;
$rsvp_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rsvp_response']) && wp_verify_nonce($_POST['rsvp_nonce'], 'partyminder_guest_rsvp')) {
    $rsvp_response = sanitize_text_field($_POST['rsvp_response']);
    $guest_name = sanitize_text_field($_POST['guest_name']);
    $dietary_restrictions = sanitize_textarea_field($_POST['dietary_restrictions']);
    $plus_one = isset($_POST['plus_one']) ? 1 : 0;
    $plus_one_name = $plus_one ? sanitize_text_field($_POST['plus_one_name']) : '';
    $guest_notes = sanitize_textarea_field($_POST['guest_notes']);
    
    if ($existing_rsvp) {
        // Update existing RSVP
        $result = $wpdb->update(
            $wpdb->prefix . 'partyminder_rsvps',
            array(
                'name' => $guest_name,
                'status' => $rsvp_response,
                'dietary_restrictions' => $dietary_restrictions,
                'plus_one' => $plus_one,
                'plus_one_name' => $plus_one_name,
                'notes' => $guest_notes,
                'rsvp_date' => current_time('mysql')
            ),
            array('id' => $existing_rsvp->id),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s'),
            array('%d')
        );
    } else {
        // Create new RSVP
        $result = $wpdb->insert(
            $wpdb->prefix . 'partyminder_rsvps',
            array(
                'event_id' => $event_id,
                'name' => $guest_name,
                'email' => $invitation->invited_email,
                'status' => $rsvp_response,
                'dietary_restrictions' => $dietary_restrictions,
                'plus_one' => $plus_one,
                'plus_one_name' => $plus_one_name,
                'notes' => $guest_notes,
                'rsvp_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
    }
    
    if ($result !== false) {
        $rsvp_submitted = true;
        $rsvp_status = $rsvp_response;
        
        // Update invitation status to accepted
        $wpdb->update(
            $invitations_table,
            array(
                'status' => 'accepted',
                'responded_at' => current_time('mysql')
            ),
            array('invitation_token' => $invitation_token),
            array('%s', '%s'),
            array('%s')
        );
        
        // Send notification to host
        $host_email = $invitation->host_email;
        $status_text = array(
            'attending' => __('will be attending', 'partyminder'),
            'not_attending' => __('cannot attend', 'partyminder'),
            'maybe' => __('might attend', 'partyminder')
        );
        
        $subject = sprintf(__('[%s] RSVP Response from %s', 'partyminder'), $invitation->title, $guest_name);
        $message = sprintf(
            __("%s has responded to your invitation for \"%s\".\n\nResponse: %s %s\n\nEvent: %s\nDate: %s\n\nView full details: %s", 'partyminder'),
            $guest_name,
            $invitation->title,
            $guest_name,
            $status_text[$rsvp_response] ?? $rsvp_response,
            $invitation->title,
            date('F j, Y \a\t g:i A', strtotime($invitation->event_date)),
            home_url('/events/' . $invitation->slug)
        );
        
        wp_mail($host_email, $subject, $message);
    }
}

// Set up template variables
$is_success_state = $rsvp_submitted || $quick_response;
$display_status = $rsvp_submitted ? $rsvp_status : $quick_response;

$page_title = $is_success_state
    ? __('RSVP Submitted!', 'partyminder') 
    : sprintf(__('RSVP: %s', 'partyminder'), $invitation->title);

$page_description = $is_success_state
    ? __('Thank you for your response. The host has been notified.', 'partyminder')
    : __('Please let us know if you can attend this event', 'partyminder');

// Main content
ob_start();
?>

<?php if ($is_success_state): ?>
    <!-- Success State -->
    <div class="pm-section pm-text-center">
        <div class="pm-mb-4">
            <?php if ($display_status === 'attending'): ?>
                <h2 class="pm-heading pm-heading-lg pm-text-primary pm-mb"><?php _e('Great! See you there!', 'partyminder'); ?></h2>
                <p class="pm-text-muted"><?php _e('Your RSVP has been confirmed. The host has been notified that you\'ll be attending.', 'partyminder'); ?></p>
            <?php elseif ($display_status === 'maybe'): ?>
                <h2 class="pm-heading pm-heading-lg pm-text-primary pm-mb"><?php _e('Thanks for letting us know!', 'partyminder'); ?></h2>
                <p class="pm-text-muted"><?php _e('Your maybe response has been recorded. The host has been notified.', 'partyminder'); ?></p>
            <?php else: ?>
                <h2 class="pm-heading pm-heading-lg pm-text-primary pm-mb"><?php _e('Sorry you can\'t make it!', 'partyminder'); ?></h2>
                <p class="pm-text-muted"><?php _e('Your response has been recorded. The host has been notified.', 'partyminder'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="pm-flex pm-gap pm-justify-center pm-flex-wrap">
            <a href="<?php echo home_url('/events/' . $invitation->slug); ?>" class="pm-btn">
                <?php _e('View Event Details', 'partyminder'); ?>
            </a>
            <a href="<?php echo home_url('/events'); ?>" class="pm-btn pm-btn-secondary">
                <?php _e('Browse Other Events', 'partyminder'); ?>
            </a>
        </div>
    </div>
    
<?php else: ?>
    <!-- RSVP Form -->
    <div class="pm-section pm-mb">
        <div class="pm-section-header">
            <h2 class="pm-heading pm-heading-lg pm-text-primary"><?php echo esc_html($invitation->title); ?></h2>
            <p class="pm-text-muted"><?php printf(__('You\'re invited by %s', 'partyminder'), esc_html($invitation->invited_by_name)); ?></p>
        </div>
        
        <!-- Event Details -->
        <div class="pm-card pm-mb-4">
            <div class="pm-card-body">
                <div class="pm-flex pm-flex-column pm-gap">
                    <div class="pm-flex pm-gap">
                        <span style="font-size: 1.2rem;">📅</span>
                        <div>
                            <strong><?php _e('When:', 'partyminder'); ?></strong>
                            <div><?php echo date('F j, Y \a\t g:i A', strtotime($invitation->event_date)); ?></div>
                        </div>
                    </div>
                    <?php if ($invitation->venue_info): ?>
                    <div class="pm-flex pm-gap">
                        <span style="font-size: 1.2rem;">📍</span>
                        <div>
                            <strong><?php _e('Where:', 'partyminder'); ?></strong>
                            <div><?php echo esc_html($invitation->venue_info); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($invitation->description): ?>
                    <div class="pm-flex pm-gap">
                        <span style="font-size: 1.2rem;">ℹ️</span>
                        <div>
                            <strong><?php _e('Details:', 'partyminder'); ?></strong>
                            <div><?php echo wpautop(esc_html($invitation->description)); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($invitation->message): ?>
                    <div class="pm-flex pm-gap">
                        <span style="font-size: 1.2rem;">💬</span>
                        <div>
                            <strong><?php _e('Personal Message:', 'partyminder'); ?></strong>
                            <div class="pm-text-muted"><em>"<?php echo esc_html($invitation->message); ?>"</em></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($existing_rsvp): ?>
        <div class="pm-alert pm-alert-info pm-mb-4">
            <h4 class="pm-heading pm-heading-sm"><?php _e('You\'ve already responded', 'partyminder'); ?></h4>
            <p><?php printf(__('Your current response: <strong>%s</strong>. You can update it below if needed.', 'partyminder'), ucfirst($existing_rsvp->status)); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- RSVP Form -->
    <div class="pm-section">
        <div class="pm-section-header">
            <h3 class="pm-heading pm-heading-md"><?php _e('Please Respond', 'partyminder'); ?></h3>
        </div>
        
        <form method="post" class="pm-form">
            <?php wp_nonce_field('partyminder_guest_rsvp', 'rsvp_nonce'); ?>
            
            <div class="pm-form-group">
                <label class="pm-form-label"><?php _e('Your Name *', 'partyminder'); ?></label>
                <input type="text" name="guest_name" class="pm-form-input" 
                       value="<?php echo $existing_rsvp ? esc_attr($existing_rsvp->name) : ''; ?>" required>
            </div>
            
            <div class="pm-form-group">
                <label class="pm-form-label"><?php _e('Will you attend? *', 'partyminder'); ?></label>
                <div class="pm-form-radio-group">
                    <label class="pm-form-radio">
                        <input type="radio" name="rsvp_response" value="attending" 
                               <?php checked($existing_rsvp && $existing_rsvp->status === 'attending'); ?> required>
                        <span><?php _e('Yes, I\'ll be there!', 'partyminder'); ?></span>
                    </label>
                    <label class="pm-form-radio">
                        <input type="radio" name="rsvp_response" value="maybe" 
                               <?php checked($existing_rsvp && $existing_rsvp->status === 'maybe'); ?> required>
                        <span><?php _e('Maybe, I\'m not sure yet', 'partyminder'); ?></span>
                    </label>
                    <label class="pm-form-radio">
                        <input type="radio" name="rsvp_response" value="not_attending" 
                               <?php checked($existing_rsvp && $existing_rsvp->status === 'not_attending'); ?> required>
                        <span><?php _e('Sorry, I can\'t make it', 'partyminder'); ?></span>
                    </label>
                </div>
            </div>
            
            <div class="pm-form-group">
                <label class="pm-form-label">
                    <input type="checkbox" name="plus_one" value="1" 
                           <?php checked($existing_rsvp && $existing_rsvp->plus_one); ?>>
                    <?php _e('I\'m bringing a plus one', 'partyminder'); ?>
                </label>
                <input type="text" name="plus_one_name" class="pm-form-input pm-mt-2" 
                       placeholder="<?php _e('Plus one name (optional)', 'partyminder'); ?>"
                       value="<?php echo $existing_rsvp ? esc_attr($existing_rsvp->plus_one_name) : ''; ?>">
            </div>
            
            <div class="pm-form-group">
                <label class="pm-form-label"><?php _e('Dietary Restrictions / Allergies', 'partyminder'); ?></label>
                <textarea name="dietary_restrictions" class="pm-form-textarea" rows="2"
                          placeholder="<?php _e('Let the host know about any dietary needs...', 'partyminder'); ?>"><?php echo $existing_rsvp ? esc_textarea($existing_rsvp->dietary_restrictions) : ''; ?></textarea>
            </div>
            
            <div class="pm-form-group">
                <label class="pm-form-label"><?php _e('Message to Host', 'partyminder'); ?></label>
                <textarea name="guest_notes" class="pm-form-textarea" rows="3"
                          placeholder="<?php _e('Any questions or notes for the host...', 'partyminder'); ?>"><?php echo $existing_rsvp ? esc_textarea($existing_rsvp->notes) : ''; ?></textarea>
            </div>
            
            <div class="pm-form-actions">
                <button type="submit" class="pm-btn pm-btn-lg">
                    <?php _e('Submit RSVP', 'partyminder'); ?>
                </button>
                <a href="<?php echo home_url('/events/' . $invitation->slug); ?>" class="pm-btn pm-btn-secondary pm-btn-lg">
                    <?php _e('View Event Page', 'partyminder'); ?>
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<!-- Event Quick Info -->
<div class="pm-card pm-mb-4">
    <div class="pm-card-header">
        <h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e('Event Info', 'partyminder'); ?></h3>
    </div>
    <div class="pm-card-body">
        <div class="pm-flex pm-flex-column pm-gap-4">
            <div>
                <strong><?php _e('Host:', 'partyminder'); ?></strong><br>
                <span class="pm-text-muted"><?php echo esc_html($invitation->invited_by_name); ?></span>
            </div>
            <div>
                <strong><?php _e('Date:', 'partyminder'); ?></strong><br>
                <span class="pm-text-muted"><?php echo date('M j, Y', strtotime($invitation->event_date)); ?></span>
            </div>
            <div>
                <strong><?php _e('Time:', 'partyminder'); ?></strong><br>
                <span class="pm-text-muted"><?php echo date('g:i A', strtotime($invitation->event_date)); ?></span>
            </div>
            <?php if ($invitation->venue_info): ?>
            <div>
                <strong><?php _e('Location:', 'partyminder'); ?></strong><br>
                <span class="pm-text-muted"><?php echo esc_html($invitation->venue_info); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$is_success_state): ?>
<!-- Quick RSVP Buttons -->
<div class="pm-card pm-mb-4">
    <div class="pm-card-header">
        <h3 class="pm-heading pm-heading-sm"><?php _e('Quick Response', 'partyminder'); ?></h3>
    </div>
    <div class="pm-card-body">
        <div class="pm-flex pm-flex-column pm-gap-4">
            <button type="button" class="pm-btn quick-rsvp-btn" data-response="attending">
                <?php _e('Yes, I\'ll be there!', 'partyminder'); ?>
            </button>
            <button type="button" class="pm-btn pm-btn-secondary quick-rsvp-btn" data-response="maybe">
                <?php _e('Maybe', 'partyminder'); ?>
            </button>
            <button type="button" class="pm-btn pm-btn-secondary quick-rsvp-btn" data-response="not_attending">
                <?php _e('Can\'t make it', 'partyminder'); ?>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Help -->
<div class="pm-card">
    <div class="pm-card-header">
        <h3 class="pm-heading pm-heading-sm"><?php _e('Need Help?', 'partyminder'); ?></h3>
    </div>
    <div class="pm-card-body">
        <p class="pm-text-muted pm-mb"><?php _e('Having trouble with your RSVP? You can reply directly to the invitation email or contact the host.', 'partyminder'); ?></p>
        <a href="<?php echo home_url('/events'); ?>" class="pm-btn pm-btn-secondary">
            <?php _e('Browse Other Events', 'partyminder'); ?>
        </a>
    </div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick RSVP button functionality
    const quickButtons = document.querySelectorAll('.quick-rsvp-btn');
    const form = document.querySelector('.pm-form');
    const guestNameInput = form ? form.querySelector('input[name="guest_name"]') : null;
    
    quickButtons.forEach(button => {
        button.addEventListener('click', function() {
            const response = this.dataset.response;
            
            if (!guestNameInput || !guestNameInput.value.trim()) {
                alert('<?php _e('Please enter your name first in the form below.', 'partyminder'); ?>');
                guestNameInput.focus();
                return;
            }
            
            // Set the radio button
            const radioButton = form.querySelector(`input[name="rsvp_response"][value="${response}"]`);
            if (radioButton) {
                radioButton.checked = true;
                
                // Auto-submit form
                if (confirm('<?php _e('Submit your RSVP now?', 'partyminder'); ?>')) {
                    form.submit();
                }
            }
        });
    });
});
</script>