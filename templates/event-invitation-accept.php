<?php
/**
 * Event Invitation Acceptance Page
 * Handles invitation token processing and RSVP creation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';

$event_manager = new PartyMinder_Event_Manager();

// Get invitation token from URL
$token = sanitize_text_field($_GET['token'] ?? '');
$message = '';
$message_type = '';
$invitation = null;

if (!$token) {
    $message = __('No invitation token provided.', 'partyminder');
    $message_type = 'error';
} else {
    // Get invitation by token
    $invitation = $event_manager->get_invitation_by_token($token);
    
    if (!$invitation) {
        $message = __('Invalid invitation token.', 'partyminder');
        $message_type = 'error';
    } elseif ($invitation->status !== 'pending') {
        $message = __('This invitation has already been processed.', 'partyminder');
        $message_type = 'error';
    } elseif (strtotime($invitation->expires_at) < time()) {
        $message = __('This invitation has expired.', 'partyminder');
        $message_type = 'error';
    }
}

// Handle form submission (invitation acceptance)
if ($_POST && $invitation && wp_verify_nonce($_POST['invitation_nonce'], 'accept_event_invitation_' . $token)) {
    $current_user = wp_get_current_user();
    
    if (!$current_user->ID) {
        $message = __('You must be logged in to accept this invitation.', 'partyminder');
        $message_type = 'error';
    } else {
        // Get additional RSVP data from form
        $guest_data = array(
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'dietary_restrictions' => sanitize_textarea_field($_POST['dietary_restrictions'] ?? ''),
            'plus_one' => isset($_POST['plus_one']) ? 1 : 0,
            'plus_one_name' => sanitize_text_field($_POST['plus_one_name'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        );
        
        // Accept invitation (creates RSVP)
        $result = $event_manager->accept_event_invitation($token, $current_user->ID, $guest_data);
        
        if (is_wp_error($result)) {
            $message = $result->get_error_message();
            $message_type = 'error';
        } else {
            $message = sprintf(__('Great! You have successfully RSVP\'d to %s.', 'partyminder'), $invitation->event_title);
            $message_type = 'success';
            
            // Redirect to event page after a delay
            echo '<script>setTimeout(function() { window.location.href = "' . home_url('/events/' . $invitation->event_slug) . '"; }, 3000);</script>';
        }
    }
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-event-invitation {
    max-width: 700px;
    margin: 40px auto;
    padding: 20px;
}

.invitation-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.invitation-header {
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    color: white;
    padding: 30px;
    text-align: center;
}

.invitation-icon {
    font-size: 3em;
    margin-bottom: 10px;
}

.invitation-title {
    font-size: 1.8em;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.invitation-subtitle {
    font-size: 1.1em;
    opacity: 0.9;
    margin: 0;
}

.invitation-body {
    padding: 30px;
}

.event-details {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.event-details h4 {
    margin: 0 0 15px 0;
    color: var(--pm-primary);
    font-size: 1.2em;
}

.event-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.event-info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #666;
}

.event-info-item strong {
    color: #333;
}

.event-description {
    color: #666;
    line-height: 1.6;
    margin-top: 15px;
}

.invitation-message {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.invitation-message em {
    font-style: italic;
    color: #856404;
}

.rsvp-form {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.rsvp-form h4 {
    margin: 0 0 20px 0;
    color: var(--pm-primary);
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-weight: bold;
    color: #333;
    margin-bottom: 8px;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 1em;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--pm-primary);
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.form-checkbox input {
    width: auto;
}

.form-help {
    font-size: 0.85em;
    color: #666;
    margin-top: 5px;
}

.pm-button {
    background: var(--pm-primary);
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    font-size: 1.1em;
}

.pm-button:hover {
    opacity: 0.9;
    color: white;
}

.pm-button-secondary {
    background: #6c757d;
}

.message-box {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}

.message-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.message-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.login-prompt {
    text-align: center;
    padding: 20px;
    background: #e3f2fd;
    border-radius: 8px;
    margin-top: 20px;
}

.already-rsvpd {
    text-align: center;
    padding: 20px;
}

@media (max-width: 768px) {
    .partyminder-event-invitation {
        margin: 20px auto;
        padding: 10px;
    }
    
    .invitation-header, .invitation-body, .rsvp-form {
        padding: 20px;
    }
    
    .invitation-title {
        font-size: 1.5em;
    }
    
    .event-info {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="partyminder-event-invitation">
    <!-- Page Header -->
    <div class="invitation-card">
        <div class="invitation-header">
            <div class="invitation-icon">🎉</div>
            <h1 class="invitation-title"><?php _e('Event Invitation', 'partyminder'); ?></h1>
            <p class="invitation-subtitle"><?php _e('You\'ve been invited to an event', 'partyminder'); ?></p>
        </div>
        
        <div class="invitation-body">
            <?php if ($message): ?>
                <div class="message-box message-<?php echo esc_attr($message_type); ?>">
                    <?php echo esc_html($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($invitation && $message_type !== 'error'): ?>
                <div class="event-details">
                    <h4><?php _e('Event Details', 'partyminder'); ?></h4>
                    <div class="event-info">
                        <div class="event-info-item">
                            <span>🎉</span>
                            <span><strong><?php _e('Event:', 'partyminder'); ?></strong> <?php echo esc_html($invitation->event_title); ?></span>
                        </div>
                        <div class="event-info-item">
                            <span>📅</span>
                            <span><strong><?php _e('Date:', 'partyminder'); ?></strong> <?php echo date('F j, Y', strtotime($invitation->event_date)); ?></span>
                        </div>
                        <?php if ($invitation->event_time): ?>
                        <div class="event-info-item">
                            <span>⏰</span>
                            <span><strong><?php _e('Time:', 'partyminder'); ?></strong> <?php echo esc_html($invitation->event_time); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($invitation->venue_info): ?>
                        <div class="event-info-item">
                            <span>📍</span>
                            <span><strong><?php _e('Venue:', 'partyminder'); ?></strong> <?php echo esc_html($invitation->venue_info); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="event-info-item">
                            <span>👤</span>
                            <span><strong><?php _e('Invited by:', 'partyminder'); ?></strong> <?php echo esc_html($invitation->inviter_name ?: __('Event Host', 'partyminder')); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($invitation->event_description): ?>
                        <div class="event-description">
                            <?php echo wpautop(esc_html($invitation->event_description)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($invitation->message): ?>
                    <div class="invitation-message">
                        <h4><?php _e('Personal Message', 'partyminder'); ?></h4>
                        <em><?php echo wpautop(esc_html($invitation->message)); ?></em>
                    </div>
                <?php endif; ?>
                
                <?php if (!is_user_logged_in()): ?>
                    <div class="login-prompt">
                        <h4><?php _e('Login Required', 'partyminder'); ?></h4>
                        <p><?php _e('You need to be logged in to RSVP to this event.', 'partyminder'); ?></p>
                        <a href="<?php echo add_query_arg('redirect_to', urlencode(home_url('/events/join?token=' . urlencode($token))), PartyMinder::get_login_url()); ?>" class="pm-button">
                            <span>🔑</span> <?php _e('Login to RSVP', 'partyminder'); ?>
                        </a>
                    </div>
                <?php elseif ($message_type === 'success'): ?>
                    <div style="text-align: center;">
                        <p><?php _e('Redirecting to the event page...', 'partyminder'); ?></p>
                        <a href="<?php echo home_url('/events/' . $invitation->event_slug); ?>" class="pm-button">
                            <span>🎉</span> <?php _e('Go to Event', 'partyminder'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="rsvp-form">
                        <h4><?php _e('RSVP Information', 'partyminder'); ?></h4>
                        <p><?php _e('Please provide your RSVP details below:', 'partyminder'); ?></p>
                        
                        <form method="post">
                            <?php wp_nonce_field('accept_event_invitation_' . $token, 'invitation_nonce'); ?>
                            
                            <div class="form-group">
                                <label class="form-label" for="phone"><?php _e('Phone Number (Optional)', 'partyminder'); ?></label>
                                <input type="tel" class="form-input" id="phone" name="phone" placeholder="<?php _e('Your phone number', 'partyminder'); ?>">
                                <div class="form-help"><?php _e('The host may contact you if needed.', 'partyminder'); ?></div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-checkbox">
                                    <input type="checkbox" name="plus_one" id="plus_one" onchange="togglePlusOne()">
                                    <span><?php _e('I\'m bringing a plus one', 'partyminder'); ?></span>
                                </label>
                                <div id="plus-one-details" style="display: none; margin-top: 10px;">
                                    <input type="text" class="form-input" name="plus_one_name" placeholder="<?php _e('Plus one name', 'partyminder'); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="dietary_restrictions"><?php _e('Dietary Restrictions (Optional)', 'partyminder'); ?></label>
                                <textarea class="form-textarea" id="dietary_restrictions" name="dietary_restrictions" rows="2" placeholder="<?php _e('Any dietary restrictions or allergies?', 'partyminder'); ?>"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="notes"><?php _e('Additional Notes (Optional)', 'partyminder'); ?></label>
                                <textarea class="form-textarea" id="notes" name="notes" rows="2" placeholder="<?php _e('Anything else you\'d like the host to know?', 'partyminder'); ?>"></textarea>
                            </div>
                            
                            <div style="text-align: center;">
                                <button type="submit" class="pm-button">
                                    <span>✅</span> <?php _e('Confirm RSVP', 'partyminder'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center;">
                    <p><?php _e('Return to events to explore other options.', 'partyminder'); ?></p>
                    <a href="<?php echo home_url('/events'); ?>" class="pm-button pm-button-secondary">
                        <span>🎉</span> <?php _e('Browse Events', 'partyminder'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function togglePlusOne() {
    const checkbox = document.getElementById('plus_one');
    const details = document.getElementById('plus-one-details');
    const nameInput = details.querySelector('input[name="plus_one_name"]');
    
    if (checkbox.checked) {
        details.style.display = 'block';
        nameInput.required = true;
    } else {
        details.style.display = 'none';
        nameInput.required = false;
        nameInput.value = '';
    }
}
</script>