<?php
/**
 * Manage Community Content Template
 * Community management interface
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

$community_manager = new PartyMinder_Community_Manager();

// Helper function for cover image upload
function handle_community_cover_image_upload( $file, $community_id ) {
	// Validate file
	$validation_result = PartyMinder_Settings::validate_uploaded_file( $file );
	if ( is_wp_error( $validation_result ) ) {
		return $validation_result;
	}

	// Use WordPress built-in upload handling
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}

	$uploaded_file = wp_handle_upload( $file, array( 'test_form' => false ) );

	if ( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
		// Update community with the image URL
		global $wpdb;
		$communities_table = $wpdb->prefix . 'partyminder_communities';
		$wpdb->update(
			$communities_table,
			array( 'featured_image' => $uploaded_file['url'] ),
			array( 'id' => $community_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $uploaded_file;
	} else {
		return new WP_Error( 'upload_failed', __( 'File upload failed.', 'partyminder' ) );
	}
}

// Get community ID from URL parameter
$community_id = isset( $_GET['community_id'] ) ? intval( $_GET['community_id'] ) : 0;
$current_tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';

if ( ! $community_id ) {
	$page_title      = __( 'Community Not Found', 'partyminder' );
	$main_content    = '<div class="pm-text-center pm-p-16"><h2>' . __( 'Community Not Found', 'partyminder' ) . '</h2><p>' . __( 'No community ID provided.', 'partyminder' ) . '</p><a href="' . esc_url( PartyMinder::get_communities_url() ) . '" class="pm-btn">' . __( 'Back to Communities', 'partyminder' ) . '</a></div>';
	$sidebar_content = '';
	include PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
	return;
}

// Get community data
$community = $community_manager->get_community( $community_id );
if ( ! $community ) {
	$page_title      = __( 'Community Not Found', 'partyminder' );
	$main_content    = '<div class="pm-text-center pm-p-16"><h2>' . __( 'Community Not Found', 'partyminder' ) . '</h2><p>' . __( 'The requested community does not exist.', 'partyminder' ) . '</p><a href="' . esc_url( PartyMinder::get_communities_url() ) . '" class="pm-btn">' . __( 'Back to Communities', 'partyminder' ) . '</a></div>';
	$sidebar_content = '';
	include PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
	return;
}

// Get current user and check permissions
$current_user = wp_get_current_user();
$user_role    = is_user_logged_in() ? $community_manager->get_member_role( $community_id, $current_user->ID ) : null;

// Check if user can manage this community
if ( ! $user_role || $user_role !== 'admin' ) {
	$page_title      = __( 'Access Denied', 'partyminder' );
	$main_content    = '<div class="pm-text-center pm-p-16"><h2>' . __( 'Access Denied', 'partyminder' ) . '</h2><p>' . __( 'You do not have permission to manage this community.', 'partyminder' ) . '</p><a href="' . esc_url( PartyMinder::get_community_url( $community->slug ) ) . '" class="pm-btn">' . __( 'View Community', 'partyminder' ) . '</a></div>';
	$sidebar_content = '';
	include PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
	return;
}

// Process form submissions
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['action'] ) ) {
	// Handle community settings update
	if ( $_POST['action'] === 'update_community_settings' && wp_verify_nonce( $_POST['nonce'], 'partyminder_community_management' ) ) {
		$update_data = array(
			'name'        => sanitize_text_field( $_POST['community_name'] ),
			'description' => sanitize_textarea_field( $_POST['description'] ),
			'visibility'  => sanitize_text_field( $_POST['visibility'] ),
		);

		// Handle cover image removal
		if ( isset( $_POST['remove_cover_image'] ) && $_POST['remove_cover_image'] == '1' ) {
			$update_data['featured_image'] = '';
		}

		// Handle cover image upload
		if ( isset( $_FILES['cover_image'] ) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK ) {
			$upload_result = handle_community_cover_image_upload( $_FILES['cover_image'], $community_id );
			if ( is_wp_error( $upload_result ) ) {
				$error_message = $upload_result->get_error_message();
			} else {
				$update_data['featured_image'] = $upload_result['url'];
			}
		}

		if ( ! isset( $error_message ) ) {
			$result = $community_manager->update_community( $community_id, $update_data );

			if ( ! is_wp_error( $result ) ) {
				$success_message = __( 'Community settings updated successfully.', 'partyminder' );
				// Refresh community data
				$community = $community_manager->get_community( $community_id );
			} else {
				$error_message = $result->get_error_message();
			}
		}
	}
	
	// Handle community deletion
	if ( $_POST['action'] === 'delete_community' && wp_verify_nonce( $_POST['nonce'], 'partyminder_community_management' ) ) {
		$confirm_name = sanitize_text_field( $_POST['confirm_name'] );
		
		if ( $confirm_name === $community->name ) {
			$result = $community_manager->delete_community( $community_id );
			
			if ( ! is_wp_error( $result ) ) {
				// Redirect to communities page after successful deletion
				wp_redirect( PartyMinder::get_communities_url() . '?deleted=1' );
				exit;
			} else {
				$error_message = $result->get_error_message();
			}
		} else {
			$error_message = __( 'Community name confirmation does not match. Community was not deleted.', 'partyminder' );
		}
	}
}

// Set up template variables
$page_title       = sprintf( __( 'Manage %s', 'partyminder' ), esc_html( $community->name ) );
$page_description = __( 'Manage settings, members, and invitations for your community', 'partyminder' );

// Breadcrumbs
$breadcrumbs = array(
	array(
		'title' => __( 'Dashboard', 'partyminder' ),
		'url'   => PartyMinder::get_dashboard_url(),
	),
	array(
		'title' => __( 'Communities', 'partyminder' ),
		'url'   => PartyMinder::get_communities_url(),
	),
	array(
		'title' => esc_html( $community->name ),
		'url'   => PartyMinder::get_community_url( $community->slug ),
	),
	array( 'title' => __( 'Manage', 'partyminder' ) ),
);

// Main content
ob_start();
?>

<!-- Success/Error Messages -->
<?php if ( isset( $success_message ) ) : ?>
	<div class="pm-alert pm-alert-success">
		<?php echo esc_html( $success_message ); ?>
	</div>
<?php endif; ?>

<?php if ( isset( $error_message ) ) : ?>
	<div class="pm-alert pm-alert-error">
		<?php echo esc_html( $error_message ); ?>
	</div>
<?php endif; ?>

<!-- Secondary Menu Bar -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<a href="?community_id=<?php echo $community_id; ?>&tab=settings" class="pm-btn <?php echo $current_tab === 'settings' ? '' : 'pm-btn'; ?>">
			<?php _e( 'Settings', 'partyminder' ); ?>
		</a>
		<a href="?community_id=<?php echo $community_id; ?>&tab=members" class="pm-btn <?php echo $current_tab === 'members' ? '' : 'pm-btn'; ?>">
			<?php _e( 'Members', 'partyminder' ); ?>
		</a>
		<a href="?community_id=<?php echo $community_id; ?>&tab=invitations" class="pm-btn <?php echo $current_tab === 'invitations' ? '' : 'pm-btn'; ?>">
			<?php _e( 'Invitations', 'partyminder' ); ?>
		</a>
		<a href="<?php echo esc_url( PartyMinder::get_community_url( $community->slug ) ); ?>" class="pm-btn">
			<?php _e( 'View Community', 'partyminder' ); ?>
		</a>
	</div>
</div>

<!-- Tab Content -->
<?php if ( $current_tab === 'settings' ) : ?>
<div class="pm-section">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Community Settings', 'partyminder' ); ?></h2>
	</div>
	
	<form method="post" class="pm-form" enctype="multipart/form-data">
		<input type="hidden" name="action" value="update_community_settings">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'partyminder_community_management' ); ?>">
		
		<div class="pm-form-group">
			<label class="pm-form-label">
				<?php _e( 'Community Name', 'partyminder' ); ?>
			</label>
			<input type="text" name="community_name" class="pm-form-input" value="<?php echo esc_attr( $community->name ); ?>">
		</div>
		
		<div class="pm-form-group">
			<label class="pm-form-label">
				<?php _e( 'Description', 'partyminder' ); ?>
			</label>
			<textarea name="description" class="pm-form-textarea" rows="4" 
						placeholder="<?php _e( 'Update community description...', 'partyminder' ); ?>"><?php echo esc_textarea( $community->description ); ?></textarea>
		</div>
		
		<!-- Cover Image Upload -->
		<div class="pm-form-group">
			<label class="pm-form-label"><?php _e( 'Cover Image', 'partyminder' ); ?></label>
			<input type="file" name="cover_image" class="pm-form-input" accept="image/*">
			<p class="pm-form-help pm-text-muted"><?php printf( __( 'Optional: Upload a cover image for this community (%s)', 'partyminder' ), PartyMinder_Settings::get_file_size_description() ); ?></p>
			
			<?php if ( ! empty( $community->featured_image ) ) : ?>
				<div class="pm-current-cover pm-mt-2">
					<p class="pm-text-muted pm-mb-2"><?php _e( 'Current cover image:', 'partyminder' ); ?></p>
					<img src="<?php echo esc_url( $community->featured_image ); ?>" alt="Current cover" style="max-width: 200px; height: auto; border-radius: 4px;">
					<label class="pm-mt-2">
						<input type="checkbox" name="remove_cover_image" value="1"> <?php _e( 'Remove current cover image', 'partyminder' ); ?>
					</label>
				</div>
			<?php endif; ?>
		</div>
		
		<div class="pm-form-group">
			<label class="pm-form-label">
				<?php _e( 'Privacy Setting', 'partyminder' ); ?>
			</label>
			<select name="visibility" class="pm-form-select">
				<option value="public" <?php selected( $community->visibility, 'public' ); ?>>
					<?php _e( 'Public - Anyone can join', 'partyminder' ); ?>
				</option>
				<option value="private" <?php selected( $community->visibility, 'private' ); ?>>
					<?php _e( 'Private - Invite only', 'partyminder' ); ?>
				</option>
			</select>
		</div>
		
		<button type="submit" class="pm-btn">
			<?php _e( 'Save Changes', 'partyminder' ); ?>
		</button>
	</form>
	
	<!-- Danger Zone -->
	<div class="pm-section pm-mt" style="border-top: 2px solid #dc3545; padding-top: 20px; margin-top: 40px;">
		<h4 class="pm-heading pm-heading-sm" style="color: #dc3545;"><?php _e( 'Danger Zone', 'partyminder' ); ?></h4>
		<p class="pm-text-muted pm-mb-4">
			<?php _e( 'Once you delete a community, there is no going back. This will permanently delete the community, all its members, events, and conversations.', 'partyminder' ); ?>
		</p>
		
		<form method="post" class="pm-form" id="delete-community-form" onsubmit="return confirmCommunityDeletion(event)">
			<input type="hidden" name="action" value="delete_community">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'partyminder_community_management' ); ?>">
			
			<div class="pm-form-group">
				<label class="pm-form-label" style="color: #dc3545;">
					<?php printf( __( 'Type "%s" to confirm deletion:', 'partyminder' ), esc_html( $community->name ) ); ?>
				</label>
				<input type="text" name="confirm_name" class="pm-form-input" id="delete-confirm-name" 
						placeholder="<?php echo esc_attr( $community->name ); ?>" required>
			</div>
			
			<button type="submit" class="pm-btn" style="background-color: #dc3545; border-color: #dc3545;" disabled id="delete-community-btn">
				<?php _e( 'Delete Community Permanently', 'partyminder' ); ?>
			</button>
		</form>
	</div>
</div>

<?php elseif ( $current_tab === 'members' ) : ?>
<div class="pm-section">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Community Members', 'partyminder' ); ?></h2>
	</div>
	<div id="members-list">
		<div class="pm-loading-placeholder">
			<p><?php _e( 'Loading community members...', 'partyminder' ); ?></p>
		</div>
	</div>
</div>

<?php elseif ( $current_tab === 'invitations' ) : ?>
<div class="pm-section">
	<div class="pm-section-header">
		<h2 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Send Invitations', 'partyminder' ); ?></h2>
	</div>

	<!-- Copyable Invitation Links -->
	<div class="pm-card pm-mb-4">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-sm"><?php _e( 'Share Community Link', 'partyminder' ); ?></h3>
		</div>
		<div class="pm-card-body">
			<p class="pm-text-muted pm-mb-4">
				<?php _e( 'Copy and share this link via text, social media, Discord, Slack, or any other platform.', 'partyminder' ); ?>
			</p>

			<div class="pm-form-group pm-mb-4">
				<label class="pm-form-label"><?php _e( 'Community Invitation Link', 'partyminder' ); ?></label>
				<div class="pm-flex pm-gap-2">
					<input type="text" class="pm-form-input pm-flex-1" id="invitation-link"
						value="<?php echo esc_attr( home_url( '/communities/' . $community->slug ) ); ?>"
						readonly>
					<button type="button" class="pm-btn pm-btn pm-copy-invitation-link">
						<?php _e( 'Copy', 'partyminder' ); ?>
					</button>
				</div>
			</div>

			<div class="pm-form-group">
				<label class="pm-form-label"><?php _e( 'Custom Message (Optional)', 'partyminder' ); ?></label>
				<textarea class="pm-form-textarea" id="custom-message" rows="3"
					placeholder="<?php _e( 'Add a personal message to include when sharing...', 'partyminder' ); ?>"></textarea>
				<div class="pm-mt-2">
					<button type="button" class="pm-btn pm-btn pm-copy-invitation-with-message">
						<?php _e( 'Copy Link with Message', 'partyminder' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Email Invitation Form -->
	<form id="send-invitation-form" class="pm-form">
		<div class="pm-form-group">
			<label class="pm-form-label">
				<?php _e( 'Email Address', 'partyminder' ); ?>
			</label>
			<input type="email" class="pm-form-input" id="invitation-email" 
					placeholder="<?php _e( 'Enter email address...', 'partyminder' ); ?>" required>
		</div>
		
		<div class="pm-form-group">
			<label class="pm-form-label">
				<?php _e( 'Personal Message (Optional)', 'partyminder' ); ?>
			</label>
			<textarea class="pm-form-textarea" id="invitation-message" rows="3"
						placeholder="<?php _e( 'Add a personal message to your invitation...', 'partyminder' ); ?>"></textarea>
		</div>
		
		<button type="submit" class="pm-btn">
			<?php _e( 'Send Invitation', 'partyminder' ); ?>
		</button>
	</form>

	<?php if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) : ?>
	<!-- Bluesky Invitations Section -->
	<div class="pm-mt">
		<h4><?php _e( 'Bluesky Invitations', 'partyminder' ); ?></h4>

		<div id="manage-bluesky-connection-section" class="pm-mb-4">
			<div id="manage-bluesky-not-connected" class="pm-card pm-card-info" style="border-left: 4px solid #1d9bf0;">
				<div class="pm-card-body">
					<h5 class="pm-heading pm-heading-sm pm-mb-4">
						<?php _e( 'Connect Bluesky for Easy Invites', 'partyminder' ); ?>
					</h5>
					<p class="pm-text-muted pm-mb-4">
						<?php _e( 'Connect your Bluesky account to invite your contacts to this community.', 'partyminder' ); ?>
					</p>
					<button type="button" class="pm-btn" id="manage-connect-bluesky-btn">
						<?php _e( 'Connect Bluesky Account', 'partyminder' ); ?>
					</button>
				</div>
			</div>

			<div id="manage-bluesky-connected" class="pm-card pm-card-success" style="border-left: 4px solid #10b981; display: none;">
				<div class="pm-card-body">
					<h5 class="pm-heading pm-heading-sm pm-mb-4">
						<?php _e( 'Bluesky Connected', 'partyminder' ); ?>
					</h5>
					<p class="pm-text-muted pm-mb-4">
						<?php _e( 'Connected as', 'partyminder' ); ?> <strong id="manage-bluesky-handle"></strong>
					</p>
					<div class="pm-flex pm-gap-2">
						<button type="button" class="pm-btn pm-btn-primary pm-btn-sm" id="create-invite-bluesky-btn">
							<?php _e( 'Invite from Bluesky', 'partyminder' ); ?>
						</button>
						<button type="button" class="pm-btn pm-btn-danger pm-btn-sm" id="manage-disconnect-bluesky-btn">
							<?php _e( 'Disconnect', 'partyminder' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="pm-mt">
		<h4><?php _e( 'Pending Invitations', 'partyminder' ); ?></h4>
		<div id="invitations-list">
			<div class="pm-loading-placeholder">
				<p><?php _e( 'Loading pending invitations...', 'partyminder' ); ?></p>
			</div>
		</div>
	</div>
</div>

<?php endif; ?>

<?php
// Enqueue scripts
$community_dependencies = array( 'jquery' );

// Enqueue shared BlueSky module if AT Protocol is enabled
if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) {
	wp_enqueue_script(
		'partyminder-bluesky-followers',
		PARTYMINDER_PLUGIN_URL . 'assets/js/bluesky-followers.js',
		array( 'jquery' ),
		PARTYMINDER_VERSION,
		true
	);
	$community_dependencies[] = 'partyminder-bluesky-followers';
}

// Enqueue manage-community script with localized data
wp_enqueue_script( 'partyminder-manage-community', PARTYMINDER_PLUGIN_URL . 'assets/js/manage-community.js', $community_dependencies, PARTYMINDER_VERSION, true );

// Localize script data
wp_localize_script( 'partyminder-manage-community', 'PartyMinderManageCommunity', array(
	'ajax_url' => admin_url( 'admin-ajax.php' ),
	'community_id' => intval( $community_id ),
	'current_tab' => esc_js( $current_tab ),
	'community_name' => esc_js( $community->name ),
	'community_slug' => esc_js( $community->slug ),
	'home_url' => home_url(),
	'community_nonce' => wp_create_nonce( 'partyminder_community_action' ),
	'at_protocol_enabled' => PartyMinder_Feature_Flags::is_at_protocol_enabled(),
	'at_protocol_nonce' => wp_create_nonce( 'partyminder_at_protocol' ),
	'strings' => array(
		'enter_email' => __( 'Please enter an email address.', 'partyminder' ),
		'sending' => __( 'Sending...', 'partyminder' ),
		'invitation_failed' => __( 'Failed to send invitation. Please try again.', 'partyminder' ),
		'network_error' => __( 'Network error. Please try again.', 'partyminder' ),
		'loading_members' => __( 'Loading community members...', 'partyminder' ),
		'no_members' => __( 'No members found.', 'partyminder' ),
		'error_loading_members' => __( 'Error loading members.', 'partyminder' ),
		'loading_invitations' => __( 'Loading pending invitations...', 'partyminder' ),
		'no_invitations' => __( 'No pending invitations.', 'partyminder' ),
		'error_loading_invitations' => __( 'Error loading invitations.', 'partyminder' ),
		'invited_on' => __( 'Invited on', 'partyminder' ),
		'expires' => __( 'Expires', 'partyminder' ),
		'pending' => __( 'pending', 'partyminder' ),
		'copy_invite' => __( 'Copy Invite', 'partyminder' ),
		'cancel' => __( 'Cancel', 'partyminder' ),
		'copied' => __( 'Copied!', 'partyminder' ),
		'copy_failed' => __( 'Failed to copy to clipboard', 'partyminder' ),
		'confirm_remove' => __( 'Are you sure you want to remove "%s" from this community?', 'partyminder' ),
		'confirm_cancel_invitation' => __( 'Are you sure you want to cancel the invitation to "%s"?', 'partyminder' ),
		'update_role_failed' => __( 'Failed to update member role.', 'partyminder' ),
		'remove_member_failed' => __( 'Failed to remove member.', 'partyminder' ),
		'cancel_invitation_failed' => __( 'Failed to cancel invitation.', 'partyminder' ),
		'connecting' => __( 'Connecting...', 'partyminder' ),
		'connection_failed' => __( 'Connection failed', 'partyminder' ),
		'connection_failed_network' => __( 'Connection failed. Please try again.', 'partyminder' ),
		'connect_account' => __( 'Connect Account', 'partyminder' ),
		'confirm_disconnect' => __( 'Are you sure you want to disconnect your BlueSky account?', 'partyminder' ),
		'disconnected_successfully' => __( 'BlueSky account disconnected successfully', 'partyminder' ),
		'disconnect_failed' => __( 'Failed to disconnect BlueSky account', 'partyminder' ),
		'select_followers' => __( 'Please select at least one follower to invite', 'partyminder' ),
		'sending_invitations' => __( 'Sending Invitations...', 'partyminder' ),
		'invitations_sent' => __( 'Invitations sent successfully!', 'partyminder' ),
		'invitations_failed' => __( 'Failed to send invitations', 'partyminder' ),
		'send_invitations' => __( 'Send Invitations', 'partyminder' ),
		'load_followers_failed' => __( 'Failed to load followers', 'partyminder' ),
		'network_error_followers' => __( 'Network error loading followers', 'partyminder' ),
		'no_followers' => __( 'No followers found to invite', 'partyminder' ),
		'confirm_delete' => __( 'Are you absolutely sure you want to delete "%s"?\n\nThis action cannot be undone. All community data, members, events, and conversations will be permanently deleted.', 'partyminder' ),
	)
) );
?>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>


<!-- Community Info -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php echo esc_html( $community->name ); ?></h3>
	</div>
	
	<?php if ( ! empty( $community->featured_image ) ) : ?>
		<div class="pm-mb-4">
			<img src="<?php echo esc_url( $community->featured_image ); ?>" alt="<?php echo esc_attr( $community->name ); ?>" style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px;">
		</div>
	<?php endif; ?>
	
	<?php if ( $community->description ) : ?>
		<p class="pm-text-muted pm-mb"><?php echo esc_html( $community->description ); ?></p>
	<?php endif; ?>
	
	<div class="pm-stat-list">
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Privacy', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo esc_html( ucfirst( $community->visibility ) ); ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Created', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo date( 'M j, Y', strtotime( $community->created_at ) ); ?></span>
		</div>
	</div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';

// Include BlueSky modals if AT Protocol is enabled
if ( PartyMinder_Feature_Flags::is_at_protocol_enabled() ) :
	include PARTYMINDER_PLUGIN_DIR . 'templates/partials/modal-bluesky-connect.php';
	include PARTYMINDER_PLUGIN_DIR . 'templates/partials/modal-bluesky-followers.php';
endif;
?>