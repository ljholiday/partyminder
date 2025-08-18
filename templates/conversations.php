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

// Get data for the page
$recent_conversations = $conversation_manager->get_recent_conversations( 20 );
$user_logged_in = is_user_logged_in();

// Get user's conversations for sidebar
$user_conversations = array();
if ( $user_logged_in ) {
	$current_user = wp_get_current_user();
	$user_conversations = $conversation_manager->get_user_conversations( $current_user->ID, 6 );
}

// Set up template variables
$page_title       = __( 'Conversations', 'partyminder' );
$page_description = __( 'Connect, share tips, and plan amazing gatherings with fellow hosts and guests', 'partyminder' );

// Main content
ob_start();
?>

<!-- Secondary Menu Bar -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<?php if ( $user_logged_in ) : ?>
			<a href="<?php echo PartyMinder::get_create_conversation_url(); ?>" class="pm-btn">
				<?php _e( 'Start Conversation', 'partyminder' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo add_query_arg( 'redirect_to', urlencode( $_SERVER['REQUEST_URI'] ), PartyMinder::get_login_url() ); ?>" class="pm-btn">
				<?php _e( 'Login to Participate', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
		<a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Browse Events', 'partyminder' ); ?>
		</a>
		<a href="<?php echo esc_url( PartyMinder::get_dashboard_url() ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Dashboard', 'partyminder' ); ?>
		</a>
	</div>
</div>

<?php if ( ! empty( $recent_conversations ) ) : ?>
	<div class="pm-section">
		<div class="pm-section-header">
			<h2 class="pm-heading pm-heading-lg pm-mb-4"><?php _e( 'Recent Conversations', 'partyminder' ); ?></h2>
		</div>
		
		<div class="pm-grid pm-grid-1 pm-gap">
			<?php foreach ( $recent_conversations as $conversation ) : ?>
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
									<?php echo esc_html( $conversation->title ); ?>
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
	</div>
	
<?php else : ?>
	<div class="pm-section pm-text-center">
		<div class="pm-text-6xl pm-mb-4">💬</div>
		<h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e( 'No Conversations Yet', 'partyminder' ); ?></h3>
		<p class="pm-text-muted pm-mb-4"><?php _e( 'Be the first to start a conversation! Share tips, ask questions, or just say hello.', 'partyminder' ); ?></p>
		<?php if ( $user_logged_in ) : ?>
			<a href="<?php echo PartyMinder::get_create_conversation_url(); ?>" class="pm-btn">
				<?php _e( 'Start the First Conversation', 'partyminder' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo add_query_arg( 'redirect_to', urlencode( $_SERVER['REQUEST_URI'] ), PartyMinder::get_login_url() ); ?>" class="pm-btn">
				<?php _e( 'Login to Start Conversations', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<?php if ( $user_logged_in && ! empty( $user_conversations ) ) : ?>
<!-- My Conversations -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'My Conversations', 'partyminder' ); ?></h3>
		<p class="pm-text-muted mt-4"><?php _e( 'Conversations you\'ve started', 'partyminder' ); ?></p>
	</div>
	<?php foreach ( $user_conversations as $conversation ) : ?>
		<div class="pm-mb-4">
			<h4 class="pm-heading pm-heading-sm">
				<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-text-primary">
					<?php echo esc_html( $conversation->title ); ?>
				</a>
			</h4>
			<div class="pm-text-muted">
				<?php echo $conversation->reply_count; ?> <?php _e( 'replies', 'partyminder' ); ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>