<?php
/**
 * Model Racing Scoreboard
 * Tracks points for competing AI models
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Scoreboard Class
 */
class NCT_Scoreboard {

	/**
	 * Table name
	 *
	 * @var string
	 */
	private static $table_name;

	/**
	 * Initialize
	 */
	public static function init() {
		global $wpdb;
		self::$table_name = $wpdb->prefix . 'nct_scoreboard';

		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS " . self::$table_name . " (
			id bigint NOT NULL AUTO_INCREMENT,
			model_name varchar(100) NOT NULL,
			task_type varchar(50) NOT NULL,
			points int NOT NULL DEFAULT 0,
			details longtext,
			created_at bigint NOT NULL,
			PRIMARY KEY (id),
			KEY model_name (model_name),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Add points for a model
	 *
	 * @param string $model_name Model name (e.g., 'claude-sonnet', 'gpt-4', 'qwen3').
	 * @param string $task_type  Task type (e.g., 'tool_built', 'fact_extracted').
	 * @param int    $points     Points to award.
	 * @param array  $details    Additional details (optional).
	 * @return bool Success.
	 */
	public static function add_points( $model_name, $task_type, $points, $details = array() ) {
		global $wpdb;

		$result = $wpdb->insert(
			self::$table_name,
			array(
				'model_name' => sanitize_text_field( $model_name ),
				'task_type'  => sanitize_text_field( $task_type ),
				'points'     => (int) $points,
				'details'    => wp_json_encode( $details ),
				'created_at' => time(),
			)
		);

		do_action( 'nct_scoreboard_points_added', $model_name, $task_type, $points );

		return false !== $result;
	}

	/**
	 * Get leaderboard
	 *
	 * @param int $limit Number of models to return.
	 * @return array Leaderboard data.
	 */
	public static function get_leaderboard( $limit = 10 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					model_name,
					SUM(points) as total_points,
					COUNT(*) as task_count
				FROM " . self::$table_name . "
				GROUP BY model_name
				ORDER BY total_points DESC
				LIMIT %d",
				$limit
			)
		);

		return $results;
	}

	/**
	 * Get model stats
	 *
	 * @param string $model_name Model name.
	 * @return object|null Model stats.
	 */
	public static function get_model_stats( $model_name ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					model_name,
					SUM(points) as total_points,
					COUNT(*) as task_count,
					MAX(created_at) as last_active
				FROM " . self::$table_name . "
				WHERE model_name = %s
				GROUP BY model_name",
				$model_name
			)
		);
	}

	/**
	 * Register REST API routes
	 */
	public static function register_rest_routes() {
		// Webhook: Submit score
		register_rest_route(
			'ninth-circuit-tools/v1',
			'/scoreboard/submit',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_submit_score' ),
				'permission_callback' => '__return_true', // Open webhook
			)
		);

		// Get leaderboard
		register_rest_route(
			'ninth-circuit-tools/v1',
			'/scoreboard',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_leaderboard' ),
				'permission_callback' => '__return_true',
			)
		);

		// Get model stats
		register_rest_route(
			'ninth-circuit-tools/v1',
			'/scoreboard/(?P<model>[a-zA-Z0-9\-_]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_model_stats' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST: Submit score (webhook)
	 */
	public static function rest_submit_score( $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['model'] ) || empty( $data['task'] ) || ! isset( $data['points'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Missing required fields: model, task, points',
				),
				400
			);
		}

		$result = self::add_points(
			$data['model'],
			$data['task'],
			$data['points'],
			isset( $data['details'] ) ? $data['details'] : array()
		);

		if ( $result ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Points added',
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'success' => false,
				'error'   => 'Failed to add points',
			),
			500
		);
	}

	/**
	 * REST: Get leaderboard
	 */
	public static function rest_get_leaderboard( $request ) {
		$limit       = $request->get_param( 'limit' ) ?: 10;
		$leaderboard = self::get_leaderboard( $limit );

		return new WP_REST_Response(
			array(
				'success'     => true,
				'leaderboard' => $leaderboard,
			),
			200
		);
	}

	/**
	 * REST: Get model stats
	 */
	public static function rest_get_model_stats( $request ) {
		$model = $request->get_param( 'model' );
		$stats = self::get_model_stats( $model );

		if ( ! $stats ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Model not found',
				),
				404
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'stats'   => $stats,
			),
			200
		);
	}
}

// Initialize
NCT_Scoreboard::init();
