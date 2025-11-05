<?php
/**
 * PDF Split Tool
 * Simple tool to split a PDF into separate pages or ranges
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT PDF Split Tool
 */
class NCT_Tool_PDF_Split {

	/**
	 * Register the tool
	 */
	public static function register() {
		NCT_Micro_Tools::register_tool(
			'pdf-split',
			array(
				'name'        => 'Split PDF',
				'description' => 'Split a PDF into separate files by page or range',
				'icon'        => '✂️',
				'category'    => 'pdf',
				'callback'    => array( __CLASS__, 'execute' ),
				'params'      => array(
					'file'        => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'Path to PDF file',
					),
					'mode'        => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Split mode: all (each page), range (specific pages)',
						'default'     => 'all',
					),
					'pages'       => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Page range (e.g., "1-3,5,7-9")',
					),
				),
			)
		);
	}

	/**
	 * Execute the tool
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	public static function execute( $params ) {
		$file  = isset( $params['file'] ) ? $params['file'] : '';
		$mode  = isset( $params['mode'] ) ? $params['mode'] : 'all';
		$pages = isset( $params['pages'] ) ? $params['pages'] : '';

		if ( empty( $file ) || ! file_exists( $file ) ) {
			return array(
				'success' => false,
				'error'   => 'File not found',
			);
		}

		$mime = mime_content_type( $file );
		if ( 'application/pdf' !== $mime ) {
			return array(
				'success' => false,
				'error'   => 'Invalid file type (must be PDF)',
			);
		}

		// Get page count
		$page_count = self::get_page_count( $file );
		if ( ! $page_count ) {
			return array(
				'success' => false,
				'error'   => 'Could not determine page count',
			);
		}

		$upload_dir = wp_upload_dir();
		$basename   = pathinfo( $file, PATHINFO_FILENAME );
		$output_files = array();

		if ( 'all' === $mode ) {
			// Split each page
			for ( $i = 1; $i <= $page_count; $i++ ) {
				$output_path = $upload_dir['path'] . '/' . $basename . '-page-' . $i . '.pdf';

				$gs_command = sprintf(
					'gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dFirstPage=%d -dLastPage=%d -sOutputFile=%s %s 2>&1',
					$i,
					$i,
					escapeshellarg( $output_path ),
					escapeshellarg( $file )
				);

				exec( $gs_command, $output, $return_var );

				if ( 0 === $return_var && file_exists( $output_path ) ) {
					$output_files[] = array(
						'page'     => $i,
						'filename' => basename( $output_path ),
						'path'     => $output_path,
						'url'      => $upload_dir['url'] . '/' . basename( $output_path ),
					);
				}
			}
		} else {
			// Split by range
			$page_ranges = self::parse_page_ranges( $pages, $page_count );

			foreach ( $page_ranges as $range ) {
				$output_name = $basename . '-pages-' . $range['start'] . '-' . $range['end'] . '.pdf';
				$output_path = $upload_dir['path'] . '/' . $output_name;

				$gs_command = sprintf(
					'gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dFirstPage=%d -dLastPage=%d -sOutputFile=%s %s 2>&1',
					$range['start'],
					$range['end'],
					escapeshellarg( $output_path ),
					escapeshellarg( $file )
				);

				exec( $gs_command, $output, $return_var );

				if ( 0 === $return_var && file_exists( $output_path ) ) {
					$output_files[] = array(
						'pages'    => $range['start'] . '-' . $range['end'],
						'filename' => $output_name,
						'path'     => $output_path,
						'url'      => $upload_dir['url'] . '/' . $output_name,
					);
				}
			}
		}

		if ( empty( $output_files ) ) {
			return array(
				'success' => false,
				'error'   => 'No files were created',
			);
		}

		return array(
			'success' => true,
			'message' => 'Successfully split PDF into ' . count( $output_files ) . ' file(s)',
			'files'   => $output_files,
		);
	}

	/**
	 * Get page count of PDF
	 *
	 * @param string $file File path.
	 * @return int|false Page count or false.
	 */
	private static function get_page_count( $file ) {
		$output = shell_exec( 'pdfinfo ' . escapeshellarg( $file ) . ' | grep Pages | awk \'{print $2}\'' );

		if ( $output ) {
			return (int) trim( $output );
		}

		// Fallback: count using ghostscript
		$output = shell_exec( 'gs -q -dNODISPLAY -c "(' . $file . ') (r) file runpdfbegin pdfpagecount = quit"' );

		return $output ? (int) trim( $output ) : false;
	}

	/**
	 * Parse page ranges
	 *
	 * @param string $ranges Range string (e.g., "1-3,5,7-9").
	 * @param int    $max_pages Maximum pages.
	 * @return array Parsed ranges.
	 */
	private static function parse_page_ranges( $ranges, $max_pages ) {
		$parsed = array();
		$parts  = explode( ',', $ranges );

		foreach ( $parts as $part ) {
			$part = trim( $part );

			if ( strpos( $part, '-' ) !== false ) {
				// Range
				list( $start, $end ) = explode( '-', $part );
				$start = max( 1, min( (int) $start, $max_pages ) );
				$end   = max( 1, min( (int) $end, $max_pages ) );

				$parsed[] = array(
					'start' => $start,
					'end'   => $end,
				);
			} else {
				// Single page
				$page = max( 1, min( (int) $part, $max_pages ) );
				$parsed[] = array(
					'start' => $page,
					'end'   => $page,
				);
			}
		}

		return $parsed;
	}
}
