<?php
/**
 * Create Community Event Content Template
 * For creating events within a specific community context
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

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

$community_manager = new PartyMinder_Community_Manager();
$current_user_id = get_current_user_id();

// Get community from URL parameter
$community_id = isset( $_GET['community_id'] ) ? intval( $_GET['community_id'] ) : 0;
if ( ! $community_id ) {
	wp_redirect( PartyMinder::get_communities_url() );
	exit;
}

// Get community and verify user is a member
$community = $community_manager->get_community( $community_id );
if ( ! $community ) {
	wp_redirect( PartyMinder::get_communities_url() );
	exit;
}

// Check if user is a member of the community
if ( ! $community_manager->is_member( $community_id, $current_user_id ) ) {
	wp_redirect( home_url( '/communities/' . $community->slug ) );
	exit;
}

// Check for event creation success
$event_created = false;
$form_errors   = array();

// Check if event was just created
if ( isset( $_GET['partyminder_created'] ) && $_GET['partyminder_created'] == '1' ) {
	$creation_data = get_transient( 'partyminder_community_event_created_' . get_current_user_id() );
	if ( $creation_data ) {
		$event_created = true;
		// Clear the transient
		delete_transient( 'partyminder_community_event_created_' . get_current_user_id() );
	}
}

// Check for form errors
$stored_errors = get_transient( 'partyminder_form_errors_' . get_current_user_id() );
if ( $stored_errors ) {
	$form_errors = $stored_errors;
	// Clear the transient
	delete_transient( 'partyminder_form_errors_' . get_current_user_id() );
}

// Set up template variables
$page_title       = sprintf( __( 'Create Event - %s', 'partyminder' ), esc_html( $community->name ) );
$page_description = sprintf( __( 'Create a new event for the %s community.', 'partyminder' ), esc_html( $community->name ) );

// Main content
ob_start();
?>

<!-- Secondary Menu Bar -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<a href="<?php echo home_url( '/communities/' . $community->slug . '/events' ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Back to Events', 'partyminder' ); ?>
		</a>
		<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Community Overview', 'partyminder' ); ?>
		</a>
		<a href="<?php echo PartyMinder::get_dashboard_url(); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Dashboard', 'partyminder' ); ?>
		</a>
	</div>
</div>

<?php if ( $event_created ) : ?>
	<!-- Success Message -->
	<div class="pm-section pm-mb-4">
		<div class="pm-alert pm-alert-success">
			<h4 class="pm-heading pm-heading-sm"><?php _e( 'Community Event Created!', 'partyminder' ); ?></h4>
			<p><?php _e( 'Your community event has been successfully created and is now live.', 'partyminder' ); ?></p>
			<div class="pm-mt-4">
				<a href="<?php echo home_url( '/communities/' . $community->slug . '/events' ); ?>" class="pm-btn pm-btn-secondary">
					<?php _e( 'View Community Events', 'partyminder' ); ?>
				</a>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ( ! empty( $form_errors ) ) : ?>
	<!-- Error Messages -->
	<div class="pm-section pm-mb-4">
		<div class="pm-alert pm-alert-error">
			<h4 class="pm-heading pm-heading-sm"><?php _e( 'Please fix the following errors:', 'partyminder' ); ?></h4>
			<ul>
				<?php foreach ( $form_errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
<?php endif; ?>

<!-- Community Context -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap pm-mb-4">
		<h2 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Creating Event For:', 'partyminder' ); ?></h2>
		<span class="pm-badge pm-badge-primary"><?php echo esc_html( $community->name ); ?></span>
	</div>
	<p class="pm-text-muted">
		<?php _e( 'This event will be associated with your community and visible to all community members.', 'partyminder' ); ?>
	</p>
</div>

<!-- Event Creation Form -->
<div class="pm-section">
	<form id="create-community-event-form" method="post" enctype="multipart/form-data" class="pm-form">
		<?php wp_nonce_field( 'create_partyminder_community_event', 'partyminder_community_event_nonce' ); ?>
		<input type="hidden" name="community_id" value="<?php echo esc_attr( $community_id ); ?>">
		
		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Event Details', 'partyminder' ); ?></h3>
			
			<div class="pm-form-group">
				<label for="event_title" class="pm-form-label"><?php _e( 'Event Title *', 'partyminder' ); ?></label>
				<input type="text" id="event_title" name="event_title" class="pm-form-input"
						value="<?php echo esc_attr( $_POST['event_title'] ?? '' ); ?>" 
						placeholder="<?php esc_attr_e( 'e.g., Community Game Night', 'partyminder' ); ?>" required />
			</div>

			<div class="pm-form-row">
				<!-- Event Date & Time Section -->
				<div class="pm-form-section">
					<h3 class="pm-heading pm-heading-sm pm-mb-4"><?php _e('When is your event?', 'partyminder'); ?></h3>
					
					<!-- All Day Toggle -->
					<div class="pm-form-group pm-mb-4">
						<label class="pm-form-label pm-flex pm-gap-2">
							<input type="checkbox" id="all_day" name="all_day" value="1" class="pm-form-checkbox">
							<?php _e('All day event', 'partyminder'); ?>
						</label>
					</div>
					
					<!-- Start Date & Time -->
					<div class="pm-form-group pm-grid pm-grid-2 pm-gap">
						<div>
							<label for="start_date" class="pm-form-label"><?php _e('Start Date *', 'partyminder'); ?></label>
							<input type="text" id="start_date" name="start_date" class="pm-form-input" 
								   value="<?php echo esc_attr( $_POST['start_date'] ?? '' ); ?>"
								   placeholder="<?php _e('Select start date...', 'partyminder'); ?>" required />
						</div>
						<div class="pm-time-field">
							<label for="start_time" class="pm-form-label"><?php _e('Start Time *', 'partyminder'); ?></label>
							<input type="text" id="start_time" name="start_time" class="pm-form-input" 
								   value="<?php echo esc_attr( $_POST['start_time'] ?? '' ); ?>"
								   placeholder="<?php _e('Select start time...', 'partyminder'); ?>" />
						</div>
					</div>
					
					<!-- End Date & Time -->
					<div class="pm-form-group pm-grid pm-grid-2 pm-gap">
						<div>
							<label for="end_date" class="pm-form-label"><?php _e('End Date', 'partyminder'); ?></label>
							<input type="text" id="end_date" name="end_date" class="pm-form-input" 
								   value="<?php echo esc_attr( $_POST['end_date'] ?? '' ); ?>"
								   placeholder="<?php _e('Select end date...', 'partyminder'); ?>" />
						</div>
						<div class="pm-time-field">
							<label for="end_time" class="pm-form-label"><?php _e('End Time', 'partyminder'); ?></label>
							<input type="text" id="end_time" name="end_time" class="pm-form-input" 
								   value="<?php echo esc_attr( $_POST['end_time'] ?? '' ); ?>"
								   placeholder="<?php _e('Select end time...', 'partyminder'); ?>" />
						</div>
					</div>
					
					<!-- Recurrence Options -->
					<div class="pm-form-group pm-mt-4">
						<label for="recurrence_type" class="pm-form-label"><?php _e('Repeat Event', 'partyminder'); ?></label>
						<select id="recurrence_type" name="recurrence_type" class="pm-form-input">
							<option value="none"><?php _e('Does not repeat', 'partyminder'); ?></option>
							<option value="daily"><?php _e('Daily', 'partyminder'); ?></option>
							<option value="weekly"><?php _e('Weekly', 'partyminder'); ?></option>
							<option value="monthly"><?php _e('Monthly', 'partyminder'); ?></option>
							<option value="yearly"><?php _e('Yearly', 'partyminder'); ?></option>
							<option value="custom"><?php _e('Custom...', 'partyminder'); ?></option>
						</select>
					</div>
					
					<!-- Recurrence Options -->
					<div class="pm-recurrence-options" style="display: none;">
						<div class="pm-recurrence-interval pm-form-group pm-mt-4">
							<label for="recurrence_interval" class="pm-form-label"><?php _e('Repeat every', 'partyminder'); ?></label>
							<input type="number" id="recurrence_interval" name="recurrence_interval" 
								   class="pm-form-input" min="1" value="1" />
						</div>
					</div>
				</div>
			</div>

			<div class="pm-form-group">
				<label for="event_description" class="pm-form-label"><?php _e( 'Event Description', 'partyminder' ); ?></label>
				<textarea id="event_description" name="event_description" class="pm-form-textarea" rows="4"
						placeholder="<?php esc_attr_e( 'Describe your community event...', 'partyminder' ); ?>"><?php echo esc_textarea( $_POST['event_description'] ?? '' ); ?></textarea>
			</div>
		</div>
		
		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Event Location', 'partyminder' ); ?></h3>
			
			<div class="pm-form-group">
				<label for="venue_info" class="pm-form-label"><?php _e( 'Venue Information', 'partyminder' ); ?></label>
				<input type="text" id="venue_info" name="venue_info" class="pm-form-input"
						value="<?php echo esc_attr( $_POST['venue_info'] ?? '' ); ?>" 
						placeholder="<?php esc_attr_e( 'e.g., Community Center, 123 Main St', 'partyminder' ); ?>" />
			</div>
		</div>

		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Event Settings', 'partyminder' ); ?></h3>
			
			<div class="pm-form-row">
				<div class="pm-form-group">
					<label for="guest_limit" class="pm-form-label"><?php _e( 'Guest Limit', 'partyminder' ); ?></label>
					<input type="number" id="guest_limit" name="guest_limit" class="pm-form-input" min="0"
							value="<?php echo esc_attr( $_POST['guest_limit'] ?? '' ); ?>" 
							placeholder="<?php esc_attr_e( 'Leave blank for unlimited', 'partyminder' ); ?>" />
				</div>
				<div class="pm-form-group">
					<label class="pm-form-label"><?php _e( 'Event Privacy', 'partyminder' ); ?></label>
					<div class="pm-form-info">
						<div class="pm-alert pm-alert-info">
							<p class="pm-mb-2">
								<strong><?php _e( 'Privacy Inheritance:', 'partyminder' ); ?></strong>
								<?php 
								printf( 
									__( 'This event will inherit the %s privacy setting from the community.', 'partyminder' ), 
									'<span class="pm-badge pm-badge-' . esc_attr( $community->privacy === 'public' ? 'success' : 'warning' ) . '">' . esc_html( ucfirst( $community->privacy ) ) . '</span>'
								); 
								?>
							</p>
							<p class="pm-text-muted pm-text-sm">
								<?php if ( $community->privacy === 'public' ) : ?>
									<?php _e( 'This event will be visible to everyone and discoverable in public listings.', 'partyminder' ); ?>
								<?php else : ?>
									<?php _e( 'This event will only be visible to community members.', 'partyminder' ); ?>
								<?php endif; ?>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Host Information', 'partyminder' ); ?></h3>
			
			<div class="pm-form-group">
				<label for="host_email" class="pm-form-label"><?php _e( 'Host Email *', 'partyminder' ); ?></label>
				<input type="email" id="host_email" name="host_email" class="pm-form-input"
						value="<?php echo esc_attr( $_POST['host_email'] ?? wp_get_current_user()->user_email ); ?>" required />
			</div>
			
			<div class="pm-form-group">
				<label for="host_notes" class="pm-form-label"><?php _e( 'Host Notes (Optional)', 'partyminder' ); ?></label>
				<textarea id="host_notes" name="host_notes" class="pm-form-textarea" rows="3"
						placeholder="<?php esc_attr_e( 'Additional information for guests...', 'partyminder' ); ?>"><?php echo esc_textarea( $_POST['host_notes'] ?? '' ); ?></textarea>
			</div>
		</div>

		<div class="pm-form-actions">
			<button type="submit" class="pm-btn pm-btn-lg">
				<?php _e( 'Create Community Event', 'partyminder' ); ?>
			</button>
			<a href="<?php echo home_url( '/communities/' . $community->slug . '/events' ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'Cancel', 'partyminder' ); ?>
			</a>
		</div>
	</form>
</div>

<?php
$content = ob_get_clean();

// Include form template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php';
?>

<script>
jQuery(document).ready(function($) {
	// Form submission via AJAX
	$('#create-community-event-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.text();
		
		// Disable submit button
		$submitBtn.text('<?php _e( 'Creating Event...', 'partyminder' ); ?>').prop('disabled', true);
		
		// Prepare form data
		const formData = new FormData(this);
		formData.append('action', 'partyminder_create_community_event');
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					// Redirect to success page
					window.location.href = '<?php echo add_query_arg( 'partyminder_created', '1', $_SERVER['REQUEST_URI'] ); ?>';
				} else {
					alert(response.data || '<?php _e( 'Error creating event. Please try again.', 'partyminder' ); ?>');
					$submitBtn.text(originalText).prop('disabled', false);
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
				$submitBtn.text(originalText).prop('disabled', false);
			}
		});
	});
});
</script>