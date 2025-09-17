<?php
/**
 * Manage Event Content Template
 * Event management interface with Settings - Guests - Invites - View Event navigation
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

// Helper function for cover image upload
function handle_event_cover_image_upload( $file, $event_id ) {
	// Validate file
	$validation_result = PartyMinder_Settings::validate_uploaded_file( $file );
	if ( is_wp_error( $validation_result ) ) {
		return $validation_result;
	}

	// Use WordPress built-in upload handling
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}

	$uploaded_file = wp_handle_upload( $file, array( 'test_form' => false ) );

	if ( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
		// Update event with the image URL
		global $wpdb;
		$events_table = $wpdb->prefix . 'partyminder_events';
		$wpdb->update(
			$events_table,
			array( 'featured_image' => $uploaded_file['url'] ),
			array( 'id' => $event_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $uploaded_file;
	} else {
		return new WP_Error( 'upload_failed', __( 'File upload failed.', 'partyminder' ) );
	}
}

// Get event ID from URL parameter
$event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : 0;
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';

if ( ! $event_id ) {
	$page_title      = __( 'Event Not Found', 'partyminder' );
	$main_content    = '<div class="pm-text-center pm-p-16"><h2>' . __( 'Event Not Found', 'partyminder' ) . '</h2><p>' . __( 'No event ID provided.', 'partyminder' ) . '</p><a href="' . esc_url( PartyMinder::get_my_events_url() ) . '" class="pm-btn">' . __( 'Back to My Events', 'partyminder' ) . '</a></div>';
	$sidebar_content = '';
	include PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
	return;
}

// Get event data
$event = $event_manager->get_event( $event_id );
if ( ! $event ) {
	$page_title      = __( 'Event Not Found', 'partyminder' );
	$main_content    = '<div class="pm-text-center pm-p-16"><h2>' . __( 'Event Not Found', 'partyminder' ) . '</h2><p>' . __( 'The requested event does not exist.', 'partyminder' ) . '</p><a href="' . esc_url( PartyMinder::get_my_events_url() ) . '" class="pm-btn">' . __( 'Back to My Events', 'partyminder' ) . '</a></div>';
	$sidebar_content = '';
	include PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
	return;
}

// Get current user and check permissions
$current_user = wp_get_current_user();
$can_manage = false;

if ( current_user_can( 'edit_posts' ) ||
	( is_user_logged_in() && $current_user->ID == $event->author_id ) ||
	( $current_user->user_email == $event->host_email ) ) {
	$can_manage = true;
}

// Check if user can manage this event
if ( ! $can_manage ) {
	$page_title      = __( 'Access Denied', 'partyminder' );
	$main_content    = '<div class="pm-text-center pm-p-16"><h2>' . __( 'Access Denied', 'partyminder' ) . '</h2><p>' . __( 'You do not have permission to manage this event.', 'partyminder' ) . '</p><a href="' . esc_url( home_url( '/events/' . $event->slug ) ) . '" class="pm-btn">' . __( 'View Event', 'partyminder' ) . '</a></div>';
	$sidebar_content = '';
	include PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
	return;
}

// Process form submissions
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['action'] ) ) {
	// Handle event settings update
	if ( $_POST['action'] === 'update_event_settings' && wp_verify_nonce( $_POST['nonce'], 'partyminder_event_management' ) ) {
		$update_data = array(
			'title'       => sanitize_text_field( $_POST['event_title'] ),
			'description' => sanitize_textarea_field( $_POST['event_description'] ),
			'venue_info'  => sanitize_text_field( $_POST['venue_info'] ),
			'host_email'  => sanitize_email( $_POST['host_email'] ),
			'host_notes'  => sanitize_textarea_field( $_POST['host_notes'] ),
			'privacy'     => sanitize_text_field( $_POST['privacy'] ),
			'guest_limit' => intval( $_POST['guest_limit'] ),
		);

		// Handle date/time fields
		if ( !empty( $_POST['start_date'] ) ) {
			$start_date = sanitize_text_field( $_POST['start_date'] );
			$start_time = sanitize_text_field( $_POST['start_time'] );
			$all_day = isset( $_POST['all_day'] );
			
			if ( $all_day ) {
				$update_data['event_date'] = $start_date . ' 00:00:00';
				$update_data['all_day'] = 1;
			} else {
				$update_data['event_date'] = $start_date . ' ' . $start_time . ':00';
				$update_data['all_day'] = 0;
			}
		}

		// Handle end date if provided
		if ( !empty( $_POST['end_date'] ) ) {
			$end_date = sanitize_text_field( $_POST['end_date'] );
			$end_time = isset( $_POST['all_day'] ) ? '23:59:59' : sanitize_text_field( $_POST['end_time'] ) . ':00';
			$update_data['end_date'] = $end_date . ' ' . $end_time;
		}

		// Handle cover image removal
		if ( isset( $_POST['remove_cover_image'] ) && $_POST['remove_cover_image'] == '1' ) {
			$update_data['featured_image'] = '';
		}

		// Handle cover image upload
		if ( isset( $_FILES['cover_image'] ) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK ) {
			$upload_result = handle_event_cover_image_upload( $_FILES['cover_image'], $event_id );
			if ( is_wp_error( $upload_result ) ) {
				$error_message = $upload_result->get_error_message();
			} else {
				$update_data['featured_image'] = $upload_result['url'];
			}
		}

		if ( ! isset( $error_message ) ) {
			$result = $event_manager->update_event( $event_id, $update_data );

			if ( ! is_wp_error( $result ) ) {
				$success_message = __( 'Event settings updated successfully.', 'partyminder' );
				// Refresh event data
				$event = $event_manager->get_event( $event_id );
			} else {
				$error_message = $result->get_error_message();
			}
		}
	}
	
	// Handle event deletion
	if ( $_POST['action'] === 'delete_event' && wp_verify_nonce( $_POST['nonce'], 'partyminder_event_management' ) ) {
		$confirm_name = sanitize_text_field( $_POST['confirm_name'] );
		
		if ( $confirm_name === $event->title ) {
			$result = $event_manager->delete_event( $event_id );
			
			if ( ! is_wp_error( $result ) ) {
				// Redirect to events page after successful deletion
				wp_redirect( PartyMinder::get_my_events_url() . '?deleted=1' );
				exit;
			} else {
				$error_message = $result->get_error_message();
			}
		} else {
			$error_message = __( 'Event name confirmation does not match. Event was not deleted.', 'partyminder' );
		}
	}
}

// Parse existing event date/time data for form fields
$event_start_date = date( 'Y-m-d', strtotime( $event->event_date ) );
$event_start_time = $event->all_day ? '' : date( 'H:i', strtotime( $event->event_date ) );
$event_end_date = $event->end_date ? date( 'Y-m-d', strtotime( $event->end_date ) ) : '';
$event_end_time = ($event->end_date && !$event->all_day) ? date( 'H:i', strtotime( $event->end_date ) ) : '';

// Set up template variables
$page_title       = sprintf( __( 'Manage %s', 'partyminder' ), esc_html( $event->title ) );
$page_description = __( 'Manage settings, guests, and invitations for your event', 'partyminder' );

// Breadcrumbs
$breadcrumbs = array(
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
	array( 'title' => __( 'Manage', 'partyminder' ) ),
);

// Main content
ob_start();
?>

<!-- Success/Error Messages -->
<?php if ( isset( $success_message ) ) : ?>
	<div class="pm-alert pm-alert-success">
		<?php echo esc_html( $success_message ); ?>
	</div>
<?php endif; ?>

<?php if ( isset( $error_message ) ) : ?>
	<div class="pm-alert pm-alert-error">
		<?php echo esc_html( $error_message ); ?>
	</div>
<?php endif; ?>

<!-- Four-Button Navigation -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<a href="?event_id=<?php echo $event_id; ?>&tab=settings" class="pm-btn <?php echo $current_tab === 'settings' ? '' : 'pm-btn'; ?>">
			<?php _e( 'Settings', 'partyminder' ); ?>
		</a>
		<a href="?event_id=<?php echo $event_id; ?>&tab=guests" class="pm-btn <?php echo $current_tab === 'guests' ? '' : 'pm-btn'; ?>">
			<?php _e( 'Guests', 'partyminder' ); ?>
		</a>
		<a href="?event_id=<?php echo $event_id; ?>&tab=invites" class="pm-btn <?php echo $current_tab === 'invites' ? '' : 'pm-btn'; ?>">
			<?php _e( 'Invites', 'partyminder' ); ?>
		</a>
		<a href="<?php echo esc_url( home_url( '/events/' . $event->slug ) ); ?>" class="pm-btn">
			<?php _e( 'View Event', 'partyminder' ); ?>
		</a>
	</div>
</div>

<!-- Tab Content -->
<?php if ( $current_tab === 'settings' ) : ?>
<div class="pm-section">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Event Settings', 'partyminder' ); ?></h2>
	</div>
	
	<form method="post" class="pm-form" id="manage-event-settings-form" enctype="multipart/form-data">
		<input type="hidden" name="action" value="update_event_settings">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'partyminder_event_management' ); ?>">
		
		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Event Details', 'partyminder' ); ?></h3>
			
			<div class="pm-form-group">
				<label for="event_title" class="pm-form-label"><?php _e( 'Event Title *', 'partyminder' ); ?></label>
				<input type="text" id="event_title" name="event_title" class="pm-form-input" 
						value="<?php echo esc_attr( $_POST['event_title'] ?? $event->title ); ?>" 
						placeholder="<?php esc_attr_e( 'e.g., Summer Dinner Party', 'partyminder' ); ?>" required />
			</div>

			<div class="pm-form-row">
				<?php 
				// Set variables for date picker partial
				$start_date = $_POST['start_date'] ?? $event_start_date;
				$start_time = $_POST['start_time'] ?? $event_start_time;
				$end_date = $_POST['end_date'] ?? $event_end_date;
				$end_time = $_POST['end_time'] ?? $event_end_time;
				$all_day = $_POST['all_day'] ?? $event->all_day;
				$recurrence_type = $_POST['recurrence_type'] ?? ($event->recurrence_type ?? 'none');
				$recurrence_interval = $_POST['recurrence_interval'] ?? ($event->recurrence_interval ?? 1);
				
				include PARTYMINDER_PLUGIN_DIR . 'templates/partials/date-picker.php'; 
				?>

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
				<p class="pm-form-help pm-text-muted"><?php printf( __( 'Optional: Upload a cover image for this event (%s)', 'partyminder' ), PartyMinder_Settings::get_file_size_description() ); ?></p>
				
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
		
		<button type="submit" class="pm-btn">
			<?php _e( 'Save Changes', 'partyminder' ); ?>
		</button>
	</form>
	
	<!-- Danger Zone -->
	<div class="pm-section pm-mt" style="border-top: 2px solid #dc3545; padding-top: 20px; margin-top: 40px;">
		<h4 class="pm-heading pm-heading-sm" style="color: #dc3545;"><?php _e( 'Danger Zone', 'partyminder' ); ?></h4>
		<p class="pm-text-muted pm-mb-4">
			<?php _e( 'Once you delete an event, there is no going back. This will permanently delete the event, all its guests, and conversations.', 'partyminder' ); ?>
		</p>
		
		<form method="post" class="pm-form" id="delete-event-form" onsubmit="return confirmEventDeletion(event)">
			<input type="hidden" name="action" value="delete_event">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'partyminder_event_management' ); ?>">
			
			<div class="pm-form-group">
				<label class="pm-form-label" style="color: #dc3545;">
					<?php printf( __( 'Type "%s" to confirm deletion:', 'partyminder' ), esc_html( $event->title ) ); ?>
				</label>
				<input type="text" name="confirm_name" class="pm-form-input" id="delete-confirm-name" 
						placeholder="<?php echo esc_attr( $event->title ); ?>" required>
			</div>
			
			<button type="submit" class="pm-btn" style="background-color: #dc3545; border-color: #dc3545;" disabled id="delete-event-btn">
				<?php _e( 'Delete Event Permanently', 'partyminder' ); ?>
			</button>
		</form>
	</div>
</div>

<?php elseif ( $current_tab === 'guests' ) : ?>
<div class="pm-section">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Event Guests', 'partyminder' ); ?></h2>
	</div>
	<div id="guests-list">
		<div class="pm-loading-placeholder">
			<p><?php _e( 'Loading event guests...', 'partyminder' ); ?></p>
		</div>
	</div>
</div>

<?php elseif ( $current_tab === 'invites' ) : ?>
<div class="pm-section">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Send Invitations', 'partyminder' ); ?></h2>
	</div>

	<!-- Copyable Invitation Links -->
	<div class="pm-card pm-mb-4">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-sm"><?php _e( 'Share Invitation Link', 'partyminder' ); ?></h3>
		</div>
		<div class="pm-card-body">
			<p class="pm-text-muted pm-mb-4">
				<?php _e( 'Copy and share this link via text, social media, Discord, Slack, or any other platform.', 'partyminder' ); ?>
			</p>

			<div class="pm-form-group pm-mb-4">
				<label class="pm-form-label"><?php _e( 'Event Invitation Link', 'partyminder' ); ?></label>
				<div class="pm-flex pm-gap-2">
					<input type="text" class="pm-form-input pm-flex-1" id="invitation-link"
						value="<?php echo esc_attr( add_query_arg( 'rsvp', '1', home_url( '/events/' . $event->slug ) ) ); ?>"
						readonly>
					<button type="button" class="pm-btn pm-btn pm-copy-invitation-link">
						<?php _e( 'Copy', 'partyminder' ); ?>
					</button>
				</div>
			</div>

			<div class="pm-form-group">
				<label class="pm-form-label"><?php _e( 'Custom Message (Optional)', 'partyminder' ); ?></label>
				<textarea class="pm-form-textarea" id="custom-message" rows="3"
					placeholder="<?php _e( 'Add a personal message to include when sharing...', 'partyminder' ); ?>"></textarea>
				<div class="pm-mt-2">
					<button type="button" class="pm-btn pm-btn pm-copy-invitation-with-message">
						<?php _e( 'Copy Link with Message', 'partyminder' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Email Invitation Form -->
	<form id="send-invitation-form" class="pm-form">
		<div class="pm-form-group">
			<label class="pm-form-label">
				<?php _e( 'Guest Email', 'partyminder' ); ?>
			</label>
			<input type="email" class="pm-form-input" id="invitation-email" 
					placeholder="<?php _e( 'Enter guest email address...', 'partyminder' ); ?>" required>
		</div>
		
		<div class="pm-form-group">
			<label class="pm-form-label">
				<?php _e( 'Personal Message (Optional)', 'partyminder' ); ?>
			</label>
			<textarea class="pm-form-textarea" id="invitation-message" rows="3"
						placeholder="<?php _e( 'Add a personal note to your invitation...', 'partyminder' ); ?>"></textarea>
		</div>
		
		<button type="submit" class="pm-btn">
			<?php _e( 'Send Invitation', 'partyminder' ); ?>
		</button>
	</form>

	<?php if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) : ?>
	<!-- Bluesky Invitations Section -->
	<div class="pm-mt">
		<h4><?php _e( 'Bluesky Invitations', 'partyminder' ); ?></h4>
		
		<div id="manage-bluesky-connection-section" class="pm-mb-4">
			<div id="manage-bluesky-not-connected" class="pm-card pm-card-info" style="border-left: 4px solid #1d9bf0;">
				<div class="pm-card-body">
					<h5 class="pm-heading pm-heading-sm pm-mb-4">
						<?php _e( 'Connect Bluesky for Easy Invites', 'partyminder' ); ?>
					</h5>
					<p class="pm-text-muted pm-mb-4">
						<?php _e( 'Connect your Bluesky account to invite your contacts to this event.', 'partyminder' ); ?>
					</p>
					<button type="button" class="pm-btn" id="manage-connect-bluesky-btn">
						<?php _e( 'Connect Bluesky Account', 'partyminder' ); ?>
					</button>
				</div>
			</div>
			
			<div id="manage-bluesky-connected" class="pm-card pm-card-success" style="border-left: 4px solid #10b981; display: none;">
				<div class="pm-card-body">
					<h5 class="pm-heading pm-heading-sm pm-mb-4">
						<?php _e( 'Bluesky Connected', 'partyminder' ); ?>
					</h5>
					<p class="pm-text-muted pm-mb-4">
						<?php _e( 'Connected as', 'partyminder' ); ?> <strong id="manage-bluesky-handle"></strong>
					</p>
					<div class="pm-flex pm-gap-2">
						<button type="button" class="pm-btn pm-btn-primary pm-btn-sm" id="create-invite-bluesky-btn">
							<?php _e( 'Invite from Bluesky', 'partyminder' ); ?>
						</button>
						<button type="button" class="pm-btn pm-btn-danger pm-btn-sm" id="manage-disconnect-bluesky-btn">
							<?php _e( 'Disconnect', 'partyminder' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>
	
	<div class="pm-mt">
		<h4><?php _e( 'Pending Invitations', 'partyminder' ); ?></h4>
		<div id="invitations-list">
			<div class="pm-loading-placeholder">
				<p><?php _e( 'Loading pending invitations...', 'partyminder' ); ?></p>
			</div>
		</div>
	</div>
</div>

<?php endif; ?>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<!-- Event Info -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php echo esc_html( $event->title ); ?></h3>
	</div>
	
	<?php if ( ! empty( $event->featured_image ) ) : ?>
		<div class="pm-mb-4">
			<img src="<?php echo esc_url( $event->featured_image ); ?>" alt="<?php echo esc_attr( $event->title ); ?>" style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px;">
		</div>
	<?php endif; ?>
	
	<?php if ( $event->description ) : ?>
		<p class="pm-text-muted pm-mb"><?php echo esc_html( wp_trim_words( $event->description, 15 ) ); ?></p>
	<?php endif; ?>
	
	<div class="pm-stat-list">
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Date', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo date( 'M j, Y', strtotime( $event->event_date ) ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Privacy', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo esc_html( ucfirst( $event->privacy ) ); ?></span>
		</div>
		<?php if ( $event->guest_limit ) : ?>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Guest Limit', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo $event->guest_limit; ?></span>
		</div>
		<?php endif; ?>
	</div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const eventId = <?php echo intval( $event_id ); ?>;
	const currentTab = '<?php echo esc_js( $current_tab ); ?>';
	
	// Load appropriate tab content based on current tab
	if (currentTab === 'guests') {
		loadEventGuests(eventId);
	} else if (currentTab === 'invites') {
		loadEventInvitations(eventId);
	}
	
	// Handle delete event form
	const deleteConfirmInput = document.getElementById('delete-confirm-name');
	const deleteBtn = document.getElementById('delete-event-btn');
	const eventTitle = '<?php echo esc_js( $event->title ); ?>';
	
	if (deleteConfirmInput && deleteBtn) {
		deleteConfirmInput.addEventListener('input', function() {
			deleteBtn.disabled = this.value !== eventTitle;
		});
	}
	
	
	// Load event guests
	function loadEventGuests(eventId) {
		const guestsList = document.getElementById('guests-list');
		if (!guestsList) return;
		
		guestsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'Loading event guests...', 'partyminder' ); ?></p></div>';
		
		jQuery.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_event_guests',
				event_id: eventId,
				nonce: partyminder_ajax.event_nonce
			},
			success: function(response) {
				if (response.success && response.data.guests_html) {
					guestsList.innerHTML = response.data.guests_html;
				} else {
					guestsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'No guests found.', 'partyminder' ); ?></p></div>';
				}
			},
			error: function() {
				guestsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'Error loading guests.', 'partyminder' ); ?></p></div>';
			}
		});
	}
	
	// Load event invitations
	function loadEventInvitations(eventId) {
		const invitationsList = document.getElementById('invitations-list');
		if (!invitationsList) return;
		
		invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'Loading pending invitations...', 'partyminder' ); ?></p></div>';
		
		jQuery.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_event_invitations',
				event_id: eventId,
				nonce: partyminder_ajax.event_nonce
			},
			success: function(response) {
				if (response.success && response.data.html) {
					invitationsList.innerHTML = response.data.html;
				} else {
					invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'No pending invitations.', 'partyminder' ); ?></p></div>';
				}
			},
			error: function() {
				invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'Error loading invitations.', 'partyminder' ); ?></p></div>';
			}
		});
	}
	
	// Event deletion confirmation
	window.confirmEventDeletion = function(event) {
		const eventTitle = '<?php echo esc_js( $event->title ); ?>';
		return confirm('<?php _e( 'Are you absolutely sure you want to delete', 'partyminder' ); ?> "' + eventTitle + '"?\n\n<?php _e( 'This action cannot be undone. All event data, guests, and conversations will be permanently deleted.', 'partyminder' ); ?>');
	};
});
</script>