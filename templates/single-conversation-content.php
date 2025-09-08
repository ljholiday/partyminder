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
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-activity-tracker.php';

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
	echo '<div class="pm-alert pm-alert-error">Conversation not found.</div>';
	return;
}

// Mark conversation as read for logged-in users
if ( is_user_logged_in() ) {
	PartyMinder_Activity_Tracker::track_user_activity( get_current_user_id(), 'conversations', $conversation->id );
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

<!-- Conversation Controls -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<button type="button" class="pm-btn pm-btn-primary pm-reply-btn" 
		        data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>">
			<?php _e( 'Add Reply', 'partyminder' ); ?>
		</button>
		<?php if ( $can_manage_conversation ) : ?>
			<a href="<?php echo home_url( '/conversations/edit/' . $conversation->slug ); ?>" class="pm-btn pm-btn">
				<?php _e( 'Edit Conversation', 'partyminder' ); ?>
			</a>
			<button type="button" class="pm-btn pm-btn-danger delete-conversation-btn" 
					data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>">
				<?php _e( 'Delete Conversation', 'partyminder' ); ?>
			</button>
		<?php endif; ?>
	</div>
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
					<button type="button" class="pm-btn pm-btn pm-btn-sm pm-reply-btn"
					        data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>"
					        data-parent-reply-id="<?php echo esc_attr( $reply->id ); ?>">
						<?php _e( 'Reply', 'partyminder' ); ?>
					</button>
					<?php
					// Show edit/delete buttons if user owns this reply
					$can_edit = false;
					if ( is_user_logged_in() ) {
						$current_user = wp_get_current_user();
						$can_edit = ( $current_user->ID == $reply->author_id );
					}
					if ( $can_edit ) :
					?>
						<button type="button" class="pm-btn pm-btn-secondary pm-btn-sm pm-edit-reply-btn" 
						        data-reply-id="<?php echo esc_attr( $reply->id ); ?>"
						        data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>">
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
		<button type="button" class="pm-btn pm-btn pm-reply-btn" 
		        data-conversation-id="<?php echo esc_attr( $conversation->id ); ?>">
			<?php _e( 'Add Reply', 'partyminder' ); ?>
		</button>
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
					location.reload();
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