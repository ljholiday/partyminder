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

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
}

.partyminder-communities {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.communities-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    color: white;
    border-radius: 12px;
}

.communities-header h1 {
    font-size: 2.5em;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.communities-header p {
    font-size: 1.2em;
    margin: 0 0 20px 0;
    opacity: 0.9;
}

.create-community-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid white;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
    transition: all 0.3s ease;
}

.create-community-btn:hover {
    background: white;
    color: var(--pm-primary);
}

.communities-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

@media (max-width: 768px) {
    .communities-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

/* Communities Grid */
.communities-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.section-header {
    background: var(--pm-primary);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h2 {
    margin: 0;
    font-size: 1.4em;
}

.communities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.community-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.2s ease;
    position: relative;
}

.community-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.community-header {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 15px;
}

.community-avatar {
    width: 50px;
    height: 50px;
    background: var(--pm-primary);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2em;
    flex-shrink: 0;
}

.community-info h3 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.1em;
}

.community-info .community-meta {
    font-size: 0.85em;
    color: #666;
    display: flex;
    gap: 15px;
}

.community-description {
    color: #555;
    line-height: 1.5;
    margin-bottom: 15px;
}

.pm-community-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
    font-size: 0.9em;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
}

.join-btn {
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

.join-btn:hover {
    opacity: 0.9;
    color: white;
}

.join-btn.member {
    background: #28a745;
}

.privacy-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.7em;
    font-weight: bold;
    text-transform: uppercase;
}

.privacy-badge.public {
    background: #d4edda;
    color: #155724;
}

.privacy-badge.private {
    background: #f8d7da;
    color: #721c24;
}

/* Sidebar */
.communities-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.sidebar-card-header {
    background: var(--pm-secondary);
    color: white;
    padding: 15px 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-community {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.user-community:last-child {
    border-bottom: none;
}

.user-community-name {
    font-weight: bold;
    color: #333;
    text-decoration: none;
}

.user-community-name:hover {
    color: var(--pm-primary);
}

.user-community-role {
    font-size: 0.8em;
    color: #666;
    background: #f8f9fa;
    padding: 2px 8px;
    border-radius: 10px;
}

.no-communities {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

@media (max-width: 768px) {
    .communities-header h1 {
        font-size: 2em;
    }
    
    .communities-grid {
        grid-template-columns: 1fr;
        padding: 15px;
    }
    
    .community-header {
        flex-direction: column;
        text-align: center;
    }
    
    .pm-community-stats {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
}
</style>

<div class="pm-container-wide">
    <!-- Header -->
    <div class="pm-card-header pm-mb-6">
        <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php _e('🏘️ Communities', 'partyminder'); ?></h1>
        <p class="pm-text-muted"><?php _e('Join communities of fellow hosts and guests to plan amazing events together', 'partyminder'); ?></p>
        
        <div class="pm-mt-4">
            <?php if (PartyMinder_Feature_Flags::can_user_create_community()): ?>
                <a href="<?php echo esc_url(site_url('/create-community')); ?>" class="pm-button pm-button-primary">
                    <span>✨</span>
                    <?php _e('Create Community', 'partyminder'); ?>
                </a>
            <?php elseif (!is_user_logged_in()): ?>
                <a href="<?php echo add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), PartyMinder::get_login_url()); ?>" class="pm-button pm-button-primary">
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
            <div class="pm-card">
            <div class="pm-card-header">
                <h2 class="pm-heading pm-heading-md pm-text-primary pm-m-0"><?php _e('🌍 Discover Communities', 'partyminder'); ?></h2>
                <p class="pm-text-muted pm-mt-2"><?php printf(__('%d communities available', 'partyminder'), count($public_communities)); ?></p>
            </div>
            
            <div class="pm-card-body">
                <?php if (!empty($public_communities)): ?>
                    <div class="pm-grid pm-grid-auto">
                    <?php foreach ($public_communities as $community): ?>
                        <div class="pm-card">
                            <div class="pm-card-header pm-flex pm-flex-between pm-flex-center-gap">
                                <h3 class="pm-heading pm-heading-sm pm-m-0">
                                    <a href="<?php echo home_url('/communities/' . $community->slug); ?>" class="pm-text-primary" >
                                        <?php echo esc_html($community->name); ?>
                                    </a>
                                </h3>
                                <div class="pm-badge pm-badge-<?php echo $community->privacy === 'public' ? 'success' : 'secondary'; ?>">
                                    <?php echo esc_html(ucfirst($community->privacy)); ?>
                                </div>
                            </div>
                            
                            <div class="pm-card-body">
                                <div class="pm-mb-4">
                                    <div class="pm-meta-item">
                                        <span>👥</span>
                                        <span class="pm-text-muted"><?php echo (int) $community->member_count; ?> <?php _e('members', 'partyminder'); ?></span>
                                    </div>
                                    <div class="pm-meta-item">
                                        <span>📂</span>
                                        <span class="pm-text-muted"><?php echo ucfirst($community->type); ?></span>
                                    </div>
                                </div>
                            
                                <?php if ($community->description): ?>
                                <div class="pm-mb-4">
                                    <p class="pm-text-muted"><?php echo esc_html(wp_trim_words($community->description, 20)); ?></p>
                                </div>
                            <?php endif; ?>
                            </div>
                            
                            <div class="pm-card-footer pm-flex pm-flex-between pm-flex-center-gap">
                                <div class="pm-stat">
                                    <div class="pm-stat-number pm-text-primary"><?php echo (int) $community->event_count; ?></div>
                                    <div class="pm-stat-label"><?php _e('Events', 'partyminder'); ?></div>
                                </div>
                                
                                <?php if (is_user_logged_in()): ?>
                                    <?php 
                                    $is_member = $community_manager->is_member($community->id, $current_user->ID);
                                    ?>
                                    <a href="<?php echo home_url('/communities/' . $community->slug); ?>" 
                                       class="pm-button pm-button-<?php echo $is_member ? 'secondary' : 'primary'; ?> pm-button-small">
                                        <?php echo $is_member ? __('Member', 'partyminder') : __('Join', 'partyminder'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), PartyMinder::get_login_url()); ?>" class="pm-button pm-button-primary pm-button-small">
                                        <?php _e('Login to Join', 'partyminder'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="pm-text-center pm-p-6">
                        <p class="pm-text-muted pm-mb-2"><?php _e('No public communities yet.', 'partyminder'); ?></p>
                        <p class="pm-text-muted"><?php _e('Be the first to create a community!', 'partyminder'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="pm-dashboard-sidebar">
            <?php if (is_user_logged_in() && !empty($user_communities)): ?>
                <!-- My Communities -->
                <div class="pm-card">
                    <div class="pm-card-header">
                        <h3 class="pm-heading pm-heading-sm pm-m-0">👥 <?php _e('My Communities', 'partyminder'); ?></h3>
                        <p class="pm-text-muted pm-mt-2"><?php _e('Communities you\'ve joined', 'partyminder'); ?></p>
                    </div>
                    <div class="pm-card-body">
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
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">⚡ <?php _e('Quick Actions', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body">
                    <div class="pm-flex pm-flex-center-gap pm-flex-column">
                        <?php if (PartyMinder_Feature_Flags::can_user_create_community()): ?>
                            <a href="<?php echo esc_url(site_url('/create-community')); ?>" class="pm-button pm-button-primary">
                                <span>✨</span>
                                <?php _e('Create Community', 'partyminder'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-button pm-button-secondary">
                            <span>🎉</span>
                            <?php _e('Create Event', 'partyminder'); ?>
                        </a>
                        
                        <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-button pm-button-secondary">
                            <span>💬</span>
                            <?php _e('Join Conversations', 'partyminder'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Community Types -->
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">🏷️ <?php _e('Community Types', 'partyminder'); ?></h3>
                    <p class="pm-text-muted pm-mt-2"><?php _e('Different ways to organize', 'partyminder'); ?></p>
                </div>
                <div class="pm-card-body">
                    <div class="pm-text-sm pm-leading-relaxed">
                        <div class="pm-mb-3">
                            <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                <span>🏢</span>
                                <strong><?php _e('Work', 'partyminder'); ?></strong>
                            </div>
                            <p class="pm-text-muted pm-m-0" class="pm-text-xs pm-ml-6"><?php _e('Office events, team building', 'partyminder'); ?></p>
                        </div>
                        <div class="pm-mb-3">
                            <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                <span>⛪</span>
                                <strong><?php _e('Faith', 'partyminder'); ?></strong>
                            </div>
                            <p class="pm-text-muted pm-m-0" class="pm-text-xs pm-ml-6"><?php _e('Church, religious gatherings', 'partyminder'); ?></p>
                        </div>
                        <div class="pm-mb-3">
                            <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                <span>👨‍👩‍👧‍👦</span>
                                <strong><?php _e('Family', 'partyminder'); ?></strong>
                            </div>
                            <p class="pm-text-muted pm-m-0" class="pm-text-xs pm-ml-6"><?php _e('Family reunions, celebrations', 'partyminder'); ?></p>
                        </div>
                        <div class="pm-mb-3">
                            <div class="pm-flex pm-flex-center-gap pm-mb-1">
                                <span>🎯</span>
                                <strong><?php _e('Hobby', 'partyminder'); ?></strong>
                            </div>
                            <p class="pm-text-muted pm-m-0" class="pm-text-xs pm-ml-6"><?php _e('Interest-based groups', 'partyminder'); ?></p>
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