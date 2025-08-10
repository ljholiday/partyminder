<?php
/**
 * Community Conversations Template
 * Main conversations listing page - restored with safer code
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
$conversation_manager = new PartyMinder_Conversation_Manager();

// Check if we're viewing a single conversation or topic
$conversation_topic = get_query_var( 'conversation_topic' );
$conversation_slug  = get_query_var( 'conversation_slug' );

if ( $conversation_topic && $conversation_slug ) {
	if ( file_exists( PARTYMINDER_PLUGIN_DIR . 'templates/single-conversation-content.php' ) ) {
		include PARTYMINDER_PLUGIN_DIR . 'templates/single-conversation-content.php';
	}
	return;
}

if ( $conversation_topic ) {
	if ( file_exists( PARTYMINDER_PLUGIN_DIR . 'templates/topic-conversations-content.php' ) ) {
		include PARTYMINDER_PLUGIN_DIR . 'templates/topic-conversations-content.php';
	}
	return;
}

// Get data for the page
$topics         = $conversation_manager->get_topics();
$user_logged_in = is_user_logged_in();

// Set up template variables
$page_title       = __( 'Community Conversations', 'partyminder' );
$page_description = __( 'Connect, share tips, and plan amazing gatherings with fellow hosts and guests', 'partyminder' );

// Main content
ob_start();
?>
<?php if ( ! empty( $topics ) ) : ?>
<div class="pm-section">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Discussion Topics', 'partyminder' ); ?></h2>
		<p class="pm-text-muted"><?php _e( 'Join conversations about hosting and party planning', 'partyminder' ); ?></p>
	</div>
	
	<?php foreach ( $topics as $topic ) : ?>
		<div class="pm-section pm-mb">
			<div class="pm-flex pm-flex-between pm-mb-4">
				<div class="pm-flex pm-gap">
					<span class="pm-text-xl"><?php echo esc_html( $topic->icon ?? '' ); ?></span>
					<div>
						<h3 class="pm-heading pm-heading-sm">
							<a href="<?php echo home_url( '/conversations/' . ( $topic->slug ?? '' ) ); ?>" class="pm-text-primary">
								<?php echo esc_html( $topic->name ?? 'Untitled Topic' ); ?>
							</a>
						</h3>
						<p class="pm-text-muted"><?php echo esc_html( $topic->description ?? '' ); ?></p>
					</div>
				</div>
				<div class="pm-text-center">
					<div class="pm-stat-number pm-text-primary"><?php echo intval( $topic->conversation_count ?? 0 ); ?></div>
					<div class="pm-stat-label"><?php _e( 'conversations', 'partyminder' ); ?></div>
				</div>
			</div>

			<?php
			// Get conversations for this topic safely
			$topic_conversations = array();
			if ( method_exists( $conversation_manager, 'get_conversations_by_topic' ) ) {
				try {
					$topic_conversations = $conversation_manager->get_conversations_by_topic( $topic->id, 3 );
				} catch ( Exception $e ) {
					// Log error but continue
					error_log( 'Error getting conversations for topic ' . $topic->id . ': ' . $e->getMessage() );
				}
			}
			?>

			<?php if ( ! empty( $topic_conversations ) ) : ?>
			<div class="pm-grid pm-gap">
				<?php foreach ( $topic_conversations as $conversation ) : ?>
					<div class="pm-flex pm-flex-between pm-p-4">
						<div class="pm-flex-1">
							<h4 class="pm-heading pm-heading-sm">
								<a href="<?php echo home_url( '/conversations/' . ( $topic->slug ?? 'general' ) . '/' . ( $conversation->slug ?? '' ) ); ?>" class="pm-text-primary">
									<?php echo esc_html( $conversation->title ?? 'Untitled Conversation' ); ?>
								</a>
							</h4>
							<div class="pm-text-muted">
								<?php
								$author_name = $conversation->author_name ?? 'Unknown';
								$date_field  = $conversation->last_reply_date ?? $conversation->created_date ?? date( 'Y-m-d H:i:s' );
								printf(
									__( 'by %1$s • %2$s ago', 'partyminder' ),
									esc_html( $author_name ),
									human_time_diff( strtotime( $date_field ), current_time( 'timestamp' ) )
								);
								?>
							</div>
						</div>
						<div class="pm-text-center">
							<div class="pm-stat-number pm-text-primary"><?php echo intval( $conversation->reply_count ?? 0 ); ?></div>
							<div class="pm-stat-label"><?php _e( 'replies', 'partyminder' ); ?></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			
			<div class="pm-text-center pm-mt-4">
				<a href="<?php echo home_url( '/conversations/' . ( $topic->slug ?? '' ) ); ?>" class="pm-btn pm-btn-secondary">
					<?php printf( __( 'View All %s Conversations', 'partyminder' ), esc_html( $topic->name ?? 'Topic' ) ); ?>
				</a>
			</div>
			<?php else : ?>
			<div class="pm-text-center pm-p-4">
				<p class="pm-text-muted"><?php _e( 'No conversations in this topic yet.', 'partyminder' ); ?></p>
				<?php if ( $user_logged_in ) : ?>
				<a href="<?php echo add_query_arg( array( 'topic_id' => $topic->id ), PartyMinder::get_create_conversation_url() ); ?>" class="pm-btn">
					<?php _e( 'Start First Conversation', 'partyminder' ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
<?php else : ?>
<div class="pm-section pm-text-center">
	<div class="pm-text-xl pm-mb-4"></div>
	<h3 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'No Topics Yet', 'partyminder' ); ?></h3>
	<p class="pm-text-muted mb-4"><?php _e( 'Conversation topics will appear here once they are created.', 'partyminder' ); ?></p>
	<?php if ( $user_logged_in ) : ?>
	<a href="<?php echo PartyMinder::get_create_conversation_url(); ?>" class="pm-btn">
		<?php _e( 'Start First Conversation', 'partyminder' ); ?>
	</a>
	<?php endif; ?>
</div>
<?php endif; ?>

<?php if ( ! $user_logged_in ) : ?>
<div class="pm-section pm-text-center">
	<div class="pm-text-xl pm-mb-4"></div>
	<h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e( 'Join the Conversation', 'partyminder' ); ?></h3>
	<p class="pm-text-muted mb-4"><?php _e( 'Sign in to participate in discussions, ask questions, and share your hosting experiences!', 'partyminder' ); ?></p>
	<div class="pm-flex pm-gap pm-flex-center pm-flex-wrap">
		<a href="<?php echo add_query_arg( 'redirect_to', urlencode( $_SERVER['REQUEST_URI'] ), PartyMinder::get_login_url() ); ?>" class="pm-btn">
			<?php _e( 'Login', 'partyminder' ); ?>
		</a>
		<?php if ( get_option( 'users_can_register' ) ) : ?>
		<a href="
			<?php
			echo add_query_arg(
				array(
					'action'      => 'register',
					'redirect_to' => urlencode( $_SERVER['REQUEST_URI'] ),
				),
				PartyMinder::get_login_url()
			);
			?>
					" class="pm-btn pm-btn-secondary">
			<?php _e( 'Sign Up', 'partyminder' ); ?>
		</a>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>
<!-- Quick Actions (No Heading) -->
<div class="pm-card pm-mb-4">
	<div class="pm-card-body">
		<div class="pm-flex pm-flex-column pm-gap-4">
			<?php if ( $user_logged_in ) : ?>
				<a href="<?php echo PartyMinder::get_create_conversation_url(); ?>" class="pm-btn">
					<?php _e( 'Start Conversation', 'partyminder' ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo add_query_arg( 'redirect_to', urlencode( $_SERVER['REQUEST_URI'] ), PartyMinder::get_login_url() ); ?>" class="pm-btn">
					<?php _e( 'Login to Participate', 'partyminder' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( PartyMinder::get_create_event_url() ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'Create Event', 'partyminder' ); ?>
			</a>
			<a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'Browse Events', 'partyminder' ); ?>
			</a>
			<a href="<?php echo esc_url( PartyMinder::get_dashboard_url() ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( '← Dashboard', 'partyminder' ); ?>
			</a>
		</div>
	</div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>