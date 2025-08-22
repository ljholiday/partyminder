<?php
/**
 * PartyMinder Smoke Test
 * 
 * Simple test to verify all main pages load without fatal errors.
 * Run this after any major changes to catch breaking issues.
 * 
 * Usage: php smoke-test.php
 * Or visit: /wp-content/plugins/partyminder/tests/smoke-test.php
 */

// Prevent direct access in browser (allow CLI)
if ( ! defined( 'WP_CLI' ) && isset( $_SERVER['HTTP_HOST'] ) ) {
    // Only allow if directly accessing the file with a secret parameter
    if ( ! isset( $_GET['run_test'] ) || $_GET['run_test'] !== 'partyminder_smoke_test' ) {
        die( 'Access denied. Add ?run_test=partyminder_smoke_test to run.' );
    }
}

// Load WordPress
$wp_load_path = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php';
if ( ! file_exists( $wp_load_path ) ) {
    die( "Could not find WordPress. Expected wp-load.php at: $wp_load_path\n" );
}
require_once $wp_load_path;

class PartyMinder_Smoke_Test {
    
    private $test_results = array();
    private $base_url;
    
    public function __construct() {
        $this->base_url = home_url();
    }
    
    /**
     * Run all smoke tests
     */
    public function run_tests() {
        echo "🧪 PartyMinder Smoke Test\n";
        echo "========================\n\n";
        
        // Test main pages
        $this->test_page_loads( '/', 'Homepage' );
        $this->test_page_loads( '/events', 'Events Page' );
        $this->test_page_loads( '/communities', 'Communities Page' );
        $this->test_page_loads( '/conversations', 'Conversations Page' );
        $this->test_page_loads( '/dashboard', 'Dashboard Page' );
        $this->test_page_loads( '/my-events', 'My Events Page' );
        $this->test_page_loads( '/my-communities', 'My Communities Page' );
        $this->test_page_loads( '/create-event', 'Create Event Page' );
        $this->test_page_loads( '/create-community', 'Create Community Page' );
        
        // Test with logged-in user if possible
        $this->test_with_logged_in_user();
        
        // Test database connections
        $this->test_database_tables();
        
        // Test core functionality
        $this->test_core_classes();
        
        $this->print_summary();
    }
    
    /**
     * Test if a page loads without fatal errors
     */
    private function test_page_loads( $path, $description ) {
        $url = $this->base_url . $path;
        
        // Use WordPress HTTP API for consistency
        $response = wp_remote_get( $url, array(
            'timeout' => 30,
            'sslverify' => false,
            'user-agent' => 'PartyMinder-SmokeTest/1.0'
        ) );
        
        if ( is_wp_error( $response ) ) {
            $this->record_result( $description, false, 'HTTP Error: ' . $response->get_error_message() );
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        // Check for fatal PHP errors
        if ( strpos( $body, 'Fatal error:' ) !== false || 
             strpos( $body, 'Parse error:' ) !== false ||
             strpos( $body, 'Fatal Error:' ) !== false ) {
            $this->record_result( $description, false, "PHP Fatal Error detected (Status: $status_code)" );
            return;
        }
        
        // Check for reasonable status codes
        if ( $status_code >= 200 && $status_code < 400 ) {
            $this->record_result( $description, true, "Status: $status_code" );
        } elseif ( $status_code == 404 ) {
            $this->record_result( $description, false, "Page not found (404) - might need page creation" );
        } else {
            $this->record_result( $description, false, "HTTP Status: $status_code" );
        }
    }
    
    /**
     * Test pages that require authentication
     */
    private function test_with_logged_in_user() {
        // Try to find an admin user
        $admin_user = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
        
        if ( empty( $admin_user ) ) {
            $this->record_result( 'Logged-in User Tests', false, 'No admin user found for testing' );
            return;
        }
        
        // Simulate logged-in session by setting current user
        $user = $admin_user[0];
        wp_set_current_user( $user->ID );
        
        // Test admin/user-specific pages
        $this->test_page_loads( '/dashboard', 'Dashboard (Logged In)' );
        $this->test_page_loads( '/my-events', 'My Events (Logged In)' );
        $this->test_page_loads( '/create-event', 'Create Event (Logged In)' );
        
        // Reset user
        wp_set_current_user( 0 );
        
        $this->record_result( 'Logged-in User Tests', true, 'Tested with admin user' );
    }
    
    /**
     * Test database table existence and basic queries
     */
    private function test_database_tables() {
        global $wpdb;
        
        $tables = array(
            'partyminder_events',
            'partyminder_communities', 
            'partyminder_conversations',
            'partyminder_guests',
            'partyminder_community_members'
        );
        
        $errors = array();
        
        foreach ( $tables as $table ) {
            $full_table_name = $wpdb->prefix . $table;
            
            // Check if table exists
            $table_exists = $wpdb->get_var( 
                $wpdb->prepare( "SHOW TABLES LIKE %s", $full_table_name ) 
            );
            
            if ( ! $table_exists ) {
                $errors[] = "Table $table does not exist";
                continue;
            }
            
            // Try a simple SELECT to ensure table is accessible
            $test_query = $wpdb->get_var( "SELECT COUNT(*) FROM $full_table_name" );
            if ( $test_query === null && $wpdb->last_error ) {
                $errors[] = "Cannot query $table: " . $wpdb->last_error;
            }
        }
        
        if ( empty( $errors ) ) {
            $this->record_result( 'Database Tables', true, 'All tables exist and accessible' );
        } else {
            $this->record_result( 'Database Tables', false, implode( '; ', $errors ) );
        }
    }
    
    /**
     * Test core class loading and instantiation
     */
    private function test_core_classes() {
        $classes_to_test = array(
            'PartyMinder_Event_Manager' => PARTYMINDER_PLUGIN_DIR . 'includes/class-event-manager.php',
            'PartyMinder_Community_Manager' => PARTYMINDER_PLUGIN_DIR . 'includes/class-community-manager.php',
            'PartyMinder_Conversation_Manager' => PARTYMINDER_PLUGIN_DIR . 'includes/class-conversation-manager.php',
        );
        
        $errors = array();
        
        foreach ( $classes_to_test as $class_name => $file_path ) {
            if ( ! file_exists( $file_path ) ) {
                $errors[] = "File missing: $file_path";
                continue;
            }
            
            require_once $file_path;
            
            if ( ! class_exists( $class_name ) ) {
                $errors[] = "Class $class_name not found";
                continue;
            }
            
            try {
                $instance = new $class_name();
                if ( ! is_object( $instance ) ) {
                    $errors[] = "Cannot instantiate $class_name";
                }
            } catch ( Exception $e ) {
                $errors[] = "Error instantiating $class_name: " . $e->getMessage();
            }
        }
        
        if ( empty( $errors ) ) {
            $this->record_result( 'Core Classes', true, 'All classes load and instantiate' );
        } else {
            $this->record_result( 'Core Classes', false, implode( '; ', $errors ) );
        }
    }
    
    /**
     * Record test result
     */
    private function record_result( $test_name, $passed, $message = '' ) {
        $this->test_results[] = array(
            'name' => $test_name,
            'passed' => $passed,
            'message' => $message
        );
        
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        $msg = $message ? " - $message" : '';
        echo "$status: $test_name$msg\n";
    }
    
    /**
     * Print test summary
     */
    private function print_summary() {
        echo "\n========================\n";
        
        $total = count( $this->test_results );
        $passed = count( array_filter( $this->test_results, function( $result ) {
            return $result['passed'];
        } ) );
        $failed = $total - $passed;
        
        echo "📊 Test Summary:\n";
        echo "Total: $total\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        
        if ( $failed === 0 ) {
            echo "\n🎉 All tests passed! PartyMinder is working correctly.\n";
            exit( 0 );
        } else {
            echo "\n⚠️ Some tests failed. Check the issues above.\n";
            exit( 1 );
        }
    }
}

// Run the tests
$smoke_test = new PartyMinder_Smoke_Test();
$smoke_test->run_tests();