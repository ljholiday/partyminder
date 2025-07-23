<?php
/**
 * Custom Login Content Template
 * Branded login/register page with profile setup flow
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is already logged in
if (is_user_logged_in()) {
    wp_redirect(PartyMinder::get_dashboard_url());
    exit;
}

// Handle form submissions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'login';
$errors = array();
$messages = array();

// Handle registration
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['partyminder_register_nonce'])) {
    if (wp_verify_nonce($_POST['partyminder_register_nonce'], 'partyminder_register')) {
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $display_name = sanitize_text_field($_POST['display_name']);
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($display_name)) {
            $errors[] = __('All fields are required.', 'partyminder');
        }
        
        if (!is_email($email)) {
            $errors[] = __('Please enter a valid email address.', 'partyminder');
        }
        
        if (username_exists($username)) {
            $errors[] = __('Username already exists.', 'partyminder');
        }
        
        if (email_exists($email)) {
            $errors[] = __('Email address is already registered.', 'partyminder');
        }
        
        if (strlen($password) < 8) {
            $errors[] = __('Password must be at least 8 characters long.', 'partyminder');
        }
        
        if ($password !== $confirm_password) {
            $errors[] = __('Passwords do not match.', 'partyminder');
        }
        
        // Create user if no errors
        if (empty($errors)) {
            $user_id = wp_create_user($username, $password, $email);
            
            if (!is_wp_error($user_id)) {
                // Update display name
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => $display_name
                ));
                
                // Create profile
                if (class_exists('PartyMinder_Profile_Manager')) {
                    PartyMinder_Profile_Manager::create_default_profile($user_id);
                }
                
                // Auto-login the user
                wp_clear_auth_cookie();
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id, true);
                
                // Redirect to profile setup
                wp_redirect(add_query_arg('setup', '1', PartyMinder::get_profile_url()));
                exit;
            } else {
                $errors[] = $user_id->get_error_message();
            }
        }
    }
}

// Handle login
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['partyminder_login_nonce'])) {
    if (wp_verify_nonce($_POST['partyminder_login_nonce'], 'partyminder_login')) {
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);
        
        if (empty($username) || empty($password)) {
            $errors[] = __('Username and password are required.', 'partyminder');
        } else {
            $creds = array(
                'user_login' => $username,
                'user_password' => $password,
                'remember' => $remember
            );
            
            $user = wp_signon($creds, false);
            
            if (!is_wp_error($user)) {
                $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : PartyMinder::get_dashboard_url();
                wp_redirect($redirect_to);
                exit;
            } else {
                $errors[] = __('Invalid username or password.', 'partyminder');
            }
        }
    }
}

// Get styling options
$primary_color = get_option('partyminder_primary_color', '#667eea');
$secondary_color = get_option('partyminder_secondary_color', '#764ba2');
?>

<style>
/* Custom login page styles */
.partyminder-login-content .login-header h1 {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-login-content .login-form .pm-button {
    background: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-login-content .form-toggle a {
    color: <?php echo esc_attr($primary_color); ?>;
}

.partyminder-login-content .social-login .social-button:hover {
    background: <?php echo esc_attr($primary_color); ?>15;
    border-color: <?php echo esc_attr($primary_color); ?>;
}
</style>

<div class="partyminder-login-content">
    
    <!-- Dashboard Link -->
    <div class="partyminder-breadcrumb">
        <a href="<?php echo esc_url(PartyMinder::get_dashboard_url()); ?>" class="breadcrumb-link">
            🏠 <?php _e('Dashboard', 'partyminder'); ?>
        </a>
        <span class="breadcrumb-separator">→</span>
        <span class="breadcrumb-current"><?php echo $action === 'register' ? __('Register', 'partyminder') : __('Login', 'partyminder'); ?></span>
    </div>
    
    <div class="login-container">
        
        <!-- Login Header -->
        <div class="login-header">
            <h1>🎉 <?php _e('Welcome to PartyMinder', 'partyminder'); ?></h1>
            <p>
                <?php if ($action === 'register'): ?>
                    <?php _e('Create your account to start hosting amazing events and connecting with your community.', 'partyminder'); ?>
                <?php else: ?>
                    <?php _e('Sign in to manage your events, join conversations, and connect with fellow party enthusiasts.', 'partyminder'); ?>
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="partyminder-errors">
            <h4><?php _e('Please fix the following errors:', 'partyminder'); ?></h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Success Messages -->
        <?php if (!empty($messages)): ?>
        <div class="partyminder-success">
            <?php foreach ($messages as $message): ?>
                <p><?php echo esc_html($message); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="login-forms-container">
            
            <?php if ($action === 'register'): ?>
            <!-- Registration Form -->
            <div class="login-form">
                <h2><?php _e('Create Account', 'partyminder'); ?></h2>
                
                <form method="post" class="partyminder-form">
                    <?php wp_nonce_field('partyminder_register', 'partyminder_register_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="display_name"><?php _e('Your Name', 'partyminder'); ?></label>
                        <input type="text" id="display_name" name="display_name" 
                               value="<?php echo esc_attr($_POST['display_name'] ?? ''); ?>" 
                               placeholder="<?php esc_attr_e('How should we address you?', 'partyminder'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username"><?php _e('Username', 'partyminder'); ?></label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo esc_attr($_POST['username'] ?? ''); ?>" 
                               placeholder="<?php esc_attr_e('Choose a unique username', 'partyminder'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php _e('Email Address', 'partyminder'); ?></label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" 
                               placeholder="<?php esc_attr_e('your@email.com', 'partyminder'); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password"><?php _e('Password', 'partyminder'); ?></label>
                            <input type="password" id="password" name="password" 
                                   placeholder="<?php esc_attr_e('At least 8 characters', 'partyminder'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password"><?php _e('Confirm Password', 'partyminder'); ?></label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="<?php esc_attr_e('Repeat your password', 'partyminder'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="pm-button pm-button-primary pm-button-large">
                            <span>✨</span>
                            <?php _e('Create Account & Setup Profile', 'partyminder'); ?>
                        </button>
                    </div>
                </form>
                
                <div class="form-toggle">
                    <p><?php _e('Already have an account?', 'partyminder'); ?> 
                       <a href="<?php echo esc_url(remove_query_arg('action')); ?>"><?php _e('Sign In', 'partyminder'); ?></a>
                    </p>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Login Form -->
            <div class="login-form">
                <h2><?php _e('Sign In', 'partyminder'); ?></h2>
                
                <form method="post" class="partyminder-form">
                    <?php wp_nonce_field('partyminder_login', 'partyminder_login_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="username"><?php _e('Username or Email', 'partyminder'); ?></label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo esc_attr($_POST['username'] ?? ''); ?>" 
                               placeholder="<?php esc_attr_e('Enter your username or email', 'partyminder'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><?php _e('Password', 'partyminder'); ?></label>
                        <input type="password" id="password" name="password" 
                               placeholder="<?php esc_attr_e('Enter your password', 'partyminder'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" value="1" 
                                   <?php checked(isset($_POST['remember'])); ?>>
                            <?php _e('Remember me for 2 weeks', 'partyminder'); ?>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="pm-button pm-button-primary pm-button-large">
                            <span>🚪</span>
                            <?php _e('Sign In', 'partyminder'); ?>
                        </button>
                    </div>
                </form>
                
                <div class="form-toggle">
                    <p><?php _e('New to PartyMinder?', 'partyminder'); ?> 
                       <a href="<?php echo esc_url(add_query_arg('action', 'register')); ?>"><?php _e('Create Account', 'partyminder'); ?></a>
                    </p>
                    <p><a href="<?php echo wp_lostpassword_url(); ?>"><?php _e('Forgot your password?', 'partyminder'); ?></a></p>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Features Preview -->
        <div class="login-features">
            <h3><?php _e('Join the PartyMinder Community', 'partyminder'); ?></h3>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎪</div>
                    <h4><?php _e('Host Events', 'partyminder'); ?></h4>
                    <p><?php _e('Create and manage amazing parties with our easy-to-use tools.', 'partyminder'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💌</div>
                    <h4><?php _e('RSVP & Attend', 'partyminder'); ?></h4>
                    <p><?php _e('Discover local events and connect with your community.', 'partyminder'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h4><?php _e('Connect', 'partyminder'); ?></h4>
                    <p><?php _e('Share tips, recipes, and stories with fellow party enthusiasts.', 'partyminder'); ?></p>
                </div>
            </div>
        </div>
        
    </div>
    
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation and enhancements
    const forms = document.querySelectorAll('.partyminder-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#ef4444';
                    isValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('<?php echo esc_js(__('Please fill in all required fields.', 'partyminder')); ?>');
            }
        });
    });
    
    // Password confirmation validation
    const confirmPassword = document.getElementById('confirm_password');
    const password = document.getElementById('password');
    
    if (confirmPassword && password) {
        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('<?php echo esc_js(__('Passwords do not match', 'partyminder')); ?>');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});
</script>