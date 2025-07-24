<?php
/**
 * Event Management Modal Template
 * Frontend modal for event hosts to manage their events and invitations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>

<!-- Event Management Modal -->
<div id="event-management-modal" class="pm-modal-overlay">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h3 class="pm-modal-title">🎉 <span id="modal-event-title">Manage Event</span></h3>
            <button type="button" class="event-management-modal-close pm-button pm-button-secondary" style="padding: 5px; border-radius: 50%; width: 35px; height: 35px;" aria-label="<?php _e('Close', 'partyminder'); ?>">×</button>
        </div>
        
        <div class="pm-modal-body">
            <div class="pm-flex pm-flex-center-gap" style="background: var(--pm-surface); border-bottom: 1px solid var(--pm-border);">
                <button class="management-tab-btn pm-button pm-button-secondary active" data-tab="overview" style="flex: 1; border-radius: 0; border-bottom: 3px solid var(--pm-primary);">
                    <?php _e('Overview', 'partyminder'); ?>
                </button>
                <button class="management-tab-btn pm-button pm-button-secondary" data-tab="guests" style="flex: 1; border-radius: 0;">
                    <?php _e('Guest List', 'partyminder'); ?>
                </button>
                <button class="management-tab-btn pm-button pm-button-secondary" data-tab="invitations" style="flex: 1; border-radius: 0;">
                    <?php _e('Invitations', 'partyminder'); ?>
                </button>
            </div>
            
            <div style="padding: 30px; min-height: 300px;">
                <!-- Overview Tab -->
                <div id="overview-tab" class="management-tab-pane active">
                    <h4 class="pm-heading pm-heading-md pm-mb-4"><?php _e('Event Overview', 'partyminder'); ?></h4>
                    
                    <div class="pm-grid pm-grid-3 pm-mb-6">
                        <div class="pm-card">
                            <div class="pm-card-body pm-stat">
                                <div class="pm-stat-number pm-text-primary" id="total-rsvps">0</div>
                                <div class="pm-stat-label"><?php _e('Total RSVPs', 'partyminder'); ?></div>
                            </div>
                        </div>
                        <div class="pm-card">
                            <div class="pm-card-body pm-stat">
                                <div class="pm-stat-number pm-text-success" id="attending-count">0</div>
                                <div class="pm-stat-label"><?php _e('Attending', 'partyminder'); ?></div>
                            </div>
                        </div>
                        <div class="pm-card">
                            <div class="pm-card-body pm-stat">
                                <div class="pm-stat-number pm-text-warning" id="pending-invites">0</div>
                                <div class="pm-stat-label"><?php _e('Pending Invites', 'partyminder'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pm-flex pm-flex-center-gap" style="margin-bottom: 30px; flex-wrap: wrap;">
                        <a href="#" class="pm-button pm-button-primary" id="edit-event-btn">
                            <span>✏️</span> <?php _e('Edit Event Details', 'partyminder'); ?>
                        </a>
                        <button class="pm-button pm-button-secondary" onclick="switchToTab('invitations')">
                            <span>📧</span> <?php _e('Send Invitations', 'partyminder'); ?>
                        </button>
                        <button class="pm-button pm-button-secondary" onclick="shareEvent()">
                            <span>📤</span> <?php _e('Share Event', 'partyminder'); ?>
                        </button>
                    </div>
                    
                    <div class="pm-card" style="border-color: var(--pm-danger);">
                        <div class="pm-card-header">
                            <h4 class="pm-heading pm-heading-sm pm-text-danger pm-m-0"><?php _e('Danger Zone', 'partyminder'); ?></h4>
                        </div>
                        <div class="pm-card-body">
                            <p class="pm-text-muted pm-mb-4">
                                <?php _e('Once you delete an event, there is no going back. All RSVPs, invitations, and related data will be permanently deleted.', 'partyminder'); ?>
                            </p>
                            <button class="pm-button pm-button-danger" id="delete-event-btn">
                                <span>🗑️</span> <?php _e('Delete Event', 'partyminder'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Guest List Tab -->
                <div id="guests-tab" class="management-tab-pane" style="display: none;">
                    <h4 class="pm-heading pm-heading-md pm-mb-4"><?php _e('Event Guest List', 'partyminder'); ?></h4>
                    <div id="guests-list">
                        <div class="pm-text-center pm-p-6 pm-placeholder">
                            <p class="pm-text-muted"><?php _e('Loading guest list...', 'partyminder'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Invitations Tab -->
                <div id="invitations-tab" class="management-tab-pane" style="display: none;">
                    <h4 class="pm-heading pm-heading-md pm-mb-4"><?php _e('Send Event Invitations', 'partyminder'); ?></h4>
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
                            <textarea class="pm-input pm-textarea" id="invitation-message" rows="3"
                                      placeholder="<?php _e('Add a personal message to your invitation...', 'partyminder'); ?>"></textarea>
                        </div>
                        
                        <button type="submit" class="pm-button pm-button-primary">
                            <?php _e('Send Invitation', 'partyminder'); ?>
                        </button>
                    </form>
                    
                    <div class="pm-mt-6">
                        <h4 class="pm-heading pm-heading-md pm-mb-4"><?php _e('Pending Invitations', 'partyminder'); ?></h4>
                        <div id="invitations-list">
                            <div class="pm-text-center pm-p-6 pm-placeholder">
                                <p class="pm-text-muted"><?php _e('Loading pending invitations...', 'partyminder'); ?></p>
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
    
    // Ensure modal starts hidden
    if (modal) {
        modal.classList.remove('active');
    }
    
    // Show modal function
    function showEventManagementModal(eventData) {
        if (!eventData || !eventData.id) {
            console.error('No event data provided to modal');
            return;
        }
        
        // Store event data globally for form submissions
        window.currentEventData = eventData;
        
        // Update modal title with event name
        const titleElement = document.getElementById('modal-event-title');
        if (titleElement) {
            titleElement.textContent = eventData.title || 'Manage Event';
        }
        
        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Load overview stats
        if (eventData.id) {
            loadEventStats(eventData.id);
        }
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
    
    // Click outside modal to close
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
        guestsList.innerHTML = '<div class="pm-placeholder pm-text-center pm-p-6"><p><?php _e('Loading guest list...', 'partyminder'); ?></p></div>';
        
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
                    guestsList.innerHTML = '<div class="pm-placeholder pm-text-center pm-p-6"><p><?php _e('No guests yet.', 'partyminder'); ?></p></div>';
                }
            },
            error: function() {
                guestsList.innerHTML = '<div class="pm-placeholder pm-text-center pm-p-6"><p>' + partyminder_ajax.strings.error + '</p></div>';
            }
        });
    }
    
    // Load event invitations
    function loadEventInvitations(eventId) {
        const invitationsList = document.getElementById('invitations-list');
        invitationsList.innerHTML = '<div class="pm-placeholder pm-text-center pm-p-6"><p><?php _e('Loading pending invitations...', 'partyminder'); ?></p></div>';
        
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
                    invitationsList.innerHTML = '<div class="pm-placeholder pm-text-center pm-p-6"><p><?php _e('No pending invitations.', 'partyminder'); ?></p></div>';
                }
            },
            error: function() {
                invitationsList.innerHTML = '<div class="pm-placeholder pm-text-center pm-p-6"><p>' + partyminder_ajax.strings.error + '</p></div>';
            }
        });
    }
    
    // Render guests list
    function renderGuestsList(guests) {
        const guestsList = document.getElementById('guests-list');
        
        if (!guests || guests.length === 0) {
            guestsList.innerHTML = '<div class="pm-placeholder pm-text-center pm-p-6"><p><?php _e('No guests yet.', 'partyminder'); ?></p></div>';
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
            invitationsList.innerHTML = '<div class="pm-placeholder pm-text-center pm-p-6"><p><?php _e('No pending invitations.', 'partyminder'); ?></p></div>';
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