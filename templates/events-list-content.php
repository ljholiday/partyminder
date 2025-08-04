<?php
/**
 * Events List Content Template - Theme Integrated
 * Content only version for theme integration via the_content filter
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

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
$button_style = get_option('partyminder_button_style', 'rounded');
?>

<style>
/* Dynamic color styles only */
.partyminder-events-content .events-header h2 {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-events-content .event-title a:hover {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-events-content .btn {
    background: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-events-content .stat-number {
    color: <?php echo esc_attr($primary_color); ?>;
}
</style>

<div class="page">
    
    <!-- Events Header -->
    <div class="card-header mb-4">
        <h1 class="heading heading-lg text-primary">
            <?php if ($show_past): ?>
                <?php _e('📅 All Events', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('🎉 Upcoming Events', 'partyminder'); ?>
            <?php endif; ?>
        </h1>
        
        <p class="text-muted">
            <?php if ($show_past): ?>
                <?php _e('Browse through our collection of events and gatherings.', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('Discover amazing events happening near you. Join the community!', 'partyminder'); ?>
            <?php endif; ?>
        </p>
        
        <!-- Events Navigation -->
        <?php if (is_user_logged_in()): ?>
        <div class="flex gap-4 mt-4 mb-4">
            <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="btn btn-secondary">
                📅 <?php _e('My Events', 'partyminder'); ?>
            </a>
            <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="btn">
                ✨ <?php _e('Create Event', 'partyminder'); ?>
            </a>
        </div>
        <?php endif; ?>
        
        <div class="flex gap-4 mt-4">
            <div class="stat">
                <div class="stat-number text-primary"><?php echo count($events); ?></div>
                <div class="stat-label"><?php _e('Events', 'partyminder'); ?></div>
            </div>
        </div>
    </div>

    <!-- Events Grid -->
    <?php if (!empty($events)): ?>
        <div class="grid grid-auto">
            <?php foreach ($events as $event): ?>
                <?php
                $event_date = new DateTime($event->event_date);
                $is_today = $event_date->format('Y-m-d') === date('Y-m-d');
                $is_tomorrow = $event_date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
                $is_past = $event_date < new DateTime();
                ?>
                
                <article class="card <?php echo $is_past ? 'past-event' : ''; ?>">
                    
                    <?php if ($event->featured_image): ?>
                    <div class="event-image">
                        <img src="<?php echo esc_url($event->featured_image); ?>" alt="<?php echo esc_attr($event->title); ?>" class="">
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-header">
                        <h3 class="heading heading-sm ">
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="text-primary "><?php echo esc_html($event->title); ?></a>
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="flex">
                                <span>📅</span>
                                <span class="text-muted">
                                    <?php if ($is_today): ?>
                                        <?php _e('Today', 'partyminder'); ?>
                                    <?php elseif ($is_tomorrow): ?>
                                        <?php _e('Tomorrow', 'partyminder'); ?>
                                    <?php else: ?>
                                        <?php echo $event_date->format('M j, Y'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if ($event->event_time): ?>
                            <div class="flex">
                                <span>🕐</span>
                                <span class="text-muted"><?php echo date('g:i A', strtotime($event->event_date)); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($event->venue_info): ?>
                            <div class="flex">
                                <span>📍</span>
                                <span class="text-muted"><?php echo esc_html($event->venue_info); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($event->excerpt || $event->description): ?>
                        <div class="mb-4">
                            <p class="text-muted"><?php echo esc_html(wp_trim_words($event->excerpt ?: $event->description, 20)); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex flex-between mb-4">
                            <div class="stat">
                                <div class="stat-number text-primary"><?php echo $event->guest_stats->confirmed; ?></div>
                                <div class="stat-label">
                                    <?php _e('Confirmed', 'partyminder'); ?>
                                    <?php if ($event->guest_limit > 0): ?>
                                        <?php printf(__(' / %d', 'partyminder'), $event->guest_limit); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($event->guest_stats->maybe > 0): ?>
                            <div class="stat">
                                <div class="stat-number text-primary"><?php echo $event->guest_stats->maybe; ?></div>
                                <div class="stat-label"><?php _e('Maybe', 'partyminder'); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-footer flex gap-4">
                        <?php if ($is_past): ?>
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn btn-secondary btn-small">
                                <span>📖</span>
                                <?php _e('View Details', 'partyminder'); ?>
                            </a>
                        <?php else: ?>
                            <?php 
                            $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit;
                            ?>
                            
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn btn-small">
                                <span>💌</span>
                                <?php if ($is_full): ?>
                                    <?php _e('Join Waitlist', 'partyminder'); ?>
                                <?php else: ?>
                                    <?php _e('RSVP Now', 'partyminder'); ?>
                                <?php endif; ?>
                            </a>
                            
                            <button type="button" class="btn btn-secondary btn-small share-event" 
                                    data-url="<?php echo esc_url(home_url('/events/' . $event->slug)); ?>" 
                                    data-title="<?php echo esc_attr($event->title); ?>">
                                <span>📤</span>
                                <?php _e('Share', 'partyminder'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        <!-- No Events Found -->
        <div class="no-events-found">
            <div class="no-events-icon">🎭</div>
            <div class="no-events-content">
                <h3><?php _e('No Events Found', 'partyminder'); ?></h3>
                <?php if ($show_past): ?>
                    <p><?php _e('There are no past events to display.', 'partyminder'); ?></p>
                <?php else: ?>
                    <p><?php _e('There are no upcoming events scheduled. Check back soon!', 'partyminder'); ?></p>
                <?php endif; ?>
                
                <?php if (current_user_can('publish_posts')): ?>
                <div class="no-events-actions">
                    <a href="<?php echo admin_url('admin.php?page=partyminder-create'); ?>" class="btn">
                        <span class="button-icon">✨</span>
                        <?php _e('Create First Event', 'partyminder'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Login Prompt for Non-Logged-In Users -->
    <?php if (!is_user_logged_in()): ?>
    <div class="card mt-4 card  text-center">
        <div class="card-body p-4">
            <div class=" mb-4">🎉</div>
            <h3 class="heading heading-md mb-4 ">
                <?php _e('Ready to Join the Fun?', 'partyminder'); ?>
            </h3>
            <p class="mb-4 ">
                <?php _e('Sign in to RSVP to events, connect with hosts, and never miss an amazing party!', 'partyminder'); ?>
            </p>
            <div class="flex gap-4 flex-center flex-wrap">
                <a href="<?php echo add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), PartyMinder::get_login_url()); ?>" class="btn btn btn-secondary">
                    <span>🔑</span>
                    <?php _e('Login', 'partyminder'); ?>
                </a>
                <?php if (get_option('users_can_register')): ?>
                <a href="<?php echo add_query_arg(array('action' => 'register', 'redirect_to' => urlencode($_SERVER['REQUEST_URI'])), PartyMinder::get_login_url()); ?>" class="btn btn-secondary">
                    <span>✨</span>
                    <?php _e('Sign Up', 'partyminder'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Share event functionality
    document.querySelectorAll('.share-event').forEach(function(button) {
        button.addEventListener('click', function() {
            const url = this.dataset.url;
            const title = this.dataset.title;
            
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                });
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    alert('<?php _e("Event URL copied to clipboard!", "partyminder"); ?>');
                });
            } else {
                // Fallback: open in new window
                window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
            }
        });
    });
});
</script>