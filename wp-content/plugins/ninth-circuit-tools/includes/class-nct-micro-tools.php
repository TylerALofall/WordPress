<?php
/**
 * Micro Tools Manager
 * Manages a collection of small, focused tools that do one thing well
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Micro Tools Manager Class
 */
class NCT_Micro_Tools {

	/**
	 * Registered tools
	 *
	 * @var array
	 */
	private static $tools = array();

	/**
	 * Checkpoint storage
	 *
	 * @var array
	 */
	private static $checkpoints = array();

	/**
	 * Initialize micro-tools system
	 */
	public static function init() {
		self::register_core_tools();
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Register a micro-tool
	 *
	 * @param string $id Tool ID (slug).
	 * @param array  $config Tool configuration.
	 */
	public static function register_tool( $id, $config ) {
		$defaults = array(
			'name'        => '',
			'description' => '',
			'icon'        => '🔧',
			'category'    => 'general',
			'callback'    => null,
			'params'      => array(),
			'checkpoint'  => true, // Create checkpoint before running
		);

		self::$tools[ $id ] = wp_parse_args( $config, $defaults );
	}

	/**
	 * Get all registered tools
	 *
	 * @param string $category Optional category filter.
	 * @return array
	 */
	public static function get_tools( $category = null ) {
		if ( $category ) {
			return array_filter(
				self::$tools,
				function( $tool ) use ( $category ) {
					return $tool['category'] === $category;
				}
			);
		}

		return self::$tools;
	}

	/**
	 * Execute a tool
	 *
	 * @param string $tool_id Tool ID.
	 * @param array  $params Tool parameters.
	 * @return array Result.
	 */
	public static function execute( $tool_id, $params = array() ) {
		if ( ! isset( self::$tools[ $tool_id ] ) ) {
			return array(
				'success' => false,
				'error'   => 'Tool not found: ' . $tool_id,
			);
		}

		$tool = self::$tools[ $tool_id ];

		// Create checkpoint if enabled
		if ( $tool['checkpoint'] ) {
			self::create_checkpoint( $tool_id, $params );
		}

		// Execute the tool
		try {
			if ( is_callable( $tool['callback'] ) ) {
				$result = call_user_func( $tool['callback'], $params );

				if ( is_array( $result ) && isset( $result['success'] ) ) {
					return $result;
				}

				return array(
					'success' => true,
					'data'    => $result,
				);
			}

			return array(
				'success' => false,
				'error'   => 'Tool callback is not callable',
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Create checkpoint
	 *
	 * @param string $tool_id Tool ID.
	 * @param array  $params Parameters.
	 */
	private static function create_checkpoint( $tool_id, $params ) {
		$checkpoint = array(
			'tool_id'   => $tool_id,
			'params'    => $params,
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => get_current_user_id(),
		);

		self::$checkpoints[] = $checkpoint;

		// Keep only last 50 checkpoints
		if ( count( self::$checkpoints ) > 50 ) {
			array_shift( self::$checkpoints );
		}

		update_option( 'nct_checkpoints', self::$checkpoints );
	}

	/**
	 * Get checkpoints
	 *
	 * @param int $limit Number of checkpoints to retrieve.
	 * @return array
	 */
	public static function get_checkpoints( $limit = 10 ) {
		$checkpoints = get_option( 'nct_checkpoints', array() );
		return array_slice( $checkpoints, -$limit );
	}

	/**
	 * Register REST API routes
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'ninth-circuit-tools/v1',
			'/tools',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_tools' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			'ninth-circuit-tools/v1',
			'/tools/execute',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_execute_tool' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			'ninth-circuit-tools/v1',
			'/checkpoints',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_checkpoints' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * REST: Get tools
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function rest_get_tools( $request ) {
		$category = $request->get_param( 'category' );
		$tools    = self::get_tools( $category );

		return new WP_REST_Response(
			array(
				'success' => true,
				'tools'   => $tools,
			),
			200
		);
	}

	/**
	 * REST: Execute tool
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function rest_execute_tool( $request ) {
		$tool_id = sanitize_text_field( $request->get_param( 'tool_id' ) );
		$params  = $request->get_param( 'params' );

		if ( empty( $tool_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Tool ID is required',
				),
				400
			);
		}

		$result = self::execute( $tool_id, $params );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * REST: Get checkpoints
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function rest_get_checkpoints( $request ) {
		$limit       = $request->get_param( 'limit' ) ? (int) $request->get_param( 'limit' ) : 10;
		$checkpoints = self::get_checkpoints( $limit );

		return new WP_REST_Response(
			array(
				'success'     => true,
				'checkpoints' => $checkpoints,
			),
			200
		);
	}

	/**
	 * Register core tools
	 */
	private static function register_core_tools() {
		// PDF Tools
		require_once NCT_PLUGIN_DIR . 'includes/tools/class-nct-tool-pdf-merge.php';
		require_once NCT_PLUGIN_DIR . 'includes/tools/class-nct-tool-pdf-split.php';
		require_once NCT_PLUGIN_DIR . 'includes/tools/class-nct-tool-pdf-page-numbers.php';

		// Caption Tools
		require_once NCT_PLUGIN_DIR . 'includes/tools/class-nct-tool-caption.php';

		// Fact Pool Tools
		require_once NCT_PLUGIN_DIR . 'includes/tools/class-nct-tool-fact-pool.php';

		// Text Tools
		require_once NCT_PLUGIN_DIR . 'includes/tools/class-nct-tool-text-clean.php';
		require_once NCT_PLUGIN_DIR . 'includes/tools/class-nct-tool-word-count.php';

		// Register each tool
		NCT_Tool_PDF_Merge::register();
		NCT_Tool_PDF_Split::register();
		NCT_Tool_PDF_Page_Numbers::register();
		NCT_Tool_Caption::register();
		NCT_Tool_Fact_Pool::register();
		NCT_Tool_Text_Clean::register();
		NCT_Tool_Word_Count::register();
	}
}
