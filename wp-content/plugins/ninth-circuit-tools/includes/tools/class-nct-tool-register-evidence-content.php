<?php
/**
 * Tool: Register Evidence Card Content
 * Auto-populates template hot bar from evidence card
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Evidence Content Tool
 */
class NCT_Tool_Register_Evidence_Content {

	/**
	 * Get tool metadata
	 *
	 * @return array Tool metadata.
	 */
	public static function get_metadata() {
		return array(
			'id'          => 'register-evidence-content',
			'name'        => 'Register Evidence Content',
			'description' => 'Auto-populate template hot bar from evidence card JSON',
			'category'    => 'template',
			'icon'        => '📥',
			'params'      => array(
				'evidence_card_id' => array(
					'type'        => 'string',
					'description' => 'Evidence Card ID (UUID)',
					'required'    => true,
				),
			),
		);
	}

	/**
	 * Execute tool
	 *
	 * @param array $params Tool parameters.
	 * @return array Result.
	 */
	public static function execute( $params ) {
		if ( empty( $params['evidence_card_id'] ) ) {
			return array(
				'success' => false,
				'error'   => 'Evidence Card ID is required',
			);
		}

		// Get evidence card
		$card = NCT_Evidence_Card::get( $params['evidence_card_id'] );

		if ( ! $card ) {
			return array(
				'success' => false,
				'error'   => 'Evidence card not found',
			);
		}

		// Register content
		$result = NCT_Template_Loader::register_from_evidence_card( (array) $card );

		if ( ! $result ) {
			return array(
				'success' => false,
				'error'   => 'Failed to register content',
			);
		}

		return array(
			'success' => true,
			'message' => 'Evidence card content registered to template hot bar',
			'uids'    => $card->uids,
			'codes'   => self::get_registered_codes( $card->uids ),
		);
	}

	/**
	 * Get codes registered for UIDs
	 *
	 * @param array $uids UIDs.
	 * @return array Codes.
	 */
	private static function get_registered_codes( $uids ) {
		$codes = array();

		foreach ( $uids as $index => $uid ) {
			$codes[] = "{$uid}-E" . ( $index + 1 );
			$codes[] = "{$uid}-CL1";
			$codes[] = "{$uid}-P";
		}

		return $codes;
	}
}
