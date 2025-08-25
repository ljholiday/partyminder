<?php
/**
 * Communities Content Template
 * Main communities listing page
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

$community_manager = new PartyMinder_Community_Manager();

// Get current user info
$current_user = wp_get_current_user();
$user_email   = is_user_logged_in() ? $current_user->user_email : '';

// Get data for the page
$public_communities = $community_manager->get_public_communities( 12 );
$user_communities   = array();

if ( is_user_logged_in() ) {
	$user_communities = $community_manager->get_user_communities( $current_user->ID, 6 );
}

// Get styling options
$primary_color   = get_option( 'partyminder_primary_color', '#667eea' );
$secondary_color = get_option( 'partyminder_secondary_color', '#764ba2' );

// Set up template variables
$page_title       = __( 'Communities', 'partyminder' );
$page_description = __( 'Join communities of fellow hosts and guests to plan amazing events together', 'partyminder' );

// Main content
ob_start();
?>

<!-- Success Message for Community Deletion -->
<?php if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == '1' ) : ?>
	<div class="pm-alert pm-alert-success pm-mb-4">
		<?php _e( 'Community has been successfully deleted.', 'partyminder' ); ?>
	</div>
<?php endif; ?>
<!-- Secondary Menu Bar -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<?php if ( PartyMinder_Feature_Flags::can_user_create_community() ) : ?>
			<a href="<?php echo esc_url( site_url( '/create-community' ) ); ?>" class="pm-btn">
				<?php _e( 'Create Community', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
		<?php if ( is_user_logged_in() && ! empty( $user_communities ) ) : ?>
			<a href="<?php echo home_url( '/my-communities' ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'My Communities', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
		<a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Create Event', 'partyminder' ); ?>
		</a>
		<a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Conversations', 'partyminder' ); ?>
		</a>
		<a href="<?php echo esc_url( PartyMinder::get_dashboard_url() ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Dashboard', 'partyminder' ); ?>
		</a>
	</div>
</div>

<div class="pm-section">

				<?php if ( ! empty( $public_communities ) ) : ?>
					<div class="pm-grid pm-gap">
					<?php foreach ( $public_communities as $community ) : ?>
					<div class="pm-section pm-border pm-p-4">
						<?php if ( ! empty( $community->featured_image ) ) : ?>
							<div class="pm-mb-4">
								<img src="<?php echo esc_url( $community->featured_image ); ?>" alt="<?php echo esc_attr( $community->name ); ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px;">
							</div>
						<?php endif; ?>
						<div class="pm-section-header pm-flex pm-flex-between pm-mb-4">
							<h3 class="pm-heading pm-heading-sm">
								<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-text-primary">
									<?php echo esc_html( $community->name ); ?>
								</a>
							</h3>
							<div class="pm-badge pm-badge-<?php echo $community->privacy === 'public' ? 'success' : 'secondary'; ?>">
								<?php echo esc_html( ucfirst( $community->privacy ) ); ?>
							</div>
						</div>
								<div class="pm-mb-4">
									<div class="pm-flex pm-gap">
										<span class="pm-text-muted"><?php echo (int) $community->member_count; ?> <?php _e( 'members', 'partyminder' ); ?></span>
									</div>
								</div>
							
							<?php if ( $community->description ) : ?>
						<div class="pm-mb-4">
							<p class="pm-text-muted"><?php echo esc_html( wp_trim_words( $community->description, 20 ) ); ?></p>
						</div>
						<?php endif; ?>
						
						<div class="pm-flex pm-flex-between pm-mt-4">
							<div class="pm-stat">
								<div class="pm-stat-number pm-text-primary"><?php echo (int) $community->event_count; ?></div>
								<div class="pm-text-muted"><?php _e( 'Events', 'partyminder' ); ?></div>
							</div>
							
							<?php if ( is_user_logged_in() ) : ?>
								<?php
								$is_member = $community_manager->is_member( $community->id, $current_user->ID );
								?>
								<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" 
									class="pm-btn <?php echo $is_member ? 'pm-btn-secondary' : ''; ?>">
									<?php echo $is_member ? __( 'Member', 'partyminder' ) : __( 'Join', 'partyminder' ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo add_query_arg( 'redirect_to', urlencode( $_SERVER['REQUEST_URI'] ), PartyMinder::get_login_url() ); ?>" class="pm-btn">
									<?php _e( 'Login to Join', 'partyminder' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
					</div>
	<?php else : ?>
		<div class="pm-text-center pm-p-4">
			<p class="pm-text-muted mb-4"><?php _e( 'No public communities yet.', 'partyminder' ); ?></p>
			<p class="pm-text-muted"><?php _e( 'Be the first to create a community!', 'partyminder' ); ?></p>
		</div>
	<?php endif; ?>
</div>
<?php
$main_content = ob_get_clean();

// Get recent activity data
global $wpdb;
$events_table = $wpdb->prefix . 'partyminder_events';
$communities_table = $wpdb->prefix . 'partyminder_communities';
$conversations_table = $wpdb->prefix . 'partyminder_conversations';

// Get recent community events
$recent_events = $wpdb->get_results( $wpdb->prepare(
	"SELECT e.id, e.title, e.slug, e.event_date, e.created_at, c.name as community_name, c.slug as community_slug
	 FROM $events_table e 
	 INNER JOIN $communities_table c ON e.community_id = c.id
	 WHERE c.privacy = 'public' AND e.event_status = 'active'
	 ORDER BY e.created_at DESC 
	 LIMIT %d", 5
) );

// Get recent community conversations
$recent_conversations = $wpdb->get_results( $wpdb->prepare(
	"SELECT conv.id, conv.title, conv.slug, conv.created_at, conv.reply_count, c.name as community_name, c.slug as community_slug
	 FROM $conversations_table conv
	 INNER JOIN $communities_table c ON conv.community_id = c.id
	 WHERE c.privacy = 'public' AND conv.privacy = 'public'
	 ORDER BY conv.created_at DESC 
	 LIMIT %d", 5
) );

// Sidebar content
ob_start();
?>

<!-- Recent Communities Activity -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Recent Activity', 'partyminder' ); ?></h3>
	</div>
	
	<?php if ( ! empty( $recent_events ) ) : ?>
	<div class="pm-activity-section pm-mb-4">
		<h4 class="pm-heading pm-heading-xs pm-mb-2"><?php _e( 'Recent Events', 'partyminder' ); ?></h4>
		<?php foreach ( $recent_events as $event ) : ?>
			<div class="pm-activity-item pm-mb-3">
				<div class="pm-flex pm-gap-2">
					<span class="pm-activity-icon">🎉</span>
					<div class="pm-flex-1">
						<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-text-primary pm-text-sm pm-font-medium">
							<?php echo esc_html( wp_trim_words( $event->title, 6 ) ); ?>
						</a>
						<div class="pm-text-muted pm-text-xs pm-mb-1">
							<?php _e( 'in', 'partyminder' ); ?> 
							<a href="<?php echo home_url( '/communities/' . $event->community_slug ); ?>" class="pm-text-muted">
								<?php echo esc_html( $event->community_name ); ?>
							</a>
						</div>
						<div class="pm-text-muted pm-text-xs">
							<?php 
							$event_date = new DateTime( $event->event_date );
							$now = new DateTime();
							if ( $event_date > $now ) {
								echo $event_date->format( 'M j, g:i A' );
							} else {
								echo human_time_diff( strtotime( $event->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'partyminder' );
							}
							?>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $recent_conversations ) ) : ?>
	<div class="pm-activity-section pm-mb-4">
		<h4 class="pm-heading pm-heading-xs pm-mb-2"><?php _e( 'Recent Conversations', 'partyminder' ); ?></h4>
		<?php foreach ( $recent_conversations as $conversation ) : ?>
			<div class="pm-activity-item pm-mb-3">
				<div class="pm-flex pm-gap-2">
					<span class="pm-activity-icon">💬</span>
					<div class="pm-flex-1">
						<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-text-primary pm-text-sm pm-font-medium">
							<?php echo esc_html( wp_trim_words( $conversation->title, 6 ) ); ?>
						</a>
						<div class="pm-text-muted pm-text-xs pm-mb-1">
							<?php _e( 'in', 'partyminder' ); ?> 
							<a href="<?php echo home_url( '/communities/' . $conversation->community_slug ); ?>" class="pm-text-muted">
								<?php echo esc_html( $conversation->community_name ); ?>
							</a>
						</div>
						<div class="pm-text-muted pm-text-xs">
							<?php if ( $conversation->reply_count > 0 ) : ?>
								<?php echo $conversation->reply_count; ?> <?php _e( 'replies', 'partyminder' ); ?> • 
							<?php endif; ?>
							<?php echo human_time_diff( strtotime( $conversation->created_at ), current_time( 'timestamp' ) ); ?> <?php _e( 'ago', 'partyminder' ); ?>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	
	<?php if ( empty( $recent_events ) && empty( $recent_conversations ) ) : ?>
		<div class="pm-text-center pm-p-4">
			<p class="pm-text-muted pm-text-sm"><?php _e( 'No recent activity yet.', 'partyminder' ); ?></p>
			<p class="pm-text-muted pm-text-sm"><?php _e( 'Join a community to see what\'s happening!', 'partyminder' ); ?></p>
		</div>
	<?php endif; ?>
</div>
<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<?php
// Community creation modal replaced with single-page interface at /create-community
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Community creation now handled by single-page interface at /create-community
	
	// Join community functionality
	const joinBtns = document.querySelectorAll('.join-btn');
	joinBtns.forEach(btn => {
		btn.addEventListener('click', function(e) {
			if (this.classList.contains('member')) {
				return; // Already a member, just redirect
			}
			
			e.preventDefault();
			
			// Check if user is logged in
			if (!partyminder_ajax.current_user.id) {
				return; // Let the login redirect happen
			}
			
			const communityCard = this.closest('.community-card');
			const communityName = communityCard.querySelector('h3 a').textContent;
			
			if (!confirm(partyminder_ajax.strings.confirm_join + ' "' + communityName + '"?')) {
				return;
			}
			
			// Get community ID from URL
			const communityUrl = communityCard.querySelector('h3 a').href;
			const urlParts = communityUrl.split('/');
			const communitySlug = urlParts[urlParts.length - 2] || urlParts[urlParts.length - 1];
			
			// For now, we'll redirect to the community page
			// In Phase 3, this will be proper AJAX
			window.location.href = communityUrl;
		});
	});
});
</script>
