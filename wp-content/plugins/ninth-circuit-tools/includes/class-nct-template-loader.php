<?php
/**
 * Template Hot Bar Loader
 * Quick-load case law, evidence, and positions by UID code
 *
 * Usage: load('933-CL1') → loads case law #1 for UID 933
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Template Loader Class
 */
class NCT_Template_Loader {

	/**
	 * Content registry (UID → content items)
	 *
	 * @var array
	 */
	private static $registry = array();

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		self::load_registry();
	}

	/**
	 * Load content registry from database
	 */
	private static function load_registry() {
		// Load from wp_options or custom table
		$stored = get_option( 'nct_content_registry', array() );
		self::$registry = $stored;
	}

	/**
	 * Save registry to database
	 */
	private static function save_registry() {
		update_option( 'nct_content_registry', self::$registry );
	}

	/**
	 * Register content for a UID
	 *
	 * @param string $uid      UID (e.g., '933', '111').
	 * @param string $type     Type: 'CL' (case law), 'E' (evidence), 'P' (plaintiff position), 'D' (defendant position), 'ECF' (their quote).
	 * @param int    $number   Position number (1, 2, 3...).
	 * @param array  $content  Content data.
	 * @return bool Success.
	 */
	public static function register( $uid, $type, $number, $content ) {
		$key = self::build_key( $uid, $type, $number );

		if ( ! isset( self::$registry[ $uid ] ) ) {
			self::$registry[ $uid ] = array();
		}

		self::$registry[ $uid ][ $key ] = $content;
		self::save_registry();

		return true;
	}

	/**
	 * Load content by code
	 *
	 * @param string $code Code like '933-CL1', '111-E2', '234-DP'.
	 * @return array|WP_Error Content or error.
	 */
	public static function load( $code ) {
		$parsed = self::parse_code( $code );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$uid    = $parsed['uid'];
		$type   = $parsed['type'];
		$number = $parsed['number'];
		$key    = self::build_key( $uid, $type, $number );

		if ( ! isset( self::$registry[ $uid ][ $key ] ) ) {
			return new WP_Error( 'not_found', "No content found for code: $code" );
		}

		return self::$registry[ $uid ][ $key ];
	}

	/**
	 * Parse code string into components
	 *
	 * @param string $code Code like '933-CL1'.
	 * @return array|WP_Error Parsed components or error.
	 */
	private static function parse_code( $code ) {
		// Format: UID-TYPE[NUMBER]
		// Examples: 933-CL1, 111-E2, 234-P, 933-DP

		if ( ! preg_match( '/^(\d{3,4})-([A-Z]+)(\d*)$/', $code, $matches ) ) {
			return new WP_Error( 'invalid_format', 'Code must be format: UID-TYPE[NUMBER] (e.g., 933-CL1)' );
		}

		$uid    = $matches[1];
		$type   = $matches[2];
		$number = ! empty( $matches[3] ) ? (int) $matches[3] : null;

		// Validate type
		$valid_types = array( 'CL', 'E', 'P', 'D', 'DP', 'ECF' );
		if ( ! in_array( $type, $valid_types, true ) ) {
			return new WP_Error( 'invalid_type', 'Type must be: CL, E, P, D, DP, or ECF' );
		}

		return array(
			'uid'    => $uid,
			'type'   => $type,
			'number' => $number,
		);
	}

	/**
	 * Build storage key
	 *
	 * @param string $uid    UID.
	 * @param string $type   Type.
	 * @param int    $number Position number.
	 * @return string Key.
	 */
	private static function build_key( $uid, $type, $number ) {
		return $number ? "{$type}{$number}" : $type;
	}

	/**
	 * Get all content for a UID
	 *
	 * @param string $uid UID.
	 * @return array All registered content.
	 */
	public static function get_all_for_uid( $uid ) {
		return isset( self::$registry[ $uid ] ) ? self::$registry[ $uid ] : array();
	}

	/**
	 * Auto-register from evidence card
	 *
	 * @param array $evidence_card Evidence card data.
	 * @return bool Success.
	 */
	public static function register_from_evidence_card( $evidence_card ) {
		$uids = $evidence_card['uids'];

		foreach ( $uids as $index => $uid ) {
			// Register evidence
			self::register(
				$uid,
				'E',
				$index + 1,
				array(
					'source'       => $evidence_card['source'],
					'description'  => $evidence_card['description'],
					'significance' => $evidence_card['significance'],
					'quote'        => isset( $evidence_card['originalText'] ) ? $evidence_card['originalText'] : '',
					'page'         => isset( $evidence_card['pageNumber'] ) ? $evidence_card['pageNumber'] : null,
				)
			);

			// Register case law from uidDetails
			if ( isset( $evidence_card['uidDetails'][ $index ] ) ) {
				$detail = $evidence_card['uidDetails'][ $index ];

				if ( isset( $detail['caseLaw'] ) ) {
					self::register(
						$uid,
						'CL',
						1,
						array(
							'citation' => $detail['caseLaw'],
							'context'  => $detail['significance'],
						)
					);
				}
			}

			// Register plaintiff position (significance)
			self::register(
				$uid,
				'P',
				null,
				array(
					'position' => $evidence_card['claim'],
					'argument' => $evidence_card['significance'],
				)
			);
		}

		return true;
	}

	/**
	 * Register REST API routes
	 */
	public static function register_rest_routes() {
		// Load content by code
		register_rest_route(
			'ninth-circuit-tools/v1',
			'/template/load/(?P<code>[0-9A-Z\-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_load' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		// Register new content
		register_rest_route(
			'ninth-circuit-tools/v1',
			'/template/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_register' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		// Get all for UID
		register_rest_route(
			'ninth-circuit-tools/v1',
			'/template/uid/(?P<uid>\d{3,4})',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_uid' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * REST: Load by code
	 */
	public static function rest_load( $request ) {
		$code    = $request->get_param( 'code' );
		$content = self::load( $code );

		if ( is_wp_error( $content ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => $content->get_error_message(),
				),
				404
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'code'    => $code,
				'content' => $content,
			),
			200
		);
	}

	/**
	 * REST: Register content
	 */
	public static function rest_register( $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['uid'] ) || empty( $data['type'] ) || empty( $data['content'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Missing required fields: uid, type, content',
				),
				400
			);
		}

		$result = self::register(
			$data['uid'],
			$data['type'],
			isset( $data['number'] ) ? $data['number'] : null,
			$data['content']
		);

		return new WP_REST_Response(
			array(
				'success' => $result,
				'message' => 'Content registered',
			),
			200
		);
	}

	/**
	 * REST: Get all for UID
	 */
	public static function rest_get_uid( $request ) {
		$uid     = $request->get_param( 'uid' );
		$content = self::get_all_for_uid( $uid );

		return new WP_REST_Response(
			array(
				'success' => true,
				'uid'     => $uid,
				'content' => $content,
			),
			200
		);
	}
}

// Initialize
NCT_Template_Loader::init();
