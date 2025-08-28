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

<div class="pm-grid pm-grid-2 pm-gap">
	<?php foreach ( $conversations as $conversation ) : ?>
		<div class="pm-section">
			<div class="pm-flex pm-flex-between pm-mb-4">
				<h3 class="pm-heading pm-heading-sm">
					<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-text-primary"><?php echo esc_html( $conversation_manager->get_display_title( $conversation ) ); ?></a>
				</h3>
			</div>
			
			<div class="pm-mb-4">
				<div class="pm-flex pm-gap pm-mb-4">
					<span class="pm-text-muted">
						<?php
						if ( $conversation->event_id ) {
							_e( 'Event Discussion', 'partyminder' );
						} elseif ( $conversation->community_id ) {
							_e( 'Community Discussion', 'partyminder' );
						} else {
							_e( 'General Discussion', 'partyminder' );
						}
						?>
					</span>
				</div>
				
				<div class="pm-flex pm-gap pm-mb-4">
					<span class="pm-text-muted">
						<?php printf( __( 'Started by %s', 'partyminder' ), esc_html( $conversation->author_name ) ); ?>
					</span>
				</div>
			</div>
			
			<?php if ( $conversation->content ) : ?>
			<div class="pm-mb-4">
				<p class="pm-text-muted"><?php echo esc_html( wp_trim_words( $conversation->excerpt ?: $conversation->content, 15 ) ); ?></p>
			</div>
			<?php endif; ?>
			
			<div class="pm-flex pm-flex-between">
				<div class="pm-stat">
					<div class="pm-stat-number pm-text-primary"><?php echo $conversation->reply_count; ?></div>
					<div class="pm-stat-label">
						<?php _e( 'Replies', 'partyminder' ); ?>
					</div>
				</div>
				
				<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-btn pm-btn">
					<?php _e( 'View Details', 'partyminder' ); ?>
				</a>
			</div>
		</div>
	<?php endforeach; ?>
</div>