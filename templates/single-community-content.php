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


// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>


<div class="pm-page">
    <!-- Breadcrumbs -->
    <div class="pm-text-muted" style="margin-bottom: 16px;">
        <a href="<?php echo PartyMinder::get_communities_url(); ?>" class="pm-text-primary">
            <?php _e('🏘️ Communities', 'partyminder'); ?>
        </a>
        <span> › </span>
        <span><?php echo esc_html($community->name); ?></span>
    </div>

    <!-- Community Header -->
    <div class="section header">
        <div style="position: relative;">
            <span class="pm-badge" style="position:absolute;top:0;right:0;">
                <?php echo esc_html(ucfirst($community->privacy)); ?>
            </span>
            
            <div class="flex gap-4 mb-4">
                <div class="avatar avatar-lg">
                    <?php echo strtoupper(substr($community->name, 0, 2)); ?>
                </div>
                <div>
                    <h1 class="pm-heading pm-heading-lg"><?php echo esc_html($community->name); ?></h1>
                    <div class="flex gap-4 text-muted" style="font-size:14px;">
                        <span><?php _e('Community', 'partyminder'); ?></span>
                        <span><?php echo date('M Y', strtotime($community->created_at)); ?></span>
                        <?php if ($is_member): ?>
                            <span class="badge <?php echo $user_role === 'admin' ? '' : 'badge-success'; ?>">
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
            
            <div class="action-buttons">
                <?php if (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-btn">
                        <span>👋</span>
                        <?php _e('Login to Join', 'partyminder'); ?>
                    </a>
                <?php elseif ($is_member): ?>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="<?php echo esc_url(site_url('/manage-community?community_id=' . $community->id . '&tab=overview')); ?>" class="btn manage-community-btn">
                            <span>⚙️</span>
                            <?php _e('Manage Community', 'partyminder'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="#" class="btn btn-secondary create-event-btn">
                        <span>🎉</span>
                        <?php _e('Create Event', 'partyminder'); ?>
                    </a>
                <?php else: ?>
                    <a href="#" class="btn join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        <span>➕</span>
                        <?php _e('Join Community', 'partyminder'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="pm-nav">
        <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="nav-item active">
            <span>🏠</span> <?php _e('Overview', 'partyminder'); ?>
        </a>
        <a href="<?php echo home_url('/communities/' . $community->slug . '/events'); ?>" class="pm-nav-item">
            <span>🗓️</span> <?php _e('Events', 'partyminder'); ?>
        </a>
        <a href="<?php echo home_url('/communities/' . $community->slug . '/members'); ?>" class="pm-nav-item">
            <span>👥</span> <?php _e('Members', 'partyminder'); ?>
        </a>
    </div>
    
    <!-- Content -->
    <div class="pm-section">
        <h3 class="pm-heading pm-heading-sm"><?php _e('Welcome to', 'partyminder'); ?> <?php echo esc_html($community->name); ?></h3>
            
            <?php if ($community->description): ?>
                <div class="pm-mb-4">
                    <?php echo wpautop(esc_html($community->description)); ?>
                </div>
            <?php endif; ?>
            
            <div class="grid gap-4">
                <div class="card p-4">
                    <h4 class=" mb-4 text-primary">🎯 Community Purpose</h4>
                    <p class=" text-muted">
                        <?php _e('A community for members to plan and attend events together.', 'partyminder'); ?>
                    </p>
                </div>
                
                
                <div class="card p-4">
                    <h4 class=" mb-4 text-primary">🎉 Get Started</h4>
                    <p class=" text-muted">
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
// Community management modal replaced with single-page interface at /manage-community
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
    
    // Manage community button is now a direct link - no JavaScript needed
});
</script>