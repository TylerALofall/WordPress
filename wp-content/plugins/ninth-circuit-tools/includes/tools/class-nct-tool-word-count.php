<?php
/**
 * Word Count Tool
 * Simple word and character counting
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Word Count Tool
 */
class NCT_Tool_Word_Count {

	/**
	 * Register the tool
	 */
	public static function register() {
		NCT_Micro_Tools::register_tool(
			'word-count',
			array(
				'name'        => 'Word Count',
				'description' => 'Count words, characters, sentences, and paragraphs',
				'icon'        => '📏',
				'category'    => 'text',
				'callback'    => array( __CLASS__, 'execute' ),
				'checkpoint'  => false,
				'params'      => array(
					'text'        => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'Text to count',
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
		$text = isset( $params['text'] ) ? $params['text'] : '';

		if ( empty( $text ) ) {
			return array(
				'success' => false,
				'error'   => 'Text is required',
			);
		}

		// Count characters
		$char_count = strlen( $text );
		$char_count_no_spaces = strlen( str_replace( array( ' ', "\t", "\n", "\r" ), '', $text ) );

		// Count words
		$word_count = str_word_count( $text );

		// Count sentences (approximation)
		$sentence_count = preg_match_all( '/[.!?]+/', $text, $matches );

		// Count paragraphs
		$paragraphs = preg_split( '/\n\s*\n/', trim( $text ) );
		$paragraph_count = count( array_filter( $paragraphs ) );

		// Count lines
		$line_count = substr_count( $text, "\n" ) + 1;

		// Average word length
		$avg_word_length = $word_count > 0 ? round( $char_count_no_spaces / $word_count, 2 ) : 0;

		// Estimated reading time (average 200 words per minute)
		$reading_time_minutes = $word_count > 0 ? ceil( $word_count / 200 ) : 0;

		return array(
			'success'              => true,
			'characters'           => $char_count,
			'characters_no_spaces' => $char_count_no_spaces,
			'words'                => $word_count,
			'sentences'            => $sentence_count,
			'paragraphs'           => $paragraph_count,
			'lines'                => $line_count,
			'avg_word_length'      => $avg_word_length,
			'reading_time_minutes' => $reading_time_minutes,
		);
	}
}
