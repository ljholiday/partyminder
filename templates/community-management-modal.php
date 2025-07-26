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
                    
                    <?php if (PartyMinder_Feature_Flags::is_at_protocol_enabled()): ?>
                    <!-- Bluesky Connection for Communities -->
                    <div id="community-bluesky-section" style="margin-bottom: 30px;">
                        <div id="community-bluesky-not-connected" class="info-card" style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                            <h5 style="margin-top: 0; color: #0284c7;">🦋 <?php _e('Connect Bluesky for Easy Community Invites', 'partyminder'); ?></h5>
                            <p style="margin-bottom: 15px;"><?php _e('Connect your Bluesky account to invite your contacts to join this community.', 'partyminder'); ?></p>
                            <button type="button" class="management-btn management-btn-secondary" id="community-connect-bluesky-btn">
                                <?php _e('Connect Bluesky Account', 'partyminder'); ?>
                            </button>
                        </div>
                        
                        <div id="community-bluesky-connected" class="success-card" style="display: none; background: #f0fdf4; border: 1px solid #22c55e; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                            <h5 style="margin-top: 0; color: #059669;">✅ <?php _e('Bluesky Connected', 'partyminder'); ?></h5>
                            <p style="margin-bottom: 15px;"><?php _e('Connected as', 'partyminder'); ?> <strong id="community-bluesky-handle"></strong></p>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button type="button" class="management-btn management-btn-primary" id="community-load-contacts-btn">
                                    <?php _e('Load Bluesky Contacts', 'partyminder'); ?>
                                </button>
                                <button type="button" class="management-btn management-btn-danger" id="community-disconnect-bluesky-btn" style="font-size: 12px; padding: 6px 12px;">
                                    <?php _e('Disconnect', 'partyminder'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Community Bluesky Contacts -->
                        <div id="community-contacts-section" style="display: none; margin-top: 20px;">
                            <h5><?php _e('Select from Bluesky Contacts', 'partyminder'); ?></h5>
                            <div style="margin-bottom: 15px;">
                                <input type="text" class="management-form-input" id="community-contacts-search" 
                                       placeholder="<?php _e('Search your contacts...', 'partyminder'); ?>">
                            </div>
                            <div id="community-contacts-list" class="community-contacts-grid" style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                                <!-- Contacts will be loaded here -->
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Email Invitation Form -->
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
                    
                    <div style="margin-top: 30px;">
                        <h4><?php _e('Pending Invitations', 'partyminder'); ?></h4>
                        <div id="invitations-list">
                            <div class="placeholder-content">
                                <p><?php _e('Loading pending invitations...', 'partyminder'); ?></p>
                            </div>
                        </div>
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
        // Store community data globally for form submissions
        window.currentCommunityData = communityData;
        
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
            
            // Load members when members tab is clicked
            if (targetTab === 'members' && window.currentCommunityData) {
                loadCommunityMembers(window.currentCommunityData.id);
            }
            
            // Load invitations when invitations tab is clicked
            if (targetTab === 'invites' && window.currentCommunityData) {
                loadCommunityInvitations(window.currentCommunityData.id);
            }
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
        
        const formData = {
            description: document.getElementById('community-description').value,
            privacy: document.getElementById('community-privacy').value
        };
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = partyminder_ajax.strings.loading;
        submitBtn.disabled = true;
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_update_community',
                community_id: window.currentCommunityData.id,
                description: formData.description,
                privacy: formData.privacy,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Update the community data
                    window.currentCommunityData.description = formData.description;
                    window.currentCommunityData.privacy = formData.privacy;
                    // Reload page to show updated community info
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert(response.data || partyminder_ajax.strings.error);
                }
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            },
            error: function() {
                alert(partyminder_ajax.strings.error);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    });
    
    document.getElementById('invite-members-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = document.getElementById('invite-email').value;
        const message = document.getElementById('invite-message').value;
        
        if (!email) {
            alert('<?php _e('Please enter an email address.', 'partyminder'); ?>');
            return;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = partyminder_ajax.strings.loading;
        submitBtn.disabled = true;
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_send_invitation',
                community_id: window.currentCommunityData.id,
                email: email,
                message: message,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Clear form
                    document.getElementById('invite-email').value = '';
                    document.getElementById('invite-message').value = '';
                    // Reload invitations list
                    loadCommunityInvitations(window.currentCommunityData.id);
                } else {
                    alert(response.data || partyminder_ajax.strings.error);
                }
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            },
            error: function() {
                alert(partyminder_ajax.strings.error);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            hideCommunityManagementModal();
        }
    });
    
    // Load community members
    function loadCommunityMembers(communityId) {
        const membersList = document.getElementById('members-list');
        membersList.innerHTML = '<div class="placeholder-content"><p><?php _e('Loading community members...', 'partyminder'); ?></p></div>';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_community_members',
                community_id: communityId,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success && response.data.members) {
                    renderMembersList(response.data.members);
                } else {
                    membersList.innerHTML = '<div class="placeholder-content"><p>' + (response.data || partyminder_ajax.strings.error) + '</p></div>';
                }
            },
            error: function() {
                membersList.innerHTML = '<div class="placeholder-content"><p>' + partyminder_ajax.strings.error + '</p></div>';
            }
        });
    }
    
    // Render members list
    function renderMembersList(members) {
        const membersList = document.getElementById('members-list');
        
        if (!members || members.length === 0) {
            membersList.innerHTML = '<div class="placeholder-content"><p><?php _e('No members found.', 'partyminder'); ?></p></div>';
            return;
        }
        
        let html = '';
        members.forEach(member => {
            const initials = member.display_name ? member.display_name.substring(0, 2).toUpperCase() : 'U';
            const joinedDate = new Date(member.joined_at).toLocaleDateString();
            
            html += `
                <div class="member-item" data-member-id="${member.id}">
                    <div class="member-info">
                        <div class="member-avatar">${initials}</div>
                        <div class="member-details">
                            <h4>${member.display_name || member.email}</h4>
                            <small><?php _e('Member since', 'partyminder'); ?> ${joinedDate}</small>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="member-role ${member.role}">${member.role}</span>
                        <div class="member-actions">
                            ${member.role === 'member' ? 
                                '<button class="management-btn management-btn-secondary promote-btn" data-member-id="' + member.id + '"><?php _e('Promote', 'partyminder'); ?></button>' : 
                                '<button class="management-btn management-btn-secondary demote-btn" data-member-id="' + member.id + '"><?php _e('Demote', 'partyminder'); ?></button>'
                            }
                            <button class="management-btn management-btn-secondary remove-btn" data-member-id="${member.id}" data-member-name="${member.display_name || member.email}">
                                <?php _e('Remove', 'partyminder'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        membersList.innerHTML = html;
        
        // Add event listeners for member actions
        attachMemberActionListeners();
    }
    
    // Attach event listeners for member actions
    function attachMemberActionListeners() {
        // Promote buttons
        document.querySelectorAll('.promote-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-member-id');
                updateMemberRole(memberId, 'admin');
            });
        });
        
        // Demote buttons
        document.querySelectorAll('.demote-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-member-id');
                updateMemberRole(memberId, 'member');
            });
        });
        
        // Remove buttons
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-member-id');
                const memberName = this.getAttribute('data-member-name');
                
                if (confirm('<?php _e('Are you sure you want to remove', 'partyminder'); ?> "' + memberName + '" <?php _e('from this community?', 'partyminder'); ?>')) {
                    removeMember(memberId);
                }
            });
        });
    }
    
    // Update member role
    function updateMemberRole(memberId, newRole) {
        const actionName = newRole === 'admin' ? '<?php _e('Promoting', 'partyminder'); ?>' : '<?php _e('Demoting', 'partyminder'); ?>';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_update_member_role',
                community_id: window.currentCommunityData.id,
                member_id: memberId,
                new_role: newRole,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload members list
                    loadCommunityMembers(window.currentCommunityData.id);
                } else {
                    alert(response.data || partyminder_ajax.strings.error);
                }
            },
            error: function() {
                alert(partyminder_ajax.strings.error);
            }
        });
    }
    
    // Remove member
    function removeMember(memberId) {
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_remove_member',
                community_id: window.currentCommunityData.id,
                member_id: memberId,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload members list
                    loadCommunityMembers(window.currentCommunityData.id);
                } else {
                    alert(response.data || partyminder_ajax.strings.error);
                }
            },
            error: function() {
                alert(partyminder_ajax.strings.error);
            }
        });
    }
    
    // Load community invitations
    function loadCommunityInvitations(communityId) {
        const invitationsList = document.getElementById('invitations-list');
        invitationsList.innerHTML = '<div class="placeholder-content"><p><?php _e('Loading pending invitations...', 'partyminder'); ?></p></div>';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_community_invitations',
                community_id: communityId,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success && response.data.invitations) {
                    renderInvitationsList(response.data.invitations);
                } else {
                    invitationsList.innerHTML = '<div class="placeholder-content"><p>' + (response.data || partyminder_ajax.strings.error) + '</p></div>';
                }
            },
            error: function() {
                invitationsList.innerHTML = '<div class="placeholder-content"><p>' + partyminder_ajax.strings.error + '</p></div>';
            }
        });
    }
    
    // Render invitations list
    function renderInvitationsList(invitations) {
        const invitationsList = document.getElementById('invitations-list');
        
        if (!invitations || invitations.length === 0) {
            invitationsList.innerHTML = '<div class="placeholder-content"><p><?php _e('No pending invitations.', 'partyminder'); ?></p></div>';
            return;
        }
        
        let html = '';
        invitations.forEach(invitation => {
            const createdDate = new Date(invitation.created_at).toLocaleDateString();
            const expiresDate = new Date(invitation.expires_at).toLocaleDateString();
            
            html += `
                <div class="member-item" data-invitation-id="${invitation.id}">
                    <div class="member-info">
                        <div class="member-avatar">📧</div>
                        <div class="member-details">
                            <h4>${invitation.invited_email}</h4>
                            <small><?php _e('Invited by', 'partyminder'); ?> ${invitation.inviter_name || '<?php _e('Unknown', 'partyminder'); ?>'} <?php _e('on', 'partyminder'); ?> ${createdDate}</small>
                            <br><small><?php _e('Expires', 'partyminder'); ?> ${expiresDate}</small>
                            ${invitation.message ? '<br><small><em>"' + invitation.message + '"</em></small>' : ''}
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="member-role member" style="background: #ffc107; color: #000;"><?php _e('pending', 'partyminder'); ?></span>
                        <button class="management-btn management-btn-secondary cancel-invitation-btn" data-invitation-id="${invitation.id}" data-email="${invitation.invited_email}">
                            <?php _e('Cancel', 'partyminder'); ?>
                        </button>
                    </div>
                </div>
            `;
        });
        
        invitationsList.innerHTML = html;
        
        // Add event listeners for invitation actions
        attachInvitationActionListeners();
    }
    
    // Attach event listeners for invitation actions
    function attachInvitationActionListeners() {
        // Cancel invitation buttons
        document.querySelectorAll('.cancel-invitation-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const invitationId = this.getAttribute('data-invitation-id');
                const email = this.getAttribute('data-email');
                
                if (confirm('<?php _e('Are you sure you want to cancel the invitation to', 'partyminder'); ?> "' + email + '"?')) {
                    cancelInvitation(invitationId);
                }
            });
        });
    }
    
    // Cancel invitation
    function cancelInvitation(invitationId) {
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_cancel_invitation',
                community_id: window.currentCommunityData.id,
                invitation_id: invitationId,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload invitations list
                    loadCommunityInvitations(window.currentCommunityData.id);
                } else {
                    alert(response.data || partyminder_ajax.strings.error);
                }
            },
            error: function() {
                alert(partyminder_ajax.strings.error);
            }
        });
    }
    
    // Community Bluesky Integration Functions
    let communityBlueSkyContacts = [];
    let isCommunityBlueskyConnected = false;
    
    // Check Bluesky connection for community tab
    function checkCommunityBlueskyConnection() {
        if (!document.getElementById('community-bluesky-section')) return;
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_check_bluesky_connection',
                nonce: partyminder_ajax.at_protocol_nonce
            },
            success: function(response) {
                if (response.success && response.data.connected) {
                    showCommunityBlueskyConnected(response.data.handle);
                } else {
                    showCommunityBlueskyNotConnected();
                }
            },
            error: function() {
                showCommunityBlueskyNotConnected();
            }
        });
    }
    
    function showCommunityBlueskyConnected(handle) {
        isCommunityBlueskyConnected = true;
        document.getElementById('community-bluesky-not-connected').style.display = 'none';
        document.getElementById('community-bluesky-connected').style.display = 'block';
        document.getElementById('community-bluesky-handle').textContent = handle;
    }
    
    function showCommunityBlueskyNotConnected() {
        isCommunityBlueskyConnected = false;
        document.getElementById('community-bluesky-not-connected').style.display = 'block';
        document.getElementById('community-bluesky-connected').style.display = 'none';
        document.getElementById('community-contacts-section').style.display = 'none';
    }
    
    // Load Community Bluesky contacts
    function loadCommunityBlueskyContacts() {
        const contactsList = document.getElementById('community-contacts-list');
        const contactsSection = document.getElementById('community-contacts-section');
        const loadBtn = document.getElementById('community-load-contacts-btn');
        
        loadBtn.disabled = true;
        loadBtn.textContent = '<?php _e('Loading...', 'partyminder'); ?>';
        
        contactsList.innerHTML = '<div style="text-align: center; padding: 20px;"><p><?php _e('Loading your Bluesky contacts...', 'partyminder'); ?></p></div>';
        contactsSection.style.display = 'block';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_bluesky_contacts',
                nonce: partyminder_ajax.at_protocol_nonce
            },
            success: function(response) {
                if (response.success && response.data.contacts) {
                    communityBlueSkyContacts = response.data.contacts;
                    renderCommunityBlueskyContacts(communityBlueSkyContacts);
                } else {
                    contactsList.innerHTML = '<div style="text-align: center; padding: 20px;"><p>' + (response.message || '<?php _e('No contacts found.', 'partyminder'); ?>') + '</p></div>';
                }
                loadBtn.disabled = false;
                loadBtn.textContent = '<?php _e('Refresh Contacts', 'partyminder'); ?>';
            },
            error: function() {
                contactsList.innerHTML = '<div style="text-align: center; padding: 20px;"><p style="color: #dc2626;"><?php _e('Failed to load contacts.', 'partyminder'); ?></p></div>';
                loadBtn.disabled = false;
                loadBtn.textContent = '<?php _e('Load Bluesky Contacts', 'partyminder'); ?>';
            }
        });
    }
    
    // Render Community Bluesky contacts
    function renderCommunityBlueskyContacts(contacts) {
        const contactsList = document.getElementById('community-contacts-list');
        
        if (!contacts || contacts.length === 0) {
            contactsList.innerHTML = '<div style="text-align: center; padding: 20px;"><p><?php _e('No contacts found.', 'partyminder'); ?></p></div>';
            return;
        }
        
        let html = '';
        contacts.forEach(contact => {
            const avatar = contact.avatar || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiNkMWQ1ZGIiLz4KPC9zdmc+';
            
            html += `
                <div class="community-contact-item" data-handle="${contact.handle}" data-display-name="${contact.display_name}" style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 8px; background: white;">
                    <div style="flex-shrink: 0;">
                        <img src="${avatar}" alt="${contact.display_name}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${contact.display_name}</div>
                        <div style="font-size: 12px; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@${contact.handle}</div>
                        ${contact.description ? '<div style="font-size: 11px; color: #9ca3af; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + contact.description.substring(0, 40) + (contact.description.length > 40 ? '...' : '') + '</div>' : ''}
                    </div>
                    <button type="button" class="management-btn management-btn-primary community-invite-contact-btn" style="font-size: 12px; padding: 5px 10px;">
                        <?php _e('Invite', 'partyminder'); ?>
                    </button>
                </div>
            `;
        });
        
        contactsList.innerHTML = html;
        
        // Add click handlers for invite buttons
        contactsList.querySelectorAll('.community-invite-contact-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const contactItem = this.closest('.community-contact-item');
                const handle = contactItem.dataset.handle;
                const displayName = contactItem.dataset.displayName;
                inviteCommunityBlueskyContact(handle, displayName, this);
            });
        });
        
        // Add search functionality
        const searchInput = document.getElementById('community-contacts-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const filteredContacts = contacts.filter(contact => 
                    contact.display_name.toLowerCase().includes(query) || 
                    contact.handle.toLowerCase().includes(query) ||
                    (contact.description && contact.description.toLowerCase().includes(query))
                );
                renderCommunityBlueskyContacts(filteredContacts);
            });
        }
    }
    
    // Invite Bluesky contact to community
    function inviteCommunityBlueskyContact(handle, displayName, btnElement) {
        const originalText = btnElement.textContent;
        btnElement.disabled = true;
        btnElement.textContent = '<?php _e('Inviting...', 'partyminder'); ?>';
        
        // Use the handle as a pseudo-email for community invitations
        const pseudoEmail = handle + '@bsky.social';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_send_invitation',
                community_id: window.currentCommunityData.id,
                email: pseudoEmail,
                name: displayName,
                source: 'bluesky',
                bluesky_handle: handle,
                message: '<?php _e('Community invitation sent via Bluesky connection', 'partyminder'); ?>',
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    btnElement.textContent = '<?php _e('Invited!', 'partyminder'); ?>';
                    btnElement.style.backgroundColor = '#10b981';
                    btnElement.style.borderColor = '#10b981';
                    
                    // Reload invitations list
                    loadCommunityInvitations(window.currentCommunityData.id);
                    
                    setTimeout(() => {
                        btnElement.disabled = false;
                        btnElement.textContent = originalText;
                        btnElement.style.backgroundColor = '';
                        btnElement.style.borderColor = '';
                    }, 3000);
                } else {
                    alert(response.data || '<?php _e('Failed to send community invitation', 'partyminder'); ?>');
                    btnElement.disabled = false;
                    btnElement.textContent = originalText;
                }
            },
            error: function() {
                alert('<?php _e('Network error. Please try again.', 'partyminder'); ?>');
                btnElement.disabled = false;
                btnElement.textContent = originalText;
            }
        });
    }
    
    // Event handlers for Community Bluesky features
    if (document.getElementById('community-connect-bluesky-btn')) {
        document.getElementById('community-connect-bluesky-btn').addEventListener('click', function() {
            // Reuse the same Bluesky connect modal from events, or create it if not available
            if (typeof showBlueskyConnectModal === 'function') {
                showBlueskyConnectModal();
            } else {
                showCommunityBlueskyConnectModal();
            }
        });
    }
    
    // Bluesky connect modal for community context
    function showCommunityBlueskyConnectModal() {
        const connectHtml = `
            <div id="bluesky-connect-modal" class="pm-modal-overlay" style="z-index: 10001;">
                <div class="pm-modal pm-modal-sm">
                    <div class="pm-modal-header">
                        <h3>🦋 <?php _e('Connect to Bluesky', 'partyminder'); ?></h3>
                        <button type="button" class="bluesky-connect-close pm-button pm-button-secondary" style="padding: 5px; border-radius: 50%; width: 35px; height: 35px;">×</button>
                    </div>
                    <div class="pm-modal-body">
                        <form id="bluesky-connect-form">
                            <div class="pm-form-group">
                                <label class="pm-label"><?php _e('Bluesky Handle', 'partyminder'); ?></label>
                                <input type="text" class="pm-input" id="bluesky-handle-input" 
                                       placeholder="<?php _e('username.bsky.social', 'partyminder'); ?>" required>
                            </div>
                            <div class="pm-form-group">
                                <label class="pm-label"><?php _e('App Password', 'partyminder'); ?></label>
                                <input type="password" class="pm-input" id="bluesky-password-input" 
                                       placeholder="<?php _e('Your Bluesky app password', 'partyminder'); ?>" required>
                                <small class="pm-text-muted">
                                    <?php _e('Create an app password in your Bluesky settings for secure access.', 'partyminder'); ?>
                                </small>
                            </div>
                            <div class="pm-flex pm-flex-center-gap pm-mt-4">
                                <button type="submit" class="pm-button pm-button-primary">
                                    <?php _e('Connect Account', 'partyminder'); ?>
                                </button>
                                <button type="button" class="bluesky-connect-close pm-button pm-button-secondary">
                                    <?php _e('Cancel', 'partyminder'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', connectHtml);
        
        const connectModal = document.getElementById('bluesky-connect-modal');
        connectModal.classList.add('active');
        
        // Close handlers
        connectModal.querySelectorAll('.bluesky-connect-close').forEach(btn => {
            btn.addEventListener('click', () => {
                connectModal.remove();
            });
        });
        
        // Form submission
        document.getElementById('bluesky-connect-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const handle = document.getElementById('bluesky-handle-input').value;
            const password = document.getElementById('bluesky-password-input').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.textContent = '<?php _e('Connecting...', 'partyminder'); ?>';
            
            jQuery.ajax({
                url: partyminder_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'partyminder_connect_bluesky',
                    handle: handle,
                    password: password,
                    nonce: partyminder_ajax.at_protocol_nonce
                },
                success: function(response) {
                    console.log('Bluesky connection response:', response);
                    if (response && response.success) {
                        alert('<?php _e('Successfully connected to Bluesky!', 'partyminder'); ?>');
                        connectModal.remove();
                        showCommunityBlueskyConnected(handle);
                    } else {
                        alert(response.message || '<?php _e('Failed to connect to Bluesky', 'partyminder'); ?>');
                        submitBtn.disabled = false;
                        submitBtn.textContent = '<?php _e('Connect Account', 'partyminder'); ?>';
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Bluesky connection error:', xhr.responseText);
                    alert('<?php _e('Network error. Please try again.', 'partyminder'); ?>');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '<?php _e('Connect Account', 'partyminder'); ?>';
                }
            });
        });
    }
    
    if (document.getElementById('community-load-contacts-btn')) {
        document.getElementById('community-load-contacts-btn').addEventListener('click', loadCommunityBlueskyContacts);
    }
    
    if (document.getElementById('community-disconnect-bluesky-btn')) {
        document.getElementById('community-disconnect-bluesky-btn').addEventListener('click', function() {
            if (confirm('<?php _e('Are you sure you want to disconnect your Bluesky account?', 'partyminder'); ?>')) {
                jQuery.ajax({
                    url: partyminder_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'partyminder_disconnect_bluesky',
                        nonce: partyminder_ajax.at_protocol_nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showCommunityBlueskyNotConnected();
                            alert('<?php _e('Bluesky account disconnected', 'partyminder'); ?>');
                        } else {
                            alert(response.message || '<?php _e('Failed to disconnect', 'partyminder'); ?>');
                        }
                    }
                });
            }
        });
    }
    
    // Override the original community modal show function to include Bluesky check
    const originalCommunityShowFunction = showCommunityManagementModal;
    showCommunityManagementModal = function(communityData) {
        originalCommunityShowFunction(communityData);
        // Check Bluesky connection when modal opens and invites tab is activated
        setTimeout(function() {
            if (document.querySelector('#invites-tab.active')) {
                checkCommunityBlueskyConnection();
            }
        }, 100);
    };
    
    // Also check when invites tab is clicked
    const originalSwitchToTab = switchToTab;
    switchToTab = function(targetTab) {
        originalSwitchToTab(targetTab);
        if (targetTab === 'invites') {
            setTimeout(checkCommunityBlueskyConnection, 100);
        }
    };

    // Make the function available globally
    window.showCommunityManagementModal = showCommunityManagementModal;
});
</script>