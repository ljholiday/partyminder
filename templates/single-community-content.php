<?php
/**
 * Single Community Content Template
 * Individual community page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

$community_manager = new PartyMinder_Community_Manager();

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

// Get community stats
$stats = $community_manager->get_community_stats($community->id);

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-single-community {
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

.community-header {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.community-hero {
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    color: white;
    padding: 40px;
    position: relative;
}

.community-title-section {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}

.community-avatar {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2em;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.community-title {
    font-size: 2.5em;
    margin: 0;
    font-weight: bold;
}

.community-meta {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 1.1em;
    opacity: 0.9;
    flex-wrap: wrap;
}

.privacy-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: bold;
    text-transform: uppercase;
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.community-description {
    font-size: 1.1em;
    line-height: 1.6;
    opacity: 0.95;
}

.community-actions {
    padding: 20px 40px;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.community-stats {
    display: flex;
    gap: 30px;
    align-items: center;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 1em;
}

.stat-number {
    font-weight: bold;
    color: var(--pm-primary);
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.community-nav {
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

.nav-tab:hover,
.nav-tab.active {
    background: white;
    color: var(--pm-primary);
    border-bottom: 3px solid var(--pm-primary);
}

.tab-content {
    padding: 30px;
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

.pm-button-secondary {
    background: #6c757d;
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

.member-badge {
    background: #28a745;
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: bold;
}

.admin-badge {
    background: #dc3545;
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: bold;
}

.recent-activity {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 30px;
}

.activity-item {
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-avatar {
    width: 40px;
    height: 40px;
    background: var(--pm-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.activity-content {
    flex: 1;
}

.activity-text {
    margin: 0 0 5px 0;
    color: #333;
}

.activity-time {
    font-size: 0.85em;
    color: #666;
}

@media (max-width: 768px) {
    .community-title-section {
        flex-direction: column;
        text-align: center;
    }
    
    .community-title {
        font-size: 2em;
    }
    
    .community-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 20px;
    }
    
    .community-stats {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .nav-tabs {
        flex-direction: column;
    }
    
    .action-buttons {
        justify-content: center;
    }
}
</style>

<div class="partyminder-single-community">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <a href="<?php echo PartyMinder::get_communities_url(); ?>">
            <?php _e('🏘️ Communities', 'partyminder'); ?>
        </a>
        <span> › </span>
        <span><?php echo esc_html($community->name); ?></span>
    </div>

    <!-- Community Header -->
    <div class="community-header">
        <div class="community-hero">
            <span class="privacy-badge">
                <?php echo esc_html(ucfirst($community->privacy)); ?>
            </span>
            
            <div class="community-title-section">
                <div class="community-avatar">
                    <?php echo strtoupper(substr($community->name, 0, 2)); ?>
                </div>
                <div>
                    <h1 class="community-title"><?php echo esc_html($community->name); ?></h1>
                    <div class="community-meta">
                        <span><?php echo esc_html(ucfirst($community->type)); ?> <?php _e('Community', 'partyminder'); ?></span>
                        <span><?php echo date('M Y', strtotime($community->created_at)); ?></span>
                        <?php if ($is_member): ?>
                            <span class="<?php echo $user_role === 'admin' ? 'admin-badge' : 'member-badge'; ?>">
                                <?php echo esc_html(ucfirst($user_role)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($community->description): ?>
                <div class="community-description">
                    <?php echo wpautop(esc_html($community->description)); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="community-actions">
            <div class="community-stats">
                <div class="stat-item">
                    <span>👥</span>
                    <span><span class="stat-number"><?php echo $stats->member_count; ?></span> <?php echo $stats->member_count === 1 ? __('member', 'partyminder') : __('members', 'partyminder'); ?></span>
                </div>
                <div class="stat-item">
                    <span>🗓️</span>
                    <span><span class="stat-number"><?php echo $stats->event_count; ?></span> <?php echo $stats->event_count === 1 ? __('event', 'partyminder') : __('events', 'partyminder'); ?></span>
                </div>
                <div class="stat-item">
                    <span>📈</span>
                    <span><span class="stat-number"><?php echo $stats->recent_activity; ?></span> <?php _e('active this month', 'partyminder'); ?></span>
                </div>
            </div>
            
            <div class="action-buttons">
                <?php if (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-button">
                        <span>👋</span>
                        <?php _e('Login to Join', 'partyminder'); ?>
                    </a>
                <?php elseif ($is_member): ?>
                    <?php if ($user_role === 'admin'): ?>
                        <button type="button" class="pm-button manage-community-btn">
                            <span>⚙️</span>
                            <?php _e('Manage Community', 'partyminder'); ?>
                        </button>
                    <?php endif; ?>
                    <a href="#" class="pm-button pm-button-secondary create-event-btn">
                        <span>🎉</span>
                        <?php _e('Create Event', 'partyminder'); ?>
                    </a>
                <?php else: ?>
                    <a href="#" class="pm-button join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        <span>➕</span>
                        <?php _e('Join Community', 'partyminder'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="community-nav">
        <div class="nav-tabs">
            <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="nav-tab active">
                <span>🏠</span> <?php _e('Overview', 'partyminder'); ?>
            </a>
            <a href="<?php echo home_url('/communities/' . $community->slug . '/events'); ?>" class="nav-tab">
                <span>🗓️</span> <?php _e('Events', 'partyminder'); ?>
            </a>
            <a href="<?php echo home_url('/communities/' . $community->slug . '/members'); ?>" class="nav-tab">
                <span>👥</span> <?php _e('Members', 'partyminder'); ?>
            </a>
        </div>
        
        <div class="tab-content">
            <h3><?php _e('Welcome to', 'partyminder'); ?> <?php echo esc_html($community->name); ?></h3>
            
            <?php if ($community->description): ?>
                <div style="margin-bottom: 30px;">
                    <?php echo wpautop(esc_html($community->description)); ?>
                </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: var(--pm-primary);">🎯 Community Purpose</h4>
                    <p style="margin: 0; color: #666;">
                        <?php 
                        switch ($community->type) {
                            case 'work':
                                _e('A professional community for workplace events and team building activities.', 'partyminder');
                                break;
                            case 'faith':
                                _e('A faith-based community for religious gatherings and spiritual events.', 'partyminder');
                                break;
                            case 'family':
                                _e('A family community for reunions, celebrations, and family gatherings.', 'partyminder');
                                break;
                            case 'hobby':
                                _e('A hobby-based community for enthusiasts with shared interests.', 'partyminder');
                                break;
                            default:
                                _e('A community for members to plan and attend events together.', 'partyminder');
                        }
                        ?>
                    </p>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: var(--pm-primary);">📅 Recent Activity</h4>
                    <p style="margin: 0; color: #666;">
                        <?php printf(__('%d members have been active this month', 'partyminder'), $stats->recent_activity); ?>
                    </p>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: var(--pm-primary);">🎉 Get Started</h4>
                    <p style="margin: 0; color: #666;">
                        <?php if ($is_member): ?>
                            <?php _e('Create your first community event or browse upcoming events.', 'partyminder'); ?>
                        <?php else: ?>
                            <?php _e('Join this community to start planning events with other members.', 'partyminder'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Include community management modal for admins
if ($is_member && $user_role === 'admin') {
    include PARTYMINDER_PLUGIN_DIR . 'templates/community-management-modal.php';
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                        // Show success message
                        alert(response.data.message);
                        // Reload page to update UI
                        window.location.reload();
                    } else {
                        alert(response.data || partyminder_ajax.strings.error);
                        // Restore button
                        joinBtn.innerHTML = originalText;
                        joinBtn.disabled = false;
                    }
                },
                error: function() {
                    alert(partyminder_ajax.strings.error);
                    // Restore button
                    joinBtn.innerHTML = originalText;
                    joinBtn.disabled = false;
                }
            });
        });
    }
    
    // Create event button - redirect to create event page with community context
    const createEventBtn = document.querySelector('.create-event-btn');
    if (createEventBtn) {
        createEventBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Redirect to the create event page
            window.location.href = '<?php echo esc_url(site_url('/create-event')); ?>?community_id=<?php echo intval($community->id); ?>';
        });
    }
    
    // Manage community button - show management modal
    const manageBtn = document.querySelector('.manage-community-btn');
    console.log('Manage button found:', manageBtn);
    if (manageBtn) {
        manageBtn.addEventListener('click', function(e) {
            console.log('Manage button clicked');
            e.preventDefault();
            
            // Check if modal element exists and try to show it
            const modal = document.getElementById('community-management-modal');
            console.log('Modal element found:', modal);
            
            if (modal && typeof window.showCommunityManagementModal === 'function') {
                // Pass community data to the modal
                const communityData = {
                    name: '<?php echo esc_js($community->name); ?>',
                    description: '<?php echo esc_js($community->description); ?>',
                    privacy: '<?php echo esc_js($community->privacy); ?>',
                    id: <?php echo intval($community->id); ?>
                };
                
                console.log('Opening modal with data:', communityData);
                window.showCommunityManagementModal(communityData);
            } else if (modal) {
                // Modal exists but function not available - show it manually
                console.log('Showing modal manually');
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            } else {
                console.log('Modal element not found - modal template may not be loaded');
                alert('Community management modal not available. Please refresh the page.');
            }
        });
    } else {
        console.log('Manage button not found');
    }
});
</script>