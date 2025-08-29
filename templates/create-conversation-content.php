<?php
/**
 * Create Conversation Content Template
 * Uses unified form template system - simplified without topics
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

// Get event_id from URL parameter for event-specific conversations
$selected_event_id = intval( $_GET['event_id'] ?? 0 );
$selected_event    = null;
if ( $selected_event_id ) {
	global $wpdb;
	$events_table   = $wpdb->prefix . 'partyminder_events';
	$selected_event = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $events_table WHERE id = %d AND event_status = 'active'",
			$selected_event_id
		)
	);
}

// Get community_id from URL parameter for community-specific conversations
$selected_community_id = intval( $_GET['community_id'] ?? 0 );
$selected_community    = null;
if ( $selected_community_id ) {
	$selected_community = $community_manager->get_community( $selected_community_id );
}

// Get current user info
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// Check for form submission success
$conversation_created = false;
$form_errors          = array();

// Check if conversation was just created
if ( isset( $_GET['partyminder_created'] ) && $_GET['partyminder_created'] == '1' ) {
	$create_data = get_transient( 'partyminder_conversation_created_' . ( $is_logged_in ? get_current_user_id() : session_id() ) );
	if ( $create_data ) {
		$conversation_created = true;
		$created_conversation = $create_data;
		// Clear the transient
		delete_transient( 'partyminder_conversation_created_' . ( $is_logged_in ? get_current_user_id() : session_id() ) );
	}
}

// Check for form errors
$stored_errors = get_transient( 'partyminder_create_conversation_errors_' . ( $is_logged_in ? get_current_user_id() : session_id() ) );
if ( $stored_errors ) {
	$form_errors = $stored_errors;
	// Clear the transient
	delete_transient( 'partyminder_create_conversation_errors_' . ( $is_logged_in ? get_current_user_id() : session_id() ) );
}

// Set up template variables
$page_title       = __( 'Start New Conversation', 'partyminder' );
$page_description = __( 'Share ideas, ask questions, and connect with the community.', 'partyminder' );
$breadcrumbs      = array(
	array(
		'title' => __( 'Conversations', 'partyminder' ),
		'url'   => PartyMinder::get_conversations_url(),
	),
	array( 'title' => __( 'Start New Conversation', 'partyminder' ) ),
);

// If we have a selected event, update breadcrumbs and title
if ( $selected_event ) {
	$breadcrumbs      = array(
		array(
			'title' => __( 'Events', 'partyminder' ),
			'url'   => PartyMinder::get_events_page_url(),
		),
		array(
			'title' => $selected_event->title,
			'url'   => home_url( '/events/' . $selected_event->slug ),
		),
		array( 'title' => __( 'Start Event Conversation', 'partyminder' ) ),
	);
	$page_title       = __( 'Start Event Conversation', 'partyminder' );
	$page_description = sprintf( __( 'Start a conversation about %s', 'partyminder' ), $selected_event->title );
} elseif ( $selected_community ) {
	$breadcrumbs      = array(
		array(
			'title' => __( 'Communities', 'partyminder' ),
			'url'   => PartyMinder::get_communities_url(),
		),
		array(
			'title' => $selected_community->name,
			'url'   => home_url( '/communities/' . $selected_community->slug ),
		),
		array( 'title' => __( 'Start Community Conversation', 'partyminder' ) ),
	);
	$page_title       = __( 'Start Community Conversation', 'partyminder' );
	$page_description = sprintf( __( 'Start a conversation in the %s community', 'partyminder' ), $selected_community->name );
}

// Main content
ob_start();
?>

<?php if ( $conversation_created ) : ?>
	<!-- Success Message -->
	<div class="pm-alert pm-alert-success pm-mb-4">
		<h3><?php _e( 'Conversation Started Successfully!', 'partyminder' ); ?></h3>
		<p><?php _e( 'Your conversation has been created and is now live.', 'partyminder' ); ?></p>
		<div class="pm-success-actions">
			<a href="<?php echo esc_url( $created_conversation['url'] ?? PartyMinder::get_conversations_url() ); ?>" class="pm-btn">
				<?php _e( 'View Conversation', 'partyminder' ); ?>
			</a>
			<a href="<?php echo PartyMinder::get_conversations_url(); ?>" class="pm-btn pm-btn">
				<?php _e( 'All Conversations', 'partyminder' ); ?>
			</a>
		</div>
	</div>
<?php endif; ?>

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

<form method="post" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" class="pm-form" id="partyminder-conversation-form" enctype="multipart/form-data">
	<?php wp_nonce_field( 'partyminder_nonce', 'nonce' ); ?>
	<input type="hidden" name="action" value="partyminder_create_conversation">
	<?php if ( $selected_event_id ) : ?>
		<input type="hidden" name="event_id" value="<?php echo esc_attr( $selected_event_id ); ?>">
	<?php endif; ?>
	<?php if ( $selected_community_id ) : ?>
		<input type="hidden" name="community_id" value="<?php echo esc_attr( $selected_community_id ); ?>">
	<?php endif; ?>
	
	<?php if ( ! $is_logged_in ) : ?>
		<div class="pm-mb-4">
			<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Your Information', 'partyminder' ); ?></h3>
			
			<div class="pm-form-row">
				<div class="pm-form-group">
					<label for="guest_name" class="pm-form-label"><?php _e( 'Your Name *', 'partyminder' ); ?></label>
					<input type="text" id="guest_name" name="guest_name" class="pm-form-input" 
							value="<?php echo esc_attr( $_POST['guest_name'] ?? '' ); ?>" 
							placeholder="<?php esc_attr_e( 'Enter your name', 'partyminder' ); ?>" required>
				</div>
				
				<div class="pm-form-group">
					<label for="guest_email" class="pm-form-label"><?php _e( 'Your Email *', 'partyminder' ); ?></label>
					<input type="email" id="guest_email" name="guest_email" class="pm-form-input" 
							value="<?php echo esc_attr( $_POST['guest_email'] ?? '' ); ?>" 
							placeholder="<?php esc_attr_e( 'Enter your email address', 'partyminder' ); ?>" required>
				</div>
			</div>
		</div>
	<?php endif; ?>
	
	<div class="pm-mb-4">
		<h3 class="pm-heading pm-heading-md pm-text-primary pm-mb-4"><?php _e( 'Conversation Details', 'partyminder' ); ?></h3>
		
		<?php if ( $selected_event ) : ?>
			<div class="pm-alert pm-alert-success pm-mb-4">
				<h4><?php _e( 'Event Conversation', 'partyminder' ); ?></h4>
				<p><?php printf( __( 'This conversation will be associated with the event: <strong>%s</strong>', 'partyminder' ), esc_html( $selected_event->title ) ); ?></p>
				<p class="pm-text-muted"><?php _e( 'Event-specific conversations appear on the event page and help attendees coordinate and discuss details.', 'partyminder' ); ?></p>
			</div>
		<?php elseif ( $selected_community ) : ?>
			<div class="pm-alert pm-alert-success pm-mb-4">
				<h4><?php _e( 'Community Conversation', 'partyminder' ); ?></h4>
				<p><?php printf( __( 'This conversation will be part of the community: <strong>%s</strong>', 'partyminder' ), esc_html( $selected_community->name ) ); ?></p>
				<p class="pm-text-muted"><?php _e( 'Community conversations appear on the community page and help members connect and share ideas.', 'partyminder' ); ?></p>
			</div>
		<?php endif; ?>
		
		<div class="pm-form-group">
			<label for="conversation_title" class="pm-form-label"><?php _e( 'Conversation Title *', 'partyminder' ); ?></label>
			<input type="text" id="conversation_title" name="title" class="pm-form-input" 
					value="<?php echo esc_attr( $_POST['title'] ?? '' ); ?>" 
					placeholder="<?php esc_attr_e( 'What would you like to discuss?', 'partyminder' ); ?>" 
					maxlength="255" required>
			<p class="pm-form-help pm-text-muted"><?php _e( 'A clear, descriptive title helps others find and join your conversation', 'partyminder' ); ?></p>
		</div>
		
		<div class="pm-form-group">
			<label for="conversation_content" class="pm-form-label"><?php _e( 'Your Message *', 'partyminder' ); ?></label>
			<textarea id="conversation_content" name="content" class="pm-form-textarea" 
						rows="8" required
						placeholder="<?php esc_attr_e( 'Share your thoughts, ask a question, or start a discussion...', 'partyminder' ); ?>"><?php echo esc_textarea( $_POST['content'] ?? '' ); ?></textarea>
			<p class="pm-form-help pm-text-muted"><?php _e( 'Provide context and details to encourage meaningful discussions', 'partyminder' ); ?></p>
		</div>
		
		<!-- Cover Image Upload -->
		<div class="pm-form-group">
			<label for="cover_image" class="pm-form-label"><?php _e( 'Cover Image', 'partyminder' ); ?></label>
			<input type="file" id="cover_image" name="cover_image" class="pm-form-input" accept="image/*">
			<p class="pm-form-help pm-text-muted"><?php _e( 'Optional: Upload a cover image for this conversation (JPG, PNG, max 5MB)', 'partyminder' ); ?></p>
		</div>
		
		<?php if ( ! $selected_event && ! $selected_community ) : ?>
			<!-- Privacy Settings for Standalone Conversations -->
			<div class="pm-form-group">
				<label for="conversation_privacy" class="pm-form-label"><?php _e( 'Conversation Visibility *', 'partyminder' ); ?></label>
				<select id="conversation_privacy" name="privacy" class="pm-form-input" required>
					<option value="public" <?php selected( $_POST['privacy'] ?? 'public', 'public' ); ?>>
						<?php _e( 'Public - Anyone can see and participate', 'partyminder' ); ?>
					</option>
					<?php if ( $is_logged_in ) : ?>
						<option value="friends" <?php selected( $_POST['privacy'] ?? '', 'friends' ); ?>>
							<?php _e( 'Friends Only - Only your connections can see and participate', 'partyminder' ); ?>
						</option>
						<option value="members" <?php selected( $_POST['privacy'] ?? '', 'members' ); ?>>
							<?php _e( 'Registered Members - Only logged-in users can see and participate', 'partyminder' ); ?>
						</option>
					<?php endif; ?>
				</select>
				<p class="pm-form-help pm-text-muted">
					<?php if ( $selected_event ) : ?>
						<?php _e( 'This conversation will inherit the privacy settings from the event.', 'partyminder' ); ?>
					<?php elseif ( $selected_community ) : ?>
						<?php _e( 'This conversation will inherit the privacy settings from the community.', 'partyminder' ); ?>
					<?php else : ?>
						<?php _e( 'Choose who can discover and participate in this conversation.', 'partyminder' ); ?>
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>
	</div>
	
	<div class="pm-form-actions">
		<button type="submit" name="partyminder_create_conversation" class="pm-btn">
			<?php _e( 'Start Conversation', 'partyminder' ); ?>
		</button>
		<a href="<?php echo esc_url( PartyMinder::get_conversations_url() ); ?>" class="pm-btn pm-btn">
			<?php _e( 'Back to Conversations', 'partyminder' ); ?>
		</a>
	</div>
</form>

<?php
$content = ob_get_clean();

// Include form template
require PARTYMINDER_PLUGIN_DIR . 'templates/base/template-form.php';
?>

<script>
jQuery(document).ready(function($) {
	$('#partyminder-conversation-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.html();
		
		// Disable submit button and show loading
		$submitBtn.prop('disabled', true).html('<?php _e( 'Starting Conversation...', 'partyminder' ); ?>');
		
		// Prepare form data including file upload
		const formData = new FormData(this);
		
		// Debug logging for mobile issues
		console.log('Form submission started', {
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			userAgent: navigator.userAgent,
			formDataEntries: Array.from(formData.entries())
		});
		
		$.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					// Redirect to the URL provided by the server (event page for event conversations)
					const redirectUrl = response.data.redirect_url || '<?php echo PartyMinder::get_create_conversation_url(); ?>?partyminder_created=1';
					window.location.href = redirectUrl;
				} else {
					// Show error message
					$form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4><?php _e( 'Please fix the following issues:', 'partyminder' ); ?></h4><ul><li>' + (response.data || 'Unknown error occurred') + '</li></ul></div>');
					
					// Scroll to top to show error message
					$('html, body').animate({scrollTop: 0}, 500);
				}
			},
			error: function(xhr, status, error) {
				console.log('AJAX Error Details:', {
					xhr: xhr,
					status: status,
					error: error,
					responseText: xhr.responseText
				});
				
				let errorMessage = '<?php _e( 'Network error. Please try again.', 'partyminder' ); ?>';
				if (xhr.status === 403) {
					errorMessage = '<?php _e( 'Security check failed. Please refresh the page and try again.', 'partyminder' ); ?>';
				} else if (xhr.status === 500) {
					errorMessage = '<?php _e( 'Server error. Please try again later.', 'partyminder' ); ?>';
				} else if (xhr.status === 0) {
					errorMessage = '<?php _e( 'Connection failed. Please check your internet connection.', 'partyminder' ); ?>';
				}
				
				// Show error message with fallback option
				$form.before('<div class="pm-alert pm-alert-error pm-mb-4"><h4><?php _e( 'Error', 'partyminder' ); ?></h4><p>' + errorMessage + '</p><p><button type="button" class="pm-btn pm-btn-secondary" onclick="window.pmFallbackSubmit()"><?php _e( 'Try Alternative Method', 'partyminder' ); ?></button></p></div>');
				
				// Scroll to top to show error message
				$('html, body').animate({scrollTop: 0}, 500);
			},
			complete: function() {
				// Re-enable submit button
				$submitBtn.prop('disabled', false).html(originalText);
			}
		});
	});

	// Fallback function for mobile issues
	window.pmFallbackSubmit = function() {
		console.log('Using fallback submission method');
		// Change form action to a regular page that handles POST
		const $form = $('#partyminder-conversation-form');
		$form.off('submit'); // Remove AJAX handler
		$form.attr('action', '<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>'); // Submit to current page
		$form.append('<input type="hidden" name="pm_fallback_submit" value="1">');
		$form.submit(); // Regular form submission
	};
});
</script>