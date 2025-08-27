<?php
/**
 * Edit Event Content Template - Theme Integrated
 * Content only version for theme integration via the_content filter
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get event ID from URL parameter
$event_id = intval( $_GET['event_id'] ?? 0 );

if ( ! $event_id ) {
	?>
	<div class="partyminder-error-content">
		<div class="pm-error-wrapper">
			<h3><?php _e( 'Event Not Found', 'partyminder' ); ?></h3>
			<p><?php _e( 'Event ID is required to edit an event.', 'partyminder' ); ?></p>
			<a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-btn">
				<?php _e( 'Back to My Events', 'partyminder' ); ?>
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
$event = $event_manager->get_event( $event_id );
if ( ! $event ) {
	?>
	<div class="partyminder-error-content">
		<div class="pm-error-wrapper">
			<h3><?php _e( 'Event Not Found', 'partyminder' ); ?></h3>
			<p><?php _e( 'The event you\'re trying to edit could not be found.', 'partyminder' ); ?></p>
			<a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-btn">
				<?php _e( 'Back to My Events', 'partyminder' ); ?>
			</a>
		</div>
	</div>
	<?php
	return;
}

// Check permissions - only event creator or admin can edit
$current_user = wp_get_current_user();
$can_edit     = false;

if ( current_user_can( 'edit_posts' ) ||
	( is_user_logged_in() && $current_user->ID == $event->author_id ) ||
	( $current_user->user_email == $event->host_email ) ) {
	$can_edit = true;
}

if ( ! $can_edit ) {
	?>
	<div class="partyminder-error-content">
		<div class="pm-error-wrapper">
			<h3><?php _e( 'Access Denied', 'partyminder' ); ?></h3>
			<p><?php _e( 'You do not have permission to edit this event.', 'partyminder' ); ?></p>
			<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn">
				<?php _e( 'View Event', 'partyminder' ); ?>
			</a>
		</div>
	</div>
	<?php
	return;
}

// Check for event update success
$event_updated = false;
$form_errors   = array();

// Check if event was just updated
if ( isset( $_GET['partyminder_updated'] ) && $_GET['partyminder_updated'] == '1' ) {
	$update_data = get_transient( 'partyminder_event_updated_' . get_current_user_id() );
	if ( $update_data ) {
		$event_updated = true;
		// Clear the transient
		delete_transient( 'partyminder_event_updated_' . get_current_user_id() );
		// Refresh event data
		$event = $event_manager->get_event( $event_id );
	}
}

// Check for form errors
$stored_errors = get_transient( 'partyminder_edit_form_errors_' . get_current_user_id() );
if ( $stored_errors ) {
	$form_errors = $stored_errors;
	// Clear the transient
	delete_transient( 'partyminder_edit_form_errors_' . get_current_user_id() );
}

$primary_color   = get_option( 'partyminder_primary_color', '#667eea' );
$secondary_color = get_option( 'partyminder_secondary_color', '#764ba2' );
$button_style    = get_option( 'partyminder_button_style', 'rounded' );
$form_layout     = get_option( 'partyminder_form_layout', 'card' );

// Parse existing event date/time data for Flatpickr fields
$event_start_date = date( 'Y-m-d', strtotime( $event->event_date ) );
$event_start_time = $event->all_day ? '' : date( 'H:i', strtotime( $event->event_date ) );
$event_end_date = $event->end_date ? date( 'Y-m-d', strtotime( $event->end_date ) ) : '';
$event_end_time = ($event->end_date && !$event->all_day) ? date( 'H:i', strtotime( $event->end_date ) ) : '';

// Set up template variables
$page_title       = __( 'Edit Event', 'partyminder' );
$page_description = __( 'Update your event details below.', 'partyminder' );
$breadcrumbs      = array(
	array(
		'title' => __( 'Dashboard', 'partyminder' ),
		'url'   => PartyMinder::get_dashboard_url(),
	),
	array(
		'title' => __( 'My Events', 'partyminder' ),
		'url'   => PartyMinder::get_my_events_url(),
	),
	array(
		'title' => esc_html( $event->title ),
		'url'   => home_url( '/events/' . $event->slug ),
	),
	array( 'title' => __( 'Edit', 'partyminder' ) ),
);

// Main content
ob_start();
?>
<!-- Event Info Summary -->
<div class="pm-event-info-summary">
	<div class="pm-event-icon"></div>
	<div class="pm-event-details">
		<h3><?php echo esc_html( $event->title ); ?></h3>
		<p>
			<?php echo date( 'M j, Y g:i A', strtotime( $event->event_date ) ); ?>
			<?php if ( $event->venue_info ) : ?>
				• <?php echo esc_html( $event->venue_info ); ?>
			<?php endif; ?>
		</p>
	</div>
</div>
<?php if ( $event_updated ) : ?>
	<!-- Success Message -->
	<div class="pm-alert pm-alert-success pm-mb-4">
		<h3><?php _e( 'Event Updated Successfully!', 'partyminder' ); ?></h3>
		<p><?php _e( 'Your event changes have been saved.', 'partyminder' ); ?></p>
		<div class="pm-success-actions">
			<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn">
				<?php _e( 'View Event', 'partyminder' ); ?>
			</a>
			<a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'My Events', 'partyminder' ); ?>
			</a>
			<button type="button" onclick="navigator.share({title: 'Check out this event!', url: '<?php echo esc_js( home_url( '/events/' . $event->slug ) ); ?>'}) || navigator.clipboard.writeText('<?php echo esc_js( home_url( '/events/' . $event->slug ) ); ?>')" class="pm-btn pm-btn-secondary">
				<?php _e( 'Share Event', 'partyminder' ); ?>
			</button>
		</div>
	</div>
<?php endif; ?>
<!-- Event Edit Form -->
<?php if ( ! empty( $form_errors ) ) : ?>
	<div class="pm-alert pm-alert-error pm-mb-4">
		<h4><?php _e( 'Please fix the following issues:', 'partyminder' ); ?></h4>
		<ul>
			<?php foreach ( $form_errors as $error ) : ?>
				<li><?php echo esc_html( $error ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<form method="post" class="pm-form" id="partyminder-event-edit-form" enctype="multipart/form-data">
	<?php wp_nonce_field( 'edit_partyminder_event', 'partyminder_edit_event_nonce' ); ?>
	<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
	
	<div class="pm-mb-4">
		<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Event Details', 'partyminder' ); ?></h3>
		
		<div class="pm-form-group">
			<label for="event_title" class="pm-form-label"><?php _e( 'Event Title *', 'partyminder' ); ?></label>
			<input type="text" id="event_title" name="event_title" class="pm-form-input" 
					value="<?php echo esc_attr( $_POST['event_title'] ?? $event->title ); ?>" 
					placeholder="<?php esc_attr_e( 'e.g., Summer Dinner Party', 'partyminder' ); ?>" required />
		</div>

		<div class="pm-form-row">
			<!-- Event Date & Time Section -->
			<div class="pm-form-section">
				<h3 class="pm-heading pm-heading-sm pm-mb-4"><?php _e('When is your event?', 'partyminder'); ?></h3>
				
				<!-- All Day Toggle -->
				<div class="pm-form-group pm-mb-4">
					<label class="pm-form-label pm-flex pm-gap-2">
						<input type="checkbox" id="all_day" name="all_day" value="1" class="pm-form-checkbox" 
							   <?php checked( $event->all_day ); ?>>
						<?php _e('All day event', 'partyminder'); ?>
					</label>
				</div>
				
				<!-- Start Date & Time -->
				<div class="pm-form-group pm-grid pm-grid-2 pm-gap">
					<div>
						<label for="start_date" class="pm-form-label"><?php _e('Start Date *', 'partyminder'); ?></label>
						<input type="text" id="start_date" name="start_date" class="pm-form-input" 
							   value="<?php echo esc_attr( $_POST['start_date'] ?? $event_start_date ); ?>"
							   placeholder="<?php _e('Select start date...', 'partyminder'); ?>" required />
					</div>
					<div class="pm-time-field">
						<label for="start_time" class="pm-form-label"><?php _e('Start Time *', 'partyminder'); ?></label>
						<input type="text" id="start_time" name="start_time" class="pm-form-input" 
							   value="<?php echo esc_attr( $_POST['start_time'] ?? $event_start_time ); ?>"
							   placeholder="<?php _e('Select start time...', 'partyminder'); ?>" />
					</div>
				</div>
				
				<!-- End Date & Time -->
				<div class="pm-form-group pm-grid pm-grid-2 pm-gap">
					<div>
						<label for="end_date" class="pm-form-label"><?php _e('End Date', 'partyminder'); ?></label>
						<input type="text" id="end_date" name="end_date" class="pm-form-input" 
							   value="<?php echo esc_attr( $_POST['end_date'] ?? $event_end_date ); ?>"
							   placeholder="<?php _e('Select end date...', 'partyminder'); ?>" />
					</div>
					<div class="pm-time-field">
						<label for="end_time" class="pm-form-label"><?php _e('End Time', 'partyminder'); ?></label>
						<input type="text" id="end_time" name="end_time" class="pm-form-input" 
							   value="<?php echo esc_attr( $_POST['end_time'] ?? $event_end_time ); ?>"
							   placeholder="<?php _e('Select end time...', 'partyminder'); ?>" />
					</div>
				</div>
				
				<!-- Recurrence Options -->
				<div class="pm-form-group pm-mt-4">
					<label for="recurrence_type" class="pm-form-label"><?php _e('Repeat Event', 'partyminder'); ?></label>
					<select id="recurrence_type" name="recurrence_type" class="pm-form-input">
						<option value="none" <?php selected( $event->recurrence_type ?? 'none', 'none' ); ?>><?php _e('Does not repeat', 'partyminder'); ?></option>
						<option value="daily" <?php selected( $event->recurrence_type ?? '', 'daily' ); ?>><?php _e('Daily', 'partyminder'); ?></option>
						<option value="weekly" <?php selected( $event->recurrence_type ?? '', 'weekly' ); ?>><?php _e('Weekly', 'partyminder'); ?></option>
						<option value="monthly" <?php selected( $event->recurrence_type ?? '', 'monthly' ); ?>><?php _e('Monthly', 'partyminder'); ?></option>
						<option value="yearly" <?php selected( $event->recurrence_type ?? '', 'yearly' ); ?>><?php _e('Yearly', 'partyminder'); ?></option>
						<option value="custom" <?php selected( $event->recurrence_type ?? '', 'custom' ); ?>><?php _e('Custom...', 'partyminder'); ?></option>
					</select>
				</div>
				
				<!-- Recurrence Options (simplified for edit form) -->
				<div class="pm-recurrence-options" style="display: none;">
					<div class="pm-recurrence-interval pm-form-group pm-mt-4">
						<label for="recurrence_interval" class="pm-form-label"><?php _e('Repeat every', 'partyminder'); ?></label>
						<input type="number" id="recurrence_interval" name="recurrence_interval" 
							   class="pm-form-input" min="1" 
							   value="<?php echo esc_attr( $event->recurrence_interval ?? 1 ); ?>" />
					</div>
				</div>
			</div>
		</div>

			<div class="pm-form-group">
				<label for="guest_limit" class="pm-form-label"><?php _e( 'Guest Limit', 'partyminder' ); ?></label>
				<input type="number" id="guest_limit" name="guest_limit" class="pm-form-input" 
						value="<?php echo esc_attr( $_POST['guest_limit'] ?? $event->guest_limit ); ?>" 
						min="1" max="100" />
			</div>
		</div>

		<div class="pm-form-group">
			<label for="venue_info" class="pm-form-label"><?php _e( 'Venue/Location', 'partyminder' ); ?></label>
			<input type="text" id="venue_info" name="venue_info" class="pm-form-input" 
					value="<?php echo esc_attr( $_POST['venue_info'] ?? $event->venue_info ); ?>" 
					placeholder="<?php esc_attr_e( 'Where will your event take place?', 'partyminder' ); ?>" />
		</div>
	</div>

	<div class="pm-mb-4">
		<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Event Description', 'partyminder' ); ?></h3>
		<div class="pm-form-group">
			<label for="event_description" class="pm-form-label"><?php _e( 'Tell guests about your event', 'partyminder' ); ?></label>
			<textarea id="event_description" name="event_description" rows="4" class="pm-form-textarea" 
						placeholder="<?php esc_attr_e( 'Describe your event, what to expect...', 'partyminder' ); ?>"><?php echo esc_textarea( $_POST['event_description'] ?? $event->description ); ?></textarea>
		</div>
		
		<!-- Cover Image Upload -->
		<div class="pm-form-group">
			<label for="cover_image" class="pm-form-label"><?php _e( 'Cover Image', 'partyminder' ); ?></label>
			<input type="file" id="cover_image" name="cover_image" class="pm-form-input" accept="image/*">
			<p class="pm-form-help pm-text-muted"><?php _e( 'Optional: Upload a cover image for this event (JPG, PNG, max 5MB)', 'partyminder' ); ?></p>
			
			<!-- Upload Progress Bar -->
			<div id="event-upload-progress" class="pm-progress-container pm-mt-2" style="display: none;">
				<div class="pm-progress-bar">
					<div class="pm-progress-fill" style="width: 0%;"></div>
				</div>
				<div id="event-upload-message" class="pm-text-muted pm-mt-1"></div>
			</div>
			
			<?php if ( ! empty( $event->featured_image ) ) : ?>
				<div class="pm-current-cover pm-mt-2">
					<p class="pm-text-muted pm-mb-2"><?php _e( 'Current cover image:', 'partyminder' ); ?></p>
					<img src="<?php echo esc_url( $event->featured_image ); ?>" alt="Current cover" style="max-width: 200px; height: auto; border-radius: 4px;">
					<label class="pm-mt-2">
						<input type="checkbox" name="remove_cover_image" value="1"> <?php _e( 'Remove current cover image', 'partyminder' ); ?>
					</label>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="pm-mb-4">
		<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Host Information', 'partyminder' ); ?></h3>
		
		<div class="pm-form-group">
			<label for="host_email" class="pm-form-label"><?php _e( 'Host Email *', 'partyminder' ); ?></label>
			<input type="email" id="host_email" name="host_email" class="pm-form-input" 
					value="<?php echo esc_attr( $_POST['host_email'] ?? $event->host_email ); ?>" 
					required />
		</div>

		<div class="pm-form-group">
			<label for="host_notes" class="pm-form-label"><?php _e( 'Special Notes for Guests', 'partyminder' ); ?></label>
			<textarea id="host_notes" name="host_notes" rows="3" class="pm-form-textarea" 
						placeholder="<?php esc_attr_e( 'Any special instructions, parking info...', 'partyminder' ); ?>"><?php echo esc_textarea( $_POST['host_notes'] ?? $event->host_notes ); ?></textarea>
		</div>
		
		<div class="pm-form-group">
			<label for="privacy" class="pm-form-label"><?php _e( 'Event Privacy *', 'partyminder' ); ?></label>
			<select id="privacy" name="privacy" class="pm-form-input" required>
				<option value="public" <?php selected( $_POST['privacy'] ?? $event->privacy ?? 'public', 'public' ); ?>>
					<?php _e( 'Public - Anyone can find and RSVP to this event', 'partyminder' ); ?>
				</option>
				<option value="private" <?php selected( $_POST['privacy'] ?? $event->privacy ?? 'public', 'private' ); ?>>
					<?php _e( 'Private - Only invited guests can see and RSVP', 'partyminder' ); ?>
				</option>
			</select>
			<p class="pm-form-help pm-text-muted"><?php _e( 'Public events appear in event listings. Private events are only accessible to people you invite.', 'partyminder' ); ?></p>
		</div>
	</div>

	<div class="pm-form-actions">
		<button type="submit" name="partyminder_update_event" class="pm-btn">
			<?php _e( 'Update Event', 'partyminder' ); ?>
		</button>
		<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'View Event', 'partyminder' ); ?>
		</a>
		<a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Back to My Events', 'partyminder' ); ?>
		</a>
		<button type="button" onclick="deleteEvent()" class="pm-btn pm-btn-danger">
			<?php _e( 'Delete Event', 'partyminder' ); ?>
		</button>
	</div>
</form>
<?php
$content = ob_get_clean();

// Include form template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php';
?>

<script>
jQuery(document).ready(function($) {
	$('#partyminder-event-edit-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.html();
		const $progress = $('#event-upload-progress');
		const $progressFill = $('.pm-progress-fill');
		const $message = $('#event-upload-message');
		
		// Disable submit button and show loading
		$submitBtn.prop('disabled', true).html('<?php _e( 'Updating Event...', 'partyminder' ); ?>');
		
		// Check if we have file uploads
		const hasFiles = $form.find('input[type="file"]').get().some(input => input.files.length > 0);
		
		if (hasFiles) {
			$progress.show();
			$message.empty();
		}
		
		// Prepare form data properly for file uploads
		const formData = new FormData(this);
		formData.append('action', 'partyminder_update_event');
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			xhr: function() {
				const xhr = new window.XMLHttpRequest();
				if (hasFiles) {
					xhr.upload.addEventListener('progress', function(evt) {
						if (evt.lengthComputable) {
							const percentComplete = (evt.loaded / evt.total) * 100;
							$progressFill.css('width', percentComplete + '%');
						}
					}, false);
				}
				return xhr;
			},
			success: function(response) {
				if (response.success) {
					// Redirect to success page
					window.location.href = '<?php echo PartyMinder::get_edit_event_url( $event_id ); ?>?partyminder_updated=1';
				} else {
					// Show error message
					$form.before('<div class="partyminder-errors"><h4><?php _e( 'Please fix the following issues:', 'partyminder' ); ?></h4><ul><li>' + (response.data || 'Unknown error occurred') + '</li></ul></div>');
					
					// Scroll to top to show error message
					$('html, body').animate({scrollTop: 0}, 500);
				}
			},
			error: function() {
				$form.before('<div class="partyminder-errors"><h4><?php _e( 'Error', 'partyminder' ); ?></h4><p><?php _e( 'Network error. Please try again.', 'partyminder' ); ?></p></div>');
				
				// Scroll to top to show error message
				$('html, body').animate({scrollTop: 0}, 500);
			},
			complete: function() {
				// Re-enable submit button
				$submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});
	
	// Delete Event functionality
	window.deleteEvent = function() {
		if (!confirm('<?php _e( 'Are you sure you want to delete this event? This action cannot be undone.', 'partyminder' ); ?>')) {
			return;
		}
		
		const $deleteBtn = $('button[onclick="deleteEvent()"]');
		const originalText = $deleteBtn.html();
		
		// Disable button and show loading
		$deleteBtn.prop('disabled', true).html('<?php _e( 'Deleting...', 'partyminder' ); ?>');
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: {
				action: 'partyminder_delete_event',
				event_id: <?php echo $event_id; ?>,
				nonce: partyminder_ajax.event_nonce
			},
			success: function(response) {
				if (response.success) {
					// Show success message briefly then redirect
					$deleteBtn.html('<?php _e( 'Event Deleted!', 'partyminder' ); ?>');
					setTimeout(function() {
						window.location.href = response.data.redirect_url || '<?php echo PartyMinder::get_my_events_url(); ?>';
					}, 1000);
				} else {
					alert(response.data || '<?php _e( 'Failed to delete event.', 'partyminder' ); ?>');
					$deleteBtn.prop('disabled', false).html(originalText);
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
				$deleteBtn.prop('disabled', false).html(originalText);
			}
		});
	};
});
</script>