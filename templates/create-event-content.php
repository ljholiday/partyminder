<?php
/**
 * Create Event Content Template - Theme Integrated
 * Content only version for theme integration via the_content filter
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if user is logged in - redirect to login if not
if ( ! is_user_logged_in() ) {
	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$login_url   = add_query_arg( 'redirect_to', urlencode( $current_url ), PartyMinder::get_login_url() );
	wp_redirect( $login_url );
	exit;
}

// Check for event creation success
$event_created = false;
$form_errors   = array();

// Check if event was just created
if ( isset( $_GET['partyminder_created'] ) && $_GET['partyminder_created'] == '1' ) {
	$creation_data = get_transient( 'partyminder_event_created_' . get_current_user_id() );
	if ( $creation_data ) {
		$event_created = true;
		// Clear the transient
		delete_transient( 'partyminder_event_created_' . get_current_user_id() );
	}
}

// Check for form errors
$stored_errors = get_transient( 'partyminder_form_errors_' . get_current_user_id() );
if ( $stored_errors ) {
	$form_errors = $stored_errors;
	// Clear the transient
	delete_transient( 'partyminder_form_errors_' . get_current_user_id() );
}

$primary_color   = get_option( 'partyminder_primary_color', '#667eea' );
$secondary_color = get_option( 'partyminder_secondary_color', '#764ba2' );
$button_style    = get_option( 'partyminder_button_style', 'rounded' );
$form_layout     = get_option( 'partyminder_form_layout', 'card' );

// Set up template variables
$page_title       = __( 'Create Your Event', 'partyminder' );
$page_description = __( 'Plan your perfect event and invite your guests.', 'partyminder' );
$breadcrumbs      = array(
	array(
		'title' => __( 'Dashboard', 'partyminder' ),
		'url'   => PartyMinder::get_dashboard_url(),
	),
	array(
		'title' => __( 'Events', 'partyminder' ),
		'url'   => PartyMinder::get_events_page_url(),
	),
	array( 'title' => __( 'Create Event', 'partyminder' ) ),
);

// Main content
ob_start();
?>

<?php if ( $event_created ) : ?>
	<!-- Success Message -->
	<div class="pm-alert pm-alert-success pm-mb-4">
		<h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e( ' Event Created Successfully!', 'partyminder' ); ?></h3>
		<p class="pm-mb-4"><?php _e( 'Your event has been created and is ready for guests to RSVP.', 'partyminder' ); ?></p>
		<div class="pm-flex pm-gap">
			<a href="<?php echo $creation_data['event_url']; ?>" class="pm-btn">
				<?php _e( 'View Event', 'partyminder' ); ?>
			</a>
			<a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-btn pm-btn">
				<?php _e( 'My Events', 'partyminder' ); ?>
			</a>
			<button type="button" onclick="navigator.share({title: 'Check out my event!', url: '<?php echo esc_js( $creation_data['event_url'] ); ?>'}) || navigator.clipboard.writeText('<?php echo esc_js( $creation_data['event_url'] ); ?>')" class="pm-btn pm-btn">
				<?php _e( 'Share Event', 'partyminder' ); ?>
			</button>
		</div>
	</div>
<?php else : ?>

	<!-- Event Creation Form -->
	<?php if ( ! empty( $form_errors ) ) : ?>
		<div class="pm-alert pm-alert-error pm-mb-4">
			<h4 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'Please fix the following issues:', 'partyminder' ); ?></h4>
			<ul>
				<?php foreach ( $form_errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	
	<form method="post" class="pm-form" id="partyminder-event-form" enctype="multipart/form-data">
		<?php wp_nonce_field( 'create_partyminder_event', 'partyminder_event_nonce' ); ?>
		
		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Event Details', 'partyminder' ); ?></h3>
			
			<div class="pm-form-group">
				<label for="event_title" class="pm-form-label"><?php _e( 'Event Title *', 'partyminder' ); ?></label>
				<input type="text" id="event_title" name="event_title" class="pm-form-input"
						value="<?php echo esc_attr( $_POST['event_title'] ?? '' ); ?>" 
						placeholder="<?php esc_attr_e( 'e.g., Summer Dinner Party', 'partyminder' ); ?>" required />
			</div>

<!-- Date form -->
            <div class="pm-form-row">
                <?php include PARTYMINDER_PLUGIN_DIR . 'templates/partials/date-picker.php'; ?>

				<div class="pm-form-group">
					<label for="guest_limit" class="pm-form-label"><?php _e( 'Guest Limit', 'partyminder' ); ?></label>
					<input type="number" id="guest_limit" name="guest_limit" class="pm-form-input"
							value="<?php echo esc_attr( $_POST['guest_limit'] ?? '10' ); ?>" 
							min="1" max="100" />
				</div>
			</div>

			<div class="pm-form-group">
				<label for="venue_info" class="pm-form-label"><?php _e( 'Venue/Location', 'partyminder' ); ?></label>
				<input type="text" id="venue_info" name="venue_info" class="pm-form-input"
						value="<?php echo esc_attr( $_POST['venue_info'] ?? '' ); ?>" 
						placeholder="<?php esc_attr_e( 'Where will your event take place?', 'partyminder' ); ?>" />
			</div>
		</div>

		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Event Description', 'partyminder' ); ?></h3>
			<div class="pm-form-group">
				<label for="event_description" class="pm-form-label"><?php _e( 'Tell guests about your event', 'partyminder' ); ?></label>
				<textarea id="event_description" name="event_description" rows="4" class="pm-form-textarea"
							placeholder="<?php esc_attr_e( 'Describe your event, what to expect, dress code...', 'partyminder' ); ?>"><?php echo esc_textarea( $_POST['event_description'] ?? '' ); ?></textarea>
			</div>
			
			<!-- Cover Image Upload -->
			<div class="pm-form-group">
				<label for="cover_image" class="pm-form-label"><?php _e( 'Cover Image', 'partyminder' ); ?></label>
				<input type="file" id="cover_image" name="cover_image" class="pm-form-input" accept="image/*">
				<p class="pm-form-help pm-text-muted"><?php printf( __( 'Optional: Upload a cover image for this event (%s)', 'partyminder' ), PartyMinder_Settings::get_file_size_description() ); ?></p>
				
				<!-- Upload Progress Bar -->
				<div id="create-event-upload-progress" class="pm-progress-container pm-mt-2" style="display: none;">
					<div class="pm-progress-bar">
						<div class="pm-progress-fill" style="width: 0%;"></div>
					</div>
					<div id="create-event-upload-message" class="pm-text-muted pm-mt-1"></div>
				</div>
			</div>
		</div>

		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Host Information', 'partyminder' ); ?></h3>
			
			<div class="pm-form-group">
				<label for="host_email" class="pm-form-label"><?php _e( 'Host Email *', 'partyminder' ); ?></label>
				<input type="email" id="host_email" name="host_email" class="pm-form-input"
						value="<?php echo esc_attr( $_POST['host_email'] ?? ( is_user_logged_in() ? wp_get_current_user()->user_email : '' ) ); ?>" 
						required />
			</div>

			<div class="pm-form-group">
				<label for="host_notes" class="pm-form-label"><?php _e( 'Special Notes for Guests', 'partyminder' ); ?></label>
				<textarea id="host_notes" name="host_notes" rows="3" class="pm-form-textarea"
							placeholder="<?php esc_attr_e( 'Any special instructions, parking info, what to bring...', 'partyminder' ); ?>"><?php echo esc_textarea( $_POST['host_notes'] ?? '' ); ?></textarea>
			</div>
			<div class="pm-form-group">
				<label for="privacy" class="pm-form-label"><?php _e( 'Event Privacy *', 'partyminder' ); ?></label>
				<select id="privacy" name="privacy" class="pm-form-input" required>
					<option value="public" <?php selected( $_POST['privacy'] ?? 'public', 'public' ); ?>>
						<?php _e( 'Public - Anyone can find and RSVP to this event', 'partyminder' ); ?>
					</option>
					<option value="private" <?php selected( $_POST['privacy'] ?? '', 'private' ); ?>>
						<?php _e( 'Private - Only invited guests can see and RSVP', 'partyminder' ); ?>
					</option>
				</select>
				<p class="pm-form-help pm-text-muted"><?php _e( 'Choose who can discover and join your event. You can always invite specific people regardless of privacy setting.', 'partyminder' ); ?></p>
			</div>
		</div>

		<!-- Invitation Section for Create Event -->
		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Ready to Invite Guests?', 'partyminder' ); ?></h3>
			
			<?php if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) : ?>
			<!-- Bluesky Connection Status -->
			<div id="create-bluesky-connection-section" class="pm-mb-4">
				<div id="create-bluesky-not-connected" class="pm-card pm-card-info" style="border-left: 4px solid #1d9bf0;">
					<div class="pm-card-body">
						<h5 class="pm-heading pm-heading-sm pm-mb-4">
							 <?php _e( 'Connect Bluesky for Easy Invites', 'partyminder' ); ?>
						</h5>
						<p class="pm-text-muted pm-mb-4">
							<?php _e( 'Connect your Bluesky account to quickly invite your contacts after creating the event.', 'partyminder' ); ?>
						</p>
						<button type="button" class="pm-btn pm-btn" id="create-connect-bluesky-btn">
							<?php _e( 'Connect Bluesky Account', 'partyminder' ); ?>
						</button>
					</div>
				</div>
				
				<div id="create-bluesky-connected" class="pm-card pm-card-success" style="border-left: 4px solid #10b981; display: none;">
					<div class="pm-card-body">
						<h5 class="pm-heading pm-heading-sm pm-mb-4">
							 <?php _e( 'Bluesky Connected', 'partyminder' ); ?>
						</h5>
						<p class="pm-text-muted pm-mb-4">
							<?php _e( 'Connected as', 'partyminder' ); ?> <strong id="create-bluesky-handle"></strong>
						</p>
						<div class="pm-flex pm-gap-2">
							<button type="button" class="pm-btn pm-btn-primary pm-btn-sm" id="create-invite-bluesky-btn">
								<?php _e( 'Invite from Bluesky', 'partyminder' ); ?>
							</button>
							<button type="button" class="pm-btn pm-btn-danger pm-btn-sm" id="create-disconnect-bluesky-btn">
								<?php _e( 'Disconnect', 'partyminder' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>
			
			<!-- Manual Email Preview -->
			<div class="pm-card">
				<div class="pm-card-body">
					<h5 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'Manual Email Invitations', 'partyminder' ); ?></h5>
					<p class="pm-text-muted pm-mb-4">
						<?php _e( 'After creating your event, you\'ll be able to send email invitations to specific guests.', 'partyminder' ); ?>
					</p>
					<div class="pm-form-group">
						<input type="email" class="pm-form-input" placeholder="<?php _e( 'Enter email addresses here...', 'partyminder' ); ?>" disabled>
					</div>
					<div class="pm-form-group">
						<textarea class="pm-form-textarea" rows="2" placeholder="<?php _e( 'Add a personal message...', 'partyminder' ); ?>" disabled></textarea>
					</div>
					<button type="button" class="pm-btn pm-btn" disabled>
						<?php _e( 'Available After Event Creation', 'partyminder' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="pm-flex pm-gap pm-mt-4">
			<button type="submit" name="partyminder_create_event" class="pm-btn pm-btn-lg">
				<?php _e( 'Create Event', 'partyminder' ); ?>
			</button>
			<a href="<?php echo PartyMinder::get_events_page_url(); ?>" class="pm-btn pm-btn">
				<?php _e( 'Back to Events', 'partyminder' ); ?>
			</a>
		</div>
	</form>

<?php endif; ?>
<?php
$content = ob_get_clean();

// Include form template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php';
?>

<script>
jQuery(document).ready(function($) {
	// Initialize Bluesky connection check on page load
	<?php if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) : ?>
	checkCreateBlueskyConnection();
	
	// Handle Bluesky buttons
	$('#create-connect-bluesky-btn').on('click', showCreateBlueskyConnectModal);
	$('#create-disconnect-bluesky-btn').on('click', disconnectCreateBluesky);
	
	// Debug the invite button
	console.log('Looking for invite button:', $('#create-invite-bluesky-btn').length);
	$('#create-invite-bluesky-btn').on('click', function() {
		console.log('Invite button clicked!');
		showBlueskyFollowersModal();
	});
	<?php endif; ?>
	
	$('#partyminder-event-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.html();
		
		// Disable submit button and show loading
		$submitBtn.prop('disabled', true).html('<span>⏳</span> <?php _e( 'Creating Event...', 'partyminder' ); ?>');
		
		// Check if we have file uploads
		const hasFiles = $form.find('input[type="file"]').get().some(input => input.files.length > 0);
		const $progress = $('#create-event-upload-progress');
		const $progressFill = $progress.find('.pm-progress-fill');
		const $message = $('#create-event-upload-message');
		
		if (hasFiles) {
			$progress.show();
			$message.empty();
		}
		
		// Prepare form data properly for file uploads
		const formData = new FormData(this);
		formData.append('action', 'partyminder_create_event');
		
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
					window.location.href = '<?php echo PartyMinder::get_create_event_url(); ?>?partyminder_created=1';
				} else {
					// Show error message
					$form.before('<div class="partyminder-errors" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 4px;"><h4><?php _e( 'Please fix the following issues:', 'partyminder' ); ?></h4><ul><li>' + (response.data || 'Unknown error occurred') + '</li></ul></div>');
					
					// Scroll to top to show error message
					$('html, body').animate({scrollTop: 0}, 500);
				}
			},
			error: function() {
				$form.before('<div class="partyminder-errors" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 4px;"><h4><?php _e( 'Error', 'partyminder' ); ?></h4><p><?php _e( 'Network error. Please try again.', 'partyminder' ); ?></p></div>');
				
				// Scroll to top to show error message
				$('html, body').animate({scrollTop: 0}, 500);
			},
			complete: function() {
				// Re-enable submit button
				$submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});
	
	// Initialize Flatpickr date and time pickers
	if (typeof flatpickr !== 'undefined') {
		// Initialize start date picker
		const startDatePicker = flatpickr('#start_date', {
			dateFormat: 'Y-m-d',
			minDate: 'today',
			defaultDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // Next week
			onChange: function(selectedDates, dateStr) {
				// Update end date minimum to start date
				if (selectedDates.length > 0 && endDatePicker) {
					endDatePicker.set('minDate', selectedDates[0]);
					if (!$('#end_date').val()) {
						endDatePicker.setDate(selectedDates[0]);
					}
				}
			}
		});

		// Initialize start time picker
		const startTimePicker = flatpickr('#start_time', {
			enableTime: true,
			noCalendar: true,
			dateFormat: 'H:i',
			defaultDate: '18:00',
			time_24hr: false
		});

		// Initialize end date picker
		const endDatePicker = flatpickr('#end_date', {
			dateFormat: 'Y-m-d',
			minDate: 'today'
		});

		// Initialize end time picker
		const endTimePicker = flatpickr('#end_time', {
			enableTime: true,
			noCalendar: true,
			dateFormat: 'H:i',
			defaultDate: '20:00',
			time_24hr: false
		});

		// Initialize recurrence end date picker
		const recurrenceEndPicker = flatpickr('#recurrence_end_date', {
			dateFormat: 'Y-m-d',
			minDate: 'today'
		});

		// All day event toggle
		$('#all_day').on('change', function() {
			const isAllDay = $(this).is(':checked');
			$('#start_time_group, #end_time_group').toggle(!isAllDay);
			$('#start_time, #end_time').prop('required', !isAllDay);
		});

		// Recurrence type handling
		$('#recurrence_type').on('change', function() {
			const recurrenceType = $(this).val();
			
			// Show/hide recurrence options
			$('#recurrence_end').toggle(recurrenceType !== '');
			$('#custom_recurrence').toggle(recurrenceType === 'custom');
			
			// Show monthly options when months selected in custom
			if (recurrenceType === 'monthly') {
				$('#monthly_options').show();
			} else {
				$('#monthly_options').hide();
			}
		});

		// Custom recurrence unit handling
		$('#recurrence_unit').on('change', function() {
			const unit = $(this).val();
			$('#monthly_options').toggle(unit === 'months');
		});
	}
	
	<?php if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) : ?>
	// Bluesky Integration Functions for Create Event Page
	function checkCreateBlueskyConnection() {
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_check_bluesky_connection',
				nonce: partyminder_ajax.at_protocol_nonce
			},
			success: function(response) {
				if (response.success && response.data.connected) {
					showCreateBlueskyConnected(response.data.handle);
				} else {
					showCreateBlueskyNotConnected();
				}
			},
			error: function() {
				showCreateBlueskyNotConnected();
			}
		});
	}
	
	function showCreateBlueskyConnected(handle) {
		$('#create-bluesky-not-connected').hide();
		$('#create-bluesky-connected').show();
		$('#create-bluesky-handle').text(handle);
	}
	
	function showCreateBlueskyNotConnected() {
		$('#create-bluesky-not-connected').show();
		$('#create-bluesky-connected').hide();
	}
	
	function showCreateBlueskyConnectModal() {
		const modal = $('#pm-bluesky-connect-modal');
		modal.show();
		$('body').addClass('pm-modal-open');
		
		// Focus on handle input
		setTimeout(() => {
			$('.pm-bluesky-handle').focus();
		}, 100);
		
		// Set up close button handler
		modal.find('.pm-modal-close').off('click').on('click', function() {
			modal.attr('aria-hidden', 'true').hide();
			$('body').removeClass('pm-modal-open');
		});
		
		// Set up form submission handler
		$('#pm-bluesky-connect-form').off('submit').on('submit', function(e) {
			e.preventDefault();
			
			const handle = $('.pm-bluesky-handle').val();
			const password = $('.pm-bluesky-password').val();
			const $submitBtn = $('.pm-bluesky-connect-submit');
			
			$submitBtn.prop('disabled', true).text('<?php _e( 'Connecting...', 'partyminder' ); ?>');
			
			$.ajax({
				url: partyminder_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'partyminder_connect_bluesky',
					handle: handle,
					password: password,
					nonce: partyminder_ajax.at_protocol_nonce
				},
				success: function(response) {
					if (response.success) {
						showCreateBlueskyConnected(response.handle);
						modal.hide();
						$('body').removeClass('pm-modal-open');
					} else {
						alert(response.message || '<?php _e( 'Connection failed. Please check your credentials.', 'partyminder' ); ?>');
					}
				},
				error: function() {
					alert('<?php _e( 'Connection failed. Please try again.', 'partyminder' ); ?>');
				},
				complete: function() {
					$submitBtn.prop('disabled', false).text('<?php _e( 'Connect Account', 'partyminder' ); ?>');
				}
			});
		});
	}
	
	function disconnectCreateBluesky() {
		if (!confirm('<?php _e( 'Are you sure you want to disconnect your Bluesky account?', 'partyminder' ); ?>')) {
			return;
		}
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_disconnect_bluesky',
				nonce: partyminder_ajax.at_protocol_nonce
			},
			success: function(response) {
				showCreateBlueskyNotConnected();
			}
		});
	}
	
	// Bluesky Followers Modal Functions
	function showBlueskyFollowersModal() {
		const modal = $('#pm-bluesky-followers-modal');
		console.log('Followers modal element:', modal.length);
		
		if (modal.length === 0) {
			alert('Followers modal not found in DOM');
			return;
		}
		
		modal.show();
		$('body').addClass('pm-modal-open');
		
		// Load followers
		loadBlueskyFollowers();
	}
	
	function loadBlueskyFollowers() {
		$('#pm-bluesky-followers-loading').show();
		$('#pm-bluesky-followers-list').hide();
		$('#pm-bluesky-followers-error').hide();
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_bluesky_contacts',
				nonce: partyminder_ajax.at_protocol_nonce
			},
			success: function(response) {
				$('#pm-bluesky-followers-loading').hide();
				
				if (response.success && response.contacts) {
					displayBlueskyFollowers(response.contacts);
					$('#pm-bluesky-followers-list').show();
				} else {
					showFollowersError(response.message || '<?php _e( 'Failed to load followers', 'partyminder' ); ?>');
				}
			},
			error: function() {
				$('#pm-bluesky-followers-loading').hide();
				showFollowersError('<?php _e( 'Network error loading followers', 'partyminder' ); ?>');
			}
		});
	}
	
	function displayBlueskyFollowers(contacts) {
		const container = $('#pm-followers-container');
		container.empty();
		
		if (contacts.length === 0) {
			container.html('<p class="pm-text-muted"><?php _e( 'No followers found', 'partyminder' ); ?></p>');
			return;
		}
		
		contacts.forEach(function(contact) {
			const followerHtml = `
				<div class="pm-follower-item pm-py-2 pm-border-b">
					<label class="pm-form-label pm-flex pm-items-center">
						<input type="checkbox" class="pm-form-checkbox pm-follower-checkbox" value="${contact.handle}" data-display-name="${contact.display_name || contact.handle}">
						<div class="pm-ml-3">
							<div class="pm-font-medium">${contact.display_name || contact.handle}</div>
							<div class="pm-text-sm pm-text-muted">@${contact.handle}</div>
						</div>
					</label>
				</div>
			`;
			container.append(followerHtml);
		});
		
		// Update send button state when checkboxes change
		$('.pm-follower-checkbox').on('change', updateSendButtonState);
		$('#pm-select-all-followers').on('change', function() {
			const isChecked = $(this).is(':checked');
			$('.pm-follower-checkbox').prop('checked', isChecked);
			updateSendButtonState();
		});
		
		updateSendButtonState();
	}
	
	function showFollowersError(message) {
		$('#pm-followers-error-message').text(message);
		$('#pm-bluesky-followers-error').show();
	}
	
	function updateSendButtonState() {
		const checkedCount = $('.pm-follower-checkbox:checked').length;
		$('#pm-send-followers-invites').prop('disabled', checkedCount === 0);
	}
	
	// Handle followers modal events
	$(document).ready(function() {
		// Close followers modal
		$('#pm-bluesky-followers-modal .pm-modal-close').on('click', function() {
			$('#pm-bluesky-followers-modal').hide();
			$('body').removeClass('pm-modal-open');
		});
		
		// Send invitations
		$('#pm-send-followers-invites').on('click', function() {
			const selectedFollowers = [];
			$('.pm-follower-checkbox:checked').each(function() {
				selectedFollowers.push({
					handle: $(this).val(),
					display_name: $(this).data('display-name')
				});
			});
			
			if (selectedFollowers.length > 0) {
				// TODO: Implement actual invitation sending
				alert('<?php _e( 'Invitation functionality will be implemented next', 'partyminder' ); ?>');
				// For now, just close the modal
				$('#pm-bluesky-followers-modal').hide();
				$('body').removeClass('pm-modal-open');
			}
		});
	});
	
	<?php endif; ?>
});
</script>
