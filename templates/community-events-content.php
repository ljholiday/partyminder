<?php
/**
 * Community Events Content Template
 * Events view for individual community
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
    $events = $event_manager->get_events(array(
        'limit' => 20,
        'status' => 'active'
    ));
    
    // Filter to community events when that relationship is implemented
    // $events = $community_manager->get_community_events($community->id, 20);
    $event_count = count($events);
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-community-events {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.breadcrumbs {
    background: #f8f9fa;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.breadcrumbs a {
    color: var(--pm-primary);
    text-decoration: none;
}

.breadcrumbs a:hover {
    text-decoration: underline;
}

.events-header {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.events-hero {
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    color: white;
    padding: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.events-title-section h1 {
    font-size: 2em;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.events-meta {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 1.1em;
    opacity: 0.9;
    flex-wrap: wrap;
}

.event-count-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
}

.events-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.events-nav {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    overflow: hidden;
}

.nav-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.nav-tab {
    flex: 1;
    padding: 15px 20px;
    text-align: center;
    background: none;
    border: none;
    color: #666;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

.nav-tab:hover {
    background: white;
    color: var(--pm-primary);
}

.nav-tab.active {
    background: white;
    color: var(--pm-primary);
    border-bottom: 3px solid var(--pm-primary);
}

.events-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 30px;
}

.pm-events-filter {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-button {
    padding: 8px 16px;
    border: 2px solid var(--pm-primary);
    background: transparent;
    color: var(--pm-primary);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9em;
}

.filter-button:hover,
.filter-button.active {
    background: var(--pm-primary);
    color: white;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.event-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    position: relative;
}

.event-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.event-image {
    height: 180px;
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    position: relative;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-date-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: rgba(255, 255, 255, 0.95);
    padding: 8px 12px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.event-date-day {
    font-weight: bold;
    font-size: 1.2em;
    color: var(--pm-primary);
    line-height: 1;
}

.event-date-month {
    font-size: 0.8em;
    color: #666;
    text-transform: uppercase;
}

.pm-event-status-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.pm-event-status-badge.upcoming {
    background: #28a745;
    color: white;
}

.pm-event-status-badge.today {
    background: #dc3545;
    color: white;
}

.pm-event-status-badge.past {
    background: #6c757d;
    color: white;
}

.event-info {
    padding: 20px;
}

.event-title {
    font-size: 1.2em;
    font-weight: bold;
    color: #333;
    margin: 0 0 10px 0;
    line-height: 1.3;
}

.event-title a {
    color: inherit;
    text-decoration: none;
}

.event-title a:hover {
    color: var(--pm-primary);
}

.event-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #666;
}

.event-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.event-description {
    color: #555;
    line-height: 1.5;
    margin-bottom: 15px;
}

.event-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.event-organizer {
    font-size: 0.85em;
    color: #666;
}

.event-actions {
    display: flex;
    gap: 10px;
}

.rsvp-btn {
    background: var(--pm-primary);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9em;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.rsvp-btn:hover {
    opacity: 0.9;
    color: white;
}

.rsvp-btn.attending {
    background: #28a745;
}

.no-events {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-access {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.pm-button {
    background: var(--pm-primary);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.pm-button:hover {
    opacity: 0.9;
    color: white;
}

.pm-button-outline {
    background: transparent;
    color: var(--pm-primary);
    border: 2px solid var(--pm-primary);
}

.pm-button-outline:hover {
    background: var(--pm-primary);
    color: white;
}

.pm-button-secondary {
    background: #6c757d;
}

@media (max-width: 768px) {
    .events-hero {
        flex-direction: column;
        text-align: center;
    }
    
    .events-title-section h1 {
        font-size: 1.6em;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
    }
    
    .nav-tabs {
        flex-direction: column;
    }
    
    .events-actions {
        justify-content: center;
    }
    
    .pm-events-filter {
        justify-content: center;
    }
}
</style>

<div class="partyminder-community-events">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <a href="<?php echo PartyMinder::get_communities_url(); ?>">
            <?php _e('🏘️ Communities', 'partyminder'); ?>
        </a>
        <span> › </span>
        <a href="<?php echo home_url('/communities/' . $community->slug); ?>">
            <?php echo esc_html($community->name); ?>
        </a>
        <span> › </span>
        <span><?php _e('Events', 'partyminder'); ?></span>
    </div>

    <!-- Events Header -->
    <div class="events-header">
        <div class="events-hero">
            <div class="events-title-section">
                <h1><?php _e('🗓️ Community Events', 'partyminder'); ?></h1>
                <div class="events-meta">
                    <span><?php echo esc_html($community->name); ?></span>
                    <span class="event-count-badge">
                        <?php printf(__('%d Events', 'partyminder'), $event_count); ?>
                    </span>
                </div>
            </div>
            
            <div class="events-actions">
                <?php if ($is_member): ?>
                    <a href="#" class="pm-button create-event-btn">
                        <span>🎉</span>
                        <?php _e('Create Event', 'partyminder'); ?>
                    </a>
                <?php endif; ?>
                
                <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="pm-button pm-button-outline">
                    <span>🔙</span>
                    <?php _e('Back to Community', 'partyminder'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="events-nav">
        <div class="nav-tabs">
            <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="nav-tab">
                <span>🏠</span> <?php _e('Overview', 'partyminder'); ?>
            </a>
            <a href="<?php echo home_url('/communities/' . $community->slug . '/events'); ?>" class="nav-tab active">
                <span>🗓️</span> <?php _e('Events', 'partyminder'); ?>
            </a>
            <a href="<?php echo home_url('/communities/' . $community->slug . '/members'); ?>" class="nav-tab">
                <span>👥</span> <?php _e('Members', 'partyminder'); ?>
            </a>
        </div>
    </div>

    <!-- Events Content -->
    <div class="events-content">
        <?php if (!$can_view_events): ?>
            <!-- Private Community - No Access -->
            <div class="no-access">
                <h3><?php _e('🔒 Private Community', 'partyminder'); ?></h3>
                <p><?php _e('This community\'s events are private. You need to be a member to view community events.', 'partyminder'); ?></p>
                
                <?php if (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-button">
                        <?php _e('Login to Join', 'partyminder'); ?>
                    </a>
                <?php else: ?>
                    <a href="#" class="pm-button join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        <?php _e('Join Community', 'partyminder'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
        <?php elseif (empty($events)): ?>
            <!-- No Events Yet -->
            <div class="no-events">
                <h3><?php _e('🎭 No Events Yet', 'partyminder'); ?></h3>
                <p><?php _e('This community hasn\'t created any events yet. Be the first to plan something amazing!', 'partyminder'); ?></p>
                
                <?php if ($is_member): ?>
                    <a href="#" class="pm-button create-event-btn">
                        <span>🎉</span>
                        <?php _e('Create First Event', 'partyminder'); ?>
                    </a>
                <?php elseif (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-button">
                        <?php _e('Login to Join', 'partyminder'); ?>
                    </a>
                <?php else: ?>
                    <a href="#" class="pm-button join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        <?php _e('Join to Create Events', 'partyminder'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- Event Filters -->
            <div class="pm-events-filter">
                <span style="font-weight: 500; color: #666;"><?php _e('Filter:', 'partyminder'); ?></span>
                <button class="filter-button active" data-filter="all">
                    <?php _e('All Events', 'partyminder'); ?>
                </button>
                <button class="filter-button" data-filter="upcoming">
                    <?php _e('Upcoming', 'partyminder'); ?>
                </button>
                <button class="filter-button" data-filter="this-month">
                    <?php _e('This Month', 'partyminder'); ?>
                </button>
                <button class="filter-button" data-filter="past">
                    <?php _e('Past Events', 'partyminder'); ?>
                </button>
            </div>

            <!-- Events Grid -->
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <?php
                    $event_date = new DateTime($event->event_date);
                    $today = new DateTime();
                    $is_past = $event_date < $today;
                    $is_today = $event_date->format('Y-m-d') === $today->format('Y-m-d');
                    
                    $status_class = $is_past ? 'past' : ($is_today ? 'today' : 'upcoming');
                    $status_text = $is_past ? __('Past', 'partyminder') : ($is_today ? __('Today', 'partyminder') : __('Upcoming', 'partyminder'));
                    ?>
                    
                    <div class="event-card" data-filter-tags="all <?php echo $status_class; ?> <?php echo $event_date->format('Y-m') === $today->format('Y-m') ? 'this-month' : ''; ?>">
                        <div class="event-image">
                            <?php if ($event->featured_image): ?>
                                <img src="<?php echo esc_url($event->featured_image); ?>" alt="<?php echo esc_attr($event->title); ?>">
                            <?php endif; ?>
                            
                            <div class="event-date-badge">
                                <div class="event-date-day"><?php echo $event_date->format('j'); ?></div>
                                <div class="event-date-month"><?php echo $event_date->format('M'); ?></div>
                            </div>
                            
                            <div class="pm-event-status-badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </div>
                        </div>
                        
                        <div class="event-info">
                            <h3 class="event-title">
                                <a href="<?php echo PartyMinder::get_event_url($event->slug); ?>">
                                    <?php echo esc_html($event->title); ?>
                                </a>
                            </h3>
                            
                            <div class="event-meta">
                                <div class="event-meta-item">
                                    <span>📅</span>
                                    <span><?php echo $event_date->format('F j, Y'); ?></span>
                                </div>
                                
                                <?php if ($event->event_time): ?>
                                <div class="event-meta-item">
                                    <span>🕒</span>
                                    <span><?php echo esc_html($event->event_time); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($event->venue_info): ?>
                                <div class="event-meta-item">
                                    <span>📍</span>
                                    <span><?php echo esc_html(wp_trim_words($event->venue_info, 8)); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($event->excerpt): ?>
                                <div class="event-description">
                                    <?php echo esc_html(wp_trim_words($event->excerpt, 20)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="event-footer">
                                <div class="event-organizer">
                                    <?php printf(__('by %s', 'partyminder'), esc_html($event->host_email)); ?>
                                </div>
                                
                                <div class="event-actions">
                                    <?php if (!$is_past): ?>
                                        <a href="<?php echo PartyMinder::get_event_url($event->slug); ?>" class="rsvp-btn">
                                            <?php _e('RSVP', 'partyminder'); ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo PartyMinder::get_event_url($event->slug); ?>" class="rsvp-btn" style="background: #6c757d;">
                                            <?php _e('View', 'partyminder'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <p style="color: #666; font-size: 0.9em;">
                    <?php _e('💡 Note: Currently showing all public events. Community-specific events coming soon!', 'partyminder'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Event filter functionality
    const filterButtons = document.querySelectorAll('.filter-button');
    const eventCards = document.querySelectorAll('.event-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter events
            eventCards.forEach(card => {
                const filterTags = card.getAttribute('data-filter-tags');
                if (filter === 'all' || filterTags.includes(filter)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Join community button with AJAX
    const joinBtn = document.querySelector('.join-community-btn');
    if (joinBtn) {
        joinBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const communityId = this.getAttribute('data-community-id');
            const communityName = '<?php echo esc_js($community->name); ?>';
            
            if (!confirm(partyminder_ajax.strings.confirm_join + ' "' + communityName + '"?')) {
                return;
            }
            
            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<span>⏳</span> ' + partyminder_ajax.strings.loading;
            this.disabled = true;
            
            // Make AJAX request
            jQuery.ajax({
                url: partyminder_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'partyminder_join_community',
                    community_id: communityId,
                    nonce: partyminder_ajax.community_nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        alert(response.data || partyminder_ajax.strings.error);
                        joinBtn.innerHTML = originalText;
                        joinBtn.disabled = false;
                    }
                },
                error: function() {
                    alert(partyminder_ajax.strings.error);
                    joinBtn.innerHTML = originalText;
                    joinBtn.disabled = false;
                }
            });
        });
    }
    
    // Create event button
    const createEventBtns = document.querySelectorAll('.create-event-btn');
    createEventBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            alert('<?php _e('Community event creation coming soon!', 'partyminder'); ?>');
        });
    });
});
</script>