<?php
/**
 * Manage Community Content Template
 * Single-page community management interface (replaces community-management-modal.php)
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

// Get community ID from URL parameter
$community_id = isset($_GET['community_id']) ? intval($_GET['community_id']) : 0;
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

if (!$community_id) {
    echo '<div class="pm-container pm-text-center pm-p-16">';
    echo '<h2>' . __('Community Not Found', 'partyminder') . '</h2>';
    echo '<p>' . __('No community ID provided.', 'partyminder') . '</p>';
    echo '<a href="' . esc_url(PartyMinder::get_communities_url()) . '" class="pm-button pm-button-primary">' . __('Back to Communities', 'partyminder') . '</a>';
    echo '</div>';
    return;
}

// Get community data
$community = $community_manager->get_community($community_id);
if (!$community) {
    echo '<div class="pm-container pm-text-center pm-p-16">';
    echo '<h2>' . __('Community Not Found', 'partyminder') . '</h2>';
    echo '<p>' . __('The requested community does not exist.', 'partyminder') . '</p>';
    echo '<a href="' . esc_url(PartyMinder::get_communities_url()) . '" class="pm-button pm-button-primary">' . __('Back to Communities', 'partyminder') . '</a>';
    echo '</div>';
    return;
}

// Get current user and check permissions
$current_user = wp_get_current_user();
$user_role = is_user_logged_in() ? $community_manager->get_user_role($community_id, $current_user->ID) : null;

// Check if user can manage this community
if (!$user_role || $user_role !== 'admin') {
    echo '<div class="pm-container pm-text-center pm-p-16">';
    echo '<h2>' . __('Access Denied', 'partyminder') . '</h2>';
    echo '<p>' . __('You do not have permission to manage this community.', 'partyminder') . '</p>';
    echo '<a href="' . esc_url(PartyMinder::get_community_url($community->slug)) . '" class="pm-button pm-button-primary">' . __('View Community', 'partyminder') . '</a>';
    echo '</div>';
    return;
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Handle community settings update
    if ($_POST['action'] === 'update_community_settings' && wp_verify_nonce($_POST['nonce'], 'partyminder_community_management')) {
        $update_data = array(
            'description' => sanitize_textarea_field($_POST['description']),
            'privacy' => sanitize_text_field($_POST['privacy'])
        );
        
        $result = $community_manager->update_community($community_id, $update_data);
        
        if (!is_wp_error($result)) {
            $success_message = __('Community settings updated successfully.', 'partyminder');
            // Refresh community data
            $community = $community_manager->get_community($community_id);
        } else {
            $error_message = $result->get_error_message();
        }
    }
}
?>



<div class="pm-manage-community pm-container-wide" style="--pm-primary: <?php echo esc_attr($primary_color); ?>; --pm-secondary: <?php echo esc_attr($secondary_color); ?>;">
    <!-- Header -->
    <div class="pm-manage-header pm-mb-8">
        <nav class="pm-breadcrumb pm-mb-4">
            <a href="<?php echo esc_url(PartyMinder::get_dashboard_url()); ?>" class="pm-breadcrumb-link">🏠 <?php _e('Dashboard', 'partyminder'); ?></a>
            <span class="pm-breadcrumb-separator">→</span>
            <a href="<?php echo esc_url(PartyMinder::get_communities_url()); ?>" class="pm-breadcrumb-link"><?php _e('Communities', 'partyminder'); ?></a>
            <span class="pm-breadcrumb-separator">→</span>
            <a href="<?php echo esc_url(PartyMinder::get_community_url($community->slug)); ?>" class="pm-breadcrumb-link"><?php echo esc_html($community->name); ?></a>
            <span class="pm-breadcrumb-separator">→</span>
            <span class="pm-breadcrumb-current"><?php _e('Manage', 'partyminder'); ?></span>
        </nav>
        <h1 class="pm-heading pm-heading-lg pm-m-0">⚙️ <?php printf(__('Manage %s', 'partyminder'), esc_html($community->name)); ?></h1>
        <p class="pm-text-muted pm-m-0 pm-opacity-90"><?php _e('Manage settings, members, and invitations for your community', 'partyminder'); ?></p>
    </div>

    <div class="pm-dashboard-grid">
        <div class="pm-dashboard-main">
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
        <div class="pm-alert pm-alert-success">
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="pm-alert pm-alert-error">
            <?php echo esc_html($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="pm-management-tabs">
        <a href="?community_id=<?php echo $community_id; ?>&tab=overview" 
           class="pm-management-tab-btn <?php echo $current_tab === 'overview' ? 'active' : ''; ?>">
            <?php _e('Overview', 'partyminder'); ?>
        </a>
        <a href="?community_id=<?php echo $community_id; ?>&tab=settings" 
           class="pm-management-tab-btn <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
            <?php _e('Settings', 'partyminder'); ?>
        </a>
        <a href="?community_id=<?php echo $community_id; ?>&tab=members" 
           class="pm-management-tab-btn <?php echo $current_tab === 'members' ? 'active' : ''; ?>">
            <?php _e('Members', 'partyminder'); ?>
        </a>
        <a href="?community_id=<?php echo $community_id; ?>&tab=invitations" 
           class="pm-management-tab-btn <?php echo $current_tab === 'invitations' ? 'active' : ''; ?>">
            <?php _e('Invitations', 'partyminder'); ?>
        </a>
    </div>

    <!-- Tab Content -->
    <div class="pm-management-content">
        
        <!-- Overview Tab -->
        <div id="overview-tab" class="pm-tab-pane <?php echo $current_tab === 'overview' ? 'active' : ''; ?>">
            <h3><?php _e('Community Overview', 'partyminder'); ?></h3>
            
            <div class="pm-community-stats pm-mb-8" id="pm-community-stats">
                <div class="pm-stat-box">
                    <div class="pm-stat-number" id="total-members">-</div>
                    <div class="pm-stat-label"><?php _e('Total Members', 'partyminder'); ?></div>
                </div>
                <div class="pm-stat-box">
                    <div class="pm-stat-number" id="active-members">-</div>
                    <div class="pm-stat-label"><?php _e('Active Members', 'partyminder'); ?></div>
                </div>
                <div class="pm-stat-box">
                    <div class="pm-stat-number" id="pending-invites">-</div>
                    <div class="pm-stat-label"><?php _e('Pending Invites', 'partyminder'); ?></div>
                </div>
                <div class="pm-stat-box">

                    <div class="pm-stat-number" id="community-events">-</div>
                    <div class="pm-stat-label"><?php _e('Community Events', 'partyminder'); ?></div>
                </div>
            </div>

            <div class="pm-flex pm-flex-wrap pm-gap-md pm-mb-8">
                <a href="?community_id=<?php echo $community_id; ?>&tab=settings" class="pm-button pm-button-primary">
                    <span>⚙️</span> <?php _e('Edit Settings', 'partyminder'); ?>
                </a>
                <a href="?community_id=<?php echo $community_id; ?>&tab=members" class="pm-button pm-button-secondary">
                    <span>👥</span> <?php _e('Manage Members', 'partyminder'); ?>
                </a>
                <a href="?community_id=<?php echo $community_id; ?>&tab=invitations" class="pm-button pm-button-secondary">
                    <span>📧</span> <?php _e('Send Invitations', 'partyminder'); ?>
                </a>
                <a href="<?php echo esc_url(PartyMinder::get_community_url($community->slug)); ?>" class="pm-button pm-button-secondary">
                    <span>👁️</span> <?php _e('View Community', 'partyminder'); ?>
                </a>
            </div>

        </div>

        <!-- Settings Tab -->
        <div id="settings-tab" class="pm-tab-pane <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
            <h3><?php _e('Community Settings', 'partyminder'); ?></h3>
            
            <form method="post" class="pm-form">
                <input type="hidden" name="action" value="update_community_settings">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('partyminder_community_management'); ?>">
                
                <div class="pm-form-group">
                    <label class="pm-label">
                        <?php _e('Community Name', 'partyminder'); ?>
                    </label>
                    <input type="text" class="pm-input" value="<?php echo esc_attr($community->name); ?>" readonly>
                    <div class="pm-form-help">
                        <?php _e('Contact site administrator to change the community name', 'partyminder'); ?>
                    </div>
                </div>
                
                <div class="pm-form-group">
                    <label class="pm-label">
                        <?php _e('Description', 'partyminder'); ?>
                    </label>
                    <textarea name="description" class="pm-textarea" rows="4" 
                              placeholder="<?php _e('Update community description...', 'partyminder'); ?>"><?php echo esc_textarea($community->description); ?></textarea>
                </div>
                
                <div class="pm-form-group">
                    <label class="pm-label">
                        <?php _e('Privacy Setting', 'partyminder'); ?>
                    </label>
                    <select name="privacy" class="pm-select">
                        <option value="public" <?php selected($community->privacy, 'public'); ?>>
                            <?php _e('🌍 Public - Anyone can join', 'partyminder'); ?>
                        </option>
                        <option value="private" <?php selected($community->privacy, 'private'); ?>>
                            <?php _e('🔒 Private - Invite only', 'partyminder'); ?>
                        </option>
                    </select>
                </div>
                
                <button type="submit" class="pm-button pm-button-primary">
                    <?php _e('Save Changes', 'partyminder'); ?>
                </button>
            </form>
        </div>

        <!-- Members Tab -->
        <div id="members-tab" class="pm-tab-pane <?php echo $current_tab === 'members' ? 'active' : ''; ?>">
            <h3><?php _e('Community Members', 'partyminder'); ?></h3>
            <div id="members-list">
                <div class="pm-loading-placeholder">
                    <p><?php _e('Loading community members...', 'partyminder'); ?></p>
                </div>
            </div>
        </div>

        <!-- Invitations Tab -->
        <div id="invitations-tab" class="pm-tab-pane <?php echo $current_tab === 'invitations' ? 'active' : ''; ?>">
            <h3><?php _e('Send Invitations', 'partyminder'); ?></h3>
            
            <!-- Email Invitation Form -->
            <form id="send-invitation-form" class="pm-form">
                <div class="pm-form-group">
                    <label class="pm-label">
                        <?php _e('Email Address', 'partyminder'); ?>
                    </label>
                    <input type="email" class="pm-input" id="invitation-email" 
                           placeholder="<?php _e('Enter email address...', 'partyminder'); ?>" required>
                </div>
                
                <div class="pm-form-group">
                    <label class="pm-label">
                        <?php _e('Personal Message (Optional)', 'partyminder'); ?>
                    </label>
                    <textarea class="pm-textarea" id="invitation-message" rows="3"
                              placeholder="<?php _e('Add a personal message to your invitation...', 'partyminder'); ?>"></textarea>
                </div>
                
                <button type="submit" class="pm-button pm-button-primary">
                    <?php _e('Send Invitation', 'partyminder'); ?>
                </button>
            </form>

            <div class="pm-mt-6">
                <h4><?php _e('Pending Invitations', 'partyminder'); ?></h4>
                <div id="invitations-list">
                    <div class="pm-loading-placeholder">
                        <p><?php _e('Loading pending invitations...', 'partyminder'); ?></p>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- pm-management-content -->
        </div> <!-- pm-dashboard-main -->
        <div class="pm-dashboard-sidebar">
            <div class="pm-card">
                <div class="pm-card-header">
                    <h3 class="pm-heading pm-heading-sm pm-m-0">💡 <?php _e('Tips', 'partyminder'); ?></h3>
                </div>
                <div class="pm-card-body">
                    <p class="pm-text-muted pm-m-0"><?php _e('Use the tabs to edit settings, manage members, and send invitations.', 'partyminder'); ?></p>
                </div>
            </div>
        </div>
    </div> <!-- pm-dashboard-grid -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const communityId = <?php echo intval($community_id); ?>;
    const currentTab = '<?php echo esc_js($current_tab); ?>';
    
    // Load appropriate tab content based on current tab
    if (currentTab === 'overview') {
        loadCommunityStats(communityId);
    } else if (currentTab === 'members') {
        loadCommunityMembers(communityId);
    } else if (currentTab === 'invitations') {
        loadCommunityInvitations(communityId);
    }
    
    // Handle invitation form submission
    const invitationForm = document.getElementById('send-invitation-form');
    if (invitationForm) {
        invitationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('invitation-email').value;
            const message = document.getElementById('invitation-message').value;
            
            if (!email) {
                alert('<?php _e('Please enter an email address.', 'partyminder'); ?>');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = '<?php _e('Sending...', 'partyminder'); ?>';
            submitBtn.disabled = true;
            
            jQuery.ajax({
                url: partyminder_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'partyminder_send_invitation',
                    community_id: communityId,
                    email: email,
                    message: message,
                    nonce: partyminder_ajax.community_nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // Clear form
                        document.getElementById('invitation-email').value = '';
                        document.getElementById('invitation-message').value = '';
                        // Reload invitations list if we're on that tab
                        if (currentTab === 'invitations') {
                            loadCommunityInvitations(communityId);
                        }
                        // Update stats if we're on overview tab  
                        if (currentTab === 'overview') {
                            loadCommunityStats(communityId);
                        }
                    } else {
                        alert(response.data || '<?php _e('Failed to send invitation. Please try again.', 'partyminder'); ?>');
                    }
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                },
                error: function() {
                    alert('<?php _e('Network error. Please try again.', 'partyminder'); ?>');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            });
        });
    }
    
    // Load community statistics
    function loadCommunityStats(communityId) {
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_community_stats',
                community_id: communityId,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    document.getElementById('total-members').textContent = data.total_members || 0;
                    document.getElementById('active-members').textContent = data.active_members || 0;
                    document.getElementById('pending-invites').textContent = data.pending_invites || 0;
                    document.getElementById('community-events').textContent = data.community_events || 0;
                }
            },
            error: function() {
                console.error('Failed to load community stats');
            }
        });
    }
    
    // Load community members
    function loadCommunityMembers(communityId) {
        const membersList = document.getElementById('members-list');
        if (!membersList) return;
        
        membersList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e('Loading community members...', 'partyminder'); ?></p></div>';
        
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
                    membersList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e('No members found.', 'partyminder'); ?></p></div>';
                }
            },
            error: function() {
                membersList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e('Error loading members.', 'partyminder'); ?></p></div>';
            }
        });
    }
    
    // Load community invitations
    function loadCommunityInvitations(communityId) {
        const invitationsList = document.getElementById('invitations-list');
        if (!invitationsList) return;
        
        invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e('Loading pending invitations...', 'partyminder'); ?></p></div>';
        
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
                    invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e('No pending invitations.', 'partyminder'); ?></p></div>';
                }
            },
            error: function() {
                invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e('Error loading invitations.', 'partyminder'); ?></p></div>';
            }
        });
    }
    
    // Render members list
    function renderMembersList(members) {
        const membersList = document.getElementById('members-list');
        
        if (!members || members.length === 0) {
            membersList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e('No members found.', 'partyminder'); ?></p></div>';
            return;
        }
        
        let html = '<div class="pm-member-list">';
        members.forEach(member => {
            const initials = member.display_name ? member.display_name.substring(0, 2).toUpperCase() : 'U';
            const joinedDate = new Date(member.joined_at).toLocaleDateString();
            
            html += `
                <div class="pm-member-item" data-member-id="${member.id}">
                    <div class="pm-member-info">
                        <div class="pm-member-avatar">${initials}</div>
                        <div class="pm-member-details">
                            <h4>${member.display_name || member.email}</h4>
                            <small><?php _e('Member since', 'partyminder'); ?> ${joinedDate}</small>
                        </div>
                    </div>
                    <div class="pm-member-actions">
                        <span class="pm-member-role ${member.role}">${member.role}</span>
                        ${member.role === 'member' ? 
                            '<button class="pm-button pm-button-secondary pm-promote-btn" data-member-id="' + member.id + '"><?php _e('Promote', 'partyminder'); ?></button>' : 
                            (member.role === 'admin' ? '<button class="pm-button pm-button-secondary pm-demote-btn" data-member-id="' + member.id + '"><?php _e('Demote', 'partyminder'); ?></button>' : '')
                        }
                        <button class="pm-button pm-button-danger pm-remove-btn" data-member-id="${member.id}" data-member-name="${member.display_name || member.email}">
                            <?php _e('Remove', 'partyminder'); ?>
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        membersList.innerHTML = html;
        
        // Add event listeners for member actions
        attachMemberActionListeners();
    }
    
    // Render invitations list
    function renderInvitationsList(invitations) {
        const invitationsList = document.getElementById('invitations-list');
        
        if (!invitations || invitations.length === 0) {
            invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e('No pending invitations.', 'partyminder'); ?></p></div>';
            return;
        }
        
        let html = '<div class="pm-invitation-list">';
        invitations.forEach(invitation => {
            const createdDate = new Date(invitation.created_at).toLocaleDateString();
            const expiresDate = new Date(invitation.expires_at).toLocaleDateString();
            
            html += `
                <div class="pm-invitation-item" data-invitation-id="${invitation.id}">
                    <div class="pm-invitation-info">
                        <div class="pm-invitation-avatar">📧</div>
                        <div class="pm-invitation-details">
                            <h4>${invitation.invited_email}</h4>
                            <small><?php _e('Invited on', 'partyminder'); ?> ${createdDate}</small>
                            <br><small><?php _e('Expires', 'partyminder'); ?> ${expiresDate}</small>
                            ${invitation.message ? '<br><small><em>"' + invitation.message + '"</em></small>' : ''}
                        </div>
                    </div>
                    <div class="pm-invitation-actions">
                        <span class="pm-member-role pm-pending"><?php _e('pending', 'partyminder'); ?></span>
                        <button class="pm-button pm-button-danger pm-cancel-invitation-btn" data-invitation-id="${invitation.id}" data-email="${invitation.invited_email}">
                            <?php _e('Cancel', 'partyminder'); ?>
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        invitationsList.innerHTML = html;
        
        // Add event listeners for invitation actions
        attachInvitationActionListeners();
    }
    
    // Attach event listeners for member actions
    function attachMemberActionListeners() {
        // Promote buttons
        document.querySelectorAll('.pm-promote-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-member-id');
                updateMemberRole(memberId, 'admin');
            });
        });
        
        // Demote buttons
        document.querySelectorAll('.pm-demote-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-member-id');
                updateMemberRole(memberId, 'member');
            });
        });
        
        // Remove buttons
        document.querySelectorAll('.pm-remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-member-id');
                const memberName = this.getAttribute('data-member-name');
                
                if (confirm('<?php _e('Are you sure you want to remove', 'partyminder'); ?> "' + memberName + '" <?php _e('from this community?', 'partyminder'); ?>')) {
                    removeMember(memberId);
                }
            });
        });
    }
    
    // Attach event listeners for invitation actions
    function attachInvitationActionListeners() {
        // Cancel invitation buttons
        document.querySelectorAll('.pm-cancel-invitation-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const invitationId = this.getAttribute('data-invitation-id');
                const email = this.getAttribute('data-email');
                
                if (confirm('<?php _e('Are you sure you want to cancel the invitation to', 'partyminder'); ?> "' + email + '"?')) {
                    cancelInvitation(invitationId);
                }
            });
        });
    }
    
    // Update member role
    function updateMemberRole(memberId, newRole) {
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_update_member_role',
                community_id: communityId,
                member_id: memberId,
                new_role: newRole,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload members list
                    loadCommunityMembers(communityId);
                } else {
                    alert(response.data || '<?php _e('Failed to update member role.', 'partyminder'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Network error. Please try again.', 'partyminder'); ?>');
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
                community_id: communityId,
                member_id: memberId,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload members list
                    loadCommunityMembers(communityId);
                    // Update stats if we're on overview tab
                    if (currentTab === 'overview') {
                        loadCommunityStats(communityId);
                    }
                } else {
                    alert(response.data || '<?php _e('Failed to remove member.', 'partyminder'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Network error. Please try again.', 'partyminder'); ?>');
            }
        });
    }
    
    // Cancel invitation
    function cancelInvitation(invitationId) {
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_cancel_invitation',
                community_id: communityId,
                invitation_id: invitationId,
                nonce: partyminder_ajax.community_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload invitations list
                    loadCommunityInvitations(communityId);
                    // Update stats if we're on overview tab
                    if (currentTab === 'overview') {
                        loadCommunityStats(communityId);
                    }
                } else {
                    alert(response.data || '<?php _e('Failed to cancel invitation.', 'partyminder'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Network error. Please try again.', 'partyminder'); ?>');
            }
        });
    }
});
</script>
