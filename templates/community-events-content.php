<?php
/**
 * Community Events Content Template
 * Events view for individual community - uses two-column layout
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';

$community_manager = new PartyMinder_Community_Manager();
$event_manager     = new PartyMinder_Event_Manager();

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

// Check if user can view events
$can_view_events = true;
if ( $community->visibility === 'private' && ! $is_member ) {
	$can_view_events = false;
}

// Get community events (if allowed to view)
$events      = array();
$event_count = 0;
if ( $can_view_events ) {
	$events = $event_manager->get_community_events( $community->id, 20 );
	$event_count = count( $events );
}

// Set up template variables
$page_title       = sprintf( __( 'Community Events - %s', 'partyminder' ), esc_html( $community->name ) );
$page_description = sprintf( __( 'Events organized by the %s community. Join community events and connect with other members.', 'partyminder' ), esc_html( $community->name ) );

// Main content
ob_start();
?>

<!-- Secondary Menu Bar -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<?php if ( $is_member ) : ?>
			<button class="pm-btn pm-create-event-btn">
				<?php _e( 'Create Event', 'partyminder' ); ?>
			</button>
			<a href="<?php echo home_url( '/communities/' . $community->slug . '/members' ); ?>" class="pm-btn pm-btn">
				<?php _e( 'View Members', 'partyminder' ); ?>
			</a>
		<?php elseif ( $is_logged_in ) : ?>
			<button class="pm-btn pm-join-community-btn" data-community-id="<?php echo esc_attr( $community->id ); ?>">
				<?php _e( 'Join Community', 'partyminder' ); ?>
			</button>
		<?php else : ?>
			<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="pm-btn">
				<?php _e( 'Login to Join', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
		<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-btn pm-btn">
			<?php _e( 'Back to Community', 'partyminder' ); ?>
		</a>
		<a href="<?php echo home_url( '/communities/' . $community->slug . '/members' ); ?>" class="pm-btn pm-btn">
			<?php _e( 'Members', 'partyminder' ); ?>
		</a>
	</div>
</div>

<div class="pm-section pm-mb-4">

	<!-- Page Header -->
	<div class="pm-mb-4">
		<h1 class="pm-heading pm-heading-lg pm-text-primary"><?php _e( 'Community Events', 'partyminder' ); ?></h1>
		<p class="pm-text-muted">
			<?php printf( __( '%1$d events in %2$s', 'partyminder' ), $event_count, esc_html( $community->name ) ); ?>
		</p>
	</div>

	<?php if ( ! $can_view_events ) : ?>
		<!-- Private Community - No Access -->
		<div class="pm-card pm-text-center">
			<div class="pm-card-body">
				<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Private Community', 'partyminder' ); ?></h3>
				<p class="pm-mb-4"><?php _e( 'This community\'s events are private. You need to be a member to view community events.', 'partyminder' ); ?></p>
				
				<?php if ( ! $is_logged_in ) : ?>
					<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="pm-btn">
						<?php _e( 'Login to Join', 'partyminder' ); ?>
					</a>
				<?php else : ?>
					<button class="pm-btn pm-join-community-btn" data-community-id="<?php echo esc_attr( $community->id ); ?>">
						<?php _e( 'Join Community', 'partyminder' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		
	<?php elseif ( empty( $events ) ) : ?>
		<!-- No Events Yet -->
		<div class="pm-card pm-text-center">
			<div class="pm-card-body">
				<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'No Events Yet', 'partyminder' ); ?></h3>
				<p class="pm-mb-4"><?php _e( 'This community hasn\'t created any events yet. Be the first to plan something amazing!', 'partyminder' ); ?></p>
				
				<?php if ( $is_member ) : ?>
					<button class="pm-btn pm-create-event-btn">
						<?php _e( 'Create First Event', 'partyminder' ); ?>
					</button>
				<?php elseif ( ! $is_logged_in ) : ?>
					<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="pm-btn">
						<?php _e( 'Login to Join', 'partyminder' ); ?>
					</a>
				<?php else : ?>
					<button class="pm-btn pm-join-community-btn" data-community-id="<?php echo esc_attr( $community->id ); ?>">
						<?php _e( 'Join to Create Events', 'partyminder' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		
	<?php else : ?>
		<!-- Event Filters -->
		<div class="pm-flex pm-gap-4 pm-mb-4">
			<span class="pm-text-muted"><?php _e( 'Filter:', 'partyminder' ); ?></span>
			<button class="pm-filter-button pm-btn pm-active" data-filter="all">
				<?php _e( 'All Events', 'partyminder' ); ?>
			</button>
			<button class="pm-filter-button pm-btn" data-filter="upcoming">
				<?php _e( 'Upcoming', 'partyminder' ); ?>
			</button>
			<button class="pm-filter-button pm-btn" data-filter="past">
				<?php _e( 'Past Events', 'partyminder' ); ?>
			</button>
		</div>

		<!-- Events List -->
		<div class="pm-grid pm-grid-1 pm-gap">
			<?php foreach ( $events as $event ) : ?>
				<?php
				$event_date = new DateTime( $event->event_date );
				$today      = new DateTime();
				$is_past    = $event_date < $today;
				$is_today   = $event_date->format( 'Y-m-d' ) === $today->format( 'Y-m-d' );
				$is_tomorrow = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d', strtotime( '+1 day' ) );

				$status_class = $is_past ? 'past' : ( $is_today ? 'today' : 'upcoming' );
				$status_text  = $is_past ? __( 'Past', 'partyminder' ) : ( $is_today ? __( 'Today', 'partyminder' ) : __( 'Upcoming', 'partyminder' ) );
				?>
				
				<div class="pm-event-card pm-section" data-filter-tags="all <?php echo $status_class; ?>">
					<div class="pm-flex pm-flex-between pm-mb-4">
						<div class="pm-flex-1">
							<h3 class="pm-heading pm-heading-sm pm-mb-2">
								<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-text-primary">
									<?php echo esc_html( $community->name . ': ' . $event->title ); ?>
								</a>
							</h3>
							<div class="pm-flex pm-gap pm-flex-wrap pm-mb-2">
								<span class="pm-badge pm-badge-<?php echo $status_class; ?>">
									<?php echo $status_text; ?>
								</span>
								<?php if ( $event->privacy === 'private' ) : ?>
									<span class="pm-badge pm-badge-secondary"><?php _e( 'Private', 'partyminder' ); ?></span>
								<?php endif; ?>
							</div>
							<div class="pm-text-muted">
								<?php if ( $is_today ) : ?>
									<?php _e( 'Today', 'partyminder' ); ?>
								<?php elseif ( $is_tomorrow ) : ?>
									<?php _e( 'Tomorrow', 'partyminder' ); ?>
								<?php elseif ( $is_past ) : ?>
									<?php echo $event_date->format( 'M j, Y' ); ?> (<?php _e( 'Past', 'partyminder' ); ?>)
								<?php else : ?>
									<?php echo $event_date->format( 'M j, Y' ); ?>
								<?php endif; ?>
								<?php if ( $event->event_time ) : ?>
									at <?php echo date( 'g:i A', strtotime( $event->event_date ) ); ?>
								<?php endif; ?>
								<?php if ( $event->venue_info ) : ?>
									• <?php echo esc_html( $event->venue_info ); ?>
								<?php endif; ?>
								• <?php PartyMinder_Member_Display::event_host_display( $event, array( 'avatar_size' => 20 ) ); ?>
							</div>
						</div>
						<div class="pm-stat pm-text-center">
							<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
							<div class="pm-stat-label"><?php _e( 'Going', 'partyminder' ); ?></div>
						</div>
					</div>
					
					<?php if ( $event->excerpt || $event->description ) : ?>
					<div class="pm-mb-4">
						<p class="pm-text-muted"><?php echo esc_html( wp_trim_words( $event->excerpt ?: $event->description, 15 ) ); ?></p>
					</div>
					<?php endif; ?>
					
					<div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
						<div class="pm-flex pm-gap pm-flex-wrap">
							<div class="pm-stat pm-text-center">
								<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->maybe; ?></div>
								<div class="pm-stat-label"><?php _e( 'Maybe', 'partyminder' ); ?></div>
							</div>
							<div class="pm-stat pm-text-center">
								<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->pending; ?></div>
								<div class="pm-stat-label"><?php _e( 'Pending', 'partyminder' ); ?></div>
							</div>
						</div>
						
						<div class="pm-flex pm-gap">
							<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn pm-btn">
								<?php echo $is_past ? __( 'View', 'partyminder' ) : __( 'RSVP', 'partyminder' ); ?>
							</a>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>
</div>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<!-- Event Stats -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Event Overview', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-stat-list">
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Total Events', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo $event_count; ?></span>
		</div>
		<?php if ( $can_view_events && !empty( $events ) ) : ?>
		<?php
		$upcoming_count = 0;
		$past_count = 0;
		$today = new DateTime();
		foreach ( $events as $event ) {
			$event_date = new DateTime( $event->event_date );
			if ( $event_date < $today ) {
				$past_count++;
			} else {
				$upcoming_count++;
			}
		}
		?>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Upcoming', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo $upcoming_count; ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Past Events', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo $past_count; ?></span>
		</div>
		<?php endif; ?>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Community', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo esc_html( $community->name ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Members', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo $community->member_count; ?></span>
		</div>
	</div>
</div>

<!-- Community Info -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'About Community', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-text-muted">
		<?php if ( $community->description ) : ?>
			<p class="pm-mb-2"><?php echo esc_html( $community->description ); ?></p>
		<?php else : ?>
			<p class="pm-mb-2"><?php _e( 'A community for members to plan and attend events together.', 'partyminder' ); ?></p>
		<?php endif; ?>
		<p class="pm-mb-2"><?php printf( __( 'Privacy: %s', 'partyminder' ), esc_html( ucfirst( $community->visibility ) ) ); ?></p>
		<p><?php printf( __( 'Created: %s', 'partyminder' ), date( 'M Y', strtotime( $community->created_at ) ) ); ?></p>
	</div>
</div>

<!-- Event Tips -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Event Tips', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-text-muted">
		<p class="pm-mb-2"><?php _e( 'Browse upcoming events to find activities that interest you.', 'partyminder' ); ?></p>
		<p class="pm-mb-2"><?php _e( 'RSVP early to secure your spot at popular events.', 'partyminder' ); ?></p>
		<?php if ( $is_member ) : ?>
		<p class="pm-mb-2"><?php _e( 'Create your own events to share activities with community members.', 'partyminder' ); ?></p>
		<?php else : ?>
		<p class="pm-mb-2"><?php _e( 'Join the community to create your own events and connect with other members.', 'partyminder' ); ?></p>
		<?php endif; ?>
		<p><?php _e( 'Check event details and location before attending.', 'partyminder' ); ?></p>
	</div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
jQuery(document).ready(function($) {
	// Event filter functionality
	$('.pm-filter-button').on('click', function() {
		const filter = $(this).data('filter');
		
		// Update active button
		$('.pm-filter-button').removeClass('pm-active');
		$(this).addClass('pm-active');
		
		// Filter events
		$('.pm-event-card').each(function() {
			const filterTags = $(this).data('filter-tags');
			if (filter === 'all' || filterTags.includes(filter)) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	});
	
	// Join community button
	$('.pm-join-community-btn').on('click', function(e) {
		e.preventDefault();
		
		const communityId = $(this).data('community-id');
		const communityName = '<?php echo esc_js( $community->name ); ?>';
		
		if (!confirm('<?php _e( 'Join community', 'partyminder' ); ?> "' + communityName + '"?')) {
			return;
		}
		
		const $btn = $(this);
		const originalText = $btn.text();
		$btn.text('<?php _e( 'Joining...', 'partyminder' ); ?>').prop('disabled', true);
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: {
				action: 'partyminder_join_community',
				community_id: communityId,
				nonce: '<?php echo wp_create_nonce( 'partyminder_community_action' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					window.location.reload();
				} else {
					alert(response.data || '<?php _e( 'Error joining community', 'partyminder' ); ?>');
					$btn.text(originalText).prop('disabled', false);
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
				$btn.text(originalText).prop('disabled', false);
			}
		});
	});
	
	// Create event button - redirect to community event creation
	$('.pm-create-event-btn').on('click', function(e) {
		e.preventDefault();
		window.location.href = '<?php echo PartyMinder::get_create_community_event_url(); ?>?community_id=<?php echo $community->id; ?>';
	});
});
</script>
