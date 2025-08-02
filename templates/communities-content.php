<?php
/**
 * Communities Content Template
 * Main communities listing page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if communities are enabled
if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
    echo '<div class="pm-text-center pm-p-16">';
    echo '<h2>' . __('Communities Feature Not Available', 'partyminder') . '</h2>';
    echo '<p>' . __('The communities feature is currently disabled. Please check back later.', 'partyminder') . '</p>';
    echo '</div>';
    return;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

$community_manager = new PartyMinder_Community_Manager();

// Get current user info
$current_user = wp_get_current_user();
$user_email = is_user_logged_in() ? $current_user->user_email : '';

// Get data for the page
$public_communities = $community_manager->get_public_communities(12);
$user_communities = array();

if (is_user_logged_in()) {
    $user_communities = $community_manager->get_user_communities($current_user->ID, 6);
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>


<div class="pm-container-wide">
    <!-- Header -->
    <div class="card-header pm-mb-6">
        <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php _e('🏘️ Communities', 'partyminder'); ?></h1>
        <p class="text-muted"><?php _e('Join communities of fellow hosts and guests to plan amazing events together', 'partyminder'); ?></p>
        
        <div class="pm-mt-4">
            <?php if (PartyMinder_Feature_Flags::can_user_create_community()): ?>
                <a href="<?php echo esc_url(site_url('/create-community')); ?>" class="btn btn-primary">
                    <span>✨</span>
                    <?php _e('Create Community', 'partyminder'); ?>
                </a>
            <?php elseif (!is_user_logged_in()): ?>
                <a href="<?php echo add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), PartyMinder::get_login_url()); ?>" class="btn btn-primary">
                    <span>👋</span>
                    <?php _e('Login to Join Communities', 'partyminder'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Use Unified Two-Column Layout -->
    <div class="pm-dashboard-grid">
        <!-- Main Content Column -->
        <div class="pm-dashboard-main">
            <div class="card">
            <div class="card-header">
                <h2 class="pm-heading pm-heading-md pm-text-primary pm-m-0"><?php _e('🌍 Discover Communities', 'partyminder'); ?></h2>
                <p class="text-muted pm-mt-2"><?php printf(__('%d communities available', 'partyminder'), count($public_communities)); ?></p>
            </div>
            
            <div class="card-body">
                <?php if (!empty($public_communities)): ?>
                    <div class="pm-grid pm-grid-auto">
                    <?php foreach ($public_communities as $community): ?>
                        <div class="card">
                            <div class="card-header pm-flex pm-flex-between pm-flex-center-gap">
                                <h3 class="pm-heading pm-heading-sm pm-m-0">
                                    <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="pm-text-primary" >
                                        <?php echo esc_html($community->name); ?>
                                    </a>
                                </h3>
                                <div class="pm-badge pm-badge-<?php echo $community->privacy === 'public' ? 'success' : 'secondary'; ?>">
                                    <?php echo esc_html(ucfirst($community->privacy)); ?>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="pm-mb-4">
                                    <div class="pm-meta-item">
                                        <span>👥</span>
                                        <span class="text-muted"><?php echo (int) $community->member_count; ?> <?php _e('members', 'partyminder'); ?></span>
                                    </div>
                                    <div class="pm-meta-item">
                                        <span>📂</span>
                                        <span class="text-muted"><?php echo ucfirst($community->type); ?></span>
                                    </div>
                                </div>
                            
                                <?php if ($community->description): ?>
                                <div class="pm-mb-4">
                                    <p class="text-muted"><?php echo esc_html(wp_trim_words($community->description, 20)); ?></p>
                                </div>
                            <?php endif; ?>
                            </div>
                            
                            <div class="card-footer pm-flex pm-flex-between pm-flex-center-gap">
                                <div class="pm-stat">
                                    <div class="pm-stat-number pm-text-primary"><?php echo (int) $community->event_count; ?></div>
                                    <div class="pm-stat-label"><?php _e('Events', 'partyminder'); ?></div>
                                </div>
                                
                                <?php if (is_user_logged_in()): ?>
                                    <?php 
                                    $is_member = $community_manager->is_member($community->id, $current_user->ID);
                                    ?>
                                    <a href="<?php echo home_url('/communities/' . $community->slug); ?>" 
                                       class="btn btn-<?php echo $is_member ? 'secondary' : 'primary'; ?> btn-small">
                                        <?php echo $is_member ? __('Member', 'partyminder') : __('Join', 'partyminder'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), PartyMinder::get_login_url()); ?>" class="btn btn-primary btn-small">
                                        <?php _e('Login to Join', 'partyminder'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="pm-text-center pm-p-6">
                        <p class="text-muted pm-mb-2"><?php _e('No public communities yet.', 'partyminder'); ?></p>
                        <p class="text-muted"><?php _e('Be the first to create a community!', 'partyminder'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="pm-dashboard-sidebar">
            <?php if (is_user_logged_in() && !empty($user_communities)): ?>
                <!-- My Communities -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="pm-heading pm-heading-sm pm-m-0">👥 <?php _e('My Communities', 'partyminder'); ?></h3>
                        <p class="text-muted pm-mt-2"><?php _e('Communities you\'ve joined', 'partyminder'); ?></p>
                    </div>
                    <div class="card-body">
                        <?php foreach ($user_communities as $user_community): ?>
                            <div class="pm-flex pm-flex-between pm-flex-center-gap pm-mb-3 pm-pb-3 pm-border-bottom">
                                <div>
                                    <h4 class="pm-heading pm-heading-xs pm-mb-1">
                                        <a href="<?php echo home_url('/communities/' . $user_community->slug); ?>" class="pm-text-primary" >
                                            <?php echo esc_html($user_community->name); ?>
                                        </a>
                                    </h4>
                                    <div class="pm-badge pm-badge-secondary pm-text-xs"><?php echo esc_html(ucfirst($user_community->role)); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
                </div>
                <div class="card-body">
                    <div class="pm-flex pm-flex-center-gap pm-flex-column">
                        <?php if (PartyMinder_Feature_Flags::can_user_create_community()): ?>
                            <a href="<?php echo esc_url(site_url('/create-community')); ?>" class="btn btn-primary">
                                <span>✨</span>
                                <?php _e('Create Community', 'partyminder'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="btn btn-secondary">
                            <span>🎉</span>
                            <?php _e('Create Event', 'partyminder'); ?>
                        </a>
                        
                        <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="btn btn-secondary">
                            <span>💬</span>
                            <?php _e('Join Conversations', 'partyminder'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Community Types -->
            <div class="card">
                <div class="card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">🏷️ <?php _e('Community Types', 'partyminder'); ?></h3>
                    <p class="text-muted pm-mt-2"><?php _e('Different ways to organize', 'partyminder'); ?></p>
                </div>
                <div class="card-body">
                    <div class="pm-text-sm pm-leading-relaxed">
                        <div class="pm-mb-3">
                            <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                <span>🏢</span>
                                <strong><?php _e('Work', 'partyminder'); ?></strong>
                            </div>
                            <p class="text-muted pm-m-0" class="pm-text-xs pm-ml-6"><?php _e('Office events, team building', 'partyminder'); ?></p>
                        </div>
                        <div class="pm-mb-3">
                            <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                <span>⛪</span>
                                <strong><?php _e('Faith', 'partyminder'); ?></strong>
                            </div>
                            <p class="text-muted pm-m-0" class="pm-text-xs pm-ml-6"><?php _e('Church, religious gatherings', 'partyminder'); ?></p>
                        </div>
                        <div class="pm-mb-3">
                            <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                <span>👨‍👩‍👧‍👦</span>
                                <strong><?php _e('Family', 'partyminder'); ?></strong>
                            </div>
                            <p class="text-muted pm-m-0" class="pm-text-xs pm-ml-6"><?php _e('Family reunions, celebrations', 'partyminder'); ?></p>
                        </div>
                        <div class="pm-mb-3">
                            <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                <span>🎯</span>
                                <strong><?php _e('Hobby', 'partyminder'); ?></strong>
                            </div>
                            <p class="text-muted pm-m-0" class="pm-text-xs pm-ml-6"><?php _e('Interest-based groups', 'partyminder'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Community creation modal replaced with single-page interface at /create-community
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Community creation now handled by single-page interface at /create-community
    
    // Join community functionality
    const joinBtns = document.querySelectorAll('.join-btn');
    joinBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (this.classList.contains('member')) {
                return; // Already a member, just redirect
            }
            
            e.preventDefault();
            
            // Check if user is logged in
            if (!partyminder_ajax.current_user.id) {
                return; // Let the login redirect happen
            }
            
            const communityCard = this.closest('.community-card');
            const communityName = communityCard.querySelector('h3 a').textContent;
            
            if (!confirm(partyminder_ajax.strings.confirm_join + ' "' + communityName + '"?')) {
                return;
            }
            
            // Get community ID from URL
            const communityUrl = communityCard.querySelector('h3 a').href;
            const urlParts = communityUrl.split('/');
            const communitySlug = urlParts[urlParts.length - 2] || urlParts[urlParts.length - 1];
            
            // For now, we'll redirect to the community page
            // In Phase 3, this will be proper AJAX
            window.location.href = communityUrl;
        });
    });
});
</script>