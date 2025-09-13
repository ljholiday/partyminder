<?php
/**
 * Reply Modal Template
 * Modal dialog for creating and editing conversation replies
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modal_id = 'pm-reply-modal';
$modal_size = 'md';
$modal_title = __( 'Reply to Conversation', 'partyminder' );
$modal_close_label = __( 'Close reply dialog', 'partyminder' );
$modal_class = 'pm-reply-modal';

ob_start();
?>
<form id="pm-reply-form" class="pm-reply-form" enctype="multipart/form-data" method="post" action="javascript:void(0)">
	<div class="pm-form-error" style="display: none;"></div>
	
	<?php if ( ! is_user_logged_in() ) : ?>
	<!-- Guest User Fields -->
	<div class="pm-guest-fields">
		<div class="pm-form-row">
			<label for="pm-guest-name"><?php _e( 'Your Name', 'partyminder' ); ?> *</label>
			<input type="text" id="pm-guest-name" class="pm-guest-name pm-input" required>
		</div>
		<div class="pm-form-row">
			<label for="pm-guest-email"><?php _e( 'Your Email', 'partyminder' ); ?> *</label>
			<input type="email" id="pm-guest-email" class="pm-guest-email pm-input" required>
		</div>
	</div>
	<?php endif; ?>
	
	<!-- Reply Content -->
	<div class="pm-form-column">
		<label for="pm-reply-content"><?php _e( 'Your Reply', 'partyminder' ); ?> *</label>
		<textarea id="pm-reply-content" class="pm-reply-content pm-textarea" rows="6" 
		          placeholder="<?php esc_attr_e( 'Share your thoughts...', 'partyminder' ); ?>" required></textarea>
	</div>
	
	<!-- File Upload Section -->
	<div class="pm-file-upload-section">
		<div class="pm-form-row">
			<label for="pm-file-input"><?php _e( 'Attach Images', 'partyminder' ); ?></label>
			<input type="file" id="pm-file-input" class="pm-file-input" 
			       accept="image/jpeg,image/png,image/gif,image/webp" multiple style="display: none;">
			<button type="button" class="pm-btn pm-btn-secondary" 
			        onclick="document.getElementById('pm-file-input').click();">
				<?php _e( 'Choose Files', 'partyminder' ); ?>
			</button>
			<div class="pm-file-help-text">
				<?php echo PartyMinder_Settings::get_file_size_description(); ?>
			</div>
		</div>
		
		<!-- File Previews -->
		<div class="pm-file-previews"></div>
	</div>
</form>
<?php
$modal_content = ob_get_clean();

ob_start();
?>
<button type="button" class="pm-btn pm-btn-secondary pm-reply-cancel-btn pm-modal-close">
	<?php _e( 'Cancel', 'partyminder' ); ?>
</button>
<button type="submit" form="pm-reply-form" class="pm-btn pm-btn-primary pm-submit-btn">
	<?php _e( 'Post Reply', 'partyminder' ); ?>
</button>
<?php
$modal_footer = ob_get_clean();

include PARTYMINDER_PLUGIN_DIR . 'templates/partials/modal-base.php';
?>