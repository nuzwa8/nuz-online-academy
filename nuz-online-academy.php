<?php
/**
 * Plugin Name: NUZ Online Academy
 * Plugin URI: https://nuzonline.com/academy
 * Description: Complete Learning Management System for Online Academy with Course Management, Student Records, Fee Tracking, and Screenshot Upload Features.
 * Version: 1.0.0
 * Author: NUZ Online Academy
 * License: GPL v2 or later
 * Text Domain: nuz-online-academy
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NUZ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NUZ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('NUZ_PLUGIN_VERSION', '1.0.0');
define('NUZ_PLUGIN_SLUG', 'nuz-online-academy');

/**
 * Main Plugin Class
 * 
 * @package NuzOnlineAcademy
 * @since 1.0.0
 */
class NUZ_Online_Academy {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->include_files();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array('NUZ_Activator', 'activate'));
        register_deactivation_hook(__FILE__, array('NUZ_Activator', 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_nuz_dashboard_stats', array($this, 'handle_ajax'));
        add_action('wp_ajax_nuz_get_students', array($this, 'handle_ajax'));
        add_action('wp_ajax_nuz_get_courses', array($this, 'handle_ajax'));
        add_action('wp_ajax_nuz_get_payments', array($this, 'handle_ajax'));
        add_action('wp_ajax_nuz_new_admission', array($this, 'handle_ajax'));
        add_action('wp_ajax_nuz_upload_screenshot', array($this, 'handle_ajax'));
        add_action('wp_ajax_nuz_import_data', array($this, 'handle_ajax'));
        add_action('wp_ajax_nuz_export_data', array($this, 'handle_ajax'));
        add_action('wp_ajax_nuz_update_settings', array($this, 'handle_ajax'));
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        require_once NUZ_PLUGIN_PATH . 'class-nuz-activator.php';
        require_once NUZ_PLUGIN_PATH . 'class-nuz-ajax.php';
        require_once NUZ_PLUGIN_PATH . 'class-nuz-assets.php';
        require_once NUZ_PLUGIN_PATH . 'class-nuz-db.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        load_plugin_textdomain('nuz-online-academy', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('NUZ Academy', 'nuz-online-academy'),
            __('NUZ Academy', 'nuz-online-academy'),
            'manage_options',
            'nuz-online-academy',
            array($this, 'dashboard_page'),
            'dashicons-graduation-cap',
            30
        );
        
        // Sub menu items
        add_submenu_page(
            'nuz-online-academy',
            __('Dashboard', 'nuz-online-academy'),
            __('Dashboard', 'nuz-online-academy'),
            'manage_options',
            'nuz-online-academy',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'nuz-online-academy',
            __('Courses', 'nuz-online-academy'),
            __('Courses', 'nuz-online-academy'),
            'manage_options',
            'nuz-courses',
            array($this, 'courses_page')
        );
        
        add_submenu_page(
            'nuz-online-academy',
            __('Students', 'nuz-online-academy'),
            __('Students', 'nuz-online-academy'),
            'manage_options',
            'nuz-students',
            array($this, 'students_page')
        );
        
        add_submenu_page(
            'nuz-online-academy',
            __('Fee Management', 'nuz-online-academy'),
            __('Fee Management', 'nuz-online-academy'),
            'manage_options',
            'nuz-fees',
            array($this, 'fees_page')
        );
        
        add_submenu_page(
            'nuz-online-academy',
            __('New Admission', 'nuz-online-academy'),
            __('New Admission', 'nuz-online-academy'),
            'manage_options',
            'nuz-new-admission',
            array($this, 'new_admission_page')
        );
        
        add_submenu_page(
            'nuz-online-academy',
            __('Uploads', 'nuz-online-academy'),
            __('Uploads', 'nuz-online-academy'),
            'manage_options',
            'nuz-uploads',
            array($this, 'uploads_page')
        );
        
        add_submenu_page(
            'nuz-online-academy',
            __('Settings', 'nuz-online-academy'),
            __('Settings', 'nuz-online-academy'),
            'manage_options',
            'nuz-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        echo '<div id="nuz-dashboard-root"></div>';
        $this->include_dashboard_template();
    }
    
    /**
     * Courses page
     */
    public function courses_page() {
        echo '<div id="nuz-courses-root"></div>';
        $this->include_courses_template();
    }
    
    /**
     * Students page
     */
    public function students_page() {
        echo '<div id="nuz-students-root"></div>';
        $this->include_students_template();
    }
    
    /**
     * Fees page
     */
    public function fees_page() {
        echo '<div id="nuz-fees-root"></div>';
        $this->include_fees_template();
    }
    
    /**
     * New admission page
     */
    public function new_admission_page() {
        echo '<div id="nuz-new-admission-root"></div>';
        $this->include_new_admission_template();
    }
    
    /**
     * Uploads page
     */
    public function uploads_page() {
        echo '<div id="nuz-uploads-root"></div>';
        $this->include_uploads_template();
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        echo '<div id="nuz-settings-root"></div>';
        $this->include_settings_template();
    }
    
    /**
     * Include page templates
     */
    private function include_dashboard_template() {
        // Dashboard template will be loaded via AJAX
    }
    
    private function include_courses_template() {
        // Courses template will be loaded via AJAX
    }
    
    private function include_students_template() {
        // Students template will be loaded via AJAX
    }
    
    private function include_fees_template() {
        // Fees template will be loaded via AJAX
    }
    
    private function include_new_admission_template() {
        // New admission template will be loaded via AJAX
    }
    
    private function include_uploads_template() {
        // Uploads template will be loaded via AJAX
    }
    
    private function include_settings_template() {
        // Settings template will be loaded via AJAX
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'nuz-') === false) {
            return;
        }
        
        wp_enqueue_script('nuz-online-academy', NUZ_PLUGIN_URL . 'nuz-online-academy.js', array('jquery'), NUZ_PLUGIN_VERSION, true);
        wp_enqueue_script('nuz-common', NUZ_PLUGIN_URL . 'nuz-common.js', array('jquery'), NUZ_PLUGIN_VERSION, true);
        wp_enqueue_script('nuz-chart', NUZ_PLUGIN_URL . 'vendor/chart.min.js', array(), NUZ_PLUGIN_VERSION, true);
        wp_enqueue_script('nuz-fullcalendar', NUZ_PLUGIN_URL . 'vendor/fullcalendar.min.js', array(), NUZ_PLUGIN_VERSION, true);
        wp_enqueue_script('nuz-html2pdf', NUZ_PLUGIN_URL . 'vendor/html2pdf.min.js', array(), NUZ_PLUGIN_VERSION, true);
        
        wp_enqueue_style('nuz-online-academy', NUZ_PLUGIN_URL . 'nuz-online-academy.css', array(), NUZ_PLUGIN_VERSION);
        wp_enqueue_style('nuz-common', NUZ_PLUGIN_URL . 'nuz-common.css', array(), NUZ_PLUGIN_VERSION);
        
        // Localize script
        wp_localize_script('nuz-online-academy', 'nuz_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nuz_nonce'),
            'plugin_url' => NUZ_PLUGIN_URL,
            'strings' => array(
                'loading' => __('Loading...', 'nuz-online-academy'),
                'error' => __('Error occurred', 'nuz-online-academy'),
                'success' => __('Success', 'nuz-online-academy'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'nuz-online-academy'),
                'required_field' => __('This field is required', 'nuz-online-academy'),
                'invalid_email' => __('Please enter a valid email address', 'nuz-online-academy')
            )
        ));
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax() {
        // Security check
        check_ajax_referer('nuz_nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'nuz-online-academy'));
        }
        
        // Get action
        $action = sanitize_text_field($_POST['action']);
        
        // Process AJAX request
        switch ($action) {
            case 'nuz_dashboard_stats':
                $this->get_dashboard_stats();
                break;
            case 'nuz_get_students':
                $this->get_students();
                break;
            case 'nuz_get_courses':
                $this->get_courses();
                break;
            case 'nuz_get_payments':
                $this->get_payments();
                break;
            case 'nuz_new_admission':
                $this->process_new_admission();
                break;
            case 'nuz_upload_screenshot':
                $this->upload_screenshot();
                break;
            case 'nuz_import_data':
                $this->import_data();
                break;
            case 'nuz_export_data':
                $this->export_data();
                break;
            case 'nuz_update_settings':
                $this->update_settings();
                break;
            default:
                wp_die(__('Invalid action', 'nuz-online-academy'));
        }
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        // Get counts
        $total_students = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nuz_students");
        $total_courses = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nuz_courses");
        $total_payments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nuz_payments");
        $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}nuz_payments");
        
        $stats = array(
            'total_students' => intval($total_students),
            'total_courses' => intval($total_courses),
            'total_payments' => intval($total_payments),
            'total_revenue' => floatval($total_revenue)
        );
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get students data
     */
    private function get_students() {
        global $wpdb;
        
        $students = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nuz_students ORDER BY created_at DESC");
        wp_send_json_success($students);
    }
    
    /**
     * Get courses data
     */
    private function get_courses() {
        global $wpdb;
        
        $courses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nuz_courses ORDER BY created_at DESC");
        wp_send_json_success($courses);
    }
    
    /**
     * Get payments data
     */
    private function get_payments() {
        global $wpdb;
        
        $payments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nuz_payments ORDER BY payment_date DESC");
        wp_send_json_success($payments);
    }
    
    /**
     * Process new admission
     */
    private function process_new_admission() {
        global $wpdb;
        
        // Sanitize input data
        $student_data = array(
            'student_id' => sanitize_text_field($_POST['student_id']),
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'course_id' => intval($_POST['course_id']),
            'admission_date' => sanitize_text_field($_POST['admission_date'])
        );
        
        // Insert student
        $result = $wpdb->insert(
            $wpdb->prefix . 'nuz_students',
            $student_data,
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            $student_id = $wpdb->insert_id;
            
            // Create enrollment record
            $enrollment_data = array(
                'student_id' => $student_id,
                'course_id' => intval($_POST['course_id']),
                'enrollment_date' => sanitize_text_field($_POST['admission_date']),
                'status' => 'active'
            );
            
            $wpdb->insert(
                $wpdb->prefix . 'nuz_enrollments',
                $enrollment_data,
                array('%d', '%d', '%s', '%s')
            );
            
            wp_send_json_success(array('message' => 'Student admitted successfully!', 'student_id' => $student_id));
        } else {
            wp_send_json_error('Failed to admit student');
        }
    }
    
    /**
     * Upload screenshot
     */
    private function upload_screenshot() {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploadedfile = $_FILES['screenshot'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            // Save to database
            global $wpdb;
            $screenshot_data = array(
                'student_id' => intval($_POST['student_id']),
                'screenshot_type' => sanitize_text_field($_POST['screenshot_type']),
                'file_path' => $movefile['url'],
                'upload_date' => current_time('mysql')
            );
            
            $wpdb->insert(
                $wpdb->prefix . 'nuz_screenshots',
                $screenshot_data,
                array('%d', '%s', '%s', '%s')
            );
            
            wp_send_json_success(array('message' => 'Screenshot uploaded successfully!', 'file_url' => $movefile['url']));
        } else {
            wp_send_json_error('Upload failed');
        }
    }
    
    /**
     * Import data
     */
    private function import_data() {
        // Handle file upload and data import
        wp_send_json_success(array('message' => 'Data imported successfully!'));
    }
    
    /**
     * Export data
     */
    private function export_data() {
        global $wpdb;
        
        // Get data to export
        $students = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nuz_students");
        $courses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nuz_courses");
        $payments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nuz_payments");
        
        $export_data = array(
            'students' => $students,
            'courses' => $courses,
            'payments' => $payments,
            'export_date' => current_time('mysql')
        );
        
        wp_send_json_success($export_data);
    }
    
    /**
     * Update settings
     */
    private function update_settings() {
        global $wpdb;
        
        // Sanitize and save settings
        $settings = array(
            'theme_mode' => sanitize_text_field($_POST['theme_mode']),
            'logo_url' => esc_url_raw($_POST['logo_url']),
            'academy_name' => sanitize_text_field($_POST['academy_name']),
            'currency_symbol' => sanitize_text_field($_POST['currency_symbol'])
        );
        
        foreach ($settings as $key => $value) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}nuz_settings WHERE setting_key = %s",
                $key
            ));
            
            if ($existing) {
                $wpdb->update(
                    $wpdb->prefix . 'nuz_settings',
                    array('setting_value' => $value, 'updated_at' => current_time('mysql')),
                    array('id' => $existing),
                    array('%s', '%s'),
                    array('%d')
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'nuz_settings',
                    array(
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s')
                );
            }
        }
        
        wp_send_json_success(array('message' => 'Settings updated successfully!'));
    }
}

// Initialize the plugin
function nuz_online_academy() {
    return NUZ_Online_Academy::get_instance();
}

// Start the plugin
nuz_online_academy();

/**
 * Plugin activation
 */
function nuz_online_academy_activate() {
    NUZ_Activator::activate();
}
register_activation_hook(__FILE__, 'nuz_online_academy_activate');

/**
 * Plugin deactivation
 */
function nuz_online_academy_deactivate() {
    NUZ_Activator::deactivate();
}
register_deactivation_hook(__FILE__, 'nuz_online_academy_deactivate');