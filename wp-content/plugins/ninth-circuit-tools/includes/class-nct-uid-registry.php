<?php
/**
 * UID Registry System
 * Master registry of all UIDs for Tyler's case
 *
 * UID Format: [Claim][Element][Defendant]
 * Example: 933 = Claim 9 (Monell), Element 30 (moving force), Defendant 3 (City)
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT UID Registry Class
 */
class NCT_UID_Registry {

	/**
	 * Causes of Action (Claims)
	 */
	private static $causes_of_action = array(
		1  => 'Unlawful Arrest/False Arrest (Fourth Amendment)',
		2  => 'Malicious Prosecution (Fourth & Fourteenth Amendments)',
		3  => 'Conspiracy to Violate Civil Rights (42 U.S.C. § 1983)',
		4  => 'Failure to Protect/Prevent Harm (Fourteenth Amendment)',
		5  => 'Cruel and Unusual Punishment (Fourteenth Amendment)',
		6  => 'False Imprisonment (Fourth & Fourteenth Amendments)',
		7  => 'Deliberate Indifference to Medical Needs (Fourteenth Amendment)',
		8  => 'Intentional Interference with Contractual Relations',
		9  => 'Monell Claim – Failure to Train and Official Custom',
		10 => 'Denial of Right to Civil Jury Trial (Seventh Amendment)',
		11 => 'Violation of Right to Due Process (Fifth & Fourteenth Amendments)',
		12 => 'Violation of Right to Counsel and Fair Trial (Sixth Amendment)',
		13 => 'Probable Cause',
		14 => 'Qualified and Absolute Immunity (42 U.S.C. § 1983)',
	);

	/**
	 * Defendants
	 */
	private static $defendants = array(
		1 => 'Officer Dana Gunnarson',
		2 => 'Officer Catlin Blyth',
		3 => 'City of West Linn',
		4 => 'DDA Rebecca Portlock',
		5 => 'Clackamas County Sheriff\'s Department',
		6 => 'Clackamas County Jail',
		7 => 'County of Clackamas',
		8 => 'John Doe 1',
		9 => 'John Doe 2',
	);

	/**
	 * Elements per Cause of Action
	 * Maps claim number to its elements (10, 20, 30, 40...)
	 */
	private static $elements = array(
		1  => array( 10 => 'Acted under color of law', 20 => 'Arrested or detained', 30 => 'Without probable cause', 40 => 'Deprived of constitutional rights', 50 => 'Negative law exception' ),
		2  => array( 10 => 'Initiated/continued proceeding', 20 => 'Terminated in favor', 30 => 'No probable cause', 40 => 'Acted with malice', 50 => 'Deprivation of liberty', 60 => 'Negative law exception' ),
		3  => array( 10 => 'Agreement existed', 20 => 'Overt acts', 30 => 'Actually deprived of rights', 40 => 'Negative law exception' ),
		4  => array( 10 => 'Duty to protect', 20 => 'Deliberate indifference', 30 => 'Suffered harm', 40 => 'Negative law exception' ),
		5  => array( 10 => 'Substantial risk of harm', 20 => 'Deliberate indifference', 30 => 'Resulted in harm', 40 => 'Negative law exception' ),
		6  => array( 10 => 'Intended to confine', 20 => 'Conscious of confinement', 30 => 'Did not consent', 40 => 'Not privileged', 50 => 'Negative law exception' ),
		7  => array( 10 => 'Serious medical need', 20 => 'Knew and disregarded risk', 30 => 'Deliberate indifference', 40 => 'Negative law exception' ),
		8  => array( 10 => 'Valid contract existed', 20 => 'Knew of contract', 30 => 'Intentionally interfered', 40 => 'Contract breached', 50 => 'Suffered damages', 60 => 'Negative law exception' ),
		9  => array( 10 => 'Official policy/custom existed', 20 => 'Deliberately indifferent', 30 => 'Moving force behind violation', 40 => 'Negative law exception' ),
		10 => array( 10 => 'Right to jury trial', 20 => 'Denied right', 30 => 'Caused harm', 40 => 'Negative law exception' ),
		11 => array( 10 => 'Protected interest', 20 => 'Deprived of interest', 30 => 'Without due process', 40 => 'Negative law exception' ),
		12 => array( 10 => 'Right to counsel/fair trial', 20 => 'Interfered/denied', 30 => 'Harmed as result', 40 => 'Negative law exception' ),
		13 => array( 10 => 'Facts within knowledge', 20 => 'Sufficient for prudent person', 30 => 'Negative law exception' ),
		14 => array( 10 => 'Discretionary authority', 20 => 'Clearly established law', 30 => 'Reasonable official would know', 40 => 'Negative law exception' ),
	);

	/**
	 * Validate a UID
	 *
	 * @param string $uid UID to validate.
	 * @return array|WP_Error Validation result or error.
	 */
	public static function validate_uid( $uid ) {
		// UID must be 3 digits
		if ( ! preg_match( '/^\d{3}$/', $uid ) ) {
			return new WP_Error( 'invalid_format', 'UID must be exactly 3 digits' );
		}

		$claim_num     = (int) substr( $uid, 0, 1 );
		$element_num   = (int) substr( $uid, 1, 1 ) * 10;
		$defendant_num = (int) substr( $uid, 2, 1 );

		// Validate claim
		if ( ! isset( self::$causes_of_action[ $claim_num ] ) ) {
			return new WP_Error( 'invalid_claim', 'Invalid claim number: ' . $claim_num );
		}

		// Validate element
		if ( ! isset( self::$elements[ $claim_num ][ $element_num ] ) ) {
			return new WP_Error( 'invalid_element', 'Invalid element ' . $element_num . ' for claim ' . $claim_num );
		}

		// Validate defendant (0 is allowed for group actions)
		if ( $defendant_num > 9 ) {
			return new WP_Error( 'invalid_defendant', 'Invalid defendant number: ' . $defendant_num );
		}

		// Check for logical inconsistencies (e.g., Monell against individual)
		if ( 9 === $claim_num && $defendant_num > 0 && $defendant_num < 3 ) {
			return new WP_Error( 'logical_error', 'Monell claims target municipalities (3, 5, 6, 7), not individuals (1, 2)' );
		}

		return array(
			'valid'          => true,
			'uid'            => $uid,
			'claim'          => self::$causes_of_action[ $claim_num ],
			'claim_number'   => $claim_num,
			'element'        => self::$elements[ $claim_num ][ $element_num ],
			'element_number' => $element_num,
			'defendant'      => $defendant_num > 0 ? self::$defendants[ $defendant_num ] : 'Group/Multiple',
			'defendant_number' => $defendant_num,
		);
	}

	/**
	 * Get all valid UIDs for a claim
	 *
	 * @param int $claim_number Claim number (1-14).
	 * @return array List of valid UIDs.
	 */
	public static function get_uids_for_claim( $claim_number ) {
		if ( ! isset( self::$elements[ $claim_number ] ) ) {
			return array();
		}

		$uids = array();
		foreach ( self::$elements[ $claim_number ] as $element_num => $element_name ) {
			foreach ( self::$defendants as $defendant_num => $defendant_name ) {
				$uid = $claim_number . ( $element_num / 10 ) . $defendant_num;
				$uids[] = $uid;
			}
			// Add group UID (0)
			$uids[] = $claim_number . ( $element_num / 10 ) . '0';
		}

		return $uids;
	}

	/**
	 * Get all claims
	 *
	 * @return array
	 */
	public static function get_claims() {
		return self::$causes_of_action;
	}

	/**
	 * Get all defendants
	 *
	 * @return array
	 */
	public static function get_defendants() {
		return self::$defendants;
	}

	/**
	 * Get elements for a claim
	 *
	 * @param int $claim_number Claim number.
	 * @return array
	 */
	public static function get_elements( $claim_number ) {
		return isset( self::$elements[ $claim_number ] ) ? self::$elements[ $claim_number ] : array();
	}

	/**
	 * Build UID from parts
	 *
	 * @param int $claim_num Claim number (1-14).
	 * @param int $element_num Element number (10, 20, 30...).
	 * @param int $defendant_num Defendant number (0-9).
	 * @return string UID.
	 */
	public static function build_uid( $claim_num, $element_num, $defendant_num ) {
		return sprintf( '%d%d%d', $claim_num, $element_num / 10, $defendant_num );
	}

	/**
	 * Get UID breakdown
	 *
	 * @param string $uid UID.
	 * @return array|WP_Error Breakdown or error.
	 */
	public static function parse_uid( $uid ) {
		return self::validate_uid( $uid );
	}

	/**
	 * Search UIDs by criteria
	 *
	 * @param array $criteria Search criteria.
	 * @return array Matching UIDs.
	 */
	public static function search_uids( $criteria = array() ) {
		$claim_num     = isset( $criteria['claim'] ) ? (int) $criteria['claim'] : null;
		$defendant_num = isset( $criteria['defendant'] ) ? (int) $criteria['defendant'] : null;
		$element_num   = isset( $criteria['element'] ) ? (int) $criteria['element'] : null;

		$results = array();

		// Filter by claim
		$claims_to_search = $claim_num ? array( $claim_num ) : array_keys( self::$causes_of_action );

		foreach ( $claims_to_search as $claim ) {
			$elements_to_search = $element_num ? array( $element_num ) : array_keys( self::$elements[ $claim ] );

			foreach ( $elements_to_search as $element ) {
				$defendants_to_search = $defendant_num !== null ? array( $defendant_num ) : array_merge( array( 0 ), array_keys( self::$defendants ) );

				foreach ( $defendants_to_search as $defendant ) {
					$uid = self::build_uid( $claim, $element, $defendant );
					$parsed = self::validate_uid( $uid );

					if ( ! is_wp_error( $parsed ) ) {
						$results[] = $parsed;
					}
				}
			}
		}

		return $results;
	}
}
