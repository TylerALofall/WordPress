<?php
/**
 * Evidence Card System
 * Manages evidence cards with UID linking
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Evidence Card Class
 */
class NCT_Evidence_Card {

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
		self::$table_name = $wpdb->prefix . 'nct_evidence_cards';

		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS " . self::$table_name . " (
			id varchar(36) NOT NULL,
			uids text NOT NULL,
			cause_of_action_ids text NOT NULL,
			defendant_ids text NOT NULL,
			non_party_players text,
			claim text NOT NULL,
			evidence_date date NOT NULL,
			description text NOT NULL,
			source varchar(500),
			significance text NOT NULL,
			depiction text,
			original_text longtext,
			page_number int,
			source_file_name varchar(500),
			case_law longtext,
			declaration_of_authenticity text,
			final_statement text,
			notes text,
			uid_details longtext,
			raw_ai_response longtext,
			created_at bigint NOT NULL,
			created_by bigint,
			updated_at bigint,
			PRIMARY KEY (id),
			KEY evidence_date (evidence_date),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create evidence card
	 *
	 * @param array $data Evidence card data.
	 * @return string|WP_Error Card ID or error.
	 */
	public static function create( $data ) {
		global $wpdb;

		// Validate UIDs (max 3)
		if ( empty( $data['uids'] ) || ! is_array( $data['uids'] ) ) {
			return new WP_Error( 'missing_uids', 'UIDs are required' );
		}

		if ( count( $data['uids'] ) > 3 ) {
			return new WP_Error( 'too_many_uids', 'Maximum 3 UIDs per evidence card' );
		}

		// Validate each UID
		foreach ( $data['uids'] as $uid ) {
			$validation = NCT_UID_Registry::validate_uid( $uid );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
		}

		// Generate UUID if not provided
		$id = ! empty( $data['id'] ) ? $data['id'] : wp_generate_uuid4();

		// Unix timestamp as primary time key
		$timestamp = time();

		// Prepare data
		$insert_data = array(
			'id'                         => $id,
			'uids'                       => wp_json_encode( $data['uids'] ),
			'cause_of_action_ids'        => wp_json_encode( $data['causeOfActionIds'] ?? array() ),
			'defendant_ids'              => wp_json_encode( $data['defendantIds'] ?? array() ),
			'non_party_players'          => wp_json_encode( $data['nonPartyPlayers'] ?? array() ),
			'claim'                      => sanitize_textarea_field( $data['claim'] ),
			'evidence_date'              => sanitize_text_field( $data['date'] ),
			'description'                => sanitize_textarea_field( $data['description'] ),
			'source'                     => sanitize_text_field( $data['source'] ?? '' ),
			'significance'               => sanitize_textarea_field( $data['significance'] ),
			'depiction'                  => sanitize_textarea_field( $data['depiction'] ?? '' ),
			'original_text'              => $data['originalText'] ?? '',
			'page_number'                => isset( $data['pageNumber'] ) ? (int) $data['pageNumber'] : null,
			'source_file_name'           => sanitize_text_field( $data['sourceFileName'] ?? '' ),
			'case_law'                   => wp_json_encode( $data['caseLaw'] ?? array() ),
			'declaration_of_authenticity' => sanitize_textarea_field( $data['declarationOfAuthenticity'] ?? '' ),
			'final_statement'            => sanitize_textarea_field( $data['finalStatement'] ?? '' ),
			'notes'                      => sanitize_textarea_field( $data['notes'] ?? '' ),
			'uid_details'                => wp_json_encode( $data['uidDetails'] ?? array() ),
			'raw_ai_response'            => wp_json_encode( $data['rawAiResponse'] ?? array() ),
			'created_at'                 => $timestamp,
			'created_by'                 => get_current_user_id(),
			'updated_at'                 => $timestamp,
		);

		$result = $wpdb->insert( self::$table_name, $insert_data );

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to create evidence card' );
		}

		do_action( 'nct_evidence_card_created', $id, $data );

		return $id;
	}

	/**
	 * Get evidence card by ID
	 *
	 * @param string $id Card ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;

		$card = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM " . self::$table_name . " WHERE id = %s",
				$id
			)
		);

		if ( $card ) {
			return self::decode_card( $card );
		}

		return null;
	}

	/**
	 * Get all evidence cards
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'      => 50,
			'offset'     => 0,
			'order_by'   => 'created_at',
			'order'      => 'DESC',
			'uid'        => null,
			'claim'      => null,
			'defendant'  => null,
			'date_from'  => null,
			'date_to'    => null,
			'search'     => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		// Filter by UID
		if ( ! empty( $args['uid'] ) ) {
			$where[] = 'uids LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['uid'] ) . '%';
		}

		// Filter by claim
		if ( ! empty( $args['claim'] ) ) {
			$where[] = 'cause_of_action_ids LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( '"' . $args['claim'] . '"' ) . '%';
		}

		// Filter by defendant
		if ( ! empty( $args['defendant'] ) ) {
			$where[] = 'defendant_ids LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( '"' . $args['defendant'] . '"' ) . '%';
		}

		// Date range
		if ( ! empty( $args['date_from'] ) ) {
			$where[] = 'evidence_date >= %s';
			$where_values[] = $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[] = 'evidence_date <= %s';
			$where_values[] = $args['date_to'];
		}

		// Search
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(claim LIKE %s OR description LIKE %s OR significance LIKE %s OR source LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM " . self::$table_name . " WHERE {$where_clause} ORDER BY {$args['order_by']} {$args['order']} LIMIT %d OFFSET %d";

		$where_values[] = $args['limit'];
		$where_values[] = $args['offset'];

		$cards = $wpdb->get_results(
			$wpdb->prepare( $query, $where_values )
		);

		return array_map( array( __CLASS__, 'decode_card' ), $cards );
	}

	/**
	 * Update evidence card
	 *
	 * @param string $id Card ID.
	 * @param array  $data Card data.
	 * @return bool
	 */
	public static function update( $id, $data ) {
		global $wpdb;

		$update_data = array(
			'updated_at' => time(),
		);

		// Only update allowed fields
		$allowed_fields = array(
			'uids', 'causeOfActionIds', 'defendantIds', 'nonPartyPlayers',
			'claim', 'date', 'description', 'source', 'significance',
			'depiction', 'originalText', 'pageNumber', 'sourceFileName',
			'caseLaw', 'declarationOfAuthenticity', 'finalStatement',
			'notes', 'uidDetails', 'rawAiResponse',
		);

		foreach ( $allowed_fields as $field ) {
			$db_field = self::camel_to_snake( $field );

			if ( isset( $data[ $field ] ) ) {
				if ( in_array( $field, array( 'uids', 'causeOfActionIds', 'defendantIds', 'nonPartyPlayers', 'caseLaw', 'uidDetails', 'rawAiResponse' ), true ) ) {
					$update_data[ $db_field ] = wp_json_encode( $data[ $field ] );
				} elseif ( 'date' === $field ) {
					$update_data['evidence_date'] = sanitize_text_field( $data[ $field ] );
				} else {
					$update_data[ $db_field ] = sanitize_textarea_field( $data[ $field ] );
				}
			}
		}

		$result = $wpdb->update(
			self::$table_name,
			$update_data,
			array( 'id' => $id ),
			null,
			array( '%s' )
		);

		if ( false !== $result ) {
			do_action( 'nct_evidence_card_updated', $id, $data );
			return true;
		}

		return false;
	}

	/**
	 * Delete evidence card
	 *
	 * @param string $id Card ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			self::$table_name,
			array( 'id' => $id ),
			array( '%s' )
		);

		if ( $result ) {
			do_action( 'nct_evidence_card_deleted', $id );
			return true;
		}

		return false;
	}

	/**
	 * Get evidence cards by UID
	 *
	 * @param string $uid UID to search for.
	 * @return array
	 */
	public static function get_by_uid( $uid ) {
		return self::get_all( array( 'uid' => $uid, 'limit' => 999 ) );
	}

	/**
	 * Export to CSV
	 *
	 * @param array $card_ids Card IDs to export (empty = all).
	 * @return string CSV content.
	 */
	public static function export_csv( $card_ids = array() ) {
		if ( empty( $card_ids ) ) {
			$cards = self::get_all( array( 'limit' => 999999, 'order_by' => 'created_at', 'order' => 'ASC' ) );
		} else {
			$cards = array();
			foreach ( $card_ids as $id ) {
				$card = self::get( $id );
				if ( $card ) {
					$cards[] = $card;
				}
			}
		}

		// CSV headers
		$csv = "timestamp,id,uids,claims,defendants,date,claim,source,significance,page,created_at_unix\n";

		foreach ( $cards as $card ) {
			$row = array(
				gmdate( 'Y-m-d H:i:s', $card->created_at ),
				$card->id,
				implode( ';', json_decode( $card->uids, true ) ),
				implode( ';', json_decode( $card->cause_of_action_ids, true ) ),
				implode( ';', json_decode( $card->defendant_ids, true ) ),
				$card->evidence_date,
				self::csv_escape( $card->claim ),
				self::csv_escape( $card->source ),
				self::csv_escape( $card->significance ),
				$card->page_number,
				$card->created_at,
			);

			$csv .= implode( ',', array_map( array( __CLASS__, 'csv_quote' ), $row ) ) . "\n";
		}

		return $csv;
	}

	/**
	 * Decode JSON fields in card
	 *
	 * @param object $card Card object.
	 * @return object
	 */
	private static function decode_card( $card ) {
		$card->uids                  = json_decode( $card->uids, true );
		$card->cause_of_action_ids   = json_decode( $card->cause_of_action_ids, true );
		$card->defendant_ids         = json_decode( $card->defendant_ids, true );
		$card->non_party_players     = json_decode( $card->non_party_players, true );
		$card->case_law              = json_decode( $card->case_law, true );
		$card->uid_details           = json_decode( $card->uid_details, true );
		$card->raw_ai_response       = json_decode( $card->raw_ai_response, true );

		return $card;
	}

	/**
	 * Convert camelCase to snake_case
	 *
	 * @param string $string String to convert.
	 * @return string
	 */
	private static function camel_to_snake( $string ) {
		return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $string ) );
	}

	/**
	 * Escape string for CSV
	 *
	 * @param string $string String to escape.
	 * @return string
	 */
	private static function csv_escape( $string ) {
		return str_replace( array( "\r", "\n" ), ' ', $string );
	}

	/**
	 * Quote string for CSV
	 *
	 * @param string $string String to quote.
	 * @return string
	 */
	private static function csv_quote( $string ) {
		if ( strpos( $string, ',' ) !== false || strpos( $string, '"' ) !== false ) {
			return '"' . str_replace( '"', '""', $string ) . '"';
		}
		return $string;
	}

	/**
	 * Register REST API routes
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'ninth-circuit-tools/v1',
			'/evidence-cards',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_get_cards' ),
					'permission_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_create_card' ),
					'permission_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
				),
			)
		);

		register_rest_route(
			'ninth-circuit-tools/v1',
			'/evidence-cards/(?P<id>[a-f0-9\-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_get_card' ),
					'permission_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( __CLASS__, 'rest_update_card' ),
					'permission_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'rest_delete_card' ),
					'permission_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
				),
			)
		);

		register_rest_route(
			'ninth-circuit-tools/v1',
			'/evidence-cards/export/csv',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_export_csv' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * REST: Get cards
	 */
	public static function rest_get_cards( $request ) {
		$args = array(
			'limit'     => $request->get_param( 'limit' ) ?: 50,
			'offset'    => $request->get_param( 'offset' ) ?: 0,
			'uid'       => $request->get_param( 'uid' ),
			'claim'     => $request->get_param( 'claim' ),
			'defendant' => $request->get_param( 'defendant' ),
			'search'    => $request->get_param( 'search' ),
		);

		$cards = self::get_all( $args );

		return new WP_REST_Response(
			array(
				'success' => true,
				'cards'   => $cards,
				'count'   => count( $cards ),
			),
			200
		);
	}

	/**
	 * REST: Create card
	 */
	public static function rest_create_card( $request ) {
		$data = $request->get_json_params();
		$id   = self::create( $data );

		if ( is_wp_error( $id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => $id->get_error_message(),
				),
				400
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $id,
				'message' => 'Evidence card created',
			),
			201
		);
	}

	/**
	 * REST: Get single card
	 */
	public static function rest_get_card( $request ) {
		$id   = $request->get_param( 'id' );
		$card = self::get( $id );

		if ( ! $card ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Card not found',
				),
				404
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'card'    => $card,
			),
			200
		);
	}

	/**
	 * REST: Update card
	 */
	public static function rest_update_card( $request ) {
		$id     = $request->get_param( 'id' );
		$data   = $request->get_json_params();
		$result = self::update( $id, $data );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Failed to update card',
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Evidence card updated',
			),
			200
		);
	}

	/**
	 * REST: Delete card
	 */
	public static function rest_delete_card( $request ) {
		$id     = $request->get_param( 'id' );
		$result = self::delete( $id );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => 'Failed to delete card',
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Evidence card deleted',
			),
			200
		);
	}

	/**
	 * REST: Export CSV
	 */
	public static function rest_export_csv( $request ) {
		$csv = self::export_csv();

		return new WP_REST_Response(
			$csv,
			200,
			array(
				'Content-Type'        => 'text/csv',
				'Content-Disposition' => 'attachment; filename="evidence-cards-' . time() . '.csv"',
			)
		);
	}
}

// Initialize
NCT_Evidence_Card::init();
