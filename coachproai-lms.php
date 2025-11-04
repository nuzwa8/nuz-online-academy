<?php
/**
 * Plugin Name: CoachProAI LMS
 * Plugin URI: https://coachproai.com/
 * Description: AI-powered Learning Management System for coaches and training professionals. Features include AI coaching sessions, personalized learning paths, advanced analytics, and intelligent content recommendations.
 * Version: 1.0.0
 * Author: CoachProAI Team
 * Author URI: https://coachproai.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coachproai-lms
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package CoachProAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'COACHPROAI_VERSION', '1.0.0' );
define( 'COACHPROAI_FILE', __FILE__ );
define( 'COACHPROAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'COACHPROAI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'COACHPROAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'COACHPROAI_MIN_WP_VERSION', '5.8' );
define( 'COACHPROAI_MIN_PHP_VERSION', '8.0' );

/**
 * Main CoachProAI LMS Class
 */
final class CoachProAI_LMS {

	/**
	 * Plugin version
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin instance
	 * @var CoachProAI_LMS
	 */
	private static $instance = null;

	/**
	 * Plugin initialized or not
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Get plugin instance
	 * @return CoachProAI_LMS
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
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_filter( 'plugin_action_links_' . COACHPROAI_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		
		// Activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		if ( self::$initialized ) {
			return;
		}

		if ( ! $this->check_requirements() ) {
			return;
		}

		// Load core files
		$this->load_dependencies();

		// Initialize core components
		$this->init_core_components();

		// Initialize modules
		$this->init_modules();

		self::$initialized = true;
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		// Core classes
		require_once COACHPROAI_PLUGIN_PATH . 'includes/class-core.php';
		require_once COACHPROAI_PLUGIN_PATH . 'includes/class-install.php';
		require_once COACHPROAI_PLUGIN_PATH . 'includes/class-database.php';
		require_once COACHPROAI_PLUGIN_PATH . 'includes/class-ajax.php';
		require_once COACHPROAI_PLUGIN_PATH . 'includes/class-shortcodes.php';
		
		// Post types
		require_once COACHPROAI_PLUGIN_PATH . 'includes/post-types/class-coaching-program.php';
		require_once COACHPROAI_PLUGIN_PATH . 'includes/post-types/class-coaching-session.php';
		require_once COACHPROAI_PLUGIN_PATH . 'includes/post-types/class-ai-coach.php';
		require_once COACHPROAI_PLUGIN_PATH . 'includes/post-types/class-assessment.php';
		
		// Admin classes
		if ( is_admin() ) {
			require_once COACHPROAI_PLUGIN_PATH . 'includes/admin/class-admin.php';
			require_once COACHPROAI_PLUGIN_PATH . 'includes/admin/class-dashboard.php';
			require_once COACHPROAI_PLUGIN_PATH . 'includes/admin/class-program-builder.php';
			require_once COACHPROAI_PLUGIN_PATH . 'includes/admin/class-ai-settings.php';
			require_once COACHPROAI_PLUGIN_PATH . 'includes/admin/class-analytics.php';
		}

		// Public classes
		if ( ! is_admin() ) {
			require_once COACHPROAI_PLUGIN_PATH . 'includes/public/class-public.php';
			require_once COACHPROAI_PLUGIN_PATH . 'includes/public/class-coaching-platform.php';
			require_once COACHPROAI_PLUGIN_PATH . 'includes/public/class-ai-coaching.php';
			require_once COACHPROAI_PLUGIN_PATH . 'includes/public/class-progress-tracker.php';
		}

		// AI Coaching Module
		if ( file_exists( COACHPROAI_PLUGIN_PATH . 'addons/ai-coaching/ai-coaching.php' ) ) {
			require_once COACHPROAI_PLUGIN_PATH . 'addons/ai-coaching/ai-coaching.php';
		}

		// Analytics Module
		if ( file_exists( COACHPROAI_PLUGIN_PATH . 'addons/analytics/analytics.php' ) ) {
			require_once COACHPROAI_PLUGIN_PATH . 'addons/analytics/analytics.php';
		}
	}

	/**
	 * Initialize core components
	 */
	private function init_core_components() {
		\CoachProAI\Core::instance();
		\CoachProAI\Database::instance();
		\CoachProAI\Ajax::instance();
		
		if ( is_admin() ) {
			\CoachProAI\Admin\Admin::instance();
		} else {
			\CoachProAI\Public\Public_Platform::instance();
		}
	}

	/**
	 * Initialize modules
	 */
	private function init_modules() {
		// AI Coaching Module
		if ( class_exists( '\CoachProAI\AI\AI_Coaching' ) ) {
			\CoachProAI\AI\AI_Coaching::instance();
		}

		// Analytics Module
		if ( class_exists( '\CoachProAI\Analytics\Analytics' ) ) {
			\CoachProAI\Analytics\Analytics::instance();
		}
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'coachproai-lms',
			false,
			dirname( COACHPROAI_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Check plugin requirements
	 * @return bool
	 */
	private function check_requirements() {
		// Check PHP version
		if ( version_compare( PHP_VERSION, COACHPROAI_MIN_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return false;
		}

		// Check WordPress version
		if ( version_compare( $GLOBALS['wp_version'], COACHPROAI_MIN_WP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
			return false;
		}

		// Check WooCommerce if needed
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Show admin notices
	 */
	public function admin_notices() {
		$this->php_version_notice();
		$this->wp_version_notice();
		$this->woocommerce_notice();
	}

	/**
	 * PHP version notice
	 */
	public function php_version_notice() {
		if ( isset( $_GET['coachproai_notice_dismissed'] ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo sprintf(
					/* translators: %1s: PHP version, %2s: Current PHP version */
					__( 'CoachProAI LMS requires PHP version %1$s or greater. You are using version %2$s.', 'coachproai-lms' ),
					COACHPROAI_MIN_PHP_VERSION,
					PHP_VERSION
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * WordPress version notice
	 */
	public function wp_version_notice() {
		if ( isset( $_GET['coachproai_notice_dismissed'] ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo sprintf(
					/* translators: %1s: WordPress version, %2s: Current WP version */
					__( 'CoachProAI LMS requires WordPress version %1$s or greater. You are using version %2$s.', 'coachproai-lms' ),
					COACHPROAI_MIN_WP_VERSION,
					$GLOBALS['wp_version']
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * WooCommerce notice
	 */
	public function woocommerce_notice() {
		if ( isset( $_GET['coachproai_notice_dismissed'] ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<?php
				echo sprintf(
					/* translators: %s: WooCommerce plugin URL */
					__( 'CoachProAI LMS requires WooCommerce to be installed and active. Please install %s.', 'coachproai-lms' ),
					'<a href="' . admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) . '">WooCommerce</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Create database tables
		\CoachProAI\Install::activate();
		
		// Create user roles and capabilities
		\CoachProAI\Install::create_user_roles();
		
		// Create required pages
		\CoachProAI\Install::create_pages();
		
		// Set default options
		\CoachProAI\Install::set_default_options();
		
		// Flush rewrite rules
		flush_rewrite_rules();
		
		// Set activation timestamp
		update_option( 'coachproai_lms_activated_time', current_time( 'timestamp' ) );
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Clear scheduled hooks
		wp_clear_scheduled_hook( 'coachproai_ai_coaching_cleanup' );
		wp_clear_scheduled_hook( 'coachproai_analytics_reports' );
		wp_clear_scheduled_hook( 'coachproai_progress_backup' );
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin uninstall
	 */
	public static function uninstall() {
		\CoachProAI\Install::uninstall();
	}

	/**
	 * Add plugin action links
	 * @param array $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=coachproai-settings' ) . '">' . __( 'Settings', 'coachproai-lms' ) . '</a>';
		$dashboard_link = '<a href="' . admin_url( 'admin.php?page=coachproai-dashboard' ) . '">' . __( 'Dashboard', 'coachproai-lms' ) . '</a>';
		$docs_link = '<a href="https://docs.coachproai.com/" target="_blank">' . __( 'Documentation', 'coachproai-lms' ) . '</a>';
		
		array_unshift( $links, $settings_link, $dashboard_link, $docs_link );
		
		return $links;
	}

	/**
	 * Get plugin data
	 * @return array
	 */
	public function get_plugin_data() {
		return array(
			'name' => 'CoachProAI LMS',
			'version' => self::VERSION,
			'author' => 'CoachProAI Team',
			'description' => __( 'AI-powered Learning Management System for coaches and training professionals', 'coachproai-lms' ),
		);
	}
}

/**
 * Main function to get CoachProAI LMS instance
 * @return CoachProAI_LMS
 */
function coachproai_lms() {
	return CoachProAI_LMS::instance();
}

// Initialize the plugin
coachproai_lms();