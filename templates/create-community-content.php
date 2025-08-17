<?php
/**
 * Create Community Content Template
 * Uses unified form template system
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

// Get current user info
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// Check for form submission success
$community_created = false;
$form_errors       = array();

// Check if community was just created
if ( isset( $_GET['partyminder_created'] ) && $_GET['partyminder_created'] == '1' ) {
	$create_data = get_transient( 'partyminder_community_created_' . get_current_user_id() );
	if ( $create_data ) {
		$community_created = true;
		$created_community = $create_data;
		// Clear the transient
		delete_transient( 'partyminder_community_created_' . get_current_user_id() );
	}
}

// Check for form errors
$stored_errors = get_transient( 'partyminder_create_community_errors_' . get_current_user_id() );
if ( $stored_errors ) {
	$form_errors = $stored_errors;
	// Clear the transient
	delete_transient( 'partyminder_create_community_errors_' . get_current_user_id() );
}

// Set up template variables
$page_title       = __( 'Create New Community', 'partyminder' );
$page_description = __( 'Build a community around shared interests and host amazing events together.', 'partyminder' );
$breadcrumbs      = array(
	array(
		'title' => __( 'Communities', 'partyminder' ),
		'url'   => PartyMinder::get_communities_url(),
	),
	array( 'title' => __( 'Create New Community', 'partyminder' ) ),
);

// Main content
ob_start();
?>

<?php if ( $community_created ) : ?>
	<!-- Success Message -->
	<div class="pm-alert pm-alert-success pm-mb-4">
		<h3><?php _e( 'Community Created Successfully!', 'partyminder' ); ?></h3>
		<p><?php _e( 'Your community has been created and is now live.', 'partyminder' ); ?></p>
		<div class="pm-success-actions">
			<a href="<?php echo esc_url( $created_community['url'] ?? PartyMinder::get_communities_url() ); ?>" class="pm-btn">
				<?php _e( 'View Community', 'partyminder' ); ?>
			</a>
			<a href="<?php echo PartyMinder::get_communities_url(); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'All Communities', 'partyminder' ); ?>
			</a>
		</div>
	</div>
<?php endif; ?>

<?php if ( ! empty( $form_errors ) ) : ?>
	<div class="pm-alert pm-alert-error pm-mb-4">
		<h4><?php _e( 'Please fix the following issues:', 'partyminder' ); ?></h4>
		<ul>
			<?php foreach ( $form_errors as $error ) : ?>
				<li><?php echo esc_html( $error ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<form method="post" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" class="pm-form" id="partyminder-community-form">
	<?php wp_nonce_field( 'create_partyminder_community', 'partyminder_community_nonce' ); ?>
	<input type="hidden" name="action" value="partyminder_create_community">
	
	<div class="pm-mb-4">
		<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Community Details', 'partyminder' ); ?></h3>
		
		<div class="pm-form-group">
			<label for="community_name" class="pm-form-label"><?php _e( 'Community Name *', 'partyminder' ); ?></label>
			<input type="text" id="community_name" name="name" class="pm-form-input" 
					value="<?php echo esc_attr( $_POST['name'] ?? '' ); ?>" 
					placeholder="<?php esc_attr_e( 'Enter your community name', 'partyminder' ); ?>" 
					maxlength="255" required>
			<p class="pm-form-help pm-text-muted"><?php _e( 'Choose a clear, memorable name that describes your community', 'partyminder' ); ?></p>
		</div>
		
		<div class="pm-form-group">
			<label for="community_description" class="pm-form-label"><?php _e( 'Description', 'partyminder' ); ?></label>
			<textarea id="community_description" name="description" class="pm-form-textarea" 
						rows="6"
						placeholder="<?php esc_attr_e( 'Describe what your community is about, what activities you plan, and who should join...', 'partyminder' ); ?>"><?php echo esc_textarea( $_POST['description'] ?? '' ); ?></textarea>
			<p class="pm-form-help pm-text-muted"><?php _e( 'Help potential members understand what your community offers and how they can participate', 'partyminder' ); ?></p>
		</div>
		
		<div class="pm-form-group">
			<label for="community_privacy" class="pm-form-label"><?php _e( 'Privacy Setting *', 'partyminder' ); ?></label>
			<select id="community_privacy" name="privacy" class="pm-form-input" required>
				<option value="public" <?php selected( $_POST['privacy'] ?? 'public', 'public' ); ?>>
					<?php _e( 'Public - Anyone can find and request to join', 'partyminder' ); ?>
				</option>
				<option value="friends" <?php selected( $_POST['privacy'] ?? '', 'friends' ); ?>>
					<?php _e( 'Friends Only - Only your connections can find and join', 'partyminder' ); ?>
				</option>
				<option value="private" <?php selected( $_POST['privacy'] ?? '', 'private' ); ?>>
					<?php _e( 'Private - Members must be invited to join', 'partyminder' ); ?>
				</option>
			</select>
			<p class="pm-form-help pm-text-muted"><?php _e( 'Choose who can discover and join your community. Private communities are completely hidden from non-members.', 'partyminder' ); ?></p>
		</div>
		
		<div class="pm-form-group">
			<label class="pm-form-label"><?php _e( 'Membership Approval', 'partyminder' ); ?></label>
			<div class="pm-form-checkbox-group">
				<label class="pm-form-checkbox-label">
					<input type="checkbox" name="requires_approval" value="1" 
							<?php checked( $_POST['requires_approval'] ?? false, '1' ); ?> 
							class="pm-form-checkbox">
					<?php _e( 'Require admin approval for new members', 'partyminder' ); ?>
				</label>
			</div>
			<p class="pm-form-help pm-text-muted"><?php _e( 'When enabled, join requests will need approval before members can access the community.', 'partyminder' ); ?></p>
		</div>
	</div>
	
	<div class="pm-form-actions">
		<button type="submit" name="partyminder_create_community" class="pm-btn">
			<?php _e( 'Create Community', 'partyminder' ); ?>
		</button>
		<a href="<?php echo esc_url( PartyMinder::get_communities_url() ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Back to Communities', 'partyminder' ); ?>
		</a>
	</div>
</form>

<?php
$content = ob_get_clean();

// Include form template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php';
?>

<script>
jQuery(document).ready(function($) {
	$('#partyminder-community-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.html();
		
		// Disable submit button and show loading
		$submitBtn.prop('disabled', true).html('<?php _e( 'Creating Community...', 'partyminder' ); ?>');
		
		// Prepare form data
		const formData = new FormData(this);
		formData.append('action', 'partyminder_create_community');
		
		// Convert FormData to regular object for jQuery
		const data = {};
		for (let [key, value] of formData.entries()) {
			data[key] = value;
		}
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: data,
			success: function(response) {
				if (response.success) {
					// Redirect to success page or community
					const redirectUrl = response.data.redirect_url || '<?php echo PartyMinder::get_create_community_url(); ?>?partyminder_created=1';
					window.location.href = redirectUrl;
				} else {
					// Show error message
					$form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4><?php _e( 'Please fix the following issues:', 'partyminder' ); ?></h4><ul><li>' + (response.data || 'Unknown error occurred') + '</li></ul></div>');
					
					// Scroll to top to show error message
					$('html, body').animate({scrollTop: 0}, 500);
				}
			},
			error: function() {
				$form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4><?php _e( 'Error', 'partyminder' ); ?></h4><p><?php _e( 'Network error. Please try again.', 'partyminder' ); ?></p></div>');
				
				// Scroll to top to show error message
				$('html, body').animate({scrollTop: 0}, 500);
			},
			complete: function() {
				// Re-enable submit button
				$submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});
});
</script>