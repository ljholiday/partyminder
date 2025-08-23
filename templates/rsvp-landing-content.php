<?php
/**
 * RSVP Landing Page Content Template
 * Anonymous guest RSVP conversion funnel for email invitations
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get RSVP token from URL
$rsvp_token = sanitize_text_field( $_GET['token'] ?? '' );
$quick_response = sanitize_text_field( $_GET['response'] ?? '' );

if ( ! $rsvp_token ) {
	?>
	<div class="pm-error-wrapper">
		<h3><?php _e( 'Invalid RSVP Link', 'partyminder' ); ?></h3>
		<p><?php _e( 'This RSVP link is invalid or has expired.', 'partyminder' ); ?></p>
		<a href="<?php echo home_url(); ?>" class="pm-btn">
			<?php _e( 'Back to Homepage', 'partyminder' ); ?>
		</a>
	</div>
	<?php
	return;
}

// Load guest manager and find the guest
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';

$guest_manager = new PartyMinder_Guest_Manager();
$event_manager = new PartyMinder_Event_Manager();

// Debug: Log the token for debugging
if ( WP_DEBUG ) {
	error_log( 'RSVP Debug - Token received: ' . $rsvp_token );
}

// Find guest by RSVP token
$guest = $guest_manager->get_guest_by_token( $rsvp_token );

// Debug: Log the guest lookup result
if ( WP_DEBUG ) {
	error_log( 'RSVP Debug - Guest found: ' . ( $guest ? 'YES (ID: ' . $guest->id . ')' : 'NO' ) );
}

if ( ! $guest ) {
	?>
	<div class="pm-error-wrapper">
		<h3><?php _e( 'RSVP Not Found', 'partyminder' ); ?></h3>
		<p><?php _e( 'This RSVP link is invalid or has expired.', 'partyminder' ); ?></p>
		<?php if ( WP_DEBUG ) : ?>
			<p style="color: #666; font-size: 12px;">Debug info: Token = '<?php echo esc_html( $rsvp_token ); ?>'</p>
		<?php endif; ?>
		<a href="<?php echo home_url(); ?>" class="pm-btn">
			<?php _e( 'Back to Homepage', 'partyminder' ); ?>
		</a>
	</div>
	<?php
	return;
}

// Get event details
$event = $event_manager->get_event( $guest->event_id );
if ( ! $event ) {
	?>
	<div class="pm-error-wrapper">
		<h3><?php _e( 'Event Not Found', 'partyminder' ); ?></h3>
		<p><?php _e( 'This event no longer exists.', 'partyminder' ); ?></p>
		<a href="<?php echo home_url(); ?>" class="pm-btn">
			<?php _e( 'Back to Homepage', 'partyminder' ); ?>
		</a>
	</div>
	<?php
	return;
}

// Handle quick RSVP from email button
$quick_rsvp_processed = false;
$rsvp_result = null;

if ( $quick_response && in_array( $quick_response, array( 'confirmed', 'declined', 'maybe' ) ) && $guest->status === 'pending' ) {
	$rsvp_result = $guest_manager->process_anonymous_rsvp( $rsvp_token, $quick_response );
	$quick_rsvp_processed = $rsvp_result['success'] ?? false;
	
	// Update guest object
	$guest = $guest_manager->get_guest_by_token( $rsvp_token );
}

// Set up template variables
$event_date = date( 'l, F j, Y', strtotime( $event->event_date ) );
$event_time = date( 'g:i A', strtotime( $event->event_date ) );

$page_title = sprintf( __( 'RSVP: %s', 'partyminder' ), esc_html( $event->title ) );
$page_description = __( 'Complete your RSVP and save your preferences', 'partyminder' );

// Breadcrumbs for navigation
$breadcrumbs = array(
	array( 'title' => $event->title )
);

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

<?php if ( $quick_rsvp_processed && $rsvp_result['success'] ) : ?>
	<!-- Quick RSVP Success -->
	<div class="pm-section pm-mb-4">
		<div class="pm-alert pm-alert-success">
			<h3><?php _e( 'Quick RSVP Received!', 'partyminder' ); ?></h3>
			<p><?php echo esc_html( $rsvp_result['message'] ); ?></p>
		</div>
	</div>

	<?php if ( $guest->status === 'confirmed' ) : ?>
		<!-- Full RSVP Form for attending guests -->
		<div class="pm-section pm-mb-4">
			<div class="pm-card">
				<div class="pm-card-body">
					<h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e( 'Complete Your RSVP', 'partyminder' ); ?></h3>
					<p class="pm-text-muted pm-mb-4"><?php _e( 'Help the host plan better by sharing your preferences:', 'partyminder' ); ?></p>
		<?php endif; ?>

<?php elseif ( $guest->status !== 'pending' ) : ?>
	<!-- Already Responded -->
	<div class="pm-section pm-mb-4">
		<div class="pm-alert pm-alert-info">
			<h3><?php _e( 'Thanks for your RSVP!', 'partyminder' ); ?></h3>
			<p><?php printf( __( 'You\'ve already responded: %s', 'partyminder' ), '<strong>' . ucfirst( $guest->status === 'confirmed' ? 'Yes' : ( $guest->status === 'declined' ? 'No' : 'Maybe' ) ) . '</strong>' ); ?></p>
		</div>
	</div>

	<?php if ( $guest->status === 'confirmed' ) : ?>
		<div class="pm-section pm-mb-4">
			<div class="pm-card">
				<div class="pm-card-body">
					<h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e( 'Update Your Preferences', 'partyminder' ); ?></h3>
	<?php endif; ?>

<?php else : ?>
	<!-- Initial RSVP Form -->
	<div class="pm-section pm-mb-4">
		<div class="pm-card">
			<div class="pm-card-body">
				<h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e( 'Please Respond', 'partyminder' ); ?></h3>
<?php endif; ?>

<?php if ( $guest->status === 'confirmed' || $guest->status === 'pending' ) : ?>
	<!-- RSVP Form -->
	<form method="post" class="pm-form" id="rsvp-form">
		<input type="hidden" name="rsvp_token" value="<?php echo esc_attr( $rsvp_token ); ?>" />
		<input type="hidden" name="event_id" value="<?php echo esc_attr( $event->id ); ?>" />
		
		<?php if ( $guest->status === 'pending' ) : ?>
		<!-- RSVP Response -->
		<div class="pm-form-group pm-mb-4">
			<label class="pm-form-label"><?php _e( 'Will you be attending?', 'partyminder' ); ?></label>
			<div class="pm-radio-group">
				<label class="pm-radio-option">
					<input type="radio" name="rsvp_status" value="confirmed" required>
					<span class="pm-radio-label"><?php _e( 'Yes, I\'ll be there!', 'partyminder' ); ?></span>
				</label>
				<label class="pm-radio-option">
					<input type="radio" name="rsvp_status" value="maybe">
					<span class="pm-radio-label"><?php _e( 'Maybe', 'partyminder' ); ?></span>
				</label>
				<label class="pm-radio-option">
					<input type="radio" name="rsvp_status" value="declined">
					<span class="pm-radio-label"><?php _e( 'Sorry, can\'t make it', 'partyminder' ); ?></span>
				</label>
			</div>
		</div>
		<?php endif; ?>

		<div id="attending-details" style="<?php echo ( $guest->status === 'declined' ) ? 'display: none;' : ''; ?>">
			<!-- Guest Information -->
			<div class="pm-form-group pm-mb-4">
				<label for="guest_name" class="pm-form-label"><?php _e( 'Your Name', 'partyminder' ); ?></label>
				<input type="text" id="guest_name" name="guest_name" class="pm-form-input" 
					   value="<?php echo esc_attr( $guest->name ); ?>" 
					   placeholder="<?php _e( 'Enter your full name', 'partyminder' ); ?>" required>
			</div>

			<!-- Dietary Restrictions -->
			<div class="pm-form-group pm-mb-4">
				<label for="dietary_restrictions" class="pm-form-label"><?php _e( 'Dietary Restrictions & Allergies', 'partyminder' ); ?></label>
				<textarea id="dietary_restrictions" name="dietary_restrictions" class="pm-form-textarea" rows="3" 
						  placeholder="<?php _e( 'Vegetarian, gluten-free, nut allergies, etc. This helps the host plan the menu.', 'partyminder' ); ?>"><?php echo esc_textarea( $guest->dietary_restrictions ); ?></textarea>
			</div>

			<!-- Plus One -->
			<div class="pm-form-group pm-mb-4">
				<label class="pm-form-label">
					<input type="checkbox" name="plus_one" value="1" <?php checked( $guest->plus_one, 1 ); ?>>
					<?php _e( 'I\'m bringing a plus one', 'partyminder' ); ?>
				</label>
				<input type="text" name="plus_one_name" class="pm-form-input pm-mt-2" 
					   value="<?php echo esc_attr( $guest->plus_one_name ); ?>"
					   placeholder="<?php _e( 'Plus one\'s name', 'partyminder' ); ?>" 
					   style="<?php echo ( ! $guest->plus_one ) ? 'display: none;' : ''; ?>" id="plus-one-name">
			</div>

			<!-- Notes -->
			<div class="pm-form-group pm-mb-4">
				<label for="guest_notes" class="pm-form-label"><?php _e( 'Message to Host (Optional)', 'partyminder' ); ?></label>
				<textarea id="guest_notes" name="guest_notes" class="pm-form-textarea" rows="2" 
						  placeholder="<?php _e( 'Any questions or special requests?', 'partyminder' ); ?>"><?php echo esc_textarea( $guest->notes ); ?></textarea>
			</div>
		</div>

		<div class="pm-form-actions pm-mb-4">
			<button type="submit" name="submit_rsvp" class="pm-btn">
				<?php _e( 'Update RSVP', 'partyminder' ); ?>
			</button>
		</div>
	</form>
				</div>
			</div>
		</div>

	<!-- Account Creation Prompt (Growth Engine) -->
	<?php if ( ! $guest->converted_user_id ) : ?>
		<div class="pm-section pm-mb-4">
			<div class="pm-card pm-card-accent">
				<div class="pm-card-body pm-text-center">
					<h4 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'Save Your Preferences for Next Time', 'partyminder' ); ?></h4>
					<p class="pm-text-muted pm-mb-4"><?php _e( 'Want to save your dietary preferences and get invited to more events? Create a free PartyMinder account and never fill this out again.', 'partyminder' ); ?></p>
					
					<div class="pm-form-group pm-mb-4">
						<label class="pm-form-label">
							<input type="checkbox" name="create_account" value="1" id="create-account-checkbox">
							<?php _e( 'Create a free account with my RSVP info', 'partyminder' ); ?>
						</label>
						<div class="pm-text-muted pm-mt-2" style="font-size: 0.9em;">
							<?php _e( 'We\'ll create your account automatically - no password needed right now!', 'partyminder' ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

<?php else : ?>
	<!-- Declined - Show social proof -->
	<div class="pm-section pm-mb-4">
		<div class="pm-card">
			<div class="pm-card-body pm-text-center">
				<h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e( 'Sorry you can\'t make it!', 'partyminder' ); ?></h3>
				<p class="pm-text-muted pm-mb-4"><?php _e( 'We\'ll miss you at this event, but we hope to see you at the next one.', 'partyminder' ); ?></p>
				
				<!-- Growth Engine for declined guests -->
				<div class="pm-mb-4">
					<h4 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'Get Notified of Future Events', 'partyminder' ); ?></h4>
					<p class="pm-text-muted pm-mb-4"><?php _e( 'Join PartyMinder to discover events in your area and never miss out again.', 'partyminder' ); ?></p>
					
					<label class="pm-form-label">
						<input type="checkbox" name="create_account" value="1">
						<?php _e( 'Create a free account to get future invitations', 'partyminder' ); ?>
					</label>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<!-- PartyMinder Branding (Growth Engine) -->
<div class="pm-section pm-text-center">
	<div class="pm-powered-by">
		<hr class="pm-mb-4">
		<small class="pm-text-muted">
			<?php _e( 'Powered by', 'partyminder' ); ?> <a href="<?php echo home_url(); ?>" class="pm-text-primary"><strong>PartyMinder</strong></a> - 
			<?php _e( 'The easiest way to plan events and bring people together', 'partyminder' ); ?>
		</small>
		<div class="pm-mt-2">
			<a href="<?php echo home_url(); ?>" class="pm-btn pm-btn-secondary pm-btn-sm">
				<?php _e( 'Start Planning Your Own Event', 'partyminder' ); ?>
			</a>
		</div>
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
		
		if (status === 'declined') {
			$details.slideUp();
			$details.find('input[required], textarea[required]').prop('required', false);
		} else {
			$details.slideDown();
			$details.find('#guest_name').prop('required', true);
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
		formData.append('action', 'partyminder_process_rsvp_landing');
		formData.append('nonce', partyminder_ajax.event_nonce);
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					// Show success and optionally redirect
					$form.html('<div class="pm-alert pm-alert-success"><h3><?php _e( "RSVP Updated Successfully!", "partyminder" ); ?></h3><p>' + response.data.message + '</p></div>');
					
					if (response.data.account_created) {
						setTimeout(function() {
							window.location.reload();
						}, 2000);
					}
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