<?php
/**
 * Base Modal Template
 * Provides standardized modal structure for all modal types
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set defaults if not provided
$modal_id = $modal_id ?? 'pm-modal';
$modal_size = $modal_size ?? 'md';
$modal_title = $modal_title ?? '';
$modal_content = $modal_content ?? '';
$modal_footer = $modal_footer ?? '';
$modal_close_label = $modal_close_label ?? __( 'Close modal', 'partyminder' );
?>

<div id="<?php echo esc_attr( $modal_id ); ?>" class="pm-modal pm-modal-<?php echo esc_attr( $modal_size ); ?>" aria-hidden="true" style="display: none;">
	<div class="pm-modal-overlay"></div>
	<div class="pm-modal-content">
		<div class="pm-modal-header">
			<h3 class="pm-modal-title"><?php echo esc_html( $modal_title ); ?></h3>
			<button type="button" class="pm-btn pm-btn-close pm-modal-close" aria-label="<?php echo esc_attr( $modal_close_label ); ?>">
				&times;
			</button>
		</div>
		<div class="pm-modal-body">
			<?php echo $modal_content; ?>
		</div>
		<?php if ( $modal_footer ) : ?>
		<div class="pm-modal-footer">
			<?php echo $modal_footer; ?>
		</div>
		<?php endif; ?>
	</div>
</div>