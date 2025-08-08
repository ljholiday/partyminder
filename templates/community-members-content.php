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
    $member_count = is_array($members) ? count($members) : 0;
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');

// Set up template variables
$page_title = __('👥 Community Members', 'partyminder');
$page_description = sprintf(__('%s - %d Members', 'partyminder'), esc_html($community->name), $member_count);

// Breadcrumbs
$breadcrumbs = array(
    array('title' => __('Communities', 'partyminder'), 'url' => PartyMinder::get_communities_url()),
    array('title' => esc_html($community->name), 'url' => home_url('/communities/' . $community->slug)),
    array('title' => __('Members', 'partyminder'))
);

// Navigation tabs
$nav_items = array(
    array('title' => __('Overview', 'partyminder'), 'url' => home_url('/communities/' . $community->slug), 'active' => false),
    array('title' => __('Events', 'partyminder'), 'url' => home_url('/communities/' . $community->slug . '/events'), 'active' => false),
    array('title' => __('Members', 'partyminder'), 'url' => home_url('/communities/' . $community->slug . '/members'), 'active' => true)
);

// Main content
ob_start();
?>
<div class="partyminder-community-members">

    <!-- Members Content -->
    <div class="members-content">
        <?php if (!$can_view_members): ?>
            <!-- Private Community - No Access -->
            <div class="no-access">
                <h3><?php _e('🔒 Private Community', 'partyminder'); ?></h3>
                <p><?php _e('This community\'s member list is private. You need to be a member to view other members.', 'partyminder'); ?></p>
                
                <?php if (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-btn">
                        <?php _e('Login to Join', 'partyminder'); ?>
                    </a>
                <?php else: ?>
                    <a href="#" class="btn join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        <?php _e('Join Community', 'partyminder'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
        <?php elseif (empty($members)): ?>
            <!-- No Members Yet -->
            <div class="no-members">
                <h3><?php _e(' No Members Yet', 'partyminder'); ?></h3>
                <p><?php _e('This community is just getting started. Be the first to join!', 'partyminder'); ?></p>
                
                <?php if (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-btn">
                        <?php _e('Login to Join', 'partyminder'); ?>
                    </a>
                <?php elseif (!$is_member): ?>
                    <a href="#" class="btn join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
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
                <div class="pm-stat-card">
                    <div class="pm-stat-number"><?php echo $admin_count; ?></div>
                    <div class="pm-stat-label"><?php echo $admin_count === 1 ? __('Admin', 'partyminder') : __('Admins', 'partyminder'); ?></div>
                </div>
                
                <?php if ($moderator_count > 0): ?>
                <div class="pm-stat-card">
                    <div class="pm-stat-number"><?php echo $moderator_count; ?></div>
                    <div class="pm-stat-label"><?php echo $moderator_count === 1 ? __('Moderator', 'partyminder') : __('Moderators', 'partyminder'); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="pm-stat-card">
                    <div class="pm-stat-number"><?php echo $member_count_regular; ?></div>
                    <div class="pm-stat-label"><?php echo $member_count_regular === 1 ? __('Member', 'partyminder') : __('Members', 'partyminder'); ?></div>
                </div>
                
                <div class="pm-stat-card">
                    <div class="pm-stat-number"><?php echo count($members); ?></div>
                    <div class="pm-stat-label"><?php _e('Total', 'partyminder'); ?></div>
                </div>
            </div>

            <!-- Members Grid -->
            <div class="members-grid">
                <?php foreach ($members as $member): ?>
                    <div class="member-card">
                        <div class="pm-member-avatar">
                            <?php echo strtoupper(substr($member->display_name, 0, 2)); ?>
                        </div>
                        
                        <div class="member-name">
                            <?php echo esc_html($member->display_name); ?>
                        </div>
                        
                        <div class="pm-member-role">
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
<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>
<div class="members-actions">
    <?php if ($is_member && $user_role === 'admin'): ?>
        <a href="#" class="pm-btn invite-members-btn">
            <span>✉️</span>
            <?php _e('Invite Members', 'partyminder'); ?>
        </a>
    <?php endif; ?>
    
    <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="pm-btn pm-btn-secondary">
        <span>🔙</span>
        <?php _e('Back to Community', 'partyminder'); ?>
    </a>
</div>
<?php
$sidebar_content = ob_get_clean();

// Include base template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php');
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