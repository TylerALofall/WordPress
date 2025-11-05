<?php
/**
 * PDF Merge Tool
 * Simple tool to merge multiple PDFs into one
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT PDF Merge Tool
 */
class NCT_Tool_PDF_Merge {

	/**
	 * Register the tool
	 */
	public static function register() {
		NCT_Micro_Tools::register_tool(
			'pdf-merge',
			array(
				'name'        => 'Merge PDFs',
				'description' => 'Combine multiple PDF files into a single document',
				'icon'        => '📑',
				'category'    => 'pdf',
				'callback'    => array( __CLASS__, 'execute' ),
				'params'      => array(
					'files'       => array(
						'type'        => 'array',
						'required'    => true,
						'description' => 'Array of file paths or URLs to merge',
					),
					'output_name' => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Output filename',
						'default'     => 'merged.pdf',
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
		$files       = isset( $params['files'] ) ? $params['files'] : array();
		$output_name = isset( $params['output_name'] ) ? sanitize_file_name( $params['output_name'] ) : 'merged.pdf';

		if ( empty( $files ) || count( $files ) < 2 ) {
			return array(
				'success' => false,
				'error'   => 'At least 2 PDF files are required to merge',
			);
		}

		// Validate files
		foreach ( $files as $file ) {
			if ( ! file_exists( $file ) ) {
				return array(
					'success' => false,
					'error'   => 'File not found: ' . basename( $file ),
				);
			}

			$mime = mime_content_type( $file );
			if ( 'application/pdf' !== $mime ) {
				return array(
					'success' => false,
					'error'   => 'Invalid file type: ' . basename( $file ) . ' (must be PDF)',
				);
			}
		}

		// Use ghostscript for merging (simple and reliable)
		$upload_dir = wp_upload_dir();
		$output_path = $upload_dir['path'] . '/' . $output_name;

		// Build ghostscript command
		$gs_command = 'gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=' . escapeshellarg( $output_path );
		foreach ( $files as $file ) {
			$gs_command .= ' ' . escapeshellarg( $file );
		}

		// Execute
		exec( $gs_command . ' 2>&1', $output, $return_var );

		if ( 0 !== $return_var ) {
			return array(
				'success' => false,
				'error'   => 'Failed to merge PDFs: ' . implode( "\n", $output ),
			);
		}

		if ( ! file_exists( $output_path ) ) {
			return array(
				'success' => false,
				'error'   => 'Output file was not created',
			);
		}

		return array(
			'success'  => true,
			'message'  => 'Successfully merged ' . count( $files ) . ' PDFs',
			'file'     => $output_name,
			'path'     => $output_path,
			'url'      => $upload_dir['url'] . '/' . $output_name,
			'size'     => size_format( filesize( $output_path ) ),
		);
	}
}
