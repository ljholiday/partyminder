<?php
/**
 * Single Event Content Template - Content Only
 * For theme integration via the_content filter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get event data from global variable set by main plugin
$event = $GLOBALS['partyminder_current_event'] ?? null;

if (!$event) {
    echo '<div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; margin: 20px;">';
    echo '<h3>Event Not Found</h3>';
    echo '<p>No event data available</p>';
    echo '</div>';
    return;
}



$event_date = new DateTime($event->event_date);
$is_today = $event_date->format('Y-m-d') === date('Y-m-d');
$is_tomorrow = $event_date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
$is_past = $event_date < new DateTime();

// Load conversation manager and get event conversations  
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
$conversation_manager = new PartyMinder_Conversation_Manager();
$event_conversations = $conversation_manager->get_event_conversations($event->id);
?>


<div class="pm-page">
    <div class="pm-card">
        <div class="pm-card-header">
            <h1 class="pm-title-primary "><?php echo esc_html($event->title); ?></h1>
            
            <?php if ($is_past): ?>
                <div class="badge badge-secondary">
                    📅 Past Event
                </div>
            <?php elseif ($is_today): ?>
                <div class="pm-badge pm-badge-success">
                    🎉 Today!
                </div>
            <?php elseif ($is_tomorrow): ?>
                <div class="pm-badge">
                    ⏰ Tomorrow
                </div>
            <?php endif; ?>
        </div>
        
        <div class="pm-card-body">
            <div class="grid grid-4 mb-4">
                <div class="pm-flex">
                    <span>📅</span>
                    <span>
                        <?php if ($is_today): ?>
                            <?php _e('Today', 'partyminder'); ?>
                        <?php elseif ($is_tomorrow): ?>
                            <?php _e('Tomorrow', 'partyminder'); ?>
                        <?php else: ?>
                            <?php echo $event_date->format('l, F j, Y'); ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="pm-flex">
                    <span>🕐</span>
                    <span><?php echo $event_date->format('g:i A'); ?></span>
                </div>
                
                <?php if ($event->venue_info): ?>
                <div class="pm-flex">
                    <span>📍</span>
                    <span><?php echo esc_html($event->venue_info); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="pm-flex">
                    <span>👥</span>
                    <span>
                        <?php echo $event->guest_stats->confirmed ?? 0; ?> confirmed
                        <?php if ($event->guest_limit > 0): ?>
                            of <?php echo $event->guest_limit; ?> max
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($event->featured_image): ?>
    <div class="card mb-4">
        <img src="<?php echo esc_url($event->featured_image); ?>" alt="<?php echo esc_attr($event->title); ?>" class="" style="height: auto; border-radius: var(--pm-radius);">
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <?php if ($event->description): ?>
            <div class="pm-card-header">
                <h3 class="pm-title-secondary ">About This Event</h3>
            </div>
            <div class="pm-card-body">
                <?php echo wpautop($event->description); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($event->host_notes): ?>
            <?php if ($event->description): ?>
                <div class="card-footer -top">
            <?php else: ?>
                <div class="pm-card-header">
                    <h3 class="pm-title-secondary ">Host Notes</h3>
                </div>
                <div class="pm-card-body">
            <?php endif; ?>
                <h4 class="pm-heading pm-heading-sm">Host Notes</h4>
                <?php echo wpautop($event->host_notes); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card mb-4">
        <div class="pm-card-header">
            <h3 class="pm-title-secondary ">Event Stats</h3>
        </div>
        <div class="pm-card-body">
            <div class="grid grid-4">
                <div class="pm-stat">
                    <div class="stat-number text-primary"><?php echo $event->guest_stats->confirmed ?? 0; ?></div>
                    <div class="pm-stat-label">Confirmed</div>
                </div>
                <div class="pm-stat">
                    <div class="stat-number text-primary"><?php echo $event->guest_stats->pending ?? 0; ?></div>
                    <div class="pm-stat-label">Pending</div>
                </div>
                <?php if (($event->guest_stats->maybe ?? 0) > 0): ?>
                <div class="pm-stat">
                    <div class="stat-number text-primary"><?php echo $event->guest_stats->maybe ?? 0; ?></div>
                    <div class="pm-stat-label">Maybe</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if (!$is_past): ?>
        <div class="card mb-4">
            <div class="pm-card-body">
                <?php 
                $is_full = $event->guest_limit > 0 && $event->guest_stats->confirmed >= $event->guest_limit;
                $current_user = wp_get_current_user();
                $is_event_host = (is_user_logged_in() && $current_user->ID == $event->author_id) || 
                                ($current_user->user_email == $event->host_email) ||
                                current_user_can('edit_others_posts');
                ?>
                
                <div class="pm-flex pm-gap" style="flex-wrap: wrap;">
                    <?php if ($is_event_host): ?>
                        <a href="<?php echo PartyMinder::get_edit_event_url($event->id); ?>" class="pm-btn">
                            <span>✏️</span>
                            <?php _e('Edit Details', 'partyminder'); ?>
                        </a>
                        
                        <button type="button" class="btn btn-danger" id="delete-event-btn" data-event-id="<?php echo esc_attr($event->id); ?>" data-event-title="<?php echo esc_attr($event->title); ?>">
                            <span>🗑️</span>
                            <?php _e('Delete Event', 'partyminder'); ?>
                        </button>
                    <?php else: ?>
                        <a href="#rsvp" class="pm-btn">
                            <?php if ($is_full): ?>
                                🎟️ Join Waitlist
                            <?php else: ?>
                                💌 RSVP Now
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                    <button type="button" class="pm-btn pm-btn-secondary" onclick="shareEvent()">
                        📤 Share Event
                    </button>
                    
                    <button type="button" class="pm-btn pm-btn-secondary" onclick="openEventConversationModal(<?php echo $event->id; ?>, '<?php echo esc_js($event->title); ?>')">
                        💬 Create Conversation
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!$is_past && $is_event_host): ?>
    <!-- Event Management Section -->
    <div class="card mb-4">
        <div class="pm-card-header">
            <h3 class="pm-title-secondary ">📧 Send Invitations</h3>
        </div>
        <div class="pm-card-body">
            <?php if (PartyMinder_Feature_Flags::is_at_protocol_enabled()): ?>
            <!-- Bluesky Connection Status -->
            <div id="bluesky-connection-section" class="pm-mb-4">
                <div id="bluesky-not-connected" class="card card-info" style="border-left: 4px solid #1d9bf0;">
                    <div class="pm-card-body">
                        <h5 class="heading heading-sm mb-4">
                            🦋 <?php _e('Connect Bluesky for Easy Invites', 'partyminder'); ?>
                        </h5>
                        <p class="pm-text-muted mb-4">
                            <?php _e('Connect your Bluesky account to invite your contacts directly from your follows list.', 'partyminder'); ?>
                        </p>
                        <button type="button" class="pm-btn pm-btn-secondary" id="connect-bluesky-btn">
                            <?php _e('Connect Bluesky Account', 'partyminder'); ?>
                        </button>
                    </div>
                </div>
                
                <div id="bluesky-connected" class="card card-success" style="border-left: 4px solid #10b981; display: none;">
                    <div class="pm-card-body">
                        <h5 class="heading heading-sm mb-4">
                            ✅ <?php _e('Bluesky Connected', 'partyminder'); ?>
                        </h5>
                        <p class="pm-text-muted mb-4">
                            <?php _e('Connected as', 'partyminder'); ?> <strong id="bluesky-handle"></strong>
                        </p>
                        <div class="pm-flex pm-gap">
                            <button type="button" class="pm-btn" id="load-bluesky-contacts-btn">
                                <?php _e('Load Bluesky Contacts', 'partyminder'); ?>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" id="disconnect-bluesky-btn">
                                <?php _e('Disconnect', 'partyminder'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bluesky Contacts Selection -->
            <div id="bluesky-contacts-section" class="pm-mb-4" style="display: none;">
                <h5 class="heading heading-sm mb-4"><?php _e('Select from Bluesky Contacts', 'partyminder'); ?></h5>
                <div id="bluesky-contacts-search" class="pm-mb-4">
                    <input type="text" class="pm-form-input" id="contacts-search" 
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
                    <label class="pm-form-label">
                        <?php _e('Email Address', 'partyminder'); ?>
                    </label>
                    <input type="email" class="pm-form-input" id="invitation-email" 
                           placeholder="<?php _e('Enter email address...', 'partyminder'); ?>" required>
                </div>
                
                <div class="pm-form-group">
                    <label class="pm-form-label">
                        <?php _e('Personal Message (Optional)', 'partyminder'); ?>
                    </label>
                    <textarea class="form-input form-textarea" id="invitation-message" rows="3"
                              placeholder="<?php _e('Add a personal message to your invitation...', 'partyminder'); ?>"></textarea>
                </div>
                
                <button type="submit" class="pm-btn">
                    <?php _e('Send Invitation', 'partyminder'); ?>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!$is_past && !$is_event_host): ?>
    <!-- RSVP Form Section -->
    <div class="card mb-4" id="rsvp">
        <div class="pm-card-header">
            <h3 class="pm-title-secondary ">RSVP for this Event</h3>
        </div>
        <div class="pm-card-body">
            <?php echo do_shortcode('[partyminder_rsvp_form event_id="' . $event->id . '"]'); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!$is_past && $is_event_host): ?>
    <!-- Invited Guests Section -->
    <div class="card mb-4">
        <div class="pm-card-header">
            <h3 class="pm-title-secondary ">👥 Invited Guests</h3>
        </div>
        <div class="pm-card-body">
            <div id="invited-guests-list">
                <div class="text-center p-4 pm-placeholder">
                    <p class="pm-text-muted"><?php _e('Loading guest list...', 'partyminder'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Event Conversations -->
    <div class="card mb-4" id="event-conversations-section">
        <div class="pm-card-header">
            <div class="pm-flex pm-flex-between">
                <h3 class="pm-title-secondary ">💬 Event Conversations</h3>
                <button type="button" class="btn btn-small" onclick="openEventConversationModal(<?php echo $event->id; ?>, '<?php echo esc_js($event->title); ?>')">
                    Create Conversation
                </button>
            </div>
        </div>
        <div class="pm-card-body">
            <?php if (!empty($event_conversations)): ?>
                <?php foreach ($event_conversations as $conversation): ?>
                    <div class="mb-4 pm-pb-3 <?php echo $conversation !== end($event_conversations) ? '-bottom' : ''; ?>">
                        <div class="pm-flex pm-flex-between pm-mb-4">
                            <h4 class="heading heading-sm ">
                                <a href="<?php echo home_url('/conversations/' . ($conversation->topic_slug ?? 'general') . '/' . $conversation->slug); ?>" class="text-primary ">
                                    <?php echo esc_html($conversation->title); ?>
                                </a>
                            </h4>
                            <div class="stat text-center">
                                <div class="stat-number text-primary "><?php echo $conversation->reply_count ?? 0; ?></div>
                                <div class="stat-label ">Replies</div>
                            </div>
                        </div>
                        <div class="pm-text-muted ">
                            <?php 
                            $content_preview = wp_trim_words(strip_tags($conversation->content), 20, '...');
                            echo esc_html($content_preview); 
                            ?>
                        </div>
                        <div class="pm-text-muted  mt-4">
                            <?php printf(__('by %s • %s ago', 'partyminder'), 
                                esc_html($conversation->author_name),
                                human_time_diff(strtotime($conversation->last_reply_date), current_time('timestamp'))
                            ); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center p-4">
                    <p class="pm-text-muted mb-4">💭 No conversations started yet for this event.</p>
                    <p class="pm-text-muted ">Be the first to start planning and discussing ideas!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Event Details -->
    <div class="card mb-4">
        <div class="pm-card-header">
            <h3 class="pm-title-secondary ">Event Details</h3>
        </div>
        <div class="pm-card-body">
            <div class="grid grid-3">
                <div>
                    <strong class="pm-text-primary">Host Email:</strong><br>
                    <span class="pm-text-muted"><?php echo esc_html($event->host_email); ?></span>
                </div>
                <div>
                    <strong class="pm-text-primary">Created:</strong><br>
                    <span class="pm-text-muted"><?php echo date('F j, Y', strtotime($event->created_at)); ?></span>
                </div>
                <?php if ($event->guest_limit > 0): ?>
                <div>
                    <strong class="pm-text-primary">Guest Limit:</strong><br>
                    <span class="pm-text-muted"><?php echo $event->guest_limit; ?> people</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Event host functionality now inline - no modal needed
$current_user = wp_get_current_user();
$is_event_host = (is_user_logged_in() && $current_user->ID == $event->author_id) || 
                ($current_user->user_email == $event->host_email) ||
                current_user_can('edit_others_posts');
?>

<?php
// Styles now properly located in assets/css/partyminder.css
?>

<script>
function shareEvent() {
    const url = window.location.href;
    const title = '<?php echo esc_js($event->title); ?>';
    
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        });
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            alert('Event URL copied to clipboard!');
        });
    } else {
        // Fallback: open social sharing
        window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title), '_blank');
    }
}

<?php if ($is_event_host): ?>
// Event management functionality
document.addEventListener('DOMContentLoaded', function() {
    const currentEventId = <?php echo $event->id; ?>;
    let blueSkyContacts = [];
    let isBlueskyConnected = false;
    
    // Initialize event management features
    initializeEventManagement();
    loadGuestList();
    
    // Check Bluesky connection on page load
    <?php if (PartyMinder_Feature_Flags::is_at_protocol_enabled()): ?>
    checkBlueskyConnection();
    <?php endif; ?>
    
    function initializeEventManagement() {
        // Handle invitation form submission
        const inviteForm = document.getElementById('send-invitation-form');
        if (inviteForm) {
            inviteForm.addEventListener('submit', handleInviteSubmission);
        }
        
        // Handle delete event button
        const deleteBtn = document.getElementById('delete-event-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', handleDeleteEvent);
        }
        
        // Handle Bluesky buttons
        const connectBtn = document.getElementById('connect-bluesky-btn');
        const loadContactsBtn = document.getElementById('load-bluesky-contacts-btn');
        const disconnectBtn = document.getElementById('disconnect-bluesky-btn');
        
        if (connectBtn) connectBtn.addEventListener('click', showBlueskyConnectModal);
        if (loadContactsBtn) loadContactsBtn.addEventListener('click', loadBlueskyContacts);
        if (disconnectBtn) disconnectBtn.addEventListener('click', disconnectBluesky);
        
        // Handle contacts search
        const searchInput = document.getElementById('contacts-search');
        if (searchInput) {
            searchInput.addEventListener('input', filterBlueskyContacts);
        }
    }
    
    function handleInviteSubmission(e) {
        e.preventDefault();
        
        const email = document.getElementById('invitation-email').value;
        const message = document.getElementById('invitation-message').value;
        const submitBtn = e.target.querySelector('button[type="submit"]');
        
        if (!email) return;
        
        submitBtn.disabled = true;
        submitBtn.textContent = '<?php _e('Sending...', 'partyminder'); ?>';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_send_event_invitation',
                event_id: currentEventId,
                email: email,
                message: message,
                nonce: partyminder_ajax.event_nonce
            },
            success: function(response) {
                if (response.success) {
                    document.getElementById('invitation-email').value = '';
                    document.getElementById('invitation-message').value = '';
                    loadGuestList(); // Refresh guest list
                    alert('<?php _e('Invitation sent successfully!', 'partyminder'); ?>');
                } else {
                    console.error('Failed to send invitation:', response.data);
                    alert('<?php _e('Failed to send invitation:', 'partyminder'); ?> ' + (response.data || '<?php _e('Unknown error', 'partyminder'); ?>'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error sending invitation:', error, xhr.responseText);
                alert('<?php _e('Network error. Please try again.', 'partyminder'); ?>');
            },
            complete: function() {
                submitBtn.disabled = false;
                submitBtn.textContent = '<?php _e('Send Invitation', 'partyminder'); ?>';
            }
        });
    }
    
    function handleDeleteEvent() {
        const deleteBtn = document.getElementById('delete-event-btn');
        const eventTitle = deleteBtn.getAttribute('data-event-title');
        
        // Show confirmation dialog
        const confirmMessage = '<?php _e('Are you sure you want to delete this event?', 'partyminder'); ?>\n\n' +
                              '<?php _e('Event:', 'partyminder'); ?> ' + eventTitle + '\n\n' +
                              '<?php _e('This action cannot be undone. All RSVPs, invitations, and related data will be permanently deleted.', 'partyminder'); ?>';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Disable button and show loading
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<span>⏳</span> <?php _e('Deleting...', 'partyminder'); ?>';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_delete_event',
                event_id: currentEventId,
                nonce: partyminder_ajax.event_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to events page
                    window.location.href = '<?php echo PartyMinder::get_events_page_url(); ?>';
                } else {
                    console.error('Failed to delete event:', response.data);
                    alert('<?php _e('Failed to delete event:', 'partyminder'); ?> ' + (response.data || '<?php _e('Unknown error', 'partyminder'); ?>'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error deleting event:', error, xhr.responseText);
                alert('<?php _e('Network error. Please try again.', 'partyminder'); ?>');
            },
            complete: function() {
                // Re-enable button
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '<span>🗑️</span> <?php _e('Delete Event', 'partyminder'); ?>';
            }
        });
    }
    
    function loadGuestList() {
        // Load both invitations (pending) and guests (RSVPed)
        const invitationsPromise = jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_event_invitations',
                event_id: currentEventId,
                nonce: partyminder_ajax.event_nonce
            }
        });
        
        const guestsPromise = jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_event_guests',
                event_id: currentEventId,
                nonce: partyminder_ajax.event_nonce
            }
        });
        
        jQuery.when(invitationsPromise, guestsPromise).done(function(invitationsResponse, guestsResponse) {
            const invitations = invitationsResponse[0].success ? invitationsResponse[0].data.invitations || [] : [];
            const guests = guestsResponse[0].success ? guestsResponse[0].data.guests || [] : [];
            
            console.log('Loaded invitations:', invitations);
            console.log('Loaded guests:', guests);
            
            renderGuestList(invitations, guests);
        }).fail(function(xhr, status, error) {
            console.error('AJAX error loading guest data:', error, xhr.responseText);
            const container = document.getElementById('invited-guests-list');
            if (container) {
                container.innerHTML = '<div class="text-center p-4"><p class="pm-text-muted"><?php _e('Error loading guest list.', 'partyminder'); ?></p></div>';
            }
        });
    }
    
    function renderGuestList(invitations, guests) {
        const container = document.getElementById('invited-guests-list');
        if (!container) return;
        
        console.log('Rendering guest list - invitations:', invitations, 'guests:', guests);
        
        // Combine invitations and guests into a single list
        const allEntries = [];
        
        // Add invitations (pending status)
        if (invitations && invitations.length > 0) {
            invitations.forEach(invitation => {
                allEntries.push({
                    name: invitation.invited_name || '',
                    email: invitation.invited_email || '',
                    status: invitation.status || 'pending',
                    type: 'invitation',
                    date: invitation.created_at || ''
                });
            });
        }
        
        // Add actual guests (RSVPed)
        if (guests && guests.length > 0) {
            guests.forEach(guest => {
                allEntries.push({
                    name: guest.name || '',
                    email: guest.email || '',
                    status: guest.status || 'confirmed',
                    type: 'rsvp',
                    date: guest.rsvp_date || ''
                });
            });
        }
        
        if (allEntries.length === 0) {
            container.innerHTML = '<div class="text-center p-4"><p class="pm-text-muted"><?php _e('No guests yet. Start sending invitations!', 'partyminder'); ?></p></div>';
            return;
        }
        
        // Sort by date (most recent first)
        allEntries.sort((a, b) => new Date(b.date) - new Date(a.date));
        
        const html = allEntries.map(entry => {
            const displayName = entry.name || entry.email || '<?php _e('Unknown Guest', 'partyminder'); ?>';
            const email = entry.email || '<?php _e('No email', 'partyminder'); ?>';
            const status = entry.status || 'pending';
            const typeLabel = entry.type === 'invitation' ? '<?php _e('(Invited)', 'partyminder'); ?>' : '';
            
            return `
                <div class="guest-item">
                    <div class="guest-info">
                        <h6>${displayName} ${typeLabel}</h6>
                        <p>${email}</p>
                    </div>
                    <div class="guest-status">
                        <span class="status-badge ${status}">${status}</span>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = html;
    }
    
    <?php if (PartyMinder_Feature_Flags::is_at_protocol_enabled()): ?>
    // Bluesky Integration Functions
    function checkBlueskyConnection() {
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
        const notConnected = document.getElementById('bluesky-not-connected');
        const connected = document.getElementById('bluesky-connected');
        const handleEl = document.getElementById('bluesky-handle');
        
        if (notConnected) notConnected.style.display = 'none';
        if (connected) connected.style.display = 'block';
        if (handleEl) handleEl.textContent = handle;
    }
    
    function showBlueskyNotConnected() {
        isBlueskyConnected = false;
        const notConnected = document.getElementById('bluesky-not-connected');
        const connected = document.getElementById('bluesky-connected');
        const contactsSection = document.getElementById('bluesky-contacts-section');
        
        if (notConnected) notConnected.style.display = 'block';
        if (connected) connected.style.display = 'none';
        if (contactsSection) contactsSection.style.display = 'none';
    }
    
    function showBlueskyConnectModal() {
        const connectHtml = `
            <div id="bluesky-connect-modal" class="pm-modal-overlay" style="z-index: 10001;">
                <div class="pm-modal pm-modal-sm">
                    <div class="pm-modal-header">
                        <h3>🦋 <?php _e('Connect to Bluesky', 'partyminder'); ?></h3>
                        <button type="button" class="bluesky-connect-close btn btn-secondary" style="padding: 5px; border-radius: 50%; width: 35px; height: 35px;">×</button>
                    </div>
                    <div class="pm-modal-body">
                        <form id="bluesky-connect-form">
                            <div class="pm-form-group">
                                <label class="pm-form-label"><?php _e('Bluesky Handle', 'partyminder'); ?></label>
                                <input type="text" class="pm-form-input" id="bluesky-handle-input" 
                                       placeholder="<?php _e('username.bsky.social', 'partyminder'); ?>" required>
                            </div>
                            <div class="pm-form-group">
                                <label class="pm-form-label"><?php _e('App Password', 'partyminder'); ?></label>
                                <input type="password" class="pm-form-input" id="bluesky-password-input" 
                                       placeholder="<?php _e('Your Bluesky app password', 'partyminder'); ?>" required>
                                <small class="pm-text-muted">
                                    <?php _e('Create an app password in your Bluesky settings for secure access.', 'partyminder'); ?>
                                </small>
                            </div>
                            <div class="flex gap-4 mt-4">
                                <button type="submit" class="pm-btn">
                                    <?php _e('Connect Account', 'partyminder'); ?>
                                </button>
                                <button type="button" class="bluesky-connect-close btn btn-secondary">
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
                data: {
                    action: 'partyminder_connect_bluesky',
                    handle: handle,
                    password: password,
                    nonce: partyminder_ajax.at_protocol_nonce
                },
                success: function(response) {
                    if (response.success) {
                        showBlueskyConnected(response.data.handle);
                        connectModal.remove();
                    } else {
                        alert(response.data || '<?php _e('Connection failed. Please check your credentials.', 'partyminder'); ?>');
                    }
                },
                error: function() {
                    alert('<?php _e('Connection failed. Please try again.', 'partyminder'); ?>');
                },
                complete: function() {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '<?php _e('Connect Account', 'partyminder'); ?>';
                }
            });
        });
    }
    
    function loadBlueskyContacts() {
        const btn = document.getElementById('load-bluesky-contacts-btn');
        const contactsSection = document.getElementById('bluesky-contacts-section');
        
        if (!btn || !contactsSection) return;
        
        btn.disabled = true;
        btn.textContent = '<?php _e('Loading...', 'partyminder'); ?>';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_get_bluesky_contacts',
                nonce: partyminder_ajax.at_protocol_nonce
            },
            success: function(response) {
                if (response.success) {
                    blueSkyContacts = response.data.contacts || [];
                    renderBlueskyContacts(blueSkyContacts);
                    contactsSection.style.display = 'block';
                } else {
                    alert('<?php _e('Failed to load contacts. Please try again.', 'partyminder'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Failed to load contacts. Please try again.', 'partyminder'); ?>');
            },
            complete: function() {
                btn.disabled = false;
                btn.textContent = '<?php _e('Load Bluesky Contacts', 'partyminder'); ?>';
            }
        });
    }
    
    function renderBlueskyContacts(contacts) {
        const container = document.getElementById('bluesky-contacts-list');
        if (!container) return;
        
        if (!contacts || contacts.length === 0) {
            container.innerHTML = '<div class="text-center p-4"><p class="pm-text-muted"><?php _e('No contacts found.', 'partyminder'); ?></p></div>';
            return;
        }
        
        const html = contacts.map(contact => `
            <div class="bluesky-contact-card" data-contact='${JSON.stringify(contact)}'>
                <div class="bluesky-contact-info">
                    <img src="${contact.avatar || ''}" alt="${contact.displayName || contact.handle}" class="bluesky-contact-avatar" 
                         onerror="this.src='data:image/svg+xml,<svg xmlns=\\"http://www.w3.org/2000/svg\\" viewBox=\\"0 0 40 40\\"><circle cx=\\"20\\" cy=\\"20\\" r=\\"20\\" fill=\\"#ddd\\"/><text x=\\"20\\" y=\\"26\\" text-anchor=\\"middle\\" fill=\\"white\\" font-size=\\"16\\">${(contact.displayName || contact.handle).charAt(0).toUpperCase()}</text></svg>'">
                    <div class="bluesky-contact-details">
                        <h6>${contact.displayName || contact.handle}</h6>
                        <p>@${contact.handle}</p>
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
        
        // Add click handlers
        container.querySelectorAll('.bluesky-contact-card').forEach(card => {
            card.addEventListener('click', function() {
                const contactData = JSON.parse(this.getAttribute('data-contact'));
                selectBlueskyContact(contactData);
            });
        });
    }
    
    function selectBlueskyContact(contact) {
        const emailInput = document.getElementById('invitation-email');
        if (emailInput && contact.email) {
            emailInput.value = contact.email;
        }
        
        // Visual feedback
        document.querySelectorAll('.bluesky-contact-card').forEach(card => {
            card.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
    }
    
    function filterBlueskyContacts() {
        const searchTerm = document.getElementById('contacts-search').value.toLowerCase();
        const filteredContacts = blueSkyContacts.filter(contact => 
            (contact.displayName && contact.displayName.toLowerCase().includes(searchTerm)) ||
            contact.handle.toLowerCase().includes(searchTerm)
        );
        renderBlueskyContacts(filteredContacts);
    }
    
    function disconnectBluesky() {
        if (!confirm('<?php _e('Are you sure you want to disconnect your Bluesky account?', 'partyminder'); ?>')) {
            return;
        }
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'partyminder_disconnect_bluesky',
                nonce: partyminder_ajax.at_protocol_nonce
            },
            success: function(response) {
                showBlueskyNotConnected();
                blueSkyContacts = [];
            }
        });
    }
    <?php endif; ?>
});
<?php endif; ?>

// Event Conversation Modal
function openEventConversationModal(eventId, eventTitle) {
    const currentUser = partyminder_ajax.current_user || {};
    const isLoggedIn = currentUser.id > 0;

    const modalHtml = `
        <div class="pm-modal-overlay" id="event-conversation-modal" style="z-index: 10001;">
            <div class="pm-modal pm-modal-sm">
                <div class="pm-modal-header">
                    <div>
                        <h3 class="pm-modal-title">💬 Create Event Conversation</h3>
                        <p class="pm-text-muted ">for <strong>${eventTitle}</strong></p>
                    </div>
                    <button type="button" class="close-modal btn btn-secondary btn-small">&times;</button>
                </div>
                <div class="pm-modal-body">
                    <form id="event-conversation-form" method="post">
                        <input type="hidden" name="nonce" value="${partyminder_ajax.nonce}">
                        <input type="hidden" name="action" value="partyminder_create_conversation">
                        <input type="hidden" name="event_id" value="${eventId}">
                        
                        ${!isLoggedIn ? `
                            <div class="pm-form-group">
                                <label for="guest_name" class="pm-form-label">Your Name *</label>
                                <input type="text" id="guest_name" name="guest_name" class="pm-form-input" required>
                            </div>
                            <div class="pm-form-group">
                                <label for="guest_email" class="pm-form-label">Your Email *</label>
                                <input type="email" id="guest_email" name="guest_email" class="pm-form-input" required>
                            </div>
                        ` : ''}
                        
                        <div class="pm-form-group">
                            <label for="conversation_title" class="pm-form-label">Conversation Title *</label>
                            <input type="text" id="conversation_title" name="title" class="pm-form-input" required maxlength="255" 
                                   placeholder="What aspect of this event would you like to discuss?">
                        </div>
                        
                        <div class="pm-form-group">
                            <label for="conversation_content" class="pm-form-label">Your Message *</label>
                            <textarea id="conversation_content" name="content" class="pm-form-textarea" required rows="6" 
                                      placeholder="Share ideas, ask questions, or coordinate details for this event..."></textarea>
                        </div>
                        
                        <div class="pm-modal-footer">
                            <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                            <button type="submit" class="pm-btn">
                                <span class="button-text">Create Conversation</span>
                                <span class="button-spinner ">Creating...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = document.getElementById('event-conversation-modal');
    modal.classList.add('active');
    
    // Focus appropriate field
    if (!isLoggedIn) {
        document.getElementById('guest_name').focus();
    } else {
        document.getElementById('conversation_title').focus();
    }
    
    // Close modal handlers
    modal.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            modal.remove();
        });
    });
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Handle form submission
    document.getElementById('event-conversation-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const buttonText = submitBtn.querySelector('.button-text');
        const buttonSpinner = submitBtn.querySelector('.button-spinner');
        
        submitBtn.disabled = true;
        buttonText.style.display = 'none';
        buttonSpinner.style.display = 'inline';
        
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: jQuery(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showNotification('Event conversation created successfully!', 'success');
                    // Simple page reload to show new conversation
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data || 'Failed to create conversation.', 'error');
                }
            },
            error: function() {
                showNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                submitBtn.disabled = false;
                buttonText.style.display = 'inline';
                buttonSpinner.style.display = 'none';
            }
        });
    });
}


// Simple notification function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `partyminder-notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 15px 20px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        max-width: 350px;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
    
    notification.addEventListener('click', () => {
        notification.remove();
    });
}
</script>