<?php
/**
 * Events List Content Template
 * Public events listing page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get shortcode attributes with defaults
$limit = intval($atts['limit'] ?? 10);
$show_past = filter_var($atts['show_past'] ?? false, FILTER_VALIDATE_BOOLEAN);
$upcoming_only = filter_var($atts['upcoming_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

// Get events using privacy-aware method
if ($show_past) {
    // For past events, we need to build our own query with privacy filtering
    // This is a more complex case that would need custom implementation
    // For now, use the get_upcoming_events method and filter past events
    $all_events = $event_manager->get_upcoming_events($limit * 2); // Get more to account for filtering
    $events = array();
    
    // Note: This is a temporary solution. A proper get_all_events() method with privacy filtering should be implemented
    global $wpdb;
    $events_table = $wpdb->prefix . 'partyminder_events';
    $guests_table = $wpdb->prefix . 'partyminder_guests';
    $invitations_table = $wpdb->prefix . 'partyminder_event_invitations';
    $current_user_id = get_current_user_id();
    
    // Build privacy clause - show public events to everyone, private events to creator and invited guests
    $privacy_clause = "e.privacy = 'public'";
    if ($current_user_id && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email;
        
        $privacy_clause = "(e.privacy = 'public' OR 
                          (e.privacy = 'private' AND e.author_id = $current_user_id) OR 
                          (e.privacy = 'private' AND EXISTS(
                              SELECT 1 FROM $guests_table g 
                              WHERE g.event_id = e.id AND g.email = %s
                          )) OR
                          (e.privacy = 'private' AND EXISTS(
                              SELECT 1 FROM $invitations_table i 
                              WHERE i.event_id = e.id AND i.invited_email = %s 
                              AND i.status = 'pending' AND i.expires_at > NOW()
                          )))";
    }
    
    $query = "SELECT DISTINCT e.* FROM $events_table e
             WHERE e.event_status = 'active'
             AND ($privacy_clause)
             ORDER BY e.event_date ASC 
             LIMIT %d";
    
    if ($current_user_id && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $events = $wpdb->get_results($wpdb->prepare($query, $current_user->user_email, $current_user->user_email, $limit));
    } else {
        $events = $wpdb->get_results($wpdb->prepare($query, $limit));
    }
} else {
    // Use the privacy-aware get_upcoming_events method
    $events = $event_manager->get_upcoming_events($limit);
}

// Set up template variables
$page_title = $show_past ? __('All Events', 'partyminder') : __('Upcoming Events', 'partyminder');
$page_description = $show_past 
    ? __('Browse through our collection of events and gatherings', 'partyminder')
    : __('Discover amazing events happening near you. Join the community!', 'partyminder');

// Main content
ob_start();
?>
<div class="pm-section pm-mb">
    <div class="pm-flex pm-flex-between pm-mb-4">
        <div>
            <div class="pm-stat">
                <div class="pm-stat-number pm-text-primary"><?php echo count($events); ?></div>
                <div class="pm-stat-label"><?php _e('Events', 'partyminder'); ?></div>
            </div>
        </div>
        <?php if (is_user_logged_in()): ?>
        <div class="pm-flex pm-gap">
            <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="pm-btn pm-btn-secondary">
                <?php _e('My Events', 'partyminder'); ?>
            </a>
            <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="pm-btn">
                <?php _e('Create Event', 'partyminder'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="pm-section">
    <?php if (!empty($events)): ?>
        <div class="pm-grid pm-grid-2 pm-gap">
            <?php foreach ($events as $event): ?>
                <?php
                $event_date = new DateTime($event->event_date);
                $is_today = $event_date->format('Y-m-d') === date('Y-m-d');
                $is_tomorrow = $event_date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
                $is_past = $event_date < new DateTime();
                ?>
                
                <div class="pm-section pm-p-4">
                    <div class="pm-flex pm-flex-between pm-mb-4">
                        <h3 class="pm-heading pm-heading-sm">
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-text-primary"><?php echo esc_html($event->title); ?></a>
                        </h3>
                    </div>
                    
                    <div class="pm-mb-4">
                        <div class="pm-flex pm-gap pm-mb-4">
                            <span class="pm-text-muted">
                                <?php if ($is_today): ?>
                                    <?php _e('Today', 'partyminder'); ?>
                                <?php elseif ($is_tomorrow): ?>
                                    <?php _e('Tomorrow', 'partyminder'); ?>
                                <?php else: ?>
                                    <?php echo $event_date->format('M j, Y'); ?>
                                <?php endif; ?>
                                <?php if ($event->event_time): ?>
                                    at <?php echo date('g:i A', strtotime($event->event_date)); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($event->venue_info): ?>
                        <div class="pm-flex pm-gap pm-mb-4">
                            <span class="pm-text-muted"><?php echo esc_html($event->venue_info); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="pm-flex pm-gap pm-mb-4">
                            <span class="pm-text-muted">
                                <?php printf(__('Hosted by %s', 'partyminder'), esc_html($event->host_email)); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($event->excerpt || $event->description): ?>
                    <div class="pm-mb-4">
                        <p class="pm-text-muted"><?php echo esc_html(wp_trim_words($event->excerpt ?: $event->description, 15)); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pm-flex pm-flex-between">
                        <div class="pm-stat">
                            <div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
                            <div class="pm-stat-label">
                                <?php _e('Confirmed', 'partyminder'); ?>
                                <?php if ($event->guest_limit > 0): ?>
                                    <?php printf(__(' / %d', 'partyminder'), $event->guest_limit); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($is_past): ?>
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-btn pm-btn-secondary">
                                <?php _e('View Details', 'partyminder'); ?>
                            </a>
                        <?php else: ?>
                            <?php 
                            $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit;
                            $can_view = $event_manager->can_user_view_event($event);
                            $is_host = is_user_logged_in() && get_current_user_id() == $event->author_id;
                            $can_rsvp = $can_view && !$is_host;
                            ?>
                            <?php if ($can_rsvp): ?>
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-btn">
                                    <?php echo $is_full ? __('Join Waitlist', 'partyminder') : __('RSVP Now', 'partyminder'); ?>
                                </a>
                            <?php elseif ($is_host): ?>
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-btn pm-btn-secondary">
                                    <?php _e('View Details', 'partyminder'); ?>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-btn pm-btn-secondary">
                                    <?php _e('View Details', 'partyminder'); ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        <div class="pm-text-center pm-p-4">
            <h3 class="pm-heading pm-heading-sm pm-mb-4"><?php _e('No Events Found', 'partyminder'); ?></h3>
            <?php if ($show_past): ?>
                <p class="pm-text-muted"><?php _e('There are no past events to display.', 'partyminder'); ?></p>
            <?php else: ?>
                <p class="pm-text-muted"><?php _e('There are no upcoming events scheduled. Check back soon!', 'partyminder'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!is_user_logged_in()): ?>
<div class="pm-section pm-text-center">
    <div class="pm-text-xl pm-mb-4"></div>
    <h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e('Ready to Join the Fun?', 'partyminder'); ?></h3>
    <p class="pm-text-muted mb-4"><?php _e('Sign in to RSVP to events, connect with hosts, and never miss an amazing party!', 'partyminder'); ?></p>
    <div class="pm-flex pm-gap pm-flex-center pm-flex-wrap">
        <a href="<?php echo add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), PartyMinder::get_login_url()); ?>" class="pm-btn">
            <?php _e('Login', 'partyminder'); ?>
        </a>
        <?php if (get_option('users_can_register')): ?>
        <a href="<?php echo add_query_arg(array('action' => 'register', 'redirect_to' => urlencode($_SERVER['REQUEST_URI'])), PartyMinder::get_login_url()); ?>" class="pm-btn pm-btn-secondary">
            <?php _e('Sign Up', 'partyminder'); ?>
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
<!-- Quick Actions -->
<div class="pm-section pm-mb">
    <div class="pm-section-header">
        <h3 class="pm-heading pm-heading-sm"><?php _e('Quick Actions', 'partyminder'); ?></h3>
    </div>
    <div class="pm-flex pm-gap pm-flex-wrap">
        <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="pm-btn">
            <?php _e('Create Event', 'partyminder'); ?>
        </a>
        <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="pm-btn pm-btn-secondary">
            <?php _e('Join Conversations', 'partyminder'); ?>
        </a>
    </div>
</div>

<!-- Event Categories -->
<div class="pm-section pm-mb">
    <div class="pm-section-header">
        <h3 class="pm-heading pm-heading-sm"><?php _e('Event Types', 'partyminder'); ?></h3>
    </div>
    <div class="pm-text-muted">
        <div class="pm-mb-4">
            <div class="pm-flex pm-gap pm-mb-4">
                <span></span>
                <strong><?php _e('Dinner Parties', 'partyminder'); ?></strong>
            </div>
        </div>
        <div class="pm-mb-4">
            <div class="pm-flex pm-gap pm-mb-4">
                <span></span>
                <strong><?php _e('Game Nights', 'partyminder'); ?></strong>
            </div>
        </div>
        <div class="pm-mb-4">
            <div class="pm-flex pm-gap pm-mb-4">
                <span></span>
                <strong><?php _e('Creative Workshops', 'partyminder'); ?></strong>
            </div>
        </div>
        <div class="pm-mb-4">
            <div class="pm-flex pm-gap pm-mb-4">
                <span></span>
                <strong><?php _e('Social Gatherings', 'partyminder'); ?></strong>
            </div>
        </div>
    </div>
</div>
<?php
$sidebar_content = ob_get_clean();

// Include two-column template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
?>