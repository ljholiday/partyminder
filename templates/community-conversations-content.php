<?php
/**
 * Community Conversations Content Template
 * Conversations view for individual community - uses two-column layout
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';

$community_manager    = new PartyMinder_Community_Manager();
$conversation_manager = new PartyMinder_Conversation_Manager();

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

// Check if user can view conversations
$can_view_conversations = true;
if ( $community->visibility === 'private' && ! $is_member ) {
	$can_view_conversations = false;
}

// Get pagination parameters
$page     = max( 1, intval( $_GET['paged'] ?? 1 ) );
$per_page = 10;
$offset   = ( $page - 1 ) * $per_page;

// Get community conversations
$community_conversations = array();
$total_conversations     = 0;
if ( $can_view_conversations ) {
	$community_conversations = $conversation_manager->get_community_conversations( $community->id, $per_page );
	// Get total count for pagination
	global $wpdb;
	$conversations_table = $wpdb->prefix . 'partyminder_conversations';
	$total_conversations = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $conversations_table WHERE community_id = %d",
			$community->id
		)
	);
}

// Set up template variables
$page_title       = sprintf( __( 'Conversations in %s', 'partyminder' ), esc_html( $community->name ) );
$page_description = __( 'Community discussions and conversations', 'partyminder' );
$breadcrumbs      = array(
	array(
		'title' => 'Communities',
		'url'   => PartyMinder::get_communities_url(),
	),
	array(
		'title' => $community->name,
		'url'   => home_url( '/communities/' . $community->slug ),
	),
	array( 'title' => 'Conversations' ),
);
$nav_items        = array(
	array(
		'title' => 'Overview',
		'url'   => home_url( '/communities/' . $community->slug ),
	),
	array(
		'title'  => 'Conversations',
		'url'    => home_url( '/communities/' . $community->slug . '/conversations' ),
		'active' => true,
	),
	array(
		'title' => 'Events',
		'url'   => home_url( '/communities/' . $community->slug . '/events' ),
	),
	array(
		'title' => 'Members',
		'url'   => home_url( '/manage-community/?community_id=' . $community->id . '&tab=members' ),
	),
);

// Main content
ob_start();
?>

<?php if ( ! $can_view_conversations ) : ?>
	<div class="pm-section pm-mb">
		<div class="pm-card">
			<div class="pm-card-body pm-text-center">
				<h3 class="pm-heading pm-heading-md pm-mb-4"><?php _e( 'Private Community', 'partyminder' ); ?></h3>
				<p class="pm-text-muted pm-mb-4">
					<?php _e( 'This is a private community. You need to join to view conversations.', 'partyminder' ); ?>
				</p>
				<?php if ( $is_logged_in ) : ?>
					<a href="#" class="pm-btn join-community-btn" data-community-id="<?php echo esc_attr( $community->id ); ?>">
						<?php _e( 'Request to Join', 'partyminder' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="pm-btn">
						<?php _e( 'Login to Request Access', 'partyminder' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php else : ?>
	<!-- Community Conversations Section -->
	<div class="pm-section pm-mb">
		<div class="pm-card">
			<div class="pm-card-header">
				<div class="pm-flex pm-flex-between">
					<div>
						<h2 class="pm-heading pm-heading-md">Community Conversations</h2>
						<p class="pm-text-muted">
							<?php printf( _n( '%d conversation', '%d conversations', $total_conversations, 'partyminder' ), $total_conversations ); ?>
						</p>
					</div>
					<?php if ( $is_member ) : ?>
						<div>
							<a href="<?php echo esc_url( site_url( '/create-conversation?community_id=' . $community->id ) ); ?>" class="pm-btn">
								<?php _e( 'Start Conversation', 'partyminder' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
			
			<div class="pm-card-body">
				<?php if ( ! empty( $community_conversations ) ) : ?>
					<div class="pm-conversations-list">
						<?php foreach ( $community_conversations as $conversation ) : ?>
							<div class="pm-section pm-flex pm-flex-between">
								<div class="pm-flex-1">
									<div class="pm-flex pm-gap pm-mb-2">
										<?php if ( $conversation->is_pinned ) : ?>
											<span class="pm-badge pm-badge-secondary">Pinned</span>
										<?php endif; ?>
										<span class="pm-badge pm-badge-primary">
											<?php echo esc_html( $conversation->topic_name ?? 'General' ); ?>
										</span>
									</div>
									
									<h3 class="pm-heading pm-heading-sm pm-mb-2">
										<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" 
											class="pm-text-primary">
											<?php echo esc_html( $conversation->title ); ?>
										</a>
									</h3>
									
									<div class="pm-text-muted pm-mb-2">
										<?php echo wp_trim_words( strip_tags( $conversation->content ), 20 ); ?>
									</div>
									
									<div class="pm-conversation-meta pm-text-muted">
										<span>Started by</span> <?php PartyMinder_Member_Display::member_display( $conversation->author_id, array( 'avatar_size' => 24, 'show_avatar' => false ) ); ?>
										<span>•</span>
										<span><?php echo human_time_diff( strtotime( $conversation->created_at ), current_time( 'timestamp' ) ) . ' ago'; ?></span>
										<?php if ( $conversation->last_reply_date != $conversation->created_at ) : ?>
											<span>•</span>
											<span>Last reply <?php echo human_time_diff( strtotime( $conversation->last_reply_date ), current_time( 'timestamp' ) ) . ' ago'; ?></span>
											<span>by <strong><?php echo esc_html( $conversation->last_reply_author ); ?></strong></span>
										<?php endif; ?>
									</div>
								</div>
								
								<div class="pm-conversation-stats pm-text-center pm-ml-4">
									<div class="pm-stat-number pm-text-primary"><?php echo intval( $conversation->reply_count ); ?></div>
									<div class="pm-stat-label"><?php _e( 'replies', 'partyminder' ); ?></div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					
					<?php if ( $total_conversations > $per_page ) : ?>
						<!-- Pagination -->
						<div class="pm-pagination pm-text-center pm-mt-4">
							<?php
							$total_pages = ceil( $total_conversations / $per_page );
							$base_url    = home_url( '/communities/' . $community->slug . '/conversations' );

							if ( $page > 1 ) :
								?>
								<a href="<?php echo $base_url . '?paged=' . ( $page - 1 ); ?>" class="pm-btn pm-btn">
									<?php _e( '← Previous', 'partyminder' ); ?>
								</a>
							<?php endif; ?>
							
							<span class="pm-pagination-info">
								<?php printf( __( 'Page %1$d of %2$d', 'partyminder' ), $page, $total_pages ); ?>
							</span>
							
							<?php if ( $page < $total_pages ) : ?>
								<a href="<?php echo $base_url . '?paged=' . ( $page + 1 ); ?>" class="pm-btn pm-btn">
									<?php _e( 'Next →', 'partyminder' ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					
				<?php else : ?>
					<div class="pm-text-center pm-p-4">
						<h3 class="pm-heading pm-heading-sm pm-mb-4"><?php _e( 'No Conversations Yet', 'partyminder' ); ?></h3>
						<p class="pm-text-muted pm-mb-4">
							<?php _e( 'This community hasn\'t started any conversations yet.', 'partyminder' ); ?>
						</p>
						<?php if ( $is_member ) : ?>
							<a href="<?php echo esc_url( site_url( '/create-conversation?community_id=' . $community->id ) ); ?>" class="pm-btn">
								<?php _e( 'Start First Conversation', 'partyminder' ); ?>
							</a>
						<?php else : ?>
							<p class="pm-text-muted"><?php _e( 'Join this community to start conversations!', 'partyminder' ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php
$main_content = ob_get_clean();

// Sidebar content
ob_start();
?>


<!-- Community Navigation -->
<div class="pm-card pm-mb-4">
	<div class="pm-card-header">
		<h3 class="pm-heading pm-heading-md pm-text-primary"><?php _e( 'Community Pages', 'partyminder' ); ?></h3>
	</div>
	<div class="pm-card-body">
		<div class="pm-flex pm-flex-column pm-gap-4">
			<a href="<?php echo home_url( '/communities/' . $community->slug ); ?>" class="pm-btn pm-btn">
				<?php _e( 'Overview', 'partyminder' ); ?>
			</a>
			<a href="<?php echo home_url( '/communities/' . $community->slug . '/events' ); ?>" class="pm-btn pm-btn">
				<?php _e( 'Events', 'partyminder' ); ?>
			</a>
			<a href="<?php echo home_url( '/manage-community/?community_id=' . $community->id . '&tab=members' ); ?>" class="pm-btn pm-btn">
				<?php _e( 'Members', 'partyminder' ); ?>
			</a>
			<a href="<?php echo home_url( '/communities/' . $community->slug . '/conversations' ); ?>" class="pm-btn pm-btn-primary">
				<?php _e( 'Conversations', 'partyminder' ); ?>
			</a>
		</div>
	</div>
</div>

<div class="pm-section pm-mb">
	<div class="pm-card">
		<div class="pm-card-header">
			<h3 class="pm-heading pm-heading-md"><?php echo esc_html( $community->name ); ?></h3>
		</div>
		<div class="pm-card-body">
		<?php if ( $community->description ) : ?>
			<p class="pm-text-muted pm-mb-4"><?php echo esc_html( $community->description ); ?></p>
		<?php endif; ?>
		
		<div class="pm-flex pm-flex-column pm-gap-4">
			<div>
				<strong class="pm-text-primary"><?php _e( 'Members:', 'partyminder' ); ?></strong><br>
				<span class="pm-text-muted"><?php echo $community->member_count ?? 0; ?></span>
			</div>
			<div>
				<strong class="pm-text-primary"><?php _e( 'Conversations:', 'partyminder' ); ?></strong><br>
				<span class="pm-text-muted"><?php echo $total_conversations; ?></span>
			</div>
			<div>
				<strong class="pm-text-primary"><?php _e( 'Privacy:', 'partyminder' ); ?></strong><br>
				<span class="pm-text-muted"><?php echo esc_html( ucfirst( $community->visibility ) ); ?></span>
			</div>
		</div>
	</div>
</div>

<?php
$sidebar_content = ob_get_clean();

// Include two-column template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-two-column.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Join community button with AJAX (reuse from single-community-content.php)
	const joinBtn = document.querySelector('.join-community-btn');
	if (joinBtn) {
		joinBtn.addEventListener('click', function(e) {
			e.preventDefault();
			
			const communityId = this.getAttribute('data-community-id');
			const communityName = '<?php echo esc_js( $community->name ); ?>';
			
			if (!confirm('Are you sure you want to join "' + communityName + '"?')) {
				return;
			}
			
			// Show loading state
			const originalText = this.innerHTML;
			this.innerHTML = 'Loading...';
			this.disabled = true;
			
			// Make AJAX request
			jQuery.ajax({
				url: partyminder_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'partyminder_join_community',
					community_id: communityId,
					nonce: partyminder_ajax.community_nonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						window.location.reload();
					} else {
						alert(response.data || 'Error occurred');
						joinBtn.innerHTML = originalText;
						joinBtn.disabled = false;
					}
				},
				error: function() {
					alert('Error occurred');
					joinBtn.innerHTML = originalText;
					joinBtn.disabled = false;
				}
			});
		});
	}
});
</script>