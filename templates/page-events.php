<?php
/**
 * Template for Events Page
 * Displays all public events
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

// Get page attributes
$show_past = isset($_GET['show_past']) ? filter_var($_GET['show_past'], FILTER_VALIDATE_BOOLEAN) : false;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

// Get events
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
    
    $events = array();
    foreach ($results as $result) {
        $event = $event_manager->get_event($result->ID);
        if ($event) {
            $events[] = $event;
        }
    }
} else {
    // Get upcoming events only
    $events = $event_manager->get_upcoming_events($limit);
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
$button_style = get_option('partyminder_button_style', 'rounded');
?>

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-events-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.events-header {
    text-align: center;
    margin-bottom: 40px;
}

.events-header h1 {
    font-size: 2.5em;
    margin-bottom: 10px;
    color: var(--pm-primary);
}

.events-breadcrumb {
    margin-bottom: 20px;
}

.events-breadcrumb a {
    color: var(--pm-primary);
    text-decoration: none;
}

.events-breadcrumb a:hover {
    text-decoration: underline;
}

.events-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.events-filter {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.pm-button {
    background: var(--pm-primary);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
    transition: background 0.3s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.pm-button:hover {
    opacity: 0.9;
    color: white;
}

.pm-button-secondary {
    background: #6c757d;
}

.pm-button.style-rounded {
    border-radius: 6px;
}

.pm-button.style-pill {
    border-radius: 25px;
}

.pm-button.style-square {
    border-radius: 0;
}

/* Include existing events grid styles */
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin: 40px 0;
}

.event-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.event-content {
    padding: 20px;
}

.event-title a {
    color: #333;
    text-decoration: none;
    font-size: 1.4em;
    font-weight: bold;
}

.event-title a:hover {
    color: var(--pm-primary);
}

.event-meta {
    margin: 15px 0;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    margin: 5px 15px 5px 0;
    font-size: 0.9em;
    color: #666;
}

.meta-icon {
    margin-right: 8px;
}

.event-description {
    margin: 15px 0;
    color: #666;
    line-height: 1.5;
}

.event-stats {
    display: flex;
    gap: 15px;
    margin: 15px 0;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9em;
    color: #666;
}

.event-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.pm-button-small {
    padding: 8px 16px;
    font-size: 0.85em;
}

.no-events-found {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-events-icon {
    font-size: 4em;
    margin-bottom: 20px;
}
</style>

<div class="partyminder-events-container">
    
    <!-- Breadcrumb -->
    <div class="events-breadcrumb">
        <a href="<?php echo home_url(); ?>"><?php _e('Home', 'partyminder'); ?></a> 
        &raquo; <?php _e('Events', 'partyminder'); ?>
    </div>
    
    <!-- Page Header -->
    <div class="events-header">
        <h1>
            <?php if ($show_past): ?>
                <?php _e('All Events', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('🎉 Upcoming Events', 'partyminder'); ?>
            <?php endif; ?>
        </h1>
        
        <p>
            <?php if ($show_past): ?>
                <?php _e('Browse through our collection of events and gatherings.', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('Discover amazing events happening near you. Join the community!', 'partyminder'); ?>
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Page Actions -->
    <div class="events-actions">
        <div class="events-filter">
            <a href="<?php echo remove_query_arg('show_past'); ?>" class="pm-button pm-button-secondary style-<?php echo esc_attr($button_style); ?> <?php echo !$show_past ? 'active' : ''; ?>">
                <span>📅</span>
                <?php _e('Upcoming Events', 'partyminder'); ?>
            </a>
            <a href="<?php echo add_query_arg('show_past', '1'); ?>" class="pm-button pm-button-secondary style-<?php echo esc_attr($button_style); ?> <?php echo $show_past ? 'active' : ''; ?>">
                <span>📚</span>
                <?php _e('Past Events', 'partyminder'); ?>
            </a>
        </div>
        
        <div class="events-cta">
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-button style-<?php echo esc_attr($button_style); ?>">
                    <span>✨</span>
                    <?php _e('Create Event', 'partyminder'); ?>
                </a>
                <a href="<?php echo PartyMinder::get_my_events_url(); ?>" class="pm-button pm-button-secondary style-<?php echo esc_attr($button_style); ?>">
                    <span>👤</span>
                    <?php _e('My Events', 'partyminder'); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-button style-<?php echo esc_attr($button_style); ?>">
                    <span>🔐</span>
                    <?php _e('Login to Create Events', 'partyminder'); ?>
                </a>
            <?php endif; ?>
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
                
                <div class="event-card <?php echo $is_past ? 'past-event' : ''; ?>">
                    
                    <?php if (has_post_thumbnail($event->ID)): ?>
                    <div class="event-image">
                        <?php echo get_the_post_thumbnail($event->ID, 'medium'); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="event-content">
                        <div class="event-header">
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
                        </div>
                        
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
                        
                        <div class="event-actions">
                            <?php if ($is_past): ?>
                                <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button pm-button-secondary pm-button-small style-<?php echo esc_attr($button_style); ?>">
                                    <span>📖</span>
                                    <?php _e('View Details', 'partyminder'); ?>
                                </a>
                            <?php else: ?>
                                <?php 
                                $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit;
                                ?>
                                
                                <a href="<?php echo get_permalink($event->ID); ?>" class="pm-button pm-button-primary pm-button-small style-<?php echo esc_attr($button_style); ?>">
                                    <span>💌</span>
                                    <?php if ($is_full): ?>
                                        <?php _e('Join Waitlist', 'partyminder'); ?>
                                    <?php else: ?>
                                        <?php _e('RSVP Now', 'partyminder'); ?>
                                    <?php endif; ?>
                                </a>
                                
                                <button type="button" class="pm-button pm-button-secondary pm-button-small share-event style-<?php echo esc_attr($button_style); ?>" 
                                        data-url="<?php echo esc_url(get_permalink($event->ID)); ?>" 
                                        data-title="<?php echo esc_attr($event->title); ?>">
                                    <span>📤</span>
                                    <?php _e('Share', 'partyminder'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
                
                <?php if (is_user_logged_in()): ?>
                <div class="no-events-actions">
                    <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-button style-<?php echo esc_attr($button_style); ?>">
                        <span>✨</span>
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

<?php get_footer(); ?>