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

// Get events from custom table
global $wpdb;
$events_table = $wpdb->prefix . 'partyminder_events';

$where_clause = "WHERE event_status = 'active'";
if (!$show_past && !$upcoming_only) {
    // Default: show upcoming events only
    $where_clause .= " AND event_date >= CURDATE()";
} elseif ($upcoming_only) {
    // Explicitly requested upcoming only
    $where_clause .= " AND event_date >= CURDATE()";
}
// If $show_past is true, show all events including past ones

$events = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $events_table 
     $where_clause
     ORDER BY event_date ASC 
     LIMIT %d",
    $limit
));

// Add guest stats to each event
foreach ($events as $event) {
    $event->guest_stats = $event_manager->get_guest_stats($event->id);
}

// Set up template variables
$page_title = $show_past ? __('All Events', 'partyminder') : __('Upcoming Events', 'partyminder');
$page_description = $show_past 
    ? __('Browse through our collection of events and gatherings', 'partyminder')
    : __('Discover amazing events happening near you. Join the community!', 'partyminder');

// Main content
ob_start();
?>
<div class="section mb-4">
    <div class="flex flex-between mb-4">
        <div>
            <div class="stat">
                <div class="stat-number text-primary"><?php echo count($events); ?></div>
                <div class="stat-label"><?php _e('Events', 'partyminder'); ?></div>
            </div>
        </div>
        <?php if (is_user_logged_in()): ?>
        <div class="flex gap-4">
            <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="btn btn-secondary">
                📅 <?php _e('My Events', 'partyminder'); ?>
            </a>
            <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn">
                ✨ <?php _e('Create Event', 'partyminder'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="section">
    <?php if (!empty($events)): ?>
        <div class="grid grid-2 gap-4">
            <?php foreach ($events as $event): ?>
                <?php
                $event_date = new DateTime($event->event_date);
                $is_today = $event_date->format('Y-m-d') === date('Y-m-d');
                $is_tomorrow = $event_date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
                $is_past = $event_date < new DateTime();
                ?>
                
                <div class="section p-4">
                    <div class="flex flex-between mb-4">
                        <h3 class="heading heading-sm">
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="text-primary"><?php echo esc_html($event->title); ?></a>
                        </h3>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex gap-4 mb-4">
                            <span>📅</span>
                            <span class="text-muted">
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
                        <div class="flex gap-4 mb-4">
                            <span>📍</span>
                            <span class="text-muted"><?php echo esc_html($event->venue_info); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($event->excerpt || $event->description): ?>
                    <div class="mb-4">
                        <p class="text-muted"><?php echo esc_html(wp_trim_words($event->excerpt ?: $event->description, 15)); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex flex-between">
                        <div class="stat">
                            <div class="stat-number text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
                            <div class="stat-label">
                                <?php _e('Confirmed', 'partyminder'); ?>
                                <?php if ($event->guest_limit > 0): ?>
                                    <?php printf(__(' / %d', 'partyminder'), $event->guest_limit); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($is_past): ?>
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn btn-secondary">
                                📖 <?php _e('View Details', 'partyminder'); ?>
                            </a>
                        <?php else: ?>
                            <?php 
                            $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit;
                            ?>
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn">
                                💌 <?php echo $is_full ? __('Join Waitlist', 'partyminder') : __('RSVP Now', 'partyminder'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        <div class="text-center p-4">
            <div class="text-xl mb-4">🎭</div>
            <h3 class="heading heading-sm mb-4"><?php _e('No Events Found', 'partyminder'); ?></h3>
            <?php if ($show_past): ?>
                <p class="text-muted"><?php _e('There are no past events to display.', 'partyminder'); ?></p>
            <?php else: ?>
                <p class="text-muted"><?php _e('There are no upcoming events scheduled. Check back soon!', 'partyminder'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!is_user_logged_in()): ?>
<div class="section text-center">
    <div class="text-xl mb-4">🎉</div>
    <h3 class="heading heading-md mb-4"><?php _e('Ready to Join the Fun?', 'partyminder'); ?></h3>
    <p class="text-muted mb-4"><?php _e('Sign in to RSVP to events, connect with hosts, and never miss an amazing party!', 'partyminder'); ?></p>
    <div class="flex gap-4 flex-center flex-wrap">
        <a href="<?php echo add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), PartyMinder::get_login_url()); ?>" class="btn">
            🔑 <?php _e('Login', 'partyminder'); ?>
        </a>
        <?php if (get_option('users_can_register')): ?>
        <a href="<?php echo add_query_arg(array('action' => 'register', 'redirect_to' => urlencode($_SERVER['REQUEST_URI'])), PartyMinder::get_login_url()); ?>" class="btn btn-secondary">
            ✨ <?php _e('Sign Up', 'partyminder'); ?>
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
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
    </div>
    <div class="flex gap-4 flex-wrap">
        <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn">
            ✨ <?php _e('Create Event', 'partyminder'); ?>
        </a>
        <a href="<?php echo esc_url(PartyMinder::get_conversations_url()); ?>" class="btn btn-secondary">
            💬 <?php _e('Join Conversations', 'partyminder'); ?>
        </a>
    </div>
</div>

<!-- Event Categories -->
<div class="section mb-4">
    <div class="section-header">
        <h3 class="heading heading-sm">🏷️ <?php _e('Event Types', 'partyminder'); ?></h3>
    </div>
    <div class="text-muted">
        <div class="mb-4">
            <div class="flex gap-4 mb-4">
                <span>🍽️</span>
                <strong><?php _e('Dinner Parties', 'partyminder'); ?></strong>
            </div>
        </div>
        <div class="mb-4">
            <div class="flex gap-4 mb-4">
                <span>🎮</span>
                <strong><?php _e('Game Nights', 'partyminder'); ?></strong>
            </div>
        </div>
        <div class="mb-4">
            <div class="flex gap-4 mb-4">
                <span>🎨</span>
                <strong><?php _e('Creative Workshops', 'partyminder'); ?></strong>
            </div>
        </div>
        <div class="mb-4">
            <div class="flex gap-4 mb-4">
                <span>🌟</span>
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