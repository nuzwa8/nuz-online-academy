<?php
/**
 * CoachProAI AJAX Handler Class
 *
 * @package CoachProAI
 */

namespace CoachProAI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX Handler Class
 */
class Ajax {

	/**
	 * Plugin instance
	 * @var Ajax
	 */
	private static $instance = null;

	/**
	 * Get instance
	 * @return Ajax
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
		// Register all AJAX handlers
		add_action( 'wp_ajax_coachproai_get_programs', array( $this, 'get_programs' ) );
		add_action( 'wp_ajax_coachproai_search_programs', array( $this, 'search_programs' ) );
		add_action( 'wp_ajax_coachproai_get_program_details', array( $this, 'get_program_details' ) );
		add_action( 'wp_ajax_coachproai_track_activity', array( $this, 'track_activity' ) );
		add_action( 'wp_ajax_coachproai_update_progress', array( $this, 'update_progress' ) );
		add_action( 'wp_ajax_coachproai_submit_assessment', array( $this, 'submit_assessment' ) );
		
		// Non-logged in handlers
		add_action( 'wp_ajax_nopriv_coachproai_get_programs', array( $this, 'get_programs' ) );
		add_action( 'wp_ajax_nopriv_coachproai_search_programs', array( $this, 'search_programs' ) );
		add_action( 'wp_ajax_nopriv_coachproai_get_program_details', array( $this, 'get_program_details' ) );
	}

	/**
	 * Get programs list
	 */
	public function get_programs() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		$page = intval( $_POST['page'] ?? 1 );
		$per_page = intval( $_POST['per_page'] ?? 10 );
		$category = sanitize_text_field( $_POST['category'] ?? '' );
		$level = sanitize_text_field( $_POST['level'] ?? '' );
		$search = sanitize_text_field( $_POST['search'] ?? '' );

		$args = array(
			'post_type'      => 'coaching_program',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
		);

		// Add search filter
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Add taxonomy filters
		$tax_query = array();
		if ( ! empty( $category ) ) {
			$tax_query[] = array(
				'taxonomy' => 'program_category',
				'field'    => 'slug',
				'terms'    => $category,
			);
		}

		if ( ! empty( $level ) ) {
			$tax_query[] = array(
				'taxonomy' => 'coaching_focus',
				'field'    => 'slug',
				'terms'    => $level,
			);
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		$query = new \WP_Query( $args );
		$programs = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$program_id = get_the_ID();

				$program = array(
					'id' => $program_id,
					'title' => get_the_title(),
					'excerpt' => get_the_excerpt(),
					'content' => wp_trim_words( get_the_content(), 30 ),
					'permalink' => get_permalink(),
					'thumbnail' => get_the_post_thumbnail_url( $program_id, 'medium' ),
					'price' => get_post_meta( $program_id, '_coachproai_price', true ),
					'duration' => get_post_meta( $program_id, '_coachproai_duration', true ),
					'level' => get_post_meta( $program_id, '_coachproai_level', true ),
					'max_students' => get_post_meta( $program_id, '_coachproai_max_students', true ),
					'categories' => wp_get_post_terms( $program_id, 'program_category', array( 'fields' => 'names' ) ),
					'focus_areas' => wp_get_post_terms( $program_id, 'coaching_focus', array( 'fields' => 'names' ) ),
				);

				$programs[] = $program;
			}
			wp_reset_postdata();
		}

		wp_send_json_success( array(
			'programs' => $programs,
			'pagination' => array(
				'current_page' => $page,
				'per_page' => $per_page,
				'total' => $query->found_posts,
				'total_pages' => $query->max_num_pages,
			),
		) );
	}

	/**
	 * Search programs
	 */
	public function search_programs() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		$query = sanitize_text_field( $_POST['query'] ?? '' );
		$limit = intval( $_POST['limit'] ?? 10 );

		if ( empty( $query ) ) {
			wp_send_json_error( __( 'Search query is required.', 'coachproai-lms' ) );
		}

		$args = array(
			'post_type'      => 'coaching_program',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			's'              => $query,
		);

		$search_query = new \WP_Query( $args );
		$results = array();

		if ( $search_query->have_posts() ) {
			while ( $search_query->have_posts() ) {
				$search_query->the_post();
				$results[] = array(
					'id' => get_the_ID(),
					'title' => get_the_title(),
					'excerpt' => wp_trim_words( get_the_excerpt(), 15 ),
					'permalink' => get_permalink(),
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( array(
			'query' => $query,
			'results' => $results,
		) );
	}

	/**
	 * Get program details
	 */
	public function get_program_details() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		$program_id = intval( $_POST['program_id'] ?? 0 );

		if ( ! $program_id ) {
			wp_send_json_error( __( 'Invalid program ID.', 'coachproai-lms' ) );
		}

		$program = get_post( $program_id );
		if ( ! $program || $program->post_type !== 'coaching_program' ) {
			wp_send_json_error( __( 'Program not found.', 'coachproai-lms' ) );
		}

		// Get program data
		$program_data = array(
			'id' => $program_id,
			'title' => $program->post_title,
			'content' => $program->post_content,
			'excerpt' => $program->post_excerpt,
			'permalink' => get_permalink( $program_id ),
			'thumbnail' => get_the_post_thumbnail_url( $program_id, 'large' ),
			'price' => get_post_meta( $program_id, '_coachproai_price', true ),
			'duration' => get_post_meta( $program_id, '_coachproai_duration', true ),
			'level' => get_post_meta( $program_id, '_coachproai_level', true ),
			'max_students' => get_post_meta( $program_id, '_coachproai_max_students', true ),
			'categories' => wp_get_post_terms( $program_id, 'program_category', array( 'fields' => 'slugs' ) ),
			'focus_areas' => wp_get_post_terms( $program_id, 'coaching_focus', array( 'fields' => 'slugs' ) ),
		);

		// Add enrollment status if user is logged in
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$db = Database::instance();
			$enrollment = $db->get_enrollment( $user_id, $program_id );
			$program_data['is_enrolled'] = ! empty( $enrollment );
		}

		wp_send_json_success( $program_data );
	}

	/**
	 * Track user activity
	 */
	public function track_activity() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'User must be logged in to track activity.', 'coachproai-lms' ) );
		}

		$user_id = get_current_user_id();
		$program_id = intval( $_POST['program_id'] ?? 0 );
		$activity_type = sanitize_text_field( $_POST['activity_type'] ?? '' );
		$activity_data = $_POST['activity_data'] ?? array();
		$duration = intval( $_POST['duration'] ?? 0 );

		if ( empty( $activity_type ) ) {
			wp_send_json_error( __( 'Activity type is required.', 'coachproai-lms' ) );
		}

		$db = Database::instance();
		$result = $db->track_event( $user_id, $program_id, $activity_type, $activity_data, 0, $duration );

		if ( $result ) {
			wp_send_json_success( __( 'Activity tracked successfully.', 'coachproai-lms' ) );
		} else {
			wp_send_json_error( __( 'Failed to track activity.', 'coachproai-lms' ) );
		}
	}

	/**
	 * Update progress
	 */
	public function update_progress() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'User must be logged in to update progress.', 'coachproai-lms' ) );
		}

		$user_id = get_current_user_id();
		$activity_id = intval( $_POST['activity_id'] ?? 0 );
		$progress_data = array(
			'percentage' => floatval( $_POST['progress'] ?? 0 ),
			'time_spent' => intval( $_POST['time_spent'] ?? 0 ),
			'score' => floatval( $_POST['score'] ?? 0 ),
			'insights' => $_POST['insights'] ?? array(),
			'type' => sanitize_text_field( $_POST['activity_type'] ?? 'lesson' ),
		);

		if ( ! $activity_id ) {
			wp_send_json_error( __( 'Invalid activity ID.', 'coachproai-lms' ) );
		}

		// Update progress in AI coaching system
		$ai_coaching = AI\AI_Coaching::instance();
		$result = $ai_coaching->update_learning_progress( $activity_id, $user_id, $progress_data );

		if ( $result ) {
			// Trigger progress update hooks
			do_action( 'coachproai_progress_updated', $user_id, $activity_id, $progress_data );

			wp_send_json_success( __( 'Progress updated successfully.', 'coachproai-lms' ) );
		} else {
			wp_send_json_error( __( 'Failed to update progress.', 'coachproai-lms' ) );
		}
	}

	/**
	 * Submit assessment
	 */
	public function submit_assessment() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'User must be logged in to submit assessments.', 'coachproai-lms' ) );
		}

		$user_id = get_current_user_id();
		$assessment_id = intval( $_POST['assessment_id'] ?? 0 );
		$responses = $_POST['responses'] ?? array();

		if ( ! $assessment_id || empty( $responses ) ) {
			wp_send_json_error( __( 'Invalid assessment or responses.', 'coachproai-lms' ) );
		}

		// Validate responses
		$assessment = get_post( $assessment_id );
		if ( ! $assessment || $assessment->post_type !== 'coaching_assessment' ) {
			wp_send_json_error( __( 'Invalid assessment.', 'coachproai-lms' ) );
		}

		// Process assessment responses
		$ai_analysis = $this->analyze_assessment_responses( $responses );
		$personality_score = $ai_analysis['personality_score'] ?? 0.5;

		// Save to database
		global $wpdb;
		$table_name = $wpdb->prefix . 'coachproai_assessments';

		$result = $wpdb->insert(
			$table_name,
			array(
				'assessment_id' => $assessment_id,
				'student_id' => $user_id,
				'responses_data' => wp_json_encode( $responses ),
				'ai_analysis' => wp_json_encode( $ai_analysis ),
				'personality_score' => $personality_score,
				'learning_style' => $ai_analysis['learning_style'] ?? 'balanced',
				'strengths' => wp_json_encode( $ai_analysis['strengths'] ?? array() ),
				'areas_for_improvement' => wp_json_encode( $ai_analysis['areas_for_improvement'] ?? array() ),
				'submitted_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			// Update AI profile
			$this->update_ai_profile_from_assessment( $user_id, $ai_analysis );

			wp_send_json_success( array(
				'message' => __( 'Assessment submitted successfully!', 'coachproai-lms' ),
				'analysis' => $ai_analysis,
			) );
		} else {
			wp_send_json_error( __( 'Failed to submit assessment.', 'coachproai-lms' ) );
		}
	}

	/**
	 * Analyze assessment responses
	 */
	private function analyze_assessment_responses( $responses ) {
		// Simple AI analysis based on response patterns
		// In a real implementation, this would use more sophisticated AI analysis

		$analysis = array();

		// Analyze response patterns for personality traits
		$personality_score = 0.5; // Default balanced score
		$learning_style = 'balanced';

		// Analyze responses for personality markers
		$total_responses = count( $responses );
		$avg_score = array_sum( $responses ) / $total_responses;

		if ( $avg_score > 0.7 ) {
			$personality_score = 0.8;
			$learning_style = 'active';
		} elseif ( $avg_score < 0.3 ) {
			$personality_score = 0.2;
			$learning_style = 'reflective';
		}

		$analysis['personality_score'] = $personality_score;
		$analysis['learning_style'] = $learning_style;

		// Generate insights based on response patterns
		$insights = $this->generate_assessment_insights( $responses );
		$analysis['insights'] = $insights;
		$analysis['strengths'] = $insights['strengths'] ?? array();
		$analysis['areas_for_improvement'] = $insights['improvements'] ?? array();

		return $analysis;
	}

	/**
	 * Generate insights from assessment
	 */
	private function generate_assessment_insights( $responses ) {
		$insights = array(
			'strengths' => array(),
			'improvements' => array(),
			'recommendations' => array(),
		);

		// Analyze response patterns
		$high_scores = array_filter( $responses, function( $score ) {
			return $score >= 0.8;
		} );

		$low_scores = array_filter( $responses, function( $score ) {
			return $score <= 0.3;
		} );

		// Map scores to insights (this would be more sophisticated in a real implementation)
		if ( count( $high_scores ) >= 2 ) {
			$insights['strengths'][] = 'Strong learning motivation';
			$insights['strengths'][] = 'Good self-awareness';
		}

		if ( count( $low_scores ) >= 2 ) {
			$insights['improvements'][] = 'Time management skills';
			$insights['improvements'][] = 'Goal setting clarity';
		}

		// Generate recommendations
		if ( ! empty( $insights['improvements'] ) ) {
			$insights['recommendations'][] = 'Focus on structured learning approaches';
			$insights['recommendations'][] = 'Set specific, measurable goals';
		}

		return $insights;
	}

	/**
	 * Update AI profile from assessment
	 */
	private function update_ai_profile_from_assessment( $user_id, $analysis ) {
		global $wpdb;

		// Find user's program profiles
		$table_name = $wpdb->prefix . 'coachproai_profiles';
		$profiles = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE student_id = %d",
				$user_id
			)
		);

	 foreach ( $profiles as $profile ) {
			// Update profile data
			$profile_data = json_decode( $profile->ai_profile_data, true ) ?: array();
			
			$profile_data['personality_type'] = $analysis['learning_style'];
			$profile_data['last_assessment'] = current_time( 'mysql' );
			$profile_data['assessment_results'] = $analysis;

			$wpdb->update(
				$table_name,
				array( 'ai_profile_data' => wp_json_encode( $profile_data ) ),
				array( 'id' => $profile->id ),
				null,
				array( '%d' )
			);
		}
	}

	/**
	 * Sanitize AJAX input data
	 */
	private function sanitize_ajax_data( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'sanitize_ajax_data' ), $data );
		}
		
		if ( is_string( $data ) ) {
			return sanitize_text_field( $data );
		}
		
		return $data;
	}
}