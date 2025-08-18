<?php
/**
 * My Communities Content Template
 * User's community memberships using unified two-column template
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
$user_email   = '';

// Check if user provided email via URL parameter
if ( isset( $_GET['email'] ) && is_email( $_GET['email'] ) ) {
	$user_email = sanitize_email( $_GET['email'] );
} elseif ( is_user_logged_in() ) {
	$user_email = $current_user->user_email;
}

// Get user's communities (if logged in)
$user_communities = array();
if ( is_user_logged_in() ) {
	$user_communities = $community_manager->get_user_communities( $current_user->ID );
}

// Set up template variables
$page_title       = is_user_logged_in()
	? sprintf( __( 'My Communities - %s', 'partyminder' ), $current_user->display_name )
	: __( 'My Communities', 'partyminder' );
$page_description = __( 'Communities you\'ve joined and your role in each community', 'partyminder' );
$breadcrumbs      = array(
	array(
		'title' => __( 'Dashboard', 'partyminder' ),
		'url'   => PartyMinder::get_dashboard_url(),
	),
	array(
		'title' => __( 'Communities', 'partyminder' ),
		'url'   => PartyMinder::get_communities_url(),
	),
	array( 'title' => __( 'My Communities', 'partyminder' ) ),
);

// Main content
ob_start();
?>

<!-- Secondary Menu Bar -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<?php if ( is_user_logged_in() ) : ?>
			<?php if ( PartyMinder_Feature_Flags::can_user_create_community() ) : ?>
				<a href="<?php echo esc_url( site_url( '/create-community' ) ); ?>" class="pm-btn">
					<?php _e( 'Create Community', 'partyminder' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo PartyMinder::get_create_event_url(); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'Create Event', 'partyminder' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( add_query_arg( 'redirect_to', get_permalink( get_the_ID() ), PartyMinder::get_login_url() ) ); ?>" class="pm-btn">
				<?php _e( 'Login', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
		<a href="<?php echo PartyMinder::get_communities_url(); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Browse Communities', 'partyminder' ); ?>
		</a>
		<a href="<?php echo esc_url( PartyMinder::get_dashboard_url() ); ?>" class="pm-btn pm-btn-secondary">
			<?php _e( 'Dashboard', 'partyminder' ); ?>
		</a>
	</div>
</div>

<!-- Login Prompt for non-logged-in users -->
<?php if ( ! is_user_logged_in() ) : ?>
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Login to See Your Communities', 'partyminder' ); ?></h3>
	</div>
	<p class="pm-text-muted pm-mb"><?php _e( 'Log in to see communities you\'ve joined and manage your memberships.', 'partyminder' ); ?></p>
	<a href="<?php echo esc_url( add_query_arg( 'redirect_to', get_permalink( get_the_ID() ), PartyMinder::get_login_url() ) ); ?>" class="pm-btn">
		<?php _e( 'Login', 'partyminder' ); ?>
	</a>
</div>

<?php elseif ( ! empty( $user_communities ) ) : ?>
<!-- User Communities Section -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<div class="pm-flex pm-flex-between pm-flex-wrap pm-gap">
			<h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Your Communities', 'partyminder' ); ?></h3>
			<span class="pm-badge pm-badge-success"><?php echo count( $user_communities ); ?></span>
		</div>
	</div>
	
	<div class="pm-grid pm-grid-1 pm-gap">
		<?php foreach ( $user_communities as $community ) : ?>
			<div class="pm-section pm-p-4">
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
							• <?php echo (int) $community->member_count; ?> <?php _e( 'members', 'partyminder' ); ?>
							• <?php echo (int) $community->event_count; ?> <?php _e( 'events', 'partyminder' ); ?>
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
						<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-btn pm-btn-secondary">
							<?php _e( 'View', 'partyminder' ); ?>
						</a>
						<?php if ( $community->role === 'admin' ) : ?>
							<a href="<?php echo esc_url( site_url( '/manage-community?community_id=' . $community->id . '&tab=overview' ) ); ?>" class="pm-btn pm-btn-secondary">
								<?php _e( 'Manage', 'partyminder' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<?php else : ?>
<!-- No Communities Message -->
<div class="pm-section pm-text-center">
	<div class="pm-text-6xl pm-mb"></div>
	<h3 class="pm-heading pm-heading-md pm-mb"><?php _e( 'No Communities Found', 'partyminder' ); ?></h3>
	<p class="pm-text-muted pm-mb"><?php _e( 'You haven\'t joined any communities yet.', 'partyminder' ); ?></p>
	<div class="pm-flex pm-gap pm-justify-center">
		<a href="<?php echo PartyMinder::get_communities_url(); ?>" class="pm-btn">
			<?php _e( 'Browse Communities', 'partyminder' ); ?>
		</a>
		<?php if ( PartyMinder_Feature_Flags::can_user_create_community() ) : ?>
			<a href="<?php echo esc_url( site_url( '/create-community' ) ); ?>" class="pm-btn pm-btn-secondary">
				<?php _e( 'Create Community', 'partyminder' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>

<!-- Community Stats -->
<div class="pm-section pm-mb">
	<div class="pm-section-header">
		<h3 class="pm-heading pm-heading-sm"><?php _e( 'Summary', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-stat-list">
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
	</div>
</div>

<!-- Community Benefits -->
<div class="pm-section pm-mb">
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