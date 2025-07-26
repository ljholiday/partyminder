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
                    
                    <?php if (PartyMinder_Feature_Flags::is_at_protocol_enabled()): ?>
                    <!-- Bluesky Connection Status -->
                    <div id="bluesky-connection-section" class="pm-mb-6">
                        <div id="bluesky-not-connected" class="pm-card pm-card-info" style="border-left: 4px solid #1d9bf0;">
                            <div class="pm-card-body">
                                <h5 class="pm-heading pm-heading-sm pm-mb-2">
                                    🦋 <?php _e('Connect Bluesky for Easy Invites', 'partyminder'); ?>
                                </h5>
                                <p class="pm-text-muted pm-mb-4">
                                    <?php _e('Connect your Bluesky account to invite your contacts directly from your follows list.', 'partyminder'); ?>
                                </p>
                                <button type="button" class="pm-button pm-button-secondary" id="connect-bluesky-btn">
                                    <?php _e('Connect Bluesky Account', 'partyminder'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div id="bluesky-connected" class="pm-card pm-card-success" style="border-left: 4px solid #10b981; display: none;">
                            <div class="pm-card-body">
                                <h5 class="pm-heading pm-heading-sm pm-mb-2">
                                    ✅ <?php _e('Bluesky Connected', 'partyminder'); ?>
                                </h5>
                                <p class="pm-text-muted pm-mb-4">
                                    <?php _e('Connected as', 'partyminder'); ?> <strong id="bluesky-handle"></strong>
                                </p>
                                <div class="pm-flex pm-flex-center-gap">
                                    <button type="button" class="pm-button pm-button-primary" id="load-bluesky-contacts-btn">
                                        <?php _e('Load Bluesky Contacts', 'partyminder'); ?>
                                    </button>
                                    <button type="button" class="pm-button pm-button-danger pm-button-sm" id="disconnect-bluesky-btn">
                                        <?php _e('Disconnect', 'partyminder'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bluesky Contacts Selection -->
                    <div id="bluesky-contacts-section" class="pm-mb-6" style="display: none;">
                        <h5 class="pm-heading pm-heading-sm pm-mb-4"><?php _e('Select from Bluesky Contacts', 'partyminder'); ?></h5>
                        <div id="bluesky-contacts-search" class="pm-mb-4">
                            <input type="text" class="pm-input" id="contacts-search" 
                                   placeholder="<?php _e('Search your contacts...', 'partyminder'); ?>">
                        </div>
                        <div id="bluesky-contacts-list" class="bluesky-contacts-grid">
                            <!-- Contacts will be loaded here -->
                        </div>
                    </div>
                    <?php endif; ?>
                    
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

<style>
/* Bluesky Integration Styles */
.bluesky-contacts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
    border: 1px solid var(--pm-border);
    border-radius: 8px;
    background: var(--pm-surface);
}

.bluesky-contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--pm-border);
    border-radius: 8px;
    background: white;
    transition: all 0.2s ease;
}

.bluesky-contact-item:hover {
    border-color: var(--pm-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.contact-avatar {
    flex-shrink: 0;
}

.contact-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.contact-info {
    flex: 1;
    min-width: 0;
}

.contact-name {
    font-weight: 600;
    color: var(--pm-text);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-handle {
    font-size: 14px;
    color: var(--pm-text-muted);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-description {
    font-size: 12px;
    color: var(--pm-text-muted);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.invite-contact-btn {
    flex-shrink: 0;
    padding: 6px 12px !important;
    font-size: 13px !important;
}

.pm-button-success {
    background-color: #10b981 !important;
    border-color: #10b981 !important;
    color: white !important;
}

.pm-button-success:hover {
    background-color: #059669 !important;
    border-color: #059669 !important;
}

.pm-card-info {
    background-color: #f0f9ff;
    border-color: #0ea5e9;
}

.pm-card-success {
    background-color: #f0fdf4;
    border-color: #22c55e;
}

#bluesky-contacts-search input {
    width: 100%;
    margin-bottom: 0;
}

/* Modal overlay styles */
.pm-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.pm-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.pm-modal-sm {
    max-width: 400px;
    width: 90%;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .bluesky-contacts-grid {
        grid-template-columns: 1fr;
    }
    
    .bluesky-contact-item {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .contact-info {
        text-align: center;
    }
    
    .contact-name,
    .contact-handle {
        white-space: normal;
        overflow: visible;
        text-overflow: unset;
    }
}
</style>

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
        tabBtns.forEach(b => {
            b.classList.remove('active');
            b.style.borderBottom = '';
        });
        const activeBtn = modal.querySelector(`[data-tab="${targetTab}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.style.borderBottom = '3px solid var(--pm-primary)';
        }
        
        // Update active tab pane - remove both display none and active class, then add active
        tabPanes.forEach(pane => {
            pane.classList.remove('active');
            pane.style.display = 'none';
        });
        const activePane = modal.querySelector('#' + targetTab + '-tab');
        if (activePane) {
            activePane.style.display = 'block';
            activePane.classList.add('active');
        }
        
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
    
    // Bluesky Integration Functions
    let blueSkyContacts = [];
    let isBlueskyConnected = false;
    
    // Check Bluesky connection status on modal open
    function checkBlueskyConnection() {
        if (!document.getElementById('bluesky-connection-section')) return;
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_check_bluesky_connection',
                nonce: partyminder_ajax.at_protocol_nonce
            },
            success: function(response) {
                if (response.success && response.data.connected) {
                    showBlueskyConnected(response.data.handle);
                } else {
                    showBlueskyNotConnected();
                }
            },
            error: function() {
                showBlueskyNotConnected();
            }
        });
    }
    
    function showBlueskyConnected(handle) {
        isBlueskyConnected = true;
        document.getElementById('bluesky-not-connected').style.display = 'none';
        document.getElementById('bluesky-connected').style.display = 'block';
        document.getElementById('bluesky-handle').textContent = handle;
    }
    
    function showBlueskyNotConnected() {
        isBlueskyConnected = false;
        document.getElementById('bluesky-not-connected').style.display = 'block';
        document.getElementById('bluesky-connected').style.display = 'none';
        document.getElementById('bluesky-contacts-section').style.display = 'none';
    }
    
    // Connect Bluesky modal
    function showBlueskyConnectModal() {
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
                        showBlueskyConnected(handle);
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
    
    // Load Bluesky contacts
    function loadBlueskyContacts() {
        const contactsList = document.getElementById('bluesky-contacts-list');
        const contactsSection = document.getElementById('bluesky-contacts-section');
        const loadBtn = document.getElementById('load-bluesky-contacts-btn');
        
        loadBtn.disabled = true;
        loadBtn.textContent = '<?php _e('Loading...', 'partyminder'); ?>';
        
        contactsList.innerHTML = '<div class="pm-text-center pm-p-4"><p><?php _e('Loading your Bluesky contacts...', 'partyminder'); ?></p></div>';
        contactsSection.style.display = 'block';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'partyminder_get_bluesky_contacts',
                nonce: partyminder_ajax.at_protocol_nonce
            },
            success: function(response) {
                console.log('Bluesky contacts response:', response);
                if (response && response.success && response.contacts) {
                    blueSkyContacts = response.contacts;
                    renderBlueskyContacts(blueSkyContacts);
                } else {
                    contactsList.innerHTML = '<div class="pm-text-center pm-p-4"><p class="pm-text-muted">' + (response.message || '<?php _e('No contacts found.', 'partyminder'); ?>') + '</p></div>';
                }
                loadBtn.disabled = false;
                loadBtn.textContent = '<?php _e('Refresh Contacts', 'partyminder'); ?>';
            },
            error: function(xhr, status, error) {
                console.log('Bluesky contacts error:', xhr.responseText);
                contactsList.innerHTML = '<div class="pm-text-center pm-p-4"><p class="pm-text-danger"><?php _e('Failed to load contacts.', 'partyminder'); ?></p></div>';
                loadBtn.disabled = false;
                loadBtn.textContent = '<?php _e('Load Bluesky Contacts', 'partyminder'); ?>';
            }
        });
    }
    
    // Render Bluesky contacts
    function renderBlueskyContacts(contacts) {
        const contactsList = document.getElementById('bluesky-contacts-list');
        
        if (!contacts || contacts.length === 0) {
            contactsList.innerHTML = '<div class="pm-text-center pm-p-4"><p class="pm-text-muted"><?php _e('No contacts found.', 'partyminder'); ?></p></div>';
            return;
        }
        
        let html = '';
        contacts.forEach(contact => {
            const avatar = contact.avatar || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNEMUQ1REIiLz4KPHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDEwQzEyLjIwOTEgMTAgMTQgOC4yMDkxNCAxNCA2QzE0IDMuNzkwODYgMTIuMjA5MSAyIDEwIDJDNy43OTA4NiAyIDYgMy43OTA4NiA2IDZDNCA4LjIwOTE0IDcuNzkwODYgMTAgMTAgMTBaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTAgMTJDNi42ODYyOSAxMiA0IDE0LjY4NjMgNCAxOEg0VjE4SDE2VjE4QzE2IDE0LjY4NjMgMTMuMzEzNyAxMiAxMCAxMloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPgo8L3N2Zz4K';
            
            html += `
                <div class="bluesky-contact-item" data-handle="${contact.handle}" data-display-name="${contact.display_name}">
                    <div class="contact-avatar">
                        <img src="${avatar}" alt="${contact.display_name}" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNEMUQ1REIiLz4KPHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDEwQzEyLjIwOTEgMTAgMTQgOC4yMDkxNCAxNCA2QzE0IDMuNzkwODYgMTIuMjA5MSAyIDEwIDJDNy43OTA4NiAyIDYgMy43OTA4NiA2IDZDNCA4LjIwOTE0IDcuNzkwODYgMTAgMTAgMTBaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTAgMTJDNi42ODYyOSAxMiA0IDE0LjY4NjMgNCAx4gNDE4SDRWMThIMTZMMThDMTYgMTQuNjg2MyAxMy4zMTM3IDEyIDEwIDEyWiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cjwvc3ZnPgo='">
                    </div>
                    <div class="contact-info">
                        <div class="contact-name">${contact.display_name}</div>
                        <div class="contact-handle">@${contact.handle}</div>
                        ${contact.description ? '<div class="contact-description">' + contact.description.substring(0, 60) + (contact.description.length > 60 ? '...' : '') + '</div>' : ''}
                    </div>
                    <button type="button" class="pm-button pm-button-sm pm-button-primary invite-contact-btn">
                        <?php _e('Invite', 'partyminder'); ?>
                    </button>
                </div>
            `;
        });
        
        contactsList.innerHTML = html;
        
        // Add click handlers for invite buttons
        contactsList.querySelectorAll('.invite-contact-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const contactItem = this.closest('.bluesky-contact-item');
                const handle = contactItem.dataset.handle;
                const displayName = contactItem.dataset.displayName;
                inviteBlueskyContact(handle, displayName, this);
            });
        });
        
        // Add search functionality
        const searchInput = document.getElementById('contacts-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const filteredContacts = contacts.filter(contact => 
                    contact.display_name.toLowerCase().includes(query) || 
                    contact.handle.toLowerCase().includes(query) ||
                    (contact.description && contact.description.toLowerCase().includes(query))
                );
                renderBlueskyContacts(filteredContacts);
            });
        }
    }
    
    // Invite Bluesky contact
    function inviteBlueskyContact(handle, displayName, btnElement) {
        const originalText = btnElement.textContent;
        btnElement.disabled = true;
        btnElement.textContent = '<?php _e('Inviting...', 'partyminder'); ?>';
        
        // For now, we'll use the handle as a pseudo-email until we implement proper AT Protocol invitations
        const pseudoEmail = handle + '@bsky.social';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_send_event_invitation',
                event_id: window.currentEventData.id,
                email: pseudoEmail,
                name: displayName,
                source: 'bluesky',
                bluesky_handle: handle,
                message: '<?php _e('Invitation sent via Bluesky connection', 'partyminder'); ?>',
                nonce: partyminder_ajax.event_nonce
            },
            success: function(response) {
                if (response.success) {
                    btnElement.textContent = '<?php _e('Invited!', 'partyminder'); ?>';
                    btnElement.classList.remove('pm-button-primary');
                    btnElement.classList.add('pm-button-success');
                    
                    // Update stats and invitations list
                    loadEventStats(window.currentEventData.id);
                    loadEventInvitations(window.currentEventData.id);
                    
                    setTimeout(() => {
                        btnElement.disabled = false;
                        btnElement.textContent = originalText;
                        btnElement.classList.remove('pm-button-success');
                        btnElement.classList.add('pm-button-primary');
                    }, 3000);
                } else {
                    alert(response.data || '<?php _e('Failed to send invitation', 'partyminder'); ?>');
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
    
    // Event handlers for Bluesky features
    if (document.getElementById('connect-bluesky-btn')) {
        document.getElementById('connect-bluesky-btn').addEventListener('click', showBlueskyConnectModal);
    }
    
    if (document.getElementById('load-bluesky-contacts-btn')) {
        document.getElementById('load-bluesky-contacts-btn').addEventListener('click', loadBlueskyContacts);
    }
    
    if (document.getElementById('disconnect-bluesky-btn')) {
        document.getElementById('disconnect-bluesky-btn').addEventListener('click', function() {
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
                            showBlueskyNotConnected();
                            alert('<?php _e('Bluesky account disconnected', 'partyminder'); ?>');
                        } else {
                            alert(response.message || '<?php _e('Failed to disconnect', 'partyminder'); ?>');
                        }
                    }
                });
            }
        });
    }
    
    // Override the original show function to include Bluesky check
    const originalShowFunction = showEventManagementModal;
    showEventManagementModal = function(eventData) {
        originalShowFunction(eventData);
        // Check Bluesky connection when modal opens
        setTimeout(checkBlueskyConnection, 100);
    };

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