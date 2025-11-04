<?php
/**
 * CoachProAI AI Coaching Module
 *
 * @package CoachProAI\AI
 */

namespace CoachProAI\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI Coaching Main Class
 */
class AI_Coaching {

	/**
	 * Plugin instance
	 * @var AI_Coaching
	 */
	private static $instance = null;

	/**
	 * OpenAI API key
	 * @var string
	 */
	private $openai_api_key = '';

	/**
	 * AI conversation history
	 * @var array
	 */
	private $conversation_cache = array();

	/**
	 * Get instance
	 * @return AI_Coaching
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
		$this->openai_api_key = get_option( 'coachproai_openai_api_key', '' );
		
		add_action( 'wp_ajax_coachproai_start_ai_session', array( $this, 'ajax_start_session' ) );
		add_action( 'wp_ajax_coachproai_send_message', array( $this, 'ajax_send_message' ) );
		add_action( 'wp_ajax_coachproai_get_recommendations', array( $this, 'ajax_get_recommendations' ) );
		add_action( 'coachproai_ai_cleanup', array( $this, 'cleanup_old_sessions' ) );

		// Schedule cleanup
		if ( ! wp_next_scheduled( 'coachproai_ai_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'coachproai_ai_cleanup' );
		}
	}

	/**
	 * Initialize student AI profile
	 */
	public function initialize_student_profile( $program_id, $student_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_profiles';

		// Check if profile exists
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE student_id = %d AND program_id = %d",
				$student_id,
				$program_id
			)
		);

		if ( ! $existing ) {
			// Create new AI profile
			$ai_profile = array(
				'learning_style' => 'adaptive',
				'personality_type' => $this->analyze_initial_personality(),
				'preferred_communication_style' => 'conversational',
				'goals' => array(),
				'strengths' => array(),
				'areas_for_improvement' => array(),
				'progress_history' => array(),
			);

			$result = $wpdb->insert(
				$table_name,
				array(
					'student_id'     => $student_id,
					'program_id'     => $program_id,
					'ai_profile_data' => wp_json_encode( $ai_profile ),
					'created_at'     => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s', '%s' )
			);

			return $result !== false;
		}

		return true;
	}

	/**
	 * Start AI coaching session
	 */
	public function start_session( $student_id, $coach_id, $session_type = 'general' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_ai_sessions';

		// Create session record
		$result = $wpdb->insert(
			$table_name,
			array(
				'student_id'    => $student_id,
				'ai_coach_id'   => $coach_id,
				'session_type'  => $session_type,
				'started_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		if ( $result ) {
			$session_id = $wpdb->insert_id;
			
			// Send welcome message from AI coach
			$welcome_message = $this->generate_welcome_message( $coach_id, $student_id, $session_type );
			
			$this->save_message( $session_id, 'ai', $welcome_message );
			
			return $session_id;
		}

		return false;
	}

	/**
	 * Process AI message and generate response
	 */
	public function process_message( $session_id, $student_message ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_ai_sessions';

		// Get session data
		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$session_id
			),
			ARRAY_A
		);

		if ( ! $session ) {
			return false;
		}

		// Save student message
		$this->save_message( $session_id, 'student', $student_message );

		// Get conversation context
		$context = $this->get_conversation_context( $session_id );

		// Generate AI response
		$ai_response = $this->generate_ai_response( $session, $student_message, $context );

		// Save AI response
		$this->save_message( $session_id, 'ai', $ai_response );

		// Update session metrics
		$this->update_session_metrics( $session_id );

		// Generate recommendations if needed
		$this->generate_session_recommendations( $session_id, $student_message );

		return $ai_response;
	}

	/**
	 * Generate AI response using OpenAI
	 */
	private function generate_ai_response( $session, $student_message, $context ) {
		if ( empty( $this->openai_api_key ) ) {
			return $this->generate_fallback_response( $student_message, $context );
		}

		// Prepare prompt for AI
		$prompt = $this->build_prompt( $session, $student_message, $context );

		// Call OpenAI API
		$response = $this->call_openai_api( $prompt );

		if ( $response ) {
			return $this->process_ai_response( $response );
		}

		// Fallback to rule-based response
		return $this->generate_fallback_response( $student_message, $context );
	}

	/**
	 * Build AI prompt based on context
	 */
	private function build_prompt( $session, $student_message, $context ) {
		$coach_data = get_post( $session['ai_coach_id'] );
		$coach_personality = get_post_meta( $session['ai_coach_id'], '_coach_personality', true ) ?: 'supportive';
		$coach_specialty = get_post_meta( $session['ai_coach_id'], '_coach_specialty', true ) ?: 'general';
		
		// Get student's progress data
		$student_progress = $this->get_student_progress( $session['student_id'], $session['ai_coach_id'] );

		$system_prompt = "You are {$coach_data->post_title}, an AI coaching specialist in {$coach_specialty}.
		
Personality traits: {$coach_personality}

Student context:
- Learning progress: {$student_progress}
- Previous conversations: {$context['conversation_summary']}
- Current goals: {$context['goals']}

Guidelines:
- Be supportive and encouraging
- Ask thoughtful questions
- Provide actionable insights
- Adapt to student's communication style
- Keep responses under 200 words
- Focus on {$coach_specialty} coaching

Student's message: {$student_message}";

		return array(
			'model' => 'gpt-3.5-turbo',
			'messages' => array(
				array(
					'role' => 'system',
					'content' => $system_prompt,
				),
				array(
					'role' => 'user',
					'content' => $student_message,
				),
			),
			'temperature' => 0.7,
			'max_tokens' => 200,
		);
	}

	/**
	 * Call OpenAI API
	 */
	private function call_openai_api( $prompt_data ) {
		$api_url = 'https://api.openai.com/v1/chat/completions';
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->openai_api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $prompt_data ),
			'timeout' => 30,
		);

		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'CoachProAI OpenAI API Error: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			error_log( 'CoachProAI OpenAI API Error: ' . $response_body );
			return false;
		}

		$data = json_decode( $response_body, true );
		
		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			return $data['choices'][0]['message']['content'];
		}

		return false;
	}

	/**
	 * Process and clean AI response
	 */
	private function process_ai_response( $raw_response ) {
		// Clean and format response
		$response = trim( $raw_response );
		
		// Remove any markdown formatting that might not be needed
		$response = preg_replace( '/\*\*(.*?)\*\*/', '$1', $response );
		$response = preg_replace( '/\*(.*?)\*/', '$1', $response );
		
		return $response;
	}

	/**
	 * Generate fallback response when OpenAI is not available
	 */
	private function generate_fallback_response( $student_message, $context ) {
		$responses = array(
			'thank_you' => 'Thank you for sharing that with me. I appreciate your openness. Can you tell me more about what you\\'d like to achieve?',
			'progress' => 'Great progress! It sounds like you\\'re on the right track. What challenges are you facing in moving forward?',
			'goal' => 'Setting goals is important for success. Let\\'s work together to make sure they\\'re specific and achievable.',
			'general' => 'I understand. Let\\'s explore this together. What would you like to focus on in our conversation today?',
		);

		// Simple keyword-based response selection
		$message_lower = strtolower( $student_message );
		
		if ( strpos( $message_lower, 'thank' ) !== false || strpos( $message_lower, 'appreciate' ) !== false ) {
			return $responses['thank_you'];
		} elseif ( strpos( $message_lower, 'progress' ) !== false || strpos( $message_lower, 'achieve' ) !== false ) {
			return $responses['progress'];
		} elseif ( strpos( $message_lower, 'goal' ) !== false || strpos( $message_lower, 'objective' ) !== false ) {
			return $responses['goal'];
		} else {
			return $responses['general'];
		}
	}

	/**
	 * Get conversation context
	 */
	private function get_conversation_context( $session_id ) {
		$messages = get_transient( 'coachproai_conversation_' . $session_id );
		
		if ( ! $messages ) {
			return array(
				'conversation_summary' => '',
				'goals' => '',
				'key_topics' => array(),
			);
		}

		// Analyze conversation for context
		$context = array(
			'conversation_summary' => $this->summarize_conversation( $messages ),
			'goals' => $this->extract_goals( $messages ),
			'key_topics' => $this->extract_topics( $messages ),
		);

		return $context;
	}

	/**
	 * Save message to conversation cache
	 */
	private function save_message( $session_id, $sender, $message ) {
		$messages = get_transient( 'coachproai_conversation_' . $session_id ) ?: array();
		
		$messages[] = array(
			'sender' => $sender,
			'message' => $message,
			'timestamp' => current_time( 'mysql' ),
		);

		// Keep only last 20 messages in cache
		if ( count( $messages ) > 20 ) {
			$messages = array_slice( $messages, -20 );
		}

		set_transient( 'coachproai_conversation_' . $session_id, $messages, HOUR_IN_SECONDS * 2 );
	}

	/**
	 * Generate welcome message
	 */
	private function generate_welcome_message( $coach_id, $student_id, $session_type ) {
		$coach_data = get_post( $coach_id );
		$coach_name = $coach_data->post_title;
		
		$welcome_messages = array(
			'general' => "Hello! I'm {$coach_name}. I'm here to help you on your coaching journey. How are you feeling today, and what would you like to work on?",
			'goal_setting' => "Welcome! I'm here to help you set and achieve your goals. Let's start by discussing what matters most to you right now.",
			'progress_review' => "Great to see you again! Let's review your progress and celebrate what you've accomplished. How have things been going?",
			'challenge_support' => "I'm here to support you through any challenges you're facing. What's been on your mind lately?",
		);

		return $welcome_messages[ $session_type ] ?? $welcome_messages['general'];
	}

	/**
	 * Update session metrics
	 */
	private function update_session_metrics( $session_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_ai_sessions';
		
		// Get current session duration
		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$session_id
			),
			ARRAY_A
		);

		if ( $session ) {
			$duration = ( current_time( 'timestamp' ) - strtotime( $session['started_at'] ) ) / 60;
			
			$wpdb->update(
				$table_name,
				array( 'duration_minutes' => floor( $duration ) ),
				array( 'id' => $session_id ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Generate recommendations based on session
	 */
	private function generate_session_recommendations( $session_id, $student_message ) {
		// Simple keyword-based recommendations
		$recommendations = array();

		$message_lower = strtolower( $student_message );
		
		if ( strpos( $message_lower, 'stress' ) !== false ) {
			$recommendations[] = array(
				'type' => 'stress_management',
				'recommendation' => 'Consider trying a short meditation or breathing exercise',
				'priority' => 2,
			);
		}

		if ( strpos( $message_lower, 'goal' ) !== false ) {
			$recommendations[] = array(
				'type' => 'goal_setting',
				'recommendation' => 'Break down your goals into smaller, achievable steps',
				'priority' => 1,
			);
		}

		// Save recommendations if any
		if ( ! empty( $recommendations ) ) {
			$this->save_recommendations( $session_id, $recommendations );
		}
	}

	/**
	 * Save recommendations to database
	 */
	private function save_recommendations( $session_id, $recommendations ) {
		global $wpdb;

		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT student_id FROM {$wpdb->prefix}coachproai_ai_sessions WHERE id = %d",
				$session_id
			)
		);

		if ( $session ) {
		 foreach ( $recommendations as $rec ) {
				$wpdb->insert(
					$wpdb->prefix . 'coachproai_recommendations',
					array(
						'student_id' => $session->student_id,
						'recommendation_type' => $rec['type'],
						'recommendation_data' => wp_json_encode( $rec ),
						'confidence_score' => 0.8,
						'priority_level' => $rec['priority'],
						'created_at' => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%f', '%d', '%s' )
				);
			}
		}
	}

	/**
	 * Analyze initial personality
	 */
	private function analyze_initial_personality() {
		return 'balanced'; // Default personality type
	}

	/**
	 * Get student progress data
	 */
	private function get_student_progress( $student_id, $coach_id ) {
		global $wpdb;

		$progress_table = $wpdb->prefix . 'coachproai_learning_progress';
		
		$recent_progress = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(progress_percentage) FROM {$progress_table} 
				 WHERE student_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
				$student_id
			)
		);

		return $recent_progress ? round( $recent_progress, 1 ) . '%' : '0%';
	}

	/**
	 * Summarize conversation
	 */
	private function summarize_conversation( $messages ) {
		$student_messages = array_filter( $messages, function( $msg ) {
			return $msg['sender'] === 'student';
		} );

		return count( $student_messages ) . ' messages exchanged';
	}

	/**
	 * Extract goals from conversation
	 */
	private function extract_goals( $messages ) {
		$goals = array();
		
	 foreach ( $messages as $msg ) {
			if ( $msg['sender'] === 'student' && strpos( strtolower( $msg['message'] ), 'goal' ) !== false ) {
				$goals[] = $msg['message'];
			}
		}

		return implode( ', ', array_slice( $goals, 0, 3 ) );
	}

	/**
	 * Extract key topics
	 */
	private function extract_topics( $messages ) {
		$topics = array();
		$keywords = array( 'career', 'goal', 'progress', 'challenge', 'stress', 'success' );

	 foreach ( $messages as $msg ) {
			if ( $msg['sender'] === 'student' ) {
			 foreach ( $keywords as $keyword ) {
					if ( strpos( strtolower( $msg['message'] ), $keyword ) !== false ) {
						$topics[] = $keyword;
					}
				}
			}
		}

		return array_unique( $topics );
	}

	/**
	 * AJAX: Start AI session
	 */
	public function ajax_start_session() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to start a session.', 'coachproai-lms' ) );
		}

		$student_id = get_current_user_id();
		$coach_id = intval( $_POST['coach_id'] ?? 0 );
		$session_type = sanitize_text_field( $_POST['session_type'] ?? 'general' );

		if ( ! $coach_id ) {
			wp_send_json_error( __( 'Invalid coach selection.', 'coachproai-lms' ) );
		}

		$session_id = $this->start_session( $student_id, $coach_id, $session_type );

		if ( $session_id ) {
			wp_send_json_success(
				array(
					'session_id' => $session_id,
					'message' => __( 'AI coaching session started successfully!', 'coachproai-lms' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to start session.', 'coachproai-lms' ) );
		}
	}

	/**
	 * AJAX: Send message
	 */
	public function ajax_send_message() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to send messages.', 'coachproai-lms' ) );
		}

		$session_id = intval( $_POST['session_id'] ?? 0 );
		$message = sanitize_textarea_field( $_POST['message'] ?? '' );

		if ( ! $session_id || empty( $message ) ) {
			wp_send_json_error( __( 'Invalid session or message.', 'coachproai-lms' ) );
		}

		$response = $this->process_message( $session_id, $message );

		if ( $response ) {
			wp_send_json_success(
				array(
					'response' => $response,
					'timestamp' => current_time( 'mysql' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to generate response.', 'coachproai-lms' ) );
		}
	}

	/**
	 * AJAX: Get recommendations
	 */
	public function ajax_get_recommendations() {
		check_ajax_referer( 'coachproai_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to view recommendations.', 'coachproai-lms' ) );
		}

		$student_id = get_current_user_id();
		$recommendations = $this->get_student_recommendations( $student_id );

		wp_send_json_success( $recommendations );
	}

	/**
	 * Get student recommendations
	 */
	public function get_student_recommendations( $student_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_recommendations';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} 
				 WHERE student_id = %d AND status = 'pending' 
				 ORDER BY priority_level DESC, created_at DESC 
				 LIMIT 10",
				$student_id
			),
			ARRAY_A
		);
	}

	/**
	 * Cleanup old sessions
	 */
	public function cleanup_old_sessions() {
		global $wpdb;

		// Delete sessions older than 30 days
		$table_name = $wpdb->prefix . 'coachproai_ai_sessions';
		
		$wpdb->query(
			"DELETE FROM {$table_name} 
			 WHERE started_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);

		// Delete old recommendations
		$rec_table = $wpdb->prefix . 'coachproai_recommendations';
		$wpdb->query(
			"DELETE FROM {$rec_table} 
			 WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)"
		);

		// Clear old conversation cache
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			 WHERE option_name LIKE '_transient_coachproai_conversation_%' 
			 AND option_name LIKE '%_transient_timeout_coachproai_conversation_%'"
		);
	}

	/**
	 * Update learning progress for AI coaching
	 */
	public function update_learning_progress( $activity_id, $student_id, $progress_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'coachproai_learning_progress';

		$result = $wpdb->insert(
			$table_name,
			array(
				'student_id' => $student_id,
				'activity_id' => $activity_id,
				'activity_type' => $progress_data['type'] ?? 'lesson',
				'progress_percentage' => $progress_data['percentage'] ?? 0,
				'time_spent_minutes' => $progress_data['time_spent'] ?? 0,
				'completion_score' => $progress_data['score'] ?? 0,
				'ai_insights' => wp_json_encode( $progress_data['insights'] ?? array() ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%f', '%d', '%f', '%s', '%s' )
		);

		return $result !== false;
	}
}