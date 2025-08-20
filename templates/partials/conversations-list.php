<?php
/**
 * Conversations List Partial
 * Renders the list of conversations with smart titles
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// $conversations should be passed in from the including template
if ( empty( $conversations ) ) {
	?>
	<div class="pm-section pm-text-center">
		<div class="pm-text-6xl pm-mb-4">💬</div>
		<h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e( 'No Conversations in This Circle', 'partyminder' ); ?></h3>
		<p class="pm-text-muted pm-mb-4"><?php _e( 'Start a conversation to connect with people in your circle.', 'partyminder' ); ?></p>
		<?php if ( is_user_logged_in() ) : ?>
			<a href="<?php echo PartyMinder::get_create_conversation_url(); ?>" class="pm-btn">
				<?php _e( 'Start a Conversation', 'partyminder' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo add_query_arg( 'redirect_to', urlencode( $_SERVER['REQUEST_URI'] ), PartyMinder::get_login_url() ); ?>" class="pm-btn">
				<?php _e( 'Login to Start Conversations', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
	return;
}

// Make sure we have conversation manager
if ( ! isset( $conversation_manager ) ) {
	require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
	$conversation_manager = new PartyMinder_Conversation_Manager();
}
?>

<div class="pm-grid pm-grid-1 pm-gap">
	<?php foreach ( $conversations as $conversation ) : ?>
		<?php
		// Determine conversation context
		$context_info = '';
		$context_link = '';
		
		if ( $conversation->event_id ) {
			$context_info = sprintf( __( 'Event Discussion', 'partyminder' ) );
			$context_link = home_url( '/events/' . $conversation->event_slug );
		} elseif ( $conversation->community_id ) {
			$context_info = sprintf( __( 'Community Discussion', 'partyminder' ) );
			$context_link = home_url( '/communities/' . $conversation->community_slug );
		} else {
			$context_info = __( 'General Discussion', 'partyminder' );
		}
		?>
		
		<div class="pm-section pm-p-4">
			<div class="pm-flex pm-flex-between pm-mb-4">
				<div class="pm-flex-1">
					<h3 class="pm-heading pm-heading-sm pm-mb-2">
						<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-text-primary">
							<?php echo esc_html( $conversation_manager->get_display_title( $conversation ) ); ?>
						</a>
					</h3>
					<div class="pm-flex pm-gap pm-flex-wrap">
						<span class="pm-badge pm-badge-secondary"><?php echo esc_html( $context_info ); ?></span>
						<?php if ( $conversation->is_pinned ) : ?>
							<span class="pm-badge pm-badge-warning"><?php _e( 'Pinned', 'partyminder' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="pm-stat pm-text-center">
					<div class="pm-stat-number pm-text-primary"><?php echo $conversation->reply_count; ?></div>
					<div class="pm-stat-label"><?php _e( 'Replies', 'partyminder' ); ?></div>
				</div>
			</div>
			
			<div class="pm-mb-4">
				<p class="pm-text-muted">
					<?php 
					$content_preview = wp_trim_words( strip_tags( $conversation->content ), 20, '...' );
					echo esc_html( $content_preview );
					?>
				</p>
			</div>
			
			<div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
				<div class="pm-text-muted">
					<?php _e( 'Started by', 'partyminder' ); ?> 
					<strong><?php echo esc_html( $conversation->author_name ); ?></strong>
					• <?php echo human_time_diff( strtotime( $conversation->created_at ), current_time( 'timestamp' ) ); ?> <?php _e( 'ago', 'partyminder' ); ?>
				</div>
				
				<?php if ( $conversation->last_reply_author && $conversation->reply_count > 0 ) : ?>
					<div class="pm-text-muted">
						<?php _e( 'Last reply by', 'partyminder' ); ?> 
						<strong><?php echo esc_html( $conversation->last_reply_author ); ?></strong>
						• <?php echo human_time_diff( strtotime( $conversation->last_reply_date ), current_time( 'timestamp' ) ); ?> <?php _e( 'ago', 'partyminder' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>