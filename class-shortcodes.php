<?php
/**
 * CoachProAI Shortcodes Class
 *
 * @package CoachProAI
 */

namespace CoachProAI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes Class
 */
class Shortcodes {

	/**
	 * Plugin instance
	 * @var Shortcodes
	 */
	private static $instance = null;

	/**
	 * Get instance
	 * @return Shortcodes
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
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode( 'coachproai_programs', array( $this, 'programs_shortcode' ) );
		add_shortcode( 'coachproai_ai_chat', array( $this, 'ai_chat_shortcode' ) );
		add_shortcode( 'coachproai_dashboard', array( $this, 'dashboard_shortcode' ) );
		add_shortcode( 'coachproai_progress', array( $this, 'progress_shortcode' ) );
		add_shortcode( 'coachproai_coaches', array( $this, 'coaches_shortcode' ) );
		add_shortcode( 'coachproai_assessment', array( $this, 'assessment_shortcode' ) );
	}

	/**
	 * Programs shortcode
	 */
	public function programs_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'category'        => '',
				'limit'           => -1,
				'orderby'         => 'date',
				'order'           => 'DESC',
				'columns'         => 3,
				'show_price'      => 'true',
				'show_ai_features' => 'true',
				'show_enrollment' => 'true',
			),
			$atts,
			'coachproai_programs'
		);

		if ( ! class_exists( '\CoachProAI\Core' ) ) {
			return '<p>' . __( 'CoachProAI is not properly loaded.', 'coachproai-lms' ) . '</p>';
		}

		$core = \CoachProAI\Core::instance();

		$args = array(
			'post_type'      => 'coaching_program',
			'posts_per_page' => $atts['limit'],
			'post_status'    => 'publish',
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'program_category',
					'field'    => 'slug',
					'terms'    => explode( ',', $atts['category'] ),
				),
			);
		}

		$query = new \WP_Query( $args );

		ob_start();
		include COACHPROAI_PLUGIN_PATH . 'templates/shortcodes/programs.php';
		return ob_get_clean();
	}

	/**
	 * AI Chat shortcode
	 */
	public function ai_chat_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="coachproai-login-notice">' . 
			       __( 'Please log in to access AI coaching.', 'coachproai-lms' ) . 
			       ' <a href="' . wp_login_url( get_permalink() ) . '">' . 
			       __( 'Login', 'coachproai-lms' ) . '</a></div>';
		}

		$atts = shortcode_atts(
			array(
				'coach_id'   => '',
				'session_id' => '',
				'width'      => '100%',
				'height'     => '500px',
			),
			$atts,
			'coachproai_ai_chat'
		);

		ob_start();
		include COACHPROAI_PLUGIN_PATH . 'templates/shortcodes/ai-chat.php';
		return ob_get_clean();
	}

	/**
	 * Dashboard shortcode
	 */
	public function dashboard_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="coachproai-login-notice">' . 
			       __( 'Please log in to view your dashboard.', 'coachproai-lms' ) . 
			       ' <a href="' . wp_login_url( get_permalink() ) . '">' . 
			       __( 'Login', 'coachproai-lms' ) . '</a></div>';
		}

		$user_id = get_current_user_id();
		$user = get_userdata( $user_id );

		// Check user roles
		$is_coach = in_array( 'coachproai_coach', $user->roles ) || in_array( 'coachproai_admin', $user->roles ) || in_array( 'administrator', $user->roles );
		$is_student = in_array( 'coachproai_student', $user->roles );

		ob_start();
		if ( $is_coach ) {
			include COACHPROAI_PLUGIN_PATH . 'templates/shortcodes/coach-dashboard.php';
		} elseif ( $is_student ) {
			include COACHPROAI_PLUGIN_PATH . 'templates/shortcodes/student-dashboard.php';
		} else {
			include COACHPROAI_PLUGIN_PATH . 'templates/shortcodes/general-dashboard.php';
		}
		return ob_get_clean();
	}

	/**
	 * Progress shortcode
	 */
	public function progress_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="coachproai-login-notice">' . 
			       __( 'Please log in to view your progress.', 'coachproai-lms' ) . 
			       ' <a href="' . wp_login_url( get_permalink() ) . '">' . 
			       __( 'Login', 'coachproai-lms' ) . '</a></div>';
		}

		$atts = shortcode_atts(
			array(
				'program_id'         => '',
				'show_charts'        => 'true',
				'show_recommendations' => 'true',
				'show_goals'         => 'true',
			),
			$atts,
			'coachproai_progress'
		);

		$user_id = get_current_user_id();

		ob_start();
		include COACHPROAI_PLUGIN_PATH . 'templates/shortcodes/progress.php';
		return ob_get_clean();
	}

	/**
	 * Coaches shortcode
	 */
	public function coaches_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'specialty'     => '',
				'limit'         => 6,
				'show_avatar'   => 'true',
				'show_specialty' => 'true',
				'layout'        => 'grid',
			),
			$atts,
			'coachproai_coaches'
		);

		$args = array(
			'post_type'      => 'ai_coach',
			'posts_per_page' => $atts['limit'],
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_coach_active',
					'value'   => 'yes',
					'compare' => '='
				)
			),
		);

		if ( ! empty( $atts['specialty'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_coach_specialty',
				'value'   => $atts['specialty'],
				'compare' => '='
			);
		}

		$query = new \WP_Query( $args );

		ob_start();
		include COACHPROAI_PLUGIN_PATH . 'templates/shortcodes/coaches.php';
		return ob_get_clean();
	}

	/**
	 * Assessment shortcode
	 */
	public function assessment_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="coachproai-login-notice">' . 
			       __( 'Please log in to take assessments.', 'coachproai-lms' ) . 
			       ' <a href="' . wp_login_url( get_permalink() ) . '">' . 
			       __( 'Login', 'coachproai-lms' ) . '</a></div>';
		}

		$atts = shortcode_atts(
			array(
				'assessment_id' => '',
				'show_results'  => 'true',
			),
			$atts,
			'coachproai_assessment'
		);

		if ( empty( $atts['assessment_id'] ) ) {
			return '<p>' . __( 'Please specify an assessment ID.', 'coachproai-lms' ) . '</p>';
		}

		$assessment = get_post( $atts['assessment_id'] );
		if ( ! $assessment || $assessment->post_type !== 'coaching_assessment' ) {
			return '<p>' . __( 'Assessment not found.', 'coachproai-lms' ) . '</p>';
		}

		ob_start();
		include COACHPROAI_PLUGIN_PATH . 'templates/shortcodes/assessment.php';
		return ob_get_clean();
	}

	/**
	 * Get available AI coaches
	 */
	public function get_available_coaches() {
		$args = array(
			'post_type'      => 'ai_coach',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_coach_active',
					'value'   => 'yes',
					'compare' => '='
				)
			),
		);

		$query = new \WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Generate coach selection dropdown
	 */
	public function get_coach_dropdown( $selected = '' ) {
		$coaches = $this->get_available_coaches();
		
		if ( empty( $coaches ) ) {
			return '<p>' . __( 'No AI coaches available.', 'coachproai-lms' ) . '</p>';
		}

		$html = '<select name="coach_id" class="coachproai-coach-select">';
		$html .= '<option value="">' . __( 'Select an AI Coach', 'coachproai-lms' ) . '</option>';
		
	 foreach ( $coaches as $coach ) {
			$specialty = get_post_meta( $coach->ID, '_coach_specialty', true );
			$option_text = $coach->post_title;
			if ( $specialty ) {
				$option_text .= ' (' . $specialty . ')';
			}
			
			$html .= '<option value="' . $coach->ID . '"' . selected( $selected, $coach->ID, false ) . '>';
			$html .= esc_html( $option_text );
			$html .= '</option>';
		}
		
		$html .= '</select>';
		
		return $html;
	}

	/**
	 * Get program categories for filtering
	 */
	public function get_program_categories() {
		return get_terms( array(
			'taxonomy'   => 'program_category',
			'hide_empty' => true,
		) );
	}

	/**
	 * Get coaching focus areas
	 */
	public function get_coaching_focus_areas() {
		return get_terms( array(
			'taxonomy'   => 'coaching_focus',
			'hide_empty' => true,
		) );
	}

	/**
	 * Generate enrollment button HTML
	 */
	public function generate_enrollment_button( $program_id ) {
		if ( ! is_user_logged_in() ) {
			return '<a href="' . wp_login_url( get_permalink() ) . '" class="coachproai-btn coachproai-btn-primary">' . 
			       __( 'Login to Enroll', 'coachproai-lms' ) . '</a>';
		}

		$public_platform = new \CoachProAI\Public\Public_Platform();
		return $public_platform->generate_enrollment_button( $program_id );
	}

	/**
	 * Generate progress display HTML
	 */
	public function generate_progress_display( $program_id, $user_id ) {
		$public_platform = new \CoachProAI\Public\Public_Platform();
		$progress = $public_platform->get_student_program_progress( $user_id, $program_id );
		
		return '<div class="coachproai-progress-display">
			<div class="coachproai-progress-bar-container">
				<div class="coachproai-progress-bar" style="width: ' . $progress . '%"></div>
			</div>
			<div class="coachproai-progress-text">' . $progress . '% Complete</div>
		</div>';
	}
}