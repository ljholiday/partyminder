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

// Get slugs from URL
$topic_slug        = get_query_var( 'conversation_topic' );
$conversation_slug = get_query_var( 'conversation_slug' );

if ( ! $topic_slug || ! $conversation_slug ) {
	wp_redirect( PartyMinder::get_conversations_url() );
	exit;
}

// Get topic and conversation
$topic        = $conversation_manager->get_topic_by_slug( $topic_slug );
$conversation = $conversation_manager->get_conversation( $conversation_slug, true );

// Get event data if this is an event conversation
$event_data = null;
if ( $conversation && $conversation->event_id ) {
	require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
	$event_manager = new PartyMinder_Event_Manager();
	$event_data    = $event_manager->get_event( $conversation->event_id );
}

if ( ! $topic || ! $conversation ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	return;
}

// Get replies
$replies = $conversation_manager->get_conversation_replies( $conversation->id );

// Get current user info
$current_user = wp_get_current_user();
$user_email   = is_user_logged_in() ? $current_user->user_email : '';
$is_following = false;

if ( $user_email ) {
	$is_following = $conversation_manager->is_following( $conversation->id, $current_user->ID, $user_email );
}

// Set up template variables
$page_title       = esc_html( $conversation->title );
$page_description = sprintf( __( 'Discussion in %1$s started by %2$s' ), $topic->name, $conversation->author_name );
$breadcrumbs      = array(
	array(
		'title' => __( 'Conversations', 'partyminder' ),
		'url'   => PartyMinder::get_conversations_url(),
	),
	array(
		'title' => $topic->icon . ' ' . $topic->name,
		'url'   => home_url( '/conversations/' . $topic->slug ),
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
			<a href="<?php echo home_url( '/conversations/' . $topic->slug ); ?>" class="pm-btn pm-btn-secondary">
				← <?php _e( 'Back to Topic', 'partyminder' ); ?>
			</a>
		</div>
	</form>
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
			<a href="<?php echo home_url( '/conversations/' . $topic->slug ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( '← Back to Topic', 'partyminder' ); ?>
			</a>
			<a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( '← All Conversations', 'partyminder' ); ?>
			</a>
		</div>
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