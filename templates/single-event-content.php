<?php
/**
 * Single Event Content Template
 * Single event page
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get event data from global variable set by main plugin
$event = $GLOBALS['partyminder_current_event'] ?? null;

if ( ! $event ) {
	echo '<p>Event not found.</p>';
	return;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-activity-tracker.php';

$event_manager        = new PartyMinder_Event_Manager();
$guest_manager        = new PartyMinder_Guest_Manager();
$conversation_manager = new PartyMinder_Conversation_Manager();

// Mark event as read for logged-in users
if ( is_user_logged_in() ) {
	PartyMinder_Activity_Tracker::track_user_activity( get_current_user_id(), 'events', $event->id );
}

// Get additional event data
$event->guest_stats  = $event_manager->get_guest_stats( $event->id );
$event_conversations = $conversation_manager->get_event_conversations( $event->id );

$event_date  = new DateTime( $event->event_date );
$is_today    = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d' );
$is_tomorrow = $event_date->format( 'Y-m-d' ) === date( 'Y-m-d', strtotime( '+1 day' ) );
$is_past     = $event_date < new DateTime();

// Check if user is event host
$current_user  = wp_get_current_user();
$is_event_host = ( is_user_logged_in() && $current_user->ID == $event->author_id ) ||
				( $current_user->user_email == $event->host_email ) ||
				current_user_can( 'edit_others_posts' );


// Set up template variables
$page_title       = esc_html( $event->title );
$page_description = '';

// Main content
ob_start();
?>

<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
				<div class="pm-flex-1">
					<h1 class="pm-heading pm-heading-lg pm-mb-2"><?php echo esc_html( $event->title ); ?></h1>
					<div class="pm-flex pm-gap pm-flex-wrap">
						<?php if ( $is_past ) : ?>
							<div class="pm-badge pm-badge-secondary">Past Event</div>
						<?php elseif ( $is_today ) : ?>
							<div class="pm-badge pm-badge-success">Today</div>
						<?php elseif ( $is_tomorrow ) : ?>
							<div class="pm-badge pm-badge-warning">Tomorrow</div>
						<?php endif; ?>
						<?php if ( $event->privacy === 'private' ) : ?>
							<div class="pm-badge pm-badge-danger">Private Event</div>
						<?php else : ?>
							<div class="pm-badge pm-badge-primary">Public Event</div>
						<?php endif; ?>
					</div>
				</div>
				
				<?php if ( $is_event_host ) : ?>
				<div class="pm-flex pm-gap">
					<a href="<?php echo esc_url( PartyMinder::get_manage_event_url( $event->id ) ); ?>" class="pm-btn">
						<?php _e( 'Manage Event', 'partyminder' ); ?>
					</a>
				</div>
				<?php endif; ?>
			</div>
		</div>
		
		<div class="pm-card-body">
			<div class="pm-grid pm-grid-2 pm-gap pm-mb-4">
				<div class="pm-flex pm-gap">
					<strong>Host:</strong>
					<?php PartyMinder_Member_Display::event_host_display( $event, array( 'prefix' => '', 'avatar_size' => 24 ) ); ?>
				</div>
				
				<div class="pm-flex pm-gap">
					<strong>Date:</strong>
					<span>
						<?php if ( $is_today ) : ?>
							Today
						<?php elseif ( $is_tomorrow ) : ?>
							Tomorrow
						<?php else : ?>
							<?php echo $event_date->format( 'l, F j, Y' ); ?>
						<?php endif; ?>
					</span>
				</div>
				
				<div class="pm-flex pm-gap">
					<strong>Time:</strong>
					<span><?php echo $event_date->format( 'g:i A' ); ?></span>
				</div>
				
				<?php if ( $event->venue_info ) : ?>
				<div class="pm-flex pm-gap">
					<strong>Location:</strong>
					<span><?php echo esc_html( $event->venue_info ); ?></span>
				</div>
				<?php endif; ?>
				
				<div class="pm-flex pm-gap">
					<strong>Guests:</strong>
					<span>
						<?php echo $event->guest_stats->confirmed ?? 0; ?> confirmed
						<?php if ( $event->guest_limit > 0 ) : ?>
							of <?php echo $event->guest_limit; ?> max
						<?php endif; ?>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if ( $event->featured_image ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<img src="<?php echo esc_url( $event->featured_image ); ?>" alt="<?php echo esc_attr( $event->title ); ?>" style="width: 100%; height: auto;">
	</div>
</div>
<?php endif; ?>


<?php if ( $event->description ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">About This Event</h3>
		</div>
		<div class="pm-card-body">
			<?php echo wpautop( esc_html( $event->description ) ); ?>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if ( $event->host_notes ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">Host Notes</h3>
		</div>
		<div class="pm-card-body">
			<?php echo wpautop( esc_html( $event->host_notes ) ); ?>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if ( $is_event_host && ! $is_past ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md"><?php _e( 'Event Management', 'partyminder' ); ?></h3>
		</div>
		<div class="pm-card-body">
			<p class="pm-text-muted pm-mb-4">
				<?php _e( 'Manage event settings, send invitations, and track RSVPs in the management interface.', 'partyminder' ); ?>
			</p>
			<a href="<?php echo add_query_arg( 'event_id', $event->id, home_url( '/manage-event' ) ); ?>" class="pm-btn pm-btn-primary">
				<?php _e( 'Manage This Event', 'partyminder' ); ?>
			</a>
		</div>
	</div>
</div>
<?php endif; ?>

<div class="pm-section">
	<div class="pm-card">
		<div class="pm-card-header">
			<div class="pm-flex pm-flex-between">
				<h3 class="pm-heading pm-heading-md">Event Conversations</h3>
				<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo add_query_arg( 'event_id', $event->id, PartyMinder::get_create_conversation_url() ); ?>" class="pm-btn pm-btn-sm">
					Create Conversation
				</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="pm-card-body">
			<?php if ( ! empty( $event_conversations ) ) : ?>
				<?php foreach ( $event_conversations as $conversation ) : ?>
					<div class="pm-mb-4">
						<div class="pm-flex pm-flex-between pm-mb-2">
							<h4 class="pm-heading pm-heading-sm">
								<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-text-primary">
									<?php echo esc_html( $conversation_manager->get_display_title( $conversation, false ) ); ?>
								</a>
							</h4>
							<div class="pm-stat pm-text-center">
								<div class="pm-stat-number pm-text-primary"><?php echo $conversation->reply_count ?? 0; ?></div>
								<div class="pm-stat-label">Replies</div>
							</div>
						</div>
						<div class="pm-text-muted pm-mb-2">
							<?php
							$content_preview = wp_trim_words( strip_tags( $conversation->content ), 15, '...' );
							echo esc_html( $content_preview );
							?>
						</div>
						<div class="pm-text-muted">
							by <?php echo esc_html( $conversation->author_name ); ?> • <?php echo human_time_diff( strtotime( $conversation->last_reply_date ), current_time( 'timestamp' ) ); ?> ago
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="pm-text-center pm-p-4">
					<p class="pm-text-muted">No conversations started yet for this event.</p>
					<p class="pm-text-muted">Be the first to start planning and discussing ideas!</p>
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



<?php if ( ! $is_past && ! $is_event_host ) : ?>
<div class="pm-section pm-mb" id="rsvp">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md">RSVP for this Event</h3>
		</div>
		<div class="pm-card-body">
			<?php echo do_shortcode( '[partyminder_rsvp_form event_id="' . $event->id . '"]' ); ?>
		</div>
	</div>
</div>
<?php endif; ?>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
function shareEvent() {
	const url = window.location.href;
	const title = '<?php echo esc_js( $event->title ); ?>';

	if (navigator.share) {
		navigator.share({
			title: title,
			url: url
		});
	} else if (navigator.clipboard) {
		navigator.clipboard.writeText(url).then(function() {
			alert('Event URL copied to clipboard!');
		});
	} else {
		window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
	}
}
</script>
