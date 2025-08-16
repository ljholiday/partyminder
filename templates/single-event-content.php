<?php
/**
 * Single Event Content Template
 * Single event page
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get event data from global variable set by main plugin
$event = $GLOBALS['partyminder_current_event'] ?? null;

if ( ! $event ) {
	echo '<p>Event not found.</p>';
	return;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';

$event_manager        = new PartyMinder_Event_Manager();
$guest_manager        = new PartyMinder_Guest_Manager();
$conversation_manager = new PartyMinder_Conversation_Manager();

// Get additional event data
$event->guest_stats  = $event_manager->get_guest_stats( $event->id );
$event_conversations = $conversation_manager->get_event_conversations( $event->id );

$event_date  = new DateTime( $event->event_date );
$is_today    = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d' );
$is_tomorrow = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d', strtotime( '+1 day' ) );
$is_past     = $event_date < new DateTime();

// Check if user is event host
$current_user  = wp_get_current_user();
$is_event_host = ( is_user_logged_in() && $current_user->ID == $event->author_id ) ||
				( $current_user->user_email == $event->host_email ) ||
				current_user_can( 'edit_others_posts' );

// Set up template variables
$page_title       = esc_html( $event->title );
$page_description = '';

// Main content
ob_start();
?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<div class="pm-flex pm-flex-between">
				<div>
					<?php if ( $is_past ) : ?>
						<div class="pm-badge pm-badge-secondary pm-mb-2">Past Event</div>
					<?php elseif ( $is_today ) : ?>
						<div class="pm-badge pm-badge-success pm-mb-2">Today</div>
					<?php elseif ( $is_tomorrow ) : ?>
						<div class="pm-badge pm-badge-warning pm-mb-2">Tomorrow</div>
					<?php endif; ?>
				</div>
				<div>
					<?php if ( $event->privacy === 'private' ) : ?>
						<div class="pm-badge pm-badge-danger pm-mb-2">Private Event</div>
					<?php else : ?>
						<div class="pm-badge pm-badge-primary pm-mb-2">Public Event</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<div class="pm-card-body">
			<div class="pm-grid pm-grid-2 pm-gap pm-mb-4">
				<div class="pm-flex pm-gap">
					<strong>Date:</strong>
					<span>
						<?php if ( $is_today ) : ?>
							Today
						<?php elseif ( $is_tomorrow ) : ?>
							Tomorrow
						<?php else : ?>
							<?php echo $event_date->format( 'l, F j, Y' ); ?>
						<?php endif; ?>
					</span>
				</div>
				
				<div class="pm-flex pm-gap">
					<strong>Time:</strong>
					<span><?php echo $event_date->format( 'g:i A' ); ?></span>
				</div>
				
				<?php if ( $event->venue_info ) : ?>
				<div class="pm-flex pm-gap">
					<strong>Location:</strong>
					<span><?php echo esc_html( $event->venue_info ); ?></span>
				</div>
				<?php endif; ?>
				
				<div class="pm-flex pm-gap">
					<strong>Guests:</strong>
					<span>
						<?php echo $event->guest_stats->confirmed ?? 0; ?> confirmed
						<?php if ( $event->guest_limit > 0 ) : ?>
							of <?php echo $event->guest_limit; ?> max
						<?php endif; ?>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if ( $event->featured_image ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<img src="<?php echo esc_url( $event->featured_image ); ?>" alt="<?php echo esc_attr( $event->title ); ?>" style="width: 100%; height: auto;">
	</div>
</div>
<?php endif; ?>

<?php
// Get uploaded event photos
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-image-upload.php';
$event_photos = array();
if ( class_exists( 'PartyMinder_Image_Upload' ) ) {
	$event_photos = PartyMinder_Image_Upload::get_event_photos( $event->id );
}
if ( ! empty( $event_photos ) ) :
?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">Event Photos</h3>
		</div>
		<div class="pm-card-body">
			<div class="pm-grid pm-grid-3 pm-gap">
				<?php foreach ( $event_photos as $photo ) : ?>
					<div class="pm-photo-item">
						<img src="<?php echo esc_url( $photo['thumb_url'] ); ?>" 
							 alt="Event photo" 
							 style="width: 100%; height: auto; border-radius: 0.375rem;">
						<div class="pm-text-muted pm-text-center" style="font-size: 12px; margin-top: 0.25rem;">
							<?php echo human_time_diff( $photo['uploaded'], current_time( 'timestamp' ) ) . ' ago'; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if ( $event->description ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">About This Event</h3>
		</div>
		<div class="pm-card-body">
			<?php echo wpautop( esc_html( $event->description ) ); ?>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if ( $event->host_notes ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">Host Notes</h3>
		</div>
		<div class="pm-card-body">
			<?php echo wpautop( esc_html( $event->host_notes ) ); ?>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if ( $is_event_host && ! $is_past ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">Upload Event Photo</h3>
		</div>
		<div class="pm-card-body">
			<form id="event-photo-upload-form" enctype="multipart/form-data">
				<div class="pm-form-group">
					<label class="pm-form-label">Choose Photo</label>
					<input type="file" name="event_photo" accept="image/*" class="pm-form-input" required>
					<div class="pm-form-help">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF, WebP</div>
				</div>
				<button type="submit" class="pm-btn pm-btn-sm">Upload Photo</button>
			</form>
			
			<div id="event-photo-progress" style="display: none;">
				<div class="pm-progress-bar">
					<div class="pm-progress-fill"></div>
				</div>
				<div class="pm-progress-text">Uploading...</div>
			</div>
			
			<div id="event-photo-message"></div>
		</div>
	</div>
</div>

<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">Invite Guests</h3>
		</div>
		<div class="pm-card-body">
			<!-- Email Invitation Form -->
			<form id="send-event-invitation-form" class="pm-form pm-mb-4">
				<div class="pm-form-group">
					<label class="pm-form-label">
						<?php _e( 'Guest Email', 'partyminder' ); ?>
					</label>
					<input type="email" class="pm-form-input" id="event-invitation-email" 
							placeholder="<?php _e( 'Enter guest email address...', 'partyminder' ); ?>" required>
				</div>
				
				<div class="pm-form-group">
					<label class="pm-form-label">
						<?php _e( 'Personal Message (Optional)', 'partyminder' ); ?>
					</label>
					<textarea class="pm-form-textarea" id="event-invitation-message" rows="3"
								placeholder="<?php _e( 'Add a personal note to your invitation...', 'partyminder' ); ?>"></textarea>
				</div>
				
				<button type="submit" class="pm-btn pm-btn-sm">
					<?php _e( 'Send Invitation', 'partyminder' ); ?>
				</button>
			</form>
			
			<div>
				<h4 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'Pending Invitations', 'partyminder' ); ?></h4>
				<div id="event-invitations-list">
					<div class="pm-text-center pm-text-muted">
						<?php _e( 'Loading invitations...', 'partyminder' ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<div class="pm-section">
	<div class="pm-card">
		<div class="pm-card-header">
			<div class="pm-flex pm-flex-between">
				<h3 class="pm-heading pm-heading-md">Event Conversations</h3>
				<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo add_query_arg( 'event_id', $event->id, PartyMinder::get_create_conversation_url() ); ?>" class="pm-btn pm-btn-sm">
					Create Conversation
				</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="pm-card-body">
			<?php if ( ! empty( $event_conversations ) ) : ?>
				<?php foreach ( $event_conversations as $conversation ) : ?>
					<div class="pm-mb-4">
						<div class="pm-flex pm-flex-between pm-mb-2">
							<h4 class="pm-heading pm-heading-sm">
								<a href="<?php echo home_url( '/conversations/' . ( $conversation->topic_slug ?? 'general' ) . '/' . $conversation->slug ); ?>" class="pm-text-primary">
									<?php echo esc_html( $conversation->title ); ?>
								</a>
							</h4>
							<div class="pm-stat pm-text-center">
								<div class="pm-stat-number pm-text-primary"><?php echo $conversation->reply_count ?? 0; ?></div>
								<div class="pm-stat-label">Replies</div>
							</div>
						</div>
						<div class="pm-text-muted pm-mb-2">
							<?php
							$content_preview = wp_trim_words( strip_tags( $conversation->content ), 15, '...' );
							echo esc_html( $content_preview );
							?>
						</div>
						<div class="pm-text-muted">
							by <?php echo esc_html( $conversation->author_name ); ?> • <?php echo human_time_diff( strtotime( $conversation->last_reply_date ), current_time( 'timestamp' ) ); ?> ago
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="pm-text-center pm-p-4">
					<p class="pm-text-muted">No conversations started yet for this event.</p>
					<p class="pm-text-muted">Be the first to start planning and discussing ideas!</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<!-- Quick Actions (No Heading) -->
<div class="pm-card pm-mb-4">
	<div class="pm-card-body">
		<div class="pm-flex pm-flex-column pm-gap-4">
			<?php if ( ! $is_past ) : ?>
				<?php $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit; ?>
				<?php if ( $is_event_host ) : ?>
					<a href="<?php echo PartyMinder::get_edit_event_url( $event->id ); ?>" class="pm-btn">
						<?php _e( 'Edit Event', 'partyminder' ); ?>
					</a>
				<?php else : ?>
					<a href="#rsvp" class="pm-btn">
						<?php if ( $is_full ) : ?>
							<?php _e( 'Join Waitlist', 'partyminder' ); ?>
						<?php else : ?>
							<?php _e( 'RSVP Now', 'partyminder' ); ?>
						<?php endif; ?>
					</a>
				<?php endif; ?>
				
				<button type="button" class="pm-btn pm-btn-secondary" onclick="shareEvent()">
					<?php _e( 'Share Event', 'partyminder' ); ?>
				</button>
				
				<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo add_query_arg( 'event_id', $event->id, PartyMinder::get_create_conversation_url() ); ?>" class="pm-btn pm-btn-secondary">
					<?php _e( 'Create Conversation', 'partyminder' ); ?>
				</a>
				<?php endif; ?>
			<?php else : ?>
				<button type="button" class="pm-btn pm-btn-secondary" onclick="shareEvent()">
					<?php _e( 'Share Event', 'partyminder' ); ?>
				</button>
			<?php endif; ?>
			
			<a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( '← All Events', 'partyminder' ); ?>
			</a>
		</div>
	</div>
</div>

<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">Event Details</h3>
		</div>
		<div class="pm-card-body">
			<div class="pm-flex pm-flex-column pm-gap">
				<div>
					<strong class="pm-text-primary">Host:</strong><br>
					<span class="pm-text-muted"><?php echo esc_html( $event->host_email ); ?></span>
				</div>
				<div>
					<strong class="pm-text-primary">Created:</strong><br>
					<span class="pm-text-muted"><?php echo date( 'F j, Y', strtotime( $event->created_at ) ); ?></span>
				</div>
				<?php if ( $event->guest_limit > 0 ) : ?>
				<div>
					<strong class="pm-text-primary">Guest Limit:</strong><br>
					<span class="pm-text-muted"><?php echo $event->guest_limit; ?> people</span>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<?php if ( ! $is_past && ! $is_event_host ) : ?>
<div class="pm-section pm-mb" id="rsvp">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">RSVP for this Event</h3>
		</div>
		<div class="pm-card-body">
			<?php echo do_shortcode( '[partyminder_rsvp_form event_id="' . $event->id . '"]' ); ?>
		</div>
	</div>
</div>
<?php endif; ?>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
jQuery(document).ready(function($) {
	// Handle event photo upload
	$('#event-photo-upload-form').on('submit', function(e) {
		e.preventDefault();
		
		const formData = new FormData();
		const fileInput = $(this).find('input[type="file"]')[0];
		
		if (!fileInput.files[0]) {
			alert('Please select a photo to upload.');
			return;
		}
		
		formData.append('action', 'partyminder_event_photo_upload');
		formData.append('event_id', <?php echo $event->id; ?>);
		formData.append('event_photo', fileInput.files[0]);
		formData.append('nonce', '<?php echo wp_create_nonce( 'partyminder_event_photo_upload' ); ?>');
		
		const $form = $(this);
		const $progress = $('#event-photo-progress');
		const $progressFill = $('.pm-progress-fill');
		const $message = $('#event-photo-message');
		
		$form.hide();
		$progress.show();
		$message.empty();
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			xhr: function() {
				const xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener('progress', function(evt) {
					if (evt.lengthComputable) {
						const percentComplete = (evt.loaded / evt.total) * 100;
						$progressFill.css('width', percentComplete + '%');
					}
				}, false);
				return xhr;
			},
			success: function(response) {
				if (response.success) {
					$message.html('<div class="pm-upload-message success">' + response.data.message + '</div>');
					$form[0].reset();
					// Refresh page after 2 seconds to show the uploaded photo
					setTimeout(function() {
						location.reload();
					}, 2000);
				} else {
					$message.html('<div class="pm-upload-message error">' + (response.data || 'Upload failed') + '</div>');
				}
			},
			error: function() {
				$message.html('<div class="pm-upload-message error">Network error. Please try again.</div>');
			},
			complete: function() {
				$progress.hide();
				$form.show();
				setTimeout(function() {
					$message.empty();
				}, 5000);
			}
		});
	});
	
	// Load pending invitations on page load
	if ($('#event-invitations-list').length > 0) {
		loadEventInvitations();
	}
	
	// Handle invitation form submission
	$('#send-event-invitation-form').on('submit', function(e) {
		e.preventDefault();
		
		const email = $('#event-invitation-email').val().trim();
		const message = $('#event-invitation-message').val().trim();
		
		if (!email) {
			alert('<?php _e( 'Please enter an email address.', 'partyminder' ); ?>');
			return;
		}
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.text();
		
		// Disable form and show loading
		$submitBtn.prop('disabled', true).text('<?php _e( 'Sending...', 'partyminder' ); ?>');
		$form.find('input, textarea').prop('disabled', true);
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: {
				action: 'partyminder_send_event_invitation',
				event_id: <?php echo $event->id; ?>,
				email: email,
				message: message,
				nonce: '<?php echo wp_create_nonce( 'partyminder_event_action' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					// Clear form
					$form[0].reset();
					
					// Reload invitations list
					loadEventInvitations();
					
					// Show success message briefly
					const $successMsg = $('<div class="pm-alert pm-alert-success pm-mb-4">' + 
						'<?php _e( 'Invitation sent successfully!', 'partyminder' ); ?>' + 
						'</div>');
					$form.before($successMsg);
					setTimeout(() => $successMsg.fadeOut(() => $successMsg.remove()), 3000);
				} else {
					alert(response.data || '<?php _e( 'Failed to send invitation.', 'partyminder' ); ?>');
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
			},
			complete: function() {
				// Re-enable form
				$submitBtn.prop('disabled', false).text(originalText);
				$form.find('input, textarea').prop('disabled', false);
			}
		});
	});
	
	// Handle invitation cancellation
	$(document).on('click', '.cancel-event-invitation', function() {
		if (!confirm('<?php _e( 'Are you sure you want to cancel this invitation?', 'partyminder' ); ?>')) {
			return;
		}
		
		const invitationId = $(this).data('invitation-id');
		const $button = $(this);
		const originalText = $button.text();
		
		$button.prop('disabled', true).text('<?php _e( 'Cancelling...', 'partyminder' ); ?>');
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: {
				action: 'partyminder_cancel_event_invitation',
				event_id: <?php echo $event->id; ?>,
				invitation_id: invitationId,
				nonce: '<?php echo wp_create_nonce( 'partyminder_event_action' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					// Reload invitations list
					loadEventInvitations();
				} else {
					alert(response.data || '<?php _e( 'Failed to cancel invitation.', 'partyminder' ); ?>');
					$button.prop('disabled', false).text(originalText);
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
				$button.prop('disabled', false).text(originalText);
			}
		});
	});
	
	function loadEventInvitations() {
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: {
				action: 'partyminder_get_event_invitations',
				event_id: <?php echo $event->id; ?>,
				nonce: '<?php echo wp_create_nonce( 'partyminder_event_action' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$('#event-invitations-list').html(response.data.html);
				} else {
					$('#event-invitations-list').html(
						'<div class="pm-text-center pm-text-muted">' + 
						'<?php _e( 'Unable to load invitations.', 'partyminder' ); ?>' + 
						'</div>'
					);
				}
			},
			error: function() {
				$('#event-invitations-list').html(
					'<div class="pm-text-center pm-text-muted">' + 
					'<?php _e( 'Unable to load invitations.', 'partyminder' ); ?>' + 
					'</div>'
				);
			}
		});
	}
});

function shareEvent() {
	const url = window.location.href;
	const title = '<?php echo esc_js( $event->title ); ?>';
	
	if (navigator.share) {
		navigator.share({
			title: title,
			url: url
		});
	} else if (navigator.clipboard) {
		navigator.clipboard.writeText(url).then(function() {
			alert('Event URL copied to clipboard!');
		});
	} else {
		window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
	}
}

function copyInvitationUrl(url) {
	if (navigator.clipboard) {
		navigator.clipboard.writeText(url).then(function() {
			alert('<?php _e( 'Invitation link copied to clipboard!', 'partyminder' ); ?>');
		});
	} else {
		// Fallback for older browsers
		const textArea = document.createElement('textarea');
		textArea.value = url;
		document.body.appendChild(textArea);
		textArea.select();
		document.execCommand('copy');
		document.body.removeChild(textArea);
		alert('<?php _e( 'Invitation link copied to clipboard!', 'partyminder' ); ?>');
	}
}

</script>