<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get event ID from shortcode attributes or URL parameter
$event_id = intval($atts['event_id'] ?? $_GET['event_id'] ?? 0);

if (!$event_id) {
    echo '<div class="partyminder-error">' . __('No event specified.', 'partyminder') . '</div>';
    return;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

// Get event details
$event_manager = new PartyMinder_Event_Manager();
$event = $event_manager->get_event($event_id);

if (!$event) {
    echo '<div class="partyminder-error">' . __('Event not found.', 'partyminder') . '</div>';
    return;
}

// Get guest manager
$guest_manager = new PartyMinder_Guest_Manager();

// Handle RSVP submission
$rsvp_submitted = false;
$rsvp_success = false;
$rsvp_message = '';

if (isset($_POST['partyminder_rsvp']) && wp_verify_nonce($_POST['partyminder_rsvp_nonce'], 'partyminder_rsvp_' . $event_id)) {
    $rsvp_submitted = true;
    
    $rsvp_data = array(
        'event_id' => $event_id,
        'name' => sanitize_text_field($_POST['guest_name']),
        'email' => sanitize_email($_POST['guest_email']),
        'status' => sanitize_text_field($_POST['rsvp_status']),
        'dietary' => sanitize_text_field($_POST['dietary_restrictions']),
        'notes' => sanitize_text_field($_POST['guest_notes'])
    );
    
    $result = $guest_manager->process_rsvp($rsvp_data);
    
    if ($result['success']) {
        $rsvp_success = true;
        $rsvp_message = $result['message'];
    } else {
        $rsvp_message = $result['message'];
    }
}

// Get existing RSVP if available
$existing_rsvp = null;
if (isset($_GET['guest_email'])) {
    $guests = $guest_manager->get_event_guests($event_id);
    foreach ($guests as $guest) {
        if ($guest->email === sanitize_email($_GET['guest_email'])) {
            $existing_rsvp = $guest;
            break;
        }
    }
}

// Get event statistics
$guest_stats = $guest_manager->get_guest_stats($event_id);

// Check if event is full
$is_event_full = $event->guest_limit > 0 && $guest_stats->confirmed >= $event->guest_limit;

// Format event date
$event_date = new DateTime($event->event_date);
$is_past_event = $event_date < new DateTime();

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
$button_style = get_option('partyminder_button_style', 'rounded');
$form_layout = get_option('partyminder_form_layout', 'card');
?>

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}
</style>

<div class="partyminder-rsvp-container">
    
    <!-- Event Header -->
    <div class="event-header">
        <h1><?php echo esc_html($event->title); ?></h1>
        
        <div class="event-meta">
            <div class="meta-item">
                <span class="meta-icon">📅</span>
                <span><?php echo $event_date->format('l, F j, Y'); ?></span>
            </div>
            
            <?php if ($event->event_time): ?>
            <div class="meta-item">
                <span class="meta-icon">🕐</span>
                <span><?php echo date('g:i A', strtotime($event->event_date)); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($event->venue_info): ?>
            <div class="meta-item">
                <span class="meta-icon">📍</span>
                <span><?php echo esc_html($event->venue_info); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="meta-item">
                <span class="meta-icon">👥</span>
                <span>
                    <?php printf(__('%d confirmed', 'partyminder'), $guest_stats->confirmed); ?>
                    <?php if ($event->guest_limit > 0): ?>
                        <?php printf(__(' of %d max', 'partyminder'), $event->guest_limit); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Event Description -->
    <?php if ($event->description): ?>
    <div class="event-description">
        <h3><?php _e('About This Event', 'partyminder'); ?></h3>
        <?php echo wp_kses_post($event->description); ?>
    </div>
    <?php endif; ?>

    <!-- Host Notes -->
    <?php if ($event->host_notes): ?>
    <div class="host-notes">
        <h3><?php _e('Special Notes', 'partyminder'); ?></h3>
        <?php echo wp_kses_post($event->host_notes); ?>
    </div>
    <?php endif; ?>

    <!-- RSVP Form or Messages -->
    <?php if ($rsvp_submitted && $rsvp_success): ?>
        
        <!-- RSVP Success -->
        <div class="rsvp-success">
            <h3><?php _e('✅ RSVP Confirmed!', 'partyminder'); ?></h3>
            <p><?php echo esc_html($rsvp_message); ?></p>
            
            <?php if ($rsvp_data['status'] === 'confirmed'): ?>
            <div class="next-steps">
                <h4><?php _e('What\'s Next?', 'partyminder'); ?></h4>
                <ul>
                    <li><?php _e('You\'ll receive a confirmation email shortly', 'partyminder'); ?></li>
                    <li><?php _e('We\'ll send you a reminder before the event', 'partyminder'); ?></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>

    <?php elseif ($is_past_event): ?>
        
        <!-- Past Event Message -->
        <div class="past-event-message">
            <h3><?php _e('⏰ This Event Has Passed', 'partyminder'); ?></h3>
            <p><?php _e('This event is no longer accepting RSVPs.', 'partyminder'); ?></p>
        </div>

    <?php elseif ($is_event_full && (!$existing_rsvp || $existing_rsvp->status !== 'confirmed')): ?>
        
        <!-- Event Full Message -->
        <div class="event-full-message">
            <h3><?php _e('🎫 Event is Full', 'partyminder'); ?></h3>
            <p><?php _e('This event has reached capacity. You can still RSVP for the waitlist.', 'partyminder'); ?></p>
        </div>

    <?php else: ?>
        
        <!-- RSVP Form -->
        <div class="partyminder-form layout-<?php echo esc_attr($form_layout); ?>">
            <div class="form-header">
                <h2>
                    <?php if ($existing_rsvp): ?>
                        <?php _e('💌 Update Your RSVP', 'partyminder'); ?>
                    <?php else: ?>
                        <?php _e('💌 RSVP for This Event', 'partyminder'); ?>
                    <?php endif; ?>
                </h2>
                <p>
                    <?php if ($existing_rsvp): ?>
                        <?php _e('You can update your RSVP details below.', 'partyminder'); ?>
                    <?php else: ?>
                        <?php _e('Let us know if you can make it!', 'partyminder'); ?>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($rsvp_submitted && !$rsvp_success): ?>
                <div class="rsvp-error">
                    <h4><?php _e('There was an issue:', 'partyminder'); ?></h4>
                    <p><?php echo esc_html($rsvp_message); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" class="pm-form" id="partyminder-rsvp-form">
                <?php wp_nonce_field('partyminder_rsvp_' . $event_id, 'partyminder_rsvp_nonce'); ?>
                
                <!-- Guest Information -->
                <div class="pm-mb-6">
                    <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Your Information', 'partyminder'); ?></h3>
                    
                    <div class="pm-form-row">
                        <div class="pm-form-group">
                            <label for="guest_name" class="pm-label"><?php _e('Your Name *', 'partyminder'); ?></label>
                            <input type="text" id="guest_name" name="guest_name" class="pm-input" 
                                   value="<?php echo esc_attr($existing_rsvp ? $existing_rsvp->name : ($_POST['guest_name'] ?? '')); ?>" 
                                   required />
                        </div>

                        <div class="pm-form-group">
                            <label for="guest_email" class="pm-label"><?php _e('Email Address *', 'partyminder'); ?></label>
                            <input type="email" id="guest_email" name="guest_email" class="pm-input" 
                                   value="<?php echo esc_attr($existing_rsvp ? $existing_rsvp->email : ($_POST['guest_email'] ?? $_GET['guest_email'] ?? '')); ?>" 
                                   required />
                        </div>
                    </div>
                </div>

                <!-- RSVP Status -->
                <div class="pm-mb-6">
                    <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Will You Attend?', 'partyminder'); ?></h3>
                    
                    <div class="rsvp-options">
                        <?php 
                        $current_status = $existing_rsvp ? $existing_rsvp->status : ($_POST['rsvp_status'] ?? '');
                        $statuses = array(
                            'confirmed' => array('icon' => '🎉', 'title' => __('Yes, I\'ll be there!', 'partyminder'), 'desc' => __('Count me in', 'partyminder')),
                            'maybe' => array('icon' => '🤔', 'title' => __('Maybe', 'partyminder'), 'desc' => __('I\'ll try to make it', 'partyminder')),
                            'declined' => array('icon' => '😔', 'title' => __('Sorry, can\'t make it', 'partyminder'), 'desc' => __('Have fun without me', 'partyminder'))
                        );
                        
                        foreach ($statuses as $status => $info):
                        ?>
                        <label class="rsvp-option <?php echo $current_status === $status ? 'selected' : ''; ?>">
                            <input type="radio" name="rsvp_status" value="<?php echo esc_attr($status); ?>" 
                                   <?php checked($current_status, $status); ?> required />
                            <div class="option-card <?php echo esc_attr($status); ?>">
                                <div class="option-icon"><?php echo $info['icon']; ?></div>
                                <div class="option-text">
                                    <div class="option-title"><?php echo esc_html($info['title']); ?></div>
                                    <div class="option-desc"><?php echo esc_html($info['desc']); ?></div>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Additional Info (only show if not declined) -->
                <div class="pm-mb-6 additional-info" id="additional-info" 
                     class="<?php echo $current_status === 'declined' ? 'pm-conditional-hide' : ''; ?>">
                     
                    <div class="pm-form-group">
                        <label for="dietary_restrictions" class="pm-label"><?php _e('Dietary Restrictions', 'partyminder'); ?></label>
                        <textarea id="dietary_restrictions" name="dietary_restrictions" rows="2" class="pm-textarea" 
                                  placeholder="<?php esc_attr_e('e.g., Vegetarian, gluten-free, no nuts...', 'partyminder'); ?>"><?php echo esc_textarea($existing_rsvp ? $existing_rsvp->dietary_restrictions : ($_POST['dietary_restrictions'] ?? '')); ?></textarea>
                    </div>

                    <div class="pm-form-group">
                        <label for="guest_notes" class="pm-label"><?php _e('Additional Notes', 'partyminder'); ?></label>
                        <textarea id="guest_notes" name="guest_notes" rows="2" class="pm-textarea" 
                                  placeholder="<?php esc_attr_e('Anything else the host should know...', 'partyminder'); ?>"><?php echo esc_textarea($existing_rsvp ? $existing_rsvp->notes : ($_POST['guest_notes'] ?? '')); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="pm-flex pm-flex-center-gap pm-mt-6">
                    <button type="submit" name="partyminder_rsvp" class="pm-button pm-button-primary style-<?php echo esc_attr($button_style); ?>">
                        <?php if ($existing_rsvp): ?>
                            <?php _e('Update My RSVP', 'partyminder'); ?>
                        <?php else: ?>
                            <?php _e('Send My RSVP', 'partyminder'); ?>
                        <?php endif; ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Guest List Preview -->
    <?php if ($guest_stats->confirmed > 0): ?>
    <div class="guest-list-preview">
        <h3><?php _e('Who\'s Coming', 'partyminder'); ?></h3>
        
        <div class="guest-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $guest_stats->confirmed; ?></span>
                <span class="stat-label"><?php _e('Confirmed', 'partyminder'); ?></span>
            </div>
            
            <?php if ($guest_stats->maybe > 0): ?>
            <div class="stat-item">
                <span class="stat-number"><?php echo $guest_stats->maybe; ?></span>
                <span class="stat-label"><?php _e('Maybe', 'partyminder'); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // RSVP status change handler
    const rsvpOptions = document.querySelectorAll('input[name="rsvp_status"]');
    const additionalInfo = document.getElementById('additional-info');
    
    rsvpOptions.forEach(function(option) {
        option.addEventListener('change', function() {
            // Update visual selection
            document.querySelectorAll('.rsvp-option').forEach(function(opt) {
                opt.classList.remove('selected');
            });
            this.closest('.rsvp-option').classList.add('selected');
            
            // Show/hide additional info
            if (this.value === 'declined') {
                additionalInfo.style.display = 'none';
            } else {
                additionalInfo.style.display = 'block';
            }
        });
    });
    
    // Form submission handling
    document.getElementById('partyminder-rsvp-form').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = '<?php _e("Submitting...", "partyminder"); ?>';
        
        // Form will submit normally, this just provides visual feedback
        setTimeout(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }, 5000);
    });
});
</script>