<?php
/**
 * REST API Handler for Ninth Circuit Tools
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT REST API Class
 */
class NCT_REST_API {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private $namespace = 'ninth-circuit-tools/v1';

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Chat endpoint
		register_rest_route(
			$this->namespace,
			'/chat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_chat' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// MCP status endpoint
		register_rest_route(
			$this->namespace,
			'/mcp/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_mcp_status' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// MCP connect endpoint
		register_rest_route(
			$this->namespace,
			'/mcp/connect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'connect_mcp' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);

		// Evidence endpoints
		register_rest_route(
			$this->namespace,
			'/evidence',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_evidence_list' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_evidence' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/evidence/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_evidence' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_evidence' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_evidence' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		// Citations endpoints
		register_rest_route(
			$this->namespace,
			'/citations',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_citations_list' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_citation' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/citations/format',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'format_citation' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// Outline endpoints
		register_rest_route(
			$this->namespace,
			'/outline',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_outlines_list' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_outline' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/outline/build',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'build_outline' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Check if user has permission
	 *
	 * @return bool
	 */
	public function check_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check if user has admin permission
	 *
	 * @return bool
	 */
	public function check_admin_permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Handle chat messages
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function handle_chat( $request ) {
		$message = sanitize_text_field( $request->get_param( 'message' ) );
		$context = sanitize_text_field( $request->get_param( 'context' ) );

		if ( empty( $message ) ) {
			return new WP_REST_Response(
				array( 'error' => 'Message is required' ),
				400
			);
		}

		// Process the message based on context
		$reply = $this->process_chat_message( $message, $context );

		return new WP_REST_Response(
			array(
				'success' => true,
				'reply'   => $reply,
			),
			200
		);
	}

	/**
	 * Process chat message
	 *
	 * @param string $message The message.
	 * @param string $context The context.
	 * @return string
	 */
	private function process_chat_message( $message, $context ) {
		// Simple response system - can be extended with AI integration
		$message_lower = strtolower( $message );

		if ( strpos( $message_lower, 'help' ) !== false ) {
			return 'Available commands: evidence, citation, outline, mcp. Type the name of any tool to get started.';
		}

		if ( strpos( $message_lower, 'evidence' ) !== false ) {
			return 'Switch to the Evidence tab to collect and organize evidence documents for your case.';
		}

		if ( strpos( $message_lower, 'citation' ) !== false ) {
			return 'Use the Citations tab to manage legal citations with automatic Bluebook formatting.';
		}

		if ( strpos( $message_lower, 'outline' ) !== false ) {
			return 'The Outline tab helps you build case outlines with rule-based auto-population.';
		}

		if ( strpos( $message_lower, 'mcp' ) !== false ) {
			$mcp_enabled = get_option( 'nct_mcp_enabled', false );
			if ( $mcp_enabled ) {
				return 'MCP Server is enabled. You can use advanced AI features through the Model Context Protocol.';
			} else {
				return 'MCP Server is not configured. Ask an administrator to set it up in the MCP Server settings.';
			}
		}

		return 'I received your message: "' . esc_html( $message ) . '". How can I help you with your 9th Circuit case?';
	}

	/**
	 * Get MCP server status
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_mcp_status( $request ) {
		$mcp_enabled  = get_option( 'nct_mcp_enabled', false );
		$mcp_endpoint = get_option( 'nct_mcp_endpoint', '' );

		$connected = false;

		if ( $mcp_enabled && ! empty( $mcp_endpoint ) ) {
			// Check if MCP server is actually reachable
			$connector = new NCT_MCP_Connector();
			$connected = $connector->test_connection( $mcp_endpoint );
		}

		return new WP_REST_Response(
			array(
				'connected' => $connected,
				'enabled'   => $mcp_enabled,
				'endpoint'  => $mcp_enabled ? $mcp_endpoint : null,
			),
			200
		);
	}

	/**
	 * Connect to MCP server
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function connect_mcp( $request ) {
		$endpoint = sanitize_text_field( $request->get_param( 'endpoint' ) );

		if ( empty( $endpoint ) ) {
			return new WP_REST_Response(
				array( 'error' => 'Endpoint is required' ),
				400
			);
		}

		// Test connection
		$connector = new NCT_MCP_Connector();
		$connected = $connector->test_connection( $endpoint );

		if ( $connected ) {
			update_option( 'nct_mcp_enabled', true );
			update_option( 'nct_mcp_endpoint', $endpoint );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Successfully connected to MCP server',
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array( 'error' => 'Could not connect to MCP server' ),
				500
			);
		}
	}

	/**
	 * Get evidence list
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_evidence_list( $request ) {
		$collector = new NCT_Evidence_Collector();
		$evidence  = $collector->get_all();

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $evidence,
			),
			200
		);
	}

	/**
	 * Create evidence
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create_evidence( $request ) {
		$data = array(
			'title'       => sanitize_text_field( $request->get_param( 'title' ) ),
			'description' => sanitize_textarea_field( $request->get_param( 'description' ) ),
			'tags'        => sanitize_text_field( $request->get_param( 'tags' ) ),
			'file_url'    => esc_url_raw( $request->get_param( 'file_url' ) ),
		);

		$collector   = new NCT_Evidence_Collector();
		$evidence_id = $collector->create( $data );

		if ( $evidence_id ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'id'      => $evidence_id,
					'message' => 'Evidence created successfully',
				),
				201
			);
		} else {
			return new WP_REST_Response(
				array( 'error' => 'Failed to create evidence' ),
				500
			);
		}
	}

	/**
	 * Get single evidence
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_evidence( $request ) {
		$id        = (int) $request->get_param( 'id' );
		$collector = new NCT_Evidence_Collector();
		$evidence  = $collector->get( $id );

		if ( $evidence ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $evidence,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array( 'error' => 'Evidence not found' ),
				404
			);
		}
	}

	/**
	 * Update evidence
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_evidence( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$data = array(
			'title'       => sanitize_text_field( $request->get_param( 'title' ) ),
			'description' => sanitize_textarea_field( $request->get_param( 'description' ) ),
			'tags'        => sanitize_text_field( $request->get_param( 'tags' ) ),
		);

		$collector = new NCT_Evidence_Collector();
		$updated   = $collector->update( $id, $data );

		if ( $updated ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Evidence updated successfully',
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array( 'error' => 'Failed to update evidence' ),
				500
			);
		}
	}

	/**
	 * Delete evidence
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function delete_evidence( $request ) {
		$id        = (int) $request->get_param( 'id' );
		$collector = new NCT_Evidence_Collector();
		$deleted   = $collector->delete( $id );

		if ( $deleted ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Evidence deleted successfully',
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array( 'error' => 'Failed to delete evidence' ),
				500
			);
		}
	}

	/**
	 * Get citations list
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_citations_list( $request ) {
		$manager   = new NCT_Citation_Manager();
		$citations = $manager->get_all();

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $citations,
			),
			200
		);
	}

	/**
	 * Create citation
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create_citation( $request ) {
		$data = array(
			'case_name' => sanitize_text_field( $request->get_param( 'case_name' ) ),
			'court'     => sanitize_text_field( $request->get_param( 'court' ) ),
			'year'      => sanitize_text_field( $request->get_param( 'year' ) ),
			'style'     => sanitize_text_field( $request->get_param( 'style' ) ),
		);

		$manager      = new NCT_Citation_Manager();
		$citation_id  = $manager->create( $data );

		if ( $citation_id ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'id'      => $citation_id,
					'message' => 'Citation created successfully',
				),
				201
			);
		} else {
			return new WP_REST_Response(
				array( 'error' => 'Failed to create citation' ),
				500
			);
		}
	}

	/**
	 * Format citation
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function format_citation( $request ) {
		$citation = $request->get_param( 'citation' );
		$style    = sanitize_text_field( $request->get_param( 'style' ) );

		$manager   = new NCT_Citation_Manager();
		$formatted = $manager->format( $citation, $style );

		return new WP_REST_Response(
			array(
				'success'   => true,
				'formatted' => $formatted,
			),
			200
		);
	}

	/**
	 * Get outlines list
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_outlines_list( $request ) {
		$builder  = new NCT_Outline_Builder();
		$outlines = $builder->get_all();

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $outlines,
			),
			200
		);
	}

	/**
	 * Create outline
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create_outline( $request ) {
		$data = array(
			'title' => sanitize_text_field( $request->get_param( 'title' ) ),
			'data'  => $request->get_param( 'data' ),
		);

		$builder    = new NCT_Outline_Builder();
		$outline_id = $builder->create( $data );

		if ( $outline_id ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'id'      => $outline_id,
					'message' => 'Outline created successfully',
				),
				201
			);
		} else {
			return new WP_REST_Response(
				array( 'error' => 'Failed to create outline' ),
				500
			);
		}
	}

	/**
	 * Build outline from key points
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function build_outline( $request ) {
		$key_points = $request->get_param( 'keyPoints' );
		$rules      = $request->get_param( 'rules' );

		$builder = new NCT_Outline_Builder();
		$html    = $builder->build_from_keypoints( $key_points, $rules );

		return new WP_REST_Response(
			array(
				'success' => true,
				'html'    => $html,
			),
			200
		);
	}
}
