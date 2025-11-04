<?php
/**
 * CoachProAI Core Class
 *
 * @package CoachProAI
 */

namespace CoachProAI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Core Class
 */
class Core {

	/**
	 * Plugin instance
	 * @var Core
	 */
	private static $instance = null;

	/**
	 * AI Coaching system
	 * @var AI\Coaching_Engine
	 */
	public $ai_coaching = null;

	/**
	 * Analytics system
	 * @var Analytics\Analyzer
	 */
	public $analytics = null;

	/**
	 * Progress tracking
	 * @var Progress\Tracker
	 */
	public $progress = null;

	/**
	 * Get instance
	 * @return Core
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
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		
		// AI System hooks
		add_action( 'coachproai_after_student_enroll', array( $this, 'initialize_ai_coaching' ), 10, 2 );
		add_action( 'coachproai_after_lesson_complete', array( $this, 'update_ai_coaching' ), 10, 3 );
		
		// Analytics hooks
		add_action( 'wp_footer', array( $this, 'track_user_behavior' ) );
		add_action( 'coachproai_session_completed', array( $this, 'update_progress_analytics' ) );
	}

	/**
	 * Register post types
	 */
	public function register_post_types() {
		// Coaching Program post type
		register_post_type(
			'coaching_program',
			array(
				'labels'             => array(
					'name'               => __( 'Coaching Programs', 'coachproai-lms' ),
					'singular_name'      => __( 'Coaching Program', 'coachproai-lms' ),
					'menu_name'          => __( 'Coaching Programs', 'coachproai-lms' ),
					'add_new'            => __( 'Add New Program', 'coachproai-lms' ),
					'add_new_item'       => __( 'Add New Coaching Program', 'coachproai-lms' ),
					'edit_item'          => __( 'Edit Coaching Program', 'coachproai-lms' ),
					'new_item'           => __( 'New Coaching Program', 'coachproai-lms' ),
					'view_item'          => __( 'View Coaching Program', 'coachproai-lms' ),
					'search_items'       => __( 'Search Programs', 'coachproai-lms' ),
					'not_found'          => __( 'No programs found', 'coachproai-lms' ),
					'not_found_in_trash' => __( 'No programs found in Trash', 'coachproai-lms' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => 'coaching-program' ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 5,
				'menu_icon'          => 'dashicons-admin-network',
				'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
				'show_in_rest'       => true,
			)
		);

		// AI Coach post type
		register_post_type(
			'ai_coach',
			array(
				'labels'             => array(
					'name'               => __( 'AI Coaches', 'coachproai-lms' ),
					'singular_name'      => __( 'AI Coach', 'coachproai-lms' ),
					'menu_name'          => __( 'AI Coaches', 'coachproai-lms' ),
					'add_new'            => __( 'Add New Coach', 'coachproai-lms' ),
					'add_new_item'       => __( 'Add New AI Coach', 'coachproai-lms' ),
					'edit_item'          => __( 'Edit AI Coach', 'coachproai-lms' ),
					'new_item'           => __( 'New AI Coach', 'coachproai-lms' ),
					'view_item'          => __( 'View AI Coach', 'coachproai-lms' ),
					'search_items'       => __( 'Search AI Coaches', 'coachproai-lms' ),
					'not_found'          => __( 'No AI coaches found', 'coachproai-lms' ),
					'not_found_in_trash' => __( 'No AI coaches found in Trash', 'coachproai-lms' ),
				),
				'public'             => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'capability_type'    => 'post',
				'supports'           => array( 'title', 'editor', 'thumbnail' ),
			)
		);

		// Assessment post type
		register_post_type(
			'coaching_assessment',
			array(
				'labels'             => array(
					'name'               => __( 'Assessments', 'coachproai-lms' ),
					'singular_name'      => __( 'Assessment', 'coachproai-lms' ),
					'menu_name'          => __( 'Assessments', 'coachproai-lms' ),
					'add_new'            => __( 'Add New', 'coachproai-lms' ),
					'add_new_item'       => __( 'Add New Assessment', 'coachproai-lms' ),
					'edit_item'          => __( 'Edit Assessment', 'coachproai-lms' ),
					'new_item'           => __( 'New Assessment', 'coachproai-lms' ),
					'view_item'          => __( 'View Assessment', 'coachproai-lms' ),
					'search_items'       => __( 'Search Assessments', 'coachproai-lms' ),
					'not_found'          => __( 'No assessments found', 'coachproai-lms' ),
					'not_found_in_trash' => __( 'No assessments found in Trash', 'coachproai-lms' ),
				),
				'public'             => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'capability_type'    => 'post',
				'supports'           => array( 'title', 'editor' ),
			)
		);
	}

	/**
	 * Register taxonomies
	 */
	public function register_taxonomies() {
		// Program category taxonomy
		register_taxonomy(
			'program_category',
			'coaching_program',
			array(
				'hierarchical'      => true,
				'labels'            => array(
					'name'              => __( 'Program Categories', 'coachproai-lms' ),
					'singular_name'     => __( 'Program Category', 'coachproai-lms' ),
					'search_items'      => __( 'Search Categories', 'coachproai-lms' ),
					'all_items'         => __( 'All Categories', 'coachproai-lms' ),
					'parent_item'       => __( 'Parent Category', 'coachproai-lms' ),
					'parent_item_colon' => __( 'Parent Category:', 'coachproai-lms' ),
					'edit_item'         => __( 'Edit Category', 'coachproai-lms' ),
					'update_item'       => __( 'Update Category', 'coachproai-lms' ),
					'add_new_item'      => __( 'Add New Category', 'coachproai-lms' ),
					'new_item_name'     => __( 'New Category Name', 'coachproai-lms' ),
					'menu_name'         => __( 'Categories', 'coachproai-lms' ),
				),
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'program-category' ),
				'show_in_rest'      => true,
			)
		);

		// Coaching focus taxonomy
		register_taxonomy(
			'coaching_focus',
			'coaching_program',
			array(
				'hierarchical'      => false,
				'labels'            => array(
					'name'                       => __( 'Coaching Focus Areas', 'coachproai-lms' ),
					'singular_name'              => __( 'Focus Area', 'coachproai-lms' ),
					'search_items'               => __( 'Search Focus Areas', 'coachproai-lms' ),
					'popular_items'              => __( 'Popular Focus Areas', 'coachproai-lms' ),
					'all_items'                  => __( 'All Focus Areas', 'coachproai-lms' ),
					'edit_item'                  => __( 'Edit Focus Area', 'coachproai-lms' ),
					'update_item'                => __( 'Update Focus Area', 'coachproai-lms' ),
					'add_new_item'               => __( 'Add New Focus Area', 'coachproai-lms' ),
					'new_item_name'              => __( 'New Focus Area Name', 'coachproai-lms' ),
					'separate_items_with_commas' => __( 'Separate focus areas with commas', 'coachproai-lms' ),
					'add_or_remove_items'        => __( 'Add or remove focus areas', 'coachproai-lms' ),
					'choose_from_most_used'      => __( 'Choose from the most used focus areas', 'coachproai-lms' ),
					'menu_name'                  => __( 'Focus Areas', 'coachproai-lms' ),
				),
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'coaching-focus' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode( 'coachproai_programs', array( $this, 'programs_shortcode' ) );
		add_shortcode( 'coachproai_coaching_session', array( $this, 'coaching_session_shortcode' ) );
		add_shortcode( 'coachproai_ai_chat', array( $this, 'ai_chat_shortcode' ) );
		add_shortcode( 'coachproai_dashboard', array( $this, 'dashboard_shortcode' ) );
		add_shortcode( 'coachproai_progress', array( $this, 'progress_shortcode' ) );
	}

	/**
	 * Programs shortcode
	 */
	public function programs_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'category'     => '',
				'limit'        => -1,
				'orderby'      => 'date',
				'order'        => 'DESC',
				'columns'      => 3,
				'show_price'   => 'true',
				'show_ai_features' => 'true',
			),
			$atts,
			'coachproai_programs'
		);

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
			return '<p>' . __( 'Please log in to access AI coaching.', 'coachproai-lms' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'coach_id' => '',
				'session_id' => '',
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
			return '<p>' . __( 'Please log in to view your dashboard.', 'coachproai-lms' ) . '</p>';
		}

		$user_id = get_current_user_id();
		$user = get_userdata( $user_id );

		// Check user role
		$is_coach = in_array( 'coachproai_coach', $user->roles ) || in_array( 'administrator', $user->roles );
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
			return '<p>' . __( 'Please log in to view your progress.', 'coachproai-lms' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'program_id' => '',
				'show_charts' => 'true',
				'show_recommendations' => 'true',
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
	 * Enqueue frontend scripts and styles
	 */
	public function enqueue_frontend_scripts() {
		wp_enqueue_style( 'coachproai-frontend', COACHPROAI_PLUGIN_URL . 'assets/css/frontend.css', array(), COACHPROAI_VERSION );
		wp_enqueue_script( 'coachproai-frontend', COACHPROAI_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery', 'wp-api' ), COACHPROAI_VERSION, true );

		// AI Chat specific scripts
		if ( has_shortcode( get_post()->post_content, 'coachproai_ai_chat' ) ) {
			wp_enqueue_script( 'coachproai-ai-chat', COACHPROAI_PLUGIN_URL . 'assets/js/ai-chat.js', array( 'jquery' ), COACHPROAI_VERSION, true );
		}

		// Chart.js for progress visualization
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true );

		// Localize script
		wp_localize_script(
			'coachproai-frontend',
			'coachproai_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'coachproai_nonce' ),
				'rest_url' => rest_url( 'coachproai/v1/' ),
				'rest_nonce' => wp_create_nonce( 'wp_rest' ),
				'user_id' => get_current_user_id(),
			)
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'coachproai' ) !== false ) {
			wp_enqueue_style( 'coachproai-admin', COACHPROAI_PLUGIN_URL . 'assets/css/admin.css', array(), COACHPROAI_VERSION );
			wp_enqueue_script( 'coachproai-admin', COACHPROAI_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), COACHPROAI_VERSION, true );
			
			// Chart.js for admin analytics
			wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true );
		}

		// Localize script
		wp_localize_script(
			'coachproai-admin',
			'coachproai_admin_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'coachproai_admin_nonce' ),
			)
		);
	}

	/**
	 * Add query vars
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'coaching-program';
		$vars[] = 'ai-coach';
		$vars[] = 'coaching-session';
		return $vars;
	}

	/**
	 * Initialize AI coaching for new student
	 */
	public function initialize_ai_coaching( $program_id, $student_id ) {
		if ( $this->ai_coaching ) {
			$this->ai_coaching->initialize_student_profile( $program_id, $student_id );
		}
	}

	/**
	 * Update AI coaching after lesson completion
	 */
	public function update_ai_coaching( $lesson_id, $student_id, $progress_data ) {
		if ( $this->ai_coaching ) {
			$this->ai_coaching->update_learning_progress( $lesson_id, $student_id, $progress_data );
		}
	}

	/**
	 * Track user behavior for analytics
	 */
	public function track_user_behavior() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		$current_page = get_permalink();
		$time_on_page = time();

		// Store in user meta for later processing
		$activity_log = get_user_meta( $user_id, '_coachproai_activity_log', true ) ?: array();
		$activity_log[] = array(
			'page' => $current_page,
			'timestamp' => $time_on_page,
			'date' => current_time( 'mysql' ),
		);

		// Keep only last 100 activities
		if ( count( $activity_log ) > 100 ) {
			$activity_log = array_slice( $activity_log, -100 );
		}

		update_user_meta( $user_id, '_coachproai_activity_log', $activity_log );
	}

	/**
	 * Update progress analytics
	 */
	public function update_progress_analytics( $session_data ) {
		if ( $this->analytics ) {
			$this->analytics->process_session_completion( $session_data );
		}
	}

	/**
	 * Get AI coaching engine
	 */
	public function get_ai_coaching() {
		if ( ! $this->ai_coaching && class_exists( '\CoachProAI\AI\Coaching_Engine' ) ) {
			$this->ai_coaching = new \CoachProAI\AI\Coaching_Engine();
		}
		return $this->ai_coaching;
	}

	/**
	 * Get analytics engine
	 */
	public function get_analytics() {
		if ( ! $this->analytics && class_exists( '\CoachProAI\Analytics\Analyzer' ) ) {
			$this->analytics = new \CoachProAI\Analytics\Analyzer();
		}
		return $this->analytics;
	}

	/**
	 * Get progress tracker
	 */
	public function get_progress_tracker() {
		if ( ! $this->progress && class_exists( '\CoachProAI\Progress\Tracker' ) ) {
			$this->progress = new \CoachProAI\Progress\Tracker();
		}
		return $this->progress;
	}
}