<?php
/**
 * CoachProAI Install Class
 *
 * @package CoachProAI
 */

namespace CoachProAI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install Class - Handles plugin activation and database setup
 */
class Install {

	/**
	 * Activate plugin
	 */
	public static function activate() {
		self::create_tables();
		self::create_user_roles();
		self::create_pages();
		self::set_default_options();
		self::create_default_ai_coaches();
	}

	/**
	 * Create database tables
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Student profiles table
		$table_name = $wpdb->prefix . 'coachproai_profiles';
		$sql        = "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			student_id bigint(20) NOT NULL,
			program_id mediumint(9) NOT NULL,
			ai_profile_data text NULL,
			learning_preferences text NULL,
			personality_traits text NULL,
			goals_data text NULL,
			progress_summary text NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY student_id (student_id),
			KEY program_id (program_id)
		) $charset_collate;";

		// AI coaching sessions table
		$table_name = $wpdb->prefix . 'coachproai_ai_sessions';
		$sql        .= "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			student_id bigint(20) NOT NULL,
			ai_coach_id mediumint(9) NOT NULL,
			session_type varchar(50) NOT NULL,
			conversation_data longtext NULL,
			ai_recommendations text NULL,
			progress_update text NULL,
			sentiment_score decimal(3,2) DEFAULT 0.00,
			session_rating tinyint(1) DEFAULT 0,
			duration_minutes smallint(5) DEFAULT 0,
			started_at datetime DEFAULT CURRENT_TIMESTAMP,
			ended_at datetime NULL,
			PRIMARY KEY (id),
			KEY student_id (student_id),
			KEY ai_coach_id (ai_coach_id),
			KEY session_type (session_type)
		) $charset_collate;";

		// Learning progress table
		$table_name = $wpdb->prefix . 'coachproai_learning_progress';
		$sql        .= "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			student_id bigint(20) NOT NULL,
			program_id mediumint(9) NOT NULL,
			module_id mediumint(9) NULL,
			activity_type varchar(50) NOT NULL,
			activity_id mediumint(9) NOT NULL,
			progress_percentage decimal(5,2) DEFAULT 0.00,
			time_spent_minutes smallint(5) DEFAULT 0,
			completion_score decimal(5,2) DEFAULT 0.00,
			ai_insights text NULL,
			completed_at datetime NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY student_id (student_id),
			KEY program_id (program_id),
			KEY activity_type (activity_type)
		) $charset_collate;";

		// AI recommendations table
		$table_name = $wpdb->prefix . 'coachproai_recommendations';
		$sql        .= "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			student_id bigint(20) NOT NULL,
			recommendation_type varchar(50) NOT NULL,
			recommendation_data text NOT NULL,
			confidence_score decimal(3,2) DEFAULT 0.00,
			priority_level tinyint(1) DEFAULT 1,
			status varchar(20) DEFAULT 'pending',
			delivered_at datetime NULL,
			feedback_score tinyint(1) NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY student_id (student_id),
			KEY recommendation_type (recommendation_type),
			KEY status (status)
		) $charset_collate;";

		// Analytics data table
		$table_name = $wpdb->prefix . 'coachproai_analytics';
		$sql        .= "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			student_id bigint(20) NOT NULL,
			program_id mediumint(9) NOT NULL,
			event_type varchar(50) NOT NULL,
			event_data text NULL,
			metric_value decimal(10,2) DEFAULT 0.00,
			session_duration smallint(5) DEFAULT 0,
			page_url varchar(255) NULL,
			user_agent text NULL,
			ip_address varchar(45) NULL,
			timestamp datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY student_id (student_id),
			KEY program_id (program_id),
			KEY event_type (event_type),
			KEY timestamp (timestamp)
		) $charset_collate;";

		// AI coach knowledge base table
		$table_name = $wpdb->prefix . 'coachproai_knowledge';
		$sql        .= "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			coach_id mediumint(9) NOT NULL,
			knowledge_type varchar(50) NOT NULL,
			content_data text NOT NULL,
			tags text NULL,
			relevance_score decimal(3,2) DEFAULT 1.00,
			usage_count mediumint(9) DEFAULT 0,
			last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY coach_id (coach_id),
			KEY knowledge_type (knowledge_type)
		) $charset_collate;";

		// Assessment responses table
		$table_name = $wpdb->prefix . 'coachproai_assessments';
		$sql        .= "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			assessment_id mediumint(9) NOT NULL,
			student_id bigint(20) NOT NULL,
			responses_data longtext NOT NULL,
			ai_analysis text NULL,
			personality_score decimal(3,2) DEFAULT 0.00,
			learning_style varchar(50) NULL,
			strengths text NULL,
			areas_for_improvement text NULL,
			submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY assessment_id (assessment_id),
			KEY student_id (student_id)
		) $charset_collate;";

		// Goals tracking table
		$table_name = $wpdb->prefix . 'coachproai_goals';
		$sql        .= "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			student_id bigint(20) NOT NULL,
			program_id mediumint(9) NOT NULL,
			goal_title varchar(255) NOT NULL,
			goal_description text NULL,
			target_date date NULL,
			progress_percentage decimal(5,2) DEFAULT 0.00,
			ai_insights text NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY student_id (student_id),
			KEY program_id (program_id),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create user roles and capabilities
	 */
	public static function create_user_roles() {
		// Coach role
		add_role(
			'coachproai_coach',
			__( 'CoachProAI Coach', 'coachproai-lms' ),
			array(
				'read'                         => true,
				'manage_coaching_programs'     => true,
				'manage_coaching_assessments'  => true,
				'manage_ai_coaches'            => true,
				'view_coaching_analytics'      => true,
				'create_coaching_programs'     => true,
				'edit_coaching_programs'       => true,
				'edit_others_coaching_programs' => true,
				'publish_coaching_programs'    => true,
				'read_private_coaching_programs' => true,
				'view_student_progress'        => true,
				'manage_student_coaching'      => true,
			)
		);

		// Student role
		add_role(
			'coachproai_student',
			__( 'CoachProAI Student', 'coachproai-lms' ),
			array(
				'read'                       => true,
				'access_coaching_programs'   => true,
				'participate_in_assessments' => true,
				'chat_with_ai_coaches'       => true,
				'view_own_progress'          => true,
				'submit_coaching_goals'      => true,
			)
		);

		// Program Admin role
		add_role(
			'coachproai_admin',
			__( 'CoachProAI Program Admin', 'coachproai-lms' ),
			array(
				'read'                             => true,
				'manage_coaching_programs'         => true,
				'manage_coaching_assessments'      => true,
				'manage_ai_coaches'                => true,
				'manage_coaching_analytics'        => true,
				'manage_coaching_settings'         => true,
				'create_coaching_programs'         => true,
				'edit_coaching_programs'           => true,
				'edit_others_coaching_programs'    => true,
				'publish_coaching_programs'        => true,
				'delete_coaching_programs'         => true,
				'delete_others_coaching_programs'  => true,
				'manage_coaching_enrollments'      => true,
				'view_all_coaching_analytics'      => true,
			)
		);

		// Add capabilities to administrator
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_capabilities = array(
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

		 foreach ( $admin_capabilities as $cap ) {
				$admin_role->add_cap( $cap );
			}
		}
	}

	/**
	 * Create required pages
	 */
	public static function create_pages() {
		$pages = array(
			'coachproai-dashboard' => array(
				'title'   => __( 'CoachProAI Dashboard', 'coachproai-lms' ),
				'content' => '[coachproai_dashboard]',
			),
			'coachproai-programs' => array(
				'title'   => __( 'Coaching Programs', 'coachproai-lms' ),
				'content' => '[coachproai_programs]',
			),
			'coachproai-ai-chat' => array(
				'title'   => __( 'AI Coaching Chat', 'coachproai-lms' ),
				'content' => '[coachproai_ai_chat]',
			),
			'coachproai-progress' => array(
				'title'   => __( 'My Progress', 'coachproai-lms' ),
				'content' => '[coachproai_progress]',
			),
		);

	 foreach ( $pages as $slug => $page_data ) {
			$page = get_page_by_path( $slug );
			if ( ! $page ) {
				$page_id = wp_insert_post(
					array(
						'post_title'   => $page_data['title'],
						'post_content' => $page_data['content'],
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'post_slug'    => $slug,
					)
				);

				// Store page ID
				update_option( 'coachproai_page_' . $slug, $page_id );
			}
		}
	}

	/**
	 * Set default options
	 */
	public static function set_default_options() {
		$defaults = array(
			'coachproai_general_settings' => array(
				'programs_page' => get_option( 'coachproai_page_coachproai-programs', 0 ),
				'dashboard_page' => get_option( 'coachproai_page_coachproai-dashboard', 0 ),
				'currency' => 'USD',
				'currency_symbol' => '$',
				'date_format' => 'M j, Y',
				'time_format' => 'g:i a',
			),
			'coachproai_ai_settings' => array(
				'ai_chat_enabled' => 'enabled',
				'ai_recommendations' => 'enabled',
				'ai_coach_personality' => 'supportive',
				'ai_response_style' => 'conversational',
				'ai_max_response_length' => 500,
				'ai_learning_adaptation' => 'enabled',
			),
			'coachproai_coaching_settings' => array(
				'session_duration' => 30,
				'session_reminders' => 'enabled',
				'progress_tracking' => 'enabled',
				'goal_setting' => 'enabled',
				'assessment_frequency' => 'monthly',
				'feedback_collection' => 'enabled',
			),
			'coachproai_analytics_settings' => array(
				'analytics_enabled' => 'enabled',
				'retention_tracking' => 'enabled',
				'engagement_metrics' => 'enabled',
				'progress_visualization' => 'enabled',
				'reporting_frequency' => 'weekly',
			),
			'coachproai_integrations' => array(
				'openai_enabled' => 'disabled',
				'google_analytics' => 'disabled',
				'zoom_integration' => 'disabled',
				'calendar_sync' => 'disabled',
				'email_notifications' => 'enabled',
			),
		);

	 foreach ( $defaults as $option_name => $default_value ) {
			if ( false === get_option( $option_name ) ) {
				update_option( $option_name, $default_value );
			}
		}
	}

	/**
	 * Create default AI coaches
	 */
	public static function create_default_ai_coaches() {
		// Check if AI coaches already exist
		$existing_coaches = get_posts(
			array(
				'post_type' => 'ai_coach',
				'posts_per_page' => 1,
				'fields' => 'ids',
			)
		);

		if ( ! empty( $existing_coaches ) ) {
			return;
		}

		$default_coaches = array(
			array(
				'name' => 'Coach Sarah',
				'specialty' => 'Life & Career Coaching',
				'personality' => 'supportive',
				'description' => 'A warm and encouraging AI coach specializing in life transitions, career development, and personal growth.',
			),
			array(
				'name' => 'Coach Marcus',
				'specialty' => 'Business & Leadership Coaching',
				'personality' => 'professional',
				'description' => 'A strategic and results-focused AI coach helping professionals develop leadership skills and business acumen.',
			),
			array(
				'name' => 'Coach Luna',
				'specialty' => 'Wellness & Mindfulness Coaching',
				'personality' => 'calm',
				'description' => 'A mindful and holistic AI coach focused on wellness, stress management, and work-life balance.',
			),
		);

	 foreach ( $default_coaches as $coach_data ) {
			$post_id = wp_insert_post(
				array(
					'post_title' => $coach_data['name'],
					'post_content' => $coach_data['description'],
					'post_status' => 'publish',
					'post_type' => 'ai_coach',
				)
			);

			if ( $post_id ) {
				// Save coach meta
				update_post_meta( $post_id, '_coach_specialty', $coach_data['specialty'] );
				update_post_meta( $post_id, '_coach_personality', $coach_data['personality'] );
				update_post_meta( $post_id, '_coach_active', 'yes' );
			}
		}
	}

	/**
	 * Uninstall plugin - cleanup database
	 */
	public static function uninstall() {
		global $wpdb;

		// Drop tables
		$tables = array(
			$wpdb->prefix . 'coachproai_profiles',
			$wpdb->prefix . 'coachproai_ai_sessions',
			$wpdb->prefix . 'coachproai_learning_progress',
			$wpdb->prefix . 'coachproai_recommendations',
			$wpdb->prefix . 'coachproai_analytics',
			$wpdb->prefix . 'coachproai_knowledge',
			$wpdb->prefix . 'coachproai_assessments',
			$wpdb->prefix . 'coachproai_goals',
		);

	 foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		// Delete options
		$options = array(
			'coachproai_general_settings',
			'coachproai_ai_settings',
			'coachproai_coaching_settings',
			'coachproai_analytics_settings',
			'coachproai_integrations',
			'coachproai_lms_db_version',
		);

	 foreach ( $options as $option ) {
			delete_option( $option );
		}

		// Delete page IDs
		$pages = array( 'coachproai-dashboard', 'coachproai-programs', 'coachproai-ai-chat', 'coachproai-progress' );
	 foreach ( $pages as $page ) {
			delete_option( 'coachproai_page_' . $page );
		}

		// Remove custom post types data
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
		 if ( $posts ) {
				wp_delete_post( $posts, true );
			}
		}

		// Remove user roles
		remove_role( 'coachproai_coach' );
		remove_role( 'coachproai_student' );
		remove_role( 'coachproai_admin' );
	}

	/**
	 * Check if database needs update
	 * @return bool
	 */
	public static function needs_update() {
		$current_db_version = get_option( 'coachproai_lms_db_version', '0' );
		return version_compare( $current_db_version, COACHPROAI_VERSION, '<' );
	}

	/**
	 * Update database to current version
	 */
	public static function update_database() {
		if ( self::needs_update() ) {
			self::create_tables();
			update_option( 'coachproai_lms_db_version', COACHPROAI_VERSION );
		}
	}
}