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
	$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
	if ( ! in_array( $file['type'], $allowed_types ) ) {
		return new WP_Error( 'invalid_file_type', __( 'Only JPG, PNG, GIF, and WebP images are allowed.', 'partyminder' ) );
	}

	if ( $file['size'] > 5 * 1024 * 1024 ) { // 5MB limit
		return new WP_Error( 'file_too_large', __( 'File size must be less than 5MB.', 'partyminder' ) );
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
		<a href="<?php echo esc_url( PartyMinder::get_community_url( $community->slug ) ); ?>" class="pm-btn pm-btn">
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
			<input type="text" class="pm-form-input" value="<?php echo esc_attr( $community->name ); ?>" readonly>
			<div class="pm-form-help">
				<?php _e( 'Contact site administrator to change the community name', 'partyminder' ); ?>
			</div>
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
			<p class="pm-form-help pm-text-muted"><?php _e( 'Optional: Upload a cover image for this community (JPG, PNG, max 5MB)', 'partyminder' ); ?></p>
			
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
					<?php _e( '🌍 Public - Anyone can join', 'partyminder' ); ?>
				</option>
				<option value="private" <?php selected( $community->visibility, 'private' ); ?>>
					<?php _e( '🔒 Private - Invite only', 'partyminder' ); ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
	const communityId = <?php echo intval( $community_id ); ?>;
	const currentTab = '<?php echo esc_js( $current_tab ); ?>';
	
	// Load appropriate tab content based on current tab
	if (currentTab === 'members') {
		loadCommunityMembers(communityId);
	} else if (currentTab === 'invitations') {
		loadCommunityInvitations(communityId);
	}
	
	// Handle delete community form
	const deleteConfirmInput = document.getElementById('delete-confirm-name');
	const deleteBtn = document.getElementById('delete-community-btn');
	const communityName = '<?php echo esc_js( $community->name ); ?>';
	
	if (deleteConfirmInput && deleteBtn) {
		deleteConfirmInput.addEventListener('input', function() {
			deleteBtn.disabled = this.value !== communityName;
		});
	}
	
	// Handle invitation form submission
	const invitationForm = document.getElementById('send-invitation-form');
	if (invitationForm) {
		invitationForm.addEventListener('submit', function(e) {
			e.preventDefault();
			
			const email = document.getElementById('invitation-email').value;
			const message = document.getElementById('invitation-message').value;
			
			if (!email) {
				alert('<?php _e( 'Please enter an email address.', 'partyminder' ); ?>');
				return;
			}
			
			const submitBtn = this.querySelector('button[type="submit"]');
			const originalText = submitBtn.textContent;
			submitBtn.textContent = '<?php _e( 'Sending...', 'partyminder' ); ?>';
			submitBtn.disabled = true;
			
			jQuery.ajax({
				url: partyminder_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'partyminder_send_invitation',
					community_id: communityId,
					email: email,
					message: message,
					nonce: partyminder_ajax.community_nonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						// Clear form
						document.getElementById('invitation-email').value = '';
						document.getElementById('invitation-message').value = '';
						// Reload invitations list if we're on that tab
						if (currentTab === 'invitations') {
							loadCommunityInvitations(communityId);
						}
					} else {
						alert(response.data || '<?php _e( 'Failed to send invitation. Please try again.', 'partyminder' ); ?>');
					}
					submitBtn.textContent = originalText;
					submitBtn.disabled = false;
				},
				error: function() {
					alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
					submitBtn.textContent = originalText;
					submitBtn.disabled = false;
				}
			});
		});
	}
	
	// Load community members
	function loadCommunityMembers(communityId) {
		const membersList = document.getElementById('members-list');
		if (!membersList) return;
		
		membersList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'Loading community members...', 'partyminder' ); ?></p></div>';
		
		jQuery.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_community_members',
				community_id: communityId,
				nonce: partyminder_ajax.community_nonce
			},
			success: function(response) {
				if (response.success && response.data.members) {
					renderMembersList(response.data.members);
				} else {
					membersList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'No members found.', 'partyminder' ); ?></p></div>';
				}
			},
			error: function() {
				membersList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'Error loading members.', 'partyminder' ); ?></p></div>';
			}
		});
	}
	
	// Load community invitations
	function loadCommunityInvitations(communityId) {
		const invitationsList = document.getElementById('invitations-list');
		if (!invitationsList) return;
		
		invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'Loading pending invitations...', 'partyminder' ); ?></p></div>';
		
		jQuery.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_get_community_invitations',
				community_id: communityId,
				nonce: partyminder_ajax.community_nonce
			},
			success: function(response) {
				if (response.success && response.data.invitations) {
					renderInvitationsList(response.data.invitations);
				} else {
					invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'No pending invitations.', 'partyminder' ); ?></p></div>';
				}
			},
			error: function() {
				invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'Error loading invitations.', 'partyminder' ); ?></p></div>';
			}
		});
	}
	
	// Render members list
	function renderMembersList(members) {
		const membersList = document.getElementById('members-list');
		
		if (!members || members.length === 0) {
			membersList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'No members found.', 'partyminder' ); ?></p></div>';
			return;
		}
		
		let html = '<div class="pm-grid pm-grid-2 pm-gap">';
		members.forEach(member => {
			const initials = member.display_name ? member.display_name.substring(0, 2).toUpperCase() : 'U';
			const joinedDate = new Date(member.joined_at).toLocaleDateString();
			
			html += `
				<div class="pm-section" data-member-id="${member.id}">
					<div class="pm-flex pm-flex-between pm-mb-4">
						<h4 class="pm-heading pm-heading-sm">${member.display_name || member.email}</h4>
						<span class="pm-badge pm-badge-${member.role === 'admin' ? 'primary' : 'secondary'}">${member.role}</span>
					</div>
					
					<div class="pm-mb-4">
						<div class="pm-flex pm-gap pm-mb-4">
							<span class="pm-text-muted"><?php _e( 'Member since', 'partyminder' ); ?> ${joinedDate}</span>
						</div>
					</div>
					
					<div class="pm-flex pm-flex-between" style="align-items: center; min-height: 40px;">
						<div class="pm-flex pm-gap-4">
							${member.role === 'member' ? 
								'<button class="pm-btn pm-btn promote-btn" data-member-id="' + member.id + '"><?php _e( 'Promote', 'partyminder' ); ?></button>' : 
								(member.role === 'admin' ? '<button class="pm-btn pm-btn demote-btn" data-member-id="' + member.id + '"><?php _e( 'Demote', 'partyminder' ); ?></button>' : '')
							}
						</div>
						<button class="pm-btn pm-btn-danger remove-btn" data-member-id="${member.id}" data-member-name="${member.display_name || member.email}">
							<?php _e( 'Remove', 'partyminder' ); ?>
						</button>
					</div>
				</div>
			`;
		});
		html += '</div>';
		
		membersList.innerHTML = html;
		
		// Add event listeners for member actions
		attachMemberActionListeners();
	}
	
	// Render invitations list
	function renderInvitationsList(invitations) {
		const invitationsList = document.getElementById('invitations-list');
		
		if (!invitations || invitations.length === 0) {
			invitationsList.innerHTML = '<div class="pm-loading-placeholder"><p><?php _e( 'No pending invitations.', 'partyminder' ); ?></p></div>';
			return;
		}
		
		let html = '<div class="pm-invitation-list">';
		invitations.forEach(invitation => {
			const createdDate = new Date(invitation.created_at).toLocaleDateString();
			const expiresDate = new Date(invitation.expires_at).toLocaleDateString();
			
			html += `
				<div class="pm-invitation-item" data-invitation-id="${invitation.id}">
					<div class="pm-invitation-info">
						<div class="pm-invitation-details">
							<h4>${invitation.invited_email}</h4>
							<small><?php _e( 'Invited on', 'partyminder' ); ?> ${createdDate}</small>
							<br><small><?php _e( 'Expires', 'partyminder' ); ?> ${expiresDate}</small>
							${invitation.message ? '<br><small><em>"' + invitation.message + '"</em></small>' : ''}
						</div>
					</div>
					<div class="pm-invitation-actions">
						<span class="pm-member-role pending"><?php _e( 'pending', 'partyminder' ); ?></span>
						<button class="pm-btn copy-invitation-btn" data-invitation-token="${invitation.invitation_token}" data-community-id="${invitation.community_id}">
							<?php _e( 'Copy Invite', 'partyminder' ); ?>
						</button>
						<button class="pm-btn pm-btn-danger cancel-invitation-btn" data-invitation-id="${invitation.id}" data-email="${invitation.invited_email}">
							<?php _e( 'Cancel', 'partyminder' ); ?>
						</button>
					</div>
				</div>
			`;
		});
		html += '</div>';
		
		invitationsList.innerHTML = html;
		
		// Add event listeners for invitation actions
		attachInvitationActionListeners();
	}
	
	// Attach event listeners for member actions
	function attachMemberActionListeners() {
		// Promote buttons
		document.querySelectorAll('.promote-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const memberId = this.getAttribute('data-member-id');
				updateMemberRole(memberId, 'admin');
			});
		});
		
		// Demote buttons
		document.querySelectorAll('.demote-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const memberId = this.getAttribute('data-member-id');
				updateMemberRole(memberId, 'member');
			});
		});
		
		// Remove buttons
		document.querySelectorAll('.remove-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const memberId = this.getAttribute('data-member-id');
				const memberName = this.getAttribute('data-member-name');
				
				if (confirm('<?php _e( 'Are you sure you want to remove', 'partyminder' ); ?> "' + memberName + '" <?php _e( 'from this community?', 'partyminder' ); ?>')) {
					removeMember(memberId);
				}
			});
		});
	}
	
	// Attach event listeners for invitation actions
	function attachInvitationActionListeners() {
		// Copy invitation buttons
		document.querySelectorAll('.copy-invitation-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const token = this.getAttribute('data-invitation-token');
				const communityId = this.getAttribute('data-community-id');
				
				// Get community slug from the current URL or community data
				const communitySlug = '<?php echo esc_js( $community->slug ); ?>';
				const invitationUrl = '<?php echo home_url(); ?>/communities/' + communitySlug + '?invitation=' + token + '&community=' + communityId;
				
				// Copy to clipboard
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(invitationUrl).then(() => {
						// Change button text temporarily
						const originalText = this.textContent;
						this.textContent = '<?php _e( 'Copied!', 'partyminder' ); ?>';
						setTimeout(() => {
							this.textContent = originalText;
						}, 2000);
					}).catch(err => {
						console.error('Failed to copy: ', err);
						alert('<?php _e( 'Failed to copy to clipboard', 'partyminder' ); ?>');
					});
				} else {
					// Fallback for older browsers
					const textArea = document.createElement('textarea');
					textArea.value = invitationUrl;
					document.body.appendChild(textArea);
					textArea.focus();
					textArea.select();
					try {
						document.execCommand('copy');
						const originalText = this.textContent;
						this.textContent = '<?php _e( 'Copied!', 'partyminder' ); ?>';
						setTimeout(() => {
							this.textContent = originalText;
						}, 2000);
					} catch (err) {
						console.error('Fallback copy failed: ', err);
						alert('<?php _e( 'Failed to copy to clipboard', 'partyminder' ); ?>');
					}
					document.body.removeChild(textArea);
				}
			});
		});
		
		// Cancel invitation buttons
		document.querySelectorAll('.cancel-invitation-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const invitationId = this.getAttribute('data-invitation-id');
				const email = this.getAttribute('data-email');
				
				if (confirm('<?php _e( 'Are you sure you want to cancel the invitation to', 'partyminder' ); ?> "' + email + '"?')) {
					cancelInvitation(invitationId);
				}
			});
		});
	}
	
	// Update member role
	function updateMemberRole(memberId, newRole) {
		jQuery.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_update_member_role',
				community_id: communityId,
				member_id: memberId,
				new_role: newRole,
				nonce: partyminder_ajax.community_nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					// Reload members list
					loadCommunityMembers(communityId);
				} else {
					alert(response.data || '<?php _e( 'Failed to update member role.', 'partyminder' ); ?>');
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
			}
		});
	}
	
	// Remove member
	function removeMember(memberId) {
		jQuery.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_remove_member',
				community_id: communityId,
				member_id: memberId,
				nonce: partyminder_ajax.community_nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					// Reload members list
					loadCommunityMembers(communityId);
				} else {
					alert(response.data || '<?php _e( 'Failed to remove member.', 'partyminder' ); ?>');
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
			}
		});
	}
	
	// Cancel invitation
	function cancelInvitation(invitationId) {
		jQuery.ajax({
			url: partyminder_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'partyminder_cancel_invitation',
				community_id: communityId,
				invitation_id: invitationId,
				nonce: partyminder_ajax.community_nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					// Reload invitations list
					loadCommunityInvitations(communityId);
				} else {
					alert(response.data || '<?php _e( 'Failed to cancel invitation.', 'partyminder' ); ?>');
				}
			},
			error: function() {
				alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
			}
		});
	}
	
	// Community deletion confirmation
	window.confirmCommunityDeletion = function(event) {
		const communityName = '<?php echo esc_js( $community->name ); ?>';
		return confirm('<?php _e( 'Are you absolutely sure you want to delete', 'partyminder' ); ?> "' + communityName + '"?\n\n<?php _e( 'This action cannot be undone. All community data, members, events, and conversations will be permanently deleted.', 'partyminder' ); ?>');
	};
});
</script>

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
			<span class="pm-stat-value"><?php echo esc_html( ucfirst( $community->privacy ) ); ?></span>
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
?>