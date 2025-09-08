/**
 * PartyMinder Reply Modal System
 * Handles modal-based reply creation and editing with file uploads and draft saving
 */
(function($) {
    'use strict';

    if (typeof window.PartyMinderReplyModal === 'undefined') {
        window.PartyMinderReplyModal = {};
    }

    const ReplyModal = {
        currentConversationId: null,
        currentReplyId: null,
        isEditMode: false,
        draftAutoSaveInterval: null,
        fileUploads: [],
        uploadXhr: null,

        init: function() {
            this.bindEvents();
            this.initDraftAutoSave();
        },

        bindEvents: function() {
            // Reply button clicks
            $(document).on('click', '.pm-reply-btn', this.openReplyModal.bind(this));
            
            // Edit reply button clicks
            $(document).on('click', '.pm-edit-reply-btn', this.openEditModal.bind(this));
            
            // Modal controls
            $(document).on('click', '.pm-reply-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.pm-reply-modal .pm-modal-overlay', this.closeModal.bind(this));
            
            // Form submission
            $(document).on('submit', '.pm-reply-form', this.handleSubmit.bind(this));
            
            // Cancel button
            $(document).on('click', '.pm-reply-cancel-btn', this.handleCancel.bind(this));
            
            // File upload controls
            $(document).on('change', '.pm-file-input', this.handleFileSelection.bind(this));
            $(document).on('click', '.pm-file-remove-btn', this.removeFile.bind(this));
            $(document).on('click', '.pm-upload-cancel-btn', this.cancelUpload.bind(this));
            
            // Draft detection
            $(document).on('input', '.pm-reply-content', this.markAsModified.bind(this));
        },

        openReplyModal: function(e) {
            e.preventDefault();
            console.log('Reply button clicked');
            const button = $(e.currentTarget);
            this.currentConversationId = button.data('conversation-id');
            this.currentReplyId = null;
            this.isEditMode = false;
            
            this.resetModal();
            this.loadDraft();
            this.showModal();
        },

        openEditModal: function(e) {
            e.preventDefault();
            const button = $(e.currentTarget);
            this.currentReplyId = button.data('reply-id');
            this.currentConversationId = button.data('conversation-id');
            this.isEditMode = true;
            
            this.resetModal();
            this.loadReplyForEdit(this.currentReplyId);
            this.showModal();
        },

        showModal: function() {
            const modal = $('.pm-reply-modal');
            modal.attr('aria-hidden', 'false').show();
            $('body').addClass('pm-modal-open');
            
            // Focus on content textarea
            setTimeout(() => {
                $('.pm-reply-content').focus();
            }, 100);
        },

        closeModal: function(e) {
            if (e) e.preventDefault();
            
            if (this.hasUnsavedChanges()) {
                if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                    return;
                }
            }
            
            this.hideModal();
            this.resetModal();
            this.clearDraftAutoSave();
        },

        hideModal: function() {
            const modal = $('.pm-reply-modal');
            modal.attr('aria-hidden', 'true').hide();
            $('body').removeClass('pm-modal-open');
        },

        resetModal: function() {
            $('.pm-reply-form')[0].reset();
            $('.pm-file-previews').empty();
            $('.pm-form-error').hide();
            $('.pm-submit-btn').prop('disabled', false).text('Post Reply');
            this.fileUploads = [];
            this.cancelUpload();
        },

        handleSubmit: function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Form submission intercepted');
            
            const form = $(e.currentTarget);
            const submitBtn = form.find('.pm-submit-btn');
            const content = form.find('.pm-reply-content').val().trim();
            
            if (!content) {
                this.showError('Please enter a reply message.');
                return;
            }
            
            submitBtn.prop('disabled', true).text('Posting...');
            
            const formData = new FormData();
            formData.append('nonce', partyminder_ajax.nonce);
            formData.append('content', content);
            
            if (this.isEditMode) {
                formData.append('action', 'partyminder_update_reply');
                formData.append('reply_id', this.currentReplyId);
            } else {
                formData.append('action', 'partyminder_add_reply');
                formData.append('conversation_id', this.currentConversationId);
                
                // Add file attachments
                this.fileUploads.forEach((file, index) => {
                    formData.append(`attachments[${index}]`, file);
                });
            }
            
            // Handle guest user fields if not logged in
            if (!partyminder_ajax.is_user_logged_in) {
                const guestName = form.find('.pm-guest-name').val().trim();
                const guestEmail = form.find('.pm-guest-email').val().trim();
                
                if (!guestName || !guestEmail) {
                    this.showError('Please provide your name and email.');
                    submitBtn.prop('disabled', false).text('Post Reply');
                    return;
                }
                
                formData.append('guest_name', guestName);
                formData.append('guest_email', guestEmail);
            }
            
            $.ajax({
                url: partyminder_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: this.handleSubmitSuccess.bind(this),
                error: this.handleSubmitError.bind(this)
            });
        },

        handleSubmitSuccess: function(response) {
            if (response.success) {
                this.clearDraft();
                this.hideModal();
                
                // Reload the page to show the new reply
                window.location.reload();
            } else {
                this.showError(response.data || 'Failed to post reply. Please try again.');
                $('.pm-submit-btn').prop('disabled', false).text('Post Reply');
            }
        },

        handleSubmitError: function() {
            this.showError('Network error. Please check your connection and try again.');
            $('.pm-submit-btn').prop('disabled', false).text('Post Reply');
        },

        handleCancel: function(e) {
            e.preventDefault();
            this.closeModal();
        },

        handleFileSelection: function(e) {
            const files = Array.from(e.target.files);
            files.forEach(file => this.addFile(file));
            e.target.value = ''; // Reset input
        },

        addFile: function(file) {
            // Validate file
            if (!this.validateFile(file)) return;
            
            this.fileUploads.push(file);
            this.createFilePreview(file);
        },

        validateFile: function(file) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (file.size > maxSize) {
                this.showError('File size must be less than 5MB.');
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                this.showError('Only JPG, PNG, GIF, and WebP images are allowed.');
                return false;
            }
            
            return true;
        },

        createFilePreview: function(file) {
            const reader = new FileReader();
            const fileIndex = this.fileUploads.length - 1;
            
            reader.onload = (e) => {
                const preview = $(`
                    <div class="pm-file-preview" data-file-index="${fileIndex}">
                        <img src="${e.target.result}" alt="File preview">
                        <div class="pm-file-info">
                            <span class="pm-file-name">${file.name}</span>
                            <button type="button" class="pm-file-remove-btn" data-file-index="${fileIndex}">Remove</button>
                        </div>
                    </div>
                `);
                
                $('.pm-file-previews').append(preview);
            };
            
            reader.readAsDataURL(file);
        },

        removeFile: function(e) {
            e.preventDefault();
            const fileIndex = parseInt($(e.currentTarget).data('file-index'));
            
            // Remove from array
            this.fileUploads.splice(fileIndex, 1);
            
            // Remove preview
            $(`.pm-file-preview[data-file-index="${fileIndex}"]`).remove();
            
            // Update remaining indices
            $('.pm-file-preview').each((index, element) => {
                $(element).attr('data-file-index', index);
                $(element).find('.pm-file-remove-btn').attr('data-file-index', index);
            });
        },

        cancelUpload: function() {
            if (this.uploadXhr) {
                this.uploadXhr.abort();
                this.uploadXhr = null;
            }
        },

        loadReplyForEdit: function(replyId) {
            // In a real implementation, this would load the reply content via AJAX
            // For now, we'll get it from the DOM
            const replyElement = $(`.pm-reply[data-reply-id="${replyId}"]`);
            const content = replyElement.find('.pm-reply-content').text().trim();
            
            $('.pm-reply-content').val(content);
            $('.pm-submit-btn').text('Update Reply');
        },

        initDraftAutoSave: function() {
            this.draftAutoSaveInterval = setInterval(() => {
                if (!this.isEditMode && this.hasContent()) {
                    this.saveDraft();
                }
            }, 30000); // Save every 30 seconds
        },

        saveDraft: function() {
            const content = $('.pm-reply-content').val().trim();
            if (content) {
                const draftKey = `pm_reply_draft_${this.currentConversationId}`;
                localStorage.setItem(draftKey, JSON.stringify({
                    content: content,
                    timestamp: Date.now()
                }));
            }
        },

        loadDraft: function() {
            if (this.isEditMode) return;
            
            const draftKey = `pm_reply_draft_${this.currentConversationId}`;
            const draft = localStorage.getItem(draftKey);
            
            if (draft) {
                const draftData = JSON.parse(draft);
                const isRecent = Date.now() - draftData.timestamp < 86400000; // 24 hours
                
                if (isRecent && draftData.content) {
                    $('.pm-reply-content').val(draftData.content);
                }
            }
        },

        clearDraft: function() {
            if (this.currentConversationId) {
                const draftKey = `pm_reply_draft_${this.currentConversationId}`;
                localStorage.removeItem(draftKey);
            }
        },

        clearDraftAutoSave: function() {
            if (this.draftAutoSaveInterval) {
                clearInterval(this.draftAutoSaveInterval);
                this.draftAutoSaveInterval = null;
            }
        },

        hasContent: function() {
            return $('.pm-reply-content').val().trim().length > 0;
        },

        hasUnsavedChanges: function() {
            return this.hasContent() || this.fileUploads.length > 0;
        },

        markAsModified: function() {
            // This is called when content is modified
            // Could be used for additional change tracking if needed
        },

        showError: function(message) {
            const errorDiv = $('.pm-form-error');
            errorDiv.text(message).show();
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                errorDiv.hide();
            }, 5000);
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('Reply modal initializing...');
        console.log('Modal exists in DOM:', $('.pm-reply-modal').length);
        console.log('Reply buttons exist:', $('.pm-reply-btn').length);
        ReplyModal.init();
        console.log('Reply modal initialized');
    });

    // Expose to global scope
    window.PartyMinderReplyModal = ReplyModal;

})(jQuery);