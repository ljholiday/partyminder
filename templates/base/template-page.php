<?php
/**
 * Base Single Page Template
 * Simple single-column layout for basic content pages
 *
 * Variables expected:
 * - $page_title (string)
 * - $page_description (string, optional)
 * - $breadcrumbs (array, optional)
 * - $content (string) - main page content
 * - $nav_items (array, optional) - navigation tabs
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="pm-page">
	<?php if ( ! empty( $breadcrumbs ) ) : ?>
	<!-- Breadcrumbs -->
	<div class="pm-text-muted mb-4" style="font-size: 14px;">
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

	<!-- Page Header -->
	<div class="pm-header">
		<h1 class="pm-heading pm-heading-lg pm-text-primary"><?php echo esc_html( $page_title ); ?></h1>
		<?php if ( ! empty( $page_description ) ) : ?>
			<p class="pm-text-muted"><?php echo esc_html( $page_description ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $nav_items ) ) : ?>
	<!-- Navigation -->
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

	<!-- Page Content -->
	<?php echo $content; ?>
</div>