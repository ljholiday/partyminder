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
    echo '<div style="text-align: center; padding: 60px 20px;">';
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

.community-stats {
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

.sidebar-card-content {
    padding: 20px;
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

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    background: var(--pm-primary);
    color: white;
    border: none;
    padding: 12px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
    text-align: center;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.action-btn:hover {
    opacity: 0.9;
    color: white;
}

.action-btn.secondary {
    background: #6c757d;
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
    
    .community-stats {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
}
</style>

<div class="partyminder-communities">
    <!-- Header -->
    <div class="communities-header">
        <h1><?php _e('🏘️ Communities', 'partyminder'); ?></h1>
        <p><?php _e('Join communities of fellow hosts and guests to plan amazing events together', 'partyminder'); ?></p>
        
        <?php if (PartyMinder_Feature_Flags::can_user_create_community()): ?>
            <a href="#" class="create-community-btn create-community-modal-btn">
                <?php _e('✨ Create Community', 'partyminder'); ?>
            </a>
        <?php elseif (!is_user_logged_in()): ?>
            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="create-community-btn">
                <?php _e('👋 Login to Join Communities', 'partyminder'); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Two-column layout -->
    <div class="communities-layout">
        <!-- LEFT COLUMN - Public Communities -->
        <div class="communities-section">
            <div class="section-header">
                <h2><?php _e('🌍 Discover Communities', 'partyminder'); ?></h2>
                <span style="font-size: 0.9em; opacity: 0.9;"><?php printf(__('%d communities', 'partyminder'), count($public_communities)); ?></span>
            </div>
            
            <?php if (!empty($public_communities)): ?>
                <div class="communities-grid">
                    <?php foreach ($public_communities as $community): ?>
                        <div class="community-card">
                            <span class="privacy-badge <?php echo esc_attr($community->privacy); ?>">
                                <?php echo esc_html(ucfirst($community->privacy)); ?>
                            </span>
                            
                            <div class="community-header">
                                <div class="community-avatar">
                                    <?php echo strtoupper(substr($community->name, 0, 2)); ?>
                                </div>
                                <div class="community-info">
                                    <h3>
                                        <a href="<?php echo home_url('/communities/' . $community->slug); ?>" style="text-decoration: none; color: inherit;">
                                            <?php echo esc_html($community->name); ?>
                                        </a>
                                    </h3>
                                    <div class="community-meta">
                                        <span><?php echo (int) $community->member_count; ?> <?php _e('members', 'partyminder'); ?></span>
                                        <span><?php echo ucfirst($community->type); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($community->description): ?>
                                <div class="community-description">
                                    <?php echo esc_html(wp_trim_words($community->description, 20)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="community-stats">
                                <div class="stat-item">
                                    <span>🗓️</span>
                                    <span><?php echo (int) $community->event_count; ?> <?php _e('events', 'partyminder'); ?></span>
                                </div>
                                
                                <?php if (is_user_logged_in()): ?>
                                    <?php 
                                    $is_member = $community_manager->is_member($community->id, $current_user->ID);
                                    ?>
                                    <a href="<?php echo home_url('/communities/' . $community->slug); ?>" 
                                       class="join-btn <?php echo $is_member ? 'member' : ''; ?>">
                                        <?php echo $is_member ? __('Member', 'partyminder') : __('Join', 'partyminder'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="join-btn">
                                        <?php _e('Login to Join', 'partyminder'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-communities">
                    <p><?php _e('No public communities yet.', 'partyminder'); ?></p>
                    <p><?php _e('Be the first to create a community!', 'partyminder'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN - User Communities & Actions -->
        <div class="communities-sidebar">
            <?php if (is_user_logged_in() && !empty($user_communities)): ?>
                <!-- My Communities -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <span>👥</span>
                        <?php _e('My Communities', 'partyminder'); ?>
                    </div>
                    <div class="sidebar-card-content">
                        <?php foreach ($user_communities as $user_community): ?>
                            <div class="user-community">
                                <div>
                                    <a href="<?php echo home_url('/communities/' . $user_community->slug); ?>" class="user-community-name">
                                        <?php echo esc_html($user_community->name); ?>
                                    </a>
                                    <div class="user-community-role"><?php echo esc_html(ucfirst($user_community->role)); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <span>⚡</span>
                    <?php _e('Quick Actions', 'partyminder'); ?>
                </div>
                <div class="sidebar-card-content">
                    <div class="quick-actions">
                        <?php if (PartyMinder_Feature_Flags::can_user_create_community()): ?>
                            <a href="#" class="action-btn create-community-modal-btn">
                                <span>✨</span>
                                <?php _e('Create Community', 'partyminder'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="action-btn secondary">
                            <span>🎉</span>
                            <?php _e('Create Event', 'partyminder'); ?>
                        </a>
                        
                        <a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="action-btn secondary">
                            <span>💬</span>
                            <?php _e('Join Conversations', 'partyminder'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Community Types -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <span>🏷️</span>
                    <?php _e('Community Types', 'partyminder'); ?>
                </div>
                <div class="sidebar-card-content">
                    <div style="font-size: 0.9em; line-height: 1.6;">
                        <p><strong>🏢 Work:</strong> <?php _e('Office events, team building', 'partyminder'); ?></p>
                        <p><strong>⛪ Faith:</strong> <?php _e('Church, religious gatherings', 'partyminder'); ?></p>
                        <p><strong>👨‍👩‍👧‍👦 Family:</strong> <?php _e('Family reunions, celebrations', 'partyminder'); ?></p>
                        <p><strong>🎯 Hobby:</strong> <?php _e('Interest-based groups', 'partyminder'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Include community creation modal if user can create communities
if (PartyMinder_Feature_Flags::can_user_create_community()) {
    include PARTYMINDER_PLUGIN_DIR . 'templates/community-creation-modal.php';
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Community creation modal is now handled by community-creation-modal.php
    
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