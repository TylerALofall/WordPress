<?php
/**
 * PDF Page Numbers Tool
 * Add page numbers to bottom center of PDF pages
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT PDF Page Numbers Tool
 */
class NCT_Tool_PDF_Page_Numbers {

	/**
	 * Register the tool
	 */
	public static function register() {
		NCT_Micro_Tools::register_tool(
			'pdf-page-numbers',
			array(
				'name'        => 'Add Page Numbers',
				'description' => 'Add page numbers to bottom center of PDF pages',
				'icon'        => '🔢',
				'category'    => 'pdf',
				'callback'    => array( __CLASS__, 'execute' ),
				'params'      => array(
					'file'        => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'Path to PDF file',
					),
					'start_number' => array(
						'type'        => 'integer',
						'required'    => false,
						'description' => 'Starting page number',
						'default'     => 1,
					),
					'format'      => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Number format: arabic (1,2,3), roman (i,ii,iii), roman-upper (I,II,III)',
						'default'     => 'arabic',
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
		$file         = isset( $params['file'] ) ? $params['file'] : '';
		$start_number = isset( $params['start_number'] ) ? (int) $params['start_number'] : 1;
		$format       = isset( $params['format'] ) ? $params['format'] : 'arabic';

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

		$upload_dir  = wp_upload_dir();
		$basename    = pathinfo( $file, PATHINFO_FILENAME );
		$output_name = $basename . '-numbered.pdf';
		$output_path = $upload_dir['path'] . '/' . $output_name;

		// Create a simple PostScript file to add page numbers
		$ps_file = $upload_dir['path'] . '/page-numbers-' . time() . '.ps';
		$ps_content = self::generate_page_number_ps( $page_count, $start_number, $format );

		file_put_contents( $ps_file, $ps_content );

		// Use pdftk or gs to add page numbers
		$gs_command = sprintf(
			'gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s %s 2>&1',
			escapeshellarg( $output_path ),
			escapeshellarg( $file ),
			escapeshellarg( $ps_file )
		);

		exec( $gs_command, $output, $return_var );

		// Clean up temp PS file
		if ( file_exists( $ps_file ) ) {
			unlink( $ps_file );
		}

		if ( 0 !== $return_var || ! file_exists( $output_path ) ) {
			return array(
				'success' => false,
				'error'   => 'Failed to add page numbers',
			);
		}

		return array(
			'success'  => true,
			'message'  => 'Successfully added page numbers to ' . $page_count . ' pages',
			'file'     => $output_name,
			'path'     => $output_path,
			'url'      => $upload_dir['url'] . '/' . $output_name,
			'size'     => size_format( filesize( $output_path ) ),
		);
	}

	/**
	 * Get page count
	 *
	 * @param string $file File path.
	 * @return int|false
	 */
	private static function get_page_count( $file ) {
		$output = shell_exec( 'pdfinfo ' . escapeshellarg( $file ) . ' | grep Pages | awk \'{print $2}\'' );

		return $output ? (int) trim( $output ) : false;
	}

	/**
	 * Generate PostScript for page numbers
	 *
	 * @param int    $page_count Page count.
	 * @param int    $start_number Starting number.
	 * @param string $format Format type.
	 * @return string PostScript content.
	 */
	private static function generate_page_number_ps( $page_count, $start_number, $format ) {
		$ps = "%!PS\n";

		for ( $i = 1; $i <= $page_count; $i++ ) {
			$page_num = $start_number + $i - 1;
			$display_num = self::format_page_number( $page_num, $format );

			$ps .= "<<\n";
			$ps .= "  /EndPage {\n";
			$ps .= "    2 eq {\n";
			$ps .= "      pop true\n";
			$ps .= "    }{\n";
			$ps .= "      /Helvetica findfont 10 scalefont setfont\n";
			$ps .= "      306 30 moveto\n";
			$ps .= "      ($display_num) dup stringwidth pop 2 div neg 0 rmoveto show\n";
			$ps .= "      pop true\n";
			$ps .= "    } ifelse\n";
			$ps .= "  } bind\n";
			$ps .= ">> setpagedevice\n";
		}

		return $ps;
	}

	/**
	 * Format page number
	 *
	 * @param int    $number Page number.
	 * @param string $format Format type.
	 * @return string Formatted number.
	 */
	private static function format_page_number( $number, $format ) {
		switch ( $format ) {
			case 'roman':
				return strtolower( self::int_to_roman( $number ) );

			case 'roman-upper':
				return self::int_to_roman( $number );

			case 'arabic':
			default:
				return (string) $number;
		}
	}

	/**
	 * Convert integer to Roman numeral
	 *
	 * @param int $num Number.
	 * @return string Roman numeral.
	 */
	private static function int_to_roman( $num ) {
		$map = array(
			'M'  => 1000,
			'CM' => 900,
			'D'  => 500,
			'CD' => 400,
			'C'  => 100,
			'XC' => 90,
			'L'  => 50,
			'XL' => 40,
			'X'  => 10,
			'IX' => 9,
			'V'  => 5,
			'IV' => 4,
			'I'  => 1,
		);

		$result = '';

		foreach ( $map as $roman => $value ) {
			while ( $num >= $value ) {
				$result .= $roman;
				$num    -= $value;
			}
		}

		return $result;
	}
}
