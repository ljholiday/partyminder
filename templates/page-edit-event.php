<?php
/**
 * Template for Edit Event Page
 * Displays the event editing form
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

// Get event ID from URL parameter
$event_id = intval($_GET['event_id'] ?? 0);

if (!$event_id) {
    ?>
    <div class="partyminder-error-page">
        <div class="error-content">
            <h1><?php _e('Event Not Found', 'partyminder'); ?></h1>
            <p><?php _e('Event ID is required to edit an event.', 'partyminder'); ?></p>
            <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-button">
                <?php _e('Back to My Events', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
$event_manager = new PartyMinder_Event_Manager();

// Get the event
$event = $event_manager->get_event($event_id);
if (!$event) {
    ?>
    <div class="partyminder-error-page">
        <div class="error-content">
            <h1><?php _e('Event Not Found', 'partyminder'); ?></h1>
            <p><?php _e('The event you\'re trying to edit could not be found.', 'partyminder'); ?></p>
            <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-button">
                <?php _e('Back to My Events', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php
    get_footer();
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
    <div class="partyminder-error-page">
        <div class="error-content">
            <h1><?php _e('Access Denied', 'partyminder'); ?></h1>
            <p><?php _e('You do not have permission to edit this event.', 'partyminder'); ?></p>
            <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button">
                <?php _e('View Event', 'partyminder'); ?>
            </a>
        </div>
    </div>
    <?php
    get_footer();
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
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-edit-event-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.page-breadcrumb {
    margin-bottom: 20px;
}

.page-breadcrumb a {
    color: var(--pm-primary);
    text-decoration: none;
}

.page-breadcrumb a:hover {
    text-decoration: underline;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 2.5em;
    margin-bottom: 10px;
    color: var(--pm-primary);
}

.event-info-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.event-info-summary .event-icon {
    font-size: 2em;
}

.event-info-summary .event-details h3 {
    margin: 0 0 5px 0;
    color: #333;
}

.event-info-summary .event-details p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.partyminder-form {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 30px;
    margin: 20px 0;
}

.partyminder-form.layout-card {
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.partyminder-form.layout-minimal {
    background: transparent;
    box-shadow: none;
    border: 1px solid #eee;
    border-radius: 6px;
}

.partyminder-form.layout-classic {
    border-radius: 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.form-header {
    text-align: center;
    margin-bottom: 30px;
}

.form-header h2 {
    font-size: 1.8em;
    margin-bottom: 10px;
    color: #333;
}

.form-section {
    margin-bottom: 30px;
}

.form-section h3 {
    font-size: 1.2em;
    margin-bottom: 15px;
    color: var(--pm-primary);
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 5px;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--pm-primary);
}

.form-actions {
    margin-top: 30px;
    text-align: center;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.pm-button {
    background: var(--pm-primary);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 1em;
    transition: background 0.3s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.pm-button:hover {
    opacity: 0.9;
    color: white;
}

.pm-button-secondary {
    background: #6c757d;
}

.pm-button.style-rounded {
    border-radius: 6px;
}

.pm-button.style-pill {
    border-radius: 25px;
}

.pm-button.style-square {
    border-radius: 0;
}

.partyminder-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    margin: 20px 0;
}

.partyminder-success h3 {
    margin-bottom: 15px;
    font-size: 1.5em;
}

.success-actions {
    margin-top: 20px;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.partyminder-errors {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.partyminder-errors h4 {
    margin-bottom: 10px;
}

.partyminder-errors ul {
    margin-left: 20px;
}

.partyminder-error-page {
    max-width: 600px;
    margin: 50px auto;
    padding: 20px;
    text-align: center;
}

.error-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 40px;
}

.error-content h1 {
    color: #dc3545;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .event-info-summary {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<div class="partyminder-edit-event-container">
    
    <!-- Breadcrumb -->
    <div class="page-breadcrumb">
        <a href="<?php echo home_url(); ?>"><?php _e('Home', 'partyminder'); ?></a> 
        &raquo; <a href="<?php echo PartyMinder::get_events_page_url(); ?>"><?php _e('Events', 'partyminder'); ?></a>
        &raquo; <a href="<?php echo PartyMinder::get_my_events_url(); ?>"><?php _e('My Events', 'partyminder'); ?></a>
        &raquo; <?php _e('Edit Event', 'partyminder'); ?>
    </div>
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><?php _e('✏️ Edit Event', 'partyminder'); ?></h1>
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
        <div class="form-header">
            <h2><?php _e('Event Details', 'partyminder'); ?></h2>
            <p><?php _e('Update your event information below.', 'partyminder'); ?></p>
        </div>

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
                <button type="submit" name="partyminder_update_event" class="pm-button pm-button-primary style-<?php echo esc_attr($button_style); ?>">
                    <span>💾</span>
                    <?php _e('Update Event', 'partyminder'); ?>
                </button>
                <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button pm-button-secondary style-<?php echo esc_attr($button_style); ?>">
                    <span>👀</span>
                    <?php _e('View Event', 'partyminder'); ?>
                </a>
                <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-button pm-button-secondary style-<?php echo esc_attr($button_style); ?>">
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
                    $form.before('<div class="partyminder-errors" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 4px;"><h4><?php _e("Please fix the following issues:", "partyminder"); ?></h4><ul><li>' + (response.data || 'Unknown error occurred') + '</li></ul></div>');
                    
                    // Scroll to top to show error message
                    $('html, body').animate({scrollTop: 0}, 500);
                }
            },
            error: function() {
                $form.before('<div class="partyminder-errors" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 4px;"><h4><?php _e("Error", "partyminder"); ?></h4><p><?php _e("Network error. Please try again.", "partyminder"); ?></p></div>');
                
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

<?php get_footer(); ?>