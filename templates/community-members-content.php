<?php
/**
 * Community Members Content Template
 * Members view for individual community
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

$community_manager = new PartyMinder_Community_Manager();

// Get community slug from URL
$community_slug = get_query_var( 'community_slug' );
if ( ! $community_slug ) {
	wp_redirect( PartyMinder::get_communities_url() );
	exit;
}

// Get community
$community = $community_manager->get_community_by_slug( $community_slug );
if ( ! $community ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	return;
}

// Get current user info
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$is_member    = false;
$user_role    = null;

if ( $is_logged_in ) {
	$is_member = $community_manager->is_member( $community->id, $current_user->ID );
	$user_role = $community_manager->get_member_role( $community->id, $current_user->ID );
}

// Check if user can view members
$can_view_members = true;
if ( $community->privacy === 'private' && ! $is_member ) {
	$can_view_members = false;
}

// Get community members (if allowed to view)
$members      = array();
$member_count = 0;
if ( $can_view_members ) {
	$members      = $community_manager->get_community_members( $community->id, 50 ); // Limit to 50 for now
	$member_count = is_array( $members ) ? count( $members ) : 0;
}

// Get styling options
$primary_color   = get_option( 'partyminder_primary_color', '#667eea' );
$secondary_color = get_option( 'partyminder_secondary_color', '#764ba2' );

// Set up template variables
$page_title       = __( 'Community Members', 'partyminder' );
$page_description = sprintf( __( '%1$s - %2$d Members', 'partyminder' ), esc_html( $community->name ), $member_count );

// Breadcrumbs
$breadcrumbs = array(
	array(
		'title' => __( 'Communities', 'partyminder' ),
		'url'   => PartyMinder::get_communities_url(),
	),
	array(
		'title' => esc_html( $community->name ),
		'url'   => home_url( '/communities/' . $community->slug ),
	),
	array( 'title' => __( 'Members', 'partyminder' ) ),
);

// No navigation tabs needed - using sidebar buttons instead

// Main content
ob_start();
?>

<!-- Secondary Menu Bar -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( '← Back to Community', 'partyminder' ); ?>
		</a>
	</div>
</div>

<div class="partyminder-community-members">

	<!-- Members Content -->
	<div class="members-content">
		<?php if ( ! $can_view_members ) : ?>
			<!-- Private Community - No Access -->
			<div class="no-access">
				<h3><?php _e( 'Private Community', 'partyminder' ); ?></h3>
				<p><?php _e( 'This community\'s member list is private. You need to be a member to view other members.', 'partyminder' ); ?></p>
				
				<?php if ( ! $is_logged_in ) : ?>
					<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="pm-btn">
						<?php _e( 'Login to Join', 'partyminder' ); ?>
					</a>
				<?php else : ?>
					<a href="#" class="btn join-community-btn" data-community-id="<?php echo esc_attr( $community->id ); ?>">
						<?php _e( 'Join Community', 'partyminder' ); ?>
					</a>
				<?php endif; ?>
			</div>
			
		<?php elseif ( empty( $members ) ) : ?>
			<!-- No Members Yet -->
			<div class="no-members">
				<h3><?php _e( ' No Members Yet', 'partyminder' ); ?></h3>
				<p><?php _e( 'This community is just getting started. Be the first to join!', 'partyminder' ); ?></p>
				
				<?php if ( ! $is_logged_in ) : ?>
					<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="pm-btn">
						<?php _e( 'Login to Join', 'partyminder' ); ?>
					</a>
				<?php elseif ( ! $is_member ) : ?>
					<a href="#" class="btn join-community-btn" data-community-id="<?php echo esc_attr( $community->id ); ?>">
						<?php _e( 'Join Community', 'partyminder' ); ?>
					</a>
				<?php endif; ?>
			</div>
			
		<?php else : ?>
			<?php
			// Get member counts for filtering functionality
			$admin_count          = count(
				array_filter(
					$members,
					function ( $m ) {
						return $m->role === 'admin';
					}
				)
			);
			$moderator_count      = count(
				array_filter(
					$members,
					function ( $m ) {
						return $m->role === 'moderator';
					}
				)
			);
			$member_count_regular = count(
				array_filter(
					$members,
					function ( $m ) {
						return $m->role === 'member';
					}
				)
			);
			?>
			
			<?php if ( $is_member && $user_role === 'admin' ) : ?>
			<!-- Admin Invite Section -->
			<div class="pm-card pm-mb-4">
				<div class="pm-card-header">
					<h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Invite New Members', 'partyminder' ); ?></h3>
				</div>
				<div class="pm-card-body">
					<form id="invite-member-form" class="pm-form">
						<div class="pm-grid pm-grid-2 pm-gap-4">
							<div class="pm-form-group">
								<label class="pm-form-label"><?php _e( 'Email Address', 'partyminder' ); ?></label>
								<input type="email" class="pm-form-input" id="invite-email" placeholder="<?php _e( 'Enter email address...', 'partyminder' ); ?>" required>
							</div>
							<div class="pm-form-group">
								<label class="pm-form-label"><?php _e( 'Member Role', 'partyminder' ); ?></label>
								<select class="pm-form-select" id="invite-role">
									<option value="member"><?php _e( 'Member', 'partyminder' ); ?></option>
									<option value="moderator"><?php _e( 'Moderator', 'partyminder' ); ?></option>
								</select>
							</div>
						</div>
						
						<div class="pm-form-group">
							<label class="pm-form-label"><?php _e( 'Personal Message (Optional)', 'partyminder' ); ?></label>
							<textarea class="pm-form-textarea" id="invite-message" rows="3" placeholder="<?php _e( 'Add a personal welcome message...', 'partyminder' ); ?>"></textarea>
						</div>
						
						<div class="pm-flex pm-gap-4">
							<button type="submit" class="pm-btn"><?php _e( 'Send Invitation', 'partyminder' ); ?></button>
							<div class="pm-text-muted pm-flex-1">
								<small><?php _e( 'BlueSky contact integration coming soon!', 'partyminder' ); ?></small>
							</div>
						</div>
					</form>
				</div>
			</div>
			<?php endif; ?>

			<!-- Member Directory -->
			<div class="pm-card">
				<div class="pm-card-header">
					<div class="pm-flex pm-flex-between">
						<h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Community Members', 'partyminder' ); ?></h3>
						<div class="pm-flex pm-gap-4">
							<select id="member-filter" class="pm-form-select">
								<option value="all"><?php _e( 'All Members', 'partyminder' ); ?></option>
								<option value="admin"><?php _e( 'Admins', 'partyminder' ); ?></option>
								<?php
								if ( $moderator_count > 0 ) :
									?>
									<option value="moderator"><?php _e( 'Moderators', 'partyminder' ); ?></option><?php endif; ?>
								<option value="member"><?php _e( 'Regular Members', 'partyminder' ); ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="pm-card-body">
					<div class="pm-grid pm-grid-auto pm-gap-4" id="members-list">
						<?php foreach ( $members as $member ) : ?>
							<div class="pm-card member-card" data-role="<?php echo esc_attr( $member->role ); ?>">
								<div class="pm-card-body">
									<div class="pm-flex pm-gap-4 pm-mb-4">
										<!-- Member Avatar -->
										<div class="pm-member-avatar-lg">
											<?php if ( ! empty( $member->profile_image ) ) : ?>
												<img src="<?php echo esc_url( $member->profile_image ); ?>" alt="<?php echo esc_attr( $member->display_name ); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
											<?php else : ?>
												<div style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold;">
													<?php echo strtoupper( substr( $member->display_name, 0, 2 ) ); ?>
												</div>
											<?php endif; ?>
										</div>
										
										<!-- Member Info -->
										<div class="pm-flex-1">
											<h4 class="pm-heading pm-heading-sm pm-mb-2"><?php echo esc_html( $member->display_name ); ?></h4>
											
											<div class="pm-flex pm-gap-4 pm-mb-2">
												<span class="pm-badge pm-badge-<?php echo $member->role === 'admin' ? 'primary' : ( $member->role === 'moderator' ? 'warning' : 'secondary' ); ?>">
													<?php echo esc_html( ucfirst( $member->role ) ); ?>
												</span>
												<span class="pm-text-muted">
													<?php printf( __( 'Joined %s', 'partyminder' ), date( 'M j, Y', strtotime( $member->joined_at ) ) ); ?>
												</span>
											</div>
											
											<?php if ( ! empty( $member->bio ) ) : ?>
												<p class="pm-text-muted pm-mb-2"><?php echo esc_html( wp_trim_words( $member->bio, 20 ) ); ?></p>
											<?php endif; ?>
											
											<?php if ( ! empty( $member->location ) ) : ?>
												<div class="pm-text-muted">
													<span><?php echo esc_html( $member->location ); ?></span>
												</div>
											<?php endif; ?>
										</div>
									</div>
									
									<!-- Member Stats -->
									<?php if ( isset( $member->events_hosted ) || isset( $member->events_attended ) ) : ?>
									<div class="pm-flex pm-gap-4 pm-text-center">
										<?php if ( isset( $member->events_hosted ) && $member->events_hosted > 0 ) : ?>
											<div class="pm-flex-1">
												<div class="pm-stat-number pm-text-primary"><?php echo $member->events_hosted; ?></div>
												<div class="pm-stat-label"><?php _e( 'Events Hosted', 'partyminder' ); ?></div>
											</div>
										<?php endif; ?>
										
										<?php if ( isset( $member->events_attended ) && $member->events_attended > 0 ) : ?>
											<div class="pm-flex-1">
												<div class="pm-stat-number pm-text-primary"><?php echo $member->events_attended; ?></div>
												<div class="pm-stat-label"><?php _e( 'Events Attended', 'partyminder' ); ?></div>
											</div>
										<?php endif; ?>
										
										<?php if ( isset( $member->host_rating ) && $member->host_rating > 0 ) : ?>
											<div class="pm-flex-1">
												<div class="pm-stat-number pm-text-primary"><?php echo number_format( $member->host_rating, 1 ); ?></div>
												<div class="pm-stat-label"><?php _e( 'Host Rating', 'partyminder' ); ?></div>
											</div>
										<?php endif; ?>
									</div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					
					<?php if ( count( $members ) >= 50 ) : ?>
						<div class="pm-text-center pm-mt-4">
							<p class="pm-text-muted">
								<?php _e( 'Showing first 50 members. Load more functionality coming soon.', 'partyminder' ); ?>
							</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<!-- Community Stats -->
<div class="pm-card">
	<div class="pm-card-header">
		<h3 class="pm-heading pm-heading-md pm-text-primary"><?php echo esc_html( $community->name ); ?></h3>
	</div>
	<div class="pm-card-body">
		<div class="pm-flex pm-gap-4">
			<div class="pm-stat pm-text-center">
				<div class="pm-stat-number pm-text-primary"><?php echo count( $members ); ?></div>
				<div class="pm-stat-label pm-text-muted"><?php _e( 'Members', 'partyminder' ); ?></div>
			</div>
			<div class="pm-stat pm-text-center">
				<div class="pm-stat-number pm-text-primary"><?php echo esc_html( ucfirst( $community->privacy ) ); ?></div>
				<div class="pm-stat-label pm-text-muted"><?php _e( 'Privacy', 'partyminder' ); ?></div>
			</div>
		</div>
	</div>
</div>
<?php
$sidebar_content = ob_get_clean();

// Include base template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Member filter functionality
	const memberFilter = document.getElementById('member-filter');
	const memberCards = document.querySelectorAll('.member-card');
	
	if (memberFilter) {
		memberFilter.addEventListener('change', function() {
			const selectedRole = this.value;
			
			memberCards.forEach(card => {
				const cardRole = card.getAttribute('data-role');
				if (selectedRole === 'all' || cardRole === selectedRole) {
					card.style.display = 'block';
				} else {
					card.style.display = 'none';
				}
			});
		});
	}
	
	// Invite member form
	const inviteForm = document.getElementById('invite-member-form');
	if (inviteForm) {
		inviteForm.addEventListener('submit', function(e) {
			e.preventDefault();
			
			const email = document.getElementById('invite-email').value.trim();
			const role = document.getElementById('invite-role').value;
			const message = document.getElementById('invite-message').value.trim();
			
			if (!email) {
				alert('<?php _e( 'Please enter an email address.', 'partyminder' ); ?>');
				return;
			}
			
			const submitBtn = inviteForm.querySelector('button[type="submit"]');
			const originalText = submitBtn.textContent;
			
			// Show loading state
			submitBtn.disabled = true;
			submitBtn.textContent = '<?php _e( 'Sending...', 'partyminder' ); ?>';
			
			// Make AJAX request
			jQuery.ajax({
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				type: 'POST',
				data: {
					action: 'partyminder_invite_community_member',
					community_id: <?php echo $community->id; ?>,
					email: email,
					role: role,
					message: message,
					nonce: '<?php echo wp_create_nonce( 'partyminder_community_action' ); ?>'
				},
				success: function(response) {
					if (response.success) {
						// Clear form
						inviteForm.reset();
						
						// Show success message
						const successMsg = document.createElement('div');
						successMsg.className = 'pm-alert pm-alert-success pm-mb-4';
						successMsg.textContent = response.data.message || '<?php _e( 'Invitation sent successfully!', 'partyminder' ); ?>';
						inviteForm.parentNode.insertBefore(successMsg, inviteForm);
						
						// Remove success message after 5 seconds
						setTimeout(() => {
							if (successMsg.parentNode) {
								successMsg.parentNode.removeChild(successMsg);
							}
						}, 5000);
					} else {
						alert(response.data || '<?php _e( 'Failed to send invitation.', 'partyminder' ); ?>');
					}
				},
				error: function() {
					alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
				},
				complete: function() {
					// Restore button
					submitBtn.disabled = false;
					submitBtn.textContent = originalText;
				}
			});
		});
	}
	
	// Join community button with AJAX (for non-members)
	const joinBtn = document.querySelector('.join-community-btn');
	if (joinBtn) {
		joinBtn.addEventListener('click', function(e) {
			e.preventDefault();
			
			const communityId = this.getAttribute('data-community-id');
			const communityName = '<?php echo esc_js( $community->name ); ?>';
			
			if (!confirm('<?php _e( 'Join community', 'partyminder' ); ?> "' + communityName + '"?')) {
				return;
			}
			
			// Show loading state
			const originalText = this.innerHTML;
			this.innerHTML = '<?php _e( 'Joining...', 'partyminder' ); ?>';
			this.disabled = true;
			
			// Make AJAX request
			jQuery.ajax({
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				type: 'POST',
				data: {
					action: 'partyminder_join_community',
					community_id: communityId,
					nonce: '<?php echo wp_create_nonce( 'partyminder_community_action' ); ?>'
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						window.location.reload();
					} else {
						alert(response.data || '<?php _e( 'Error joining community', 'partyminder' ); ?>');
						joinBtn.innerHTML = originalText;
						joinBtn.disabled = false;
					}
				},
				error: function() {
					alert('<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>');
					joinBtn.innerHTML = originalText;
					joinBtn.disabled = false;
				}
			});
		});
	}
});
</script>
