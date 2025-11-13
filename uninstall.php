<?php
/**
 * Uninstall NUZ Online Academy Plugin
 * 
 * This file is called when the plugin is uninstalled
 * It cleans up all plugin data, tables, and options
 * 
 * @package NuzOnlineAcademy
 * @version 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has permission to uninstall plugins
if (!current_user_can('activate_plugins')) {
    exit;
}

/**
 * NUZ Academy Uninstall Class
 */
class NUZ_Academy_Uninstaller {
    
    /**
     * Run uninstallation process
     */
    public static function uninstall() {
        // Remove all plugin options
        self::remove_options();
        
        // Drop all plugin tables
        self::drop_tables();
        
        // Remove uploaded files
        self::remove_uploaded_files();
        
        // Remove user capabilities
        self::remove_user_capabilities();
        
        // Remove scheduled events
        self::remove_scheduled_events();
        
        // Clear any cached data
        self::clear_cache();
        
        // Remove any custom post types/taxonomies
        self::remove_custom_post_types();
        
        // Clean up WordPress transients
        self::remove_transients();
        
        // Remove any plugin logs
        self::remove_logs();
    }
    
    /**
     * Remove all plugin options
     */
    private static function remove_options() {
        // Remove main plugin options
        $options_to_remove = array(
            'nuz_academy_version',
            'nuz_academy_activation_time',
            'nuz_academy_settings',
            'nuz_academy_db_version',
            'nuz_academy_installed_date',
            'nuz_academy_first_activation',
            
            // Individual settings
            'nuz_academy_academy_name',
            'nuz_academy_theme_mode',
            'nuz_academy_currency_symbol',
            'nuz_academy_contact_email',
            'nuz_academy_contact_phone',
            'nuz_academy_max_upload_size',
            'nuz_academy_auto_backup',
            'nuz_academy_logo_url',
            'nuz_academy_primary_color',
            'nuz_academy_secondary_color',
            
            // Theme preferences
            'nuz_academy_dark_mode_enabled',
            'nuz_academy_custom_css',
            'nuz_academy_custom_logo',
            
            // Integration settings
            'nuz_academy_payment_gateway',
            'nuz_academy_email_settings',
            'nuz_academy_sms_settings',
            
            // Feature toggles
            'nuz_academy_screenshot_enabled',
            'nuz_academy_certificates_enabled',
            'nuz_academy_multi_language',
            'nuz_academy_advanced_reports',
            
            // Cached data
            'nuz_academy_dashboard_cache',
            'nuz_academy_stats_cache',
            'nuz_academy_recent_activities'
        );
        
        foreach ($options_to_remove as $option) {
            delete_option($option);
            delete_site_option($option); // For multisite
        }
        
        // Remove any option that starts with 'nuz_academy_'
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'nuz_academy_%'");
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'nuz_academy_%'");
        
        // Remove any user meta related to the plugin
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'nuz_academy_%'");
    }
    
    /**
     * Drop all plugin database tables
     */
    private static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            'nuz_students',
            'nuz_courses',
            'nuz_payments',
            'nuz_enrollments',
            'nuz_screenshots',
            'nuz_settings',
            'nuz_audit_log',
            'nuz_certificates',
            'nuz_assignments',
            'nuz_attendance',
            'nuz_notifications'
        );
        
        foreach ($tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");
        }
        
        // Remove any foreign key constraints if they exist
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");
        }
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    /**
     * Remove uploaded files and directories
     */
    private static function remove_uploaded_files() {
        $upload_dir = wp_upload_dir();
        $plugin_uploads = $upload_dir['basedir'] . '/nuz-academy';
        
        if (is_dir($plugin_uploads)) {
            self::delete_directory($plugin_uploads);
        }
        
        // Remove any files in the plugins directory
        $plugin_dir = WP_PLUGIN_DIR . '/nuz-online-academy';
        if (is_dir($plugin_dir)) {
            self::delete_directory($plugin_dir);
        }
    }
    
    /**
     * Recursively delete a directory and all its contents
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $file_path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file_path)) {
                self::delete_directory($file_path);
            } else {
                unlink($file_path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Remove user capabilities
     */
    private static function remove_user_capabilities() {
        // Remove roles
        remove_role('nuz_instructor');
        remove_role('nuz_student');
        
        // Remove capabilities from Administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $capabilities_to_remove = array(
                'nuz_manage_academy',
                'nuz_view_students',
                'nuz_view_courses',
                'nuz_view_payments',
                'nuz_manage_students',
                'nuz_manage_courses',
                'nuz_manage_payments',
                'nuz_upload_screenshots',
                'nuz_manage_settings',
                'nuz_export_data',
                'nuz_import_data'
            );
            
            foreach ($capabilities_to_remove as $capability) {
                $admin_role->remove_cap($capability);
            }
        }
        
        // Remove capabilities from other roles
        $roles_to_clean = array('editor', 'author', 'contributor', 'subscriber');
        foreach ($roles_to_clean as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $capabilities_to_remove = array(
                    'nuz_view_students',
                    'nuz_view_courses',
                    'nuz_view_payments',
                    'nuz_upload_screenshots',
                    'nuz_view_own_data',
                    'nuz_upload_work'
                );
                
                foreach ($capabilities_to_remove as $capability) {
                    $role->remove_cap($capability);
                }
            }
        }
    }
    
    /**
     * Remove scheduled events
     */
    private static function remove_scheduled_events() {
        $scheduled_events = array(
            'nuz_daily_reports',
            'nuz_backup_data',
            'nuz_cleanup_temp_files',
            'nuz_send_payment_reminders',
            'nuz_update_stats',
            'nuz_process_screenshots',
            'nuz_generate_certificates'
        );
        
        foreach ($scheduled_events as $event) {
            wp_clear_scheduled_hook($event);
        }
    }
    
    /**
     * Clear cache
     */
    private static function clear_cache() {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear any plugin-specific cached data
        delete_transient('nuz_dashboard_stats');
        delete_transient('nuz_recent_activities');
        delete_transient('nuz_payment_summary');
        delete_transient('nuz_enrollment_stats');
        delete_transient('nuz_course_performance');
        delete_transient('nuz_monthly_revenue');
        
        // Clear any custom cached data
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nuz_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nuz_%'");
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_nuz_%'");
    }
    
    /**
     * Remove custom post types and taxonomies
     */
    private static function remove_custom_post_types() {
        // Remove any custom posts of plugin post types
        $post_types = array('nuz_lesson', 'nuz_certificate', 'nuz_assignment');
        
        foreach ($post_types as $post_type) {
            // Delete all posts of this type
            $posts = get_posts(array(
                'post_type' => $post_type,
                'numberposts' => -1,
                'post_status' => 'any'
            ));
            
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }
            
            // Remove post type registration
            global $wp_post_types;
            if (isset($wp_post_types[$post_type])) {
                unset($wp_post_types[$post_type]);
            }
        }
        
        // Remove custom taxonomies
        $taxonomies = array('nuz_course_category', 'nuz_certificate_type', 'nuz_assignment_type');
        
        foreach ($taxonomies as $taxonomy) {
            // Delete all terms
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'number' => 0
            ));
            
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
            
            // Remove taxonomy registration
            global $wp_taxonomies;
            if (isset($wp_taxonomies[$taxonomy])) {
                unset($wp_taxonomies[$taxonomy]);
            }
        }
    }
    
    /**
     * Remove transients
     */
    private static function remove_transients() {
        global $wpdb;
        
        // Remove all transients related to the plugin
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nuz_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nuz_%'");
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_timeout_nuz_%'");
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_nuz_%'");
    }
    
    /**
     * Remove logs
     */
    private static function remove_logs() {
        // Remove any error logs created by the plugin
        $log_files = array(
            WP_CONTENT_DIR . '/debug.log',
            WP_CONTENT_DIR . '/nuz-academy-error.log',
            WP_CONTENT_DIR . '/nuz-academy-debug.log',
            WP_CONTENT_DIR . '/uploads/nuz-academy/logs/',
        );
        
        foreach ($log_files as $log_file) {
            if (is_file($log_file)) {
                unlink($log_file);
            } elseif (is_dir($log_file)) {
                self::delete_directory($log_file);
            }
        }
        
        // Remove log entries from database if any
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}nuz_audit_log");
    }
    
    /**
     * Clean up WordPress cron
     */
    private static function cleanup_cron() {
        // Remove any custom cron jobs
        $cron_jobs = wp_get_scheduled_event('nuz_daily_reports');
        if ($cron_jobs) {
            wp_clear_scheduled_hook('nuz_daily_reports');
        }
        
        $cron_jobs = wp_get_scheduled_event('nuz_backup_data');
        if ($cron_jobs) {
            wp_clear_scheduled_hook('nuz_backup_data');
        }
    }
    
    /**
     * Remove rewrite rules
     */
    private static function remove_rewrite_rules() {
        // Force WordPress to refresh rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Clean up database indexes
     */
    private static function cleanup_indexes() {
        global $wpdb;
        
        // Remove any custom indexes we might have created
        $indexes_to_remove = array(
            'nuz_students_email',
            'nuz_students_phone',
            'nuz_courses_instructor',
            'nuz_payments_date',
            'nuz_enrollments_status'
        );
        
        foreach ($indexes_to_remove as $index) {
            $table_name = $wpdb->prefix . substr($index, 0, strpos($index, '_', 4));
            $wpdb->query("DROP INDEX IF EXISTS {$index} ON {$table_name}");
        }
    }
    
    /**
     * Remove any custom tables created for reporting
     */
    private static function remove_reporting_tables() {
        global $wpdb;
        
        $reporting_tables = array(
            'nuz_reports_cache',
            'nuz_daily_stats',
            'nuz_monthly_summary',
            'nuz_performance_metrics'
        );
        
        foreach ($reporting_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");
        }
    }
    
    /**
     * Remove any custom database functions
     */
    private static function remove_database_functions() {
        global $wpdb;
        
        // Remove any custom functions we might have created
        $functions_to_remove = array(
            'nuz_get_student_stats',
            'nuz_calculate_revenue',
            'nuz_get_course_performance'
        );
        
        foreach ($functions_to_remove as $function) {
            $wpdb->query("DROP FUNCTION IF EXISTS {$function}");
        }
    }
}

/**
 * Run the uninstallation process
 */
NUZ_Academy_Uninstaller::uninstall();

/**
 * Additional cleanup functions
 */
function nuz_additional_cleanup() {
    // Remove any files left in WordPress temp directory
    $temp_dir = get_temp_dir();
    $nuz_temp_files = glob($temp_dir . 'nuz_*');
    foreach ($nuz_temp_files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    // Clean up any database queries that might be cached
    wp_cache_delete('nuz_all_students');
    wp_cache_delete('nuz_all_courses');
    wp_cache_delete('nuz_all_payments');
    wp_cache_delete('nuz_dashboard_data');
    
    // Clear any object cache entries
    $cache_keys = array(
        'nuz_students_count',
        'nuz_courses_count',
        'nuz_revenue_total',
        'nuz_monthly_stats',
        'nuz_recent_enrollments'
    );
    
    foreach ($cache_keys as $key) {
        wp_cache_delete($key);
    }
}

// Run additional cleanup
nuz_additional_cleanup();

/**
 * Log uninstallation for audit purposes
 */
function nuz_log_uninstall() {
    // Create a simple log entry (optional)
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'action' => 'plugin_uninstalled',
        'plugin' => 'nuz-online-academy',
        'version' => '1.0.0'
    );
    
    // You could save this to a log file or external service
    // For now, we'll just save it as an option (temporary)
    $uninstall_logs = get_option('nuz_uninstall_logs', array());
    $uninstall_logs[] = $log_entry;
    
    // Keep only the last 10 entries
    if (count($uninstall_logs) > 10) {
        $uninstall_logs = array_slice($uninstall_logs, -10);
    }
    
    update_option('nuz_uninstall_logs', $uninstall_logs);
}

// Log the uninstallation
nuz_log_uninstall();

/**
 * Final cleanup message
 * Note: This won't be visible to the user as it's called during uninstall
 */
function nuz_final_cleanup() {
    // Force cleanup of any remaining resources
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Remove any remaining plugin data
    global $wpdb;
    
    // Clean up any orphaned options
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'nuz_%' AND option_name NOT LIKE 'nuz_uninstall_logs'");
    
    // Remove any orphaned user meta
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'nuz_%'");
}

// Run final cleanup
nuz_final_cleanup();

/**
 * Uninstallation complete
 * 
 * The plugin has been completely removed from the system.
 * All data, tables, files, and configurations have been cleaned up.
 * 
 * To reinstall the plugin:
 * 1. Upload the plugin files to wp-content/plugins/nuz-online-academy/
 * 2. Activate the plugin from WordPress admin
 * 3. The activation process will recreate all necessary data
 */