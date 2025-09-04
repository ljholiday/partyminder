<?php
/**
 * Base Two-Column Template
 * Main content + sidebar layout for listings and detailed views
 *
 * Variables expected:
 * - $page_title (string)
 * - $page_description (string, optional)
 * - $breadcrumbs (array, optional)
 * - $main_content (string) - main content area
 * - $sidebar_content (string) - sidebar content area
 * - $nav_items (array, optional) - navigation tabs
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php if ( ! empty( $breadcrumbs ) ) : ?>
<!-- Breadcrumbs -->
<div class="pm-text-muted mb-4" style="font-size: 18px; font-weight: 500;">
	<?php
	$breadcrumb_parts = array();
	foreach ( $breadcrumbs as $crumb ) {
		if ( isset( $crumb['url'] ) ) {
			$breadcrumb_parts[] = '<a href="' . esc_url( $crumb['url'] ) . '" class="pm-text-primary">' . esc_html( $crumb['title'] ) . '</a>';
		} else {
			$breadcrumb_parts[] = '<span>' . esc_html( $crumb['title'] ) . '</span>';
		}
	}
	echo implode( ' › ', $breadcrumb_parts );
	?>
</div>
<?php endif; ?>


<?php if ( ! empty( $nav_items ) ) : ?>
<!-- Navigation - Spans Full Width -->
<div class="pm-nav">
	<?php foreach ( $nav_items as $nav_item ) : ?>
		<a href="<?php echo esc_url( $nav_item['url'] ); ?>" 
			class="pm-nav-item <?php echo ! empty( $nav_item['active'] ) ? 'active' : ''; ?>">
			<?php if ( ! empty( $nav_item['icon'] ) ) : ?>
				<span><?php echo $nav_item['icon']; ?></span>
			<?php endif; ?>
			<?php echo esc_html( $nav_item['title'] ); ?>
		</a>
	<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Two Column Layout -->
<div class="pm-page-two-column">
	<div class="pm-main">
		<!-- Fixed Main Navigation -->
		<div class="pm-main-nav">
			<a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" 
			   class="pm-main-nav-item <?php echo ( strpos( $_SERVER['REQUEST_URI'], '/events' ) !== false ) ? 'active' : ''; ?>">
				Events
			</a>
			<a href="<?php echo esc_url( PartyMinder::get_conversations_url() ); ?>" 
			   class="pm-main-nav-item <?php echo ( strpos( $_SERVER['REQUEST_URI'], '/conversations' ) !== false ) ? 'active' : ''; ?>">
				Conversations
			</a>
			<a href="<?php echo esc_url( PartyMinder::get_communities_url() ); ?>" 
			   class="pm-main-nav-item <?php echo ( strpos( $_SERVER['REQUEST_URI'], '/communities' ) !== false ) ? 'active' : ''; ?>">
				Communities
			</a>
		</div>
		
		<!-- Scrollable Main Content -->
		<div class="pm-main-content">
			<?php echo $main_content; ?>
		</div>
	</div>

	<div class="pm-sidebar">
		<!-- Standardized Secondary Navigation -->
		<?php include PARTYMINDER_PLUGIN_DIR . 'templates/partials/sidebar-secondary-nav.php'; ?>
		
		<!-- Sidebar Content -->
		<?php if ( isset( $sidebar_content ) && $sidebar_content ) : ?>
			<?php echo $sidebar_content; ?>
		<?php endif; ?>
	</div>
</div>

<!-- Mobile Menu Modal - Server-side rendered -->
<div class="pm-modal pm-mobile-menu-modal" aria-hidden="true" style="display: none;">
	<div class="pm-modal-overlay"></div>
	<div class="pm-modal-content" style="padding: 1.5rem;">
		<div class="pm-flex pm-justify-end pm-align-center pm-mb-4">
			<button class="pm-mobile-menu-close pm-btn pm-btn-sm" aria-label="Close menu">&times;</button>
		</div>
		<?php include PARTYMINDER_PLUGIN_DIR . 'templates/partials/mobile-menu-content.php'; ?>
	</div>
</div>