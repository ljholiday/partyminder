<?php
/**
 * Reply Modal Template
 * Modal dialog for creating and editing conversation replies
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- Reply Modal - Following existing mobile menu modal pattern -->
<div class="pm-modal pm-reply-modal" aria-hidden="true" style="display: none;">
	<div class="pm-modal-overlay"></div>
	<div class="pm-modal-content">
		<div class="pm-modal-header">
			<h3 class="pm-modal-title">Reply to Conversation</h3>
			<button class="pm-reply-modal-close pm-btn-close" aria-label="Close reply dialog">&times;</button>
		</div>
		
		<div class="pm-modal-body">
			<form class="pm-reply-form" enctype="multipart/form-data" method="post" action="javascript:void(0)">
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
						<?php _e( 'JPG, PNG, GIF, WebP up to 5MB each', 'partyminder' ); ?>
					</div>
				</div>
				
				<!-- File Previews -->
				<div class="pm-file-previews"></div>
			</div>
			
			<!-- Form Actions -->
			<div class="pm-form-actions">
				<button type="button" class="pm-btn pm-btn-secondary pm-reply-cancel-btn">
					<?php _e( 'Cancel', 'partyminder' ); ?>
				</button>
				<button type="submit" class="pm-btn pm-btn-primary pm-submit-btn">
					<?php _e( 'Post Reply', 'partyminder' ); ?>
				</button>
			</div>
		</form>
		</div>
	</div>
</div>