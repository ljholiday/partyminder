<?php
/**
 * Profile Content Template - Unified System
 * User profile display and editing page using unified templates
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get user ID from query var or default to current user
$user_id         = get_query_var( 'user', get_current_user_id() );
$current_user_id = get_current_user_id();
$is_own_profile  = ( $user_id == $current_user_id );
$is_editing      = $is_own_profile && isset( $_GET['edit'] );

// Get WordPress user data
$user_data = get_userdata( $user_id );
if ( ! $user_data ) {
	echo '<div class="pm-section pm-text-center">';
	echo '<h3 class="pm-heading pm-heading-md">' . __( 'Profile Not Found', 'partyminder' ) . '</h3>';
	echo '<p class="pm-text-muted">' . __( 'The requested user profile could not be found.', 'partyminder' ) . '</p>';
	echo '</div>';
	return;
}

// Get PartyMinder profile data
$profile_data = PartyMinder_Profile_Manager::get_user_profile( $user_id );

// Handle profile form submission
$profile_updated = false;
$form_errors     = array();
if ( $is_own_profile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['partyminder_profile_nonce'] ) ) {
	if ( wp_verify_nonce( $_POST['partyminder_profile_nonce'], 'partyminder_profile_update' ) ) {
		$result = PartyMinder_Profile_Manager::update_profile( $user_id, $_POST );
		if ( $result['success'] ) {
			$profile_updated = true;
			// Refresh profile data
			$profile_data = PartyMinder_Profile_Manager::get_user_profile( $user_id );
		} else {
			$form_errors = $result['errors'];
		}
	}
}

// Set up template variables
$page_title       = $is_editing
	? __( 'Edit Profile', 'partyminder' )
	: $user_data->display_name;
$page_description = $is_editing
	? __( 'Update your information, preferences, and privacy settings', 'partyminder' )
	: sprintf( __( '%s\'s profile and activity', 'partyminder' ), $user_data->display_name );

$breadcrumbs = array(
	array(
		'title' => __( 'Dashboard', 'partyminder' ),
		'url'   => PartyMinder::get_dashboard_url(),
	),
	array( 'title' => __( 'Profile', 'partyminder' ) ),
);

// If editing, use form template
if ( $is_editing ) {
	// Main content for form
	ob_start();

	// Success message
	if ( $profile_updated || isset( $_GET['updated'] ) ) {
		echo '<div class="pm-alert pm-alert-success pm-mb-4">';
		echo '<h4 class="pm-heading pm-heading-sm">' . __( 'Profile Updated!', 'partyminder' ) . '</h4>';
		echo '<p>' . __( 'Your profile has been successfully updated.', 'partyminder' ) . '</p>';
		echo '<a href="' . esc_url( PartyMinder::get_profile_url() ) . '" class="pm-btn pm-btn-secondary">';
		echo '👤 ' . __( 'View Profile', 'partyminder' );
		echo '</a>';
		echo '</div>';
	}

	// Show errors if any
	if ( ! empty( $form_errors ) ) {
		echo '<div class="pm-alert pm-alert-error pm-mb-4">';
		echo '<h4 class="pm-heading pm-heading-sm">' . __( 'Please fix the following errors:', 'partyminder' ) . '</h4>';
		echo '<ul>';
		foreach ( $form_errors as $error ) {
			echo '<li>' . esc_html( $error ) . '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}
	?>

	<form method="post" class="pm-form" enctype="multipart/form-data">
		<?php wp_nonce_field( 'partyminder_profile_update', 'partyminder_profile_nonce' ); ?>
		
		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Basic Information', 'partyminder' ); ?></h3>
			
			<div class="pm-form-group">
				<label class="pm-form-label" for="display_name"><?php _e( 'Display Name *', 'partyminder' ); ?></label>
				<input type="text" 
						id="display_name" 
						name="display_name" 
						class="pm-form-input" 
						value="<?php echo esc_attr( $user_data->display_name ); ?>" 
						required>
			</div>
			
			<div class="pm-form-group">
				<label class="pm-form-label" for="bio"><?php _e( 'Bio', 'partyminder' ); ?></label>
				<textarea id="bio" 
							name="bio" 
							class="pm-form-textarea" 
							rows="4"
							placeholder="<?php _e( 'Tell people a bit about yourself...', 'partyminder' ); ?>"><?php echo esc_textarea( $profile_data['bio'] ?? '' ); ?></textarea>
			</div>
			
			<div class="pm-form-group">
				<label class="pm-form-label" for="location"><?php _e( 'Location', 'partyminder' ); ?></label>
				<input type="text" 
						id="location" 
						name="location" 
						class="pm-form-input" 
						value="<?php echo esc_attr( $profile_data['location'] ?? '' ); ?>" 
						placeholder="<?php _e( 'City, State/Country', 'partyminder' ); ?>">
			</div>
		</div>
		
		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Profile Images', 'partyminder' ); ?></h3>
			
			<div class="pm-form-row">
				<!-- Profile Photo Upload -->
				<div class="pm-form-group">
					<label class="pm-form-label"><?php _e( 'Profile Photo', 'partyminder' ); ?></label>
					<div class="pm-text-center pm-mb">
						<div class="pm-profile-avatar" style="width: 120px; height: 120px; margin: 0 auto;">
							<?php if ( ( $profile_data['avatar_source'] ?? 'gravatar' ) === 'custom' && ! empty( $profile_data['profile_image'] ) ) : ?>
							<img src="<?php echo esc_url( $profile_data['profile_image'] ); ?>" 
								style="width: 100%; height: 100%; object-fit: cover;" 
								alt="<?php _e( 'Profile photo', 'partyminder' ); ?>">
							<?php else : ?>
							<?php echo get_avatar( $user_id, 120, '', '', array( 'style' => 'width: 100%; height: 100%; object-fit: cover;' ) ); ?>
							<?php endif; ?>
						</div>
					</div>
					<p class="pm-form-help pm-text-muted pm-mb"><?php _e( 'Your profile photo appears throughout the site', 'partyminder' ); ?></p>
					
					<div class="pm-form-group">
						<label class="pm-form-label"><?php _e( 'Avatar Source', 'partyminder' ); ?></label>
						<div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
							<label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
								<input type="radio" name="avatar_source" value="gravatar" <?php checked( $profile_data['avatar_source'] ?? 'gravatar', 'gravatar' ); ?>>
								<span class="pm-btn pm-btn-secondary"><?php _e( 'Gravatar', 'partyminder' ); ?></span>
							</label>
							<label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
								<input type="radio" name="avatar_source" value="custom" <?php checked( $profile_data['avatar_source'] ?? 'gravatar', 'custom' ); ?>>
								<span class="pm-btn pm-btn-secondary"><?php _e( 'Custom Avatar', 'partyminder' ); ?></span>
							</label>
						</div>
					</div>
					
					<div class="pm-avatar-upload">
						<input type="file" id="avatar_upload" accept="image/*" style="display: none;">
						<button type="button" class="pm-btn pm-btn-secondary" onclick="document.getElementById('avatar_upload').click()">
							Upload Profile Photo
						</button>
						<div class="pm-upload-progress" style="display: none; margin-top: 10px;">
							<div class="pm-progress-bar">
								<div class="pm-progress-fill"></div>
							</div>
							<div class="pm-progress-text">0%</div>
						</div>
						<div class="pm-upload-message" style="margin-top: 10px;"></div>
					</div>
				</div>
				
				<!-- Cover Photo Upload -->
				<div class="pm-form-group">
					<label class="pm-form-label"><?php _e( 'Cover Photo', 'partyminder' ); ?></label>
					<div class="pm-text-center pm-mb">
						<div style="width: 200px; height: 80px; margin: 0 auto; border-radius: 0.5rem; overflow: hidden; border: 2px solid #e2e8f0;">
							<?php if ( ! empty( $profile_data['cover_image'] ) ) : ?>
							<img src="<?php echo esc_url( $profile_data['cover_image'] ); ?>" 
								style="width: 100%; height: 100%; object-fit: cover;" 
								alt="<?php _e( 'Cover photo preview', 'partyminder' ); ?>">
							<?php else : ?>
							<div style="width: 100%; height: 100%; background: linear-gradient(135deg, #3b82f6 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem;">
								<?php _e( 'No cover photo', 'partyminder' ); ?>
							</div>
							<?php endif; ?>
						</div>
					</div>
					<p class="pm-form-help pm-text-muted pm-mb"><?php _e( 'Your cover photo appears at the top of your profile', 'partyminder' ); ?></p>
					
					<div class="pm-cover-upload">
						<input type="file" id="cover_upload" accept="image/*" style="display: none;">
						<button type="button" class="pm-btn pm-btn-secondary" onclick="document.getElementById('cover_upload').click()">
							Upload Cover Photo
						</button>
						<div class="pm-upload-progress" style="display: none; margin-top: 10px;">
							<div class="pm-progress-bar">
								<div class="pm-progress-fill"></div>
							</div>
							<div class="pm-progress-text">0%</div>
						</div>
						<div class="pm-upload-message" style="margin-top: 10px;"></div>
					</div>
				</div>
			</div>
		</div>
		
		<div class="pm-form-actions">
			<button type="submit" class="pm-btn">
				<?php _e( 'Save Profile Info', 'partyminder' ); ?>
			</button>
			<a href="<?php echo esc_url( PartyMinder::get_profile_url() ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'View Profile', 'partyminder' ); ?>
			</a>
		</div>
	</form>


	<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Avatar upload
		document.getElementById('avatar_upload').addEventListener('change', function() {
			if (this.files.length > 0) {
				uploadImage(this.files[0], 'avatar', '.pm-avatar-upload');
			}
		});

		// Cover upload
		document.getElementById('cover_upload').addEventListener('change', function() {
			if (this.files.length > 0) {
				uploadImage(this.files[0], 'cover', '.pm-cover-upload');
			}
		});

		function uploadImage(file, type, containerSelector) {
			const container = document.querySelector(containerSelector);
			const progress = container.querySelector('.pm-upload-progress');
			const progressFill = container.querySelector('.pm-progress-fill');
			const progressText = container.querySelector('.pm-progress-text');
			const message = container.querySelector('.pm-upload-message');

			// Show progress
			progress.style.display = 'block';
			message.innerHTML = '';
			progressFill.style.width = '0%';
			progressText.textContent = '0%';

			// Create form data
			const formData = new FormData();
			formData.append(type, file);
			formData.append('action', 'partyminder_' + type + '_upload');
			if (type === 'avatar') {
				formData.append('nonce', partyminder_ajax.avatar_upload_nonce);
			} else {
				formData.append('nonce', partyminder_ajax.cover_upload_nonce);
			}

			// Upload
			const xhr = new XMLHttpRequest();

			xhr.upload.addEventListener('progress', function(e) {
				if (e.lengthComputable) {
					const percent = Math.round((e.loaded / e.total) * 100);
					progressFill.style.width = percent + '%';
					progressText.textContent = percent + '%';
				}
			});

			xhr.addEventListener('load', function() {
				progress.style.display = 'none';
				
				try {
					const response = JSON.parse(xhr.responseText);
					if (response.success) {
						message.innerHTML = response.data.message;
						message.className = 'pm-upload-message success';
						// Reload page to show new image
						setTimeout(function() { 
							window.location.reload(); 
						}, 1500);
					} else {
						message.innerHTML = response.data;
						message.className = 'pm-upload-message error';
					}
				} catch (e) {
					message.innerHTML = 'Upload failed';
					message.className = 'pm-upload-message error';
				}
			});

			xhr.open('POST', partyminder_ajax.ajax_url);
			xhr.send(formData);
		}

		// Handle avatar source radio button changes
		document.querySelectorAll('input[name="avatar_source"]').forEach(function(radio) {
			radio.addEventListener('change', function() {
				const avatarImages = document.querySelectorAll('.pm-profile-avatar img');
				const isCustom = this.value === 'custom';
				const hasCustomImage = <?php echo ! empty( $profile_data['profile_image'] ) ? 'true' : 'false'; ?>;
				
				avatarImages.forEach(function(img) {
					if (isCustom && hasCustomImage) {
						img.src = '<?php echo esc_js( $profile_data['profile_image'] ?? '' ); ?>';
					} else {
						img.src = '<?php echo esc_js( get_avatar_url( $user_id, array( 'size' => 120 ) ) ); ?>';
					}
				});
			});
		});

	});
	</script>

	<?php
	$content = ob_get_clean();

	// Include form template
	include PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php';

} else {
	// Profile view mode - use two-column template

	// Main content
	ob_start();
	?>
	
	<!-- Secondary Menu Bar -->
	<div class="pm-section pm-mb-4">
		<div class="pm-flex pm-gap-4 pm-flex-wrap">
			<?php if ( $is_own_profile ) : ?>
				<a href="<?php echo esc_url( PartyMinder::get_create_event_url() ); ?>" class="pm-btn">
					<?php _e( 'Create Event', 'partyminder' ); ?>
				</a>
				<a href="<?php echo add_query_arg( 'edit', '1', PartyMinder::get_profile_url() ); ?>" class="pm-btn pm-btn-secondary">
					<?php _e( 'Edit Profile', 'partyminder' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( PartyMinder::get_conversations_url() ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'Conversations', 'partyminder' ); ?>
			</a>
			<a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'Browse Events', 'partyminder' ); ?>
			</a>
			<a href="<?php echo esc_url( PartyMinder::get_dashboard_url() ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'Dashboard', 'partyminder' ); ?>
			</a>
		</div>
	</div>
	
	<?php
	// Profile Header Section - Modern competitive style
	$cover_photo = $profile_data['cover_image'] ?? '';
	$cover_photo_url = $cover_photo ? esc_url( $cover_photo ) : '';
	$avatar_url = get_avatar_url( $user_id, array( 'size' => 120 ) );
	
	// Debug: Check what we're getting
	// error_log('Cover photo data: ' . print_r($cover_photo, true));
	// error_log('Cover photo URL: ' . $cover_photo_url);
	?>
	
	<!-- Modern Profile Header -->
	<section class="pm-profile-header-modern pm-mb">
		<!-- Banner -->
		<div class="pm-profile-cover pm-profile-banner">
			<?php if ( $cover_photo_url ) : ?>
				<img id="pm-banner-img" 
					 src="<?php echo $cover_photo_url; ?>" 
					 alt="<?php esc_attr_e( 'Profile banner', 'partyminder' ); ?>"
					 style="width: 100%; height: 100%; object-fit: cover;">
			<?php else : ?>
				<div class="pm-flex pm-flex-center pm-text-center" style="height: 100%; background: linear-gradient(135deg, var(--pm-primary) 0%, #764ba2 100%);"></div>
			<?php endif; ?>
		</div>
		
		<!-- Avatar + Right content row -->
		<div class="pm-flex pm-flex-between pm-avatar-row">
			<div id="pm-avatar" 
				 class="pm-profile-avatar pm-avatar-modern" 
				 role="img"
				 aria-label="<?php echo esc_attr( $user_data->display_name ); ?>">
				<?php if ( ( $profile_data['avatar_source'] ?? 'gravatar' ) === 'custom' && ! empty( $profile_data['profile_image'] ) ) : ?>
				<img src="<?php echo esc_url( $profile_data['profile_image'] ); ?>" alt="<?php echo esc_attr( $user_data->display_name ); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
				<?php else : ?>
				<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $user_data->display_name ); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
				<?php endif; ?>
			</div>
			
			<?php if ( ! $is_own_profile ) : ?>
			<div class="pm-flex pm-gap">
				<button class="pm-btn" onclick="alert('Follow functionality coming soon!')">
					<?php _e( 'Follow', 'partyminder' ); ?>
				</button>
			</div>
			<?php endif; ?>
		</div>
		
		<!-- Identity/text row -->
		<div class="pm-profile-identity">
			<h1 class="pm-heading pm-heading-xl pm-mb"><?php echo esc_html( $user_data->display_name ); ?></h1>
			<div class="pm-text-muted pm-mb">@<?php echo esc_html( $user_data->user_login ); ?></div>
			
			<?php if ( ! empty( $profile_data['bio'] ) ) : ?>
			<div class="pm-mb"><?php echo esc_html( $profile_data['bio'] ); ?></div>
			<?php endif; ?>
			
			<div class="pm-flex pm-flex-wrap pm-gap pm-text-muted">
				<?php if ( ! empty( $profile_data['location'] ) ) : ?>
				<span><?php echo esc_html( $profile_data['location'] ); ?></span>
				<?php endif; ?>
				<span><?php printf( __( 'Joined %s', 'partyminder' ), date( 'M Y', strtotime( $user_data->user_registered ) ) ); ?></span>
			</div>
		</div>
	</section>

	<div class="pm-section">
		<div class="pm-section-header">
			<h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Activity Stats', 'partyminder' ); ?></h3>
		</div>
		
		<?php
		// Get user activity stats
		global $wpdb;
		$events_table        = $wpdb->prefix . 'partyminder_events';
		$conversations_table = $wpdb->prefix . 'partyminder_conversations';

		$events_created = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $events_table WHERE author_id = %d AND event_status = 'active'",
				$user_id
			)
		);

		$conversations_started = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $conversations_table WHERE author_id = %d",
				$user_id
			)
		);
		?>
		
		<div class="pm-grid pm-grid-3 pm-gap">
			<div class="pm-text-center">
				<div class="pm-stat-number pm-text-primary"><?php echo intval( $events_created ); ?></div>
				<div class="pm-stat-label"><?php _e( 'Events Created', 'partyminder' ); ?></div>
			</div>
			<div class="pm-text-center">
				<div class="pm-stat-number pm-text-primary"><?php echo intval( $conversations_started ); ?></div>
				<div class="pm-stat-label"><?php _e( 'Conversations Started', 'partyminder' ); ?></div>
			</div>
			<div class="pm-text-center">
				<div class="pm-stat-number pm-text-primary"><?php echo rand( 5, 25 ); ?></div>
				<div class="pm-stat-label"><?php _e( 'Events Attended', 'partyminder' ); ?></div>
			</div>
		</div>
	</div>

	<?php
	$main_content = ob_get_clean();

	// Sidebar content
	ob_start();
	?>
	

	<div class="pm-section pm-mb">
		<div class="pm-section-header">
			<h3 class="pm-heading pm-heading-sm"> <?php _e( 'Community Stats', 'partyminder' ); ?></h3>
		</div>
		<div class="pm-stat-list">
			<div class="pm-stat-item">
				<span class="pm-stat-label"><?php _e( 'Member Level', 'partyminder' ); ?></span>
				<span class="pm-stat-value"><?php _e( 'Active Host', 'partyminder' ); ?></span>
			</div>
			<div class="pm-stat-item">
				<span class="pm-stat-label"><?php _e( 'Reputation', 'partyminder' ); ?></span>
				<span class="pm-stat-value"><?php echo rand( 85, 98 ); ?>%</span>
			</div>
		</div>
	</div>
	
	<?php
	$sidebar_content = ob_get_clean();

	// Set template variables for two-column layout
	$page_title = $user_data->display_name;
	$page_description = sprintf( __( '%s\'s profile and activity', 'partyminder' ), $user_data->display_name );
	$breadcrumbs = array(
		array(
			'title' => __( 'Dashboard', 'partyminder' ),
			'url'   => PartyMinder::get_dashboard_url(),
		),
		array( 'title' => __( 'Profile', 'partyminder' ) ),
	);

	// Include two-column template
	include PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
}
?>
