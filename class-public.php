<?php
/**
 * CoachProAI Public Class
 *
 * @package CoachProAI\Public
 */

namespace CoachProAI\Public;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public Class
 */
class Public_Platform {

	/**
	 * Plugin instance
	 * @var Public_Platform
	 */
	private static $instance = null;

	/**
	 * Get instance
	 * @return Public_Platform
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
		add_action( 'wp_head', array( $this, 'add_structured_data' ) );
		add_action( 'wp_footer', array( $this, 'add_tracking_code' ) );
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );
		add_action( 'wp_ajax_coachproai_enroll_program', array( $this, 'ajax_enroll_program' ) );
		add_action( 'wp_ajax_nopriv_coachproai_enroll_program', array( $this, 'ajax_enroll_program' ) );
		add_action( 'wp_ajax_coachproai_get_progress', array( $this, 'ajax_get_progress' ) );
		add_action( 'wp_ajax_coachproai_get_progress', array( $this, 'ajax_get_progress' ) );
	}

	/**
	 * Add structured data for SEO
	 */
	public function add_structured_data() {
		if ( is_singular( 'coaching_program' ) ) {
			$program_id = get_the_ID();
			$program_data = $this->get_program_structured_data( $program_id );

			if ( $program_data ) {
				echo '<script type="application/ld+json">' . wp_json_encode( $program_data ) . '</script>';
			}
		}
	}

	/**
	 * Get program structured data for SEO
	 */
	private function get_program_structured_data( $program_id ) {
		$program = get_post( $program_id );
		if ( ! $program ) {
			return false;
		}

		$price = get_post_meta( $program_id, '_coachproai_price', true );
		$duration = get_post_meta( $program_id, '_coachproai_duration', true );

		return array(
			'@context' => 'https://schema.org',
			'@type' => 'Course',
			'name' => $program->post_title,
			'description' => wp_strip_all_tags( $program->post_content ),
			'provider' => array(
				'@type' => 'Organization',
				'name' => get_bloginfo( 'name' ),
				'sameAs' => get_site_url(),
			),
			'offers' => array(
				'@type' => 'Offer',
				'price' => $price ? $price : '0',
				'priceCurrency' => get_option( 'coachproai_general_settings' )['currency'] ?? 'USD',
				'availability' => 'https://schema.org/InStock',
			),
		);
	}

	/**
	 * Add tracking code
	 */
	public function add_tracking_code() {
		$settings = get_option( 'coachproai_integrations', array() );
		
		if ( isset( $settings['google_analytics'] ) && $settings['google_analytics'] === 'enabled' ) {
			$ga_id = $settings['google_analytics_id'] ?? '';
			if ( ! empty( $ga_id ) ) {
				$this->output_google_analytics( $ga_id );
			}
		}
	}

	/**
	 * Output Google Analytics code
	 */
	private function output_google_analytics( $ga_id ) {
		?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', '<?php echo esc_js( $ga_id ); ?>');
		</script>
		<?php
	}

	/**
	 * Add body classes
	 */
	public function add_body_classes( $classes ) {
		if ( is_singular( 'coaching_program' ) ) {
			$classes[] = 'single-coaching-program';
		}

		if ( is_post_type_archive( 'coaching_program' ) ) {
			$classes[] = 'archive-coaching-programs';
		}

		if ( has_shortcode( get_post()->post_content ?? '', 'coachproai_ai_chat' ) ) {
			$classes[] = 'coachproai-ai-chat-enabled';
		}

		return $classes;
	}

	/**
	 * AJAX: Enroll in program
	 */
	public function ajax_enroll_program() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to enroll in programs.', 'coachproai-lms' ) );
		}

		$program_id = intval( $_POST['program_id'] ?? 0 );
		$student_id = get_current_user_id();

		if ( ! $program_id ) {
			wp_send_json_error( __( 'Invalid program.', 'coachproai-lms' ) );
		}

		// Check if already enrolled
		$db = \CoachProAI\Database::instance();
		$existing_enrollment = $db->get_enrollment( $student_id, $program_id );

		if ( $existing_enrollment ) {
			wp_send_json_error( __( 'You are already enrolled in this program.', 'coachproai-lms' ) );
		}

		// Check capacity
		$max_students = get_post_meta( $program_id, '_coachproai_max_students', true );
		if ( $max_students ) {
			$current_students = wp_count_posts( 'coaching_program' )->publish; // This would need proper implementation
			if ( $current_students >= $max_students ) {
				wp_send_json_error( __( 'This program is at maximum capacity.', 'coachproai-lms' ) );
			}
		}

		// Create enrollment
		$enrollment_id = $db->create_enrollment( $student_id, $program_id );

		if ( $enrollment_id ) {
			// Initialize AI coaching profile
			$core = \CoachProAI\Core::instance();
			if ( method_exists( $core, 'initialize_ai_coaching' ) ) {
				$core->initialize_ai_coaching( $program_id, $student_id );
			}

			// Track enrollment
			$db->track_event( $student_id, $program_id, 'program_enrolled', array(
				'enrollment_id' => $enrollment_id,
				'program_title' => get_the_title( $program_id ),
			) );

			wp_send_json_success( array(
				'message' => __( 'Successfully enrolled! AI coaching profile created.', 'coachproai-lms' ),
				'enrollment_id' => $enrollment_id,
			) );
		} else {
			wp_send_json_error( __( 'Failed to enroll. Please try again.', 'coachproai-lms' ) );
		}
	}

	/**
	 * AJAX: Get progress data
	 */
	public function ajax_get_progress() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to view progress.', 'coachproai-lms' ) );
		}

		$student_id = get_current_user_id();
		$program_id = intval( $_POST['program_id'] ?? 0 );

		$db = \CoachProAI\Database::instance();
		$progress_data = $db->get_learning_progress( $student_id, $program_id );

		// Calculate progress percentage
		$total_activities = count( $progress_data );
		$completed_activities = array_filter( $progress_data, function( $activity ) {
			return $activity['progress_percentage'] >= 100;
		} );
		$progress_percentage = $total_activities > 0 ? ( count( $completed_activities ) / $total_activities ) * 100 : 0;

		wp_send_json_success( array(
			'progress' => round( $progress_percentage, 1 ),
			'activities' => $total_activities,
			'completed' => count( $completed_activities ),
			'data' => $progress_data,
		) );
	}

	/**
	 * Get student dashboard data
	 */
	public function get_student_dashboard_data( $student_id ) {
		$db = \CoachProAI\Database::instance();
		
		// Get enrolled programs
		$enrollments = array(); // This would need proper implementation
		
		// Get recent AI sessions
		$recent_sessions = $db->get_student_sessions( $student_id, 5 );
		
		// Get recent progress
		$recent_progress = $db->get_learning_progress( $student_id, null, 10 );

		// Get recommendations
		$ai_coaching = \CoachProAI\AI\AI_Coaching::instance();
		$recommendations = $ai_coaching->get_student_recommendations( $student_id );

		return array(
			'enrolled_programs' => $enrollments,
			'recent_sessions' => $recent_sessions,
			'recent_progress' => $recent_progress,
			'recommendations' => $recommendations,
		);
	}

	/**
	 * Get coach dashboard data
	 */
	public function get_coach_dashboard_data( $coach_id ) {
		// This would need proper implementation for coach-specific data
		$ai_coaching = \CoachProAI\AI\AI_Coaching::instance();
		
		// Get coach performance metrics
		$db = \CoachProAI\Database::instance();
		$performance = $db->get_coach_performance( $coach_id );

		// Get active students
		$active_students = array(); // This would need implementation

		return array(
			'performance' => $performance,
			'active_students' => $active_students,
			'sessions_this_month' => 0, // This would need calculation
		);
	}

	/**
	 * Format currency
	 */
	public function format_currency( $amount ) {
		$settings = get_option( 'coachproai_general_settings', array() );
		$currency = $settings['currency'] ?? 'USD';
		$symbol = $settings['currency_symbol'] ?? '$';
		$position = $settings['currency_position'] ?? 'before';

		if ( $position === 'before' ) {
			return $symbol . number_format( floatval( $amount ), 2 );
		} else {
			return number_format( floatval( $amount ), 2 ) . $symbol;
		}
	}

	/**
	 * Get program price display
	 */
	public function get_program_price_display( $program_id ) {
		$price = get_post_meta( $program_id, '_coachproai_price', true );
		
		if ( ! $price || $price <= 0 ) {
			return __( 'Free', 'coachproai-lms' );
		}

		return $this->format_currency( $price );
	}

	/**
	 * Check if program is available for enrollment
	 */
	public function is_program_available( $program_id ) {
		$max_students = get_post_meta( $program_id, '_coachproai_max_students', true );
		
		if ( ! $max_students ) {
			return true; // No limit set
		}

		// Check current enrollment (this would need proper implementation)
		$current_students = 0; // Placeholder
		
		return $current_students < $max_students;
	}

	/**
	 * Get program duration display
	 */
	public function get_program_duration_display( $program_id ) {
		$duration = get_post_meta( $program_id, '_coachproai_duration', true );
		
		if ( ! $duration ) {
			return '';
		}

		$unit = $duration > 1 ? 'weeks' : 'week';
		return $duration . ' ' . $unit;
	}

	/**
	 * Generate program enrollment button
	 */
	public function generate_enrollment_button( $program_id ) {
		$is_logged_in = is_user_logged_in();
		$available = $this->is_program_available( $program_id );
		$price = $this->get_program_price_display( $program_id );

		if ( ! $is_logged_in ) {
			return '<button class="coachproai-btn coachproai-btn-primary" onclick="coachproai.showLoginForm()">' . __( 'Log in to Enroll', 'coachproai-lms' ) . '</button>';
		}

		if ( ! $available ) {
			return '<button class="coachproai-btn coachproai-btn-disabled" disabled>' . __( 'Program Full', 'coachproai-lms' ) . '</button>';
		}

		if ( $price === __( 'Free', 'coachproai-lms' ) ) {
			return '<button class="coachproai-btn coachproai-btn-success" onclick="coachproai.enrollProgram(' . $program_id . ')">' . __( 'Enroll for Free', 'coachproai-lms' ) . '</button>';
		}

		return '<button class="coachproai-btn coachproai-btn-primary" onclick="coachproai.enrollProgram(' . $program_id . ')">' . sprintf( __( 'Enroll for %s', 'coachproai-lms' ), $price ) . '</button>';
	}

	/**
	 * Get program progress for student
	 */
	public function get_student_program_progress( $student_id, $program_id ) {
		$db = \CoachProAI\Database::instance();
		$progress_data = $db->get_learning_progress( $student_id, $program_id );

		if ( empty( $progress_data ) ) {
			return 0;
		}

		$total_activities = count( $progress_data );
		$completed_activities = array_filter( $progress_data, function( $activity ) {
			return $activity['progress_percentage'] >= 100;
		} );

		return $total_activities > 0 ? round( ( count( $completed_activities ) / $total_activities ) * 100, 1 ) : 0;
	}

	/**
	 * Get next recommended activity for student
	 */
	public function get_next_activity( $student_id, $program_id ) {
		$db = \CoachProAI\Database::instance();
		$progress_data = $db->get_learning_progress( $student_id, $program_id );

		// Find incomplete activities
		$incomplete_activities = array_filter( $progress_data, function( $activity ) {
			return $activity['progress_percentage'] < 100;
		} );

		// Return first incomplete activity
		if ( ! empty( $incomplete_activities ) ) {
			return reset( $incomplete_activities );
		}

		// If all completed, return null
		return null;
	}
}