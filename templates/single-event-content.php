<?php
/**
 * Single Event Content Template - Content Only
 * For theme integration via the_content filter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get event data from global variable set by main plugin
$event = $GLOBALS['partyminder_current_event'] ?? null;

if (!$event) {
    echo '<div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; margin: 20px;">';
    echo '<h3>Event Not Found</h3>';
    echo '<p>No event data available</p>';
    echo '</div>';
    return;
}


$event_date = new DateTime($event->event_date);
$is_today = $event_date->format('Y-m-d') === date('Y-m-d');
$is_tomorrow = $event_date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
$is_past = $event_date < new DateTime();
?>


<div class="partyminder-content pm-container">
    <div class="pm-card">
        <div class="pm-card-header">
            <h1 class="pm-title-primary pm-m-0"><?php echo esc_html($event->title); ?></h1>
            
            <?php if ($is_past): ?>
                <div class="pm-badge pm-badge-warning">
                    📅 Past Event
                </div>
            <?php elseif ($is_today): ?>
                <div class="pm-badge pm-badge-success">
                    🎉 Today!
                </div>
            <?php elseif ($is_tomorrow): ?>
                <div class="pm-badge pm-badge-primary">
                    ⏰ Tomorrow
                </div>
            <?php endif; ?>
        </div>
        
        <div class="pm-card-body">
            <div class="pm-grid pm-grid-4 pm-mb-4">
                <div class="pm-meta-item">
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
                
                <div class="pm-meta-item">
                    <span>🕐</span>
                    <span><?php echo $event_date->format('g:i A'); ?></span>
                </div>
                
                <?php if ($event->venue_info): ?>
                <div class="pm-meta-item">
                    <span>📍</span>
                    <span><?php echo esc_html($event->venue_info); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="pm-meta-item">
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
    </div>
    
    <?php if ($event->featured_image): ?>
    <div class="pm-card pm-mb-6">
        <img src="<?php echo esc_url($event->featured_image); ?>" alt="<?php echo esc_attr($event->title); ?>" class="pm-w-full" style="height: auto; border-radius: var(--pm-radius);">
    </div>
    <?php endif; ?>
    
    <div class="pm-card pm-mb-6">
        <?php if ($event->description): ?>
            <div class="pm-card-header">
                <h3 class="pm-title-secondary pm-m-0">About This Event</h3>
            </div>
            <div class="pm-card-body">
                <?php echo wpautop($event->description); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($event->host_notes): ?>
            <?php if ($event->description): ?>
                <div class="pm-card-footer pm-border-top">
            <?php else: ?>
                <div class="pm-card-header">
                    <h3 class="pm-title-secondary pm-m-0">Host Notes</h3>
                </div>
                <div class="pm-card-body">
            <?php endif; ?>
                <h4 class="pm-heading pm-heading-sm">Host Notes</h4>
                <?php echo wpautop($event->host_notes); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="pm-card pm-mb-6">
        <div class="pm-card-header">
            <h3 class="pm-title-secondary pm-m-0">Event Stats</h3>
        </div>
        <div class="pm-card-body">
            <div class="pm-grid pm-grid-4">
                <div class="pm-stat">
                    <div class="pm-stat-number pm-text-success"><?php echo $event->guest_stats->confirmed ?? 0; ?></div>
                    <div class="pm-stat-label">Confirmed</div>
                </div>
                <div class="pm-stat">
                    <div class="pm-stat-number pm-text-warning"><?php echo $event->guest_stats->pending ?? 0; ?></div>
                    <div class="pm-stat-label">Pending</div>
                </div>
                <?php if (($event->guest_stats->maybe ?? 0) > 0): ?>
                <div class="pm-stat">
                    <div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->maybe ?? 0; ?></div>
                    <div class="pm-stat-label">Maybe</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if (!$is_past): ?>
        <div class="pm-card pm-mb-6">
            <div class="pm-card-body">
                <?php 
                $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit;
                $current_user = wp_get_current_user();
                $is_event_host = (is_user_logged_in() && $current_user->ID == $event->author_id) || 
                                ($current_user->user_email == $event->host_email) ||
                                current_user_can('edit_others_posts');
                ?>
                
                <div class="pm-flex pm-flex-center-gap" style="flex-wrap: wrap;">
                    <?php if ($is_event_host): ?>
                        <button class="pm-button pm-button-primary manage-event-btn" 
                                data-event-id="<?php echo esc_attr($event->id); ?>"
                                data-event-title="<?php echo esc_attr($event->title); ?>"
                                data-event-slug="<?php echo esc_attr($event->slug); ?>">
                            <span>⚙️</span>
                            <?php _e('Manage Event', 'partyminder'); ?>
                        </button>
                        
                        <a href="<?php echo PartyMinder::get_edit_event_url($event->id); ?>" class="pm-button pm-button-secondary">
                            <span>✏️</span>
                            <?php _e('Edit Details', 'partyminder'); ?>
                        </a>
                    <?php else: ?>
                        <a href="#rsvp" class="pm-button pm-button-primary">
                            <?php if ($is_full): ?>
                                🎟️ Join Waitlist
                            <?php else: ?>
                                💌 RSVP Now
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                    <button type="button" class="pm-button pm-button-secondary" onclick="shareEvent()">
                        📤 Share Event
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!$is_past): ?>
    <!-- RSVP Form Section -->
    <div class="pm-card pm-mb-6" id="rsvp">
        <div class="pm-card-header">
            <h3 class="pm-title-secondary pm-m-0">RSVP for this Event</h3>
        </div>
        <div class="pm-card-body">
            <?php echo do_shortcode('[partyminder_rsvp_form event_id="' . $event->id . '"]'); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Event Details -->
    <div class="pm-card pm-mb-6">
        <div class="pm-card-header">
            <h3 class="pm-title-secondary pm-m-0">Event Details</h3>
        </div>
        <div class="pm-card-body">
            <div class="pm-grid pm-grid-3">
                <div>
                    <strong class="pm-text-primary">Host Email:</strong><br>
                    <span class="pm-text-muted"><?php echo esc_html($event->host_email); ?></span>
                </div>
                <div>
                    <strong class="pm-text-primary">Created:</strong><br>
                    <span class="pm-text-muted"><?php echo date('F j, Y', strtotime($event->created_at)); ?></span>
                </div>
                <?php if ($event->guest_limit > 0): ?>
                <div>
                    <strong class="pm-text-primary">Guest Limit:</strong><br>
                    <span class="pm-text-muted"><?php echo $event->guest_limit; ?> people</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include event management modal if user is the event host
$current_user = wp_get_current_user();
$is_event_host = (is_user_logged_in() && $current_user->ID == $event->author_id) || 
                ($current_user->user_email == $event->host_email) ||
                current_user_can('edit_others_posts');

if ($is_event_host) {
    include PARTYMINDER_PLUGIN_DIR . 'templates/event-management-modal.php';
}
?>

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

<?php if ($is_event_host): ?>
// Handle manage event button clicks
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.manage-event-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get event data from button attributes
            const eventData = {
                id: this.getAttribute('data-event-id'),
                title: this.getAttribute('data-event-title'),
                slug: this.getAttribute('data-event-slug')
            };
            
            // Show the management modal
            if (typeof window.showEventManagementModal === 'function') {
                window.showEventManagementModal(eventData);
            } else {
                console.error('Event management modal not loaded');
            }
        });
    });
});
<?php endif; ?>
</script>