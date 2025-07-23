<?php
/**
 * Community Management Modal Template
 * Frontend modal for community admins to manage their communities
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if communities are enabled
if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
    return;
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
.community-management-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    animation: fadeIn 0.3s ease;
}

.community-management-modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.community-management-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 800px;
    width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideIn 0.3s ease;
}

.community-management-modal-header {
    background: linear-gradient(135deg, <?php echo esc_attr($primary_color); ?>, <?php echo esc_attr($secondary_color); ?>);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.community-management-modal-title {
    font-size: 1.3em;
    font-weight: bold;
    margin: 0;
}

.community-management-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5em;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
}

.community-management-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.community-management-modal-body {
    padding: 0;
}

.management-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.management-tab-btn {
    flex: 1;
    background: none;
    border: none;
    padding: 15px 20px;
    cursor: pointer;
    color: #666;
    font-weight: 500;
    transition: all 0.2s ease;
    border-bottom: 3px solid transparent;
}

.management-tab-btn:hover,
.management-tab-btn.active {
    color: <?php echo esc_attr($primary_color); ?>;
    border-bottom-color: <?php echo esc_attr($primary_color); ?>;
    background: white;
}

.management-tab-content {
    padding: 30px;
    min-height: 300px;
}

.management-tab-pane {
    display: none;
}

.management-tab-pane.active {
    display: block;
}

.management-form-group {
    margin-bottom: 20px;
}

.management-form-label {
    display: block;
    font-weight: bold;
    color: #333;
    margin-bottom: 8px;
}

.management-form-input,
.management-form-select,
.management-form-textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 1em;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
}

.management-form-input:focus,
.management-form-select:focus,
.management-form-textarea:focus {
    outline: none;
    border-color: <?php echo esc_attr($primary_color); ?>;
}

.management-form-help {
    font-size: 0.85em;
    color: #666;
    margin-top: 5px;
}

.management-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1em;
}

.management-btn-primary {
    background: <?php echo esc_attr($primary_color); ?>;
    color: white;
}

.management-btn-primary:hover {
    opacity: 0.9;
}

.management-btn-secondary {
    background: #6c757d;
    color: white;
}

.management-btn-secondary:hover {
    opacity: 0.9;
}

.member-list {
    margin-top: 20px;
}

.member-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 10px;
}

.member-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.member-avatar {
    width: 40px;
    height: 40px;
    background: <?php echo esc_attr($primary_color); ?>;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.member-details h4 {
    margin: 0 0 5px 0;
    color: #333;
}

.member-details small {
    color: #666;
}

.member-role {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.member-role.admin {
    background: #dc3545;
    color: white;
}

.member-role.member {
    background: #28a745;
    color: white;
}

.placeholder-content {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

@media (max-width: 768px) {
    .community-management-modal {
        width: 98%;
        margin: 10px;
    }
    
    .community-management-modal-body {
        padding: 0;
    }
    
    .management-tab-content {
        padding: 20px;
    }
    
    .management-tabs {
        flex-direction: column;
    }
}
</style>

<!-- Community Management Modal -->
<div id="community-management-modal" class="community-management-modal-overlay">
    <div class="community-management-modal">
        <div class="community-management-modal-header">
            <h3 class="community-management-modal-title">⚙️ Manage Community</h3>
            <button type="button" class="community-management-modal-close" aria-label="<?php _e('Close', 'partyminder'); ?>">×</button>
        </div>
        
        <div class="community-management-modal-body">
            <div class="management-tabs">
                <button class="management-tab-btn active" data-tab="settings">
                    <?php _e('Settings', 'partyminder'); ?>
                </button>
                <button class="management-tab-btn" data-tab="members">
                    <?php _e('Members', 'partyminder'); ?>
                </button>
                <button class="management-tab-btn" data-tab="invites">
                    <?php _e('Invitations', 'partyminder'); ?>
                </button>
            </div>
            
            <div class="management-tab-content">
                <!-- Settings Tab -->
                <div id="settings-tab" class="management-tab-pane active">
                    <h4><?php _e('Community Settings', 'partyminder'); ?></h4>
                    <form id="community-settings-form">
                        <div class="management-form-group">
                            <label class="management-form-label">
                                <?php _e('Community Name', 'partyminder'); ?>
                            </label>
                            <input type="text" class="management-form-input" id="community-name" readonly>
                            <div class="management-form-help">
                                <?php _e('Contact site administrator to change the community name', 'partyminder'); ?>
                            </div>
                        </div>
                        
                        <div class="management-form-group">
                            <label class="management-form-label">
                                <?php _e('Description', 'partyminder'); ?>
                            </label>
                            <textarea class="management-form-textarea" id="community-description" rows="3" 
                                      placeholder="<?php _e('Update community description...', 'partyminder'); ?>"></textarea>
                        </div>
                        
                        <div class="management-form-group">
                            <label class="management-form-label">
                                <?php _e('Privacy Setting', 'partyminder'); ?>
                            </label>
                            <select class="management-form-select" id="community-privacy">
                                <option value="public"><?php _e('🌍 Public - Anyone can join', 'partyminder'); ?></option>
                                <option value="private"><?php _e('🔒 Private - Invite only', 'partyminder'); ?></option>
                            </select>
                        </div>
                        
                        <button type="submit" class="management-btn management-btn-primary">
                            <?php _e('Save Changes', 'partyminder'); ?>
                        </button>
                    </form>
                </div>
                
                <!-- Members Tab -->
                <div id="members-tab" class="management-tab-pane">
                    <h4><?php _e('Community Members', 'partyminder'); ?></h4>
                    <div id="members-list">
                        <div class="placeholder-content">
                            <p><?php _e('Loading community members...', 'partyminder'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Invitations Tab -->
                <div id="invites-tab" class="management-tab-pane">
                    <h4><?php _e('Invite New Members', 'partyminder'); ?></h4>
                    <form id="invite-members-form">
                        <div class="management-form-group">
                            <label class="management-form-label">
                                <?php _e('Email Address', 'partyminder'); ?>
                            </label>
                            <input type="email" class="management-form-input" id="invite-email" 
                                   placeholder="<?php _e('Enter email address...', 'partyminder'); ?>" required>
                        </div>
                        
                        <div class="management-form-group">
                            <label class="management-form-label">
                                <?php _e('Personal Message (Optional)', 'partyminder'); ?>
                            </label>
                            <textarea class="management-form-textarea" id="invite-message" rows="3"
                                      placeholder="<?php _e('Add a personal message to your invitation...', 'partyminder'); ?>"></textarea>
                        </div>
                        
                        <button type="submit" class="management-btn management-btn-primary">
                            <?php _e('Send Invitation', 'partyminder'); ?>
                        </button>
                    </form>
                    
                    <div class="placeholder-content" style="margin-top: 30px;">
                        <p><?php _e('💡 Member invitation system coming in the next update!', 'partyminder'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('community-management-modal');
    
    // Show modal function
    function showCommunityManagementModal(communityData) {
        // Populate modal with community data
        if (communityData) {
            document.getElementById('community-name').value = communityData.name || '';
            document.getElementById('community-description').value = communityData.description || '';
            document.getElementById('community-privacy').value = communityData.privacy || 'public';
        }
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // Hide modal function
    function hideCommunityManagementModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Tab switching
    const tabBtns = modal.querySelectorAll('.management-tab-btn');
    const tabPanes = modal.querySelectorAll('.management-tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Update active tab button
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update active tab pane
            tabPanes.forEach(pane => pane.classList.remove('active'));
            modal.querySelector('#' + targetTab + '-tab').classList.add('active');
        });
    });
    
    // Close modal events
    modal.querySelector('.community-management-modal-close').addEventListener('click', hideCommunityManagementModal);
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideCommunityManagementModal();
        }
    });
    
    // Form handlers
    document.getElementById('community-settings-form').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('<?php _e('Community settings update coming soon!', 'partyminder'); ?>');
    });
    
    document.getElementById('invite-members-form').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('<?php _e('Member invitation system coming soon!', 'partyminder'); ?>');
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            hideCommunityManagementModal();
        }
    });
    
    // Make the function available globally
    window.showCommunityManagementModal = showCommunityManagementModal;
});
</script>