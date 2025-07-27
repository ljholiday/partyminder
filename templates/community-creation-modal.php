<?php
/**
 * Community Creation Modal Template
 * Modal for creating new communities
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if communities are enabled
if (!PartyMinder_Feature_Flags::is_communities_enabled()) {
    return;
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
.pm-modal-overlay {
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

.pm-modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.pm-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideIn 0.3s ease;
}

.pm-modal-header {
    background: linear-gradient(135deg, <?php echo esc_attr($primary_color); ?>, <?php echo esc_attr($secondary_color); ?>);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pm-modal-title {
    font-size: 1.3em;
    font-weight: bold;
    margin: 0;
}

.pm-modal-close {
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

.pm-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.pm-modal-body {
    padding: 30px;
}

/* Using standard pm- form classes instead of custom styles */

.pm-form-help {
    font-size: 0.85em;
    color: #666;
    margin-top: 5px;
}

.pm-modal-footer {
    padding: 20px 30px;
    background: #f8f9fa;
    border-radius: 0 0 12px 12px;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
}

.pm-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1em;
}

.pm-btn-primary {
    background: <?php echo esc_attr($primary_color); ?>;
    color: white;
}

.pm-btn-primary:hover {
    opacity: 0.9;
}

.pm-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.pm-btn-secondary {
    background: #6c757d;
    color: white;
}

.pm-btn-secondary:hover {
    opacity: 0.9;
}

.pm-error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: none;
}

.pm-success-message {
    background: #d4edda;
    color: #155724;
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: none;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to { 
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 768px) {
    .pm-modal {
        width: 95%;
        margin: 20px;
    }
    
    .pm-modal-body {
        padding: 20px;
    }
    
    .pm-modal-footer {
        padding: 15px 20px;
        flex-direction: column;
    }
}
</style>

<!-- Community Creation Modal -->
<div id="pm-create-community-modal" class="pm-modal-overlay">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h3 class="pm-modal-title"><?php _e('✨ Create New Community', 'partyminder'); ?></h3>
            <button type="button" class="pm-modal-close" aria-label="<?php _e('Close', 'partyminder'); ?>">×</button>
        </div>
        
        <div class="pm-modal-body">
            <div id="pm-modal-error" class="pm-error-message"></div>
            <div id="pm-modal-success" class="pm-success-message"></div>
            
            <form id="pm-create-community-form">
                <div class="pm-form-group">
                    <label for="community-name" class="pm-label">
                        <?php _e('Community Name', 'partyminder'); ?> <span class="pm-text-required">*</span>
                    </label>
                    <input type="text" id="community-name" name="name" class="pm-input" 
                           placeholder="<?php _e('Enter community name...', 'partyminder'); ?>" 
                           maxlength="100" required>
                    <div class="pm-form-help"><?php _e('Choose a descriptive name for your community (3-100 characters)', 'partyminder'); ?></div>
                </div>
                
                <div class="pm-form-group">
                    <label for="community-description" class="pm-label">
                        <?php _e('Description', 'partyminder'); ?>
                    </label>
                    <textarea id="community-description" name="description" class="pm-textarea" 
                              placeholder="<?php _e('Describe what your community is about...', 'partyminder'); ?>"></textarea>
                    <div class="pm-form-help"><?php _e('Help members understand what your community is for', 'partyminder'); ?></div>
                </div>
                
                <div class="pm-form-group">
                    <label for="community-type" class="pm-label">
                        <?php _e('Community Type', 'partyminder'); ?>
                    </label>
                    <select id="community-type" name="type" class="pm-select">
                        <option value="standard"><?php _e('🌟 Standard - General community', 'partyminder'); ?></option>
                        <option value="work"><?php _e('🏢 Work - Office events and team building', 'partyminder'); ?></option>
                        <option value="faith"><?php _e('⛪ Faith - Religious gatherings and events', 'partyminder'); ?></option>
                        <option value="family"><?php _e('👨‍👩‍👧‍👦 Family - Family reunions and celebrations', 'partyminder'); ?></option>
                        <option value="hobby"><?php _e('🎯 Hobby - Interest-based groups', 'partyminder'); ?></option>
                    </select>
                    <div class="pm-form-help"><?php _e('This helps others find and understand your community', 'partyminder'); ?></div>
                </div>
                
                <div class="pm-form-group">
                    <label for="community-privacy" class="pm-label">
                        <?php _e('Privacy Setting', 'partyminder'); ?>
                    </label>
                    <select id="community-privacy" name="privacy" class="pm-select">
                        <option value="public"><?php _e('🌍 Public - Anyone can see and join', 'partyminder'); ?></option>
                        <option value="private"><?php _e('🔒 Private - Invite-only membership', 'partyminder'); ?></option>
                    </select>
                    <div class="pm-form-help"><?php _e('You can change this setting later', 'partyminder'); ?></div>
                </div>
            </form>
        </div>
        
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" id="pm-cancel-community">
                <?php _e('Cancel', 'partyminder'); ?>
            </button>
            <button type="submit" class="pm-btn pm-btn-primary" id="pm-submit-community" form="pm-create-community-form">
                <span class="btn-text"><?php _e('Create Community', 'partyminder'); ?></span>
                <span class="btn-loading" style="display: none;"><?php _e('Creating...', 'partyminder'); ?></span>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('pm-create-community-modal');
    const form = document.getElementById('pm-create-community-form');
    const closeBtn = modal.querySelector('.pm-modal-close');
    const cancelBtn = document.getElementById('pm-cancel-community');
    const submitBtn = document.getElementById('pm-submit-community');
    const errorDiv = document.getElementById('pm-modal-error');
    const successDiv = document.getElementById('pm-modal-success');
    
    // Show modal function
    function showModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        // Focus first input
        setTimeout(() => {
            document.getElementById('community-name').focus();
        }, 100);
    }
    
    // Hide modal function
    function hideModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        form.reset();
        hideMessages();
        resetSubmitButton();
    }
    
    // Show error message
    function showError(message) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        successDiv.style.display = 'none';
    }
    
    // Show success message
    function showSuccess(message) {
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        errorDiv.style.display = 'none';
    }
    
    // Hide messages
    function hideMessages() {
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
    }
    
    // Reset submit button
    function resetSubmitButton() {
        submitBtn.disabled = false;
        submitBtn.querySelector('.btn-text').style.display = 'inline';
        submitBtn.querySelector('.btn-loading').style.display = 'none';
    }
    
    // Set loading state
    function setLoadingState() {
        submitBtn.disabled = true;
        submitBtn.querySelector('.btn-text').style.display = 'none';
        submitBtn.querySelector('.btn-loading').style.display = 'inline';
    }
    
    // Bind to create community buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('.create-community-modal-btn')) {
            e.preventDefault();
            showModal();
        }
    });
    
    // Close modal events
    closeBtn.addEventListener('click', hideModal);
    cancelBtn.addEventListener('click', hideModal);
    
    // Close on overlay click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideModal();
        }
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            hideModal();
        }
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        hideMessages();
        
        // Validate required fields
        const name = document.getElementById('community-name').value.trim();
        if (!name) {
            showError('<?php _e('Community name is required.', 'partyminder'); ?>');
            return;
        }
        
        if (name.length < 3) {
            showError('<?php _e('Community name must be at least 3 characters.', 'partyminder'); ?>');
            return;
        }
        
        setLoadingState();
        
        // Prepare form data
        const formData = new FormData(form);
        formData.append('action', 'partyminder_create_community');
        formData.append('nonce', partyminder_ajax.community_nonce);
        
        // Make AJAX request
        jQuery.ajax({
            url: partyminder_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    setTimeout(() => {
                        hideModal();
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    }, 1500);
                } else {
                    showError(response.data || partyminder_ajax.strings.error);
                    resetSubmitButton();
                }
            },
            error: function(xhr, status, error) {
                showError(partyminder_ajax.strings.error);
                resetSubmitButton();
            }
        });
    });
});
</script>