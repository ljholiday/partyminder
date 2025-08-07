<?php
/**
 * Single Event Content Template
 * Single event page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get event data from global variable set by main plugin
$event = $GLOBALS['partyminder_current_event'] ?? null;

if (!$event) {
    echo '<p>Event not found.</p>';
    return;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-guest-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';

$event_manager = new PartyMinder_Event_Manager();
$guest_manager = new PartyMinder_Guest_Manager();
$conversation_manager = new PartyMinder_Conversation_Manager();

// Get additional event data
$event->guest_stats = $event_manager->get_guest_stats($event->id);
$event_conversations = $conversation_manager->get_event_conversations($event->id);

$event_date = new DateTime($event->event_date);
$is_today = $event_date->format('Y-m-d') === date('Y-m-d');
$is_tomorrow = $event_date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
$is_past = $event_date < new DateTime();

// Check if user is event host
$current_user = wp_get_current_user();
$is_event_host = (is_user_logged_in() && $current_user->ID == $event->author_id) || 
                ($current_user->user_email == $event->host_email) ||
                current_user_can('edit_others_posts');

// Set up template variables
$page_title = esc_html($event->title);
$page_description = '';

// Main content
ob_start();
?>
<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <div class="pm-flex pm-flex-between">
                <div>
                    <?php if ($is_past): ?>
                        <div class="pm-badge pm-badge-secondary pm-mb-2">Past Event</div>
                    <?php elseif ($is_today): ?>
                        <div class="pm-badge pm-badge-success pm-mb-2">Today</div>
                    <?php elseif ($is_tomorrow): ?>
                        <div class="pm-badge pm-badge-warning pm-mb-2">Tomorrow</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="pm-card-body">
            <div class="pm-grid pm-grid-2 pm-gap pm-mb-4">
                <div class="pm-flex pm-gap">
                    <strong>Date:</strong>
                    <span>
                        <?php if ($is_today): ?>
                            Today
                        <?php elseif ($is_tomorrow): ?>
                            Tomorrow
                        <?php else: ?>
                            <?php echo $event_date->format('l, F j, Y'); ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="pm-flex pm-gap">
                    <strong>Time:</strong>
                    <span><?php echo $event_date->format('g:i A'); ?></span>
                </div>
                
                <?php if ($event->venue_info): ?>
                <div class="pm-flex pm-gap">
                    <strong>Location:</strong>
                    <span><?php echo esc_html($event->venue_info); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="pm-flex pm-gap">
                    <strong>Guests:</strong>
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
</div>

<?php if ($event->featured_image): ?>
<div class="pm-section pm-mb">
    <div class="pm-card">
        <img src="<?php echo esc_url($event->featured_image); ?>" alt="<?php echo esc_attr($event->title); ?>" style="width: 100%; height: auto;">
    </div>
</div>
<?php endif; ?>

<?php if ($event->description): ?>
<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md">About This Event</h3>
        </div>
        <div class="pm-card-body">
            <?php echo wpautop(esc_html($event->description)); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($event->host_notes): ?>
<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md">Host Notes</h3>
        </div>
        <div class="pm-card-body">
            <?php echo wpautop(esc_html($event->host_notes)); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="pm-section">
    <div class="pm-card">
        <div class="pm-card-header">
            <div class="pm-flex pm-flex-between">
                <h3 class="pm-heading pm-heading-md">Event Conversations</h3>
                <?php if (is_user_logged_in()): ?>
                <a href="<?php echo add_query_arg('event_id', $event->id, PartyMinder::get_create_conversation_url()); ?>" class="pm-btn pm-btn-sm">
                    Create Conversation
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="pm-card-body">
            <?php if (!empty($event_conversations)): ?>
                <?php foreach ($event_conversations as $conversation): ?>
                    <div class="pm-mb-4">
                        <div class="pm-flex pm-flex-between pm-mb-2">
                            <h4 class="pm-heading pm-heading-sm">
                                <a href="<?php echo home_url('/conversations/' . ($conversation->topic_slug ?? 'general') . '/' . $conversation->slug); ?>" class="pm-text-primary">
                                    <?php echo esc_html($conversation->title); ?>
                                </a>
                            </h4>
                            <div class="pm-stat pm-text-center">
                                <div class="pm-stat-number pm-text-primary"><?php echo $conversation->reply_count ?? 0; ?></div>
                                <div class="pm-stat-label">Replies</div>
                            </div>
                        </div>
                        <div class="pm-text-muted pm-mb-2">
                            <?php 
                            $content_preview = wp_trim_words(strip_tags($conversation->content), 15, '...');
                            echo esc_html($content_preview); 
                            ?>
                        </div>
                        <div class="pm-text-muted">
                            by <?php echo esc_html($conversation->author_name); ?> • <?php echo human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp')); ?> ago
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="pm-text-center pm-p-4">
                    <p class="pm-text-muted">No conversations started yet for this event.</p>
                    <p class="pm-text-muted">Be the first to start planning and discussing ideas!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>
<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md">Event Stats</h3>
        </div>
        <div class="pm-card-body">
            <div class="pm-grid pm-grid-2 pm-gap">
                <div class="pm-stat pm-text-center">
                    <div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->confirmed ?? 0; ?></div>
                    <div class="pm-stat-label">Confirmed</div>
                </div>
                <div class="pm-stat pm-text-center">
                    <div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->pending ?? 0; ?></div>
                    <div class="pm-stat-label">Pending</div>
                </div>
                <?php if (($event->guest_stats->maybe ?? 0) > 0): ?>
                <div class="pm-stat pm-text-center">
                    <div class="pm-stat-number pm-text-primary"><?php echo $event->guest_stats->maybe ?? 0; ?></div>
                    <div class="pm-stat-label">Maybe</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$is_past): ?>
<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-sm">Quick Actions</h3>
        </div>
        <div class="pm-card-body">
            <?php $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit; ?>
            
            <div class="pm-flex pm-flex-column pm-gap">
                <?php if ($is_event_host): ?>
                    <a href="<?php echo PartyMinder::get_edit_event_url($event->id); ?>" class="pm-btn">
                        Edit Event Details
                    </a>
                <?php else: ?>
                    <a href="#rsvp" class="pm-btn">
                        <?php if ($is_full): ?>
                            Join Waitlist
                        <?php else: ?>
                            RSVP Now
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                
                <button type="button" class="pm-btn pm-btn-secondary" onclick="shareEvent()">
                    Share Event
                </button>
                
                <?php if (is_user_logged_in()): ?>
                <a href="<?php echo add_query_arg('event_id', $event->id, PartyMinder::get_create_conversation_url()); ?>" class="pm-btn pm-btn-secondary">
                    Create Conversation
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md">Event Details</h3>
        </div>
        <div class="pm-card-body">
            <div class="pm-flex pm-flex-column pm-gap">
                <div>
                    <strong class="pm-text-primary">Host:</strong><br>
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

<?php if (!$is_past && !$is_event_host): ?>
<div class="pm-section pm-mb" id="rsvp">
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md">RSVP for this Event</h3>
        </div>
        <div class="pm-card-body">
            <?php echo do_shortcode('[partyminder_rsvp_form event_id="' . $event->id . '"]'); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
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
        window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
    }
}
</script>