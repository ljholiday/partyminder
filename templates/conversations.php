<?php
/**
 * Conversations Template
 * Main conversations listing page - simplified without topics
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
$conversation_manager = new PartyMinder_Conversation_Manager();

// Check if we're viewing a single conversation
$conversation_slug = get_query_var( 'conversation_slug' );

if ( $conversation_slug ) {
	if ( file_exists( PARTYMINDER_PLUGIN_DIR . 'templates/single-conversation-content.php' ) ) {
		include PARTYMINDER_PLUGIN_DIR . 'templates/single-conversation-content.php';
	}
	return;
}

// Let JavaScript handle conversation loading via circle filtering
$recent_conversations = array();
$user_logged_in = is_user_logged_in();

// Get user's conversations for sidebar
$user_conversations = array();
if ( $user_logged_in ) {
	$current_user = wp_get_current_user();
	$user_conversations = $conversation_manager->get_user_conversations( $current_user->ID, 6 );
}

// Check for filter parameter from URL
$active_filter = sanitize_text_field( $_GET['filter'] ?? '' );
$valid_filters = array( 'events', 'communities' );
if ( ! in_array( $active_filter, $valid_filters ) ) {
	$active_filter = '';
}

// Set up template variables
$page_title       = __( 'Conversations', 'partyminder' );
$page_description = __( 'Connect, share tips, and plan amazing gatherings with fellow hosts and guests', 'partyminder' );

// Main content
ob_start();
?>

<!-- Conversation Filters -->
<?php if ( $user_logged_in ) : ?>
<div class="pm-section pm-mb-4">
	<div class="pm-conversations-nav pm-flex pm-gap-4 pm-flex-wrap">
		<!-- Circle Filters -->
		<button class="pm-btn pm-btn is-active" data-circle="close" role="tab" aria-selected="true" aria-controls="pm-convo-list">
			<?php _e( 'Close', 'partyminder' ); ?>
		</button>
		<button class="pm-btn pm-btn" data-circle="trusted" role="tab" aria-selected="false" aria-controls="pm-convo-list">
			<?php _e( 'Trusted', 'partyminder' ); ?>
		</button>
		<button class="pm-btn pm-btn" data-circle="extended" role="tab" aria-selected="false" aria-controls="pm-convo-list">
			<?php _e( 'Extended', 'partyminder' ); ?>
		</button>
		
		<!-- Type Filters -->
		<button class="pm-btn pm-btn" data-filter="events" role="tab" aria-selected="false" aria-controls="pm-convo-list">
			<?php _e( 'Event', 'partyminder' ); ?>
		</button>
		<button class="pm-btn pm-btn" data-filter="communities" role="tab" aria-selected="false" aria-controls="pm-convo-list">
			<?php _e( 'Community', 'partyminder' ); ?>
		</button>
	</div>
</div>
<?php endif; ?>

<div class="pm-section">
	<div id="pm-convo-list" class="pm-grid pm-grid-2 pm-gap">
		<?php if ( $user_logged_in ) : ?>
			<div class="pm-text-center pm-p-4">
				<p class="pm-text-muted"><?php _e( 'Loading conversations...', 'partyminder' ); ?></p>
			</div>
		<?php else : ?>
			<?php
			// For non-logged in users, show recent public conversations
			$public_conversations = $conversation_manager->get_recent_conversations( 20 );
			if ( ! empty( $public_conversations ) ) :
				foreach ( $public_conversations as $conversation ) : ?>
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
				<?php endforeach;
			else : ?>
				<div class="pm-text-center pm-p-4">
					<h3 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'No Conversations Found', 'partyminder' ); ?></h3>
					<p class="pm-text-muted"><?php _e( 'There are no conversations to display.', 'partyminder' ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>
