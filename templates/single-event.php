<?php
get_header(); 

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
    get_footer();
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
.partyminder-single-event {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}
.event-header {
    text-align: center;
    margin-bottom: 30px;
}
.event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    margin: 20px 0;
}
.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f9fa;
    padding: 8px 16px;
    border-radius: 20px;
}
.event-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin: 20px 0;
}
.event-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}
.stat-item {
    background: var(--pm-primary);
    color: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    flex: 1;
}
.pm-button {
    background: var(--pm-primary);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    margin: 10px 10px 10px 0;
}
.pm-button:hover {
    opacity: 0.9;
    color: white;
}
.pm-button-secondary {
    background: #6c757d;
}
</style>

<div class="partyminder-single-event">
    <div class="event-header">
        <h1 class="event-title"><?php echo esc_html($event->title); ?></h1>
        
        <?php if ($is_past): ?>
            <div class="event-status past-event">
                <span style="background: #dc3545; color: white; padding: 4px 12px; border-radius: 20px;">
                    📅 Past Event
                </span>
            </div>
        <?php elseif ($is_today): ?>
            <div class="event-status today-event">
                <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 20px;">
                    🎉 Today!
                </span>
            </div>
        <?php elseif ($is_tomorrow): ?>
            <div class="event-status tomorrow-event">
                <span style="background: #ffc107; color: black; padding: 4px 12px; border-radius: 20px;">
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
                    <?php echo $event->guest_stats->confirmed; ?> confirmed
                    <?php if ($event->guest_limit > 0): ?>
                        of <?php echo $event->guest_limit; ?> max
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    
    <?php if (has_post_thumbnail()): ?>
    <div class="event-image" style="text-align: center; margin: 20px 0;">
        <?php the_post_thumbnail('large', array('style' => 'max-width: 100%; border-radius: 8px;')); ?>
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
                <div class="stat-number"><?php echo $event->guest_stats->confirmed; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $event->guest_stats->pending; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <?php if ($event->guest_stats->maybe > 0): ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo $event->guest_stats->maybe; ?></div>
                <div class="stat-label">Maybe</div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!$is_past): ?>
            <div class="event-actions" style="text-align: center; margin-top: 30px;">
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
    <div class="event-rsvp" id="rsvp" style="margin-top: 40px;">
        <?php echo do_shortcode('[partyminder_rsvp_form event_id="' . $event->ID . '"]'); ?>
    </div>
    <?php endif; ?>
    
    <!-- Event Details -->
    <div class="event-details" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
        <h3>Event Details</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Host Email:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($event->host_email); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Created:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo date('F j, Y', strtotime($event->created_date)); ?></td>
            </tr>
            <?php if ($event->guest_limit > 0): ?>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Guest Limit:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo $event->guest_limit; ?> people</td>
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

<?php get_footer(); ?>