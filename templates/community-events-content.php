<?php
/**
 * Community Events Content Template
 * Events view for individual community - uses two-column layout
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';

$community_manager = new PartyMinder_Community_Manager();
$event_manager = new PartyMinder_Event_Manager();

// Get community slug from URL
$community_slug = get_query_var('community_slug');
if (!$community_slug) {
    wp_redirect(PartyMinder::get_communities_url());
    exit;
}

// Get community
$community = $community_manager->get_community_by_slug($community_slug);
if (!$community) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
}

// Get current user info
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$is_member = false;
$user_role = null;

if ($is_logged_in) {
    $is_member = $community_manager->is_member($community->id, $current_user->ID);
    $user_role = $community_manager->get_member_role($community->id, $current_user->ID);
}

// Check if user can view events
$can_view_events = true;
if ($community->privacy === 'private' && !$is_member) {
    $can_view_events = false;
}

// Get community events (if allowed to view)
$events = array();
$event_count = 0;
if ($can_view_events) {
    // For now, get all public events - will be enhanced to show community-specific events
    $events = $event_manager->get_upcoming_events(20);
    
    // Filter to community events when that relationship is implemented
    $event_count = count($events);
}

// Set up template variables
$page_title = sprintf(__('Community Events - %s', 'partyminder'), esc_html($community->name));
$page_description = sprintf(__('Events organized by the %s community. Join community events and connect with other members.', 'partyminder'), esc_html($community->name));

// Main content
ob_start();
?>

<div class="pm-section pm-mb-4">
    <!-- Community Navigation Tabs -->
    <div class="pm-nav-tabs pm-mb-4">
        <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="pm-nav-tab">
            <?php _e('Overview', 'partyminder'); ?>
        </a>
        <a href="<?php echo home_url('/communities/' . $community->slug . '/events'); ?>" class="pm-nav-tab pm-nav-tab-active">
            <?php _e('Events', 'partyminder'); ?>
        </a>
        <a href="<?php echo home_url('/communities/' . $community->slug . '/members'); ?>" class="pm-nav-tab">
            <?php _e('Members', 'partyminder'); ?>
        </a>
    </div>

    <!-- Page Header -->
    <div class="pm-flex pm-flex-between pm-mb-4">
        <div>
            <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php _e('Community Events', 'partyminder'); ?></h1>
            <p class="pm-text-muted">
                <?php printf(__('%d events in %s', 'partyminder'), $event_count, esc_html($community->name)); ?>
            </p>
        </div>
        <div>
            <?php if ($is_member): ?>
                <button class="pm-btn pm-create-event-btn">
                    <?php _e('Create Event', 'partyminder'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$can_view_events): ?>
        <!-- Private Community - No Access -->
        <div class="pm-card pm-text-center">
            <div class="pm-card-body">
                <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('Private Community', 'partyminder'); ?></h3>
                <p class="pm-mb-4"><?php _e('This community\'s events are private. You need to be a member to view community events.', 'partyminder'); ?></p>
                
                <?php if (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-btn">
                        <?php _e('Login to Join', 'partyminder'); ?>
                    </a>
                <?php else: ?>
                    <button class="pm-btn pm-join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        <?php _e('Join Community', 'partyminder'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif (empty($events)): ?>
        <!-- No Events Yet -->
        <div class="pm-card pm-text-center">
            <div class="pm-card-body">
                <h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e('No Events Yet', 'partyminder'); ?></h3>
                <p class="pm-mb-4"><?php _e('This community hasn\'t created any events yet. Be the first to plan something amazing!', 'partyminder'); ?></p>
                
                <?php if ($is_member): ?>
                    <button class="pm-btn pm-create-event-btn">
                        <?php _e('Create First Event', 'partyminder'); ?>
                    </button>
                <?php elseif (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-btn">
                        <?php _e('Login to Join', 'partyminder'); ?>
                    </a>
                <?php else: ?>
                    <button class="pm-btn pm-join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        <?php _e('Join to Create Events', 'partyminder'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Event Filters -->
        <div class="pm-flex pm-gap-4 pm-mb-4">
            <span class="pm-text-muted"><?php _e('Filter:', 'partyminder'); ?></span>
            <button class="pm-filter-button pm-btn-secondary pm-active" data-filter="all">
                <?php _e('All Events', 'partyminder'); ?>
            </button>
            <button class="pm-filter-button pm-btn-secondary" data-filter="upcoming">
                <?php _e('Upcoming', 'partyminder'); ?>
            </button>
            <button class="pm-filter-button pm-btn-secondary" data-filter="past">
                <?php _e('Past Events', 'partyminder'); ?>
            </button>
        </div>

        <!-- Events Grid -->
        <div class="pm-grid pm-grid-auto pm-gap-4">
            <?php foreach ($events as $event): ?>
                <?php
                $event_date = new DateTime($event->event_date);
                $today = new DateTime();
                $is_past = $event_date < $today;
                $is_today = $event_date->format('Y-m-d') === $today->format('Y-m-d');
                
                $status_class = $is_past ? 'past' : ($is_today ? 'today' : 'upcoming');
                $status_text = $is_past ? __('Past', 'partyminder') : ($is_today ? __('Today', 'partyminder') : __('Upcoming', 'partyminder'));
                ?>
                
                <div class="pm-event-card pm-card" data-filter-tags="all <?php echo $status_class; ?>">
                    <div class="pm-card-header">
                        <div class="pm-flex pm-flex-between">
                            <h3 class="pm-heading pm-heading-sm">
                                <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-text-primary">
                                    <?php echo esc_html($event->title); ?>
                                </a>
                            </h3>
                            <span class="pm-badge pm-badge-<?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="pm-card-body">
                        <div class="pm-mb-4">
                            <div class="pm-text-muted pm-mb-2">
                                <strong><?php echo $event_date->format('F j, Y'); ?></strong>
                                <?php if ($event->event_time): ?>
                                    at <?php echo esc_html($event->event_time); ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($event->venue_info): ?>
                                <div class="pm-text-muted pm-mb-2">
                                    <?php echo esc_html(wp_trim_words($event->venue_info, 8)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($event->excerpt): ?>
                            <p class="pm-text-muted pm-mb-4">
                                <?php echo esc_html(wp_trim_words($event->excerpt, 20)); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="pm-flex pm-flex-between">
                            <div class="pm-text-muted">
                                <?php printf(__('by %s', 'partyminder'), esc_html($event->host_email)); ?>
                            </div>
                            <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="pm-btn pm-btn-secondary">
                                <?php echo $is_past ? __('View', 'partyminder') : __('RSVP', 'partyminder'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="pm-card pm-mt-4">
            <div class="pm-card-body pm-text-center">
                <p class="pm-text-muted">
                    <?php _e('Note: Currently showing all public events. Community-specific events coming soon!', 'partyminder'); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<div class="pm-section pm-mb-4">
    <!-- Community Info -->
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md pm-text-primary"><?php echo esc_html($community->name); ?></h3>
        </div>
        <div class="pm-card-body">
            <?php if ($community->description): ?>
                <p class="pm-text-muted pm-mb-4"><?php echo esc_html($community->description); ?></p>
            <?php endif; ?>
            
            <div class="pm-flex pm-gap-4 pm-mb-4">
                <div class="pm-stat pm-text-center">
                    <div class="pm-stat-number pm-text-primary"><?php echo $community->member_count; ?></div>
                    <div class="pm-stat-label pm-text-muted"><?php _e('Members', 'partyminder'); ?></div>
                </div>
                <div class="pm-stat pm-text-center">
                    <div class="pm-stat-number pm-text-primary"><?php echo $event_count; ?></div>
                    <div class="pm-stat-label pm-text-muted"><?php _e('Events', 'partyminder'); ?></div>
                </div>
            </div>
            
            <div class="pm-flex pm-gap-4">
                <?php if (!$is_member && $is_logged_in): ?>
                    <button class="pm-btn pm-join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        <?php _e('Join Community', 'partyminder'); ?>
                    </button>
                <?php endif; ?>
                
                <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="pm-btn pm-btn-secondary">
                    <?php _e('Back to Overview', 'partyminder'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($is_member): ?>
<div class="pm-section">
    <!-- Quick Actions -->
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e('Quick Actions', 'partyminder'); ?></h3>
        </div>
        <div class="pm-card-body">
            <div class="pm-flex pm-flex-column pm-gap-4">
                <button class="pm-btn pm-create-event-btn">
                    <?php _e('Create New Event', 'partyminder'); ?>
                </button>
                <a href="<?php echo home_url('/communities/' . $community->slug . '/members'); ?>" class="pm-btn pm-btn-secondary">
                    <?php _e('View Members', 'partyminder'); ?>
                </a>
            </div>
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
jQuery(document).ready(function($) {
    // Event filter functionality
    $('.pm-filter-button').on('click', function() {
        const filter = $(this).data('filter');
        
        // Update active button
        $('.pm-filter-button').removeClass('pm-active');
        $(this).addClass('pm-active');
        
        // Filter events
        $('.pm-event-card').each(function() {
            const filterTags = $(this).data('filter-tags');
            if (filter === 'all' || filterTags.includes(filter)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Join community button
    $('.pm-join-community-btn').on('click', function(e) {
        e.preventDefault();
        
        const communityId = $(this).data('community-id');
        const communityName = '<?php echo esc_js($community->name); ?>';
        
        if (!confirm('<?php _e('Join community', 'partyminder'); ?> "' + communityName + '"?')) {
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.text('<?php _e('Joining...', 'partyminder'); ?>').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'partyminder_join_community',
                community_id: communityId,
                nonce: '<?php echo wp_create_nonce('partyminder_community_action'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    alert(response.data || '<?php _e('Error joining community', 'partyminder'); ?>');
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('<?php _e('Network error. Please try again.', 'partyminder'); ?>');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Create event button placeholder
    $('.pm-create-event-btn').on('click', function(e) {
        e.preventDefault();
        alert('<?php _e('Community event creation coming soon!', 'partyminder'); ?>');
    });
});
</script>