<?php
/**
 * Conversations Circle Navigation Partial
 * Shared navigation for filtering conversations by circles of trust
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current circle from URL parameter or default to 'close'
$current_circle = sanitize_text_field( $_GET['circle'] ?? 'close' );
$topic_slug = get_query_var( 'topic_slug' ) ?? '';
?>

<div class="pm-conversations-nav" role="tablist" aria-label="Filter conversations">
	<button class="pm-btn pm-btn-secondary <?php echo $current_circle === 'close' ? 'is-active' : ''; ?>" 
			data-circle="close" 
			role="tab" 
			aria-selected="<?php echo $current_circle === 'close' ? 'true' : 'false'; ?>" 
			aria-controls="pm-convo-list">
		<?php _e( 'Close Circle', 'partyminder' ); ?>
	</button>
	<button class="pm-btn pm-btn-secondary <?php echo $current_circle === 'trusted' ? 'is-active' : ''; ?>" 
			data-circle="trusted" 
			role="tab" 
			aria-selected="<?php echo $current_circle === 'trusted' ? 'true' : 'false'; ?>" 
			aria-controls="pm-convo-list">
		<?php _e( 'Trusted Circle', 'partyminder' ); ?>
	</button>
	<button class="pm-btn pm-btn-secondary <?php echo $current_circle === 'extended' ? 'is-active' : ''; ?>" 
			data-circle="extended" 
			role="tab" 
			aria-selected="<?php echo $current_circle === 'extended' ? 'true' : 'false'; ?>" 
			aria-controls="pm-convo-list">
		<?php _e( 'Extended Circle', 'partyminder' ); ?>
	</button>
</div>

<div id="pm-convo-list" class="pm-conversations-list" aria-live="polite" data-topic="<?php echo esc_attr( $topic_slug ); ?>">
	<!-- Conversation list will be loaded here -->
</div>