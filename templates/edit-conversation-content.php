<?php
/**
 * Edit Conversation Content Template
 * Reuses the create conversation form template for editing
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php';
require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php';
$conversation_manager = new PartyMinder_Conversation_Manager();
$community_manager    = new PartyMinder_Community_Manager();

// Get conversation slug from URL
$conversation_slug = get_query_var( 'conversation_slug' );

if ( ! $conversation_slug ) {
	wp_redirect( PartyMinder::get_conversations_url() );
	exit;
}

// Get conversation by slug
$conversation = $conversation_manager->get_conversation( $conversation_slug, true );

if ( ! $conversation ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	return;
}

// Check permissions
$current_user = wp_get_current_user();
$can_edit = false;

if ( is_user_logged_in() ) {
	// User can edit if they are the author or an admin
	$can_edit = ( $current_user->ID == $conversation->author_id ) || current_user_can( 'manage_options' );
}

if ( ! $can_edit ) {
	wp_redirect( home_url( '/conversations/' . $conversation->slug ) );
	exit;
}

// Get event data if this is an event conversation
$event_data = null;
if ( $conversation->event_id ) {
	require_once PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php';
	$event_manager = new PartyMinder_Event_Manager();
	$event_data    = $event_manager->get_event( $conversation->event_id );
}

// Get community data if this is a community conversation
$community_data = null;
if ( $conversation->community_id ) {
	$community_data = $community_manager->get_community( $conversation->community_id );
}

// Check for form errors
$form_errors = array();

// Check for form errors
$stored_errors = get_transient( 'partyminder_edit_conversation_errors_' . get_current_user_id() );
if ( $stored_errors ) {
	$form_errors = $stored_errors;
	// Clear the transient
	delete_transient( 'partyminder_edit_conversation_errors_' . get_current_user_id() );
}

// Set up template variables
$page_title       = __( 'Edit Conversation', 'partyminder' );
$page_description = sprintf( __( 'Edit "%s"', 'partyminder' ), $conversation->title );
$breadcrumbs      = array(
	array(
		'title' => __( 'Conversations', 'partyminder' ),
		'url'   => PartyMinder::get_conversations_url(),
	),
	array(
		'title' => $conversation->title,
		'url'   => home_url( '/conversations/' . $conversation->slug ),
	),
	array( 'title' => __( 'Edit', 'partyminder' ) ),
);

// Main content
ob_start();
?>

<!-- Navigation -->
<div class="pm-section pm-mb-4">
	<div class="pm-flex pm-gap-4">
		<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-btn pm-btn">
			<?php _e( '← Back to Conversation', 'partyminder' ); ?>
		</a>
		<a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn">
			<?php _e( 'All Conversations', 'partyminder' ); ?>
		</a>
	</div>
</div>

<?php if ( ! empty( $form_errors ) ) : ?>
	<div class="pm-alert pm-alert-error pm-mb-4">
		<h4><?php _e( 'Please fix the following issues:', 'partyminder' ); ?></h4>
		<ul>
			<?php foreach ( $form_errors as $error ) : ?>
				<li><?php echo esc_html( $error ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<form method="post" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" class="pm-form" id="partyminder-edit-conversation-form" enctype="multipart/form-data">
	<?php wp_nonce_field( 'partyminder_nonce', 'nonce' ); ?>
	<input type="hidden" name="action" value="partyminder_update_conversation">
	<input type="hidden" name="conversation_id" value="<?php echo esc_attr( $conversation->id ); ?>">
	
	<div class="pm-mb-4">
		<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Conversation Details', 'partyminder' ); ?></h3>
		
		<?php if ( $event_data ) : ?>
			<div class="pm-alert pm-alert-info pm-mb-4">
				<h4><?php _e( 'Event Conversation', 'partyminder' ); ?></h4>
				<p><?php printf( __( 'This conversation is associated with the event: <strong>%s</strong>', 'partyminder' ), esc_html( $event_data->title ) ); ?></p>
			</div>
		<?php elseif ( $community_data ) : ?>
			<div class="pm-alert pm-alert-info pm-mb-4">
				<h4><?php _e( 'Community Conversation', 'partyminder' ); ?></h4>
				<p><?php printf( __( 'This conversation is part of the community: <strong>%s</strong>', 'partyminder' ), esc_html( $community_data->name ) ); ?></p>
			</div>
		<?php endif; ?>
		
		<div class="pm-form-group">
			<label for="conversation_title" class="pm-form-label"><?php _e( 'Conversation Title *', 'partyminder' ); ?></label>
			<input type="text" id="conversation_title" name="title" class="pm-form-input" 
					value="<?php echo esc_attr( $conversation->title ); ?>" 
					maxlength="255" required>
		</div>
		
		<div class="pm-form-group">
			<label for="conversation_content" class="pm-form-label"><?php _e( 'Your Message *', 'partyminder' ); ?></label>
			<textarea id="conversation_content" name="content" class="pm-form-textarea" 
						rows="8" required><?php echo esc_textarea( $conversation->content ); ?></textarea>
		</div>
		
		<!-- Cover Image Upload -->
		<div class="pm-form-group">
			<label for="cover_image" class="pm-form-label"><?php _e( 'Cover Image', 'partyminder' ); ?></label>
			<input type="file" id="cover_image" name="cover_image" class="pm-form-input" accept="image/*">
			<p class="pm-form-help pm-text-muted"><?php _e( 'Optional: Upload a cover image for this conversation (JPG, PNG, max 5MB)', 'partyminder' ); ?></p>
			
			<?php if ( ! empty( $conversation->featured_image ) ) : ?>
				<div class="pm-current-cover pm-mt-2">
					<p class="pm-text-muted pm-mb-2"><?php _e( 'Current cover image:', 'partyminder' ); ?></p>
					<img src="<?php echo esc_url( $conversation->featured_image ); ?>" alt="Current cover" style="max-width: 200px; height: auto; border-radius: 4px;">
					<label class="pm-mt-2">
						<input type="checkbox" name="remove_cover_image" value="1"> <?php _e( 'Remove current cover image', 'partyminder' ); ?>
					</label>
				</div>
			<?php endif; ?>
		</div>
		
		<?php if ( ! $event_data && ! $community_data ) : ?>
			<!-- Privacy Settings for Standalone Conversations -->
			<div class="pm-form-group">
				<label for="conversation_privacy" class="pm-form-label"><?php _e( 'Conversation Visibility *', 'partyminder' ); ?></label>
				<select id="conversation_privacy" name="privacy" class="pm-form-input" required>
					<option value="public" <?php selected( $conversation->privacy, 'public' ); ?>>
						<?php _e( 'Public - Anyone can see and participate', 'partyminder' ); ?>
					</option>
					<option value="friends" <?php selected( $conversation->privacy, 'friends' ); ?>>
						<?php _e( 'Friends Only - Only your connections can see and participate', 'partyminder' ); ?>
					</option>
					<option value="members" <?php selected( $conversation->privacy, 'members' ); ?>>
						<?php _e( 'Registered Members - Only logged-in users can see and participate', 'partyminder' ); ?>
					</option>
				</select>
			</div>
		<?php endif; ?>
	</div>
	
	<div class="pm-form-actions">
		<button type="submit" name="partyminder_update_conversation" class="pm-btn">
			<?php _e( 'Save Changes', 'partyminder' ); ?>
		</button>
		<a href="<?php echo home_url( '/conversations/' . $conversation->slug ); ?>" class="pm-btn pm-btn">
			<?php _e( 'Cancel', 'partyminder' ); ?>
		</a>
	</div>
</form>

<script>
jQuery(document).ready(function($) {
	$('#partyminder-edit-conversation-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.html();
		
		// Disable submit button and show loading
		$submitBtn.prop('disabled', true).html('<?php _e( 'Saving Changes...', 'partyminder' ); ?>');
		
		// Prepare form data including file upload
		const formData = new FormData(this);
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					// Redirect back to conversation using updated slug
					window.location.href = '<?php echo home_url( '/conversations/' ); ?>' + response.data.slug;
				} else {
					// Show error message
					$form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4><?php _e( 'Please fix the following issues:', 'partyminder' ); ?></h4><ul><li>' + (response.data || 'Unknown error occurred') + '</li></ul></div>');
					
					// Scroll to top to show error message
					$('html, body').animate({scrollTop: 0}, 500);
				}
			},
			error: function() {
				$form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4><?php _e( 'Error', 'partyminder' ); ?></h4><p><?php _e( 'Network error. Please try again.', 'partyminder' ); ?></p></div>');
				
				// Scroll to top to show error message
				$('html, body').animate({scrollTop: 0}, 500);
			},
			complete: function() {
				// Re-enable submit button
				$submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});
});
</script>

<?php
$content = ob_get_clean();

// Include form template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php';