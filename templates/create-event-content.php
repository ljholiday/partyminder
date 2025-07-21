<?php
/**
 * Create Event Content Template - Theme Integrated
 * Content only version for theme integration via the_content filter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check for event creation success
$event_created = false;
$form_errors = array();

// Check if event was just created
if (isset($_GET['partyminder_created']) && $_GET['partyminder_created'] == '1') {
    $creation_data = get_transient('partyminder_event_created_' . get_current_user_id());
    if ($creation_data) {
        $event_created = true;
        // Clear the transient
        delete_transient('partyminder_event_created_' . get_current_user_id());
    }
}

// Check for form errors
$stored_errors = get_transient('partyminder_form_errors_' . get_current_user_id());
if ($stored_errors) {
    $form_errors = $stored_errors;
    // Clear the transient
    delete_transient('partyminder_form_errors_' . get_current_user_id());
}

$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
$button_style = get_option('partyminder_button_style', 'rounded');
$form_layout = get_option('partyminder_form_layout', 'card');
?>

<style>
.partyminder-create-content {
    max-width: 800px;
    margin: 20px auto;
    padding: 0 20px;
}

.partyminder-create-content .page-header {
    text-align: center;
    margin-bottom: 30px;
}

.partyminder-create-content .page-header h2 {
    font-size: 2.2em;
    margin-bottom: 10px;
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-create-content .partyminder-form {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
    margin: 20px 0;
}

.partyminder-create-content .form-section {
    margin-bottom: 25px;
}

.partyminder-create-content .form-section h3 {
    font-size: 1.2em;
    margin-bottom: 15px;
    color: <?php echo esc_attr($primary_color); ?>;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 5px;
}

.partyminder-create-content .form-group {
    margin-bottom: 20px;
}

.partyminder-create-content .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.partyminder-create-content .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.partyminder-create-content .form-group input,
.partyminder-create-content .form-group textarea,
.partyminder-create-content .form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.partyminder-create-content .form-group input:focus,
.partyminder-create-content .form-group textarea:focus,
.partyminder-create-content .form-group select:focus {
    outline: none;
    border-color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-create-content .form-actions {
    margin-top: 30px;
    text-align: center;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.partyminder-create-content .pm-button {
    background: <?php echo esc_attr($primary_color); ?>;
    color: white !important;
    padding: 12px 30px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 1em;
    transition: opacity 0.3s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.partyminder-create-content .pm-button:hover {
    opacity: 0.9;
    color: white !important;
}

.partyminder-create-content .pm-button-secondary {
    background: #6c757d;
}

.partyminder-create-content .partyminder-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    margin: 20px 0;
}

.partyminder-create-content .partyminder-success h3 {
    margin-bottom: 15px;
    font-size: 1.5em;
}

.partyminder-create-content .success-actions {
    margin-top: 20px;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.partyminder-create-content .partyminder-errors {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.partyminder-create-content .partyminder-errors h4 {
    margin-bottom: 10px;
}

.partyminder-create-content .partyminder-errors ul {
    margin-left: 20px;
}

@media (max-width: 768px) {
    .partyminder-create-content .form-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .partyminder-create-content .form-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .partyminder-create-content .pm-button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="partyminder-create-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <h2><?php _e('✨ Create Your Event', 'partyminder'); ?></h2>
        <p><?php _e('Plan your perfect event and invite your guests.', 'partyminder'); ?></p>
    </div>

    <?php if ($event_created): ?>
        <!-- Success Message -->
        <div class="partyminder-success">
            <h3><?php _e('🎉 Event Created Successfully!', 'partyminder'); ?></h3>
            <p><?php _e('Your event has been created and is ready for guests to RSVP.', 'partyminder'); ?></p>
            <div class="success-actions">
                <a href="<?php echo $creation_data['event_url']; ?>" class="pm-button pm-button-primary">
                    <span>👀</span>
                    <?php _e('View Event', 'partyminder'); ?>
                </a>
                <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-button pm-button-secondary">
                    <span>📋</span>
                    <?php _e('My Events', 'partyminder'); ?>
                </a>
                <button type="button" onclick="navigator.share({title: 'Check out my event!', url: '<?php echo esc_js($creation_data['event_url']); ?>'}) || navigator.clipboard.writeText('<?php echo esc_js($creation_data['event_url']); ?>')" class="pm-button pm-button-secondary">
                    <span>📤</span>
                    <?php _e('Share Event', 'partyminder'); ?>
                </button>
            </div>
        </div>
    <?php else: ?>

        <!-- Event Creation Form -->
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
            
            <form method="post" class="partyminder-event-form" id="partyminder-event-form">
                <?php wp_nonce_field('create_partyminder_event', 'partyminder_event_nonce'); ?>
                
                <div class="form-section">
                    <h3><?php _e('Event Details', 'partyminder'); ?></h3>
                    
                    <div class="form-group">
                        <label for="event_title"><?php _e('Event Title *', 'partyminder'); ?></label>
                        <input type="text" id="event_title" name="event_title" 
                               value="<?php echo esc_attr($_POST['event_title'] ?? ''); ?>" 
                               placeholder="<?php esc_attr_e('e.g., Summer Dinner Party', 'partyminder'); ?>" required />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date"><?php _e('Event Date *', 'partyminder'); ?></label>
                            <input type="datetime-local" id="event_date" name="event_date" 
                                   value="<?php echo esc_attr($_POST['event_date'] ?? ''); ?>" 
                                   min="<?php echo date('Y-m-d\TH:i'); ?>" required />
                        </div>

                        <div class="form-group">
                            <label for="guest_limit"><?php _e('Guest Limit', 'partyminder'); ?></label>
                            <input type="number" id="guest_limit" name="guest_limit" 
                                   value="<?php echo esc_attr($_POST['guest_limit'] ?? '10'); ?>" 
                                   min="1" max="100" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="venue_info"><?php _e('Venue/Location', 'partyminder'); ?></label>
                        <input type="text" id="venue_info" name="venue_info" 
                               value="<?php echo esc_attr($_POST['venue_info'] ?? ''); ?>" 
                               placeholder="<?php esc_attr_e('Where will your event take place?', 'partyminder'); ?>" />
                    </div>
                </div>

                <div class="form-section">
                    <h3><?php _e('Event Description', 'partyminder'); ?></h3>
                    <div class="form-group">
                        <label for="event_description"><?php _e('Tell guests about your event', 'partyminder'); ?></label>
                        <textarea id="event_description" name="event_description" rows="4" 
                                  placeholder="<?php esc_attr_e('Describe your event, what to expect, dress code...', 'partyminder'); ?>"><?php echo esc_textarea($_POST['event_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3><?php _e('Host Information', 'partyminder'); ?></h3>
                    
                    <div class="form-group">
                        <label for="host_email"><?php _e('Host Email *', 'partyminder'); ?></label>
                        <input type="email" id="host_email" name="host_email" 
                               value="<?php echo esc_attr($_POST['host_email'] ?? (is_user_logged_in() ? wp_get_current_user()->user_email : '')); ?>" 
                               required />
                    </div>

                    <div class="form-group">
                        <label for="host_notes"><?php _e('Special Notes for Guests', 'partyminder'); ?></label>
                        <textarea id="host_notes" name="host_notes" rows="3" 
                                  placeholder="<?php esc_attr_e('Any special instructions, parking info, what to bring...', 'partyminder'); ?>"><?php echo esc_textarea($_POST['host_notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="partyminder_create_event" class="pm-button pm-button-primary">
                        <span>🎉</span>
                        <?php _e('Create Event', 'partyminder'); ?>
                    </button>
                    <a href="<?php echo PartyMinder::get_events_page_url(); ?>" class="pm-button pm-button-secondary">
                        <span>👈</span>
                        <?php _e('Back to Events', 'partyminder'); ?>
                    </a>
                </div>
            </form>
        </div>

    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('#partyminder-event-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).html('<span>⏳</span> <?php _e("Creating Event...", "partyminder"); ?>');
        
        // Prepare form data
        const formData = new FormData(this);
        formData.append('action', 'partyminder_create_event');
        
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
                    window.location.href = '<?php echo PartyMinder::get_create_event_url(); ?>?partyminder_created=1';
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