<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check for event creation success
$event_created = false;
$event_url = '';
$form_errors = array();

// Check if event was just created (from query parameter and transient)
if (isset($_GET['partyminder_created']) && $_GET['partyminder_created'] == '1') {
    $event_data = get_transient('partyminder_event_created_' . get_current_user_id());
    if ($event_data) {
        $event_created = true;
        $event_url = $event_data['event_url'];
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
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}
.partyminder-form.layout-<?php echo esc_attr($form_layout); ?> {
    /* Form layout styles will be applied */
}
.btn.style-<?php echo esc_attr($button_style); ?> {
    /* Button style variations */
}
</style>

<div class="partyminder-event-form-container">
    <?php if ($event_created): ?>
        <!-- Success Message -->
        <div class="partyminder-success">
            <h3><?php _e('🎉 Event Created Successfully!', 'partyminder'); ?></h3>
            <p><?php _e('Your party event has been created and is ready for guests.', 'partyminder'); ?></p>
            <div class="success-actions">
                <a href="<?php echo esc_url($event_url); ?>" class="btn">
                    <?php _e('View Event', 'partyminder'); ?>
                </a>
                <button type="button" onclick="navigator.share({title: 'Check out this event!', url: '<?php echo esc_js($event_url); ?>'}) || navigator.clipboard.writeText('<?php echo esc_js($event_url); ?>')">
                    <?php _e('Share Event', 'partyminder'); ?>
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- Event Creation Form -->
        <div class="partyminder-form layout-<?php echo esc_attr($form_layout); ?>">
            <div class="form-header">
                <h2><?php echo esc_html($atts['title'] ?? __('Create Your Event', 'partyminder')); ?></h2>
                <p><?php _e('Plan an amazing gathering with our event management tools.', 'partyminder'); ?></p>
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
                                  placeholder="<?php esc_attr_e('Describe your event, what to expect...', 'partyminder'); ?>"><?php echo esc_textarea($_POST['event_description'] ?? ''); ?></textarea>
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
                                  placeholder="<?php esc_attr_e('Any special instructions, parking info...', 'partyminder'); ?>"><?php echo esc_textarea($_POST['host_notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="partyminder_create_event" class="btn style-<?php echo esc_attr($button_style); ?>">
                        <?php _e('Create My Event', 'partyminder'); ?>
                    </button>
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
        const originalText = $submitBtn.text();
        
        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).text('<?php _e("Creating Event...", "partyminder"); ?>');
        
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
                    // Show success message
                    $form.before('<div class="partyminder-success partyminder-js-success"><h3><?php _e("🎉 Event Created Successfully!", "partyminder"); ?></h3><p><?php _e("Your party event has been created and is ready for guests.", "partyminder"); ?></p><div class="success-actions"><a href="' + response.data.event_url + '" class="btn"><?php _e("View Event", "partyminder"); ?></a></div></div>');
                    
                    // Hide the form
                    $form.hide();
                } else {
                    // Show error message
                    $form.before('<div class="partyminder-errors partyminder-js-errors"><h4><?php _e("Please fix the following issues:", "partyminder"); ?></h4><ul><li>' + (response.data || 'Unknown error occurred') + '</li></ul></div>');
                }
            },
            error: function() {
                $form.before('<div class="partyminder-errors partyminder-js-errors"><h4><?php _e("Error", "partyminder"); ?></h4><p><?php _e("Network error. Please try again.", "partyminder"); ?></p></div>');
            },
            complete: function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>