<?php
/**
 * Create Community Content Template
 * Single-page community creation interface (replaces community-creation-modal.php)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user can create communities
if (!PartyMinder_Feature_Flags::can_user_create_community()) {
    echo '<div class="pm-text-center pm-p-16">';
    echo '<h2>' . __('Cannot Create Community', 'partyminder') . '</h2>';
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

// Set up template variables
$page_title = __('Create New Community', 'partyminder');
$page_description = __('Build a community around shared interests and host amazing events together', 'partyminder');
$breadcrumbs = array(
    array('title' => __('Dashboard', 'partyminder'), 'url' => PartyMinder::get_dashboard_url()),
    array('title' => __('Communities', 'partyminder'), 'url' => PartyMinder::get_communities_url()),
    array('title' => __('Create Community', 'partyminder'))
);

// Capture content
ob_start();
?>

<!-- Success/Error Messages -->
<?php if (isset($success_message)): ?>
<div class="alert alert-success mb-4">
    <strong><?php echo esc_html($success_message); ?></strong>
    <br><small><?php _e('Redirecting to your new community...', 'partyminder'); ?></small>
</div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="alert alert-error mb-4">
    <strong><?php _e('Error:', 'partyminder'); ?></strong> <?php echo esc_html($error_message); ?>
</div>
<?php endif; ?>

<!-- Creation Form -->
<div class="section">
    <form method="post" class="form" id="create-community-form">
            <input type="hidden" name="action" value="create_community">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('partyminder_create_community'); ?>">
            
        <!-- Basic Information Section -->
        <div class="form-section mb-4">
            <h3 class="heading heading-sm mb-4"><?php _e('Basic Information', 'partyminder'); ?></h3>
                
                <div class="form-group">
                    <label class="form-label" for="community-name">
                        <?php _e('Community Name', 'partyminder'); ?> <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="text" 
                           id="community-name" 
                           name="name" 
                           class="form-input" 
                           placeholder="<?php _e('Enter community name...', 'partyminder'); ?>" 
                           required
                           maxlength="100"
                           value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : ''; ?>">
                    <div class="text-muted">
                        <?php _e('Choose a descriptive name that reflects your community\'s purpose', 'partyminder'); ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="community-description">
                        <?php _e('Description', 'partyminder'); ?>
                    </label>
                    <textarea id="community-description" 
                              name="description" 
                              class="form-textarea" 
                              placeholder="<?php _e('Describe what your community is about...', 'partyminder'); ?>"
                              maxlength="500"><?php echo isset($_POST['description']) ? esc_textarea($_POST['description']) : ''; ?></textarea>
                    <div class="text-muted">
                        <?php _e('Optional: Tell people what your community is about and what kind of events you might host', 'partyminder'); ?>
                    </div>
                </div>
            </div>

        <!-- Community Type Section -->
        <div class="form-section mb-4">
            <h3 class="heading heading-sm mb-4"><?php _e('Community Type', 'partyminder'); ?></h3>
            <div class="grid grid-2 gap-4 type-options">
                <div class="section option-card cursor-pointer p-4 border rounded" data-option="general">
                    <div class="text-center">
                        <span class="text-xl mb-4 block">🌟</span>
                        <h4 class="heading heading-sm mb-4"><?php _e('General Community', 'partyminder'); ?></h4>
                        <p class="text-muted"><?php _e('For any kind of social gathering or mixed interests', 'partyminder'); ?></p>
                    </div>
                </div>
                <div class="section option-card cursor-pointer p-4 border rounded" data-option="food">
                    <div class="text-center">
                        <span class="text-xl mb-4 block">🍽️</span>
                        <h4 class="heading heading-sm mb-4"><?php _e('Food & Dining', 'partyminder'); ?></h4>
                        <p class="text-muted"><?php _e('Dinner parties, cooking clubs, restaurant meetups', 'partyminder'); ?></p>
                    </div>
                </div>
                <div class="section option-card cursor-pointer p-4 border rounded" data-option="hobby">
                    <div class="text-center">
                        <span class="text-xl mb-4 block">🎨</span>
                        <h4 class="heading heading-sm mb-4"><?php _e('Hobby & Interest', 'partyminder'); ?></h4>
                        <p class="text-muted"><?php _e('Book clubs, game nights, art groups, crafting', 'partyminder'); ?></p>
                    </div>
                </div>
                <div class="section option-card cursor-pointer p-4 border rounded" data-option="professional">
                    <div class="text-center">
                        <span class="text-xl mb-4 block">💼</span>
                        <h4 class="heading heading-sm mb-4"><?php _e('Professional', 'partyminder'); ?></h4>
                        <p class="text-muted"><?php _e('Networking events, work celebrations, team building', 'partyminder'); ?></p>
                    </div>
                </div>
            </div>
            <input type="hidden" name="type" id="community-type" value="general">
        </div>

        <!-- Privacy Settings Section -->
        <div class="form-section mb-4">
            <h3 class="heading heading-sm mb-4"><?php _e('Privacy Settings', 'partyminder'); ?></h3>
            <div class="grid grid-2 gap-4 privacy-options">
                <div class="section option-card selected cursor-pointer p-4 border rounded" data-option="public">
                    <div class="text-center">
                        <span class="text-xl mb-4 block">🌍</span>
                        <h4 class="heading heading-sm mb-4"><?php _e('Public Community', 'partyminder'); ?></h4>
                        <p class="text-muted"><?php _e('Anyone can find and join this community', 'partyminder'); ?></p>
                    </div>
                </div>
                <div class="section option-card cursor-pointer p-4 border rounded" data-option="private">
                    <div class="text-center">
                        <span class="text-xl mb-4 block">🔒</span>
                        <h4 class="heading heading-sm mb-4"><?php _e('Private Community', 'partyminder'); ?></h4>
                        <p class="text-muted"><?php _e('Only invited members can join this community', 'partyminder'); ?></p>
                    </div>
                </div>
            </div>
            <input type="hidden" name="privacy" id="community-privacy" value="public">
            <div class="text-muted mt-4">
                <?php _e('You can change this setting later from your community management page', 'partyminder'); ?>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions flex gap-4">
            <a href="<?php echo esc_url(PartyMinder::get_communities_url()); ?>" class="btn btn-secondary">
                <?php _e('Cancel', 'partyminder'); ?>
            </a>
            <button type="submit" class="btn" id="create-btn">
                <?php _e('Create Community', 'partyminder'); ?>
            </button>
        </div>
    </form>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div class="loading-content text-center p-4 bg-white rounded">
        <div class="loading-spinner"></div>
        <p><?php _e('Creating your community...', 'partyminder'); ?></p>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include form template
include(PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php');
?>

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
            counter.className = 'char-counter text-muted';
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