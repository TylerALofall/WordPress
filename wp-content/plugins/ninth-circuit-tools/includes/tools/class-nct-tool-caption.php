<?php
/**
 * Caption Tool
 * Add caption placeholder to document body
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Caption Tool
 */
class NCT_Tool_Caption {

	/**
	 * Register the tool
	 */
	public static function register() {
		NCT_Micro_Tools::register_tool(
			'caption',
			array(
				'name'        => 'Add Caption',
				'description' => 'Add a caption placeholder that can be easily updated',
				'icon'        => '💬',
				'category'    => 'text',
				'callback'    => array( __CLASS__, 'execute' ),
				'params'      => array(
					'caption_id'   => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'Unique caption identifier',
					),
					'initial_text' => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Initial caption text',
						'default'     => '[Caption will be added here]',
					),
					'style'        => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Caption style: italic, bold, underline, normal',
						'default'     => 'italic',
					),
				),
			)
		);

		// Also register update caption tool
		NCT_Micro_Tools::register_tool(
			'caption-update',
			array(
				'name'        => 'Update Caption',
				'description' => 'Update an existing caption by ID',
				'icon'        => '✏️',
				'category'    => 'text',
				'callback'    => array( __CLASS__, 'update_caption' ),
				'params'      => array(
					'caption_id' => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'Caption identifier to update',
					),
					'new_text'   => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'New caption text',
					),
				),
			)
		);
	}

	/**
	 * Execute the tool - create caption
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	public static function execute( $params ) {
		$caption_id   = isset( $params['caption_id'] ) ? sanitize_key( $params['caption_id'] ) : '';
		$initial_text = isset( $params['initial_text'] ) ? sanitize_text_field( $params['initial_text'] ) : '[Caption will be added here]';
		$style        = isset( $params['style'] ) ? sanitize_text_field( $params['style'] ) : 'italic';

		if ( empty( $caption_id ) ) {
			return array(
				'success' => false,
				'error'   => 'Caption ID is required',
			);
		}

		// Store caption in database
		global $wpdb;
		$table_name = $wpdb->prefix . 'nct_captions';

		// Create table if doesn't exist
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			caption_id varchar(100) NOT NULL,
			caption_text text NOT NULL,
			style varchar(50),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY caption_id (caption_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Insert caption
		$result = $wpdb->replace(
			$table_name,
			array(
				'caption_id'   => $caption_id,
				'caption_text' => $initial_text,
				'style'        => $style,
			),
			array( '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return array(
				'success' => false,
				'error'   => 'Failed to create caption',
			);
		}

		// Generate shortcode
		$shortcode = '[nct_caption id="' . $caption_id . '"]';

		return array(
			'success'   => true,
			'message'   => 'Caption created successfully',
			'caption_id' => $caption_id,
			'text'      => $initial_text,
			'style'     => $style,
			'shortcode' => $shortcode,
			'html'      => self::render_caption( $caption_id, $initial_text, $style ),
		);
	}

	/**
	 * Update caption
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	public static function update_caption( $params ) {
		$caption_id = isset( $params['caption_id'] ) ? sanitize_key( $params['caption_id'] ) : '';
		$new_text   = isset( $params['new_text'] ) ? sanitize_text_field( $params['new_text'] ) : '';

		if ( empty( $caption_id ) ) {
			return array(
				'success' => false,
				'error'   => 'Caption ID is required',
			);
		}

		if ( empty( $new_text ) ) {
			return array(
				'success' => false,
				'error'   => 'New text is required',
			);
		}

		// Update caption in database
		global $wpdb;
		$table_name = $wpdb->prefix . 'nct_captions';

		$result = $wpdb->update(
			$table_name,
			array( 'caption_text' => $new_text ),
			array( 'caption_id' => $caption_id ),
			array( '%s' ),
			array( '%s' )
		);

		if ( false === $result ) {
			return array(
				'success' => false,
				'error'   => 'Failed to update caption',
			);
		}

		// Get updated caption
		$caption = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE caption_id = %s",
				$caption_id
			)
		);

		return array(
			'success'   => true,
			'message'   => 'Caption updated successfully',
			'caption_id' => $caption_id,
			'text'      => $new_text,
			'style'     => $caption->style,
			'html'      => self::render_caption( $caption_id, $new_text, $caption->style ),
		);
	}

	/**
	 * Render caption HTML
	 *
	 * @param string $caption_id Caption ID.
	 * @param string $text Caption text.
	 * @param string $style Style.
	 * @return string HTML.
	 */
	private static function render_caption( $caption_id, $text, $style ) {
		$style_attr = '';

		switch ( $style ) {
			case 'italic':
				$style_attr = 'font-style: italic;';
				break;
			case 'bold':
				$style_attr = 'font-weight: bold;';
				break;
			case 'underline':
				$style_attr = 'text-decoration: underline;';
				break;
		}

		return sprintf(
			'<span class="nct-caption" data-caption-id="%s" style="%s">%s</span>',
			esc_attr( $caption_id ),
			esc_attr( $style_attr ),
			esc_html( $text )
		);
	}

	/**
	 * Get all captions
	 *
	 * @return array Captions.
	 */
	public static function get_all_captions() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nct_captions';

		return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY updated_at DESC" );
	}
}
