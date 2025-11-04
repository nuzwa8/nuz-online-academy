<?php
/**
 * CoachProAI Database Class
 *
 * @package CoachProAI
 */

namespace CoachProAI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database Class - Handles database operations
 */
class Database {

	/**
	 * Plugin instance
	 * @var Database
	 */
	private static $instance = null;

	/**
	 * Get instance
	 * @return Database
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'check_db_version' ) );
		add_action( 'upgrader_process_complete', array( $this, 'update_database' ) );
	}

	/**
	 * Check database version
	 */
	public function check_db_version() {
		if ( get_option( 'coachproai_lms_db_version', '0' ) !== COACHPROAI_VERSION ) {
			Install::update_database();
		}
	}

	/**
	 * Update database schema
	 */
	public function update_database() {
		Install::update_database();
	}

	/**
	 * Get enrollment data
	 */
	public function get_enrollment( $student_id, $program_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_profiles';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE student_id = %d AND program_id = %d",
				$student_id,
				$program_id
			),
			ARRAY_A
		);
	}

	/**
	 * Create new enrollment
	 */
	public function create_enrollment( $student_id, $program_id, $enrollment_data = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_profiles';

		$default_data = array(
			'student_id' => $student_id,
			'program_id' => $program_id,
			'learning_preferences' => wp_json_encode( array() ),
			'personality_traits' => wp_json_encode( array() ),
			'goals_data' => wp_json_encode( array() ),
			'progress_summary' => wp_json_encode( array() ),
			'created_at' => current_time( 'mysql' ),
		);

		$data = array_merge( $default_data, $enrollment_data );

		$result = $wpdb->insert(
			$table_name,
			$data,
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update student profile
	 */
	public function update_profile( $profile_id, $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_profiles';

		$result = $wpdb->update(
			$table_name,
			$data,
			array( 'id' => $profile_id ),
			null,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get learning progress
	 */
	public function get_learning_progress( $student_id, $program_id = null, $limit = 10 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_learning_progress';

		$where_clause = $wpdb->prepare( " WHERE student_id = %d", $student_id );
		
		if ( $program_id ) {
			$where_clause .= $wpdb->prepare( " AND program_id = %d", $program_id );
		}

		$sql = "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC LIMIT %d";

		return $wpdb->get_results(
			$wpdb->prepare( $sql, $limit ),
			ARRAY_A
		);
	}

	/**
	 * Get AI session data
	 */
	public function get_ai_session( $session_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_ai_sessions';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$session_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get student sessions
	 */
	public function get_student_sessions( $student_id, $limit = 20 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_ai_sessions';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, p.post_title as coach_name 
				 FROM {$table_name} s 
				 LEFT JOIN {$wpdb->posts} p ON s.ai_coach_id = p.ID 
				 WHERE s.student_id = %d 
				 ORDER BY s.started_at DESC 
				 LIMIT %d",
				$student_id,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get analytics data
	 */
	public function get_analytics_data( $student_id = null, $date_from = null, $date_to = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_analytics';

		$where_conditions = array();
		$where_values = array();

		if ( $student_id ) {
			$where_conditions[] = "student_id = %d";
			$where_values[] = $student_id;
		}

		if ( $date_from ) {
			$where_conditions[] = "timestamp >= %s";
			$where_values[] = $date_from . ' 00:00:00';
		}

		if ( $date_to ) {
			$where_conditions[] = "timestamp <= %s";
			$where_values[] = $date_to . ' 23:59:59';
		}

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		$sql = "SELECT 
					event_type,
					COUNT(*) as event_count,
					AVG(metric_value) as avg_metric,
					AVG(session_duration) as avg_duration
				FROM {$table_name} 
				{$where_clause}
				GROUP BY event_type";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Track event
	 */
	public function track_event( $student_id, $program_id, $event_type, $event_data = array(), $metric_value = 0, $session_duration = 0 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_analytics';

		$result = $wpdb->insert(
			$table_name,
			array(
				'student_id'     => $student_id,
				'program_id'     => $program_id,
				'event_type'     => $event_type,
				'event_data'     => wp_json_encode( $event_data ),
				'metric_value'   => $metric_value,
				'session_duration' => $session_duration,
				'page_url'       => isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '',
				'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
				'ip_address'     => $this->get_user_ip(),
				'timestamp'      => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Get user IP address
	 */
	private function get_user_ip() {
		$ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );
		
	 foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = $_SERVER[ $key ];
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip );
					$ip = trim( $ip[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}
		
		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}

	/**
	 * Get program statistics
	 */
	public function get_program_stats( $program_id ) {
		global $wpdb;

		$profiles_table = $wpdb->prefix . 'coachproai_profiles';
		$progress_table = $wpdb->prefix . 'coachproai_learning_progress';
		$analytics_table = $wpdb->prefix . 'coachproai_analytics';

		// Get total students
		$total_students = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT student_id) FROM {$profiles_table} WHERE program_id = %d",
				$program_id
			)
		);

		// Get active students (with activity in last 7 days)
		$active_students = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT student_id) FROM {$progress_table} 
				 WHERE program_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
				$program_id
			)
		);

		// Get average progress
		$avg_progress = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(progress_percentage) FROM {$progress_table} 
				 WHERE program_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
				$program_id
			)
		);

		// Get completion rate
		$completion_rate = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT student_id) / (SELECT COUNT(*) FROM {$profiles_table} WHERE program_id = %d) * 100 
				 FROM {$progress_table} 
				 WHERE program_id = %d AND progress_percentage >= 100",
				$program_id,
				$program_id
			)
		);

		return array(
			'total_students' => intval( $total_students ),
			'active_students' => intval( $active_students ),
			'avg_progress' => round( floatval( $avg_progress ), 1 ),
			'completion_rate' => round( floatval( $completion_rate ), 1 ),
		);
	}

	/**
	 * Get coach performance metrics
	 */
	public function get_coach_performance( $coach_id ) {
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'coachproai_ai_sessions';

		// Total sessions
		$total_sessions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$sessions_table} WHERE ai_coach_id = %d",
				$coach_id
			)
		);

		// Average session duration
		$avg_duration = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(duration_minutes) FROM {$sessions_table} WHERE ai_coach_id = %d AND duration_minutes > 0",
				$coach_id
			)
		);

		// Average session rating
		$avg_rating = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(session_rating) FROM {$sessions_table} WHERE ai_coach_id = %d AND session_rating > 0",
				$coach_id
			)
		);

		return array(
			'total_sessions' => intval( $total_sessions ),
			'avg_duration' => round( floatval( $avg_duration ), 1 ),
			'avg_rating' => round( floatval( $avg_rating ), 1 ),
		);
	}

	/**
	 * Clean up old data
	 */
	public function cleanup_old_data( $days = 90 ) {
		global $wpdb;

		$analytics_table = $wpdb->prefix . 'coachproai_analytics';
		$progress_table = $wpdb->prefix . 'coachproai_learning_progress';

		// Clean old analytics data
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$analytics_table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		// Clean old progress data (keep aggregated data)
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$progress_table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY) AND progress_percentage < 100",
				$days
			)
		);
	}

	/**
	 * Export data for analytics
	 */
	public function export_analytics_data( $format = 'json', $date_range = '30 days' ) {
		global $wpdb;

		$analytics_table = $wpdb->prefix . 'coachproai_analytics';

		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$analytics_table} WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %s)",
				$date_range
			),
			ARRAY_A
		);

		switch ( $format ) {
			case 'csv':
				return $this->convert_to_csv( $data );
			case 'json':
			default:
				return wp_json_encode( $data );
		}
	}

	/**
	 * Convert data to CSV format
	 */
	private function convert_to_csv( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$headers = array_keys( $data[0] );
		$csv = implode( ',', $headers ) . "\n";

	 foreach ( $data as $row ) {
			$csv .= implode( ',', array_map( function( $value ) {
				return '"' . str_replace( '"', '""', $value ) . '"';
			}, $row ) ) . "\n";
		}

		return $csv;
	}
}