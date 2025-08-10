<?php
/**
 * Custom Login Content Template
 * Branded login/register page with profile setup flow
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if user is already logged in
if ( is_user_logged_in() ) {
	wp_redirect( PartyMinder::get_dashboard_url() );
	exit;
}

// Handle form submissions
$action   = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'login';
$errors   = array();
$messages = array();

// Handle registration
if ( $action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['partyminder_register_nonce'] ) ) {
	if ( wp_verify_nonce( $_POST['partyminder_register_nonce'], 'partyminder_register' ) ) {
		$username         = sanitize_user( $_POST['username'] );
		$email            = sanitize_email( $_POST['email'] );
		$password         = $_POST['password'];
		$confirm_password = $_POST['confirm_password'];
		$display_name     = sanitize_text_field( $_POST['display_name'] );

		// Validation
		if ( empty( $username ) || empty( $email ) || empty( $password ) || empty( $display_name ) ) {
			$errors[] = __( 'All fields are required.', 'partyminder' );
		}

		if ( ! is_email( $email ) ) {
			$errors[] = __( 'Please enter a valid email address.', 'partyminder' );
		}

		if ( username_exists( $username ) ) {
			$errors[] = __( 'Username already exists.', 'partyminder' );
		}

		if ( email_exists( $email ) ) {
			$errors[] = __( 'Email address is already registered.', 'partyminder' );
		}

		if ( strlen( $password ) < 8 ) {
			$errors[] = __( 'Password must be at least 8 characters long.', 'partyminder' );
		}

		if ( $password !== $confirm_password ) {
			$errors[] = __( 'Passwords do not match.', 'partyminder' );
		}

		// Create user if no errors
		if ( empty( $errors ) ) {
			$user_id = wp_create_user( $username, $password, $email );

			if ( ! is_wp_error( $user_id ) ) {
				// Update display name
				wp_update_user(
					array(
						'ID'           => $user_id,
						'display_name' => $display_name,
					)
				);

				// Create profile
				if ( class_exists( 'PartyMinder_Profile_Manager' ) ) {
					PartyMinder_Profile_Manager::create_default_profile( $user_id );
				}

				// Auto-login the user
				wp_clear_auth_cookie();
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id, true );

				// Redirect to profile setup
				wp_redirect( add_query_arg( 'setup', '1', PartyMinder::get_profile_url() ) );
				exit;
			} else {
				$errors[] = $user_id->get_error_message();
			}
		}
	}
}

// Handle login
if ( $action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['partyminder_login_nonce'] ) ) {
	if ( wp_verify_nonce( $_POST['partyminder_login_nonce'], 'partyminder_login' ) ) {
		$username = sanitize_user( $_POST['username'] );
		$password = $_POST['password'];
		$remember = isset( $_POST['remember'] );

		if ( empty( $username ) || empty( $password ) ) {
			$errors[] = __( 'Username and password are required.', 'partyminder' );
		} else {
			$creds = array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			);

			$user = wp_signon( $creds, false );

			if ( ! is_wp_error( $user ) ) {
				$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : PartyMinder::get_dashboard_url();
				wp_redirect( $redirect_to );
				exit;
			} else {
				$errors[] = __( 'Invalid username or password.', 'partyminder' );
			}
		}
	}
}

// Set up template variables
$page_title       = $action === 'register' ? __( 'Welcome to PartyMinder', 'partyminder' ) : __( 'Welcome Back', 'partyminder' );
$page_description = $action === 'register'
	? __( 'Create your account to start hosting amazing events and connecting with your community.', 'partyminder' )
	: __( 'Sign in to manage your events, join conversations, and connect with fellow party enthusiasts.', 'partyminder' );

$breadcrumbs = array(
	array(
		'title' => __( 'Dashboard', 'partyminder' ),
		'url'   => PartyMinder::get_dashboard_url(),
	),
	array( 'title' => $action === 'register' ? __( 'Register', 'partyminder' ) : __( 'Login', 'partyminder' ) ),
);

// Capture content
ob_start();
?>
<!-- Error Messages -->
<?php if ( ! empty( $errors ) ) : ?>
<div class="alert alert-error mb-4">
	<h4 class="heading heading-sm mb-4"><?php _e( 'Please fix the following errors:', 'partyminder' ); ?></h4>
	<ul>
		<?php foreach ( $errors as $error ) : ?>
			<li><?php echo esc_html( $error ); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<!-- Success Messages -->
<?php if ( ! empty( $messages ) ) : ?>
<div class="alert alert-success mb-4">
	<?php foreach ( $messages as $message ) : ?>
		<p><?php echo esc_html( $message ); ?></p>
	<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ( $action === 'register' ) : ?>
<!-- Registration Form -->
<div class="pm-section">
	<h2 class="heading heading-md mb-4"><?php _e( 'Create Account', 'partyminder' ); ?></h2>
	
	<form method="post" class="pm-form">
		<?php wp_nonce_field( 'partyminder_register', 'partyminder_register_nonce' ); ?>
		
		<div class="pm-form-group">
			<label for="display_name" class="pm-form-label"><?php _e( 'Your Name', 'partyminder' ); ?></label>
			<input type="text" id="display_name" name="display_name" class="pm-form-input"
					value="<?php echo esc_attr( $_POST['display_name'] ?? '' ); ?>" 
					placeholder="<?php esc_attr_e( 'How should we address you?', 'partyminder' ); ?>" required>
		</div>
		
		<div class="pm-form-group">
			<label for="username" class="pm-form-label"><?php _e( 'Username', 'partyminder' ); ?></label>
			<input type="text" id="username" name="username" class="pm-form-input"
					value="<?php echo esc_attr( $_POST['username'] ?? '' ); ?>" 
					placeholder="<?php esc_attr_e( 'Choose a unique username', 'partyminder' ); ?>" required>
		</div>
		
		<div class="pm-form-group">
			<label for="email" class="pm-form-label"><?php _e( 'Email Address', 'partyminder' ); ?></label>
			<input type="email" id="email" name="email" class="pm-form-input"
					value="<?php echo esc_attr( $_POST['email'] ?? '' ); ?>" 
					placeholder="<?php esc_attr_e( 'your@email.com', 'partyminder' ); ?>" required>
		</div>
		
		<div class="pm-form-row">
			<div class="pm-form-group">
				<label for="password" class="pm-form-label"><?php _e( 'Password', 'partyminder' ); ?></label>
				<input type="password" id="password" name="password" class="pm-form-input"
						placeholder="<?php esc_attr_e( 'At least 8 characters', 'partyminder' ); ?>" required>
			</div>
			
			<div class="pm-form-group">
				<label for="confirm_password" class="pm-form-label"><?php _e( 'Confirm Password', 'partyminder' ); ?></label>
				<div>
					<input type="password" id="confirm_password" name="confirm_password" class="pm-form-input"
							placeholder="<?php esc_attr_e( 'Repeat your password', 'partyminder' ); ?>" required>
					<div id="password-match-indicator" style="display: none;">
						<span></span>
						<span></span>
					</div>
				</div>
			</div>
		</div>
		
		<div class="pm-text-center">
			<button type="submit" class="btn btn-large">
				<span></span>
				<?php _e( 'Create Account & Setup Profile', 'partyminder' ); ?>
			</button>
		</div>
	</form>
	
	<div class="text-center mt-4">
		<p class="pm-text-muted"><?php _e( 'Already have an account?', 'partyminder' ); ?> 
			<a href="<?php echo esc_url( remove_query_arg( 'action' ) ); ?>" class="pm-text-primary"><?php _e( 'Sign In', 'partyminder' ); ?></a>
		</p>
	</div>
</div>

<?php else : ?>
<!-- Login Form -->
<div class="pm-section">
	<h2 class="heading heading-md mb-4"><?php _e( 'Sign In', 'partyminder' ); ?></h2>
	
	<form method="post" class="pm-form">
		<?php wp_nonce_field( 'partyminder_login', 'partyminder_login_nonce' ); ?>
		
		<div class="pm-form-group">
			<label for="username" class="pm-form-label"><?php _e( 'Username or Email', 'partyminder' ); ?></label>
			<input type="text" id="username" name="username" class="pm-form-input"
					value="<?php echo esc_attr( $_POST['username'] ?? '' ); ?>" 
					placeholder="<?php esc_attr_e( 'Enter your username or email', 'partyminder' ); ?>" required>
		</div>
		
		<div class="pm-form-group">
			<label for="password" class="pm-form-label"><?php _e( 'Password', 'partyminder' ); ?></label>
			<input type="password" id="password" name="password" class="pm-form-input"
					placeholder="<?php esc_attr_e( 'Enter your password', 'partyminder' ); ?>" required>
		</div>
		
		<div class="pm-form-group">
			<label class="pm-flex">
				<input type="checkbox" name="remember" value="1"
						<?php checked( isset( $_POST['remember'] ) ); ?>>
				<span class="pm-text-muted"><?php _e( 'Remember me for 2 weeks', 'partyminder' ); ?></span>
			</label>
		</div>
		
		<div class="pm-text-center">
			<button type="submit" class="btn btn-large">
				<span>🚪</span>
				<?php _e( 'Sign In', 'partyminder' ); ?>
			</button>
		</div>
	</form>
	
	<div class="text-center mt-4">
		<p class="pm-text-muted mb-4"><?php _e( 'New to PartyMinder?', 'partyminder' ); ?> 
			<a href="<?php echo esc_url( add_query_arg( 'action', 'register' ) ); ?>" class="pm-text-primary"><?php _e( 'Create Account', 'partyminder' ); ?></a>
		</p>
		<p><a href="<?php echo wp_lostpassword_url(); ?>" class="pm-text-primary"><?php _e( 'Forgot your password?', 'partyminder' ); ?></a></p>
	</div>
</div>
<?php endif; ?>

<!-- Features Preview -->
<div class="section mt-4">
	<h3 class="heading heading-md text-center mb-4"><?php _e( 'Join the PartyMinder Community', 'partyminder' ); ?></h3>
	
	<div class="grid grid-3 gap-4">
		<div class="text-center p-4">
			<div class="text-xl mb-4">🎪</div>
			<h4 class="heading heading-sm mb-4"><?php _e( 'Host Events', 'partyminder' ); ?></h4>
			<p class="pm-text-muted"><?php _e( 'Create and manage amazing parties with our easy-to-use tools.', 'partyminder' ); ?></p>
		</div>
		
		<div class="text-center p-4">
			<div class="text-xl mb-4"></div>
			<h4 class="heading heading-sm mb-4"><?php _e( 'RSVP & Attend', 'partyminder' ); ?></h4>
			<p class="pm-text-muted"><?php _e( 'Discover local events and connect with your community.', 'partyminder' ); ?></p>
		</div>
		
		<div class="text-center p-4">
			<div class="text-xl mb-4"></div>
			<h4 class="heading heading-sm mb-4"><?php _e( 'Connect', 'partyminder' ); ?></h4>
			<p class="pm-text-muted"><?php _e( 'Share tips, recipes, and stories with fellow party enthusiasts.', 'partyminder' ); ?></p>
		</div>
	</div>
</div>
<?php
$content = ob_get_clean();

// Include form template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Form validation and enhancements
	const forms = document.querySelectorAll('.form');
	
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
				alert('<?php echo esc_js( __( 'Please fill in all required fields.', 'partyminder' ) ); ?>');
			}
		});
	});
	
	// Password confirmation validation with visual feedback
	const confirmPassword = document.getElementById('confirm_password');
	const password = document.getElementById('password');
	const matchIndicator = document.getElementById('password-match-indicator');
	
	if (confirmPassword && password && matchIndicator) {
		function updatePasswordMatchIndicator() {
			const passwordValue = password.value;
			const confirmValue = confirmPassword.value;
			
			// Only show indicator if confirm password has content
			if (confirmValue.length === 0) {
				matchIndicator.style.display = 'none';
				confirmPassword.setCustomValidity('');
				return;
			}
			
			matchIndicator.style.display = 'block';
			
			if (passwordValue === confirmValue) {
				// Passwords match
				matchIndicator.className = 'text-success';
				matchIndicator.innerHTML = '<span>✓</span> <span><?php echo esc_js( __( 'Passwords match', 'partyminder' ) ); ?></span>';
				confirmPassword.setCustomValidity('');
			} else {
				// Passwords don't match
				matchIndicator.className = 'text-error';
				matchIndicator.innerHTML = '<span>✗</span> <span><?php echo esc_js( __( 'Passwords do not match', 'partyminder' ) ); ?></span>';
				confirmPassword.setCustomValidity('<?php echo esc_js( __( 'Passwords do not match', 'partyminder' ) ); ?>');
			}
		}
		
		confirmPassword.addEventListener('input', updatePasswordMatchIndicator);
		password.addEventListener('input', updatePasswordMatchIndicator);
	}
});
</script>