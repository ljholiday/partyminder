<?php
/**
 * Single Community Content Template
 * Individual community page
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';

$community_manager    = new PartyMinder_Community_Manager();
$conversation_manager = new PartyMinder_Conversation_Manager();
$event_manager        = new PartyMinder_Event_Manager();

// Get community slug from URL
$community_slug = get_query_var( 'community_slug' );
if ( ! $community_slug ) {
	wp_redirect( PartyMinder::get_communities_url() );
	exit;
}

// Get community
$community = $community_manager->get_community_by_slug( $community_slug );
if ( ! $community ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	return;
}

// Get current user info
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$is_member    = false;
$user_role    = null;

if ( $is_logged_in ) {
	$is_member = $community_manager->is_member( $community->id, $current_user->ID );
	$user_role = $community_manager->get_member_role( $community->id, $current_user->ID );
}

// Get community conversations
$community_conversations = $conversation_manager->get_community_conversations( $community->id, 5 );

// Get community events
$community_events = $event_manager->get_community_events( $community->id, 5 );

// Set up template variables
$page_title       = esc_html( $community->name );
$page_description = '';
$breadcrumbs      = array(
	array(
		'title' => 'Communities',
		'url'   => PartyMinder::get_communities_url(),
	),
	array( 'title' => $community->name ),
);
// No navigation items - using sidebar navigation instead

// Main content
ob_start();
?>

<!-- Community Cover Image -->
<?php if ( ! empty( $community->featured_image ) ) : ?>
<div class="pm-section pm-mb-4">
	<div class="pm-community-cover" style="position: relative; width: 100%; height: 300px; background-image: url('<?php echo esc_url( $community->featured_image ); ?>'); background-size: cover; background-position: center; border-radius: 8px;">
		<div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 20px; border-radius: 0 0 8px 8px;">
			<h1 class="pm-heading pm-heading-lg" style="color: white; margin: 0;"><?php echo esc_html( $community->name ); ?></h1>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Secondary Menu Bar -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<?php if ( ! $is_logged_in ) : ?>
			<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="pm-btn">
				<?php _e( 'Login to Join', 'partyminder' ); ?>
			</a>
		<?php elseif ( $is_member ) : ?>
			<?php if ( $user_role === 'admin' ) : ?>
				<a href="<?php echo esc_url( site_url( '/manage-community?community_id=' . $community->id . '&tab=settings' ) ); ?>" class="pm-btn">
					<?php _e( 'Manage Community', 'partyminder' ); ?>
				</a>
			<?php endif; ?>
			<a href="#" class="pm-btn pm-btn-secondary create-event-btn">
				<?php _e( 'Create Event', 'partyminder' ); ?>
			</a>
			<a href="<?php echo home_url( '/communities/' . $community->slug . '/events' ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'View Events', 'partyminder' ); ?>
			</a>
			<a href="<?php echo home_url( '/communities/' . $community->slug . '/members' ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'View Members', 'partyminder' ); ?>
			</a>
		<?php else : ?>
			<button class="pm-btn join-community-btn" data-community-id="<?php echo esc_attr( $community->id ); ?>">
				<?php _e( 'Join Community', 'partyminder' ); ?>
			</button>
		<?php endif; ?>
	</div>
</div>

<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<div class="pm-flex pm-flex-between">
				<div class="pm-flex pm-gap">
					<?php if ( ! empty( $community->featured_image ) ) : ?>
						<div class="pm-avatar pm-avatar-lg" style="background-image: url('<?php echo esc_url( $community->featured_image ); ?>'); background-size: cover; background-position: center;">
						</div>
					<?php else : ?>
						<div class="pm-avatar pm-avatar-lg">
							<?php echo strtoupper( substr( $community->name, 0, 2 ) ); ?>
						</div>
					<?php endif; ?>
					<div>
						<div class="pm-flex pm-gap pm-text-muted pm-mb-2">
							<span>Community</span>
							<span><?php echo date( 'M Y', strtotime( $community->created_at ) ); ?></span>
							<?php if ( $is_member ) : ?>
								<span class="pm-badge pm-badge-<?php echo $user_role === 'admin' ? 'primary' : 'success'; ?>">
									<?php echo esc_html( ucfirst( $user_role ) ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div>
					<span class="pm-badge pm-badge-secondary">
						<?php echo esc_html( ucfirst( $community->privacy ) ); ?>
					</span>
				</div>
			</div>
		</div>
		
		<?php if ( $community->description ) : ?>
		<div class="pm-card-body">
			<div class="pm-text-muted">
				<?php echo wpautop( esc_html( $community->description ) ); ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>


<!-- Community Events Section -->
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<div class="pm-flex pm-flex-between">
				<h3 class="pm-heading pm-heading-md">Community Events</h3>
				<?php if ( $is_member ) : ?>
					<div>
						<a href="<?php echo PartyMinder::get_create_community_event_url(); ?>?community_id=<?php echo $community->id; ?>" class="pm-btn pm-btn-secondary">
							Create Event
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="pm-card-body">
			<?php if ( ! empty( $community_events ) ) : ?>
				<div class="pm-flex pm-gap pm-flex-column">
					<?php foreach ( $community_events as $event ) : ?>
						<?php
						$event_date = new DateTime( $event->event_date );
						$today      = new DateTime();
						$is_past    = $event_date < $today;
						$is_today   = $event_date->format( 'Y-m-d' ) === $today->format( 'Y-m-d' );
						
						$status_class = $is_past ? 'past' : ( $is_today ? 'today' : 'upcoming' );
						$status_text  = $is_past ? __( 'Past', 'partyminder' ) : ( $is_today ? __( 'Today', 'partyminder' ) : __( 'Upcoming', 'partyminder' ) );
						?>
						<div class="pm-flex pm-flex-between pm-p-4">
							<div class="pm-flex-1">
								<div class="pm-flex pm-gap pm-mb-2">
									<span class="pm-badge pm-badge-<?php echo $status_class; ?>">
										<?php echo $status_text; ?>
									</span>
									<?php if ( $event->privacy === 'private' ) : ?>
										<span class="pm-badge pm-badge-secondary"><?php _e( 'Private', 'partyminder' ); ?></span>
									<?php endif; ?>
								</div>
								<h4 class="pm-heading pm-heading-sm pm-mb-2">
									<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-text-primary">
										<?php echo esc_html( $event->title ); ?>
									</a>
								</h4>
								<div class="pm-text-muted">
									<?php
									if ( $is_today ) {
										_e( 'Today', 'partyminder' );
									} elseif ( $is_past ) {
										echo $event_date->format( 'M j, Y' );
									} else {
										echo $event_date->format( 'M j, Y' );
									}
									
									if ( $event->event_time ) {
										echo ' at ' . date( 'g:i A', strtotime( $event->event_date ) );
									}
									
									if ( $event->venue_info ) {
										echo ' • ' . esc_html( $event->venue_info );
									}
									?>
								</div>
							</div>
							<div class="pm-text-center">
								<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
								<div class="pm-stat-label"><?php _e( 'Going', 'partyminder' ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				
				<div class="pm-card-footer pm-text-center">
					<a href="<?php echo esc_url( home_url( '/communities/' . $community->slug . '/events' ) ); ?>" class="pm-btn pm-btn-secondary">
						<?php _e( 'View All Events', 'partyminder' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="pm-text-center pm-p-4">
					<p class="pm-text-muted pm-mb-4">
						<?php if ( $is_member ) : ?>
							<?php _e( 'No community events yet. Be the first to create an event for your community!', 'partyminder' ); ?>
						<?php else : ?>
							<?php _e( 'This community hasn\'t created any events yet.', 'partyminder' ); ?>
						<?php endif; ?>
					</p>
					<?php if ( $is_member ) : ?>
						<a href="<?php echo PartyMinder::get_create_community_event_url(); ?>?community_id=<?php echo $community->id; ?>" class="pm-btn">
							<?php _e( 'Create First Event', 'partyminder' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Community Conversations Section -->
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<div class="pm-flex pm-flex-between">
				<h3 class="pm-heading pm-heading-md">Community Conversations</h3>
				<?php if ( $is_member ) : ?>
					<div>
						<a href="<?php echo esc_url( site_url( '/create-conversation?community_id=' . $community->id ) ); ?>" class="pm-btn pm-btn-secondary">
							Start Conversation
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="pm-card-body">
			<?php if ( ! empty( $community_conversations ) ) : ?>
				<div class="pm-flex pm-gap pm-flex-column">
					<?php foreach ( $community_conversations as $conversation ) : ?>
						<div class="pm-flex pm-flex-between pm-p-4">
							<div class="pm-flex-1">
								<div class="pm-flex pm-gap">
									<?php if ( $conversation->is_pinned ) : ?>
										<span class="pm-badge pm-badge-secondary">Pinned</span>
									<?php endif; ?>
									<h4 class="pm-heading pm-heading-sm">
										<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-text-primary">
											<?php echo esc_html( $conversation_manager->get_display_title( $conversation, false ) ); ?>
										</a>
									</h4>
								</div>
								<div class="pm-text-muted">
									<?php
									printf(
										__( 'by %1$s • %2$s ago', 'partyminder' ),
										esc_html( $conversation->author_name ),
										human_time_diff( strtotime( $conversation->last_reply_date ), current_time( 'timestamp' ) )
									);
									?>
								</div>
							</div>
							<div class="pm-text-center">
								<div class="pm-stat-number pm-text-primary"><?php echo $conversation->reply_count; ?></div>
								<div class="pm-stat-label"><?php _e( 'replies', 'partyminder' ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="pm-card-footer pm-text-center">
					<a href="<?php echo esc_url( home_url( '/communities/' . $community->slug . '/conversations' ) ); ?>" class="pm-btn pm-btn-secondary">
						<?php _e( 'View All Conversations', 'partyminder' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="pm-text-center pm-p-4">
					<h4 class="pm-heading pm-heading-sm pm-mb"><?php _e( 'No Conversations Yet', 'partyminder' ); ?></h4>
					<p class="pm-text-muted"><?php _e( 'Be the first to start a discussion in this community!', 'partyminder' ); ?></p>
					<?php if ( $is_member ) : ?>
						<div class="pm-mt-4">
							<a href="<?php echo esc_url( site_url( '/create-conversation?community_id=' . $community->id ) ); ?>" class="pm-btn">
								<?php _e( 'Start First Conversation', 'partyminder' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<!-- Recent Activity -->
<?php if ( ! empty( $community_conversations ) ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Recent Activity', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-text-muted">
		<?php
		$recent_conversation = $community_conversations[0];
		$time_ago = human_time_diff( strtotime( $recent_conversation->last_reply_date ), current_time( 'timestamp' ) );
		?>
		<p class="pm-mb-2"><?php printf( __( 'Last conversation: %s ago', 'partyminder' ), $time_ago ); ?></p>
		<p class="pm-mb-2"><?php printf( __( 'Most recent: "%s"', 'partyminder' ), esc_html( $conversation_manager->get_display_title( $recent_conversation, false ) ) ); ?></p>
		<p><?php printf( __( 'By %s', 'partyminder' ), esc_html( $recent_conversation->author_name ) ); ?></p>
	</div>
</div>
<?php endif; ?>

<!-- Community Stats -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Community Stats', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-stat-list">
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Members', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo (int) $community->member_count; ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Events', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo (int) $community->event_count; ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Conversations', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo count( $community_conversations ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Privacy', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo esc_html( ucfirst( $community->privacy ) ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Created', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo date( 'M Y', strtotime( $community->created_at ) ); ?></span>
		</div>
		<?php if ( $community->location ) : ?>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Location', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo esc_html( $community->location ); ?></span>
		</div>
		<?php endif; ?>
	</div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Join community button with AJAX
	const joinBtn = document.querySelector('.join-community-btn');
	if (joinBtn) {
		joinBtn.addEventListener('click', function(e) {
			e.preventDefault();
			
			const communityId = this.getAttribute('data-community-id');
			const communityName = '<?php echo esc_js( $community->name ); ?>';
			
			if (!confirm('Are you sure you want to join "' + communityName + '"?')) {
				return;
			}
			
			// Show loading state
			const originalText = this.innerHTML;
			this.innerHTML = 'Loading...';
			this.disabled = true;
			
			// Make AJAX request
			jQuery.ajax({
				url: partyminder_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'partyminder_join_community',
					community_id: communityId,
					nonce: partyminder_ajax.community_nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						alert(response.data.message);
						// Reload page to update UI
						window.location.reload();
					} else {
						alert(response.data || 'Error occurred');
						// Restore button
						joinBtn.innerHTML = originalText;
						joinBtn.disabled = false;
					}
				},
				error: function() {
					alert('Error occurred');
					// Restore button
					joinBtn.innerHTML = originalText;
					joinBtn.disabled = false;
				}
			});
		});
	}
	
	// Create event button - redirect to create event page with community context
	const createEventBtn = document.querySelector('.create-event-btn');
	if (createEventBtn) {
		createEventBtn.addEventListener('click', function(e) {
			e.preventDefault();
			// Redirect to the create event page
			window.location.href = '<?php echo esc_url( site_url( '/create-event' ) ); ?>?community_id=<?php echo intval( $community->id ); ?>';
		});
	}
});
</script>
