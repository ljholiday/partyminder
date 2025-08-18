<?php
/**
 * Single Conversation Content Template
 * Shows individual conversation with replies using unified two-column system
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';

$conversation_manager = new PartyMinder_Conversation_Manager();

// Get slug from URL
$conversation_slug = get_query_var( 'conversation_slug' );

if ( ! $conversation_slug ) {
	wp_redirect( PartyMinder::get_conversations_url() );
	exit;
}

// Get conversation by slug
$conversation = $conversation_manager->get_conversation( $conversation_slug, true );

if ( ! $conversation ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	return;
}

// Get replies
$replies = $conversation_manager->get_conversation_replies( $conversation->id );

// Get event data if this is an event conversation
$event_data = null;
if ( $conversation->event_id ) {
	require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
	$event_manager = new PartyMinder_Event_Manager();
	$event_data    = $event_manager->get_event( $conversation->event_id );
}

// Get current user info
$current_user = wp_get_current_user();
$user_email   = is_user_logged_in() ? $current_user->user_email : '';
$is_following = false;

if ( $user_email ) {
	$is_following = $conversation_manager->is_following( $conversation->id, $current_user->ID, $user_email );
}

// Determine conversation context
$context_info = '';
if ( $conversation->event_id ) {
	$context_info = __( 'Event Discussion', 'partyminder' );
} elseif ( $conversation->community_id ) {
	$context_info = __( 'Community Discussion', 'partyminder' );
} else {
	$context_info = __( 'General Discussion', 'partyminder' );
}

// Set up template variables
$page_title       = esc_html( $conversation->title );
$page_description = sprintf( __( '%1$s started by %2$s' ), $context_info, $conversation->author_name );
$breadcrumbs      = array(
	array(
		'title' => __( 'Conversations', 'partyminder' ),
		'url'   => PartyMinder::get_conversations_url(),
	),
	array( 'title' => $conversation->title ),
);

// Main content
ob_start();
?>

<!-- Conversation Header -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-lg pm-text-primary"><?php echo esc_html( $conversation->title ); ?></h2>
		<div class="pm-flex pm-flex-wrap pm-gap pm-text-muted">
			<span><?php printf( __( 'Started by %s', 'partyminder' ), esc_html( $conversation->author_name ) ); ?></span>
			<span><?php echo human_time_diff( strtotime( $conversation->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'partyminder' ); ?></span>
			<?php if ( $event_data ) : ?>
				<span><?php printf( __( 'for event: %s', 'partyminder' ), esc_html( $event_data->title ) ); ?></span>
			<?php endif; ?>
		</div>
	</div>
	
	<div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
		<div class="pm-flex pm-gap pm-text-muted">
			<span> <?php echo $conversation->reply_count; ?> <?php echo $conversation->reply_count === 1 ? __( 'reply', 'partyminder' ) : __( 'replies', 'partyminder' ); ?></span>
			<?php if ( $conversation->reply_count > 0 ) : ?>
				<span>🕐 <?php printf( __( 'Last activity %s ago', 'partyminder' ), human_time_diff( strtotime( $conversation->last_reply_date ), current_time( 'timestamp' ) ) ); ?></span>
			<?php endif; ?>
		</div>
		
		<div class="pm-flex pm-gap">
			<?php if ( $event_data ) : ?>
				<a href="<?php echo home_url( '/events/' . $event_data->slug ); ?>" class="pm-btn pm-btn-secondary pm-btn-sm">
					<?php _e( 'Go To Event', 'partyminder' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $user_email ) : ?>
				<button class="pm-btn pm-btn-secondary pm-btn-sm follow-btn" 
						data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>">
					<?php if ( $is_following ) : ?>
						🔕 <?php _e( 'Unfollow', 'partyminder' ); ?>
					<?php else : ?>
						🔔 <?php _e( 'Follow', 'partyminder' ); ?>
					<?php endif; ?>
				</button>
			<?php endif; ?>
			<a href="#reply-form" class="pm-btn pm-btn-sm">
				<?php _e( 'Reply', 'partyminder' ); ?>
			</a>
		</div>
	</div>
</div>

<!-- Original Post -->
<div class="pm-section pm-mb">
	<div class="pm-flex pm-gap pm-mb">
		<div class="pm-avatar">
			<?php echo strtoupper( substr( $conversation->author_name, 0, 2 ) ); ?>
		</div>
		<div class="pm-flex-1">
			<h4 class="pm-heading pm-heading-sm"><?php echo esc_html( $conversation->author_name ); ?></h4>
			<div class="pm-text-muted"><?php echo date( 'F j, Y \a\t g:i A', strtotime( $conversation->created_at ) ); ?></div>
		</div>
	</div>
	<div class="pm-content">
		<?php echo wpautop( $conversation->content ); ?>
	</div>
</div>

<?php
// Get uploaded conversation photos
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-image-upload.php';
$conversation_photos = array();
if ( class_exists( 'PartyMinder_Image_Upload' ) ) {
	$conversation_photos = PartyMinder_Image_Upload::get_conversation_photos( $conversation->id );
}
if ( ! empty( $conversation_photos ) ) :
?>
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-md">Photos</h3>
	</div>
	<div class="pm-flex pm-flex-column pm-gap">
		<?php foreach ( $conversation_photos as $photo ) : ?>
			<div class="pm-photo-item">
				<img src="<?php echo esc_url( $photo['medium_url'] ); ?>" 
					 alt="Conversation photo" 
					 style="max-width: 100%; height: auto; border-radius: 0.375rem;">
				<div class="pm-text-muted" style="font-size: 12px; margin-top: 0.25rem;">
					<?php echo human_time_diff( $photo['uploaded'], current_time( 'timestamp' ) ) . ' ago'; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

<!-- Replies Section -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-md"><?php printf( __( '%1$d %2$s', 'partyminder' ), $conversation->reply_count, $conversation->reply_count === 1 ? __( 'Reply', 'partyminder' ) : __( 'Replies', 'partyminder' ) ); ?></h3>
	</div>
	
	<?php if ( ! empty( $replies ) ) : ?>
		<?php foreach ( $replies as $reply ) : ?>
			<div class="pm-reply-item pm-mb" id="reply-<?php echo $reply->id; ?>" style="margin-left: <?php echo min( $reply->depth_level, 5 ) * 20; ?>px;">
				<div class="pm-flex pm-gap pm-mb">
					<div class="pm-avatar pm-avatar-sm">
						<?php echo strtoupper( substr( $reply->author_name, 0, 2 ) ); ?>
					</div>
					<div class="pm-flex-1">
						<h5 class="pm-heading pm-heading-sm"><?php echo esc_html( $reply->author_name ); ?></h5>
						<div class="pm-text-muted"><?php echo date( 'F j, Y \a\t g:i A', strtotime( $reply->created_at ) ); ?></div>
					</div>
				</div>
				<div class="pm-content pm-mb">
					<?php echo wpautop( $reply->content ); ?>
				</div>
				<div class="pm-reply-actions">
					<a href="#reply-form" class="pm-btn pm-btn-secondary pm-btn-sm reply-btn"
						data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>"
						data-parent-reply-id="<?php echo esc_attr( $reply->id ); ?>">
						↩️ <?php _e( 'Reply', 'partyminder' ); ?>
					</a>
				</div>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="pm-text-center pm-p-4">
			<p class="pm-text-muted"><?php _e( 'No replies yet. Be the first to respond!', 'partyminder' ); ?></p>
		</div>
	<?php endif; ?>
</div>

<!-- Photo Upload Section -->
<?php if ( is_user_logged_in() ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-md"><?php _e( 'Share a Photo', 'partyminder' ); ?></h3>
	</div>
	
	<form id="conversation-photo-upload-form" enctype="multipart/form-data">
		<div class="pm-form-group">
			<label class="pm-form-label">Choose Photo</label>
			<input type="file" name="conversation_photo" accept="image/*" class="pm-form-input" required>
			<div class="pm-form-help">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF, WebP</div>
		</div>
		<button type="submit" class="pm-btn pm-btn-sm">Upload Photo</button>
	</form>
	
	<div id="conversation-photo-progress" style="display: none;">
		<div class="pm-progress-bar">
			<div class="pm-progress-fill"></div>
		</div>
		<div class="pm-progress-text">Uploading...</div>
	</div>
	
	<div id="conversation-photo-message"></div>
</div>
<?php endif; ?>

<!-- Reply Form -->
<div class="pm-section" id="reply-form">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-md"><?php _e( 'Add Your Reply', 'partyminder' ); ?></h3>
	</div>
	
	<form class="pm-form" method="post">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'partyminder_nonce' ); ?>">
		<input type="hidden" name="action" value="partyminder_add_reply">
		<input type="hidden" name="conversation_id" value="<?php echo esc_attr( $conversation->id ); ?>">
		<input type="hidden" name="parent_reply_id" value="">
		
		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="pm-form-row">
				<div class="pm-form-group">
					<label for="reply_guest_name" class="pm-form-label"><?php _e( 'Your Name *', 'partyminder' ); ?></label>
					<input type="text" id="reply_guest_name" name="guest_name" class="pm-form-input" required>
				</div>
				<div class="pm-form-group">
					<label for="reply_guest_email" class="pm-form-label"><?php _e( 'Your Email *', 'partyminder' ); ?></label>
					<input type="email" id="reply_guest_email" name="guest_email" class="pm-form-input" required>
				</div>
			</div>
		<?php endif; ?>
		
		<div class="pm-form-group">
			<label for="reply_content" class="pm-form-label"><?php _e( 'Your Reply *', 'partyminder' ); ?></label>
			<textarea id="reply_content" name="content" class="pm-form-textarea" required rows="6" 
						placeholder="<?php esc_attr_e( 'Share your thoughts on this conversation...', 'partyminder' ); ?>"></textarea>
		</div>
		
		<div class="pm-form-actions">
			<button type="submit" class="pm-btn">
				<span class="button-text"><?php _e( 'Post Reply', 'partyminder' ); ?></span>
				<span class="button-spinner" style="display: none;"><?php _e( 'Posting...', 'partyminder' ); ?></span>
			</button>
			<a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn-secondary">
				← <?php _e( 'Back to Conversations', 'partyminder' ); ?>
			</a>
		</div>
	</form>
</div>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<!-- Context Section -->
<?php if ( $event_data ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Event Context', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-card pm-p-4">
		<h4 class="pm-heading pm-heading-sm pm-mb-2">
			<a href="<?php echo home_url( '/events/' . $event_data->slug ); ?>" class="pm-text-primary">
				<?php echo esc_html( $event_data->title ); ?>
			</a>
		</h4>
		<div class="pm-text-muted pm-mb-2">
			<?php
			$event_date = new DateTime( $event_data->event_date );
			$is_today = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d' );
			$is_tomorrow = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d', strtotime( '+1 day' ) );
			$is_past = $event_date < new DateTime();
			?>
			<?php if ( $is_today ) : ?>
				<?php _e( 'Today', 'partyminder' ); ?>
			<?php elseif ( $is_tomorrow ) : ?>
				<?php _e( 'Tomorrow', 'partyminder' ); ?>
			<?php elseif ( $is_past ) : ?>
				<?php echo $event_date->format( 'M j, Y' ); ?> (<?php _e( 'Past', 'partyminder' ); ?>)
			<?php else : ?>
				<?php echo $event_date->format( 'M j, Y' ); ?>
			<?php endif; ?>
			<?php if ( $event_data->event_time ) : ?>
				at <?php echo date( 'g:i A', strtotime( $event_data->event_date ) ); ?>
			<?php endif; ?>
		</div>
		<?php if ( $event_data->venue_info ) : ?>
			<div class="pm-text-muted pm-mb-2"><?php echo esc_html( $event_data->venue_info ); ?></div>
		<?php endif; ?>
		<div class="pm-flex pm-gap">
			<div class="pm-stat pm-text-center">
				<div class="pm-stat-number pm-text-primary"><?php echo $event_data->guest_stats->confirmed; ?></div>
				<div class="pm-stat-label"><?php _e( 'Going', 'partyminder' ); ?></div>
			</div>
		</div>
	</div>
</div>
<?php elseif ( $conversation->community_id ) : ?>
<!-- Community context would go here if implemented -->
<?php endif; ?>

<!-- Conversation Info -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Conversation Info', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-stat-list">
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Started by', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo esc_html( $conversation->author_name ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Created', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo human_time_diff( strtotime( $conversation->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'partyminder' ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Replies', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo $conversation->reply_count; ?></span>
		</div>
		<?php if ( $conversation->reply_count > 0 ) : ?>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Last Reply', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo human_time_diff( strtotime( $conversation->last_reply_date ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'partyminder' ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Last Reply By', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo esc_html( $conversation->last_reply_author ); ?></span>
		</div>
		<?php endif; ?>
	</div>
</div>

<!-- Essential Actions -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Actions', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-flex pm-flex-column pm-gap">
		<?php if ( $user_email ) : ?>
			<button class="pm-btn follow-btn" data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>">
				<?php if ( $is_following ) : ?>
					<?php _e( 'Unfollow', 'partyminder' ); ?>
				<?php else : ?>
					<?php _e( 'Follow', 'partyminder' ); ?>
				<?php endif; ?>
			</button>
		<?php endif; ?>
		<?php if ( $event_data ) : ?>
			<a href="<?php echo home_url( '/events/' . $event_data->slug ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'View Event', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
		<a href="#reply-form" class="pm-btn pm-btn-secondary">
			<?php _e( 'Add Reply', 'partyminder' ); ?>
		</a>
		<a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Back to Conversations', 'partyminder' ); ?>
		</a>
	</div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
jQuery(document).ready(function($) {
	// Handle conversation photo upload
	$('#conversation-photo-upload-form').on('submit', function(e) {
		e.preventDefault();
		
		const formData = new FormData();
		const fileInput = $(this).find('input[type="file"]')[0];
		
		if (!fileInput.files[0]) {
			alert('Please select a photo to upload.');
			return;
		}
		
		formData.append('action', 'partyminder_conversation_photo_upload');
		formData.append('conversation_id', <?php echo $conversation->id; ?>);
		formData.append('conversation_photo', fileInput.files[0]);
		formData.append('nonce', '<?php echo wp_create_nonce( 'partyminder_conversation_photo_upload' ); ?>');
		
		const $form = $(this);
		const $progress = $('#conversation-photo-progress');
		const $progressFill = $('.pm-progress-fill');
		const $message = $('#conversation-photo-message');
		
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
	
	// Handle follow/unfollow
	$('.follow-btn').on('click', function() {
		const $btn = $(this);
		const conversationId = $btn.data('conversation-id');
		const isFollowing = $btn.text().includes('Unfollow');
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: isFollowing ? 'partyminder_unfollow_conversation' : 'partyminder_follow_conversation',
				conversation_id: conversationId,
				nonce: partyminder_ajax.nonce
			},
			success: function(response) {
				if (response.success) {
					if (isFollowing) {
						$btn.html('🔔 <?php _e( 'Follow', 'partyminder' ); ?>');
					} else {
						$btn.html('🔕 <?php _e( 'Unfollow', 'partyminder' ); ?>');
					}
				}
			}
		});
	});
	
	// Handle reply button clicks
	$('.reply-btn').on('click', function(e) {
		e.preventDefault();
		const parentReplyId = $(this).data('parent-reply-id') || '';
		$('input[name="parent_reply_id"]').val(parentReplyId);
		$('#reply-form')[0].scrollIntoView({behavior: 'smooth'});
		$('#reply_content').focus();
	});
	
	// Handle form submission
	$('.pm-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const $buttonText = $submitBtn.find('.button-text');
		const $buttonSpinner = $submitBtn.find('.button-spinner');
		
		$buttonText.hide();
		$buttonSpinner.show();
		$submitBtn.prop('disabled', true);
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: $form.serialize(),
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert(response.data || '<?php _e( 'Error posting reply. Please try again.', 'partyminder' ); ?>');
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
			},
			complete: function() {
				$buttonText.show();
				$buttonSpinner.hide();
				$submitBtn.prop('disabled', false);
			}
		});
	});
});

</script>