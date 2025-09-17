<?php
/**
 * Mobile Menu Content
 * Mobile-optimized version of search, navigation, and profile actions
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
?>

<?php if ( $is_logged_in ) : ?>
<!-- Mobile Search -->
<div class="pm-mb-4">
    <input type="text" id="pm-mobile-search-input" class="pm-input pm-mobile-search-input" placeholder="<?php _e( 'Search...', 'partyminder' ); ?>" autocomplete="off" style="width: 100%;">
    <div id="pm-mobile-search-results" class="pm-search-results" style="display: none;"></div>
</div>

<!-- Mobile Navigation -->
<div class="pm-mobile-nav pm-mb-4">
    <a href="<?php echo esc_url( PartyMinder::get_create_event_url() ); ?>" class="pm-btn pm-btn-secondary pm-mb-3" style="width: 100%; display: block;">
        <?php _e( 'Create Event', 'partyminder' ); ?>
    </a>
    
    <a href="<?php echo esc_url( PartyMinder::get_create_conversation_url() ); ?>" class="pm-btn pm-btn-secondary pm-mb-3" style="width: 100%; display: block;">
        <?php _e( 'Create Conversation', 'partyminder' ); ?>
    </a>
    
    <?php if ( PartyMinder_Feature_Flags::can_user_create_community() ) : ?>
        <a href="<?php echo esc_url( PartyMinder::get_create_community_url() ); ?>" class="pm-btn pm-btn-secondary pm-mb-3" style="width: 100%; display: block;">
            <?php _e( 'Create Community', 'partyminder' ); ?>
        </a>
    <?php endif; ?>
    
    <a href="<?php echo esc_url( PartyMinder::get_profile_url() ); ?>" class="pm-btn pm-btn-secondary pm-mb-3" style="width: 100%; display: block;">
        <?php _e( 'My Profile', 'partyminder' ); ?>
    </a>
    
    <a href="<?php echo esc_url( PartyMinder::get_dashboard_url() ); ?>" class="pm-btn pm-btn-secondary pm-mb-3" style="width: 100%; display: block;">
        <?php _e( 'Dashboard', 'partyminder' ); ?>
    </a>
</div>

<!-- Mobile Profile Actions -->
<div class="pm-mobile-profile-actions">
    <a href="<?php echo esc_url( PartyMinder::get_profile_url() ); ?>" class="pm-btn pm-mb-3" style="width: 100%; display: block;">
        <?php _e( 'Edit Profile', 'partyminder' ); ?>
    </a>
    <a href="<?php echo esc_url( PartyMinder::get_logout_url() ); ?>" class="pm-btn" style="width: 100%; display: block;">
        <?php _e( 'Logout', 'partyminder' ); ?>
    </a>
</div>

<?php else : ?>
<!-- Mobile Navigation for Logged Out Users -->
<div class="pm-mobile-nav">
    <a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn pm-btn-secondary pm-mb-3" style="width: 100%; display: block;">
        <?php _e( 'Browse Events', 'partyminder' ); ?>
    </a>
    
    <a href="<?php echo esc_url( PartyMinder::get_conversations_url() ); ?>" class="pm-btn pm-btn-secondary pm-mb-3" style="width: 100%; display: block;">
        <?php _e( 'Join Conversations', 'partyminder' ); ?>
    </a>
    
    <a href="<?php echo esc_url( PartyMinder::get_communities_url() ); ?>" class="pm-btn pm-btn-secondary pm-mb-3" style="width: 100%; display: block;">
        <?php _e( 'Browse Communities', 'partyminder' ); ?>
    </a>
    
    <a href="<?php echo esc_url( PartyMinder::get_login_url() ); ?>" class="pm-btn pm-btn-secondary pm-mb-3" style="width: 100%; display: block;">
        <?php _e( 'Sign In', 'partyminder' ); ?>
    </a>
</div>
<?php endif; ?>