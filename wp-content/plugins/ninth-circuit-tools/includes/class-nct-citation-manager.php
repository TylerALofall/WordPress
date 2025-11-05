<?php
/**
 * Citation Manager for Ninth Circuit Tools
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Citation Manager Class
 */
class NCT_Citation_Manager {

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
		$this->table_name = $wpdb->prefix . 'nct_citations';
	}

	/**
	 * Create new citation
	 *
	 * @param array $data Citation data.
	 * @return int|false Citation ID or false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'case_name'     => '',
			'citation_text' => '',
			'court'         => '',
			'year'          => '',
			'notes'         => '',
			'evidence_id'   => null,
			'created_by'    => get_current_user_id(),
		);

		$data = wp_parse_args( $data, $defaults );

		// Auto-format citation if not provided
		if ( empty( $data['citation_text'] ) ) {
			$style = ! empty( $data['style'] ) ? $data['style'] : get_option( 'nct_citation_style', 'bluebook' );
			$data['citation_text'] = $this->format_citation( $data, $style );
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'case_name'     => $data['case_name'],
				'citation_text' => $data['citation_text'],
				'court'         => $data['court'],
				'year'          => $data['year'],
				'notes'         => $data['notes'],
				'evidence_id'   => $data['evidence_id'],
				'created_by'    => $data['created_by'],
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
			)
		);

		if ( $result ) {
			do_action( 'nct_citation_created', $wpdb->insert_id, $data );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get citation by ID
	 *
	 * @param int $id Citation ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$id
			)
		);
	}

	/**
	 * Get all citations
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'       => 50,
			'offset'      => 0,
			'order_by'    => 'created_at',
			'order'       => 'DESC',
			'search'      => '',
			'court'       => '',
			'year'        => '',
			'evidence_id' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		// Search
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(case_name LIKE %s OR citation_text LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Court filter
		if ( ! empty( $args['court'] ) ) {
			$where[] = 'court = %s';
			$where_values[] = $args['court'];
		}

		// Year filter
		if ( ! empty( $args['year'] ) ) {
			$where[] = 'year = %s';
			$where_values[] = $args['year'];
		}

		// Evidence ID filter
		if ( ! empty( $args['evidence_id'] ) ) {
			$where[] = 'evidence_id = %d';
			$where_values[] = $args['evidence_id'];
		}

		$where_clause = implode( ' AND ', $where );

		$query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$args['order_by']} {$args['order']} LIMIT %d OFFSET %d";

		$where_values[] = $args['limit'];
		$where_values[] = $args['offset'];

		return $wpdb->get_results(
			$wpdb->prepare( $query, $where_values )
		);
	}

	/**
	 * Update citation
	 *
	 * @param int   $id Citation ID.
	 * @param array $data Citation data.
	 * @return bool
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$allowed_fields = array( 'case_name', 'citation_text', 'court', 'year', 'notes', 'evidence_id' );
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
			do_action( 'nct_citation_updated', $id, $data );
			return true;
		}

		return false;
	}

	/**
	 * Delete citation
	 *
	 * @param int $id Citation ID.
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
			do_action( 'nct_citation_deleted', $id );
			return true;
		}

		return false;
	}

	/**
	 * Format citation based on style
	 *
	 * @param array|object $data Citation data.
	 * @param string       $style Citation style.
	 * @return string
	 */
	public function format( $data, $style = 'bluebook' ) {
		// Convert object to array if needed
		if ( is_object( $data ) ) {
			$data = (array) $data;
		}

		$style = strtolower( $style );

		switch ( $style ) {
			case 'bluebook':
				return $this->format_bluebook( $data );

			case 'alwd':
				return $this->format_alwd( $data );

			case 'mla':
				return $this->format_mla( $data );

			case 'apa':
				return $this->format_apa( $data );

			default:
				return $this->format_bluebook( $data );
		}
	}

	/**
	 * Format citation in Bluebook style
	 *
	 * @param array $data Citation data.
	 * @return string
	 */
	private function format_bluebook( $data ) {
		$case_name = ! empty( $data['case_name'] ) ? $data['case_name'] : '';
		$court     = ! empty( $data['court'] ) ? $data['court'] : '';
		$year      = ! empty( $data['year'] ) ? $data['year'] : '';

		// Basic Bluebook format: Case Name, Court (Year)
		$parts = array();

		if ( $case_name ) {
			$parts[] = $case_name;
		}

		$paren_parts = array();
		if ( $court ) {
			$paren_parts[] = $court;
		}
		if ( $year ) {
			$paren_parts[] = $year;
		}

		if ( ! empty( $paren_parts ) ) {
			$parts[] = '(' . implode( ' ', $paren_parts ) . ')';
		}

		$citation = implode( ', ', $parts );

		return apply_filters( 'nct_citation_format_bluebook', $citation, $data );
	}

	/**
	 * Format citation in ALWD style
	 *
	 * @param array $data Citation data.
	 * @return string
	 */
	private function format_alwd( $data ) {
		// ALWD is similar to Bluebook for basic cases
		return $this->format_bluebook( $data );
	}

	/**
	 * Format citation in MLA style
	 *
	 * @param array $data Citation data.
	 * @return string
	 */
	private function format_mla( $data ) {
		$case_name = ! empty( $data['case_name'] ) ? $data['case_name'] : '';
		$court     = ! empty( $data['court'] ) ? $data['court'] : '';
		$year      = ! empty( $data['year'] ) ? $data['year'] : '';

		// MLA format: Case Name. Court, Year.
		$parts = array();

		if ( $case_name ) {
			$parts[] = $case_name . '.';
		}
		if ( $court ) {
			$parts[] = $court . ',';
		}
		if ( $year ) {
			$parts[] = $year . '.';
		}

		$citation = implode( ' ', $parts );

		return apply_filters( 'nct_citation_format_mla', $citation, $data );
	}

	/**
	 * Format citation in APA style
	 *
	 * @param array $data Citation data.
	 * @return string
	 */
	private function format_apa( $data ) {
		$case_name = ! empty( $data['case_name'] ) ? $data['case_name'] : '';
		$year      = ! empty( $data['year'] ) ? $data['year'] : '';

		// APA format: Case Name (Year)
		$parts = array();

		if ( $case_name ) {
			$parts[] = $case_name;
		}
		if ( $year ) {
			$parts[] = '(' . $year . ')';
		}

		$citation = implode( ' ', $parts );

		return apply_filters( 'nct_citation_format_apa', $citation, $data );
	}

	/**
	 * Get citation count
	 *
	 * @return int
	 */
	public function get_count() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
	}

	/**
	 * Get citations by evidence ID
	 *
	 * @param int $evidence_id Evidence ID.
	 * @return array
	 */
	public function get_by_evidence( $evidence_id ) {
		return $this->get_all( array( 'evidence_id' => $evidence_id ) );
	}

	/**
	 * Search citations
	 *
	 * @param string $query Search query.
	 * @return array
	 */
	public function search( $query ) {
		return $this->get_all( array( 'search' => $query ) );
	}

	/**
	 * Get unique courts
	 *
	 * @return array
	 */
	public function get_unique_courts() {
		global $wpdb;

		$courts = $wpdb->get_col(
			"SELECT DISTINCT court FROM {$this->table_name} WHERE court != '' ORDER BY court ASC"
		);

		return $courts;
	}

	/**
	 * Get unique years
	 *
	 * @return array
	 */
	public function get_unique_years() {
		global $wpdb;

		$years = $wpdb->get_col(
			"SELECT DISTINCT year FROM {$this->table_name} WHERE year != '' ORDER BY year DESC"
		);

		return $years;
	}

	/**
	 * Export citations to JSON
	 *
	 * @param array $ids Citation IDs to export.
	 * @return string JSON data.
	 */
	public function export_json( $ids = array() ) {
		if ( empty( $ids ) ) {
			$citations = $this->get_all( array( 'limit' => 999999 ) );
		} else {
			$citations = array();
			foreach ( $ids as $id ) {
				$item = $this->get( $id );
				if ( $item ) {
					$citations[] = $item;
				}
			}
		}

		return wp_json_encode( $citations, JSON_PRETTY_PRINT );
	}

	/**
	 * Import citations from JSON
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
				$errors[] = 'Failed to import: ' . ( $item['case_name'] ?? 'Unknown' );
			}
		}

		return array(
			'success'  => true,
			'imported' => $imported,
			'errors'   => $errors,
		);
	}
}
