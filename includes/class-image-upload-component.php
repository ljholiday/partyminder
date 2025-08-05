<?php

/**
 * PartyMinder Image Upload Component
 * Unified, reusable image upload module for profile, event, and community pages
 */
class PartyMinder_Image_Upload_Component {
    
    /**
     * Render image upload component
     * 
     * @param array $config Configuration array
     * @return string HTML output
     */
    public static function render($config = array()) {
        $defaults = array(
            'entity_type' => 'user', // user, event, community
            'entity_id' => get_current_user_id(),
            'image_type' => 'profile', // profile, cover, thumbnail
            'current_image' => '',
            'button_text' => __('Upload Image', 'partyminder'),
            'button_icon' => '📷',
            'button_class' => 'pm-btn pm-btn-secondary',
            'show_preview' => true,
            'preview_class' => 'pm-image-preview',
            'modal_title' => __('Upload Image', 'partyminder'),
            'accepted_formats' => 'image/jpeg,image/png,image/gif,image/webp',
            'max_file_size' => '5MB',
            'dimensions' => null // Will be set based on image_type
        );
        
        $config = array_merge($defaults, $config);
        
        // Set dimensions based on image type
        if (!$config['dimensions']) {
            switch ($config['image_type']) {
                case 'cover':
                    $config['dimensions'] = __('Recommended: 1200x400 pixels', 'partyminder');
                    break;
                case 'profile':
                    $config['dimensions'] = __('Recommended: 400x400 pixels', 'partyminder');
                    break;
                default:
                    $config['dimensions'] = __('Square format recommended', 'partyminder');
            }
        }
        
        // Generate unique IDs for this component instance
        $component_id = 'image-upload-' . uniqid();
        $modal_id = 'modal-' . $component_id;
        $input_id = 'input-' . $component_id;
        $preview_id = 'preview-' . $component_id;
        
        ob_start();
        ?>
        
        <!-- Upload Button -->
        <button type="button" 
                class="<?php echo esc_attr($config['button_class']); ?> image-upload-trigger" 
                data-modal="<?php echo esc_attr($modal_id); ?>"
                title="<?php echo esc_attr($config['button_text']); ?>">
            <?php echo $config['button_icon']; ?> <?php echo esc_html($config['button_text']); ?>
        </button>
        
        <?php if ($config['show_preview'] && !empty($config['current_image'])): ?>
        <!-- Current Image Preview -->
        <div id="<?php echo esc_attr($preview_id); ?>" class="<?php echo esc_attr($config['preview_class']); ?> mt-4">
            <img src="<?php echo esc_url($config['current_image']); ?>" 
                 alt="<?php _e('Current image', 'partyminder'); ?>"
                 style="max-width: 200px; height: auto; border-radius: 0.5rem; border: 2px solid var(--border);">
        </div>
        <?php endif; ?>
        
        <!-- Upload Modal -->
        <div id="<?php echo esc_attr($modal_id); ?>" class="image-upload-modal" style="display: none;">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="heading heading-sm"><?php echo esc_html($config['modal_title']); ?></h3>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                
                <form class="pm-image-upload-form" enctype="multipart/form-data">
                    <div class="modal-body">
                        <!-- Hidden fields -->
                        <input type="hidden" name="entity_type" value="<?php echo esc_attr($config['entity_type']); ?>">
                        <input type="hidden" name="entity_id" value="<?php echo esc_attr($config['entity_id']); ?>">
                        <input type="hidden" name="image_type" value="<?php echo esc_attr($config['image_type']); ?>">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('partyminder_image_upload'); ?>">
                        
                        <!-- File Input -->
                        <div class="pm-form-group">
                            <label class="pm-form-label" for="<?php echo esc_attr($input_id); ?>">
                                <?php _e('Select Image', 'partyminder'); ?>
                            </label>
                            <input type="file" 
                                   id="<?php echo esc_attr($input_id); ?>"
                                   name="image_file" 
                                   class="pm-form-input pm-image-file-input"
                                   accept="<?php echo esc_attr($config['accepted_formats']); ?>"
                                   required>
                            <div class="pm-text-muted pm-mt">
                                <div><?php echo esc_html($config['dimensions']); ?></div>
                                <div><?php printf(__('Maximum file size: %s', 'partyminder'), $config['max_file_size']); ?></div>
                                <div><?php _e('Formats: JPG, PNG, GIF, WebP', 'partyminder'); ?></div>
                            </div>
                        </div>
                        
                        <!-- Preview Area -->
                        <div class="upload-preview" style="display: none;">
                            <img class="preview-image" style="max-width: 100%; height: auto; border-radius: 0.5rem; border: 2px solid var(--border);">
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="upload-progress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                            <div class="progress-text"><?php _e('Uploading...', 'partyminder'); ?></div>
                        </div>
                        
                        <!-- Error/Success Messages -->
                        <div class="upload-messages"></div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="pm-btn pm-btn-secondary pm-modal-close">
                            <?php _e('Cancel', 'partyminder'); ?>
                        </button>
                        <button type="submit" class="pm-btn pm-upload-submit" disabled>
                            ✨ <?php _e('Upload', 'partyminder'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue required scripts and styles
     */
    public static function enqueue_assets() {
        // Only enqueue once per page
        static $assets_enqueued = false;
        if ($assets_enqueued) return;
        $assets_enqueued = true;
        
        // Add CSS for the component
        add_action('wp_footer', array(__CLASS__, 'output_styles'));
        add_action('wp_footer', array(__CLASS__, 'output_scripts'));
    }
    
    /**
     * Output component styles
     */
    public static function output_styles() {
        ?>
        <style>
        /* Image Upload Component Styles */
        .image-upload-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
        }
        
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 0.75rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 2px solid var(--border);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.25rem;
        }
        
        .modal-close:hover {
            background: var(--background);
            color: var(--text);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding: 1.5rem;
            border-top: 2px solid var(--border);
        }
        
        .progress-bar {
            width: 100%;
            height: 0.5rem;
            background: var(--border);
            border-radius: 0.25rem;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .upload-messages .message {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .upload-messages .error {
            background: var(--danger);
            color: white;
        }
        
        .upload-messages .success {
            background: var(--success);
            color: white;
        }
        
        .image-preview img {
            transition: all 0.3s ease;
        }
        
        @media (max-width: 48rem) {
            .modal-content {
                width: 95%;
                max-height: 95vh;
            }
            
            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1rem;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Output component JavaScript
     */
    public static function output_scripts() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle modal triggers
            document.addEventListener('click', function(e) {
                if (e.target.matches('.image-upload-trigger') || e.target.closest('.image-upload-trigger')) {
                    const trigger = e.target.matches('.image-upload-trigger') ? e.target : e.target.closest('.image-upload-trigger');
                    const modalId = trigger.getAttribute('data-modal');
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    }
                }
            });
            
            // Handle modal closing
            document.addEventListener('click', function(e) {
                if (e.target.matches('.pm-modal-close') || e.target.matches('.modal-overlay')) {
                    const modal = e.target.closest('.image-upload-modal');
                    if (modal) {
                        closeModal(modal);
                    }
                }
            });
            
            // Handle file input changes
            document.addEventListener('change', function(e) {
                if (e.target.matches('.pm-image-file-input')) {
                    handleFilePreview(e.target);
                }
            });
            
            // Handle form submissions
            document.addEventListener('submit', function(e) {
                if (e.target.matches('.pm-image-upload-form')) {
                    e.preventDefault();
                    handleImageUpload(e.target);
                }
            });
            
            function closeModal(modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                
                // Reset form
                const form = modal.querySelector('.pm-image-upload-form');
                if (form) {
                    form.reset();
                    form.querySelector('.upload-preview').style.display = 'none';
                    form.querySelector('.upload-progress').style.display = 'none';
                    form.querySelector('.upload-messages').innerHTML = '';
                    form.querySelector('.pm-upload-submit').disabled = true;
                }
            }
            
            function handleFilePreview(fileInput) {
                const file = fileInput.files[0];
                const form = fileInput.closest('.pm-image-upload-form');
                const preview = form.querySelector('.upload-preview');
                const previewImg = form.querySelector('.preview-image');
                const submitBtn = form.querySelector('.pm-upload-submit');
                
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.style.display = 'block';
                        submitBtn.disabled = false;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                    submitBtn.disabled = true;
                }
            }
            
            function handleImageUpload(form) {
                const formData = new FormData(form);
                const progressContainer = form.querySelector('.upload-progress');
                const progressFill = form.querySelector('.progress-fill');
                const progressText = form.querySelector('.progress-text');
                const messages = form.querySelector('.upload-messages');
                const submitBtn = form.querySelector('.pm-upload-submit');
                const entityType = formData.get('entity_type');
                const entityId = formData.get('entity_id');
                const imageType = formData.get('image_type');
                
                // Dispatch upload started event
                document.dispatchEvent(new CustomEvent('partyminder:uploadStarted', {
                    detail: { entityType, entityId, imageType }
                }));
                
                // Show progress
                progressContainer.style.display = 'block';
                submitBtn.disabled = true;
                submitBtn.textContent = '<?php _e('Uploading...', 'partyminder'); ?>';
                messages.innerHTML = '';
                
                // Add action for WordPress AJAX
                formData.append('action', 'partyminder_upload_image');
                
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressFill.style.width = percentComplete + '%';
                        progressText.textContent = Math.round(percentComplete) + '%';
                        
                        // Dispatch progress event
                        document.dispatchEvent(new CustomEvent('partyminder:uploadProgress', {
                            detail: { entityType, entityId, imageType, progress: percentComplete }
                        }));
                    }
                });
                
                xhr.addEventListener('load', function() {
                    progressContainer.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.textContent = '✨ <?php _e('Upload', 'partyminder'); ?>';
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            messages.innerHTML = '<div class="message success">' + response.data.message + '</div>';
                            
                            // Update preview if exists
                            updateImagePreview(entityType, entityId, imageType, response.data.url);
                            
                            // Dispatch completion event
                            document.dispatchEvent(new CustomEvent('partyminder:uploadCompleted', {
                                detail: { entityType, entityId, imageType, url: response.data.url }
                            }));
                            
                            // Close modal after success
                            setTimeout(() => {
                                closeModal(form.closest('.image-upload-modal'));
                            }, 1500);
                            
                        } else {
                            messages.innerHTML = '<div class="message error">' + response.data.error + '</div>';
                            
                            // Dispatch error event
                            document.dispatchEvent(new CustomEvent('partyminder:uploadError', {
                                detail: { entityType, entityId, imageType, error: response.data.error }
                            }));
                        }
                    } catch (e) {
                        messages.innerHTML = '<div class="message error"><?php _e('Upload failed. Please try again.', 'partyminder'); ?></div>';
                        
                        // Dispatch error event
                        document.dispatchEvent(new CustomEvent('partyminder:uploadError', {
                            detail: { entityType, entityId, imageType, error: 'Parse error' }
                        }));
                    }
                });
                
                xhr.addEventListener('error', function() {
                    progressContainer.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.textContent = '✨ <?php _e('Upload', 'partyminder'); ?>';
                    messages.innerHTML = '<div class="message error"><?php _e('Upload failed. Please try again.', 'partyminder'); ?></div>';
                    
                    // Dispatch error event
                    document.dispatchEvent(new CustomEvent('partyminder:uploadError', {
                        detail: { entityType, entityId, imageType, error: 'Network error' }
                    }));
                });
                
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
                xhr.send(formData);
            }
            
            function updateImagePreview(entityType, entityId, imageType, newImageUrl) {
                // Update any existing image previews on the page
                if (imageType === 'cover') {
                    // Update cover photo background
                    const coverElements = document.querySelectorAll('.profile-cover');
                    coverElements.forEach(element => {
                        element.style.backgroundImage = 'url(' + newImageUrl + ')';
                        element.style.backgroundSize = 'cover';
                        element.style.backgroundPosition = 'center';
                    });
                } else if (imageType === 'profile') {
                    // Update profile avatar images
                    const avatarImages = document.querySelectorAll('.profile-avatar img, img[alt*="avatar"], img[alt*="profile"]');
                    avatarImages.forEach(img => {
                        img.src = newImageUrl;
                    });
                }
                
                // Update any generic image previews
                const genericPreviews = document.querySelectorAll('.image-preview img');
                genericPreviews.forEach(preview => {
                    if (preview.dataset.imageType === imageType) {
                        preview.src = newImageUrl;
                    }
                });
                
                // Trigger custom event for external listeners
                document.dispatchEvent(new CustomEvent('partyminder:imageUploaded', {
                    detail: {
                        entityType: entityType,
                        entityId: entityId,
                        imageType: imageType,
                        url: newImageUrl
                    }
                }));
            }
        });
        </script>
        <?php
    }
    
    /**
     * Handle AJAX image upload
     */
    public static function handle_ajax_upload() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'partyminder_image_upload')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => array('error' => __('Security check failed.', 'partyminder'))
            )));
        }
        
        // Validate required fields
        if (empty($_POST['entity_type']) || empty($_POST['entity_id']) || empty($_POST['image_type'])) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => array('error' => __('Required fields missing.', 'partyminder'))
            )));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => array('error' => __('No file uploaded or upload error.', 'partyminder'))
            )));
        }
        
        $entity_type = sanitize_text_field($_POST['entity_type']);
        $entity_id = intval($_POST['entity_id']);
        $image_type = sanitize_text_field($_POST['image_type']);
        
        // Handle the upload using the existing image manager
        $result = PartyMinder_Image_Manager::handle_image_upload(
            $_FILES['image_file'],
            $image_type,
            $entity_id,
            $entity_type
        );
        
        if ($result['success']) {
            // Update the database based on entity type
            self::update_entity_image($entity_type, $entity_id, $image_type, $result['url']);
            
            wp_die(json_encode(array(
                'success' => true,
                'data' => array(
                    'message' => __('Image uploaded successfully!', 'partyminder'),
                    'url' => $result['url'],
                    'filename' => $result['filename']
                )
            )));
        } else {
            wp_die(json_encode(array(
                'success' => false,
                'data' => array('error' => $result['error'])
            )));
        }
    }
    
    /**
     * Update entity image in database
     */
    private static function update_entity_image($entity_type, $entity_id, $image_type, $image_url) {
        global $wpdb;
        
        switch ($entity_type) {
            case 'user':
                $table = $wpdb->prefix . 'partyminder_user_profiles';
                $field = ($image_type === 'cover') ? 'cover_image' : 'profile_image';
                $wpdb->update(
                    $table,
                    array($field => $image_url),
                    array('user_id' => $entity_id),
                    array('%s'),
                    array('%d')
                );
                break;
                
            case 'event':
                $table = $wpdb->prefix . 'partyminder_events';
                $field = ($image_type === 'cover') ? 'cover_image' : 'thumbnail_image';
                $wpdb->update(
                    $table,
                    array($field => $image_url),
                    array('id' => $entity_id),
                    array('%s'),
                    array('%d')
                );
                break;
                
            case 'community':
                $table = $wpdb->prefix . 'partyminder_communities';
                $field = ($image_type === 'cover') ? 'cover_image' : 'logo_image';
                $wpdb->update(
                    $table,
                    array($field => $image_url),
                    array('id' => $entity_id),
                    array('%s'),
                    array('%d')
                );
                break;
        }
    }
}