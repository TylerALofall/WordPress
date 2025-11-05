<?php
/**
 * Fact Pool Tool
 * Collect and manage facts for your case
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT Fact Pool Tool
 */
class NCT_Tool_Fact_Pool {

	/**
	 * Register the tool
	 */
	public static function register() {
		// Add fact
		NCT_Micro_Tools::register_tool(
			'fact-add',
			array(
				'name'        => 'Add Fact',
				'description' => 'Add a fact to your pool',
				'icon'        => '➕',
				'category'    => 'facts',
				'callback'    => array( __CLASS__, 'add_fact' ),
				'params'      => array(
					'fact'        => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'Fact statement',
					),
					'category'    => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Fact category',
						'default'     => 'general',
					),
					'source'      => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Source of fact',
					),
					'date'        => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Date of fact',
					),
					'tags'        => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Comma-separated tags',
					),
				),
			)
		);

		// List facts
		NCT_Micro_Tools::register_tool(
			'fact-list',
			array(
				'name'        => 'List Facts',
				'description' => 'Get all facts in your pool',
				'icon'        => '📋',
				'category'    => 'facts',
				'callback'    => array( __CLASS__, 'list_facts' ),
				'checkpoint'  => false,
				'params'      => array(
					'category'    => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Filter by category',
					),
					'search'      => array(
						'type'        => 'string',
						'required'    => false,
						'description' => 'Search facts',
					),
				),
			)
		);

		// Search facts
		NCT_Micro_Tools::register_tool(
			'fact-search',
			array(
				'name'        => 'Search Facts',
				'description' => 'Search for specific facts',
				'icon'        => '🔍',
				'category'    => 'facts',
				'callback'    => array( __CLASS__, 'search_facts' ),
				'checkpoint'  => false,
				'params'      => array(
					'query'       => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'Search query',
					),
				),
			)
		);

		// Delete fact
		NCT_Micro_Tools::register_tool(
			'fact-delete',
			array(
				'name'        => 'Delete Fact',
				'description' => 'Remove a fact from the pool',
				'icon'        => '🗑️',
				'category'    => 'facts',
				'callback'    => array( __CLASS__, 'delete_fact' ),
				'params'      => array(
					'fact_id'     => array(
						'type'        => 'integer',
						'required'    => true,
						'description' => 'Fact ID to delete',
					),
				),
			)
		);
	}

	/**
	 * Add fact
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	public static function add_fact( $params ) {
		$fact     = isset( $params['fact'] ) ? sanitize_textarea_field( $params['fact'] ) : '';
		$category = isset( $params['category'] ) ? sanitize_text_field( $params['category'] ) : 'general';
		$source   = isset( $params['source'] ) ? sanitize_text_field( $params['source'] ) : '';
		$date     = isset( $params['date'] ) ? sanitize_text_field( $params['date'] ) : '';
		$tags     = isset( $params['tags'] ) ? sanitize_text_field( $params['tags'] ) : '';

		if ( empty( $fact ) ) {
			return array(
				'success' => false,
				'error'   => 'Fact is required',
			);
		}

		// Create table if doesn't exist
		global $wpdb;
		$table_name = $wpdb->prefix . 'nct_facts';

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			fact text NOT NULL,
			category varchar(100),
			source text,
			fact_date varchar(100),
			tags text,
			created_by bigint(20),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Insert fact
		$result = $wpdb->insert(
			$table_name,
			array(
				'fact'       => $fact,
				'category'   => $category,
				'source'     => $source,
				'fact_date'  => $date,
				'tags'       => $tags,
				'created_by' => get_current_user_id(),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( false === $result ) {
			return array(
				'success' => false,
				'error'   => 'Failed to add fact',
			);
		}

		$fact_id = $wpdb->insert_id;

		return array(
			'success'  => true,
			'message'  => 'Fact added to pool',
			'fact_id'  => $fact_id,
			'fact'     => $fact,
			'category' => $category,
			'source'   => $source,
			'date'     => $date,
			'tags'     => $tags,
		);
	}

	/**
	 * List facts
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	public static function list_facts( $params ) {
		$category = isset( $params['category'] ) ? sanitize_text_field( $params['category'] ) : '';
		$search   = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';

		global $wpdb;
		$table_name = $wpdb->prefix . 'nct_facts';

		// Build query
		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $category ) ) {
			$where[] = 'category = %s';
			$where_values[] = $category;
		}

		if ( ! empty( $search ) ) {
			$where[] = '(fact LIKE %s OR tags LIKE %s OR source LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at DESC";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$facts = $wpdb->get_results( $query );

		return array(
			'success' => true,
			'count'   => count( $facts ),
			'facts'   => $facts,
		);
	}

	/**
	 * Search facts
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	public static function search_facts( $params ) {
		$query = isset( $params['query'] ) ? sanitize_text_field( $params['query'] ) : '';

		if ( empty( $query ) ) {
			return array(
				'success' => false,
				'error'   => 'Search query is required',
			);
		}

		return self::list_facts( array( 'search' => $query ) );
	}

	/**
	 * Delete fact
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	public static function delete_fact( $params ) {
		$fact_id = isset( $params['fact_id'] ) ? (int) $params['fact_id'] : 0;

		if ( empty( $fact_id ) ) {
			return array(
				'success' => false,
				'error'   => 'Fact ID is required',
			);
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'nct_facts';

		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $fact_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return array(
				'success' => false,
				'error'   => 'Failed to delete fact',
			);
		}

		return array(
			'success' => true,
			'message' => 'Fact deleted from pool',
			'fact_id' => $fact_id,
		);
	}

	/**
	 * Get categories
	 *
	 * @return array Categories.
	 */
	public static function get_categories() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nct_facts';

		$categories = $wpdb->get_col( "SELECT DISTINCT category FROM $table_name WHERE category != '' ORDER BY category ASC" );

		return $categories;
	}

	/**
	 * Get fact count
	 *
	 * @return int Count.
	 */
	public static function get_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'nct_facts';

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
	}
}
