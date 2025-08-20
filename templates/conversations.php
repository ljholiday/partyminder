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
	<div class="pm-flex pm-gap-4 pm-flex-wrap">
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
		
		<!-- Circle Filter Buttons -->
		<button class="pm-btn pm-btn-secondary is-active" data-circle="close" role="tab" aria-selected="true" aria-controls="pm-convo-list">
			<?php _e( 'Close Circle', 'partyminder' ); ?>
		</button>
		<button class="pm-btn pm-btn-secondary" data-circle="trusted" role="tab" aria-selected="false" aria-controls="pm-convo-list">
			<?php _e( 'Trusted Circle', 'partyminder' ); ?>
		</button>
		<button class="pm-btn pm-btn-secondary" data-circle="extended" role="tab" aria-selected="false" aria-controls="pm-convo-list">
			<?php _e( 'Extended Circle', 'partyminder' ); ?>
		</button>
	</div>
</div>

<div class="pm-section">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-lg pm-mb-4"><?php _e( 'Conversations', 'partyminder' ); ?></h2>
	</div>
	
	<div id="pm-convo-list" class="pm-conversations-list" aria-live="polite">
		<?php
		// Load conversations directly to avoid duplicate navigation
		$conversations = $recent_conversations;
		include PARTYMINDER_PLUGIN_DIR . 'templates/partials/conversations-list.php';
		?>
	</div>
</div>

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
					<?php echo esc_html( $conversation_manager->get_display_title( $conversation ) ); ?>
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