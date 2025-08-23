<?php
/**
 * RSVP Landing Page Content Template
 * Anonymous guest RSVP flow for event invitations
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get RSVP token and response from URL parameters
$rsvp_token = get_query_var( 'rsvp_token' ) ?: sanitize_text_field( $_GET['token'] ?? '' );
$quick_response = sanitize_text_field( $_GET['response'] ?? '' );

if ( ! $rsvp_token ) {
	?>
	<div class="partyminder-error-content">
		<div class="pm-error-wrapper">
			<h3><?php _e( 'Invalid RSVP Link', 'partyminder' ); ?></h3>
			<p><?php _e( 'This RSVP link is invalid or has expired.', 'partyminder' ); ?></p>
			<a href="<?php echo home_url(); ?>" class="pm-btn">
				<?php _e( 'Back to Homepage', 'partyminder' ); ?>
			</a>
		</div>
	</div>
	<?php
	return;
}

// Load event manager and find the event
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
$event_manager = new PartyMinder_Event_Manager();

// Find event by invitation token (need to implement this method)
$event = $event_manager->get_event_by_rsvp_token( $rsvp_token );

if ( ! $event ) {
	?>
	<div class="partyminder-error-content">
		<div class="pm-error-wrapper">
			<h3><?php _e( 'Event Not Found', 'partyminder' ); ?></h3>
			<p><?php _e( 'This event no longer exists or the RSVP link has expired.', 'partyminder' ); ?></p>
			<a href="<?php echo home_url(); ?>" class="pm-btn">
				<?php _e( 'Back to Homepage', 'partyminder' ); ?>
			</a>
		</div>
	</div>
	<?php
	return;
}

// Check if already responded
global $wpdb;
$rsvps_table = $wpdb->prefix . 'partyminder_event_rsvps';
$existing_rsvp = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT * FROM $rsvps_table WHERE invitation_token = %s",
		$rsvp_token
	)
);

// Handle quick RSVP from email button
$quick_rsvp_processed = false;
if ( $quick_response && in_array( $quick_response, array( 'yes', 'no', 'maybe' ) ) && ! $existing_rsvp ) {
	// Process quick RSVP (will implement AJAX handler)
	$quick_rsvp_processed = true;
}

// Set up template variables
$page_title = sprintf( __( 'RSVP: %s', 'partyminder' ), esc_html( $event->title ) );
$page_description = __( 'Please confirm your attendance and share your preferences', 'partyminder' );

// Format event date
$event_date = date( 'l, F j, Y', strtotime( $event->event_date ) );
$event_time = date( 'g:i A', strtotime( $event->event_date ) );

// Main content
ob_start();
?>

<!-- Event Header -->
<div class="pm-section pm-mb-4">
	<?php if ( ! empty( $event->featured_image ) ) : ?>
		<div class="pm-event-cover pm-mb-4" style="position: relative; width: 100%; height: 200px; background-image: url('<?php echo esc_url( $event->featured_image ); ?>'); background-size: cover; background-position: center; border-radius: 8px;">
			<div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 20px; border-radius: 0 0 8px 8px;">
				<h1 class="pm-heading pm-heading-lg" style="color: white; margin: 0;"><?php echo esc_html( $event->title ); ?></h1>
			</div>
		</div>
	<?php else : ?>
		<h1 class="pm-heading pm-heading-lg pm-mb-4"><?php echo esc_html( $event->title ); ?></h1>
	<?php endif; ?>
	
	<div class="pm-event-details pm-mb-4">
		<div class="pm-flex pm-gap-4 pm-text-muted">
			<span><strong><?php _e( 'When:', 'partyminder' ); ?></strong> <?php echo $event_date; ?> at <?php echo $event_time; ?></span>
			<?php if ( $event->venue_info ) : ?>
				<span><strong><?php _e( 'Where:', 'partyminder' ); ?></strong> <?php echo esc_html( $event->venue_info ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( $event->description ) : ?>
			<div class="pm-mt-4">
				<p><?php echo wpautop( esc_html( $event->description ) ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php if ( $existing_rsvp ) : ?>
	<!-- Already Responded -->
	<div class="pm-section">
		<div class="pm-alert pm-alert-success">
			<h3><?php _e( 'Thanks for your RSVP!', 'partyminder' ); ?></h3>
			<p><?php printf( __( 'You\'ve already responded: %s', 'partyminder' ), '<strong>' . ucfirst( $existing_rsvp->status ) . '</strong>' ); ?></p>
			
			<div class="pm-mt-4">
				<button type="button" class="pm-btn pm-btn-secondary" onclick="document.getElementById('update-rsvp-form').style.display='block'; this.style.display='none';">
					<?php _e( 'Update My RSVP', 'partyminder' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Hidden Update Form -->
	<div id="update-rsvp-form" class="pm-section" style="display: none;">
<?php elseif ( $quick_rsvp_processed ) : ?>
	<!-- Quick RSVP Success -->
	<div class="pm-section">
		<div class="pm-alert pm-alert-success">
			<h3><?php _e( 'Quick RSVP Received!', 'partyminder' ); ?></h3>
			<p><?php printf( __( 'Thanks for letting us know you\'ll be %s.', 'partyminder' ), '<strong>' . $quick_response . '</strong>' ); ?></p>
			<p><?php _e( 'Want to share more details and save your preferences for future events?', 'partyminder' ); ?></p>
		</div>
	</div>

	<!-- Optional Full RSVP Form -->
	<div class="pm-section">
<?php else : ?>
	<!-- Initial RSVP Form -->
	<div class="pm-section">
<?php endif; ?>

	<form method="post" class="pm-form" id="rsvp-form">
		<input type="hidden" name="rsvp_token" value="<?php echo esc_attr( $rsvp_token ); ?>" />
		<input type="hidden" name="event_id" value="<?php echo esc_attr( $event->id ); ?>" />
		
		<!-- RSVP Response -->
		<div class="pm-form-group">
			<label class="pm-form-label"><?php _e( 'Will you be attending?', 'partyminder' ); ?></label>
			<div class="pm-radio-group">
				<label class="pm-radio-option">
					<input type="radio" name="rsvp_status" value="yes" <?php checked( $existing_rsvp ? $existing_rsvp->status : $quick_response, 'yes' ); ?> required>
					<span class="pm-radio-label"><?php _e( '✅ Yes, I\'ll be there!', 'partyminder' ); ?></span>
				</label>
				<label class="pm-radio-option">
					<input type="radio" name="rsvp_status" value="maybe" <?php checked( $existing_rsvp ? $existing_rsvp->status : $quick_response, 'maybe' ); ?>>
					<span class="pm-radio-label"><?php _e( '🤔 Maybe', 'partyminder' ); ?></span>
				</label>
				<label class="pm-radio-option">
					<input type="radio" name="rsvp_status" value="no" <?php checked( $existing_rsvp ? $existing_rsvp->status : $quick_response, 'no' ); ?>>
					<span class="pm-radio-label"><?php _e( '❌ Sorry, can\'t make it', 'partyminder' ); ?></span>
				</label>
			</div>
		</div>

		<div id="attending-details" style="<?php echo ( $existing_rsvp && $existing_rsvp->status === 'no' ) || $quick_response === 'no' ? 'display: none;' : ''; ?>">
			<!-- Guest Information -->
			<div class="pm-form-group">
				<label for="guest_name" class="pm-form-label"><?php _e( 'Your Name', 'partyminder' ); ?></label>
				<input type="text" id="guest_name" name="guest_name" class="pm-form-input" 
					   value="<?php echo $existing_rsvp ? esc_attr( $existing_rsvp->name ) : ''; ?>" 
					   placeholder="<?php _e( 'Enter your full name', 'partyminder' ); ?>" required>
			</div>

			<div class="pm-form-group">
				<label for="guest_email" class="pm-form-label"><?php _e( 'Email Address', 'partyminder' ); ?></label>
				<input type="email" id="guest_email" name="guest_email" class="pm-form-input" 
					   value="<?php echo $existing_rsvp ? esc_attr( $existing_rsvp->email ) : ''; ?>" 
					   placeholder="<?php _e( 'your.email@example.com', 'partyminder' ); ?>" required>
			</div>

			<!-- Dietary Restrictions -->
			<div class="pm-form-group">
				<label for="dietary_restrictions" class="pm-form-label"><?php _e( 'Dietary Restrictions', 'partyminder' ); ?></label>
				<textarea id="dietary_restrictions" name="dietary_restrictions" class="pm-form-textarea" rows="2" 
						  placeholder="<?php _e( 'Vegetarian, gluten-free, allergies, etc.', 'partyminder' ); ?>"><?php echo $existing_rsvp ? esc_textarea( $existing_rsvp->dietary_restrictions ) : ''; ?></textarea>
			</div>

			<!-- Plus One -->
			<div class="pm-form-group">
				<label class="pm-form-label">
					<input type="checkbox" name="plus_one" value="1" <?php checked( $existing_rsvp ? $existing_rsvp->plus_one : 0, 1 ); ?>>
					<?php _e( 'I\'m bringing a plus one', 'partyminder' ); ?>
				</label>
				<input type="text" name="plus_one_name" class="pm-form-input pm-mt-2" 
					   value="<?php echo $existing_rsvp ? esc_attr( $existing_rsvp->plus_one_name ) : ''; ?>"
					   placeholder="<?php _e( 'Plus one\'s name', 'partyminder' ); ?>" 
					   style="<?php echo ( ! $existing_rsvp || ! $existing_rsvp->plus_one ) ? 'display: none;' : ''; ?>" id="plus-one-name">
			</div>

			<!-- Notes -->
			<div class="pm-form-group">
				<label for="guest_notes" class="pm-form-label"><?php _e( 'Message to Host (Optional)', 'partyminder' ); ?></label>
				<textarea id="guest_notes" name="guest_notes" class="pm-form-textarea" rows="2" 
						  placeholder="<?php _e( 'Any questions or special requests?', 'partyminder' ); ?>"><?php echo $existing_rsvp ? esc_textarea( $existing_rsvp->notes ) : ''; ?></textarea>
			</div>
		</div>

		<div class="pm-form-actions">
			<button type="submit" name="submit_rsvp" class="pm-btn">
				<?php echo $existing_rsvp ? __( 'Update RSVP', 'partyminder' ) : __( 'Submit RSVP', 'partyminder' ); ?>
			</button>
		</div>
	</form>

	<?php if ( ! $existing_rsvp && ! $quick_rsvp_processed ) : ?>
		<!-- Account Creation Prompt -->
		<div class="pm-section pm-mt-4">
			<div class="pm-card pm-card-info">
				<div class="pm-card-body">
					<h4 class="pm-heading pm-heading-sm"><?php _e( 'Save Your Preferences', 'partyminder' ); ?></h4>
					<p class="pm-text-muted pm-mb-4"><?php _e( 'Want to save your dietary preferences and get invited to more events? Create a free PartyMinder account.', 'partyminder' ); ?></p>
					<label class="pm-form-label">
						<input type="checkbox" name="create_account" value="1" id="create-account-checkbox">
						<?php _e( 'Create a free account with my RSVP info', 'partyminder' ); ?>
					</label>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- PartyMinder Branding -->
<div class="pm-section pm-text-center pm-mt-4">
	<div class="pm-powered-by">
		<small class="pm-text-muted">
			<?php _e( 'Powered by', 'partyminder' ); ?> <a href="<?php echo home_url(); ?>" class="pm-text-primary"><strong>PartyMinder</strong></a>
		</small>
	</div>
</div>

<?php
$content = ob_get_clean();

// Include form template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php';
?>

<script>
jQuery(document).ready(function($) {
	// Show/hide attending details based on RSVP status
	$('input[name="rsvp_status"]').on('change', function() {
		const status = $(this).val();
		const $details = $('#attending-details');
		
		if (status === 'no') {
			$details.slideUp();
			// Clear required fields when not attending
			$details.find('input[required], textarea[required]').prop('required', false);
		} else {
			$details.slideDown();
			$details.find('#guest_name, #guest_email').prop('required', true);
		}
	});

	// Show/hide plus one name field
	$('input[name="plus_one"]').on('change', function() {
		const $plusOneName = $('#plus-one-name');
		if ($(this).is(':checked')) {
			$plusOneName.slideDown();
		} else {
			$plusOneName.slideUp().val('');
		}
	});

	// Handle form submission
	$('#rsvp-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.text();
		
		// Disable submit button
		$submitBtn.prop('disabled', true).text('<?php _e( 'Submitting...', 'partyminder' ); ?>');
		
		// Prepare form data
		const formData = new FormData(this);
		formData.append('action', 'partyminder_submit_rsvp');
		formData.append('nonce', partyminder_ajax.event_nonce);
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					// Show success message and redirect or update UI
					$form.html('<div class="pm-alert pm-alert-success"><h3><?php _e( "RSVP Submitted Successfully!", "partyminder" ); ?></h3><p>' + response.data.message + '</p></div>');
				} else {
					// Show error message
					$form.before('<div class="pm-alert pm-alert-error"><h4><?php _e( "Error", "partyminder" ); ?></h4><p>' + (response.data || '<?php _e( "Please try again.", "partyminder" ); ?>') + '</p></div>');
				}
			},
			error: function() {
				$form.before('<div class="pm-alert pm-alert-error"><h4><?php _e( "Network Error", "partyminder" ); ?></h4><p><?php _e( "Please check your connection and try again.", "partyminder" ); ?></p></div>');
			},
			complete: function() {
				// Re-enable submit button
				$submitBtn.prop('disabled', false).text(originalText);
			}
		});
	});
});
</script>