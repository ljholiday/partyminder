<?php
/**
 * Create Event Content Template - Theme Integrated
 * Content only version for theme integration via the_content filter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in - redirect to login if not
if (!is_user_logged_in()) {
    $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $login_url = add_query_arg('redirect_to', urlencode($current_url), PartyMinder::get_login_url());
    wp_redirect($login_url);
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


<div class="partyminder-create-content pm-container">
    
    <!-- Page Header -->
    <div class="pm-card-header pm-mb-6">
        <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php _e('✨ Create Your Event', 'partyminder'); ?></h1>
        <p class="pm-text-muted"><?php _e('Plan your perfect event and invite your guests.', 'partyminder'); ?></p>
    </div>

    <?php if ($event_created): ?>
        <!-- Success Message -->
        <div class="pm-message pm-message-success pm-mb-6">
            <h3 class="pm-heading pm-heading-md pm-mb-2"><?php _e('🎉 Event Created Successfully!', 'partyminder'); ?></h3>
            <p class="pm-mb-4"><?php _e('Your event has been created and is ready for guests to RSVP.', 'partyminder'); ?></p>
            <div class="pm-flex pm-flex-center-gap">
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
        <div class="pm-card">
            
            <?php if (!empty($form_errors)): ?>
                <div class="pm-message pm-message-error pm-mb-4">
                    <h4 class="pm-heading pm-heading-sm pm-mb-2"><?php _e('Please fix the following issues:', 'partyminder'); ?></h4>
                    <ul class="pm-m-0 pm-pl-5">
                        <?php foreach ($form_errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="pm-card-body">
            <form method="post" class="pm-form" id="partyminder-event-form">
                <?php wp_nonce_field('create_partyminder_event', 'partyminder_event_nonce'); ?>
                
                <div class="pm-mb-6">
                    <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Event Details', 'partyminder'); ?></h3>
                    
                    <div class="pm-form-group">
                        <label for="event_title" class="pm-label"><?php _e('Event Title *', 'partyminder'); ?></label>
                        <input type="text" id="event_title" name="event_title" class="pm-input"
                               value="<?php echo esc_attr($_POST['event_title'] ?? ''); ?>" 
                               placeholder="<?php esc_attr_e('e.g., Summer Dinner Party', 'partyminder'); ?>" required />
                    </div>

                    <div class="pm-form-row">
                        <div class="pm-form-group">
                            <label for="event_date" class="pm-label"><?php _e('Event Date *', 'partyminder'); ?></label>
                            <input type="datetime-local" id="event_date" name="event_date" class="pm-input"
                                   value="<?php echo esc_attr($_POST['event_date'] ?? ''); ?>" 
                                   min="<?php echo date('Y-m-d\TH:i'); ?>" required />
                        </div>

                        <div class="pm-form-group">
                            <label for="guest_limit" class="pm-label"><?php _e('Guest Limit', 'partyminder'); ?></label>
                            <input type="number" id="guest_limit" name="guest_limit" class="pm-input"
                                   value="<?php echo esc_attr($_POST['guest_limit'] ?? '10'); ?>" 
                                   min="1" max="100" />
                        </div>
                    </div>

                    <div class="pm-form-group">
                        <label for="venue_info" class="pm-label"><?php _e('Venue/Location', 'partyminder'); ?></label>
                        <input type="text" id="venue_info" name="venue_info" class="pm-input"
                               value="<?php echo esc_attr($_POST['venue_info'] ?? ''); ?>" 
                               placeholder="<?php esc_attr_e('Where will your event take place?', 'partyminder'); ?>" />
                    </div>
                </div>

                <div class="pm-mb-6">
                    <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Event Description', 'partyminder'); ?></h3>
                    <div class="pm-form-group">
                        <label for="event_description" class="pm-label"><?php _e('Tell guests about your event', 'partyminder'); ?></label>
                        <textarea id="event_description" name="event_description" rows="4" class="pm-textarea"
                                  placeholder="<?php esc_attr_e('Describe your event, what to expect, dress code...', 'partyminder'); ?>"><?php echo esc_textarea($_POST['event_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="pm-mb-6">
                    <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Host Information', 'partyminder'); ?></h3>
                    
                    <div class="pm-form-group">
                        <label for="host_email" class="pm-label"><?php _e('Host Email *', 'partyminder'); ?></label>
                        <input type="email" id="host_email" name="host_email" class="pm-input"
                               value="<?php echo esc_attr($_POST['host_email'] ?? (is_user_logged_in() ? wp_get_current_user()->user_email : '')); ?>" 
                               required />
                    </div>

                    <div class="pm-form-group">
                        <label for="host_notes" class="pm-label"><?php _e('Special Notes for Guests', 'partyminder'); ?></label>
                        <textarea id="host_notes" name="host_notes" rows="3" class="pm-textarea"
                                  placeholder="<?php esc_attr_e('Any special instructions, parking info, what to bring...', 'partyminder'); ?>"><?php echo esc_textarea($_POST['host_notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="pm-flex pm-flex-center-gap pm-mt-6">
                    <button type="submit" name="partyminder_create_event" class="pm-button pm-button-primary pm-button-large">
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