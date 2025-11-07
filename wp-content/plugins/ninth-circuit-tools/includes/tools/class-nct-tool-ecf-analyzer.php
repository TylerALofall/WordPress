<?php
/**
 * ECF Contradiction Analyzer Tool
 * Finds negative statements in ECF documents and matches to defendant claims
 *
 * @package NinthCircuitTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NCT_Tool_ECF_Analyzer {

	public static function register() {
		$metadata = self::get_metadata();
		NCT_Micro_Tools::register_tool(
			$metadata['id'],
			array(
				'name'        => $metadata['name'],
				'description' => $metadata['description'],
				'icon'        => $metadata['icon'],
				'category'    => $metadata['category'],
				'params'      => $metadata['params'],
				'callback'    => array( __CLASS__, 'execute' ),
			)
		);
	}

	public static function get_metadata() {
		return array(
			'id'          => 'ecf-analyzer',
			'name'        => 'ECF Contradiction Finder',
			'description' => 'Find negative statements in ECF and prove defendants wrong',
			'category'    => 'document',
			'icon'        => '🔍',
			'params'      => array(
				'ecf_number'  => array(
					'type'        => 'string',
					'description' => 'ECF document number (e.g., 60)',
					'required'    => true,
				),
				'search_type' => array(
					'type'        => 'select',
					'description' => 'Type of negative statements',
					'options'     => array(
						'factual_errors'      => 'Factual Errors',
						'legal_misstatements' => 'Legal Misstatements',
						'procedural_games'    => 'Procedural Manipulation',
					),
					'default'     => 'procedural_games',
					'required'    => true,
				),
			),
		);
	}

	public static function execute( $params ) {
		$ecf_number  = sanitize_text_field( $params['ecf_number'] );
		$search_type = sanitize_text_field( $params['search_type'] );

		$ecf_content = self::load_ecf_document( $ecf_number );

		if ( is_wp_error( $ecf_content ) ) {
			return array(
				'success' => false,
				'error'   => $ecf_content->get_error_message(),
			);
		}

		$negative_statements  = self::extract_negative_statements( $ecf_content, $search_type );
		$defendant_statements = self::get_defendant_statements();
		$contradictions       = self::find_contradictions( $negative_statements, $defendant_statements, $ecf_number );

		return array(
			'success'        => true,
			'ecf_number'     => $ecf_number,
			'search_type'    => $search_type,
			'negative_count' => count( $negative_statements ),
			'matches_found'  => count( $contradictions ),
			'contradictions' => $contradictions,
			'report'         => self::generate_report( $contradictions, $ecf_number ),
		);
	}

	private static function load_ecf_document( $ecf_number ) {
		global $wpdb;

		$attachment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID, guid FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_title LIKE %s",
				'%ECF%' . $ecf_number . '%'
			)
		);

		if ( ! $attachment ) {
			return new WP_Error( 'not_found', 'ECF document not found. Upload it to Media Library first.' );
		}

		$file_path = get_attached_file( $attachment->ID );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', 'ECF file not found on server' );
		}

		if ( pathinfo( $file_path, PATHINFO_EXTENSION ) === 'pdf' ) {
			return self::extract_pdf_text( $file_path );
		}

		return file_get_contents( $file_path );
	}

	private static function extract_pdf_text( $file_path ) {
		if ( class_exists( 'Smalot\PdfParser\Parser' ) ) {
			$parser = new \Smalot\PdfParser\Parser();
			$pdf    = $parser->parseFile( $file_path );
			return $pdf->getText();
		}

		exec( "pdftotext " . escapeshellarg( $file_path ) . " -", $output, $return_code );

		if ( $return_code === 0 ) {
			return implode( "\n", $output );
		}

		return new WP_Error( 'pdf_error', 'Cannot extract text from PDF. Install pdftotext or pdf-parser library.' );
	}

	private static function extract_negative_statements( $content, $type ) {
		$patterns = array(
			'factual_errors'      => array(
				'/plaintiff\s+(failed\s+to|did\s+not|never|lacks|cannot)/i',
				'/no\s+evidence\s+(of|that|to\s+support)/i',
				'/unsupported\s+by/i',
			),
			'legal_misstatements' => array(
				'/no\s+cause\s+of\s+action/i',
				'/fails\s+to\s+state/i',
				'/insufficient\s+(evidence|facts|pleading)/i',
				'/not\s+cognizable/i',
			),
			'procedural_games'    => array(
				'/untimely/i',
				'/procedurally\s+barred/i',
				'/waived/i',
				'/statute\s+of\s+limitations/i',
				'/exhausted\s+remedies/i',
			),
		);

		$pattern_list = isset( $patterns[ $type ] ) ? $patterns[ $type ] : $patterns['procedural_games'];
		$statements   = array();

		foreach ( $pattern_list as $pattern ) {
			preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE );

			foreach ( $matches[0] as $match ) {
				$statements[] = array(
					'text'     => $match[0],
					'position' => $match[1],
					'context'  => self::get_surrounding_context( $content, $match[1], 200 ),
					'pattern'  => $pattern,
				);
			}
		}

		return $statements;
	}

	private static function get_surrounding_context( $content, $position, $length = 200 ) {
		$start = max( 0, $position - $length );
		$end   = min( strlen( $content ), $position + $length );
		return substr( $content, $start, $end - $start );
	}

	private static function get_defendant_statements() {
		$cards      = NCT_Evidence_Card::get_all( array( 'limit' => 999 ) );
		$statements = array();

		foreach ( $cards as $card ) {
			$statements[] = array(
				'uid'         => implode( ', ', $card->uids ),
				'claim'       => $card->claim,
				'description' => $card->description,
				'source'      => $card->source,
				'text'        => $card->claim . ' ' . $card->description . ' ' . $card->significance,
			);
		}

		return $statements;
	}

	private static function find_contradictions( $negative_statements, $defendant_statements, $ecf_number ) {
		$contradictions = array();

		foreach ( $negative_statements as $negative ) {
			foreach ( $defendant_statements as $defendant ) {
				$similarity = self::calculate_text_similarity( $negative['context'], $defendant['text'] );

				if ( $similarity > 0.3 ) {
					$contradictions[] = array(
						'defendant_uid'     => $defendant['uid'],
						'defendant_claim'   => $defendant['claim'],
						'defendant_source'  => $defendant['source'],
						'ecf_states'        => $negative['context'],
						'ecf_number'        => $ecf_number,
						'similarity'        => $similarity,
						'hot_bar_code'      => $defendant['uid'] . '-DP',
						'proof'             => self::build_proof_statement( $defendant, $negative, $ecf_number ),
					);
				}
			}
		}

		usort(
			$contradictions,
			function( $a, $b ) {
				return $b['similarity'] <=> $a['similarity'];
			}
		);

		return array_slice( $contradictions, 0, 10 );
	}

	private static function calculate_text_similarity( $text1, $text2 ) {
		$text1 = strtolower( preg_replace( '/[^a-z0-9\s]/i', '', $text1 ) );
		$text2 = strtolower( preg_replace( '/[^a-z0-9\s]/i', '', $text2 ) );

		$words1 = array_unique( explode( ' ', $text1 ) );
		$words2 = array_unique( explode( ' ', $text2 ) );

		$common = count( array_intersect( $words1, $words2 ) );
		$total  = count( array_unique( array_merge( $words1, $words2 ) ) );

		return $total > 0 ? $common / $total : 0;
	}

	private static function build_proof_statement( $defendant, $negative, $ecf_number ) {
		return sprintf(
			"CONTRADICTION:\n\n" .
			"Defendant's Evidence (UID %s):\n'%s'\n\n" .
			"ECF %s States:\n'%s'\n\n" .
			"Analysis: Defendant's claim is contradicted by ECF %s. Load Hot Bar code '%s' for full defendant position.",
			$defendant['uid'],
			substr( $defendant['claim'], 0, 200 ),
			$ecf_number,
			substr( $negative['context'], 0, 200 ),
			$ecf_number,
			$defendant['uid'] . '-DP'
		);
	}

	private static function generate_report( $contradictions, $ecf_number ) {
		if ( empty( $contradictions ) ) {
			return "No contradictions found in ECF {$ecf_number}.";
		}

		$report = "ECF {$ecf_number} CONTRADICTION REPORT\n";
		$report .= "Found " . count( $contradictions ) . " potential contradictions:\n\n";
		$report .= str_repeat( '=', 80 ) . "\n\n";

		foreach ( $contradictions as $i => $contradiction ) {
			$report .= sprintf(
				"#%d - UID %s (Similarity: %.1f%%)\n",
				$i + 1,
				$contradiction['defendant_uid'],
				$contradiction['similarity'] * 100
			);
			$report .= str_repeat( '-', 80 ) . "\n";
			$report .= $contradiction['proof'] . "\n\n";
			$report .= "Hot Bar Code: " . $contradiction['hot_bar_code'] . "\n";
			$report .= str_repeat( '=', 80 ) . "\n\n";
		}

		return $report;
	}
}
