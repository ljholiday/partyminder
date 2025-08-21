<?php
/**
 * My Events Content Template - Unified System
 * User's events and RSVPs using unified two-column template
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

// Get shortcode attributes and URL parameters
$show_past = filter_var( $atts['show_past'] ?? $_GET['show_past'] ?? false, FILTER_VALIDATE_BOOLEAN );

// Get current user info
$current_user = wp_get_current_user();
$user_email   = '';

// Check if user provided email via URL parameter
if ( isset( $_GET['email'] ) && is_email( $_GET['email'] ) ) {
	$user_email = sanitize_email( $_GET['email'] );
} elseif ( is_user_logged_in() ) {
	$user_email = $current_user->user_email;
}

// Get user's created events (if logged in)
$created_events = array();
if ( is_user_logged_in() ) {
	global $wpdb;
	$events_table = $wpdb->prefix . 'partyminder_events';

	$query = "SELECT * FROM $events_table 
              WHERE author_id = %d 
              AND event_status = 'active'";

	if ( ! $show_past ) {
		$query .= ' AND event_date >= CURDATE()';
	}

	$query .= ' ORDER BY event_date ' . ( $show_past ? 'DESC' : 'ASC' );

	$created_events = $wpdb->get_results( $wpdb->prepare( $query, $current_user->ID ) );

	// Add guest stats to each event
	foreach ( $created_events as $event ) {
		$event->guest_stats = $event_manager->get_guest_stats( $event->id );
	}
}

// Get user's RSVP'd events (by email)
$rsvp_events = array();
if ( $user_email ) {
	global $wpdb;
	$guests_table = $wpdb->prefix . 'partyminder_guests';
	$events_table = $wpdb->prefix . 'partyminder_events';

	$query = "SELECT DISTINCT e.*, g.status as rsvp_status FROM $events_table e 
              INNER JOIN $guests_table g ON e.id = g.event_id 
              WHERE g.email = %s 
              AND e.event_status = 'active'";

	if ( ! $show_past ) {
		$query .= ' AND e.event_date >= CURDATE()';
	}

	$query .= ' ORDER BY e.event_date ' . ( $show_past ? 'DESC' : 'ASC' );

	$rsvp_events = $wpdb->get_results( $wpdb->prepare( $query, $user_email ) );

	// Add guest stats to each event and preserve RSVP status
	foreach ( $rsvp_events as $event ) {
		$event->guest_stats = $event_manager->get_guest_stats( $event->id );
		// RSVP status is already in $event->rsvp_status from the query
	}
}

// Set up template variables
$page_title       = is_user_logged_in()
	? sprintf( __( 'Hi %s!', 'partyminder' ), $current_user->display_name )
	: __( 'My Events', 'partyminder' );
$page_description = $show_past
	? __( 'All your events and RSVPs', 'partyminder' )
	: __( 'Your upcoming events and RSVPs', 'partyminder' );
$breadcrumbs      = array(
	array(
		'title' => __( 'Dashboard', 'partyminder' ),
		'url'   => PartyMinder::get_dashboard_url(),
	),
	array( 'title' => __( 'My Events', 'partyminder' ) ),
);

// Main content
ob_start();
?>

<!-- Secondary Menu Bar -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<?php if ( is_user_logged_in() ) : ?>
			<a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-btn">
				<?php _e( 'Create Event', 'partyminder' ); ?>
			</a>
			<a href="<?php echo PartyMinder::get_profile_url(); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'My Profile', 'partyminder' ); ?>
			</a>
			<?php if ( $show_past ) : ?>
				<a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-btn pm-btn-secondary">
					<?php _e( 'Hide Past Events', 'partyminder' ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo add_query_arg( 'show_past', '1', PartyMinder::get_my_events_url() ); ?>" class="pm-btn pm-btn-secondary">
					<?php _e( 'Show Past Events', 'partyminder' ); ?>
				</a>
			<?php endif; ?>
		<?php else : ?>
			<a href="<?php echo esc_url( add_query_arg( 'redirect_to', get_permalink( get_the_ID() ), PartyMinder::get_login_url() ) ); ?>" class="pm-btn">
				<?php _e( 'Login', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
		<a href="<?php echo esc_url( PartyMinder::get_dashboard_url() ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Dashboard', 'partyminder' ); ?>
		</a>
	</div>
</div>

<!-- Login/Email Prompt for non-logged-in users -->
<?php if ( ! is_user_logged_in() && ! $user_email ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Login to See Your Events', 'partyminder' ); ?></h3>
	</div>
	<p class="pm-text-muted pm-mb"><?php _e( 'Log in to see events you\'ve created and your RSVPs.', 'partyminder' ); ?></p>
	<a href="<?php echo esc_url( add_query_arg( 'redirect_to', get_permalink( get_the_ID() ), PartyMinder::get_login_url() ) ); ?>" class="pm-btn">
		<?php _e( 'Login', 'partyminder' ); ?>
	</a>
</div>

<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Or Find Your RSVPs by Email', 'partyminder' ); ?></h3>
	</div>
	<p class="pm-text-muted pm-mb"><?php _e( 'Enter your email to see events you\'ve RSVP\'d to.', 'partyminder' ); ?></p>
	<form method="get" class="pm-flex pm-gap">
		<input type="email" name="email" class="pm-form-input pm-flex-1" placeholder="<?php esc_attr_e( 'Enter your email address', 'partyminder' ); ?>" required />
		<button type="submit" class="pm-btn"><?php _e( 'Find My RSVPs', 'partyminder' ); ?></button>
	</form>
</div>
<?php endif; ?>

<!-- Created Events Section -->
<?php if ( is_user_logged_in() && ! empty( $created_events ) ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
			<h3 class="pm-heading pm-heading-md pm-text-primary"> <?php _e( 'Events You Created', 'partyminder' ); ?></h3>
			<span class="pm-badge pm-badge-success"><?php echo count( $created_events ); ?></span>
		</div>
	</div>
	
	<div class="pm-grid pm-grid-1 pm-gap">
		<?php foreach ( $created_events as $event ) : ?>
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
							<span class="pm-badge pm-badge-primary"><?php _e( 'Hosting', 'partyminder' ); ?></span>
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
						</div>
					</div>
					<div class="pm-stat pm-text-center">
						<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
						<div class="pm-stat-label"><?php _e( 'Confirmed', 'partyminder' ); ?></div>
					</div>
				</div>
				
				<div class="pm-mb-4">
					<?php if ( $event->excerpt || $event->description ) : ?>
						<p class="pm-text-muted"><?php echo esc_html( wp_trim_words( $event->excerpt ?: $event->description, 15 ) ); ?></p>
					<?php endif; ?>
				</div>
				
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
						<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn pm-btn-secondary">
							<?php _e( 'View', 'partyminder' ); ?>
						</a>
						<a href="<?php echo PartyMinder::get_edit_event_url( $event->id ); ?>" class="pm-btn pm-btn-secondary">
							<?php _e( 'Edit', 'partyminder' ); ?>
						</a>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

<!-- RSVP'd Events Section -->
<?php if ( $user_email && ! empty( $rsvp_events ) ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
			<h3 class="pm-heading pm-heading-md pm-text-primary"> <?php _e( 'Events You\'ve RSVP\'d To', 'partyminder' ); ?></h3>
			<span class="pm-badge pm-badge-primary"><?php echo count( $rsvp_events ); ?></span>
		</div>
	</div>
	
	<div class="pm-grid pm-grid-1 pm-gap">
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
							<span class="pm-badge <?php echo esc_attr( $badge_class ); ?>">
								<?php echo esc_html( $badge_text[ $event->rsvp_status ] ?? __( 'RSVP\'d', 'partyminder' ) ); ?>
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
							• <?php _e( 'Hosted by', 'partyminder' ); ?> <?php echo esc_html( $event->host_email ); ?>
						</div>
					</div>
					<div class="pm-stat pm-text-center">
						<div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
						<div class="pm-stat-label"><?php _e( 'Confirmed', 'partyminder' ); ?></div>
					</div>
				</div>
				
				<div class="pm-mb-4">
					<?php if ( $event->excerpt || $event->description ) : ?>
						<p class="pm-text-muted"><?php echo esc_html( wp_trim_words( $event->excerpt ?: $event->description, 15 ) ); ?></p>
					<?php endif; ?>
				</div>
				
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
						<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-btn pm-btn-secondary">
							<?php _e( 'View', 'partyminder' ); ?>
						</a>
						<?php if ( ! $is_past ) : ?>
							<a href="<?php echo home_url( '/events/' . $event->slug ); ?>#rsvp" class="pm-btn pm-btn-secondary">
								<?php _e( 'Update RSVP', 'partyminder' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

<!-- No Events Message -->
<?php if ( ( is_user_logged_in() && empty( $created_events ) && empty( $rsvp_events ) ) || ( ! is_user_logged_in() && $user_email && empty( $rsvp_events ) ) ) : ?>
<div class="pm-section pm-text-center">
	<div class="pm-text-6xl pm-mb"></div>
	<h3 class="pm-heading pm-heading-md pm-mb"><?php _e( 'No Events Found', 'partyminder' ); ?></h3>
	<?php if ( is_user_logged_in() ) : ?>
		<p class="pm-text-muted pm-mb"><?php _e( 'You haven\'t created any events yet, and no RSVPs found.', 'partyminder' ); ?></p>
		<a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-btn">
			<?php _e( 'Create Your First Event', 'partyminder' ); ?>
		</a>
	<?php else : ?>
		<p class="pm-text-muted"><?php _e( 'No RSVPs found for this email address.', 'partyminder' ); ?></p>
	<?php endif; ?>
</div>
<?php endif; ?>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>


<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Summary', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-stat-list">
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Events Created', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo count( $created_events ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'RSVPs', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo count( $rsvp_events ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Total Events', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo count( $created_events ) + count( $rsvp_events ); ?></span>
		</div>
	</div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>
