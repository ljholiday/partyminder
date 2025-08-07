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

// Set up template variables
$page_title = esc_html($community->name);
$page_description = '';
$breadcrumbs = array(
    array('title' => 'Communities', 'url' => PartyMinder::get_communities_url()),
    array('title' => $community->name)
);
$nav_items = array(
    array('title' => 'Overview', 'url' => home_url('/communities/' . $community->slug), 'active' => true),
    array('title' => 'Events', 'url' => home_url('/communities/' . $community->slug . '/events')),
    array('title' => 'Members', 'url' => home_url('/communities/' . $community->slug . '/members'))
);

// Main content
ob_start();
?>
<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <div class="pm-flex pm-flex-between">
                <div class="pm-flex pm-gap">
                    <div class="pm-avatar pm-avatar-lg">
                        <?php echo strtoupper(substr($community->name, 0, 2)); ?>
                    </div>
                    <div>
                        <div class="pm-flex pm-gap pm-text-muted pm-mb-2">
                            <span>Community</span>
                            <span><?php echo date('M Y', strtotime($community->created_at)); ?></span>
                            <?php if ($is_member): ?>
                                <span class="pm-badge pm-badge-<?php echo $user_role === 'admin' ? 'primary' : 'success'; ?>">
                                    <?php echo esc_html(ucfirst($user_role)); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="pm-badge pm-badge-secondary">
                        <?php echo esc_html(ucfirst($community->privacy)); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <?php if ($community->description): ?>
        <div class="pm-card-body">
            <div class="pm-text-muted">
                <?php echo wpautop(esc_html($community->description)); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md">Welcome to <?php echo esc_html($community->name); ?></h3>
        </div>
        <div class="pm-card-body">
            <div class="pm-grid pm-grid-2 pm-gap">
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h4 class="pm-heading pm-heading-sm pm-text-primary">Community Purpose</h4>
                    </div>
                    <div class="pm-card-body">
                        <p class="pm-text-muted">
                            A community for members to plan and attend events together.
                        </p>
                    </div>
                </div>
                
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h4 class="pm-heading pm-heading-sm pm-text-primary">Get Started</h4>
                    </div>
                    <div class="pm-card-body">
                        <p class="pm-text-muted">
                            <?php if ($is_member): ?>
                                Create your first community event or browse upcoming events.
                            <?php else: ?>
                                Join this community to start planning events with other members.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
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
            <h3 class="pm-heading pm-heading-md">Community Actions</h3>
        </div>
        <div class="pm-card-body">
            <div class="pm-flex pm-flex-column pm-gap">
                <?php if (!$is_logged_in): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pm-btn">
                        Login to Join
                    </a>
                <?php elseif ($is_member): ?>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="<?php echo esc_url(site_url('/manage-community?community_id=' . $community->id . '&tab=overview')); ?>" class="pm-btn">
                            Manage Community
                        </a>
                    <?php endif; ?>
                    <a href="#" class="pm-btn pm-btn-secondary create-event-btn">
                        Create Event
                    </a>
                <?php else: ?>
                    <a href="#" class="pm-btn join-community-btn" data-community-id="<?php echo esc_attr($community->id); ?>">
                        Join Community
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md">Community Stats</h3>
        </div>
        <div class="pm-card-body">
            <div class="pm-stat pm-text-center">
                <div class="pm-stat-number pm-text-primary"><?php echo $community->member_count ?? 0; ?></div>
                <div class="pm-stat-label">Members</div>
            </div>
        </div>
    </div>
</div>

<div class="pm-section pm-mb">
    <div class="pm-card">
        <div class="pm-card-header">
            <h3 class="pm-heading pm-heading-md">Community Details</h3>
        </div>
        <div class="pm-card-body">
            <div class="pm-flex pm-flex-column pm-gap">
                <div>
                    <strong class="pm-text-primary">Privacy:</strong><br>
                    <span class="pm-text-muted"><?php echo esc_html(ucfirst($community->privacy)); ?></span>
                </div>
                <div>
                    <strong class="pm-text-primary">Created:</strong><br>
                    <span class="pm-text-muted"><?php echo date('F j, Y', strtotime($community->created_at)); ?></span>
                </div>
                <?php if ($community->location): ?>
                <div>
                    <strong class="pm-text-primary">Location:</strong><br>
                    <span class="pm-text-muted"><?php echo esc_html($community->location); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
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
            
            if (!confirm('Are you sure you want to join "' + communityName + '"?')) {
                return;
            }
            
            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = 'Loading...';
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
                        alert(response.data || 'Error occurred');
                        // Restore button
                        joinBtn.innerHTML = originalText;
                        joinBtn.disabled = false;
                    }
                },
                error: function() {
                    alert('Error occurred');
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
});
</script>