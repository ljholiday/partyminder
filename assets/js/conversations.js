/**
 * PartyMinder Conversations JavaScript
 */

(function($) {
    'use strict';

    // Initialize Conversations functionality
    window.PartyMinderConversations = window.PartyMinderConversations || {};

    $(document).ready(function() {
        PartyMinderConversations.init();
    });

    /**
     * Initialize Conversations functionality
     */
    PartyMinderConversations.init = function() {
        this.initModalEvents();
        this.initFormValidation();
        this.initConversationActions();
    };

    /**
     * Initialize modal events
     */
    PartyMinderConversations.initModalEvents = function() {
        // Open conversation modal
        $(document).on('click', '.start-conversation-btn', function(e) {
            e.preventDefault();
            const topicId = $(this).data('topic-id') || '';
            const topicName = $(this).data('topic-name') || '';
            PartyMinderConversations.openConversationModal(topicId, topicName);
        });

        // Close modal events
        $(document).on('click', '.pm-modal-overlay, .close-modal', function(e) {
            if (e.target === this || $(this).hasClass('close-modal')) {
                PartyMinderConversations.closeModal();
            }
        });

        // Escape key closes modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                PartyMinderConversations.closeModal();
            }
        });

        // Prevent modal content clicks from closing modal
        $(document).on('click', '.pm-modal', function(e) {
            e.stopPropagation();
        });
    };

    /**
     * Initialize form validation
     */
    PartyMinderConversations.initFormValidation = function() {
        // Real-time validation
        $(document).on('input blur', '.pm-form input[required], .pm-form textarea[required]', function() {
            PartyMinderConversations.validateField($(this));
        });

        // Form submission
        $(document).on('submit', '.pm-form', function(e) {
            e.preventDefault();
            PartyMinderConversations.submitConversation($(this));
        });

        $(document).on('submit', '.reply-form', function(e) {
            e.preventDefault();
            PartyMinderConversations.submitReply($(this));
        });
    };

    /**
     * Initialize conversation actions
     */
    PartyMinderConversations.initConversationActions = function() {
        // Reply button
        $(document).on('click', '.reply-btn', function(e) {
            e.preventDefault();
            const conversationId = $(this).data('conversation-id');
            const parentReplyId = $(this).data('parent-reply-id') || null;
            PartyMinderConversations.showReplyForm(conversationId, parentReplyId, $(this));
        });

        // Follow/Unfollow
        $(document).on('click', '.follow-btn', function(e) {
            e.preventDefault();
            // TODO: Implement follow functionality
        });
    };

    /**
     * Open event conversation modal
     */
    PartyMinderConversations.openEventConversationModal = function(eventId, eventTitle) {
        const currentUser = partyminder_ajax.current_user || {};
        const isLoggedIn = currentUser.id > 0;

        const modalHtml = `
            <div class="pm-modal-overlay" id="conversation-modal">
                <div class="pm-modal">
                    <div class="pm-modal-header">
                        <div>
                            <h3 class="pm-modal-title">💬 Create Event Conversation</h3>
                            <p class="text-muted pm-m-0">for <strong>${eventTitle}</strong></p>
                        </div>
                        <button class="close-modal btn btn-secondary btn-small" type="button">&times;</button>
                    </div>
                    <div class="pm-modal-body">
                        <form class="pm-form" method="post">
                            <input type="hidden" name="nonce" value="${partyminder_ajax.nonce}">
                            <input type="hidden" name="action" value="partyminder_create_conversation">
                            <input type="hidden" name="event_id" value="${eventId}">
                            
                            ${!isLoggedIn ? `
                                <div class="pm-form-group">
                                    <label for="guest_name" class="pm-label">Your Name *</label>
                                    <input type="text" id="guest_name" name="guest_name" class="pm-input" required>
                                </div>
                                <div class="pm-form-group">
                                    <label for="guest_email" class="pm-label">Your Email *</label>
                                    <input type="email" id="guest_email" name="guest_email" class="pm-input" required>
                                </div>
                            ` : ''}
                            
                            <div class="pm-form-group">
                                <label for="conversation_title" class="pm-label">Conversation Title *</label>
                                <input type="text" id="conversation_title" name="title" class="pm-input" required maxlength="255" 
                                       placeholder="What aspect of this event would you like to discuss?">
                            </div>
                            
                            <div class="pm-form-group">
                                <label for="conversation_content" class="pm-label">Your Message *</label>
                                <textarea id="conversation_content" name="content" class="pm-textarea" required rows="6" 
                                          placeholder="Share ideas, ask questions, or coordinate details for this event..."></textarea>
                            </div>
                            
                            <div class="pm-modal-footer">
                                <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <span class="button-text">Create Conversation</span>
                                    <span class="button-spinner pm-hidden">Creating...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $('#conversation-modal').addClass('active');
        
        // Focus appropriate field
        if (!isLoggedIn) {
            $('#guest_name').focus();
        } else {
            $('#conversation_title').focus();
        }
    };

    /**
     * Open conversation modal
     */
    PartyMinderConversations.openConversationModal = function(topicId, topicName) {
        const currentUser = partyminder_ajax.current_user || {};
        const isLoggedIn = currentUser.id > 0;
        
        // Get available topics for dropdown if no specific topic
        const topicsHtml = PartyMinderConversations.getTopicsDropdown(topicId);

        const modalHtml = `
            <div class="pm-modal-overlay" id="conversation-modal">
                <div class="pm-modal">
                    <div class="pm-modal-header">
                        <div>
                            <h3 class="pm-modal-title">💬 Start New Conversation</h3>
                            ${topicName ? `<p class="text-muted pm-m-0">in <strong>${topicName}</strong></p>` : ''}
                        </div>
                        <button class="close-modal btn btn-secondary btn-small" type="button">&times;</button>
                    </div>
                    <div class="pm-modal-body">
                        <form class="pm-form" method="post">
                            <input type="hidden" name="nonce" value="${partyminder_ajax.nonce}">
                            <input type="hidden" name="action" value="partyminder_create_conversation">
                            
                            ${!isLoggedIn ? `
                                <div class="pm-form-group">
                                    <label for="guest_name" class="pm-label">Your Name *</label>
                                    <input type="text" id="guest_name" name="guest_name" class="pm-input" required>
                                </div>
                                <div class="pm-form-group">
                                    <label for="guest_email" class="pm-label">Your Email *</label>
                                    <input type="email" id="guest_email" name="guest_email" class="pm-input" required>
                                </div>
                            ` : ''}
                            
                            ${!topicId ? `
                                <div class="pm-form-group">
                                    <label for="topic_select" class="pm-label">Topic *</label>
                                    <select id="topic_select" name="topic_id" class="pm-select pm-input" required>
                                        <option value="">Choose a topic...</option>
                                        ${topicsHtml}
                                    </select>
                                </div>
                            ` : `<input type="hidden" name="topic_id" value="${topicId}">`}
                            
                            <div class="pm-form-group">
                                <label for="conversation_title" class="pm-label">Conversation Title *</label>
                                <input type="text" id="conversation_title" name="title" class="pm-input" required maxlength="255" 
                                       placeholder="What would you like to discuss?">
                            </div>
                            
                            <div class="pm-form-group">
                                <label for="conversation_content" class="pm-label">Your Message *</label>
                                <textarea id="conversation_content" name="content" class="pm-textarea" required rows="8" 
                                          placeholder="Share your thoughts, ask a question, or start a discussion..."></textarea>
                            </div>
                            
                            <div class="pm-modal-footer">
                                <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <span class="button-text">Start Conversation</span>
                                    <span class="button-spinner pm-hidden">Starting...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $('#conversation-modal').addClass('active');
        
        // Focus appropriate field
        if (!isLoggedIn) {
            $('#guest_name').focus();
        } else if (!topicId) {
            $('#topic_select').focus();
        } else {
            $('#conversation_title').focus();
        }
    };
    
    /**
     * Get topics dropdown HTML
     */
    PartyMinderConversations.getTopicsDropdown = function(selectedTopicId) {
        // This would ideally come from the server, but for now we'll use the topics visible on the page
        const topics = [];
        $('.topic-section').each(function() {
            const $header = $(this).find('.topic-header');
            const $btn = $(this).find('.start-conversation-btn');
            if ($btn.length) {
                const topicId = $btn.data('topic-id');
                const topicName = $btn.data('topic-name');
                const topicIcon = $header.find('.topic-icon').text();
                if (topicId && topicName) {
                    topics.push({
                        id: topicId,
                        name: topicName,
                        icon: topicIcon
                    });
                }
            }
        });
        
        return topics.map(topic => 
            `<option value="${topic.id}" ${selectedTopicId == topic.id ? 'selected' : ''}>
                ${topic.icon} ${topic.name}
            </option>`
        ).join('');
    };

    /**
     * Close modal
     */
    PartyMinderConversations.closeModal = function() {
        $('.pm-modal-overlay').removeClass('active');
        setTimeout(() => {
            $('.pm-modal-overlay').remove();
        }, 300);
    };

    /**
     * Validate form field
     */
    PartyMinderConversations.validateField = function($field) {
        const value = $field.val().trim();
        const fieldName = $field.attr('name');
        
        PartyMinderConversations.clearFieldError($field);

        if ($field.prop('required') && !value) {
            PartyMinderConversations.showFieldError($field, 'This field is required.');
            return false;
        }

        if ($field.attr('type') === 'email' && value && !PartyMinderConversations.isValidEmail(value)) {
            PartyMinderConversations.showFieldError($field, 'Please enter a valid email address.');
            return false;
        }

        if (fieldName === 'title' && value.length > 255) {
            PartyMinderConversations.showFieldError($field, 'Title must be 255 characters or less.');
            return false;
        }

        return true;
    };

    /**
     * Show field error
     */
    PartyMinderConversations.showFieldError = function($field, message) {
        $field.addClass('pm-input-error');
        $field.siblings('.pm-field-error').remove();
        $field.after(`<div class="pm-field-error pm-text-danger pm-text-sm pm-mt-1">${message}</div>`);
    };

    /**
     * Clear field error
     */
    PartyMinderConversations.clearFieldError = function($field) {
        $field.removeClass('pm-input-error');
        $field.siblings('.pm-field-error').remove();
    };

    /**
     * Validate email
     */
    PartyMinderConversations.isValidEmail = function(email) {
        const emailRegex = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;
        return emailRegex.test(email);
    };

    /**
     * Submit conversation
     */
    PartyMinderConversations.submitConversation = function($form) {
        // Validate form
        let isValid = true;
        $form.find('input[required], textarea[required]').each(function() {
            if (!PartyMinderConversations.validateField($(this))) {
                isValid = false;
            }
        });

        if (!isValid) {
            return;
        }

        const $submitBtn = $form.find('button[type="submit"]');
        const $buttonText = $submitBtn.find('.button-text');
        const $buttonSpinner = $submitBtn.find('.button-spinner');

        // Show loading state
        $submitBtn.prop('disabled', true);
        $buttonText.hide();
        $buttonSpinner.show();

        // Submit via AJAX
        $.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    PartyMinderConversations.showNotification(response.data.message, 'success');
                    PartyMinderConversations.closeModal();
                    
                    // If redirect URL provided (event conversation), redirect there
                    if (response.data.redirect_url) {
                        setTimeout(() => {
                            window.location.href = response.data.redirect_url;
                        }, 1000);
                    } else {
                        // Otherwise refresh the page to show new conversation
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    PartyMinderConversations.showNotification(response.data || 'Failed to create conversation.', 'error');
                }
            },
            error: function() {
                PartyMinderConversations.showNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                // Reset button state
                $submitBtn.prop('disabled', false);
                $buttonText.show();
                $buttonSpinner.hide();
            }
        });
    };

    /**
     * Submit reply
     */
    PartyMinderConversations.submitReply = function($form) {
        // Validate form
        let isValid = true;
        $form.find('input[required], textarea[required]').each(function() {
            if (!PartyMinderConversations.validateField($(this))) {
                isValid = false;
            }
        });

        if (!isValid) {
            return;
        }

        const $submitBtn = $form.find('button[type="submit"]');
        const $buttonText = $submitBtn.find('.button-text');
        const $buttonSpinner = $submitBtn.find('.button-spinner');

        // Show loading state
        $submitBtn.prop('disabled', true);
        if ($buttonText.length && $buttonSpinner.length) {
            $buttonText.hide();
            $buttonSpinner.show();
        } else {
            $submitBtn.text('Posting...');
        }

        // Submit via AJAX
        $.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    PartyMinderConversations.showNotification(response.data.message, 'success');
                    // Refresh the page to show new reply
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    PartyMinderConversations.showNotification(response.data || 'Failed to add reply.', 'error');
                }
            },
            error: function() {
                PartyMinderConversations.showNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                // Reset button state
                $submitBtn.prop('disabled', false);
                if ($buttonText.length && $buttonSpinner.length) {
                    $buttonText.show();
                    $buttonSpinner.hide();
                } else {
                    $submitBtn.text('Post Reply');
                }
            }
        });
    };

    /**
     * Show reply form
     */
    PartyMinderConversations.showReplyForm = function(conversationId, parentReplyId, $trigger) {
        // Set the parent reply ID in the main reply form
        const $replyForm = $('.reply-form');
        const $parentInput = $replyForm.find('input[name="parent_reply_id"]');
        
        if (parentReplyId) {
            $parentInput.val(parentReplyId);
            
            // Update form heading to show it's a reply to a specific comment
            const $formTitle = $replyForm.closest('.reply-form-section').find('h3');
            $formTitle.text('Reply to Comment');
            
            // Scroll to form
            $('html, body').animate({
                scrollTop: $('#reply-form').offset().top - 100
            }, 500);
            
            // Focus the content textarea
            $replyForm.find('textarea[name="content"]').focus();
        } else {
            // Reset to main reply
            $parentInput.val('');
            const $formTitle = $replyForm.closest('.reply-form-section').find('h3');
            $formTitle.text('Add Your Reply');
        }
    };

    /**
     * Show notification
     */
    PartyMinderConversations.showNotification = function(message, type) {
        type = type || 'info';
        
        const $notification = $(`<div class="partyminder-notification notification-${type}">${message}</div>`)
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                background: type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#3b82f6'),
                color: 'white',
                padding: '15px 20px',
                borderRadius: '6px',
                boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
                zIndex: 10000,
                maxWidth: '350px'
            });

        $('body').append($notification);

        // Auto-remove after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);

        // Click to dismiss
        $notification.on('click', function() {
            $(this).fadeOut(300, function() {
                $(this).remove();
            });
        });
    };

})(jQuery);