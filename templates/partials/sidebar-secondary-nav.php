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
    
    <?php if ( $is_logged_in ) : ?>
    <!-- Profile Card -->
    <div class="pm-profile-card pm-mt-4">
        <div class="pm-flex pm-gap pm-mb">
            <?php 
            $user_id = $current_user->ID;
            $user_name = $current_user->display_name;
            $size = 'lg';
            PartyMinder::render_avatar( $user_id, $user_name, $size );
            
            // Get user profile data
            $profile_data = null;
            if ( class_exists( 'PartyMinder_Profile_Manager' ) ) {
                $profile_data = PartyMinder_Profile_Manager::get_user_profile( $current_user->ID );
            }
            ?>
            <div class="pm-flex-1">
                <div class="pm-heading pm-heading-sm"><?php echo esc_html( $current_user->display_name ); ?></div>
                <?php if ( $profile_data && $profile_data['location'] ) : ?>
                <div class="pm-text-muted"> <?php echo esc_html( $profile_data['location'] ); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="pm-flex pm-gap pm-flex-column">
            <a href="<?php echo esc_url( PartyMinder::get_profile_url() ); ?>" class="pm-btn pm-btn">
                <?php _e( 'Edit Profile', 'partyminder' ); ?>
            </a>
            <a href="<?php echo esc_url( PartyMinder::get_logout_url() ); ?>" class="pm-btn pm-btn">
                <?php _e( 'Logout', 'partyminder' ); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
    
</div>
