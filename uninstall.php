<?php
/**
 * Uninstall CoachProAI LMS
 *
 * This file is called when the plugin is deleted
 * to clean up plugin data
 *
 * @package CoachProAI
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Delete plugin options
$options_to_delete = array(
	'coachproai_general_settings',
	'coachproai_ai_settings', 
	'coachproai_coaching_settings',
	'coachproai_analytics_settings',
	'coachproai_integrations',
	'coachproai_lms_db_version',
	'coachproai_lms_activated_time',
	'coachproai_openai_api_key',
	'coachproai_page_coachproai-dashboard',
	'coachproai_page_coachproai-programs',
	'coachproai_page_coachproai-ai-chat',
	'coachproai_page_coachproai-progress',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// Delete user meta related to CoachProAI
delete_metadata( 'user', 0, '_coachproai_activity_log', '', true );
delete_metadata( 'user', 0, '_coachproai_user_preferences', '', true );

// Delete all plugin pages
$plugin_pages = array(
	'coachproai-dashboard',
	'coachproai-programs', 
	'coachproai-ai-chat',
	'coachproai-progress'
);

foreach ( $plugin_pages as $slug ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		wp_delete_post( $page->ID, true );
	}
}

// Remove user roles
remove_role( 'coachproai_coach' );
remove_role( 'coachproai_student' );
remove_role( 'coachproai_admin' );

// Remove capabilities from administrator
$admin_role = get_role( 'administrator' );
if ( $admin_role ) {
	$capabilities = array(
		'manage_coaching_programs',
		'manage_coaching_assessments',
		'manage_ai_coaches',
		'manage_coaching_analytics',
		'manage_coaching_settings',
		'create_coaching_programs',
		'edit_coaching_programs',
		'edit_others_coaching_programs',
		'publish_coaching_programs',
		'delete_coaching_programs',
		'delete_others_coaching_programs',
		'read_private_coaching_programs',
		'manage_coaching_enrollments',
		'view_coaching_analytics',
		'view_all_coaching_analytics',
		'view_student_progress',
		'manage_student_coaching',
		'participate_in_assessments',
		'chat_with_ai_coaches',
		'view_own_progress',
		'submit_coaching_goals',
	);
	
 foreach ( $capabilities as $cap ) {
		$admin_role->remove_cap( $cap );
	}
}

// Delete custom post types data
$post_types = array( 'coaching_program', 'ai_coach', 'coaching_assessment' );

foreach ( $post_types as $post_type ) {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'number_posts'   => -1,
			'fields'         => 'ids',
		)
	);
	
 foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}

// Delete transients
global $wpdb;
$wpdb->query(
	"DELETE FROM {$wpdb->options} 
	 WHERE option_name LIKE '_transient_coachproai_%' 
	 OR option_name LIKE '_transient_timeout_coachproai_%'"
);

// Delete scheduled events
wp_clear_scheduled_hook( 'coachproai_ai_cleanup' );
wp_clear_scheduled_hook( 'coachproai_analytics_reports' );
wp_clear_scheduled_hook( 'coachproai_progress_backup' );

// Log uninstall (optional)
if ( defined( 'COACHPROAI_DEBUG' ) && COACHPROAI_DEBUG ) {
	error_log( 'CoachProAI LMS: Plugin uninstalled and all data cleaned up' );
}