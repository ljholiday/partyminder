<?php
/**
 * Unit Tests Bootstrap - Uses Brain\Monkey to mock WordPress
 */

use Brain\Monkey;

// Initialize Brain\Monkey
Monkey\setUp();

// Mock WordPress functions commonly used in the plugin
Monkey\Functions\when('__')->returnArg();
Monkey\Functions\when('_e')->returnArg();
Monkey\Functions\when('esc_html')->returnArg();
Monkey\Functions\when('esc_attr')->returnArg();
Monkey\Functions\when('esc_url')->returnArg();
Monkey\Functions\when('esc_js')->returnArg();
Monkey\Functions\when('sanitize_text_field')->returnArg();
Monkey\Functions\when('sanitize_email')->returnArg();
Monkey\Functions\when('sanitize_textarea_field')->returnArg();
Monkey\Functions\when('wp_kses_post')->returnArg();
Monkey\Functions\when('current_time')->alias(function($type = 'mysql') {
    return $type === 'mysql' ? date('Y-m-d H:i:s') : time();
});
Monkey\Functions\when('home_url')->alias(function($path = '') {
    return 'http://example.org' . $path;
});
Monkey\Functions\when('admin_url')->alias(function($path = '') {
    return 'http://example.org/wp-admin/' . $path;
});
Monkey\Functions\when('wp_create_nonce')->alias(function($action) {
    return 'test_nonce_' . md5($action);
});
Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
Monkey\Functions\when('is_user_logged_in')->justReturn(false);
Monkey\Functions\when('get_current_user_id')->justReturn(0);

// Mock WordPress classes
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $errors = [];
        private $error_data = [];
        
        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->errors[$code][] = $message;
                if (!empty($data)) {
                    $this->error_data[$code] = $data;
                }
            }
        }
        
        public function get_error_message($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return isset($this->errors[$code][0]) ? $this->errors[$code][0] : '';
        }
        
        public function get_error_code() {
            $codes = array_keys($this->errors);
            return isset($codes[0]) ? $codes[0] : '';
        }
        
        public function has_errors() {
            return !empty($this->errors);
        }
    }
}

// Helper function for unit tests
function is_wp_error($thing) {
    return $thing instanceof WP_Error;
}

// Register tearDown to clean up after each test
register_shutdown_function(function() {
    Monkey\tearDown();
});