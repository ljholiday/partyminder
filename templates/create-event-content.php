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
			<a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'My Events', 'partyminder' ); ?>
			</a>
			<button type="button" onclick="navigator.share({title: 'Check out my event!', url: '<?php echo esc_js( $creation_data['event_url'] ); ?>'}) || navigator.clipboard.writeText('<?php echo esc_js( $creation_data['event_url'] ); ?>')" class="pm-btn pm-btn-secondary">
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

            <div class="pm-form-row">
                <?php include PARTYMINDER_PLUGIN_DIR . 'templates/partials/enhanced-date-picker.php'; ?>

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
				<p class="pm-form-help pm-text-muted"><?php _e( 'Optional: Upload a cover image for this event (JPG, PNG, max 5MB)', 'partyminder' ); ?></p>
				
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
						<button type="button" class="pm-btn pm-btn-secondary" id="create-connect-bluesky-btn">
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
						<button type="button" class="pm-btn pm-btn-danger pm-btn-sm" id="create-disconnect-bluesky-btn">
							<?php _e( 'Disconnect', 'partyminder' ); ?>
						</button>
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
					<button type="button" class="pm-btn pm-btn-secondary" disabled>
						<?php _e( 'Available After Event Creation', 'partyminder' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="pm-flex pm-gap pm-mt-4">
			<button type="submit" name="partyminder_create_event" class="pm-btn pm-btn-lg">
				<?php _e( 'Create Event', 'partyminder' ); ?>
			</button>
			<a href="<?php echo PartyMinder::get_events_page_url(); ?>" class="pm-btn pm-btn-secondary">
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
		const connectHtml = `
			<div id="create-bluesky-connect-modal" class="pm-modal-overlay" style="z-index: 10001;">
				<div class="pm-modal pm-modal-sm">
					<div class="pm-modal-header">
						<h3>🦋 <?php _e( 'Connect to Bluesky', 'partyminder' ); ?></h3>
						<button type="button" class="create-bluesky-connect-close pm-btn pm-btn-secondary" style="padding: 5px; border-radius: 50%; width: 35px; height: 35px;">×</button>
					</div>
					<div class="pm-modal-body">
						<form id="create-bluesky-connect-form">
							<div class="pm-form-group">
								<label class="pm-form-label"><?php _e( 'Bluesky Handle', 'partyminder' ); ?></label>
								<input type="text" class="pm-form-input" id="create-bluesky-handle-input" 
										placeholder="<?php _e( 'username.bsky.social', 'partyminder' ); ?>" required>
							</div>
							<div class="pm-form-group">
								<label class="pm-form-label"><?php _e( 'App Password', 'partyminder' ); ?></label>
								<input type="password" class="pm-form-input" id="create-bluesky-password-input" 
										placeholder="<?php _e( 'Your Bluesky app password', 'partyminder' ); ?>" required>
								<small class="pm-text-muted">
									<?php _e( 'Create an app password in your Bluesky settings for secure access.', 'partyminder' ); ?>
								</small>
							</div>
							<div class="pm-flex pm-gap pm-mt-4">
								<button type="submit" class="pm-btn">
									<?php _e( 'Connect Account', 'partyminder' ); ?>
								</button>
								<button type="button" class="create-bluesky-connect-close pm-btn pm-btn-secondary">
									<?php _e( 'Cancel', 'partyminder' ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		`;
		
		$('body').append(connectHtml);
		
		const $connectModal = $('#create-bluesky-connect-modal');
		$connectModal.addClass('active');
		
		// Close handlers
		$connectModal.find('.create-bluesky-connect-close').on('click', function() {
			$connectModal.remove();
		});
		
		// Form submission
		$('#create-bluesky-connect-form').on('submit', function(e) {
			e.preventDefault();
			
			const handle = $('#create-bluesky-handle-input').val();
			const password = $('#create-bluesky-password-input').val();
			const $submitBtn = $(this).find('button[type="submit"]');
			
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
						showCreateBlueskyConnected(response.data.handle);
						$connectModal.remove();
					} else {
						alert(response.data || '<?php _e( 'Connection failed. Please check your credentials.', 'partyminder' ); ?>');
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
	<?php endif; ?>
});
</script>
