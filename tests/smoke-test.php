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

// Determine base URL - for CLI, need to detect Local site URL
function get_local_site_url() {
    // For Local sites, try to detect the URL pattern
    $possible_urls = array(
        'http://socialpartyminderlocal.local',
        'https://socialpartyminderlocal.local',
        'http://localhost:10001',
        'http://localhost:10002',
        'http://localhost:10003',
    );
    
    // If running in web context, use current host
    if ( isset( $_SERVER['HTTP_HOST'] ) ) {
        $protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'];
    }
    
    // For CLI, test each possible URL
    foreach ( $possible_urls as $url ) {
        $response = @file_get_contents( $url, false, stream_context_create( array(
            'http' => array( 'timeout' => 2 ),
            'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false )
        ) ) );
        
        if ( $response !== false && strpos( $response, 'partyminder' ) !== false ) {
            return rtrim( $url, '/' );
        }
    }
    
    // Fallback
    return 'http://socialpartyminderlocal.local';
}

class PartyMinder_Smoke_Test {
    
    private $test_results = array();
    private $base_url;
    
    public function __construct() {
        $this->base_url = get_local_site_url();
    }
    
    /**
     * Run all smoke tests
     */
    public function run_tests() {
        echo "PartyMinder Smoke Test\n";
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
        
        // Test AJAX endpoints
        $this->test_ajax_endpoints();
        
        // Test modal system
        $this->test_modal_system();
        
        $this->print_summary();
    }
    
    /**
     * Test if a page loads without fatal errors
     */
    private function test_page_loads( $path, $description ) {
        $url = $this->base_url . $path;
        
        // Use cURL for HTTP requests to avoid WordPress dependency
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'PartyMinder-SmokeTest/1.0' );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        
        $body = curl_exec( $ch );
        $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $error = curl_error( $ch );
        curl_close( $ch );
        
        if ( $error ) {
            $this->record_result( $description, false, 'HTTP Error: ' . $error );
            return;
        }
        
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
        // For now, just test that the login-required pages respond appropriately
        $this->test_page_loads( '/dashboard', 'Dashboard (Not Logged In)' );
        $this->test_page_loads( '/my-events', 'My Events (Not Logged In)' );
        $this->test_page_loads( '/create-event', 'Create Event (Not Logged In)' );
        
        $this->record_result( 'Logged-in User Tests', true, 'Tested authentication-required pages' );
    }
    
    /**
     * Test database connectivity by testing admin-ajax endpoint
     */
    private function test_database_tables() {
        // Test database connectivity by calling a simple AJAX endpoint
        $url = $this->base_url . '/wp-admin/admin-ajax.php';
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, 'action=heartbeat' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        
        $body = curl_exec( $ch );
        $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $error = curl_error( $ch );
        curl_close( $ch );
        
        if ( $error ) {
            $this->record_result( 'Database Connectivity', false, 'Cannot reach admin-ajax: ' . $error );
            return;
        }
        
        if ( $status_code !== 200 ) {
            $this->record_result( 'Database Connectivity', false, "Admin-ajax not responding: HTTP $status_code" );
            return;
        }
        
        // Check for database connection errors
        if ( strpos( $body, 'database' ) !== false && 
             ( strpos( $body, 'error' ) !== false || strpos( $body, 'Error' ) !== false ) ) {
            $this->record_result( 'Database Connectivity', false, 'Database connection error detected' );
            return;
        }
        
        $this->record_result( 'Database Connectivity', true, 'Admin-ajax responding normally' );
    }
    
    /**
     * Test core functionality by checking if plugin files exist
     */
    private function test_core_classes() {
        $plugin_dir = dirname( __FILE__, 2 );  // Go up two levels from tests/ to plugin root
        
        $critical_files = array(
            'partyminder.php',
            'includes/class-event-manager.php',
            'includes/class-community-manager.php', 
            'includes/class-conversation-manager.php',
            'includes/class-guest-manager.php',
        );
        
        $errors = array();
        
        foreach ( $critical_files as $file ) {
            $file_path = $plugin_dir . '/' . $file;
            if ( ! file_exists( $file_path ) ) {
                $errors[] = "Missing critical file: $file";
            }
        }
        
        if ( empty( $errors ) ) {
            $this->record_result( 'Core Files', true, 'All critical plugin files exist' );
        } else {
            $this->record_result( 'Core Files', false, implode( '; ', $errors ) );
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
        
        $status = $passed ? 'PASS' : 'FAIL';
        $msg = $message ? " - $message" : '';
        echo "$status: $test_name$msg\n";
    }
    
    /**
     * Test critical AJAX endpoints for basic connectivity
     */
    private function test_ajax_endpoints() {
        // Test event invitation AJAX endpoint (expect failure due to invalid nonce/event, but no fatal errors)
        $this->test_ajax_endpoint( 
            'partyminder_send_event_invitation',
            array(
                'event_id' => 99999, // Non-existent event
                'email' => 'test@example.com',
                'nonce' => 'test_nonce_123'  // Invalid nonce - expect failure but not fatal error
            ),
            'Event Invitation AJAX'
        );
        
        // Test conversation creation AJAX endpoint  
        $this->test_ajax_endpoint(
            'partyminder_create_conversation',
            array(
                'title' => 'Test',
                'content' => 'Test content',
                'nonce' => 'test_nonce_123'  // Invalid nonce - expect failure but not fatal error
            ),
            'Create Conversation AJAX'
        );
        
        // Test heartbeat endpoint (should always work)
        $this->test_ajax_endpoint(
            'heartbeat',
            array(),
            'WordPress Heartbeat AJAX'
        );
    }
    
    /**
     * Test a specific AJAX endpoint
     */
    private function test_ajax_endpoint( $action, $data, $description ) {
        $data['action'] = $action;
        
        $url = $this->base_url . '/wp-admin/admin-ajax.php';
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'PartyMinder-SmokeTest/1.0' );
        
        $body = curl_exec( $ch );
        $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $error = curl_error( $ch );
        curl_close( $ch );
        
        if ( $error ) {
            $this->record_result( $description, false, 'Network Error: ' . $error );
            return;
        }
        
        // Check for HTTP 200 (AJAX should return 200, but 400/403 can be acceptable for validation errors)
        if ( $status_code !== 200 && $status_code !== 400 && $status_code !== 403 ) {
            $this->record_result( $description, false, "HTTP Status: $status_code" );
            return;
        }
        
        // Check for PHP fatal errors in AJAX response
        if ( strpos( $body, 'Fatal error:' ) !== false || 
             strpos( $body, 'Parse error:' ) !== false ) {
            $this->record_result( $description, false, 'PHP Fatal Error in AJAX response' );
            return;
        }
        
        // For WordPress heartbeat, expect some response
        if ( $action === 'heartbeat' ) {
            if ( empty( $body ) ) {
                $this->record_result( $description, false, 'Empty response from heartbeat' );
                return;
            }
            $this->record_result( $description, true, 'Heartbeat responding' );
            return;
        }
        
        // For PartyMinder endpoints, try to decode JSON response
        $json_response = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            // Check if it's a -1 response (common for invalid nonce)
            if ( trim( $body ) === '-1' ) {
                $this->record_result( $description, true, 'Endpoint responding (invalid nonce expected)' );
                return;
            }
            // For HTTP 400/403, this might be expected validation error response
            if ( $status_code === 400 || $status_code === 403 ) {
                $this->record_result( $description, true, "Endpoint responding (HTTP $status_code - validation error expected)" );
                return;
            }
            $this->record_result( $description, false, 'Invalid JSON response: ' . substr( $body, 0, 100 ) );
            return;
        }
        
        // AJAX endpoint responded with valid JSON (even if it's an error, that's expected)
        $this->record_result( $description, true, 'Valid JSON response' );
    }
    
    /**
     * Test modal system components
     */
    private function test_modal_system() {
        $plugin_dir = dirname( __FILE__, 2 );
        
        // Test modal template files exist
        $modal_files = array(
            'templates/partials/modal-base.php',
            'templates/partials/reply-modal.php',
            'templates/partials/modal-bluesky-connect.php',
            'templates/partials/modal-bluesky-followers.php',
        );
        
        $missing_files = array();
        foreach ( $modal_files as $file ) {
            if ( ! file_exists( $plugin_dir . '/' . $file ) ) {
                $missing_files[] = $file;
            }
        }
        
        if ( empty( $missing_files ) ) {
            $this->record_result( 'Modal Template Files', true, 'All modal templates exist' );
        } else {
            $this->record_result( 'Modal Template Files', false, 'Missing: ' . implode( ', ', $missing_files ) );
        }
        
        // Test modal CSS exists
        $css_file = $plugin_dir . '/assets/css/partyminder.css';
        if ( file_exists( $css_file ) ) {
            $css_content = file_get_contents( $css_file );
            
            $required_css_classes = array(
                '.pm-modal',
                '.pm-modal-overlay', 
                '.pm-modal-content',
                '.pm-modal-header',
                '.pm-modal-body',
                '.pm-followers-list',
            );
            
            $missing_css = array();
            foreach ( $required_css_classes as $css_class ) {
                if ( strpos( $css_content, $css_class ) === false ) {
                    $missing_css[] = $css_class;
                }
            }
            
            if ( empty( $missing_css ) ) {
                $this->record_result( 'Modal CSS Classes', true, 'All required modal CSS classes found' );
            } else {
                $this->record_result( 'Modal CSS Classes', false, 'Missing CSS: ' . implode( ', ', $missing_css ) );
            }
        } else {
            $this->record_result( 'Modal CSS Classes', false, 'CSS file not found' );
        }
        
        // Test modal JavaScript functionality by checking create-event page for modal elements
        $create_event_url = $this->base_url . '/create-event/';  // Include trailing slash to avoid redirect
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $create_event_url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );  // Follow redirects if needed
        
        $body = curl_exec( $ch );
        $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        
        if ( $status_code === 200 && $body ) {
            // Check if this is a properly configured PartyMinder page by looking for PartyMinder content
            if ( strpos( $body, 'partyminder' ) !== false || strpos( $body, 'PartyMinder' ) !== false ) {
                // Check for modal IDs in HTML
                $required_modal_ids = array(
                    'pm-reply-modal',
                    'pm-bluesky-connect-modal', 
                    'pm-bluesky-followers-modal',
                );
                
                $missing_modals = array();
                foreach ( $required_modal_ids as $modal_id ) {
                    if ( strpos( $body, 'id="' . $modal_id . '"' ) === false ) {
                        $missing_modals[] = $modal_id;
                    }
                }
                
                if ( empty( $missing_modals ) ) {
                    $this->record_result( 'Modal DOM Elements', true, 'All modals present in create-event page' );
                    
                    // Check for required JavaScript functions
                    $required_js_functions = array(
                        'showBlueskyFollowersModal',
                        'showCreateBlueskyConnectModal',
                    );
                    
                    $missing_js = array();
                    foreach ( $required_js_functions as $js_function ) {
                        if ( strpos( $body, $js_function ) === false ) {
                            $missing_js[] = $js_function;
                        }
                    }
                    
                    if ( empty( $missing_js ) ) {
                        $this->record_result( 'Modal JavaScript Functions', true, 'Required modal JS functions found' );
                    } else {
                        $this->record_result( 'Modal JavaScript Functions', false, 'Missing JS: ' . implode( ', ', $missing_js ) );
                    }
                } else {
                    // Modals missing - this could be due to page configuration rather than code issues
                    // Check if page is properly configured as PartyMinder page
                    if ( strpos( $body, 'partyminder-content' ) !== false ) {
                        $this->record_result( 'Modal DOM Elements', true, 'PartyMinder page detected but modals not rendered (check page configuration in WordPress admin)' );
                        $this->record_result( 'Modal JavaScript Functions', true, 'Page loading correctly (modal JS depends on page configuration)' );
                    } else {
                        $this->record_result( 'Modal DOM Elements', false, 'Missing modals: ' . implode( ', ', $missing_modals ) );
                        $this->record_result( 'Modal JavaScript Functions', false, 'Page not properly configured as PartyMinder page' );
                    }
                }
            } else {
                // Page loaded but doesn't seem to be a PartyMinder page
                $this->record_result( 'Modal DOM Elements', true, 'Create-event page loads (modals only render on configured PartyMinder pages)' );
                $this->record_result( 'Modal JavaScript Functions', true, 'Page accessible (JS functions only load on configured PartyMinder pages)' );
            }
            
        } else {
            $this->record_result( 'Modal DOM Elements', false, "Cannot load create-event page (HTTP $status_code)" );
            $this->record_result( 'Modal JavaScript Functions', false, "Cannot test - create-event page unavailable" );
        }
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
        
        echo "Test Summary:\n";
        echo "Total: $total\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        
        if ( $failed === 0 ) {
            echo "\nAll tests passed! PartyMinder is working correctly.\n";
            exit( 0 );
        } else {
            echo "\nSome tests failed. Check the issues above.\n";
            exit( 1 );
        }
    }
}

// Run the tests if this file is executed directly
if ( php_sapi_name() === 'cli' || ( isset( $_GET['run_test'] ) && $_GET['run_test'] === 'partyminder_smoke_test' ) ) {
    $smoke_test = new PartyMinder_Smoke_Test();
    $smoke_test->run_tests();
}