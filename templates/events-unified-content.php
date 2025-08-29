<?php
/**
 * Events Unified Content Template
 * Main events page with tab-based filtering (like conversations page)
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

// Get current user info
$current_user = wp_get_current_user();
$user_logged_in = is_user_logged_in();
$user_email = $user_logged_in ? $current_user->user_email : '';

// Get data for both tabs
$public_events = $event_manager->get_upcoming_events( 20 );
$user_events = array();
$rsvp_events = array();

if ( $user_logged_in ) {
	$user_events = $event_manager->get_user_events( $current_user->ID, 20 );
}

// Get user's RSVP events (copied from my-events-content.php)
if ( $user_email ) {
	global $wpdb;
	$events_table = $wpdb->prefix . 'partyminder_events';
	$guests_table = $wpdb->prefix . 'partyminder_guests';
	
	$query = "SELECT DISTINCT e.*, g.status as rsvp_status FROM $events_table e 
			  INNER JOIN $guests_table g ON e.id = g.event_id 
			  WHERE g.email = %s 
			  AND e.event_status = 'active' 
			  ORDER BY e.event_date ASC";
	
	$rsvp_events = $wpdb->get_results( $wpdb->prepare( $query, $user_email ) );
	
	// Add guest stats to each RSVP event
	foreach ( $rsvp_events as $event ) {
		$event->guest_stats = $event_manager->get_guest_stats( $event->id );
	}
}

// Set up template variables
$page_title       = __( 'Events', 'partyminder' );
$page_description = __( 'Discover amazing events and manage your gatherings', 'partyminder' );

// Main content
ob_start();
?>

<!-- Event Filters/Tabs -->
<?php if ( $user_logged_in ) : ?>
<div class="pm-section pm-mb-4">
	<div class="pm-conversations-nav pm-flex pm-gap-4 pm-flex-wrap">
		<!-- Event Type Filters -->
		<button class="pm-btn pm-btn is-active" data-filter="my-events" role="tab" aria-selected="true" aria-controls="pm-events-list">
			<?php _e( 'My Events', 'partyminder' ); ?>
		</button>
		<button class="pm-btn pm-btn" data-filter="all-events" role="tab" aria-selected="false" aria-controls="pm-events-list">
			<?php _e( 'All Events', 'partyminder' ); ?>
		</button>
		<button class="pm-btn pm-btn" data-filter="rsvp-events" role="tab" aria-selected="false" aria-controls="pm-events-list">
			<?php _e( 'My RSVPs', 'partyminder' ); ?>
		</button>
	</div>
</div>
<?php endif; ?>

<div class="pm-section">
	<div id="pm-events-list" class="pm-grid pm-grid-2 pm-gap">
		<?php if ( $user_logged_in ) : ?>
			<!-- My Events Tab Content (Default) -->
			<div class="pm-events-tab-content" data-tab="my-events">
				<?php if ( ! empty( $user_events ) ) : ?>
					<?php foreach ( $user_events as $event ) : ?>
						<?php
						$event_date  = new DateTime( $event->event_date );
						$is_today    = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d' );
						$is_tomorrow = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d', strtotime( '+1 day' ) );
						$is_past     = $event_date < new DateTime();
						?>
						<div class="pm-section">
							<div class="pm-flex pm-flex-between pm-mb-4">
								<div class="pm-flex-1">
									<h3 class="pm-heading pm-heading-sm pm-mb-2">
										<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-text-primary">
											<?php echo esc_html( $event->title ); ?>
										</a>
									</h3>
									<div class="pm-flex pm-gap pm-flex-wrap pm-mb-2">
										<span class="pm-text-muted">
											<?php if ( $is_today ) : ?>
												<?php _e( 'Today', 'partyminder' ); ?>
											<?php elseif ( $is_tomorrow ) : ?>
												<?php _e( 'Tomorrow', 'partyminder' ); ?>
											<?php elseif ( $is_past ) : ?>
												<?php echo $event_date->format( 'M j' ); ?> (<?php _e( 'Past', 'partyminder' ); ?>)
											<?php else : ?>
												<?php echo $event_date->format( 'M j' ); ?>
											<?php endif; ?>
											<?php if ( $event->event_time ) : ?>
												at <?php echo date( 'g:i A', strtotime( $event->event_date ) ); ?>
											<?php endif; ?>
										</span>
									</div>
								</div>
							</div>
							
							<div class="pm-flex pm-flex-between">
								<div class="pm-stat">
									<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
									<div class="pm-stat-label"><?php _e( 'Going', 'partyminder' ); ?></div>
								</div>
								
								<div class="pm-flex pm-gap">
									<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn pm-btn pm-btn-sm">
										<?php _e( 'View', 'partyminder' ); ?>
									</a>
									<a href="<?php echo PartyMinder::get_edit_event_url( $event->id ); ?>" class="pm-btn pm-btn pm-btn-sm">
										<?php _e( 'Edit', 'partyminder' ); ?>
									</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="pm-text-center pm-p-4">
						<p class="pm-text-muted"><?php _e( 'You haven\'t created any events yet.', 'partyminder' ); ?></p>
						<a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-btn">
							<?php _e( 'Create Your First Event', 'partyminder' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>

			<!-- All Events Tab Content -->
			<div class="pm-events-tab-content pm-hidden" data-tab="all-events">
				<?php if ( ! empty( $public_events ) ) : ?>
					<?php foreach ( $public_events as $event ) : ?>
						<?php
						$event_date  = new DateTime( $event->event_date );
						$is_today    = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d' );
						$is_tomorrow = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d', strtotime( '+1 day' ) );
						$is_past     = $event_date < new DateTime();
						?>
						<div class="pm-section">
							<div class="pm-flex pm-flex-between pm-mb-4">
								<div class="pm-flex-1">
									<h3 class="pm-heading pm-heading-sm pm-mb-2">
										<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-text-primary">
											<?php echo esc_html( $event->title ); ?>
										</a>
									</h3>
									<div class="pm-flex pm-gap pm-flex-wrap pm-mb-2">
										<span class="pm-text-muted">
											<?php if ( $is_today ) : ?>
												<?php _e( 'Today', 'partyminder' ); ?>
											<?php elseif ( $is_tomorrow ) : ?>
												<?php _e( 'Tomorrow', 'partyminder' ); ?>
											<?php elseif ( $is_past ) : ?>
												<?php echo $event_date->format( 'M j' ); ?> (<?php _e( 'Past', 'partyminder' ); ?>)
											<?php else : ?>
												<?php echo $event_date->format( 'M j' ); ?>
											<?php endif; ?>
											<?php if ( $event->event_time ) : ?>
												at <?php echo date( 'g:i A', strtotime( $event->event_date ) ); ?>
											<?php endif; ?>
										</span>
									</div>
								</div>
							</div>
							
							<div class="pm-flex pm-flex-between">
								<div class="pm-stat">
									<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
									<div class="pm-stat-label"><?php _e( 'Going', 'partyminder' ); ?></div>
								</div>
								
								<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn pm-btn pm-btn-sm">
									<?php _e( 'View Details', 'partyminder' ); ?>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="pm-text-center pm-p-4">
						<p class="pm-text-muted"><?php _e( 'No public events found.', 'partyminder' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- My RSVPs Tab Content -->
			<div class="pm-events-tab-content pm-hidden" data-tab="rsvp-events">
				<?php if ( ! empty( $rsvp_events ) ) : ?>
					<?php foreach ( $rsvp_events as $event ) : ?>
						<?php
						$event_date  = new DateTime( $event->event_date );
						$is_today    = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d' );
						$is_tomorrow = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d', strtotime( '+1 day' ) );
						$is_past     = $event_date < new DateTime();
						$badge_text  = array(
							'confirmed' => __( 'Going', 'partyminder' ),
							'maybe'     => __( 'Maybe', 'partyminder' ),
							'declined'  => __( 'Can\'t Go', 'partyminder' ),
							'pending'   => __( 'Pending', 'partyminder' ),
						);
						$badge_class = 'pm-badge-' . ( $event->rsvp_status === 'confirmed' ? 'success' : ( $event->rsvp_status === 'declined' ? 'danger' : 'warning' ) );
						?>
						<div class="pm-section">
							<div class="pm-flex pm-flex-between pm-mb-4">
								<div class="pm-flex-1">
									<h3 class="pm-heading pm-heading-sm pm-mb-2">
										<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-text-primary">
											<?php echo esc_html( $event->title ); ?>
										</a>
									</h3>
									<div class="pm-flex pm-gap pm-flex-wrap pm-mb-2">
										<span class="pm-text-muted">
											<?php if ( $is_today ) : ?>
												<?php _e( 'Today', 'partyminder' ); ?>
											<?php elseif ( $is_tomorrow ) : ?>
												<?php _e( 'Tomorrow', 'partyminder' ); ?>
											<?php elseif ( $is_past ) : ?>
												<?php echo $event_date->format( 'M j' ); ?> (<?php _e( 'Past', 'partyminder' ); ?>)
											<?php else : ?>
												<?php echo $event_date->format( 'M j' ); ?>
											<?php endif; ?>
											<?php if ( $event->event_time ) : ?>
												at <?php echo date( 'g:i A', strtotime( $event->event_date ) ); ?>
											<?php endif; ?>
										</span>
									</div>
								</div>
								<div class="pm-badge <?php echo $badge_class; ?>">
									<?php echo $badge_text[ $event->rsvp_status ] ?? __( 'Unknown', 'partyminder' ); ?>
								</div>
							</div>
							
							<div class="pm-flex pm-flex-between">
								<div class="pm-stat">
									<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
									<div class="pm-stat-label"><?php _e( 'Going', 'partyminder' ); ?></div>
								</div>
								
								<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn pm-btn pm-btn-sm">
									<?php _e( 'View Details', 'partyminder' ); ?>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="pm-text-center pm-p-4">
						<p class="pm-text-muted"><?php _e( 'You haven\'t RSVP\'d to any events yet.', 'partyminder' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<!-- Not logged in - show public events -->
			<?php if ( ! empty( $public_events ) ) : ?>
				<?php foreach ( $public_events as $event ) : ?>
					<?php
					$event_date  = new DateTime( $event->event_date );
					$is_today    = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d' );
					$is_tomorrow = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d', strtotime( '+1 day' ) );
					$is_past     = $event_date < new DateTime();
					?>
					<div class="pm-section">
						<div class="pm-flex pm-flex-between pm-mb-4">
							<div class="pm-flex-1">
								<h3 class="pm-heading pm-heading-sm pm-mb-2">
									<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-text-primary">
										<?php echo esc_html( $event->title ); ?>
									</a>
								</h3>
								<div class="pm-flex pm-gap pm-flex-wrap pm-mb-2">
									<span class="pm-text-muted">
										<?php if ( $is_today ) : ?>
											<?php _e( 'Today', 'partyminder' ); ?>
										<?php elseif ( $is_tomorrow ) : ?>
											<?php _e( 'Tomorrow', 'partyminder' ); ?>
										<?php elseif ( $is_past ) : ?>
											<?php echo $event_date->format( 'M j' ); ?> (<?php _e( 'Past', 'partyminder' ); ?>)
										<?php else : ?>
											<?php echo $event_date->format( 'M j' ); ?>
										<?php endif; ?>
										<?php if ( $event->event_time ) : ?>
											at <?php echo date( 'g:i A', strtotime( $event->event_date ) ); ?>
										<?php endif; ?>
									</span>
								</div>
							</div>
						</div>
						
						<div class="pm-flex pm-flex-between">
							<div class="pm-stat">
								<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
								<div class="pm-stat-label"><?php _e( 'Going', 'partyminder' ); ?></div>
							</div>
							
							<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn pm-btn pm-btn-sm">
								<?php _e( 'View Details', 'partyminder' ); ?>
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="pm-text-center pm-p-4">
					<h3 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'No Events Found', 'partyminder' ); ?></h3>
					<p class="pm-text-muted"><?php _e( 'There are no events to display.', 'partyminder' ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<?php
$main_content = ob_get_clean();

// Consistent sidebar - no switching content
$sidebar_content = '';

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
jQuery(document).ready(function($) {
	// Handle event tab switching (similar to conversations)
	$('.pm-conversations-nav button[data-filter]').on('click', function() {
		const filter = $(this).data('filter');
		
		// Update active button
		$('.pm-conversations-nav button').removeClass('is-active').attr('aria-selected', 'false');
		$(this).addClass('is-active').attr('aria-selected', 'true');
		
		// Show/hide tab content
		$('.pm-events-tab-content').addClass('pm-hidden');
		$(`.pm-events-tab-content[data-tab="${filter}"]`).removeClass('pm-hidden');
	});
});
</script>