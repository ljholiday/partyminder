<?php
/**
 * Standardized Secondary Navigation for Sidebar
 * Universal secondary menu used across all pages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
?>

<div class="pm-sidebar-section pm-mb-4">
    
    <?php if ( $is_logged_in ) : ?>
    <!-- Search Section -->
    <div class="pm-search-box pm-mb-4">
        <input type="text" id="pm-search-input" class="pm-input" placeholder="<?php _e( 'Search...', 'partyminder' ); ?>" autocomplete="off">
        <div id="pm-search-results" class="pm-search-results" style="display: none;"></div>
    </div>
    <?php endif; ?>
    
    <div class="pm-sidebar-nav">
        <?php if ( $is_logged_in ) : ?>
            <a href="<?php echo esc_url( PartyMinder::get_create_event_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'Create Event', 'partyminder' ); ?>
            </a>
            
            <a href="<?php echo esc_url( PartyMinder::get_create_conversation_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'Create Conversation', 'partyminder' ); ?>
            </a>
            
            <?php if ( PartyMinder_Feature_Flags::can_user_create_community() ) : ?>
                <a href="<?php echo esc_url( site_url( '/create-community' ) ); ?>" class="pm-btn pm-btn-secondary">
                    <?php _e( 'Create Community', 'partyminder' ); ?>
                </a>
            <?php endif; ?>
            
            <a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'My Events', 'partyminder' ); ?>
            </a>
            
            <a href="<?php echo add_query_arg('show_all', '1', PartyMinder::get_events_page_url()); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'All Events', 'partyminder' ); ?>
            </a>
            
            <a href="<?php echo esc_url( PartyMinder::get_conversations_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'Join Conversations', 'partyminder' ); ?>
            </a>
            
            <a href="<?php echo esc_url( PartyMinder::get_communities_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'Browse Communities', 'partyminder' ); ?>
            </a>
            
            <a href="<?php echo esc_url( PartyMinder::get_profile_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'My Profile', 'partyminder' ); ?>
            </a>
            
            <a href="<?php echo esc_url( PartyMinder::get_dashboard_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'Dashboard', 'partyminder' ); ?>
            </a>
            
        <?php else : ?>
            <a href="<?php echo esc_url( PartyMinder::get_events_page_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'Browse Events', 'partyminder' ); ?>
            </a>
            
            <a href="<?php echo esc_url( PartyMinder::get_conversations_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'Join Conversations', 'partyminder' ); ?>
            </a>
            
            <a href="<?php echo esc_url( PartyMinder::get_communities_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'Browse Communities', 'partyminder' ); ?>
            </a>
            
            <a href="<?php echo esc_url( PartyMinder::get_login_url() ); ?>" class="pm-btn pm-btn-secondary">
                <?php _e( 'Sign In', 'partyminder' ); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
