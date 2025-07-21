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

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

// Get events using our custom method
if ($show_past) {
    // Get all events
    global $wpdb;
    $events_table = $wpdb->prefix . 'partyminder_events';
    $posts_table = $wpdb->posts;
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID FROM $posts_table p 
         INNER JOIN $events_table e ON p.ID = e.post_id 
         WHERE p.post_type = 'party_event' 
         AND p.post_status = 'publish' 
         AND e.event_status = 'active'
         ORDER BY e.event_date DESC 
         LIMIT %d",
        $limit
    ));
} else {
    // Get upcoming events only
    $upcoming_events = $event_manager->get_upcoming_events($limit);
    $events = $upcoming_events;
}

// If we got results from the direct query, convert them to event objects
if (isset($results) && !empty($results)) {
    $events = array();
    foreach ($results as $result) {
        $event = $event_manager->get_event($result->ID);
        if ($event) {
            $events[] = $event;
        }
    }
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

.partyminder-events-content .pm-button {
    background: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-events-content .stat-number {
    color: <?php echo esc_attr($primary_color); ?>;
}
</style>

<div class="partyminder-events-content">
    
    <!-- Events Header -->
    <div class="events-header">
        <h2 class="events-title">
            <?php if ($show_past): ?>
                <?php _e('All Events', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('🎉 Upcoming Events', 'partyminder'); ?>
            <?php endif; ?>
        </h2>
        
        <p class="events-description">
            <?php if ($show_past): ?>
                <?php _e('Browse through our collection of events and gatherings.', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('Discover amazing events happening near you. Join the community!', 'partyminder'); ?>
            <?php endif; ?>
        </p>
        
        <div class="events-stats">
            <span class="stat-item">
                <span class="stat-number"><?php echo count($events); ?></span>
                <span class="stat-label"><?php _e('Events', 'partyminder'); ?></span>
            </span>
        </div>
    </div>

    <!-- Events Grid -->
    <?php if (!empty($events)): ?>
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
                <?php
                $event_date = new DateTime($event->event_date);
                $is_today = $event_date->format('Y-m-d') === date('Y-m-d');
                $is_tomorrow = $event_date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
                $is_past = $event_date < new DateTime();
                ?>
                
                <article class="event-card <?php echo $is_past ? 'past-event' : ''; ?>">
                    
                    <?php if (has_post_thumbnail($event->ID)): ?>
                    <div class="event-image">
                        <?php echo get_the_post_thumbnail($event->ID, 'medium'); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="event-content">
                        <header class="event-header">
                            <h3 class="event-title">
                                <a href="<?php echo get_permalink($event->ID); ?>"><?php echo esc_html($event->title); ?></a>
                            </h3>
                            
                            <div class="event-meta">
                                <div class="meta-item event-date-meta">
                                    <span class="meta-icon">📅</span>
                                    <span class="meta-text">
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
                                <div class="meta-item">
                                    <span class="meta-icon">🕐</span>
                                    <span class="meta-text"><?php echo date('g:i A', strtotime($event->event_date)); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($event->venue_info): ?>
                                <div class="meta-item">
                                    <span class="meta-icon">📍</span>
                                    <span class="meta-text"><?php echo esc_html($event->venue_info); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </header>
                        
                        <?php if ($event->excerpt || $event->description): ?>
                        <div class="event-description">
                            <p><?php echo esc_html(wp_trim_words($event->excerpt ?: $event->description, 20)); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="event-stats">
                            <div class="stat-item">
                                <span class="stat-icon">👥</span>
                                <span class="stat-text">
                                    <?php echo $event->guest_stats->confirmed; ?> <?php _e('confirmed', 'partyminder'); ?>
                                    <?php if ($event->guest_limit > 0): ?>
                                        <?php printf(__(' of %d', 'partyminder'), $event->guest_limit); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if ($event->guest_stats->maybe > 0): ?>
                            <div class="stat-item">
                                <span class="stat-icon">🤔</span>
                                <span class="stat-text"><?php echo $event->guest_stats->maybe; ?> <?php _e('maybe', 'partyminder'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <footer class="event-actions">
                            <?php if ($is_past): ?>
                                <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button pm-button-secondary pm-button-small">
                                    <span class="button-icon">📖</span>
                                    <?php _e('View Details', 'partyminder'); ?>
                                </a>
                            <?php else: ?>
                                <?php 
                                $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit;
                                ?>
                                
                                <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button pm-button-primary pm-button-small">
                                    <span class="button-icon">💌</span>
                                    <?php if ($is_full): ?>
                                        <?php _e('Join Waitlist', 'partyminder'); ?>
                                    <?php else: ?>
                                        <?php _e('RSVP Now', 'partyminder'); ?>
                                    <?php endif; ?>
                                </a>
                                
                                <button type="button" class="pm-button pm-button-secondary pm-button-small share-event" 
                                        data-url="<?php echo esc_url(get_permalink($event->ID)); ?>" 
                                        data-title="<?php echo esc_attr($event->title); ?>">
                                    <span class="button-icon">📤</span>
                                    <?php _e('Share', 'partyminder'); ?>
                                </button>
                            <?php endif; ?>
                        </footer>
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
                    <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-button pm-button-primary">
                        <span class="button-icon">✨</span>
                        <?php _e('Create First Event', 'partyminder'); ?>
                    </a>
                </div>
                <?php endif; ?>
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