<?php
/**
 * Conversations Content Template - Content Only
 * For theme integration via the_content filter
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if we're on the dedicated page and include the full template
$on_dedicated_page = ( is_page() && get_post_meta( get_the_ID(), '_partyminder_page_type', true ) === 'conversations' );

if ( $on_dedicated_page ) {
	// Include the full conversations template
	include PARTYMINDER_PLUGIN_DIR . 'templates/conversations.php';
} else {
	// Fallback embedded version - set up template variables
	$page_title       = __( 'Community Conversations', 'partyminder' );
	$page_description = __( 'Connect with fellow hosts and guests, share tips, and plan amazing gatherings together.', 'partyminder' );

	// Main content
	ob_start();
	?>
	<div class="text-center p-4">
		<p><?php echo esc_html( $page_description ); ?></p>
		<a href="<?php echo esc_url( PartyMinder::get_conversations_url() ); ?>" class="pm-btn"><?php _e( 'Join Conversations', 'partyminder' ); ?></a>
	</div>
	<?php
	$content = ob_get_clean();

	// Include base template
	include PARTYMINDER_PLUGIN_DIR . 'templates/base/template-page.php';
}