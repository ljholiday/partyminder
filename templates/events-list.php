<?php
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

// Get events from custom table
global $wpdb;
$events_table = $wpdb->prefix . 'partyminder_events';

$where_clause = "WHERE event_status = 'active'";
if (!$show_past) {
    // Default: show upcoming events only
    $where_clause .= " AND event_date >= CURDATE()";
}

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

.events-header h2 {
    font-size: 2.5em;
    margin-bottom: 10px;
    color: var(--pm-primary);
}

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

.btn {
    background: var(--pm-primary);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
    transition: background 0.3s ease;
    cursor: pointer;
}

.btn:hover {
    opacity: 0.9;
    color: white;
}

.btn-secondary {
    background: #6c757d;
}

.btn-small {
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

.events-stats {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: var(--pm-primary);
}

.stat-label {
    color: #666;
    font-size: 0.9em;
}
</style>

<div class="partyminder-events-container">
    
    <!-- Events Header -->
    <div class="events-header">
        <h2 class="events-title">
            <?php if ($show_past): ?>
                <?php _e('All Events', 'partyminder'); ?>
            <?php else: ?>
                <?php _e(' Upcoming Events', 'partyminder'); ?>
            <?php endif; ?>
        </h2>
        
        <p class="events-description">
            <?php if ($show_past): ?>
                <?php _e('Browse through our collection of events and gatherings.', 'partyminder'); ?>
            <?php else: ?>
                <?php _e('Discover amazing events happening near you. Join the community!', 'partyminder'); ?>
            <?php endif; ?>
        </p>
        
        <!-- Events Navigation -->
        <?php if (is_user_logged_in()): ?>
        <div class="events-navigation" style="margin: 20px 0; text-align: center;">
            <a href="<?php echo esc_url(PartyMinder::get_my_events_url()); ?>" class="pm-btn pm-btn-secondary" style="margin-right: 10px;">
                 <?php _e('My Events', 'partyminder'); ?>
            </a>
            <a href="<?php echo esc_url(PartyMinder::get_create_event_url()); ?>" class="pm-btn">
                 <?php _e('Create Event', 'partyminder'); ?>
            </a>
        </div>
        <?php endif; ?>
        
        <div class="events-stats">
            <span class="stat-item">
                <span class="pm-stat-number"><?php echo count($events); ?></span>
                <span class="pm-stat-label"><?php _e('Events', 'partyminder'); ?></span>
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
                
                <div class="event-card <?php echo $is_past ? 'past-event' : ''; ?>">
                    
                    <?php if ($event->featured_image): ?>
                    <div class="event-image">
                        <?php echo '<img src="' . esc_url($event->featured_image) . '" alt="' . esc_attr($event->title) . '" style="max-width: 100%; height: auto;">'; ?>
                        <?php if ($is_past): ?>
                            <div class="event-overlay past-overlay">
                                <span class="overlay-text"><?php _e('Past Event', 'partyminder'); ?></span>
                            </div>
                        <?php elseif ($is_today): ?>
                            <div class="event-overlay today-overlay">
                                <span class="overlay-text"><?php _e('Today!', 'partyminder'); ?></span>
                            </div>
                        <?php elseif ($is_tomorrow): ?>
                            <div class="event-overlay tomorrow-overlay">
                                <span class="overlay-text"><?php _e('Tomorrow', 'partyminder'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="event-content">
                        <div class="event-header">
                            <h3 class="event-title">
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>"><?php echo esc_html($event->title); ?></a>
                            </h3>
                            
                            <div class="event-meta">
                                <div class="meta-item event-date-meta">
                                    <span class="meta-icon"></span>
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
                                    <span class="meta-icon"></span>
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
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn btn-secondary btn-small style-<?php echo esc_attr($button_style); ?>">
                                    <span class="button-icon"></span>
                                    <?php _e('View Details', 'partyminder'); ?>
                                </a>
                            <?php else: ?>
                                <?php 
                                $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit;
                                ?>
                                
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn btn-small style-<?php echo esc_attr($button_style); ?>">
                                    <span class="button-icon"></span>
                                    <?php if ($is_full): ?>
                                        <?php _e('Join Waitlist', 'partyminder'); ?>
                                    <?php else: ?>
                                        <?php _e('RSVP Now', 'partyminder'); ?>
                                    <?php endif; ?>
                                </a>
                                
                                <button type="button" class="btn btn-secondary btn-small share-event style-<?php echo esc_attr($button_style); ?>" 
                                        data-url="<?php echo esc_url(home_url('/events/' . $event->slug)); ?>" 
                                        data-title="<?php echo esc_attr($event->title); ?>">
                                    <span class="button-icon">📤</span>
                                    <?php _e('Share', 'partyminder'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($event->guest_stats->confirmed > 0): ?>
                    <div class="event-guests">
                        <div class="guests-preview">
                            <?php
                            $confirmed_guests = $guest_manager->get_event_guests($event->id, 'confirmed');
                            $guests_to_show = array_slice($confirmed_guests, 0, 5);
                            ?>
                            
                            <div class="guest-avatars">
                                <?php foreach ($guests_to_show as $guest): ?>
                                    <div class="guest-avatar" title="<?php echo esc_attr($guest->name); ?>">
                                        <?php echo strtoupper(substr($guest->name, 0, 1)); ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($confirmed_guests) > 5): ?>
                                    <div class="guest-avatar more-guests">
                                        +<?php echo count($confirmed_guests) - 5; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Load More (if needed) -->
        <?php if (count($events) >= $limit): ?>
        <div class="events-pagination">
            <button type="button" class="btn btn-secondary load-more-events style-<?php echo esc_attr($button_style); ?>" 
                    data-page="2" data-limit="<?php echo esc_attr($limit); ?>">
                <span class="button-icon">⬇️</span>
                <?php _e('Load More Events', 'partyminder'); ?>
            </button>
        </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- No Events Found -->
        <div class="no-events-found">
            <div class="no-events-icon"></div>
            <div class="no-events-content">
                <h3><?php _e('No Events Found', 'partyminder'); ?></h3>
                <?php if ($show_past): ?>
                    <p><?php _e('There are no past events to display.', 'partyminder'); ?></p>
                <?php else: ?>
                    <p><?php _e('There are no upcoming events scheduled. Check back soon!', 'partyminder'); ?></p>
                <?php endif; ?>
                
                <?php if (current_user_can('publish_posts')): ?>
                <div class="no-events-actions">
                    <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="btn style-<?php echo esc_attr($button_style); ?>">
                        <span class="button-icon"></span>
                        <?php _e('Create First Event', 'partyminder'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Newsletter Signup -->
    <div class="events-newsletter">
        <div class="newsletter-content">
            <h3><?php _e('Stay Updated', 'partyminder'); ?></h3>
            <p><?php _e('Get notified about new events in your area.', 'partyminder'); ?></p>
            
            <form class="newsletter-form" id="events-newsletter-form">
                <div class="pm-form-group">
                    <input type="email" placeholder="<?php esc_attr_e('Enter your email', 'partyminder'); ?>" required />
                    <button type="submit" class="btn style-<?php echo esc_attr($button_style); ?>">
                        <span class="button-icon">📧</span>
                        <?php _e('Subscribe', 'partyminder'); ?>
                    </button>
                </div>
                <small class="newsletter-disclaimer">
                    <?php _e('We respect your privacy. Unsubscribe anytime.', 'partyminder'); ?>
                </small>
            </form>
        </div>
    </div>
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
    
    // Load more events
    const loadMoreBtn = document.querySelector('.load-more-events');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const page = parseInt(this.dataset.page);
            const limit = parseInt(this.dataset.limit);
            
            this.disabled = true;
            this.textContent = '<?php _e("Loading...", "partyminder"); ?>';
            
            // AJAX call to load more events
            fetch(partyminder_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'partyminder_load_more_events',
                    nonce: partyminder_ajax.nonce,
                    page: page,
                    limit: limit
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.html) {
                    document.querySelector('.events-grid').insertAdjacentHTML('beforeend', data.data.html);
                    
                    if (data.data.has_more) {
                        this.dataset.page = page + 1;
                        this.disabled = false;
                        this.innerHTML = '<span class="button-icon">⬇️</span> <?php _e("Load More Events", "partyminder"); ?>';
                    } else {
                        this.style.display = 'none';
                    }
                } else {
                    this.style.display = 'none';
                }
            })
            .catch(() => {
                this.disabled = false;
                this.innerHTML = '<span class="button-icon">⬇️</span> <?php _e("Load More Events", "partyminder"); ?>';
            });
        });
    }
    
    // Newsletter signup
    document.getElementById('events-newsletter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = this.querySelector('input[type="email"]').value;
        const button = this.querySelector('button');
        
        button.disabled = true;
        button.textContent = '<?php _e("Subscribing...", "partyminder"); ?>';
        
        // Simple success message for demo
        setTimeout(() => {
            this.innerHTML = '<div class="newsletter-success">✅ <?php _e("Thank you for subscribing!", "partyminder"); ?></div>';
        }, 1000);
    });
});
</script>