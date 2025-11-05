<?php
/**
 * Outline Builder for Ninth Circuit Tools
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Outline Builder Class
 */
class NCT_Outline_Builder {

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
		$this->table_name = $wpdb->prefix . 'nct_outlines';
	}

	/**
	 * Create new outline
	 *
	 * @param array $data Outline data.
	 * @return int|false Outline ID or false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'title'        => '',
			'data'         => array(),
			'rules_config' => array(),
			'created_by'   => get_current_user_id(),
		);

		$data = wp_parse_args( $data, $defaults );

		// Encode arrays as JSON
		$outline_data  = is_array( $data['data'] ) ? wp_json_encode( $data['data'] ) : $data['data'];
		$rules_config  = is_array( $data['rules_config'] ) ? wp_json_encode( $data['rules_config'] ) : $data['rules_config'];

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'title'        => $data['title'],
				'outline_data' => $outline_data,
				'rules_config' => $rules_config,
				'created_by'   => $data['created_by'],
			),
			array(
				'%s',
				'%s',
				'%s',
				'%d',
			)
		);

		if ( $result ) {
			do_action( 'nct_outline_created', $wpdb->insert_id, $data );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get outline by ID
	 *
	 * @param int $id Outline ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;

		$outline = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$id
			)
		);

		if ( $outline ) {
			$outline->outline_data_decoded = json_decode( $outline->outline_data, true );
			$outline->rules_config_decoded = json_decode( $outline->rules_config, true );
		}

		return $outline;
	}

	/**
	 * Get all outlines
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'    => 50,
			'offset'   => 0,
			'order_by' => 'created_at',
			'order'    => 'DESC',
			'search'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		// Search
		if ( ! empty( $args['search'] ) ) {
			$where[] = 'title LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_clause = implode( ' AND ', $where );

		$query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$args['order_by']} {$args['order']} LIMIT %d OFFSET %d";

		$where_values[] = $args['limit'];
		$where_values[] = $args['offset'];

		$outlines = $wpdb->get_results(
			$wpdb->prepare( $query, $where_values )
		);

		// Decode JSON for each outline
		foreach ( $outlines as $outline ) {
			$outline->outline_data_decoded = json_decode( $outline->outline_data, true );
			$outline->rules_config_decoded = json_decode( $outline->rules_config, true );
		}

		return $outlines;
	}

	/**
	 * Update outline
	 *
	 * @param int   $id Outline ID.
	 * @param array $data Outline data.
	 * @return bool
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$update_data = array();
		$format = array();

		if ( isset( $data['title'] ) ) {
			$update_data['title'] = $data['title'];
			$format[] = '%s';
		}

		if ( isset( $data['data'] ) ) {
			$update_data['outline_data'] = is_array( $data['data'] ) ? wp_json_encode( $data['data'] ) : $data['data'];
			$format[] = '%s';
		}

		if ( isset( $data['rules_config'] ) ) {
			$update_data['rules_config'] = is_array( $data['rules_config'] ) ? wp_json_encode( $data['rules_config'] ) : $data['rules_config'];
			$format[] = '%s';
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
			do_action( 'nct_outline_updated', $id, $data );
			return true;
		}

		return false;
	}

	/**
	 * Delete outline
	 *
	 * @param int $id Outline ID.
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
			do_action( 'nct_outline_deleted', $id );
			return true;
		}

		return false;
	}

	/**
	 * Build outline from key points using rule-based approach
	 *
	 * @param array $key_points Key points for the outline.
	 * @param array $rules Optional rules configuration.
	 * @return string HTML output of the outline.
	 */
	public function build_from_keypoints( $key_points, $rules = array() ) {
		if ( empty( $key_points ) ) {
			return '<div style="color: var(--nct-text-secondary);">No key points provided.</div>';
		}

		$defaults = array(
			'autopopulate'     => true,
			'hierarchy'        => 'auto',
			'numbering_style'  => 'legal',
			'include_evidence' => true,
			'include_citations' => true,
		);

		$rules = wp_parse_args( $rules, $defaults );

		// Process key points
		$structured_outline = $this->structure_keypoints( $key_points, $rules );

		// Auto-populate with evidence and citations if enabled
		if ( $rules['autopopulate'] ) {
			$structured_outline = $this->auto_populate( $structured_outline, $rules );
		}

		// Generate HTML
		$html = $this->generate_outline_html( $structured_outline, $rules );

		return $html;
	}

	/**
	 * Structure key points into hierarchical outline
	 *
	 * @param array $key_points Key points.
	 * @param array $rules Rules configuration.
	 * @return array Structured outline.
	 */
	private function structure_keypoints( $key_points, $rules ) {
		$structured = array();

		foreach ( $key_points as $index => $point ) {
			$point = trim( $point );
			if ( empty( $point ) ) {
				continue;
			}

			// Detect hierarchy based on indentation or numbering
			$level = $this->detect_hierarchy_level( $point );

			// Extract the actual text without any numbering
			$text = $this->extract_text( $point );

			// Apply rules to categorize and enhance the point
			$category = $this->categorize_point( $text );

			$structured[] = array(
				'level'    => $level,
				'text'     => $text,
				'category' => $category,
				'original' => $point,
				'index'    => $index,
				'evidence' => array(),
				'citations' => array(),
			);
		}

		return $structured;
	}

	/**
	 * Detect hierarchy level from text
	 *
	 * @param string $text Text to analyze.
	 * @return int Level (1-4).
	 */
	private function detect_hierarchy_level( $text ) {
		// Count leading spaces/tabs
		$indent = 0;
		$length = strlen( $text );

		for ( $i = 0; $i < $length; $i++ ) {
			if ( $text[ $i ] === ' ' ) {
				$indent++;
			} elseif ( $text[ $i ] === "\t" ) {
				$indent += 4;
			} else {
				break;
			}
		}

		// Convert indent to level (every 4 spaces = 1 level)
		$level = min( 4, max( 1, floor( $indent / 4 ) + 1 ) );

		// Check for Roman numerals, letters, or numbers at start
		$trimmed = ltrim( $text );
		if ( preg_match( '/^(I{1,3}|IV|V|VI{0,3}|IX|X)\.?\s/i', $trimmed ) ) {
			return 1;
		}
		if ( preg_match( '/^[A-Z]\.?\s/', $trimmed ) ) {
			return 2;
		}
		if ( preg_match( '/^[0-9]+\.?\s/', $trimmed ) ) {
			return 3;
		}
		if ( preg_match( '/^[a-z]\.?\s/', $trimmed ) ) {
			return 4;
		}

		return $level;
	}

	/**
	 * Extract clean text from point
	 *
	 * @param string $text Text with possible numbering.
	 * @return string Clean text.
	 */
	private function extract_text( $text ) {
		$text = trim( $text );

		// Remove leading numbering/bullets
		$text = preg_replace( '/^(I{1,3}|IV|V|VI{0,3}|IX|X)\.?\s+/i', '', $text );
		$text = preg_replace( '/^[A-Za-z0-9]+\.?\s+/', '', $text );
		$text = preg_replace( '/^[-•*]\s+/', '', $text );

		return trim( $text );
	}

	/**
	 * Categorize a point based on keywords
	 *
	 * @param string $text Point text.
	 * @return string Category.
	 */
	private function categorize_point( $text ) {
		$text_lower = strtolower( $text );

		$categories = array(
			'issue'      => array( 'issue', 'question', 'whether' ),
			'rule'       => array( 'rule', 'law', 'statute', 'regulation', 'standard' ),
			'analysis'   => array( 'analysis', 'because', 'since', 'therefore', 'thus' ),
			'application' => array( 'apply', 'application', 'here', 'in this case' ),
			'conclusion' => array( 'conclude', 'conclusion', 'therefore', 'result' ),
			'fact'       => array( 'fact', 'evidence', 'occurred', 'happened' ),
			'argument'   => array( 'argue', 'argument', 'contend', 'assert' ),
		);

		foreach ( $categories as $category => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $text_lower, $keyword ) !== false ) {
					return $category;
				}
			}
		}

		return 'general';
	}

	/**
	 * Auto-populate outline with related evidence and citations
	 *
	 * @param array $structured Structured outline.
	 * @param array $rules Rules configuration.
	 * @return array Enhanced outline.
	 */
	private function auto_populate( $structured, $rules ) {
		if ( empty( $structured ) ) {
			return $structured;
		}

		// Get evidence and citations
		$evidence_collector = new NCT_Evidence_Collector();
		$citation_manager   = new NCT_Citation_Manager();

		foreach ( $structured as &$point ) {
			// Search for related evidence
			if ( $rules['include_evidence'] ) {
				$evidence_results = $evidence_collector->search( $point['text'] );
				if ( ! empty( $evidence_results ) ) {
					$point['evidence'] = array_slice( $evidence_results, 0, 3 ); // Limit to 3
				}
			}

			// Search for related citations
			if ( $rules['include_citations'] ) {
				$citation_results = $citation_manager->search( $point['text'] );
				if ( ! empty( $citation_results ) ) {
					$point['citations'] = array_slice( $citation_results, 0, 3 ); // Limit to 3
				}
			}
		}

		return $structured;
	}

	/**
	 * Generate HTML from structured outline
	 *
	 * @param array  $structured Structured outline.
	 * @param array  $rules Rules configuration.
	 * @return string HTML output.
	 */
	private function generate_outline_html( $structured, $rules ) {
		if ( empty( $structured ) ) {
			return '<div style="color: var(--nct-text-secondary);">Empty outline.</div>';
		}

		$html = '<div class="nct-outline" style="color: var(--nct-text-primary); line-height: 1.8;">';

		$counters = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0 );

		foreach ( $structured as $point ) {
			$level = $point['level'];
			$counters[ $level ]++;

			// Reset sub-levels
			for ( $i = $level + 1; $i <= 4; $i++ ) {
				$counters[ $i ] = 0;
			}

			// Generate numbering
			$numbering = $this->generate_numbering( $counters, $level, $rules['numbering_style'] );

			// Indent based on level
			$indent = ( $level - 1 ) * 24;

			// Category color
			$category_color = $this->get_category_color( $point['category'] );

			$html .= sprintf(
				'<div style="margin-left: %dpx; margin-bottom: 12px;">',
				$indent
			);

			$html .= sprintf(
				'<div style="font-weight: %s; font-size: %dpx;"><span style="color: %s;">%s</span> %s</div>',
				$level <= 2 ? '600' : '400',
				18 - ( $level * 1 ),
				$category_color,
				$numbering,
				esc_html( $point['text'] )
			);

			// Add evidence if available
			if ( ! empty( $point['evidence'] ) ) {
				$html .= '<div style="margin-top: 6px; margin-left: 20px; font-size: 13px; color: var(--nct-text-secondary);">';
				$html .= '<strong>📋 Evidence:</strong> ';
				$evidence_titles = array_map(
					function( $e ) {
						return esc_html( $e->title );
					},
					$point['evidence']
				);
				$html .= implode( ', ', $evidence_titles );
				$html .= '</div>';
			}

			// Add citations if available
			if ( ! empty( $point['citations'] ) ) {
				$html .= '<div style="margin-top: 4px; margin-left: 20px; font-size: 13px; color: var(--nct-text-secondary);">';
				$html .= '<strong>📚 Citations:</strong> ';
				$citation_texts = array_map(
					function( $c ) {
						return esc_html( $c->case_name );
					},
					$point['citations']
				);
				$html .= implode( ', ', $citation_texts );
				$html .= '</div>';
			}

			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Generate numbering based on style
	 *
	 * @param array  $counters Current counters.
	 * @param int    $level Current level.
	 * @param string $style Numbering style.
	 * @return string Numbering.
	 */
	private function generate_numbering( $counters, $level, $style ) {
		if ( 'legal' === $style ) {
			switch ( $level ) {
				case 1:
					return $this->int_to_roman( $counters[1] ) . '.';
				case 2:
					return chr( 64 + $counters[2] ) . '.';
				case 3:
					return $counters[3] . '.';
				case 4:
					return chr( 96 + $counters[4] ) . '.';
			}
		}

		// Default numeric
		return $counters[ $level ] . '.';
	}

	/**
	 * Convert integer to Roman numeral
	 *
	 * @param int $num Number.
	 * @return string Roman numeral.
	 */
	private function int_to_roman( $num ) {
		$map = array(
			'X' => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1,
		);

		$result = '';

		foreach ( $map as $roman => $value ) {
			while ( $num >= $value ) {
				$result .= $roman;
				$num -= $value;
			}
		}

		return $result;
	}

	/**
	 * Get color for category
	 *
	 * @param string $category Category.
	 * @return string Color.
	 */
	private function get_category_color( $category ) {
		$colors = array(
			'issue'       => 'rgba(59, 130, 246, 0.9)',
			'rule'        => 'rgba(139, 92, 246, 0.9)',
			'analysis'    => 'rgba(16, 185, 129, 0.9)',
			'application' => 'rgba(245, 158, 11, 0.9)',
			'conclusion'  => 'rgba(239, 68, 68, 0.9)',
			'fact'        => 'rgba(236, 72, 153, 0.9)',
			'argument'    => 'rgba(6, 182, 212, 0.9)',
			'general'     => 'var(--nct-text-primary)',
		);

		return isset( $colors[ $category ] ) ? $colors[ $category ] : $colors['general'];
	}

	/**
	 * Get outline count
	 *
	 * @return int
	 */
	public function get_count() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
	}
}
