<?php
/**
 * CoachProAI Student Dashboard Template
 *
 * @package CoachProAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$db = \CoachProAI\Database::instance();

// Get student data
$enrolled_programs = array(); // This would get actual enrolled programs
$recent_sessions = $db->get_student_sessions( $user_id, 5 );
$recent_progress = $db->get_learning_progress( $user_id, null, 10 );
$ai_coaching = \CoachProAI\AI\AI_Coaching::instance();
$recommendations = $ai_coaching->get_student_recommendations( $user_id );
?>

<div class="coachproai-student-dashboard">
	<div class="coachproai-dashboard-header">
		<h2><?php _e( 'My Dashboard', 'coachproai-lms' ); ?></h2>
		<p><?php _e( 'Welcome back! Here\'s your coaching journey overview.', 'coachproai-lms' ); ?></p>
	</div>

	<div class="coachproai-dashboard-stats">
		<div class="coachproai-stat-card">
			<div class="coachproai-stat-value"><?php echo count( $enrolled_programs ); ?></div>
			<div class="coachproai-stat-label"><?php _e( 'Enrolled Programs', 'coachproai-lms' ); ?></div>
		</div>

		<div class="coachproai-stat-card">
			<div class="coachproai-stat-value"><?php echo count( $recent_sessions ); ?></div>
			<div class="coachproai-stat-label"><?php _e( 'AI Sessions', 'coachproai-lms' ); ?></div>
		</div>

		<div class="coachproai-stat-card">
			<div class="coachproai-stat-value">
				<?php 
				$total_progress = 0;
			 foreach ( $recent_progress as $progress ) {
					$total_progress += $progress['progress_percentage'];
				}
				echo count( $recent_progress ) > 0 ? round( $total_progress / count( $recent_progress ), 1 ) : 0;
				?>%
			</div>
			<div class="coachproai-stat-label"><?php _e( 'Avg Progress', 'coachproai-lms' ); ?></div>
		</div>

		<div class="coachproai-stat-card">
			<div class="coachproai-stat-value"><?php echo count( $recommendations ); ?></div>
			<div class="coachproai-stat-label"><?php _e( 'New Recommendations', 'coachproai-lms' ); ?></div>
		</div>
	</div>

	<div class="coachproai-dashboard-content">
		<div class="coachproai-tabs">
			<div class="coachproai-tab-nav">
				<a href="#enrolled-programs" class="active"><?php _e( 'My Programs', 'coachproai-lms' ); ?></a>
				<a href="#ai-sessions"><?php _e( 'AI Sessions', 'coachproai-lms' ); ?></a>
				<a href="#progress"><?php _e( 'Progress', 'coachproai-lms' ); ?></a>
				<a href="#recommendations"><?php _e( 'AI Recommendations', 'coachproai-lms' ); ?></a>
			</div>

			<div id="enrolled-programs" class="coachproai-tab-content active">
				<?php if ( ! empty( $enrolled_programs ) ) : ?>
					<div class="coachproai-programs-grid">
						<?php foreach ( $enrolled_programs as $program ) : ?>
							<div class="coachproai-program-card">
								<div class="coachproai-program-thumbnail">
									<a href="<?php echo get_permalink( $program['ID'] ); ?>">
										<?php echo get_the_post_thumbnail( $program['ID'], 'medium' ); ?>
									</a>
								</div>
								<div class="coachproai-program-content">
									<h3 class="coachproai-program-title">
										<a href="<?php echo get_permalink( $program['ID'] ); ?>"><?php echo $program['post_title']; ?></a>
									</h3>
									<div class="coachproai-progress-container">
										<div class="coachproai-progress-bar-container">
											<div class="coachproai-progress-bar" style="width: <?php echo $program['progress']; ?>%"></div>
										</div>
										<div class="coachproai-progress-text"><?php echo $program['progress']; ?>% Complete</div>
									</div>
									<div class="coachproai-program-actions">
										<a href="<?php echo get_permalink( $program['ID'] ); ?>" class="coachproai-btn coachproai-btn-secondary">
											<?php _e( 'Continue Learning', 'coachproai-lms' ); ?>
										</a>
                                                                                <button class="coachproai-btn coachproai-btn-primary" data-action="start-ai-chat" data-program-id="<?php echo $program['ID']; ?>">
											<?php _e( 'Chat with AI Coach', 'coachproai-lms' ); ?>
										</button>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="coachproai-no-programs">
						<p><?php _e( 'You haven\'t enrolled in any programs yet.', 'coachproai-lms' ); ?></p>
						<a href="<?php echo get_permalink( get_option( 'coachproai_page_coachproai-programs' ) ); ?>" class="coachproai-btn coachproai-btn-primary">
							<?php _e( 'Browse Programs', 'coachproai-lms' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>

			<div id="ai-sessions" class="coachproai-tab-content">
				<?php if ( ! empty( $recent_sessions ) ) : ?>
					<table class="coachproai-table">
						<thead>
							<tr>
								<th><?php _e( 'Coach', 'coachproai-lms' ); ?></th>
								<th><?php _e( 'Type', 'coachproai-lms' ); ?></th>
								<th><?php _e( 'Duration', 'coachproai-lms' ); ?></th>
								<th><?php _e( 'Date', 'coachproai-lms' ); ?></th>
								<th><?php _e( 'Actions', 'coachproai-lms' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_sessions as $session ) : ?>
								<tr>
									<td><?php echo esc_html( $session['coach_name'] ?? 'AI Coach' ); ?></td>
									<td><?php echo esc_html( $session['session_type'] ); ?></td>
									<td><?php echo $session['duration_minutes']; ?> <?php _e( 'min', 'coachproai-lms' ); ?></td>
									<td><?php echo date( 'M j, Y', strtotime( $session['started_at'] ) ); ?></td>
									<td>
										<button class="btn btn-view" data-action="view-session" data-session-id="<?php echo $session['id']; ?>">
											<?php _e( 'View', 'coachproai-lms' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<div class="coachproai-no-sessions">
						<p><?php _e( 'No AI coaching sessions yet.', 'coachproai-lms' ); ?></p>
						<?php if ( ! empty( $enrolled_programs ) ) : ?>
							<button class="coachproai-btn coachproai-btn-primary" data-action="start-new-session">
								<?php _e( 'Start Your First Session', 'coachproai-lms' ); ?>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div id="progress" class="coachproai-tab-content">
				<?php if ( ! empty( $recent_progress ) ) : ?>
					<div class="coachproai-progress-list">
						<?php foreach ( $recent_progress as $progress ) : ?>
							<div class="coachproai-progress-item">
								<div class="coachproai-progress-header">
									<h4><?php echo esc_html( $progress['activity_type'] ); ?> #<?php echo $progress['activity_id']; ?></h4>
									<span class="coachproai-progress-date"><?php echo date( 'M j, Y', strtotime( $progress['created_at'] ) ); ?></span>
								</div>
								<div class="coachproai-progress-bar-container">
									<div class="coachproai-progress-bar" style="width: <?php echo $progress['progress_percentage']; ?>%"></div>
								</div>
								<div class="coachproai-progress-details">
									<span><?php _e( 'Progress:', 'coachproai-lms' ); ?> <?php echo $progress['progress_percentage']; ?>%</span>
									<span><?php _e( 'Time Spent:', 'coachproai-lms' ); ?> <?php echo $progress['time_spent_minutes']; ?> min</span>
									<?php if ( $progress['completion_score'] > 0 ) : ?>
										<span><?php _e( 'Score:', 'coachproai-lms' ); ?> <?php echo $progress['completion_score']; ?></span>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="coachproai-no-progress">
						<p><?php _e( 'No progress data available yet.', 'coachproai-lms' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<div id="recommendations" class="coachproai-tab-content">
				<?php if ( ! empty( $recommendations ) ) : ?>
					<div class="coachproai-recommendations">
						<?php foreach ( $recommendations as $recommendation ) : ?>
							<div class="coachproai-recommendation-item">
								<div class="coachproai-recommendation-header">
									<h4><?php echo esc_html( $recommendation['recommendation_type'] ); ?></h4>
									<span class="coachproai-recommendation-priority priority-<?php echo $recommendation['priority_level']; ?>">
										<?php 
										switch ( $recommendation['priority_level'] ) {
											case 1: _e( 'Low Priority', 'coachproai-lms' ); break;
											case 2: _e( 'Medium Priority', 'coachproai-lms' ); break;
											case 3: _e( 'High Priority', 'coachproai-lms' ); break;
										}
										?>
									</span>
								</div>
								<p><?php echo esc_html( wp_json_encode( $recommendation['recommendation_data'] ) ); ?></p>
								<div class="coachproai-recommendation-actions">
									<button class="coachproai-btn coachproai-btn-secondary" data-action="dismiss-recommendation" data-id="<?php echo $recommendation['id']; ?>">
										<?php _e( 'Dismiss', 'coachproai-lms' ); ?>
									</button>
									<button class="coachproai-btn coachproai-btn-primary" data-action="apply-recommendation" data-id="<?php echo $recommendation['id']; ?>">
										<?php _e( 'Apply', 'coachproai-lms' ); ?>
									</button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="coachproai-no-recommendations">
						<p><?php _e( 'No new recommendations at this time.', 'coachproai-lms' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<style>
.coachproai-student-dashboard {
	max-width: 1200px;
	margin: 0 auto;
	padding: 20px;
}

.coachproai-dashboard-header {
	text-align: center;
	margin-bottom: 40px;
	padding: 30px;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	border-radius: 12px;
}

.coachproai-dashboard-header h2 {
	margin: 0 0 10px 0;
	font-size: 2rem;
}

.coachproai-dashboard-stats {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin-bottom: 40px;
}

.coachproai-progress-list {
	display: grid;
	gap: 20px;
}

.coachproai-progress-item {
	background: white;
	border-radius: 8px;
	padding: 20px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.coachproai-progress-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 15px;
}

.coachproai-progress-details {
	display: flex;
	gap: 20px;
	font-size: 0.9rem;
	color: #7f8c8d;
	margin-top: 10px;
}

.coachproai-recommendations {
	display: grid;
	gap: 20px;
}

.coachproai-recommendation-item {
	background: #fff9e6;
	border-left: 4px solid #f39c12;
	padding: 20px;
	border-radius: 0 8px 8px 0;
}

.coachproai-recommendation-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 15px;
}

.coachproai-recommendation-priority {
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 0.8rem;
	font-weight: 500;
}

.priority-1 { background: #d5f4e6; color: #27ae60; }
.priority-2 { background: #fff3e0; color: #f39c12; }
.priority-3 { background: #fadbd8; color: #e74c3c; }

.coachproai-recommendation-actions {
	display: flex;
	gap: 10px;
	margin-top: 15px;
}

.coachproai-no-programs,
.coachproai-no-sessions,
.coachproai-no-progress,
.coachproai-no-recommendations {
	text-align: center;
	padding: 60px 20px;
	background: #f8f9fa;
	border-radius: 8px;
	border: 2px dashed #dee2e6;
}

.coachproai-no-programs p,
.coachproai-no-sessions p,
.coachproai-no-progress p,
.coachproai-no-recommendations p {
	color: #7f8c8d;
	margin-bottom: 20px;
}
</style>