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

<?php
// Check if current user can manage this conversation
$can_manage_conversation = false;
if ( is_user_logged_in() ) {
	$current_user = wp_get_current_user();
	// User can manage if they are the author or an admin
	$can_manage_conversation = ( $current_user->ID == $conversation->author_id ) || current_user_can( 'manage_options' );
}
?>

<?php if ( $can_manage_conversation ) : ?>
<!-- Admin Controls -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<a href="<?php echo home_url( '/conversations/edit/' . $conversation->slug ); ?>" class="pm-btn pm-btn">
			<?php _e( 'Edit Conversation', 'partyminder' ); ?>
		</a>
		<button type="button" class="pm-btn pm-btn-danger delete-conversation-btn" 
				data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>">
			<?php _e( 'Delete Conversation', 'partyminder' ); ?>
		</button>
	</div>
</div>
<?php endif; ?>

<!-- Simple Reply Form -->
<div class="pm-section pm-mb" id="reply-form">
	<form class="pm-form">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'partyminder_nonce' ); ?>">
		<input type="hidden" name="action" value="partyminder_add_reply">
		<input type="hidden" name="conversation_id" value="<?php echo esc_attr( $conversation->id ); ?>">
		<input type="hidden" name="parent_reply_id" value="">
		
		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="pm-form-row pm-mb">
				<div class="pm-form-group">
					<input type="text" name="guest_name" class="pm-form-input" placeholder="<?php esc_attr_e( 'Your Name', 'partyminder' ); ?>" required>
				</div>
				<div class="pm-form-group">
					<input type="email" name="guest_email" class="pm-form-input" placeholder="<?php esc_attr_e( 'Your Email', 'partyminder' ); ?>" required>
				</div>
			</div>
		<?php endif; ?>
		
		<div class="pm-form-group pm-reply-input-container">
			<textarea name="content" class="pm-form-textarea" placeholder="<?php esc_attr_e( 'Reply', 'partyminder' ); ?>" required rows="3"></textarea>
			<button type="button" class="pm-attachment-btn" title="Add file">
				<span class="pm-plus-icon">+</span>
			</button>
			<input type="file" class="pm-file-input" accept="image/*,application/pdf,.doc,.docx" multiple style="display: none;">
		</div>
		
		<div class="pm-form-actions">
			<button type="submit" class="pm-btn">
				<span class="button-text"><?php _e( 'Post Reply', 'partyminder' ); ?></span>
				<span class="button-spinner" style="display: none;"><?php _e( 'Posting...', 'partyminder' ); ?></span>
			</button>
		</div>
	</form>
</div>

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
				<span><?php printf( __( 'Last activity %s ago', 'partyminder' ), human_time_diff( strtotime( $conversation->last_reply_date ), current_time( 'timestamp' ) ) ); ?></span>
			<?php endif; ?>
		</div>
		
		<div class="pm-flex pm-gap">
			<?php if ( $event_data ) : ?>
				<a href="<?php echo home_url( '/events/' . $event_data->slug ); ?>" class="pm-btn pm-btn pm-btn-sm">
					<?php _e( 'Go To Event', 'partyminder' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $user_email ) : ?>
				<button class="pm-btn pm-btn pm-btn-sm follow-btn" 
						data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>">
					<?php if ( $is_following ) : ?>
						<?php _e( 'Unfollow', 'partyminder' ); ?>
					<?php else : ?>
						<?php _e( 'Follow', 'partyminder' ); ?>
					<?php endif; ?>
				</button>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php if ( ! empty( $conversation->featured_image ) ) : ?>
<!-- Featured Image -->
<div class="pm-section pm-mb">
	<div class="pm-card">
		<img src="<?php echo esc_url( $conversation->featured_image ); ?>" alt="<?php echo esc_attr( $conversation->title ); ?>" style="width: 100%; height: auto;">
	</div>
</div>
<?php endif; ?>

<!-- Original Post -->
<div class="pm-section pm-mb">
	<div class="pm-flex pm-gap pm-mb">
		<?php PartyMinder_Member_Display::member_display( $conversation->author_id, array( 'show_name' => false, 'avatar_size' => 40 ) ); ?>
		<div class="pm-flex-1">
			<?php PartyMinder_Member_Display::member_display( $conversation->author_id, array( 'show_avatar' => false, 'class' => 'pm-member-display-name-only' ) ); ?>
			<div class="pm-text-muted"><?php echo date( 'F j, Y \a\t g:i A', strtotime( $conversation->created_at ) ); ?></div>
		</div>
	</div>
	<div class="pm-content">
		<?php echo $conversation_manager->process_content_embeds( $conversation->content ); ?>
	</div>
</div>


<!-- Replies Section -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-md"><?php printf( __( '%1$d %2$s', 'partyminder' ), $conversation->reply_count, $conversation->reply_count === 1 ? __( 'Reply', 'partyminder' ) : __( 'Replies', 'partyminder' ) ); ?></h3>
	</div>
	
	<?php if ( ! empty( $replies ) ) : ?>
		<?php foreach ( $replies as $reply ) : ?>
			<div class="pm-reply-item pm-mb" id="reply-<?php echo $reply->id; ?>" style="margin-left: <?php echo min( $reply->depth_level, 5 ) * 20; ?>px;">
				<div class="pm-flex pm-gap pm-mb">
					<?php PartyMinder_Member_Display::member_display( $reply->author_id, array( 'show_name' => false, 'avatar_size' => 32 ) ); ?>
					<div class="pm-flex-1">
						<?php PartyMinder_Member_Display::member_display( $reply->author_id, array( 'show_avatar' => false, 'class' => 'pm-member-display-name-only' ) ); ?>
						<div class="pm-text-muted"><?php echo date( 'F j, Y \a\t g:i A', strtotime( $reply->created_at ) ); ?></div>
					</div>
				</div>
				<div class="pm-content pm-mb">
					<?php echo $conversation_manager->process_content_embeds( $reply->content ); ?>
				</div>
				<div class="pm-reply-actions">
					<a href="#reply-form" class="pm-btn pm-btn pm-btn-sm reply-btn"
						data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>"
						data-parent-reply-id="<?php echo esc_attr( $reply->id ); ?>">
						<?php _e( 'Reply', 'partyminder' ); ?>
					</a>
					<?php
					// Show edit/delete buttons if user owns this reply
					$can_edit = false;
					if ( is_user_logged_in() ) {
						$current_user = wp_get_current_user();
						$can_edit = ( $current_user->ID == $reply->author_id );
					}
					if ( $can_edit ) :
					?>
						<button type="button" class="pm-btn pm-btn-secondary pm-btn-sm edit-reply-btn" 
								data-reply-id="<?php echo esc_attr( $reply->id ); ?>">
							<?php _e( 'Edit', 'partyminder' ); ?>
						</button>
					<?php endif; ?>
					<?php
					// Show delete button if user can delete this reply
					$can_delete = false;
					if ( is_user_logged_in() ) {
						$current_user = wp_get_current_user();
						// User can delete if they are the author or an admin
						$can_delete = ( $current_user->ID == $reply->author_id ) || current_user_can( 'manage_options' );
					}
					if ( $can_delete ) :
					?>
						<button type="button" class="pm-btn pm-btn pm-btn-sm delete-reply-btn" 
								data-reply-id="<?php echo esc_attr( $reply->id ); ?>"
								data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>">
							<?php _e( 'Delete', 'partyminder' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="pm-text-center pm-p-4">
			<p class="pm-text-muted"><?php _e( 'No replies yet. Be the first to respond!', 'partyminder' ); ?></p>
		</div>
	<?php endif; ?>
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
			<a href="<?php echo home_url( '/events/' . $event_data->slug ); ?>" class="pm-btn pm-btn">
				<?php _e( 'View Event', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
		<a href="#reply-form" class="pm-btn pm-btn">
			<?php _e( 'Add Reply', 'partyminder' ); ?>
		</a>
		<a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn">
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
						$btn.html('Follow');
					} else {
						$btn.html('Unfollow');
					}
				}
			},
			error: function() {
				alert('Network error. Please try again.');
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
	
	// Handle attachment button click
	$(document).on('click', '.pm-attachment-btn', function(e) {
		e.preventDefault();
		const $fileInput = $(this).siblings('.pm-file-input');
		$fileInput.click();
	});
	
	// Handle file selection
	$(document).on('change', '.pm-file-input', function(e) {
		const files = this.files;
		if (files && files.length > 0) {
			$(this).closest('form').data('attachedFiles', files);
		}
	});
	
	// Handle form submission
	$('.pm-form').off('submit').on('submit', function(e) {
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const $buttonText = $submitBtn.find('.button-text');
		const $buttonSpinner = $submitBtn.find('.button-spinner');
		
		// Prevent multiple submissions
		if ($submitBtn.prop('disabled') || $form.data('submitting')) {
			return false;
		}
		
		// Mark as submitting
		$form.data('submitting', true);
		
		$buttonText.hide();
		$buttonSpinner.show();
		$submitBtn.prop('disabled', true);
		
		// Check for attached files
		const attachedFiles = $form.data('attachedFiles');
		let formData;
		
		if (attachedFiles && attachedFiles.length > 0) {
			// Use FormData for file upload
			formData = new FormData();
			const formElements = $form.serializeArray();
			formElements.forEach(function(field) {
				formData.append(field.name, field.value);
			});
			
			// Add all files
			for (let i = 0; i < attachedFiles.length; i++) {
				formData.append('attachments[]', attachedFiles[i]);
			}
		} else {
			// Use regular form data
			formData = $form.serialize();
		}
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: formData,
			processData: !attachedFiles,
			contentType: attachedFiles ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert(response.data || 'Error posting reply. Please try again.');
				}
			},
			error: function() {
				alert('Network error. Please try again.');
			},
			complete: function() {
				$buttonText.show();
				$buttonSpinner.hide();
				$submitBtn.prop('disabled', false);
				$form.data('submitting', false);
			}
		});
	});
	
	// Handle edit reply button clicks
	$('.edit-reply-btn').on('click', function(e) {
		e.preventDefault();
		
		const $btn = $(this);
		const replyId = $btn.data('reply-id');
		const $replyItem = $btn.closest('.pm-reply-item');
		const $content = $replyItem.find('.pm-content');
		const $actions = $replyItem.find('.pm-reply-actions');
		
		// Get current content text
		const currentContent = $content.text().trim();
		
		// Replace content with textarea
		$content.html(`
			<textarea class="pm-form-textarea edit-reply-textarea" style="width: 100%; min-height: 100px;">${currentContent}</textarea>
		`);
		
		// Replace actions with save/cancel buttons
		$actions.html(`
			<button type="button" class="pm-btn pm-btn-sm save-reply-btn" data-reply-id="${replyId}">
				<?php _e( 'Save', 'partyminder' ); ?>
			</button>
			<button type="button" class="pm-btn pm-btn-secondary pm-btn-sm cancel-edit-btn">
				<?php _e( 'Cancel', 'partyminder' ); ?>
			</button>
		`);
		
		// Focus textarea
		$content.find('.edit-reply-textarea').focus();
	});
	
	// Handle save reply button clicks
	$(document).on('click', '.save-reply-btn', function(e) {
		e.preventDefault();
		
		const $btn = $(this);
		const replyId = $btn.data('reply-id');
		const $replyItem = $btn.closest('.pm-reply-item');
		const $textarea = $replyItem.find('.edit-reply-textarea');
		const newContent = $textarea.val().trim();
		
		if (!newContent) {
			alert('<?php _e( 'Reply content cannot be empty.', 'partyminder' ); ?>');
			return;
		}
		
		$btn.prop('disabled', true).text('<?php _e( 'Saving...', 'partyminder' ); ?>');
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_update_reply',
				reply_id: replyId,
				content: newContent,
				nonce: partyminder_ajax.nonce
			},
			success: function(response) {
				if (response.success) {
					// Reload the page to show updated content
					location.reload();
				} else {
					alert(response.data || '<?php _e( 'Failed to update reply.', 'partyminder' ); ?>');
					$btn.prop('disabled', false).text('<?php _e( 'Save', 'partyminder' ); ?>');
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
				$btn.prop('disabled', false).text('<?php _e( 'Save', 'partyminder' ); ?>');
			}
		});
	});
	
	// Handle cancel edit button clicks
	$(document).on('click', '.cancel-edit-btn', function(e) {
		e.preventDefault();
		
		// Reload the page to restore original content and actions
		location.reload();
	});
	
	// Handle delete reply button clicks
	$('.delete-reply-btn').on('click', function(e) {
		e.preventDefault();
		
		const $btn = $(this);
		const replyId = $btn.data('reply-id');
		const conversationId = $btn.data('conversation-id');
		
		if (!confirm('Are you sure you want to delete this reply? This action cannot be undone.')) {
			return;
		}
		
		$btn.prop('disabled', true).text('Deleting...');
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_delete_reply',
				reply_id: replyId,
				conversation_id: conversationId,
				nonce: partyminder_ajax.nonce
			},
			success: function(response) {
				if (response.success) {
					// Remove the reply from the DOM
					$btn.closest('.pm-reply-item').fadeOut(300, function() {
						$(this).remove();
						// Update reply count in header
						location.reload(); // Simple approach - reload to update counts
					});
				} else {
					alert(response.data || 'Failed to delete reply.');
					$btn.prop('disabled', false).text('Delete');
				}
			},
			error: function() {
				alert('Network error. Please try again.');
				$btn.prop('disabled', false).text('Delete');
			}
		});
	});
	
	// Handle delete conversation button clicks
	$('.delete-conversation-btn').on('click', function(e) {
		e.preventDefault();
		
		const $btn = $(this);
		const conversationId = $btn.data('conversation-id');
		
		if (!confirm('Are you sure you want to delete this entire conversation? This will delete all replies and cannot be undone.')) {
			return;
		}
		
		$btn.prop('disabled', true).text('Deleting...');
		
		$.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_delete_conversation',
				conversation_id: conversationId,
				nonce: partyminder_ajax.nonce
			},
			success: function(response) {
				if (response.success) {
					// Redirect to conversations list
					window.location.href = '<?php echo PartyMinder::get_conversations_url(); ?>';
				} else {
					alert(response.data || 'Failed to delete conversation.');
					$btn.prop('disabled', false).text('Delete Conversation');
				}
			},
			error: function() {
				alert('Network error. Please try again.');
				$btn.prop('disabled', false).text('Delete Conversation');
			}
		});
	});
});

</script>