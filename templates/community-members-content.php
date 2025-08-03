<?php
/**
 * Community Members Content Template
 * Members view for individual community
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

// Check if user can view members
$can_view_members = true;
if ($community->privacy === 'private' && !$is_member) {
    $can_view_members = false;
}

// Get community members (if allowed to view)
$members = array();
$member_count = 0;
if ($can_view_members) {
    $members = $community_manager->get_community_members($community->id, 50); // Limit to 50 for now
    $stats = $community_manager->get_community_stats($community->id);
    $member_count = $stats ? $stats->member_count : 0;
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

.partyminder-community-members {
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

.members-header {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.members-hero {
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    color: white;
    padding: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.members-title-section h1 {
    font-size: 2em;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.members-meta {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 1.1em;
    opacity: 0.9;
    flex-wrap: wrap;
}

.member-count-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
}

.members-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.members-nav {
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

.members-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 30px;
}

.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.member-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s ease;
}

.member-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.member-avatar {
    width: 60px;
    height: 60px;
    background: var(--pm-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.5em;
    margin: 0 auto 15px auto;
}

.member-name {
    font-size: 1.1em;
    font-weight: bold;
    color: #333;
    margin: 0 0 5px 0;
}

.member-role {
    color: #666;
    font-size: 0.9em;
    margin: 0 0 10px 0;
}

.member-since {
    color: #999;
    font-size: 0.8em;
}

.role-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75em;
    font-weight: bold;
    text-transform: uppercase;
}

.role-badge.admin {
    background: #dc3545;
    color: white;
}

.role-badge.moderator {
    background: #fd7e14;
    color: white;
}

.role-badge.member {
    background: #28a745;
    color: white;
}

.no-members {
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

.members-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: var(--pm-primary);
    margin: 0;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
    margin: 5px 0 0 0;
}

@media (max-width: 768px) {
    .members-hero {
        flex-direction: column;
        text-align: center;
    }
    
    .members-title-section h1 {
        font-size: 1.6em;
    }
    
    .members-grid {
        grid-template-columns: 1fr;
    }
    
    .nav-tabs {
        flex-direction: column;
    }
    
    .members-actions {
        justify-content: center;
    }
}
</style>

<div class="partyminder-community-members">
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
        <span><?php _e('Members', 'partyminder'); ?></span>
    </div>

    <!-- Members Header -->
    <div class="members-header">
        <div class="members-hero">
            <div class="members-title-section">
                <h1><?php _e('👥 Community Members', 'partyminder'); ?></h1>
                <div class="members-meta">
                    <span><?php echo esc_html($community->name); ?></span>
                    <span class="member-count-badge">
                        <?php printf(__('%d Members', 'partyminder'), $member_count); ?>
                    </span>
                </div>
            </div>
            
            <div class="members-actions">
                <?php if ($is_member && $user_role === 'admin'): ?>
                    <a href="#" class="pm-button invite-members-btn">
                        <span>✉️</span>
                        <?php _e('Invite Members', 'partyminder'); ?>
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
    <div class="members-nav">
        <div class="nav-tabs">
            <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="nav-tab">
                <span>🏠</span> <?php _e('Overview', 'partyminder'); ?>
            </a>
            <a href="<?php echo home_url('/communities/' . $community->slug . '/events'); ?>" class="nav-tab">
                <span>🗓️</span> <?php _e('Events', 'partyminder'); ?>
            </a>
            <a href="<?php echo home_url('/communities/' . $community->slug . '/members'); ?>" class="nav-tab active">
                <span>👥</span> <?php _e('Members', 'partyminder'); ?>
            </a>
        </div>
    </div>

    <!-- Members Content -->
    <div class="members-content">
        <?php if (!$can_view_members): ?>
            <!-- Private Community - No Access -->
            <div class="no-access">
                <h3><?php _e('🔒 Private Community', 'partyminder'); ?></h3>
                <p><?php _e('This community\'s member list is private. You need to be a member to view other members.', 'partyminder'); ?></p>
                
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
            
        <?php elseif (empty($members)): ?>
            <!-- No Members Yet -->
            <div class="no-members">
                <h3><?php _e('🎭 No Members Yet', 'partyminder'); ?></h3>
                <p><?php _e('This community is just getting started. Be the first to join!', 'partyminder'); ?></p>
                
                <?php if (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-button">
                        <?php _e('Login to Join', 'partyminder'); ?>
                    </a>
                <?php elseif (!$is_member): ?>
                    <a href="#" class="pm-button join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        <?php _e('Join Community', 'partyminder'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- Member Statistics -->
            <?php 
            $admin_count = count(array_filter($members, function($m) { return $m->role === 'admin'; }));
            $moderator_count = count(array_filter($members, function($m) { return $m->role === 'moderator'; }));
            $member_count_regular = count(array_filter($members, function($m) { return $m->role === 'member'; }));
            ?>
            
            <div class="members-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $admin_count; ?></div>
                    <div class="stat-label"><?php echo $admin_count === 1 ? __('Admin', 'partyminder') : __('Admins', 'partyminder'); ?></div>
                </div>
                
                <?php if ($moderator_count > 0): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $moderator_count; ?></div>
                    <div class="stat-label"><?php echo $moderator_count === 1 ? __('Moderator', 'partyminder') : __('Moderators', 'partyminder'); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $member_count_regular; ?></div>
                    <div class="stat-label"><?php echo $member_count_regular === 1 ? __('Member', 'partyminder') : __('Members', 'partyminder'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($members); ?></div>
                    <div class="stat-label"><?php _e('Total', 'partyminder'); ?></div>
                </div>
            </div>

            <!-- Members Grid -->
            <div class="members-grid">
                <?php foreach ($members as $member): ?>
                    <div class="member-card">
                        <div class="member-avatar">
                            <?php echo strtoupper(substr($member->display_name, 0, 2)); ?>
                        </div>
                        
                        <div class="member-name">
                            <?php echo esc_html($member->display_name); ?>
                        </div>
                        
                        <div class="member-role">
                            <span class="role-badge <?php echo esc_attr($member->role); ?>">
                                <?php echo esc_html(ucfirst($member->role)); ?>
                            </span>
                        </div>
                        
                        <div class="member-since">
                            <?php printf(__('Joined %s', 'partyminder'), date('M Y', strtotime($member->joined_at))); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($members) >= 50): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <p style="color: #666;">
                        <?php _e('Showing first 50 members. More member management features coming soon.', 'partyminder'); ?>
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

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
    
    // Invite members button
    const inviteBtn = document.querySelector('.invite-members-btn');
    if (inviteBtn) {
        inviteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            alert('<?php _e('Member invitation system coming soon!', 'partyminder'); ?>');
        });
    }
});
</script>