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

<?php
// JavaScript is now loaded from assets/js/create-event.js via wp_enqueue_script in the main plugin file
// This keeps the template clean and follows WordPress best practices
?>
