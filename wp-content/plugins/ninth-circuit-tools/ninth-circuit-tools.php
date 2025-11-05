<?php
/**
 * Plugin Name: Ninth Circuit Tools
 * Plugin URI: https://github.com/TylerALofall/WordPress
 * Description: Advanced legal tools for 9th Circuit case development with evidence collection, citation management, and rule-based outline building. Features a futuristic floating glass UI with MCP server connectivity.
 * Version: 1.0.0
 * Author: Tyler Lofall
 * Author URI: https://github.com/TylerALofall
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ninth-circuit-tools
 * Domain Path: /languages
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'NCT_VERSION', '1.0.0' );
define( 'NCT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NCT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NCT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Ninth Circuit Tools Class
 *
 * @since 1.0.0
 */
class Ninth_Circuit_Tools {

	/**
	 * Single instance of the class
	 *
	 * @var Ninth_Circuit_Tools
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Ninth_Circuit_Tools
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
		$this->load_dependencies();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_footer', array( $this, 'render_floating_panel' ) );
		add_action( 'wp_footer', array( $this, 'render_floating_panel' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-evidence-collector.php';
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-citation-manager.php';
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-outline-builder.php';
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-mcp-connector.php';
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-rest-api.php';
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-micro-tools.php';
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-uid-registry.php';
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-evidence-card.php';
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-scoreboard.php';
		require_once NCT_PLUGIN_DIR . 'includes/class-nct-template-loader.php';

		// Initialize micro-tools
		NCT_Micro_Tools::init();
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_style(
			'ninth-circuit-tools-admin',
			NCT_PLUGIN_URL . 'assets/css/ninth-circuit-tools.css',
			array(),
			NCT_VERSION
		);

		wp_enqueue_script(
			'ninth-circuit-tools-admin',
			NCT_PLUGIN_URL . 'assets/js/ninth-circuit-tools.js',
			array( 'jquery' ),
			NCT_VERSION,
			true
		);

		// Localize script with REST API data
		wp_localize_script(
			'ninth-circuit-tools-admin',
			'nctData',
			array(
				'restUrl'   => rest_url( 'ninth-circuit-tools/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'pluginUrl' => NCT_PLUGIN_URL,
			)
		);
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		// Only load for logged-in users
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_style(
			'ninth-circuit-tools-frontend',
			NCT_PLUGIN_URL . 'assets/css/ninth-circuit-tools.css',
			array(),
			NCT_VERSION
		);

		wp_enqueue_script(
			'ninth-circuit-tools-frontend',
			NCT_PLUGIN_URL . 'assets/js/ninth-circuit-tools.js',
			array( 'jquery' ),
			NCT_VERSION,
			true
		);

		wp_localize_script(
			'ninth-circuit-tools-frontend',
			'nctData',
			array(
				'restUrl'   => rest_url( 'ninth-circuit-tools/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'pluginUrl' => NCT_PLUGIN_URL,
			)
		);
	}

	/**
	 * Render the floating glass panel
	 */
	public function render_floating_panel() {
		// Only show for logged-in users
		if ( ! is_user_logged_in() ) {
			return;
		}

		include NCT_PLUGIN_DIR . 'templates/floating-panel.php';
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		$rest_api = new NCT_REST_API();
		$rest_api->register_routes();
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Ninth Circuit Tools', 'ninth-circuit-tools' ),
			__( '9th Circuit', 'ninth-circuit-tools' ),
			'edit_posts',
			'ninth-circuit-tools',
			array( $this, 'render_admin_page' ),
			'dashicons-portfolio',
			30
		);

		// Submenu pages
		add_submenu_page(
			'ninth-circuit-tools',
			__( 'Evidence Collection', 'ninth-circuit-tools' ),
			__( 'Evidence', 'ninth-circuit-tools' ),
			'edit_posts',
			'nct-evidence',
			array( $this, 'render_evidence_page' )
		);

		add_submenu_page(
			'ninth-circuit-tools',
			__( 'Citations', 'ninth-circuit-tools' ),
			__( 'Citations', 'ninth-circuit-tools' ),
			'edit_posts',
			'nct-citations',
			array( $this, 'render_citations_page' )
		);

		add_submenu_page(
			'ninth-circuit-tools',
			__( 'Outline Builder', 'ninth-circuit-tools' ),
			__( 'Outline', 'ninth-circuit-tools' ),
			'edit_posts',
			'nct-outline',
			array( $this, 'render_outline_page' )
		);

		add_submenu_page(
			'ninth-circuit-tools',
			__( 'MCP Server', 'ninth-circuit-tools' ),
			__( 'MCP Server', 'ninth-circuit-tools' ),
			'manage_options',
			'nct-mcp',
			array( $this, 'render_mcp_page' )
		);
	}

	/**
	 * Render main admin page
	 */
	public function render_admin_page() {
		include NCT_PLUGIN_DIR . 'templates/admin-main.php';
	}

	/**
	 * Render evidence collection page
	 */
	public function render_evidence_page() {
		include NCT_PLUGIN_DIR . 'templates/admin-evidence.php';
	}

	/**
	 * Render citations page
	 */
	public function render_citations_page() {
		include NCT_PLUGIN_DIR . 'templates/admin-citations.php';
	}

	/**
	 * Render outline builder page
	 */
	public function render_outline_page() {
		include NCT_PLUGIN_DIR . 'templates/admin-outline.php';
	}

	/**
	 * Render MCP server page
	 */
	public function render_mcp_page() {
		include NCT_PLUGIN_DIR . 'templates/admin-mcp.php';
	}
}

/**
 * Initialize the plugin
 */
function ninth_circuit_tools_init() {
	return Ninth_Circuit_Tools::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'ninth_circuit_tools_init' );

/**
 * Activation hook
 */
register_activation_hook( __FILE__, 'ninth_circuit_tools_activate' );
function ninth_circuit_tools_activate() {
	// Create database tables if needed
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	// Evidence table
	$evidence_table = $wpdb->prefix . 'nct_evidence';
	$sql_evidence = "CREATE TABLE IF NOT EXISTS $evidence_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		title varchar(255) NOT NULL,
		description text,
		file_url varchar(500),
		citation_format text,
		tags text,
		created_by bigint(20),
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) $charset_collate;";

	// Citations table
	$citations_table = $wpdb->prefix . 'nct_citations';
	$sql_citations = "CREATE TABLE IF NOT EXISTS $citations_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		case_name varchar(500) NOT NULL,
		citation_text text NOT NULL,
		court varchar(255),
		year varchar(10),
		notes text,
		evidence_id bigint(20),
		created_by bigint(20),
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) $charset_collate;";

	// Outlines table
	$outlines_table = $wpdb->prefix . 'nct_outlines';
	$sql_outlines = "CREATE TABLE IF NOT EXISTS $outlines_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		title varchar(255) NOT NULL,
		outline_data longtext,
		rules_config longtext,
		created_by bigint(20),
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_evidence );
	dbDelta( $sql_citations );
	dbDelta( $sql_outlines );

	// Load dependencies for table creation
	require_once NCT_PLUGIN_DIR . 'includes/class-nct-uid-registry.php';
	require_once NCT_PLUGIN_DIR . 'includes/class-nct-evidence-card.php';
	require_once NCT_PLUGIN_DIR . 'includes/class-nct-scoreboard.php';

	// Create tables
	NCT_Evidence_Card::create_table();
	NCT_Scoreboard::create_table();

	// Set default options
	add_option( 'nct_version', NCT_VERSION );
	add_option( 'nct_mcp_enabled', false );
	add_option( 'nct_citation_style', 'bluebook' );
}

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, 'ninth_circuit_tools_deactivate' );
function ninth_circuit_tools_deactivate() {
	// Cleanup tasks if needed
}
