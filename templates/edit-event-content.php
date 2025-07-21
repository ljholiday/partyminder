<?php
/**
 * Edit Event Content Template - Theme Integrated
 * Content only version for theme integration via the_content filter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get event ID from URL parameter
$event_id = intval($_GET['event_id'] ?? 0);

if (!$event_id) {
    ?>
    <div class="partyminder-error-content">
        <div class="error-wrapper">
            <h3><?php _e('Event Not Found', 'partyminder'); ?></h3>
            <p><?php _e('Event ID is required to edit an event.', 'partyminder'); ?></p>
            <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-button">
                <?php _e('Back to My Events', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php
    return;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
$event_manager = new PartyMinder_Event_Manager();

// Get the event
$event = $event_manager->get_event($event_id);
if (!$event) {
    ?>
    <div class="partyminder-error-content">
        <div class="error-wrapper">
            <h3><?php _e('Event Not Found', 'partyminder'); ?></h3>
            <p><?php _e('The event you\'re trying to edit could not be found.', 'partyminder'); ?></p>
            <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-button">
                <?php _e('Back to My Events', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php
    return;
}

// Check permissions - only event creator or admin can edit
$current_user = wp_get_current_user();
$can_edit = false;

if (current_user_can('edit_posts') || 
    (is_user_logged_in() && $current_user->ID == $event->post_author) ||
    ($current_user->user_email == $event->host_email)) {
    $can_edit = true;
}

if (!$can_edit) {
    ?>
    <div class="partyminder-error-content">
        <div class="error-wrapper">
            <h3><?php _e('Access Denied', 'partyminder'); ?></h3>
            <p><?php _e('You do not have permission to edit this event.', 'partyminder'); ?></p>
            <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button">
                <?php _e('View Event', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php
    return;
}

// Check for event update success
$event_updated = false;
$form_errors = array();

// Check if event was just updated
if (isset($_GET['partyminder_updated']) && $_GET['partyminder_updated'] == '1') {
    $update_data = get_transient('partyminder_event_updated_' . get_current_user_id());
    if ($update_data) {
        $event_updated = true;
        // Clear the transient
        delete_transient('partyminder_event_updated_' . get_current_user_id());
        // Refresh event data
        $event = $event_manager->get_event($event_id);
    }
}

// Check for form errors
$stored_errors = get_transient('partyminder_edit_form_errors_' . get_current_user_id());
if ($stored_errors) {
    $form_errors = $stored_errors;
    // Clear the transient
    delete_transient('partyminder_edit_form_errors_' . get_current_user_id());
}

$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
$button_style = get_option('partyminder_button_style', 'rounded');
$form_layout = get_option('partyminder_form_layout', 'card');

// Format event date for datetime-local input
$event_datetime = date('Y-m-d\TH:i', strtotime($event->event_date));
?>

<style>
/* Dynamic theme colors for edit event content */
.partyminder-edit-content .page-header h2 {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-edit-content .form-section h3 {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-edit-content .form-group input:focus,
.partyminder-edit-content .form-group textarea:focus,
.partyminder-edit-content .form-group select:focus {
    border-color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-edit-content .pm-button {
    background: <?php echo esc_attr($primary_color); ?>;
}
</style>

<div class="partyminder-edit-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <h2><?php _e('✏️ Edit Event', 'partyminder'); ?></h2>
        <p><?php _e('Update your event details below.', 'partyminder'); ?></p>
    </div>

    <!-- Event Info Summary -->
    <div class="event-info-summary">
        <div class="event-icon">🎉</div>
        <div class="event-details">
            <h3><?php echo esc_html($event->title); ?></h3>
            <p>
                <?php echo date('M j, Y g:i A', strtotime($event->event_date)); ?>
                <?php if ($event->venue_info): ?>
                    • <?php echo esc_html($event->venue_info); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if ($event_updated): ?>
        <!-- Success Message -->
        <div class="partyminder-success">
            <h3><?php _e('✅ Event Updated Successfully!', 'partyminder'); ?></h3>
            <p><?php _e('Your event changes have been saved.', 'partyminder'); ?></p>
            <div class="success-actions">
                <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button pm-button-primary">
                    <span>👀</span>
                    <?php _e('View Event', 'partyminder'); ?>
                </a>
                <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-button pm-button-secondary">
                    <span>📋</span>
                    <?php _e('My Events', 'partyminder'); ?>
                </a>
                <button type="button" onclick="navigator.share({title: 'Check out this event!', url: '<?php echo esc_js(get_permalink($event->ID)); ?>'}) || navigator.clipboard.writeText('<?php echo esc_js(get_permalink($event->ID)); ?>')" class="pm-button pm-button-secondary">
                    <span>📤</span>
                    <?php _e('Share Event', 'partyminder'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Event Edit Form -->
    <div class="partyminder-form layout-<?php echo esc_attr($form_layout); ?>">

        <?php if (!empty($form_errors)): ?>
            <div class="partyminder-errors">
                <h4><?php _e('Please fix the following issues:', 'partyminder'); ?></h4>
                <ul>
                    <?php foreach ($form_errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        

        <form method="post" class="partyminder-event-edit-form" id="partyminder-event-edit-form">
            <?php wp_nonce_field('edit_partyminder_event', 'partyminder_edit_event_nonce'); ?>
            <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>" />
            
            <div class="form-section">
                <h3><?php _e('Event Details', 'partyminder'); ?></h3>
                
                <div class="form-group">
                    <label for="event_title"><?php _e('Event Title *', 'partyminder'); ?></label>
                    <input type="text" id="event_title" name="event_title" 
                           value="<?php echo esc_attr($_POST['event_title'] ?? $event->title); ?>" 
                           placeholder="<?php esc_attr_e('e.g., Summer Dinner Party', 'partyminder'); ?>" required />
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date"><?php _e('Event Date *', 'partyminder'); ?></label>
                        <input type="datetime-local" id="event_date" name="event_date" 
                               value="<?php echo esc_attr($_POST['event_date'] ?? $event_datetime); ?>" 
                               min="<?php echo date('Y-m-d\TH:i'); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="guest_limit"><?php _e('Guest Limit', 'partyminder'); ?></label>
                        <input type="number" id="guest_limit" name="guest_limit" 
                               value="<?php echo esc_attr($_POST['guest_limit'] ?? $event->guest_limit); ?>" 
                               min="1" max="100" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="venue_info"><?php _e('Venue/Location', 'partyminder'); ?></label>
                    <input type="text" id="venue_info" name="venue_info" 
                           value="<?php echo esc_attr($_POST['venue_info'] ?? $event->venue_info); ?>" 
                           placeholder="<?php esc_attr_e('Where will your event take place?', 'partyminder'); ?>" />
                </div>
            </div>

            <div class="form-section">
                <h3><?php _e('Event Description', 'partyminder'); ?></h3>
                <div class="form-group">
                    <label for="event_description"><?php _e('Tell guests about your event', 'partyminder'); ?></label>
                    <textarea id="event_description" name="event_description" rows="4" 
                              placeholder="<?php esc_attr_e('Describe your event, what to expect...', 'partyminder'); ?>"><?php echo esc_textarea($_POST['event_description'] ?? $event->description); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3><?php _e('Host Information', 'partyminder'); ?></h3>
                
                <div class="form-group">
                    <label for="host_email"><?php _e('Host Email *', 'partyminder'); ?></label>
                    <input type="email" id="host_email" name="host_email" 
                           value="<?php echo esc_attr($_POST['host_email'] ?? $event->host_email); ?>" 
                           required />
                </div>

                <div class="form-group">
                    <label for="host_notes"><?php _e('Special Notes for Guests', 'partyminder'); ?></label>
                    <textarea id="host_notes" name="host_notes" rows="3" 
                              placeholder="<?php esc_attr_e('Any special instructions, parking info...', 'partyminder'); ?>"><?php echo esc_textarea($_POST['host_notes'] ?? $event->host_notes); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="partyminder_update_event" class="pm-button pm-button-primary">
                    <span>💾</span>
                    <?php _e('Update Event', 'partyminder'); ?>
                </button>
                <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button pm-button-secondary">
                    <span>👀</span>
                    <?php _e('View Event', 'partyminder'); ?>
                </a>
                <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-button pm-button-secondary">
                    <span>👈</span>
                    <?php _e('Back to My Events', 'partyminder'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#partyminder-event-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).html('<span>⏳</span> <?php _e("Updating Event...", "partyminder"); ?>');
        
        // Prepare form data
        const formData = new FormData(this);
        formData.append('action', 'partyminder_update_event');
        
        // Convert FormData to regular object for jQuery
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Redirect to success page
                    window.location.href = '<?php echo PartyMinder::get_edit_event_url($event_id); ?>?partyminder_updated=1';
                } else {
                    // Show error message
                    $form.before('<div class="partyminder-errors"><h4><?php _e("Please fix the following issues:", "partyminder"); ?></h4><ul><li>' + (response.data || 'Unknown error occurred') + '</li></ul></div>');
                    
                    // Scroll to top to show error message
                    $('html, body').animate({scrollTop: 0}, 500);
                }
            },
            error: function() {
                $form.before('<div class="partyminder-errors"><h4><?php _e("Error", "partyminder"); ?></h4><p><?php _e("Network error. Please try again.", "partyminder"); ?></p></div>');
                
                // Scroll to top to show error message
                $('html, body').animate({scrollTop: 0}, 500);
            },
            complete: function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>