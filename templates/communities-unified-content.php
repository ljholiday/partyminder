<?php
/**
 * Communities Unified Content Template
 * Main communities page with tab-based filtering (like events and conversations pages)
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';

$community_manager = new PartyMinder_Community_Manager();

// Get current user info
$current_user = wp_get_current_user();
$user_logged_in = is_user_logged_in();
$user_email = $user_logged_in ? $current_user->user_email : '';

// Get data for both tabs
$public_communities = $community_manager->get_public_communities( 20 );
$user_communities = array();

if ( $user_logged_in ) {
	$user_communities = $community_manager->get_user_communities( $current_user->ID );
}

// Set up template variables
$page_title       = __( 'Communities', 'partyminder' );
$page_description = __( 'Join communities of fellow hosts and guests to plan amazing events together', 'partyminder' );

// Main content
ob_start();
?>

<!-- Success Message for Community Deletion -->
<?php if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == '1' ) : ?>
	<div class="pm-alert pm-alert-success pm-mb-4">
		<?php _e( 'Community has been successfully deleted.', 'partyminder' ); ?>
	</div>
<?php endif; ?>

<!-- Community Filters/Tabs -->
<?php if ( $user_logged_in ) : ?>
<div class="pm-section pm-mb-4">
	<div class="pm-conversations-nav pm-flex pm-gap-4 pm-flex-wrap">
		<!-- Community Type Filters -->
		<button class="pm-btn pm-btn is-active" data-filter="my-communities" role="tab" aria-selected="true" aria-controls="pm-communities-list">
			<?php _e( 'My Communities', 'partyminder' ); ?>
		</button>
		<button class="pm-btn pm-btn" data-filter="all-communities" role="tab" aria-selected="false" aria-controls="pm-communities-list">
			<?php _e( 'All Communities', 'partyminder' ); ?>
		</button>
	</div>
</div>
<?php endif; ?>

<div class="pm-section">
	<div id="pm-communities-list" class="pm-grid pm-gap">
		<?php if ( $user_logged_in ) : ?>
			<!-- My Communities Tab Content (Default) -->
			<div class="pm-communities-tab-content" data-tab="my-communities">
				<?php if ( ! empty( $user_communities ) ) : ?>
					<?php foreach ( $user_communities as $community ) : ?>
						<div class="pm-section pm-border pm-p-4">
							<?php if ( ! empty( $community->featured_image ) ) : ?>
								<div class="pm-mb-4">
									<img src="<?php echo esc_url( $community->featured_image ); ?>" alt="<?php echo esc_attr( $community->name ); ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px;">
								</div>
							<?php endif; ?>
							<div class="pm-flex pm-flex-between pm-mb-4">
								<div class="pm-flex-1">
									<h3 class="pm-heading pm-heading-sm pm-mb-2">
										<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-text-primary">
											<?php echo esc_html( $community->name ); ?>
										</a>
									</h3>
									<div class="pm-flex pm-gap pm-flex-wrap pm-mb-2">
										<span class="pm-badge pm-badge-<?php echo $community->role === 'admin' ? 'primary' : 'success'; ?>">
											<?php echo esc_html( ucfirst( $community->role ) ); ?>
										</span>
										<?php if ( $community->privacy === 'private' ) : ?>
											<span class="pm-badge pm-badge-secondary"><?php _e( 'Private', 'partyminder' ); ?></span>
										<?php endif; ?>
									</div>
									<div class="pm-text-muted">
										<?php printf( __( 'Joined %s ago', 'partyminder' ), human_time_diff( strtotime( $community->joined_at ), current_time( 'timestamp' ) ) ); ?>
									</div>
								</div>
								<div class="pm-stat pm-text-center">
									<div class="pm-stat-number pm-text-primary"><?php echo (int) $community->event_count; ?></div>
									<div class="pm-stat-label"><?php _e( 'Events', 'partyminder' ); ?></div>
								</div>
							</div>
							
							<?php if ( $community->description ) : ?>
							<div class="pm-mb-4">
								<p class="pm-text-muted"><?php echo esc_html( wp_trim_words( $community->description, 15 ) ); ?></p>
							</div>
							<?php endif; ?>
							
							<div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
								<div class="pm-flex pm-gap pm-flex-wrap">
									<div class="pm-stat pm-text-center">
										<div class="pm-stat-number pm-text-primary"><?php echo (int) $community->member_count; ?></div>
										<div class="pm-stat-label"><?php _e( 'Members', 'partyminder' ); ?></div>
									</div>
								</div>
								
								<div class="pm-flex pm-gap">
									<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-btn pm-btn">
										<?php _e( 'View', 'partyminder' ); ?>
									</a>
									<?php if ( $community->role === 'admin' ) : ?>
										<a href="<?php echo esc_url( site_url( '/manage-community?community_id=' . $community->id . '&tab=settings' ) ); ?>" class="pm-btn pm-btn">
											<?php _e( 'Manage', 'partyminder' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="pm-text-center pm-p-4">
						<p class="pm-text-muted pm-mb-4"><?php _e( 'You haven\'t joined any communities yet.', 'partyminder' ); ?></p>
						<div class="pm-flex pm-gap pm-justify-center">
							<a href="<?php echo PartyMinder::get_communities_url(); ?>" class="pm-btn">
								<?php _e( 'Browse Communities', 'partyminder' ); ?>
							</a>
							<?php if ( PartyMinder_Feature_Flags::can_user_create_community() ) : ?>
								<a href="<?php echo esc_url( site_url( '/create-community' ) ); ?>" class="pm-btn pm-btn">
									<?php _e( 'Create Community', 'partyminder' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- All Communities Tab Content -->
		<div class="pm-communities-tab-content" data-tab="all-communities" <?php echo $user_logged_in ? 'style="display: none;"' : ''; ?>>
			<?php if ( ! empty( $public_communities ) ) : ?>
				<?php foreach ( $public_communities as $community ) : ?>
				<div class="pm-section pm-border pm-p-4">
					<?php if ( ! empty( $community->featured_image ) ) : ?>
						<div class="pm-mb-4">
							<img src="<?php echo esc_url( $community->featured_image ); ?>" alt="<?php echo esc_attr( $community->name ); ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px;">
						</div>
					<?php endif; ?>
					<div class="pm-section-header pm-flex pm-flex-between pm-mb-4">
						<h3 class="pm-heading pm-heading-sm">
							<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-text-primary">
								<?php echo esc_html( $community->name ); ?>
							</a>
						</h3>
						<div class="pm-badge pm-badge-<?php echo $community->privacy === 'public' ? 'success' : 'secondary'; ?>">
							<?php echo esc_html( ucfirst( $community->privacy ) ); ?>
						</div>
					</div>
							<div class="pm-mb-4">
								<div class="pm-flex pm-gap">
									<span class="pm-text-muted"><?php echo (int) $community->member_count; ?> <?php _e( 'members', 'partyminder' ); ?></span>
								</div>
							</div>
						
						<?php if ( $community->description ) : ?>
					<div class="pm-mb-4">
						<p class="pm-text-muted"><?php echo esc_html( wp_trim_words( $community->description, 20 ) ); ?></p>
					</div>
					<?php endif; ?>
					
					<div class="pm-flex pm-flex-between pm-mt-4">
						<div class="pm-stat">
							<div class="pm-stat-number pm-text-primary"><?php echo (int) $community->event_count; ?></div>
							<div class="pm-text-muted"><?php _e( 'Events', 'partyminder' ); ?></div>
						</div>
						
						<?php if ( $user_logged_in ) : ?>
							<?php
							$is_member = $community_manager->is_member( $community->id, $current_user->ID );
							?>
							<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" 
								class="pm-btn <?php echo $is_member ? 'pm-btn' : ''; ?>">
								<?php echo $is_member ? __( 'Member', 'partyminder' ) : __( 'Join', 'partyminder' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo add_query_arg( 'redirect_to', urlencode( $_SERVER['REQUEST_URI'] ), PartyMinder::get_login_url() ); ?>" class="pm-btn">
								<?php _e( 'Login to Join', 'partyminder' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="pm-text-center pm-p-4">
					<p class="pm-text-muted pm-mb-4"><?php _e( 'No public communities yet.', 'partyminder' ); ?></p>
					<p class="pm-text-muted"><?php _e( 'Be the first to create a community!', 'partyminder' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
$main_content = ob_get_clean();

// Get recent activity data
global $wpdb;
$events_table = $wpdb->prefix . 'partyminder_events';
$communities_table = $wpdb->prefix . 'partyminder_communities';
$conversations_table = $wpdb->prefix . 'partyminder_conversations';

// Get recent community events
$recent_events = $wpdb->get_results( $wpdb->prepare(
	"SELECT e.id, e.title, e.slug, e.event_date, e.created_at, c.name as community_name, c.slug as community_slug
	 FROM $events_table e 
	 INNER JOIN $communities_table c ON e.community_id = c.id
	 WHERE c.privacy = 'public' AND e.event_status = 'active'
	 ORDER BY e.created_at DESC 
	 LIMIT %d", 5
) );

// Get recent community conversations
$recent_conversations = $wpdb->get_results( $wpdb->prepare(
	"SELECT conv.id, conv.title, conv.slug, conv.created_at, conv.reply_count, c.name as community_name, c.slug as community_slug
	 FROM $conversations_table conv
	 INNER JOIN $communities_table c ON conv.community_id = c.id
	 WHERE c.privacy = 'public' AND conv.privacy = 'public'
	 ORDER BY conv.created_at DESC 
	 LIMIT %d", 5
) );

// Sidebar content
ob_start();
?>

<!-- Community Stats -->
<div class="pm-section pm-mb-4">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Summary', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-stat-list">
		<?php if ( $user_logged_in ) : ?>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Communities Joined', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo count( $user_communities ); ?></span>
		</div>
		<?php if ( ! empty( $user_communities ) ) : ?>
		<?php
		$admin_count = 0;
		$member_count = 0;
		$total_events = 0;
		foreach ( $user_communities as $community ) {
			if ( $community->role === 'admin' ) {
				$admin_count++;
			} else {
				$member_count++;
			}
			$total_events += (int) $community->event_count;
		}
		?>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Admin Role', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo $admin_count; ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Member Role', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo $member_count; ?></span>
		</div>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Total Events', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo $total_events; ?></span>
		</div>
		<?php endif; ?>
		<?php endif; ?>
		<div class="pm-stat-item">
			<span class="pm-stat-label"><?php _e( 'Public Communities', 'partyminder' ); ?></span>
			<span class="pm-stat-value"><?php echo count( $public_communities ); ?></span>
		</div>
	</div>
</div>

<!-- Community Benefits -->
<div class="pm-section pm-mb-4">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Community Benefits', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-text-muted">
		<p class="pm-mb-2"><?php _e( 'Connect with like-minded people in your communities.', 'partyminder' ); ?></p>
		<p class="pm-mb-2"><?php _e( 'Discover events organized by community members.', 'partyminder' ); ?></p>
		<p class="pm-mb-2"><?php _e( 'Share your own events with engaged audiences.', 'partyminder' ); ?></p>
		<p><?php _e( 'Build lasting relationships through shared interests.', 'partyminder' ); ?></p>
	</div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Tab functionality for communities
	const communityTabs = document.querySelectorAll('[data-filter]');
	const communityTabContents = document.querySelectorAll('.pm-communities-tab-content');
	
	// Initialize tab functionality
	function initCommunityTabs() {
		communityTabs.forEach(tab => {
			tab.addEventListener('click', function() {
				const filter = this.getAttribute('data-filter');
				
				// Update active tab
				communityTabs.forEach(t => {
					t.classList.remove('is-active');
					t.setAttribute('aria-selected', 'false');
				});
				this.classList.add('is-active');
				this.setAttribute('aria-selected', 'true');
				
				// Show/hide content
				communityTabContents.forEach(content => {
					const tab = content.getAttribute('data-tab');
					content.style.display = (tab === filter) ? '' : 'none';
				});
			});
		});
	}
	
	// Initialize tabs if they exist
	if (communityTabs.length > 0) {
		initCommunityTabs();
	}
	
	// Join community functionality
	const joinBtns = document.querySelectorAll('.join-btn');
	joinBtns.forEach(btn => {
		btn.addEventListener('click', function(e) {
			if (this.classList.contains('member')) {
				return; // Already a member, just redirect
			}
			
			e.preventDefault();
			
			// Check if user is logged in
			if (!partyminder_ajax.current_user.id) {
				return; // Let the login redirect happen
			}
			
			const communityCard = this.closest('.community-card');
			const communityName = communityCard.querySelector('h3 a').textContent;
			
			if (!confirm(partyminder_ajax.strings.confirm_join + ' "' + communityName + '"?')) {
				return;
			}
			
			// Get community ID from URL
			const communityUrl = communityCard.querySelector('h3 a').href;
			const urlParts = communityUrl.split('/');
			const communitySlug = urlParts[urlParts.length - 2] || urlParts[urlParts.length - 1];
			
			// For now, we'll redirect to the community page
			// In Phase 3, this will be proper AJAX
			window.location.href = communityUrl;
		});
	});
});
</script>