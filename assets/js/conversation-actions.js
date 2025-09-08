/**
 * PartyMinder Conversation Actions
 * Handles follow/unfollow, delete reply, and delete conversation functionality
 */
(function($) {
    'use strict';

    const ConversationActions = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Handle follow/unfollow
            $(document).on('click', '.follow-btn', this.handleFollowToggle.bind(this));
            
            // Handle delete reply button clicks
            $(document).on('click', '.delete-reply-btn', this.handleDeleteReply.bind(this));
            
            // Handle delete conversation button clicks
            $(document).on('click', '.delete-conversation-btn', this.handleDeleteConversation.bind(this));
        },

        handleFollowToggle: function(e) {
            const $btn = $(e.currentTarget);
            const conversationId = $btn.data('conversation-id');
            const isFollowing = $btn.text().includes('Unfollow');
            
            $.ajax({
                url: partyminder_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: isFollowing ? 'partyminder_unfollow_conversation' : 'partyminder_follow_conversation',
                    conversation_id: conversationId,
                    nonce: partyminder_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (isFollowing) {
                            $btn.html('Follow');
                        } else {
                            $btn.html('Unfollow');
                        }
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                }
            });
        },

        handleDeleteReply: function(e) {
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            const replyId = $btn.data('reply-id');
            const conversationId = $btn.data('conversation-id');
            
            if (!confirm('Are you sure you want to delete this reply? This action cannot be undone.')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: partyminder_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'partyminder_delete_reply',
                    reply_id: replyId,
                    conversation_id: conversationId,
                    nonce: partyminder_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Failed to delete reply.');
                        $btn.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text('Delete');
                }
            });
        },

        handleDeleteConversation: function(e) {
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            const conversationId = $btn.data('conversation-id');
            
            if (!confirm('Are you sure you want to delete this entire conversation? This will delete all replies and cannot be undone.')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: partyminder_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'partyminder_delete_conversation',
                    conversation_id: conversationId,
                    nonce: partyminder_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = partyminder_ajax.conversations_url || '/conversations/';
                    } else {
                        alert(response.data || 'Failed to delete conversation.');
                        $btn.prop('disabled', false).text('Delete Conversation');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text('Delete Conversation');
                }
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        ConversationActions.init();
    });

    // Expose to global scope
    window.PartyMinderConversationActions = ConversationActions;

})(jQuery);