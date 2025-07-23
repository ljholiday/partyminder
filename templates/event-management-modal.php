<?php
/**
 * Event Management Modal Template
 * Frontend modal for event hosts to manage their events and invitations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
.event-management-modal-overlay {
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

.event-management-modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.event-management-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 800px;
    width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideIn 0.3s ease;
}

.event-management-modal-header {
    background: linear-gradient(135deg, <?php echo esc_attr($primary_color); ?>, <?php echo esc_attr($secondary_color); ?>);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.event-management-modal-title {
    font-size: 1.3em;
    font-weight: bold;
    margin: 0;
    max-width: calc(100% - 50px);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

#modal-event-title {
    font-weight: normal;
}

.event-management-modal-close {
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

.event-management-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.event-management-modal-body {
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

.guest-list, .invitation-list {
    margin-top: 20px;
}

.guest-item, .invitation-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 10px;
}

.guest-info, .invitation-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.guest-avatar, .invitation-avatar {
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

.guest-details h4, .invitation-details h4 {
    margin: 0 0 5px 0;
    color: #333;
}

.guest-details small, .invitation-details small {
    color: #666;
}

.guest-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.guest-status.attending {
    background: #28a745;
    color: white;
}

.guest-status.pending {
    background: #ffc107;
    color: #000;
}

.guest-status.declined {
    background: #dc3545;
    color: white;
}

.placeholder-content {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.event-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
    color: <?php echo esc_attr($primary_color); ?>;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .event-management-modal {
        width: 98%;
        margin: 10px;
    }
    
    .event-management-modal-body {
        padding: 0;
    }
    
    .management-tab-content {
        padding: 20px;
    }
    
    .management-tabs {
        flex-direction: column;
    }
    
    .event-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<!-- Event Management Modal -->
<div id="event-management-modal" class="event-management-modal-overlay">
    <div class="event-management-modal">
        <div class="event-management-modal-header">
            <h3 class="event-management-modal-title">🎉 <span id="modal-event-title">Manage Event</span></h3>
            <button type="button" class="event-management-modal-close" aria-label="<?php _e('Close', 'partyminder'); ?>">×</button>
        </div>
        
        <div class="event-management-modal-body">
            <div class="management-tabs">
                <button class="management-tab-btn active" data-tab="overview">
                    <?php _e('Overview', 'partyminder'); ?>
                </button>
                <button class="management-tab-btn" data-tab="guests">
                    <?php _e('Guest List', 'partyminder'); ?>
                </button>
                <button class="management-tab-btn" data-tab="invitations">
                    <?php _e('Invitations', 'partyminder'); ?>
                </button>
            </div>
            
            <div class="management-tab-content">
                <!-- Overview Tab -->
                <div id="overview-tab" class="management-tab-pane active">
                    <h4><?php _e('Event Overview', 'partyminder'); ?></h4>
                    
                    <div class="event-stats">
                        <div class="stat-card">
                            <div class="stat-number" id="total-rsvps">0</div>
                            <div class="stat-label"><?php _e('Total RSVPs', 'partyminder'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="attending-count">0</div>
                            <div class="stat-label"><?php _e('Attending', 'partyminder'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="pending-invites">0</div>
                            <div class="stat-label"><?php _e('Pending Invites', 'partyminder'); ?></div>
                        </div>
                    </div>
                    
                    <div class="management-actions">
                        <a href="#" class="management-btn management-btn-primary" id="edit-event-btn">
                            <span>✏️</span> <?php _e('Edit Event Details', 'partyminder'); ?>
                        </a>
                        <button class="management-btn management-btn-secondary" onclick="switchToTab('invitations')">
                            <span>📧</span> <?php _e('Send Invitations', 'partyminder'); ?>
                        </button>
                        <button class="management-btn management-btn-secondary" onclick="shareEvent()">
                            <span>📤</span> <?php _e('Share Event', 'partyminder'); ?>
                        </button>
                    </div>
                    
                    <div class="danger-zone">
                        <h4><?php _e('Danger Zone', 'partyminder'); ?></h4>
                        <p>
                            <?php _e('Once you delete an event, there is no going back. All RSVPs, invitations, and related data will be permanently deleted.', 'partyminder'); ?>
                        </p>
                        <button class="management-btn danger" id="delete-event-btn">
                            <span>🗑️</span> <?php _e('Delete Event', 'partyminder'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Guest List Tab -->
                <div id="guests-tab" class="management-tab-pane">
                    <h4><?php _e('Event Guest List', 'partyminder'); ?></h4>
                    <div id="guests-list">
                        <div class="placeholder-content">
                            <p><?php _e('Loading guest list...', 'partyminder'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Invitations Tab -->
                <div id="invitations-tab" class="management-tab-pane">
                    <h4><?php _e('Send Event Invitations', 'partyminder'); ?></h4>
                    <form id="send-invitation-form">
                        <div class="management-form-group">
                            <label class="management-form-label">
                                <?php _e('Email Address', 'partyminder'); ?>
                            </label>
                            <input type="email" class="management-form-input" id="invitation-email" 
                                   placeholder="<?php _e('Enter email address...', 'partyminder'); ?>" required>
                        </div>
                        
                        <div class="management-form-group">
                            <label class="management-form-label">
                                <?php _e('Personal Message (Optional)', 'partyminder'); ?>
                            </label>
                            <textarea class="management-form-textarea" id="invitation-message" rows="3"
                                      placeholder="<?php _e('Add a personal message to your invitation...', 'partyminder'); ?>"></textarea>
                        </div>
                        
                        <button type="submit" class="management-btn management-btn-primary">
                            <?php _e('Send Invitation', 'partyminder'); ?>
                        </button>
                    </form>
                    
                    <div class="invitations-section">
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
    const modal = document.getElementById('event-management-modal');
    
    // Show modal function
    function showEventManagementModal(eventData) {
        // Store event data globally for form submissions
        window.currentEventData = eventData;
        
        // Update modal title with event name
        document.getElementById('modal-event-title').textContent = eventData.title;
        
        // Update edit event link - we'll get this from PHP since we need the proper URL
        // The edit button will be updated via AJAX when stats are loaded
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Load overview stats
        loadEventStats(eventData.id);
    }
    
    // Hide modal function
    function hideEventManagementModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Tab switching
    const tabBtns = modal.querySelectorAll('.management-tab-btn');
    const tabPanes = modal.querySelectorAll('.management-tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            switchToTab(targetTab);
        });
    });
    
    function switchToTab(targetTab) {
        // Update active tab button
        tabBtns.forEach(b => b.classList.remove('active'));
        modal.querySelector(`[data-tab="${targetTab}"]`).classList.add('active');
        
        // Update active tab pane
        tabPanes.forEach(pane => pane.classList.remove('active'));
        modal.querySelector('#' + targetTab + '-tab').classList.add('active');
        
        // Load tab-specific content
        if (targetTab === 'guests' && window.currentEventData) {
            loadEventGuests(window.currentEventData.id);
        } else if (targetTab === 'invitations' && window.currentEventData) {
            loadEventInvitations(window.currentEventData.id);
        }
    }
    
    // Close modal events
    modal.querySelector('.event-management-modal-close').addEventListener('click', hideEventManagementModal);
    
    // Delete event button
    modal.querySelector('#delete-event-btn').addEventListener('click', function() {
        if (!window.currentEventData) {
            alert('<?php _e('No event selected', 'partyminder'); ?>');
            return;
        }
        
        const eventTitle = window.currentEventData.title;
        const confirmMessage = '<?php _e('Are you sure you want to delete', 'partyminder'); ?> "' + eventTitle + '"?\n\n<?php _e('This action cannot be undone. All RSVPs, invitations, and related data will be permanently deleted.', 'partyminder'); ?>';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Show loading state
        const deleteBtn = this;
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<span>⏳</span> <?php _e('Deleting...', 'partyminder'); ?>';
        deleteBtn.disabled = true;
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_delete_event',
                event_id: window.currentEventData.id,
                nonce: partyminder_ajax.event_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Close modal
                    hideEventManagementModal();
                    // Redirect to my events page
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert(response.data || partyminder_ajax.strings.error);
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                }
            },
            error: function() {
                alert(partyminder_ajax.strings.error);
                deleteBtn.innerHTML = originalText;
                deleteBtn.disabled = false;
            }
        });
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideEventManagementModal();
        }
    });
    
    // Send invitation form handler
    document.getElementById('send-invitation-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = document.getElementById('invitation-email').value;
        const message = document.getElementById('invitation-message').value;
        
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
                action: 'partyminder_send_event_invitation',
                event_id: window.currentEventData.id,
                email: email,
                message: message,
                nonce: partyminder_ajax.event_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Clear form
                    document.getElementById('invitation-email').value = '';
                    document.getElementById('invitation-message').value = '';
                    // Reload invitations list
                    loadEventInvitations(window.currentEventData.id);
                    // Update stats
                    loadEventStats(window.currentEventData.id);
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
    
    // Load event statistics
    function loadEventStats(eventId) {
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_event_stats',
                event_id: eventId,
                nonce: partyminder_ajax.event_nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    document.getElementById('total-rsvps').textContent = data.total_rsvps || 0;
                    document.getElementById('attending-count').textContent = data.attending_count || 0;
                    document.getElementById('pending-invites').textContent = data.pending_invites || 0;
                    // Update edit event link
                    if (data.edit_url) {
                        document.getElementById('edit-event-btn').href = data.edit_url;
                    }
                }
            }
        });
    }
    
    // Load event guests
    function loadEventGuests(eventId) {
        const guestsList = document.getElementById('guests-list');
        guestsList.innerHTML = '<div class="placeholder-content"><p><?php _e('Loading guest list...', 'partyminder'); ?></p></div>';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_event_guests',
                event_id: eventId,
                nonce: partyminder_ajax.event_nonce
            },
            success: function(response) {
                if (response.success && response.data.guests) {
                    renderGuestsList(response.data.guests);
                } else {
                    guestsList.innerHTML = '<div class="placeholder-content"><p><?php _e('No guests yet.', 'partyminder'); ?></p></div>';
                }
            },
            error: function() {
                guestsList.innerHTML = '<div class="placeholder-content"><p>' + partyminder_ajax.strings.error + '</p></div>';
            }
        });
    }
    
    // Load event invitations
    function loadEventInvitations(eventId) {
        const invitationsList = document.getElementById('invitations-list');
        invitationsList.innerHTML = '<div class="placeholder-content"><p><?php _e('Loading pending invitations...', 'partyminder'); ?></p></div>';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_event_invitations',
                event_id: eventId,
                nonce: partyminder_ajax.event_nonce
            },
            success: function(response) {
                if (response.success && response.data.invitations) {
                    renderInvitationsList(response.data.invitations);
                } else {
                    invitationsList.innerHTML = '<div class="placeholder-content"><p><?php _e('No pending invitations.', 'partyminder'); ?></p></div>';
                }
            },
            error: function() {
                invitationsList.innerHTML = '<div class="placeholder-content"><p>' + partyminder_ajax.strings.error + '</p></div>';
            }
        });
    }
    
    // Render guests list
    function renderGuestsList(guests) {
        const guestsList = document.getElementById('guests-list');
        
        if (!guests || guests.length === 0) {
            guestsList.innerHTML = '<div class="placeholder-content"><p><?php _e('No guests yet.', 'partyminder'); ?></p></div>';
            return;
        }
        
        let html = '';
        guests.forEach(guest => {
            const initials = guest.name ? guest.name.substring(0, 2).toUpperCase() : 'G';
            const rsvpDate = new Date(guest.rsvp_date).toLocaleDateString();
            
            html += `
                <div class="guest-item">
                    <div class="guest-info">
                        <div class="guest-avatar">${initials}</div>
                        <div class="guest-details">
                            <h4>${guest.name}</h4>
                            <small><?php _e('RSVP\'d on', 'partyminder'); ?> ${rsvpDate}</small>
                            ${guest.plus_one ? '<br><small>👥 ' + (guest.plus_one_name || '<?php _e('Plus one', 'partyminder'); ?>') + '</small>' : ''}
                        </div>
                    </div>
                    <span class="guest-status ${guest.status}">${guest.status}</span>
                </div>
            `;
        });
        
        guestsList.innerHTML = html;
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
            const sentDate = new Date(invitation.created_at).toLocaleDateString();
            const expiresDate = new Date(invitation.expires_at).toLocaleDateString();
            
            html += `
                <div class="invitation-item">
                    <div class="invitation-info">
                        <div class="invitation-avatar">📧</div>
                        <div class="invitation-details">
                            <h4>${invitation.invited_email}</h4>
                            <small><?php _e('Sent on', 'partyminder'); ?> ${sentDate}</small>
                            <br><small><?php _e('Expires', 'partyminder'); ?> ${expiresDate}</small>
                        </div>
                    </div>
                    <button class="management-btn management-btn-secondary cancel-invitation-btn" 
                            data-invitation-id="${invitation.id}" 
                            data-email="${invitation.invited_email}">
                        <?php _e('Cancel', 'partyminder'); ?>
                    </button>
                </div>
            `;
        });
        
        invitationsList.innerHTML = html;
        
        // Add cancel invitation listeners
        document.querySelectorAll('.cancel-invitation-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const invitationId = this.getAttribute('data-invitation-id');
                const email = this.getAttribute('data-email');
                
                if (confirm('<?php _e('Are you sure you want to cancel the invitation to', 'partyminder'); ?> "' + email + '"?')) {
                    cancelEventInvitation(invitationId);
                }
            });
        });
    }
    
    // Cancel invitation
    function cancelEventInvitation(invitationId) {
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_cancel_event_invitation',
                event_id: window.currentEventData.id,
                invitation_id: invitationId,
                nonce: partyminder_ajax.event_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload invitations list
                    loadEventInvitations(window.currentEventData.id);
                    // Update stats
                    loadEventStats(window.currentEventData.id);
                } else {
                    alert(response.data || partyminder_ajax.strings.error);
                }
            },
            error: function() {
                alert(partyminder_ajax.strings.error);
            }
        });
    }
    
    // Share event function
    function shareEvent() {
        if (window.currentEventData) {
            const eventUrl = window.location.origin + '/events/' + window.currentEventData.slug;
            
            if (navigator.share) {
                navigator.share({
                    title: window.currentEventData.title,
                    text: 'Check out this event!',
                    url: eventUrl
                });
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(eventUrl).then(() => {
                    alert('<?php _e('Event link copied to clipboard!', 'partyminder'); ?>');
                });
            } else {
                alert('<?php _e('Event URL:', 'partyminder'); ?> ' + eventUrl);
            }
        }
    }
    
    // Make functions available globally
    window.showEventManagementModal = showEventManagementModal;
    window.switchToTab = switchToTab;
    window.shareEvent = shareEvent;
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            hideEventManagementModal();
        }
    });
});
</script>