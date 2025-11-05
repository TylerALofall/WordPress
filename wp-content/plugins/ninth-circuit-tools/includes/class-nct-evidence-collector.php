<?php
/**
 * Evidence Collector for Ninth Circuit Tools
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Evidence Collector Class
 */
class NCT_Evidence_Collector {

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'nct_evidence';
	}

	/**
	 * Create new evidence
	 *
	 * @param array $data Evidence data.
	 * @return int|false Evidence ID or false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'title'            => '',
			'description'      => '',
			'file_url'         => '',
			'citation_format'  => '',
			'tags'             => '',
			'created_by'       => get_current_user_id(),
		);

		$data = wp_parse_args( $data, $defaults );

		// Auto-generate citation format
		if ( empty( $data['citation_format'] ) ) {
			$data['citation_format'] = $this->generate_citation_format( $data );
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'title'           => $data['title'],
				'description'     => $data['description'],
				'file_url'        => $data['file_url'],
				'citation_format' => $data['citation_format'],
				'tags'            => $data['tags'],
				'created_by'      => $data['created_by'],
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
			)
		);

		if ( $result ) {
			do_action( 'nct_evidence_created', $wpdb->insert_id, $data );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get evidence by ID
	 *
	 * @param int $id Evidence ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		$evidence = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$id
			)
		);

		if ( $evidence ) {
			$evidence->tags_array = $this->parse_tags( $evidence->tags );
		}

		return $evidence;
	}

	/**
	 * Get all evidence
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'      => 50,
			'offset'     => 0,
			'order_by'   => 'created_at',
			'order'      => 'DESC',
			'search'     => '',
			'tags'       => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		// Search
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(title LIKE %s OR description LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Tags filter
		if ( ! empty( $args['tags'] ) ) {
			$where[] = 'tags LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['tags'] ) . '%';
		}

		$where_clause = implode( ' AND ', $where );

		$query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$args['order_by']} {$args['order']} LIMIT %d OFFSET %d";

		$where_values[] = $args['limit'];
		$where_values[] = $args['offset'];

		$evidence_list = $wpdb->get_results(
			$wpdb->prepare( $query, $where_values )
		);

		// Parse tags for each item
		foreach ( $evidence_list as $evidence ) {
			$evidence->tags_array = $this->parse_tags( $evidence->tags );
		}

		return $evidence_list;
	}

	/**
	 * Update evidence
	 *
	 * @param int   $id Evidence ID.
	 * @param array $data Evidence data.
	 * @return bool
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$allowed_fields = array( 'title', 'description', 'file_url', 'citation_format', 'tags' );
		$update_data = array();
		$format = array();

		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$update_data[ $field ] = $data[ $field ];
				$format[] = '%s';
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		if ( $result !== false ) {
			do_action( 'nct_evidence_updated', $id, $data );
			return true;
		}

		return false;
	}

	/**
	 * Delete evidence
	 *
	 * @param int $id Evidence ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( $result ) {
			do_action( 'nct_evidence_deleted', $id );
			return true;
		}

		return false;
	}

	/**
	 * Search evidence
	 *
	 * @param string $query Search query.
	 * @return array
	 */
	public function search( $query ) {
		return $this->get_all( array( 'search' => $query ) );
	}

	/**
	 * Get evidence by tags
	 *
	 * @param string $tags Tags to search for.
	 * @return array
	 */
	public function get_by_tags( $tags ) {
		return $this->get_all( array( 'tags' => $tags ) );
	}

	/**
	 * Generate citation format for evidence
	 *
	 * @param array $data Evidence data.
	 * @return string
	 */
	private function generate_citation_format( $data ) {
		$title = ! empty( $data['title'] ) ? $data['title'] : 'Exhibit';
		$date  = gmdate( 'Y' );

		// Simple format: "Title (Date)"
		$citation = sprintf(
			'%s (%s)',
			esc_html( $title ),
			$date
		);

		return apply_filters( 'nct_evidence_citation_format', $citation, $data );
	}

	/**
	 * Parse tags string into array
	 *
	 * @param string $tags Tags string.
	 * @return array
	 */
	private function parse_tags( $tags ) {
		if ( empty( $tags ) ) {
			return array();
		}

		return array_map( 'trim', explode( ',', $tags ) );
	}

	/**
	 * Get evidence count
	 *
	 * @return int
	 */
	public function get_count() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
	}

	/**
	 * Export evidence to JSON
	 *
	 * @param array $ids Evidence IDs to export.
	 * @return string JSON data.
	 */
	public function export_json( $ids = array() ) {
		if ( empty( $ids ) ) {
			$evidence = $this->get_all( array( 'limit' => 999999 ) );
		} else {
			$evidence = array();
			foreach ( $ids as $id ) {
				$item = $this->get( $id );
				if ( $item ) {
					$evidence[] = $item;
				}
			}
		}

		return wp_json_encode( $evidence, JSON_PRETTY_PRINT );
	}

	/**
	 * Import evidence from JSON
	 *
	 * @param string $json JSON data.
	 * @return array Import results.
	 */
	public function import_json( $json ) {
		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			return array(
				'success' => false,
				'error'   => 'Invalid JSON format',
			);
		}

		$imported = 0;
		$errors   = array();

		foreach ( $data as $item ) {
			$result = $this->create( $item );
			if ( $result ) {
				$imported++;
			} else {
				$errors[] = 'Failed to import: ' . ( $item['title'] ?? 'Unknown' );
			}
		}

		return array(
			'success'  => true,
			'imported' => $imported,
			'errors'   => $errors,
		);
	}
}
