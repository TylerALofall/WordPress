<?php
/**
 * Text Clean Tool
 * Clean up text formatting issues
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Text Clean Tool
 */
class NCT_Tool_Text_Clean {

	/**
	 * Register the tool
	 */
	public static function register() {
		NCT_Micro_Tools::register_tool(
			'text-clean',
			array(
				'name'        => 'Clean Text',
				'description' => 'Remove extra spaces, fix line breaks, clean formatting',
				'icon'        => '🧹',
				'category'    => 'text',
				'callback'    => array( __CLASS__, 'execute' ),
				'checkpoint'  => false,
				'params'      => array(
					'text'        => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'Text to clean',
					),
					'options'     => array(
						'type'        => 'array',
						'required'    => false,
						'description' => 'Cleaning options: trim, spaces, linebreaks, quotes',
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
		$text    = isset( $params['text'] ) ? $params['text'] : '';
		$options = isset( $params['options'] ) ? $params['options'] : array( 'trim', 'spaces', 'linebreaks' );

		if ( empty( $text ) ) {
			return array(
				'success' => false,
				'error'   => 'Text is required',
			);
		}

		$original_length = strlen( $text );
		$cleaned = $text;

		// Trim whitespace
		if ( in_array( 'trim', $options, true ) ) {
			$cleaned = trim( $cleaned );
		}

		// Fix multiple spaces
		if ( in_array( 'spaces', $options, true ) ) {
			$cleaned = preg_replace( '/[ \t]+/', ' ', $cleaned );
		}

		// Fix line breaks
		if ( in_array( 'linebreaks', $options, true ) ) {
			// Remove multiple line breaks
			$cleaned = preg_replace( '/\n{3,}/', "\n\n", $cleaned );
			// Remove trailing spaces on lines
			$cleaned = preg_replace( '/[ \t]+$/m', '', $cleaned );
		}

		// Fix quotes
		if ( in_array( 'quotes', $options, true ) ) {
			// Convert smart quotes to regular quotes
			$cleaned = str_replace( array( '"', '"', ''', ''' ), array( '"', '"', "'", "'" ), $cleaned );
		}

		// Remove zero-width spaces and other invisible characters
		if ( in_array( 'invisible', $options, true ) ) {
			$cleaned = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $cleaned );
		}

		$new_length = strlen( $cleaned );
		$saved      = $original_length - $new_length;

		return array(
			'success'         => true,
			'message'         => 'Text cleaned successfully',
			'original_length' => $original_length,
			'new_length'      => $new_length,
			'saved'           => $saved,
			'cleaned_text'    => $cleaned,
			'options_applied' => $options,
		);
	}
}
