<?php
/**
 * Create Community Content Template
 * Single-page community creation interface (replaces community-creation-modal.php)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if communities are enabled
if (!PartyMinder_Feature_Flags::is_communities_enabled() || !PartyMinder_Feature_Flags::can_user_create_community()) {
    echo '<div class="pm-text-center pm-p-16">';
    echo '<h2>' . __('Communities Feature Not Available', 'partyminder') . '</h2>';
    echo '<p>' . __('You do not have permission to create communities.', 'partyminder') . '</p>';
    echo '</div>';
    return;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

$community_manager = new PartyMinder_Community_Manager();

// Get current user
$current_user = wp_get_current_user();

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_community') {
    if (wp_verify_nonce($_POST['nonce'], 'partyminder_create_community')) {
        
        $community_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'privacy' => sanitize_text_field($_POST['privacy']),
            'type' => sanitize_text_field($_POST['type'])
        );
        
        $result = $community_manager->create_community($community_data);
        
        if (!is_wp_error($result)) {
            $success_message = __('Community created successfully!', 'partyminder');
            $created_community = $community_manager->get_community($result);
            
            // Redirect to the new community page after a short delay
            $redirect_url = PartyMinder::get_community_url($created_community->slug);
            echo '<script>setTimeout(function() { window.location.href = "' . esc_url($redirect_url) . '"; }, 2000);</script>';
        } else {
            $error_message = $result->get_error_message();
        }
    } else {
        $error_message = __('Security check failed. Please try again.', 'partyminder');
    }
}
?>

<style>
:root {
    --pm-primary: <?php echo esc_attr($primary_color); ?>;
    --pm-secondary: <?php echo esc_attr($secondary_color); ?>;
    --pm-surface: #ffffff;
    --pm-border: #e5e7eb;
    --pm-text: #374151;
    --pm-text-muted: #6b7280;
}

.partyminder-create-community {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.community-creation-header {
    background: linear-gradient(135deg, var(--pm-primary), var(--pm-secondary));
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}

.community-creation-header h1 {
    font-size: 2rem;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.community-creation-header .breadcrumb {
    opacity: 0.9;
    margin-bottom: 0;
}

.community-creation-header .breadcrumb a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
}

.community-creation-header .breadcrumb a:hover {
    color: white;
}

.creation-form-container {
    background: var(--pm-surface);
    border: 1px solid var(--pm-border);
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    max-width: 900px;
    margin: 0 auto;
}

.pm-form-group {
    margin-bottom: 25px;
}

.pm-label {
    display: block;
    font-weight: 600;
    color: var(--pm-text);
    margin-bottom: 8px;
    font-size: 15px;
}

.pm-input,
.pm-textarea,
.pm-select {
    width: 100%;
    padding: 15px 18px;
    border: 2px solid var(--pm-border);
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.pm-input:focus,
.pm-textarea:focus,
.pm-select:focus {
    outline: none;
    border-color: var(--pm-primary);
    box-shadow: 0 0 0 3px rgba(103, 126, 234, 0.1);
}

.pm-textarea {
    resize: vertical;
    min-height: 120px;
}

.pm-button {
    padding: 15px 30px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.pm-button-primary {
    background: var(--pm-primary);
    color: white;
}

.pm-button-primary:hover {
    background: #5a67d8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(103, 126, 234, 0.3);
}

.pm-button-secondary {
    background: #6c757d;
    color: white;
}

.pm-button-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.pm-alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-weight: 500;
}

.pm-alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-left: 4px solid #28a745;
}

.pm-alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f1aeb5;
    border-left: 4px solid #dc3545;
}

.pm-form-help {
    font-size: 14px;
    color: var(--pm-text-muted);
    margin-top: 8px;
    line-height: 1.4;
}

.form-section {
    margin-bottom: 35px;
}

.form-section-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--pm-text);
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--pm-border);
}

.privacy-options,
.type-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.option-card {
    border: 2px solid var(--pm-border);
    border-radius: 8px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--pm-surface);
}

.option-card:hover {
    border-color: var(--pm-primary);
    box-shadow: 0 2px 8px rgba(103, 126, 234, 0.15);
}

.option-card.selected {
    border-color: var(--pm-primary);
    background: rgba(103, 126, 234, 0.05);
}

.option-card h4 {
    margin: 0 0 8px 0;
    color: var(--pm-text);
    font-size: 16px;
}

.option-card p {
    margin: 0;
    color: var(--pm-text-muted);
    font-size: 14px;
    line-height: 1.4;
}

.option-card .option-icon {
    font-size: 24px;
    margin-bottom: 10px;
    display: block;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid var(--pm-border);
}

.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.loading-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--pm-border);
    border-top: 4px solid var(--pm-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .partyminder-create-community {
        padding: 10px;
    }
    
    .creation-form-container {
        padding: 25px;
    }
    
    .privacy-options,
    .type-options {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<div class="partyminder-create-community">
    <!-- Header -->
    <div class="community-creation-header">
        <div class="breadcrumb" style="margin-bottom: 15px;">
            <a href="<?php echo esc_url(PartyMinder::get_dashboard_url()); ?>">🏠 <?php _e('Dashboard', 'partyminder'); ?></a>
            →
            <a href="<?php echo esc_url(PartyMinder::get_communities_url()); ?>"><?php _e('Communities', 'partyminder'); ?></a>
            →
            <span><?php _e('Create Community', 'partyminder'); ?></span>
        </div>
        <h1>✨ <?php _e('Create New Community', 'partyminder'); ?></h1>
        <p style="margin: 0; opacity: 0.9;"><?php _e('Build a community around shared interests and host amazing events together', 'partyminder'); ?></p>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
        <div class="pm-alert pm-alert-success">
            <strong><?php echo esc_html($success_message); ?></strong>
            <br><small><?php _e('Redirecting to your new community...', 'partyminder'); ?></small>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="pm-alert pm-alert-error">
            <strong><?php _e('Error:', 'partyminder'); ?></strong> <?php echo esc_html($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Creation Form -->
    <div class="creation-form-container">
        <form method="post" class="pm-form" id="create-community-form">
            <input type="hidden" name="action" value="create_community">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('partyminder_create_community'); ?>">
            
            <!-- Basic Information Section -->
            <div class="form-section">
                <h3 class="form-section-title"><?php _e('Basic Information', 'partyminder'); ?></h3>
                
                <div class="pm-form-group">
                    <label class="pm-label" for="community-name">
                        <?php _e('Community Name', 'partyminder'); ?> <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="text" 
                           id="community-name" 
                           name="name" 
                           class="pm-input" 
                           placeholder="<?php _e('Enter community name...', 'partyminder'); ?>" 
                           required
                           maxlength="100"
                           value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : ''; ?>">
                    <div class="pm-form-help">
                        <?php _e('Choose a descriptive name that reflects your community\'s purpose', 'partyminder'); ?>
                    </div>
                </div>
                
                <div class="pm-form-group">
                    <label class="pm-label" for="community-description">
                        <?php _e('Description', 'partyminder'); ?>
                    </label>
                    <textarea id="community-description" 
                              name="description" 
                              class="pm-textarea" 
                              placeholder="<?php _e('Describe what your community is about...', 'partyminder'); ?>"
                              maxlength="500"><?php echo isset($_POST['description']) ? esc_textarea($_POST['description']) : ''; ?></textarea>
                    <div class="pm-form-help">
                        <?php _e('Optional: Tell people what your community is about and what kind of events you might host', 'partyminder'); ?>
                    </div>
                </div>
            </div>

            <!-- Community Type Section -->
            <div class="form-section">
                <h3 class="form-section-title"><?php _e('Community Type', 'partyminder'); ?></h3>
                <div class="type-options">
                    <div class="option-card" data-option="general">
                        <span class="option-icon">🌟</span>
                        <h4><?php _e('General Community', 'partyminder'); ?></h4>
                        <p><?php _e('For any kind of social gathering or mixed interests', 'partyminder'); ?></p>
                    </div>
                    <div class="option-card" data-option="food">
                        <span class="option-icon">🍽️</span>
                        <h4><?php _e('Food & Dining', 'partyminder'); ?></h4>
                        <p><?php _e('Dinner parties, cooking clubs, restaurant meetups', 'partyminder'); ?></p>
                    </div>
                    <div class="option-card" data-option="hobby">
                        <span class="option-icon">🎨</span>
                        <h4><?php _e('Hobby & Interest', 'partyminder'); ?></h4>
                        <p><?php _e('Book clubs, game nights, art groups, crafting', 'partyminder'); ?></p>
                    </div>
                    <div class="option-card" data-option="professional">
                        <span class="option-icon">💼</span>
                        <h4><?php _e('Professional', 'partyminder'); ?></h4>
                        <p><?php _e('Networking events, work celebrations, team building', 'partyminder'); ?></p>
                    </div>
                </div>
                <input type="hidden" name="type" id="community-type" value="general">
            </div>

            <!-- Privacy Settings Section -->
            <div class="form-section">
                <h3 class="form-section-title"><?php _e('Privacy Settings', 'partyminder'); ?></h3>
                <div class="privacy-options">
                    <div class="option-card selected" data-option="public">
                        <span class="option-icon">🌍</span>
                        <h4><?php _e('Public Community', 'partyminder'); ?></h4>
                        <p><?php _e('Anyone can find and join this community', 'partyminder'); ?></p>
                    </div>
                    <div class="option-card" data-option="private">
                        <span class="option-icon">🔒</span>
                        <h4><?php _e('Private Community', 'partyminder'); ?></h4>
                        <p><?php _e('Only invited members can join this community', 'partyminder'); ?></p>
                    </div>
                </div>
                <input type="hidden" name="privacy" id="community-privacy" value="public">
                <div class="pm-form-help">
                    <?php _e('You can change this setting later from your community management page', 'partyminder'); ?>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="<?php echo esc_url(PartyMinder::get_communities_url()); ?>" class="pm-button pm-button-secondary">
                    <?php _e('Cancel', 'partyminder'); ?>
                </a>
                <button type="submit" class="pm-button pm-button-primary" id="create-btn">
                    <?php _e('Create Community', 'partyminder'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p><?php _e('Creating your community...', 'partyminder'); ?></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle option card selections
    const privacyOptions = document.querySelectorAll('.privacy-options .option-card');
    const typeOptions = document.querySelectorAll('.type-options .option-card');
    const privacyInput = document.getElementById('community-privacy');
    const typeInput = document.getElementById('community-type');
    
    // Privacy option handling
    privacyOptions.forEach(card => {
        card.addEventListener('click', function() {
            privacyOptions.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            privacyInput.value = this.getAttribute('data-option');
        });
    });
    
    // Type option handling
    typeOptions.forEach(card => {
        card.addEventListener('click', function() {
            typeOptions.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            typeInput.value = this.getAttribute('data-option');
        });
    });
    
    // Form submission handling
    const form = document.getElementById('create-community-form');
    const createBtn = document.getElementById('create-btn');
    const loadingOverlay = document.getElementById('loading-overlay');
    
    form.addEventListener('submit', function(e) {
        // Show loading overlay
        loadingOverlay.style.display = 'flex';
        createBtn.disabled = true;
        createBtn.textContent = '<?php _e('Creating...', 'partyminder'); ?>';
        
        // Basic validation
        const name = document.getElementById('community-name').value.trim();
        if (!name) {
            e.preventDefault();
            alert('<?php _e('Please enter a community name.', 'partyminder'); ?>');
            loadingOverlay.style.display = 'none';
            createBtn.disabled = false;
            createBtn.textContent = '<?php _e('Create Community', 'partyminder'); ?>';
            return;
        }
        
        if (name.length > 100) {
            e.preventDefault();
            alert('<?php _e('Community name must be 100 characters or less.', 'partyminder'); ?>');
            loadingOverlay.style.display = 'none';
            createBtn.disabled = false;
            createBtn.textContent = '<?php _e('Create Community', 'partyminder'); ?>';
            return;
        }
        
        const description = document.getElementById('community-description').value.trim();
        if (description.length > 500) {
            e.preventDefault();
            alert('<?php _e('Description must be 500 characters or less.', 'partyminder'); ?>');
            loadingOverlay.style.display = 'none';
            createBtn.disabled = false;
            createBtn.textContent = '<?php _e('Create Community', 'partyminder'); ?>';
            return;
        }
    });
    
    // Character counter for name field
    const nameInput = document.getElementById('community-name');
    const descriptionInput = document.getElementById('community-description');
    
    function updateCharCounter(input, maxLength) {
        const currentLength = input.value.length;
        const remaining = maxLength - currentLength;
        
        // Find or create counter element
        let counter = input.parentNode.querySelector('.char-counter');
        if (!counter) {
            counter = document.createElement('div');
            counter.className = 'char-counter pm-form-help';
            counter.style.textAlign = 'right';
            counter.style.fontSize = '12px';
            input.parentNode.appendChild(counter);
        }
        
        counter.textContent = remaining + ' <?php _e('characters remaining', 'partyminder'); ?>';
        counter.style.color = remaining < 20 ? '#dc3545' : '#6b7280';
    }
    
    nameInput.addEventListener('input', function() {
        updateCharCounter(this, 100);
    });
    
    descriptionInput.addEventListener('input', function() {
        updateCharCounter(this, 500);
    });
    
    // Initialize character counters
    updateCharCounter(nameInput, 100);
    updateCharCounter(descriptionInput, 500);
});
</script>