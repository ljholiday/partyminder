<?php
/**
 * Dashboard Content Template - Clean Mobile-First Rebuild
 * Your PartyMinder home with conversations and navigation
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-profile-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-activity-tracker.php';

$event_manager        = new PartyMinder_Event_Manager();
$guest_manager        = new PartyMinder_Guest_Manager();
$conversation_manager = new PartyMinder_Conversation_Manager();

// Get current user info
$current_user   = wp_get_current_user();
$user_logged_in = is_user_logged_in();

// Get user profile data if logged in
$profile_data = null;
if ( $user_logged_in ) {
	$profile_data = PartyMinder_Profile_Manager::get_user_profile( $current_user->ID );
}

// Get user's recent activity
$recent_events = array();
if ( $user_logged_in ) {
	global $wpdb;
	$events_table = $wpdb->prefix . 'partyminder_events';

	// Get user's 3 most recent events (created or RSVP'd)
	$recent_events = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DISTINCT e.*, 'created' as relationship_type FROM $events_table e 
         WHERE e.author_id = %d AND e.event_status = 'active'
         UNION
         SELECT DISTINCT e.*, 'rsvpd' as relationship_type FROM $events_table e
         INNER JOIN {$wpdb->prefix}partyminder_guests g ON e.id = g.event_id
         WHERE g.email = %s AND e.event_status = 'active'
         ORDER BY event_date DESC
         LIMIT 3",
			$current_user->ID,
			$current_user->user_email
		)
	);
}

// Get recent conversations from user's close circle for dashboard
$recent_conversations = array();
if ( $user_logged_in ) {
	require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-circle-scope.php';
	$scope = PartyMinder_Circle_Scope::resolve_conversation_scope( $current_user->ID, 'close' );
	$recent_conversations = $conversation_manager->get_conversations_by_scope( $scope, '', 1, 3 );
}

// Get recent event conversations for dashboard, grouped by event
$recent_event_conversations = $conversation_manager->get_event_conversations( null, 10 );

// Get recent community conversations for dashboard, grouped by community
$recent_community_conversations = $conversation_manager->get_community_conversations( null, 10 );

// Group conversations by event
$conversations_by_event     = array();
$conversations_by_community = array();
if ( ! empty( $recent_event_conversations ) ) {
	foreach ( $recent_event_conversations as $conversation ) {
		$event_key = $conversation->event_id;
		if ( ! isset( $conversations_by_event[ $event_key ] ) ) {
			$conversations_by_event[ $event_key ] = array(
				'event_title'   => $conversation->event_title,
				'event_slug'    => $conversation->event_slug,
				'event_date'    => $conversation->event_date,
				'conversations' => array(),
			);
		}
		$conversations_by_event[ $event_key ]['conversations'][] = $conversation;
	}

	// Sort events by most recent conversation activity
	uasort(
		$conversations_by_event,
		function ( $a, $b ) {
			$a_latest = max(
				array_map(
					function ( $conv ) {
						return strtotime( $conv->last_reply_date );
					},
					$a['conversations']
				)
			);
			$b_latest = max(
				array_map(
					function ( $conv ) {
						return strtotime( $conv->last_reply_date );
					},
					$b['conversations']
				)
			);
			return $b_latest - $a_latest;
		}
	);

	// Limit to 3 most active events
	$conversations_by_event = array_slice( $conversations_by_event, 0, 3, true );
}

// Group conversations by community
if ( ! empty( $recent_community_conversations ) ) {
	foreach ( $recent_community_conversations as $conversation ) {
		$community_key = $conversation->community_id;
		if ( ! isset( $conversations_by_community[ $community_key ] ) ) {
			$conversations_by_community[ $community_key ] = array(
				'community_name' => $conversation->community_name,
				'community_slug' => $conversation->community_slug,
				'conversations'  => array(),
			);
		}
		$conversations_by_community[ $community_key ]['conversations'][] = $conversation;
	}

	// Sort communities by most recent conversation activity
	uasort(
		$conversations_by_community,
		function ( $a, $b ) {
			$a_latest = max(
				array_map(
					function ( $conv ) {
						return strtotime( $conv->last_reply_date );
					},
					$a['conversations']
				)
			);
			$b_latest = max(
				array_map(
					function ( $conv ) {
						return strtotime( $conv->last_reply_date );
					},
					$b['conversations']
				)
			);
			return $b_latest - $a_latest;
		}
	);

	// Limit to 3 most active communities
	$conversations_by_community = array_slice( $conversations_by_community, 0, 3, true );
}

// Set up template variables
$page_title       = $user_logged_in
	? sprintf( __( 'Welcome back, %s!', 'partyminder' ), esc_html( $current_user->display_name ) )
	: __( 'Welcome to PartyMinder', 'partyminder' );
$page_description = __( 'Your social event hub for connecting, planning, and celebrating together.', 'partyminder' );
$breadcrumbs      = array();

// Main content
ob_start();
?>
<?php if ( $user_logged_in ) : ?>
<!-- Events Section -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<?php 
		// Get events notification count
		$events_new_count = 0;
		if ( $user_logged_in && ! empty( $recent_events ) ) {
			$events_new_count = PartyMinder_Activity_Tracker::get_new_count( $current_user->ID, 'events', $recent_events );
		}
		?>
		<h2 class="pm-heading pm-heading-md pm-mb">
			<?php _e( 'Events', 'partyminder' ); ?>
			<?php if ( $events_new_count > 0 ) : ?>
				<?php echo ' ' . sprintf( _n( '%d update', '%d updates', $events_new_count, 'partyminder' ), $events_new_count ); ?>
			<?php endif; ?>
		</h2>
	</div>
	<?php if ( ! empty( $recent_events ) ) : ?>
		<div class="pm-flex pm-gap pm-flex-column">
			<?php foreach ( $recent_events as $event ) : ?>
				<?php
				$is_past    = strtotime( $event->event_date ) < time();
				$is_hosting = $event->relationship_type === 'created';
				$is_new_event = $user_logged_in && PartyMinder_Activity_Tracker::has_new_activity( $current_user->ID, 'events', $event->id, $event->updated_at ?? $event->created_at );
				$item_classes = 'pm-section pm-flex pm-flex-between';
				if ( $is_new_event ) {
					$item_classes .= ' pm-item-new';
				}
				?>
				<div class="<?php echo $item_classes; ?>">
					<div class="pm-flex-1">
						<h4 class="pm-heading pm-heading-sm">
							<a href="<?php echo home_url( '/events/' . $event->slug ); ?>" class="pm-text-primary">
								<?php echo esc_html( $event->title ); ?>
							</a>
						</h4>
						<div class="pm-flex pm-flex-wrap pm-gap-4 pm-text-muted">
							<span> <?php echo date( 'M j, Y', strtotime( $event->event_date ) ); ?></span>
							<?php if ( $event->venue_info ) : ?>
								<span> <?php echo esc_html( wp_trim_words( $event->venue_info, 3 ) ); ?></span>
							<?php endif; ?>
						</div>
						<span class="pm-badge pm-badge-<?php echo $is_hosting ? 'primary' : 'secondary'; ?>">
							<?php echo $is_hosting ? __( 'Hosting', 'partyminder' ) : __( 'Attending', 'partyminder' ); ?>
						</span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="pm-text-center pm-p-4">
			<div class="pm-mb-4"></div>
			<h3 class="pm-heading pm-heading-sm pm-mb"><?php _e( 'No Recent Events', 'partyminder' ); ?></h3>
			<p class="pm-text-muted"><?php _e( 'Create an event or RSVP to events to see them here.', 'partyminder' ); ?></p>
		</div>
	<?php endif; ?>
	<div class="pm-text-center pm-mt-4">
		<a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn">
			<?php _e( 'Browse All Events', 'partyminder' ); ?>
		</a>
	</div>
</div>

<!-- Conversations Section -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<?php 
		// Get conversations notification count
		$conversations_new_count = 0;
		$conversations_reply_count = 0;
		if ( $user_logged_in && ! empty( $recent_conversations ) ) {
			$conversations_new_count = PartyMinder_Activity_Tracker::get_new_count( $current_user->ID, 'conversations', $recent_conversations );
			// Count total new replies across all conversations
			foreach ( $recent_conversations as $conversation ) {
				if ( PartyMinder_Activity_Tracker::has_new_activity( $current_user->ID, 'conversations', $conversation->id, $conversation->last_reply_date ?? $conversation->created_at ) ) {
					$conversations_reply_count++;
				}
			}
		}
		?>
		<h2 class="pm-heading pm-heading-md pm-mb">
			<?php _e( 'Recent Conversations', 'partyminder' ); ?>
			<?php if ( $conversations_new_count > 0 || $conversations_reply_count > 0 ) : ?>
				<?php 
				$notification_parts = array();
				if ( $conversations_new_count > 0 ) {
					$notification_parts[] = sprintf( _n( '%d new', '%d new', $conversations_new_count, 'partyminder' ), $conversations_new_count );
				}
				if ( $conversations_reply_count > 0 ) {
					$notification_parts[] = sprintf( _n( '%d reply', '%d replies', $conversations_reply_count, 'partyminder' ), $conversations_reply_count );
				}
				echo ' ' . implode( ' ', $notification_parts );
				?>
			<?php endif; ?>
		</h2>
	</div>
	
	<?php if ( ! empty( $recent_conversations ) ) : ?>
		<div class="pm-grid pm-grid-3 pm-gap">
			<?php 
			$conversation_index = 0;
			foreach ( $recent_conversations as $conversation ) : 
				$is_new_conversation = $user_logged_in && PartyMinder_Activity_Tracker::has_new_activity( $current_user->ID, 'conversations', $conversation->id, $conversation->last_reply_date ?? $conversation->created_at );
				$card_classes = 'pm-card';
				if ( $is_new_conversation ) {
					$card_classes .= ' pm-item-unread';
				}
				
				$conversation_index++;
				?>
				<div class="<?php echo $card_classes; ?>">
					<div class="pm-card-body">
						<h3 class="pm-heading pm-heading-sm pm-mb-4">
							<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-text-primary">
								<?php echo esc_html( $conversation_manager->get_display_title( $conversation ) ); ?>
							</a>
						</h3>
						
						<div class="pm-mb-4">
							<span class="pm-text-muted">
								<?php
								if ( $conversation->event_id ) {
									echo esc_html( $conversation->event_title );
								} elseif ( $conversation->community_id ) {
									echo esc_html( $conversation->community_name );
								} else {
									_e( 'General Discussion', 'partyminder' );
								}
								?>
							</span>
						</div>
						
						<div class="pm-flex pm-flex-between">
							<div class="pm-stat">
								<div class="pm-stat-number pm-text-primary"><?php echo $conversation->reply_count; ?></div>
								<div class="pm-stat-label"><?php _e( 'Replies', 'partyminder' ); ?></div>
							</div>
							<div class="pm-text-muted pm-text-sm">
								<?php echo human_time_diff( strtotime( $conversation->created_at ), current_time( 'timestamp' ) ); ?> <?php _e( 'ago', 'partyminder' ); ?>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="pm-text-center pm-p-4">
			<h3 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'No Recent Conversations', 'partyminder' ); ?></h3>
			<p class="pm-text-muted pm-mb-4"><?php _e( 'Start a conversation to connect with people in your circle.', 'partyminder' ); ?></p>
			<?php if ( $user_logged_in ) : ?>
				<a href="<?php echo PartyMinder::get_create_conversation_url(); ?>" class="pm-btn">
					<?php _e( 'Start a Conversation', 'partyminder' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<div class="pm-text-center pm-mt-4">
		<a href="<?php echo esc_url( PartyMinder::get_conversations_url() ); ?>" class="pm-btn">
			<?php _e( 'View All Conversations', 'partyminder' ); ?>
		</a>
	</div>
</div>
			
<!-- Event Conversations Section -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<?php 
		// Get event conversations notification count
		$event_conversations_new_count = 0;
		if ( $user_logged_in && ! empty( $conversations_by_event ) ) {
			foreach ( $conversations_by_event as $event_data ) {
				foreach ( $event_data['conversations'] as $conversation ) {
					if ( PartyMinder_Activity_Tracker::has_new_activity( get_current_user_id(), 'conversations', $conversation->id, $conversation->last_reply_date ?? $conversation->created_at ) ) {
						$event_conversations_new_count++;
					}
				}
			}
		}
		?>
		<h2 class="pm-heading pm-heading-md pm-mb">
			<?php _e( 'Event Conversations', 'partyminder' ); ?>
			<?php if ( $event_conversations_new_count > 0 ) : ?>
				<?php echo ' ' . sprintf( _n( '%d new', '%d new', $event_conversations_new_count, 'partyminder' ), $event_conversations_new_count ); ?>
			<?php endif; ?>
		</h2>
	</div>
	<?php if ( ! empty( $conversations_by_event ) ) : ?>
		<div class="pm-event-conversations-grouped">
			<?php foreach ( $conversations_by_event as $event_id => $event_data ) : ?>
				<?php
				$conversation_count = count( $event_data['conversations'] );
				$event_date         = new DateTime( $event_data['event_date'] );
				$is_upcoming        = $event_date > new DateTime();
				?>
				<div class="pm-event-conversation-group pm-mb-4">
					<!-- Event Header (Clickable to expand/collapse) -->
					<div class="pm-event-group-header pm-flex pm-flex-between pm-p-4" 
						onclick="toggleEventConversations('event-<?php echo $event_id; ?>')">
						<div class="pm-flex pm-gap-4 pm-flex-1">
							<span><?php echo $is_upcoming ? '' : ''; ?></span>
							<div class="pm-flex-1">
								<h4 class="pm-heading pm-heading-sm pm-text-primary">
									<?php echo esc_html( $event_data['event_title'] ); ?>
								</h4>
								<div class="pm-text-muted">
									<?php echo $event_date->format( 'M j, Y' ); ?> • 
									<?php printf( _n( '%d conversation', '%d conversations', $conversation_count, 'partyminder' ), $conversation_count ); ?>
								</div>
							</div>
						</div>
						<div class="pm-flex pm-gap">
							<div class="pm-stat pm-text-center">
								<div class="pm-stat-number pm-text-primary">
									<?php
									echo array_sum(
										array_map(
											function ( $conv ) {
												return $conv->reply_count;
											},
											$event_data['conversations']
										)
									);
									?>
								</div>
								<div class="pm-stat-label"><?php _e( 'Replies', 'partyminder' ); ?></div>
							</div>
							<span class="pm-expand-icon pm-text-muted" id="icon-event-<?php echo $event_id; ?>">▼</span>
						</div>
					</div>
					
					<!-- Conversations List (Initially collapsed) -->
					<div class="pm-event-conversations-list pm-mt-4" id="event-<?php echo $event_id; ?>" style="display: none;">
						<?php foreach ( $event_data['conversations'] as $conversation ) : ?>
							<?php
							$is_unread_conversation = $user_logged_in && PartyMinder_Activity_Tracker::has_new_activity( get_current_user_id(), 'conversations', $conversation->id, $conversation->last_reply_date ?? $conversation->created_at );
							$conversation_classes = 'pm-section pm-flex pm-flex-between pm-mb-4';
							if ( $is_unread_conversation ) {
								$conversation_classes .= ' pm-item-unread';
							}
							?>
							<div class="<?php echo $conversation_classes; ?>">
								<div class="pm-flex-1">
									<div class="pm-flex pm-gap">
										<span></span>
										<h5 class="pm-heading pm-heading-sm">
											<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" 
												class="pm-text-primary">
												<?php echo esc_html( $conversation_manager->get_display_title( $conversation ) ); ?>
											</a>
										</h5>
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
								<div class="pm-stat pm-text-center">
									<div class="pm-stat-number pm-text-primary"><?php echo $conversation->reply_count; ?></div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="pm-text-center pm-p-4">
			<div class="pm-mb-4"></div>
			<h3 class="pm-heading pm-heading-sm pm-mb"><?php _e( 'No Event Discussions Yet', 'partyminder' ); ?></h3>
			<p class="pm-text-muted"><?php _e( 'Event conversations will appear here when people start planning together!', 'partyminder' ); ?></p>
		</div>
	<?php endif; ?>
	<div class="pm-text-center pm-mt-4">
		<a href="<?php echo esc_url( add_query_arg( 'filter', 'events', PartyMinder::get_conversations_url() ) ); ?>" class="pm-btn">
			<?php _e( 'View Event Discussions', 'partyminder' ); ?>
		</a>
	</div>
</div>

<!-- Community Conversations Section -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<?php 
		// Get community activity counts
		$community_new_members = 0;
		$community_new_conversations = 0;
		$community_new_events = 0;
		if ( $user_logged_in ) {
			// This is simplified for now - we'll expand when we implement community tracking
			if ( ! empty( $conversations_by_community ) ) {
				foreach ( $conversations_by_community as $community_data ) {
					$community_new_conversations += count( $community_data['conversations'] );
				}
			}
		}
		?>
		<h2 class="pm-heading pm-heading-md pm-mb">
			<?php _e( 'My Communities', 'partyminder' ); ?>
			<?php if ( $community_new_members > 0 || $community_new_conversations > 0 || $community_new_events > 0 ) : ?>
				<?php 
				$community_parts = array();
				if ( $community_new_members > 0 ) {
					$community_parts[] = sprintf( _n( '%d new member', '%d new members', $community_new_members, 'partyminder' ), $community_new_members );
				}
				if ( $community_new_conversations > 0 ) {
					$community_parts[] = sprintf( _n( '%d new conversation', '%d new conversations', $community_new_conversations, 'partyminder' ), $community_new_conversations );
				}
				if ( $community_new_events > 0 ) {
					$community_parts[] = sprintf( _n( '%d new event', '%d new events', $community_new_events, 'partyminder' ), $community_new_events );
				}
				echo ' ' . implode( ' ', $community_parts );
				?>
			<?php endif; ?>
		</h2>
	</div>
	<?php if ( ! empty( $conversations_by_community ) ) : ?>
		<div class="pm-community-conversations-grouped">
			<?php foreach ( $conversations_by_community as $community_id => $community_data ) : ?>
				<?php
				$conversation_count = count( $community_data['conversations'] );
				?>
				<div class="pm-community-conversation-group pm-mb-4">
					<!-- Community Header (Clickable to expand/collapse) -->
					<div class="pm-community-group-header pm-flex pm-flex-between pm-p-4" 
						onclick="toggleCommunityConversations('community-<?php echo $community_id; ?>')">
						<div class="pm-flex pm-gap-4 pm-flex-1">
							<div class="pm-flex-1">
								<h4 class="pm-heading pm-heading-sm pm-text-primary">
									<?php echo esc_html( $community_data['community_name'] ); ?>
								</h4>
								<div class="pm-text-muted">
									<?php printf( _n( '%d conversation', '%d conversations', $conversation_count, 'partyminder' ), $conversation_count ); ?>
								</div>
							</div>
						</div>
						<div class="pm-flex pm-gap">
							<div class="pm-stat pm-text-center">
								<div class="pm-stat-number pm-text-primary">
									<?php
									echo array_sum(
										array_map(
											function ( $conv ) {
												return $conv->reply_count;
											},
											$community_data['conversations']
										)
									);
									?>
								</div>
								<div class="pm-stat-label"><?php _e( 'Replies', 'partyminder' ); ?></div>
							</div>
							<span class="pm-expand-icon pm-text-muted" id="icon-community-<?php echo $community_id; ?>">▼</span>
						</div>
					</div>
					
					<!-- Conversations List (Initially collapsed) -->
					<div class="pm-community-conversations-list pm-mt-4" id="community-<?php echo $community_id; ?>" style="display: none;">
						<?php foreach ( $community_data['conversations'] as $conversation ) : ?>
							<?php
							$is_unread_community_conversation = $user_logged_in && PartyMinder_Activity_Tracker::has_new_activity( get_current_user_id(), 'conversations', $conversation->id, $conversation->last_reply_date ?? $conversation->created_at );
							$community_conversation_classes = 'pm-section pm-flex pm-flex-between pm-mb-4';
							if ( $is_unread_community_conversation ) {
								$community_conversation_classes .= ' pm-item-unread';
							}
							?>
							<div class="<?php echo $community_conversation_classes; ?>">
								<div class="pm-flex-1">
									<div class="pm-flex pm-gap">
										<?php if ( $conversation->is_pinned ) : ?>
											<span class="pm-badge pm-badge-secondary">Pinned</span>
										<?php endif; ?>
										<h5 class="pm-heading pm-heading-sm">
											<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" 
												class="pm-text-primary">
												<?php echo esc_html( $conversation_manager->get_display_title( $conversation ) ); ?>
											</a>
										</h5>
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
								<div class="pm-stat pm-text-center">
									<div class="pm-stat-number pm-text-primary"><?php echo $conversation->reply_count; ?></div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="pm-text-center pm-p-4">
			<h3 class="pm-heading pm-heading-sm pm-mb"><?php _e( 'No Community Discussions Yet', 'partyminder' ); ?></h3>
			<p class="pm-text-muted"><?php _e( 'Join communities and start conversations to see them here!', 'partyminder' ); ?></p>
		</div>
	<?php endif; ?>
	<div class="pm-text-center pm-mt-4">
		<a href="<?php echo esc_url( add_query_arg( 'filter', 'communities', PartyMinder::get_conversations_url() ) ); ?>" class="pm-btn">
			<?php _e( 'View Community Discussions', 'partyminder' ); ?>
		</a>
	</div>
</div>

<!-- Community Activity Section -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-mb"> <?php _e( 'Community Activity', 'partyminder' ); ?></h2>
	</div>
	<?php
	// Include community activity feed
	$user_id             = null; // No specific user = community feed
	$limit               = 5;
	$show_user_names     = true; // Show who did what
	$activity_types      = array( 'events', 'conversations' ); // Only public activities
	$show_empty_state    = true;
	$empty_state_actions = true;

	include PARTYMINDER_PLUGIN_DIR . 'templates/components/activity-feed.php';
	?>
</div>
<?php else : ?>
<!-- Login Section for Non-Logged-In Users -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-mb"><?php _e( 'Sign In to Get Started', 'partyminder' ); ?></h2>
		<p class="pm-text-muted"><?php _e( 'Log in to create events, join conversations, and connect with the community', 'partyminder' ); ?></p>
	</div>
	<div class="pm-text-center pm-p-4">
		<div class="pm-text-xl pm-mb-4"></div>
		<h3 class="pm-heading pm-heading-md pm-mb"><?php _e( 'Welcome to PartyMinder!', 'partyminder' ); ?></h3>
		<p class="pm-text-muted pm-mb"><?php _e( 'Your social event hub for connecting, planning, and celebrating together.', 'partyminder' ); ?></p>
		<div class="pm-flex pm-gap-4 pm-justify-center">
			<a href="<?php echo esc_url( PartyMinder::get_login_url() ); ?>" class="pm-btn pm-btn-lg">
				<?php _e( 'Sign In', 'partyminder' ); ?>
			</a>
			<?php if ( get_option( 'users_can_register' ) ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'action', 'register', PartyMinder::get_login_url() ) ); ?>" class="pm-btn pm-btn-lg">
				<?php _e( 'Create Account', 'partyminder' ); ?>
			</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Preview Section for Non-Logged-In Users -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-mb"> <?php _e( 'What You Can Do', 'partyminder' ); ?></h2>
		<p class="pm-text-muted"><?php _e( 'Discover all the features waiting for you', 'partyminder' ); ?></p>
	</div>
	<div class="pm-grid pm-gap-4">
		<div class="pm-flex pm-gap-4 pm-p-4">
			<div class="pm-text-xl"></div>
			<div class="pm-flex-1">
				<h4 class="pm-heading pm-heading-sm"><?php _e( 'Create & Host Events', 'partyminder' ); ?></h4>
				<p class="pm-text-muted"><?php _e( 'Plan dinner parties, game nights, and social gatherings', 'partyminder' ); ?></p>
			</div>
		</div>
		<div class="pm-flex pm-gap-4 pm-p-4">
			<div class="pm-text-xl"></div>
			<div class="pm-flex-1">
				<h4 class="pm-heading pm-heading-sm"><?php _e( 'Join Conversations', 'partyminder' ); ?></h4>
				<p class="pm-text-muted"><?php _e( 'Share tips and connect with fellow hosts and party-goers', 'partyminder' ); ?></p>
			</div>
		</div>
		<div class="pm-flex pm-gap-4 pm-p-4">
			<div class="pm-flex-1">
				<h4 class="pm-heading pm-heading-sm"><?php _e( 'Build Communities', 'partyminder' ); ?></h4>
				<p class="pm-text-muted"><?php _e( 'Create groups around shared interests and plan together', 'partyminder' ); ?></p>
			</div>
		</div>
	</div>
	<div class="pm-text-center pm-mt-4">
		<a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn">
			<?php _e( 'Browse Public Events', 'partyminder' ); ?>
		</a>
	</div>
</div>

<?php endif; ?>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<?php if ( ! $user_logged_in ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Get Started', 'partyminder' ); ?></h3>
	</div>
	<p class="pm-text-muted pm-mb"><?php _e( 'Log in to access all features and manage your events.', 'partyminder' ); ?></p>
	<div class="pm-flex pm-gap pm-flex-column">
		<a href="<?php echo esc_url( PartyMinder::get_login_url() ); ?>" class="pm-btn">
			<?php _e( 'Login', 'partyminder' ); ?>
		</a>
		<?php if ( get_option( 'users_can_register' ) ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'action', 'register', PartyMinder::get_login_url() ) ); ?>" class="pm-btn">
			<?php _e( 'Register', 'partyminder' ); ?>
		</a>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
function toggleEventConversations(elementId) {
	const conversationsList = document.getElementById(elementId);
	const icon = document.getElementById('icon-' + elementId);
	
	if (conversationsList.style.display === 'none' || conversationsList.style.display === '') {
		conversationsList.style.display = 'block';
		icon.textContent = '▲';
	} else {
		conversationsList.style.display = 'none';
		icon.textContent = '▼';
	}
}

function toggleCommunityConversations(elementId) {
	const conversationsList = document.getElementById(elementId);
	const icon = document.getElementById('icon-' + elementId);
	
	if (conversationsList.style.display === 'none' || conversationsList.style.display === '') {
		conversationsList.style.display = 'block';
		icon.textContent = '▲';
	} else {
		conversationsList.style.display = 'none';
		icon.textContent = '▼';
	}
}
</script>
