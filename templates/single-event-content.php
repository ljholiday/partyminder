<?php
/**
 * Single Event Content Template - Content Only
 * For theme integration via the_content filter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get event data
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();

$event = $event_manager->get_event(get_the_ID());

if (!$event) {
    echo '<div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; margin: 20px;">';
    echo '<h3>Event Not Found</h3>';
    echo '<p>Event ID: ' . get_the_ID() . '</p>';
    echo '<p>Post Type: ' . get_post_type() . '</p>';
    echo '</div>';
    return;
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
$button_style = get_option('partyminder_button_style', 'rounded');

$event_date = new DateTime($event->event_date);
$is_today = $event_date->format('Y-m-d') === date('Y-m-d');
$is_tomorrow = $event_date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
$is_past = $event_date < new DateTime();
?>

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}
</style>

<div class="partyminder-single-event">
    <div class="event-header">
        <h1 class="event-title"><?php echo esc_html($event->title); ?></h1>
        
        <?php if ($is_past): ?>
            <div class="event-status">
                <span class="past-event">
                    📅 Past Event
                </span>
            </div>
        <?php elseif ($is_today): ?>
            <div class="event-status">
                <span class="today-event">
                    🎉 Today!
                </span>
            </div>
        <?php elseif ($is_tomorrow): ?>
            <div class="event-status">
                <span class="tomorrow-event">
                    ⏰ Tomorrow
                </span>
            </div>
        <?php endif; ?>
        
        <div class="event-meta">
            <div class="meta-item">
                <span>📅</span>
                <span>
                    <?php if ($is_today): ?>
                        <?php _e('Today', 'partyminder'); ?>
                    <?php elseif ($is_tomorrow): ?>
                        <?php _e('Tomorrow', 'partyminder'); ?>
                    <?php else: ?>
                        <?php echo $event_date->format('l, F j, Y'); ?>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="meta-item">
                <span>🕐</span>
                <span><?php echo $event_date->format('g:i A'); ?></span>
            </div>
            
            <?php if ($event->venue_info): ?>
            <div class="meta-item">
                <span>📍</span>
                <span><?php echo esc_html($event->venue_info); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="meta-item">
                <span>👥</span>
                <span>
                    <?php echo $event->guest_stats->confirmed ?? 0; ?> confirmed
                    <?php if ($event->guest_limit > 0): ?>
                        of <?php echo $event->guest_limit; ?> max
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    
    <?php if (has_post_thumbnail()): ?>
    <div class="event-image">
        <?php the_post_thumbnail('large'); ?>
    </div>
    <?php endif; ?>
    
    <div class="event-content">
        <?php if ($event->description): ?>
            <div class="event-description">
                <h3>About This Event</h3>
                <?php echo wpautop($event->description); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($event->host_notes): ?>
            <div class="host-notes">
                <h3>Host Notes</h3>
                <?php echo wpautop($event->host_notes); ?>
            </div>
        <?php endif; ?>
        
        <div class="event-stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $event->guest_stats->confirmed ?? 0; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $event->guest_stats->pending ?? 0; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <?php if (($event->guest_stats->maybe ?? 0) > 0): ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo $event->guest_stats->maybe ?? 0; ?></div>
                <div class="stat-label">Maybe</div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!$is_past): ?>
            <div class="event-actions">
                <?php 
                $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit;
                ?>
                
                <a href="#rsvp" class="pm-button pm-button-primary">
                    <?php if ($is_full): ?>
                        🎟️ Join Waitlist
                    <?php else: ?>
                        💌 RSVP Now
                    <?php endif; ?>
                </a>
                
                <button type="button" class="pm-button pm-button-secondary" onclick="shareEvent()">
                    📤 Share Event
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!$is_past): ?>
    <!-- RSVP Form Section -->
    <div class="event-rsvp" id="rsvp">
        <?php echo do_shortcode('[partyminder_rsvp_form event_id="' . $event->ID . '"]'); ?>
    </div>
    <?php endif; ?>
    
    <!-- Event Details -->
    <div class="event-details">
        <h3>Event Details</h3>
        <table>
            <tr>
                <td><strong>Host Email:</strong></td>
                <td><?php echo esc_html($event->host_email); ?></td>
            </tr>
            <tr>
                <td><strong>Created:</strong></td>
                <td><?php echo date('F j, Y', strtotime($event->created_date)); ?></td>
            </tr>
            <?php if ($event->guest_limit > 0): ?>
            <tr>
                <td><strong>Guest Limit:</strong></td>
                <td><?php echo $event->guest_limit; ?> people</td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function shareEvent() {
    const url = window.location.href;
    const title = '<?php echo esc_js($event->title); ?>';
    
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
        // Fallback: open social sharing
        window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
    }
}
</script>