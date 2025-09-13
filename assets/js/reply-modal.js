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
        parentReplyId: null,
        isEditMode: false,
        draftAutoSaveInterval: null,
        fileUploads: [],
        removedImages: [],

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
            $(document).on('click', '#pm-reply-modal .pm-modal-close', this.closeModal.bind(this));
            $(document).on('click', '#pm-reply-modal .pm-modal-overlay', this.closeModal.bind(this));
            
            // Form submission
            $(document).on('submit', '.pm-reply-form', this.handleSubmit.bind(this));
            
            // Cancel button
            $(document).on('click', '.pm-reply-cancel-btn', this.handleCancel.bind(this));
            
            // File upload controls
            $(document).on('change', '.pm-file-input', this.handleFileSelection.bind(this));
            $(document).on('click', '.pm-file-remove-btn', this.removeFile.bind(this));
            $(document).on('click', '.pm-existing-image-remove-btn', this.removeExistingImage.bind(this));
            
        },

        openReplyModal: function(e) {
            e.preventDefault();
            const button = $(e.currentTarget);
            this.currentConversationId = button.data('conversation-id');
            this.currentReplyId = null;
            this.parentReplyId = button.data('parent-reply-id') || null;
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
            this.parentReplyId = null; // Clear parent ID for edit mode
            this.isEditMode = true;
            
            this.resetModal();
            this.loadReplyForEdit(this.currentReplyId);
            this.showModal();
        },

        showModal: function() {
            const modal = $('#pm-reply-modal');
            modal.show();
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
            const modal = $('#pm-reply-modal');
            modal.hide();
            $('body').removeClass('pm-modal-open');
        },

        resetModal: function() {
            $('.pm-reply-form')[0].reset();
            $('.pm-file-previews').empty();
            $('.pm-form-error').hide();
            $('.pm-submit-btn').prop('disabled', false).text('Post Reply');
            $('.pm-modal-title').text('Reply to Conversation');
            this.fileUploads = [];
            this.removedImages = [];
        },

        handleSubmit: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
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
                
                // Add file attachments for edit mode too
                this.fileUploads.forEach((file, index) => {
                    formData.append(`attachments[${index}]`, file);
                });
                
                // Add removed images list
                if (this.removedImages.length > 0) {
                    formData.append('removed_images', JSON.stringify(this.removedImages));
                }
            } else {
                formData.append('action', 'partyminder_add_reply');
                formData.append('conversation_id', this.currentConversationId);
                
                // Add parent reply ID if this is a reply to a reply
                if (this.parentReplyId) {
                    formData.append('parent_reply_id', this.parentReplyId);
                }
                
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
            const maxSize = partyminder_ajax.max_file_size || (5 * 1024 * 1024); // Use setting or fallback to 5MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (file.size > maxSize) {
                this.showError(partyminder_ajax.file_size_error || 'File size must be less than 5MB.');
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

        removeExistingImage: function(e) {
            e.preventDefault();
            const imageSrc = $(e.currentTarget).data('image-src');
            
            // Add to removed images list
            if (!this.removedImages.includes(imageSrc)) {
                this.removedImages.push(imageSrc);
            }
            
            // Remove preview
            $(`.pm-existing-image[data-image-src="${imageSrc}"]`).remove();
        },

        loadReplyForEdit: function(replyId) {
            // Find the reply by its ID attribute (reply-{id})
            const replyElement = $(`#reply-${replyId}`);
            if (replyElement.length > 0) {
                const contentElement = replyElement.find('.pm-content');
                
                // Extract text content without images
                let textContent = '';
                const clonedContent = contentElement.clone();
                clonedContent.find('img').remove(); // Remove images from clone
                textContent = clonedContent.text().trim();
                
                // Extract existing images
                const existingImages = contentElement.find('img');
                existingImages.each((index, img) => {
                    const imgSrc = $(img).attr('src');
                    const imgAlt = $(img).attr('alt') || 'Existing image';
                    
                    // Create preview for existing image
                    const preview = $(`
                        <div class="pm-file-preview pm-existing-image" data-image-src="${imgSrc}">
                            <img src="${imgSrc}" alt="${imgAlt}">
                            <div class="pm-file-info">
                                <span class="pm-file-name">${imgAlt}</span>
                                <button type="button" class="pm-existing-image-remove-btn" data-image-src="${imgSrc}">Remove</button>
                            </div>
                        </div>
                    `);
                    
                    $('.pm-file-previews').append(preview);
                });
                
                $('.pm-reply-content').val(textContent);
                $('.pm-submit-btn').text('Update Reply');
                $('.pm-modal-title').text('Edit Reply');
            } else {
                // If we can't find the reply, show error
                this.showError('Could not load reply content for editing.');
            }
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
        ReplyModal.init();
    });

    // Expose to global scope
    window.PartyMinderReplyModal = ReplyModal;

})(jQuery);