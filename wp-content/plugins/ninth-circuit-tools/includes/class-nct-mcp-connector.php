<?php
/**
 * MCP (Model Context Protocol) Connector for Ninth Circuit Tools
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCT MCP Connector Class
 *
 * Handles connections to MCP servers for advanced AI features
 */
class NCT_MCP_Connector {

	/**
	 * MCP endpoint URL
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * Connection timeout in seconds
	 *
	 * @var int
	 */
	private $timeout = 10;

	/**
	 * Constructor
	 *
	 * @param string $endpoint Optional MCP endpoint URL.
	 */
	public function __construct( $endpoint = null ) {
		if ( $endpoint ) {
			$this->endpoint = $endpoint;
		} else {
			$this->endpoint = get_option( 'nct_mcp_endpoint', '' );
		}
	}

	/**
	 * Test connection to MCP server
	 *
	 * @param string $endpoint Optional endpoint to test.
	 * @return bool True if connected, false otherwise.
	 */
	public function test_connection( $endpoint = null ) {
		if ( $endpoint ) {
			$this->endpoint = $endpoint;
		}

		if ( empty( $this->endpoint ) ) {
			return false;
		}

		try {
			$response = wp_remote_get(
				$this->endpoint . '/health',
				array(
					'timeout' => $this->timeout,
					'headers' => array(
						'Content-Type' => 'application/json',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				$this->log_error( 'Connection test failed: ' . $response->get_error_message() );
				return false;
			}

			$status_code = wp_remote_retrieve_response_code( $response );

			if ( 200 === $status_code ) {
				$this->log_info( 'MCP server connection successful' );
				return true;
			}

			$this->log_error( 'MCP server returned status code: ' . $status_code );
			return false;

		} catch ( Exception $e ) {
			$this->log_error( 'Connection exception: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Send request to MCP server
	 *
	 * @param string $method HTTP method.
	 * @param string $path API path.
	 * @param array  $data Request data.
	 * @return array|WP_Error Response data or error.
	 */
	public function request( $method, $path, $data = array() ) {
		if ( empty( $this->endpoint ) ) {
			return new WP_Error( 'no_endpoint', 'MCP endpoint not configured' );
		}

		$url = trailingslashit( $this->endpoint ) . ltrim( $path, '/' );

		$args = array(
			'method'  => strtoupper( $method ),
			'timeout' => $this->timeout,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
		);

		// Add API key if configured
		$api_key = get_option( 'nct_mcp_api_key', '' );
		if ( ! empty( $api_key ) ) {
			$args['headers']['Authorization'] = 'Bearer ' . $api_key;
		}

		if ( ! empty( $data ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log_error( 'MCP request failed: ' . $response->get_error_message() );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$decoded     = json_decode( $body, true );

		if ( $status_code >= 200 && $status_code < 300 ) {
			$this->log_info( 'MCP request successful: ' . $method . ' ' . $path );
			return $decoded ? $decoded : $body;
		}

		$error_message = isset( $decoded['error'] ) ? $decoded['error'] : 'Unknown error';
		$this->log_error( 'MCP request error (' . $status_code . '): ' . $error_message );

		return new WP_Error( 'mcp_error', $error_message, array( 'status' => $status_code ) );
	}

	/**
	 * Send chat message to MCP server
	 *
	 * @param string $message Message text.
	 * @param array  $context Context data.
	 * @return array|WP_Error Response or error.
	 */
	public function send_chat( $message, $context = array() ) {
		$data = array(
			'message' => $message,
			'context' => $context,
		);

		return $this->request( 'POST', 'chat', $data );
	}

	/**
	 * Get available MCP tools
	 *
	 * @return array|WP_Error List of tools or error.
	 */
	public function get_tools() {
		return $this->request( 'GET', 'tools' );
	}

	/**
	 * Execute MCP tool
	 *
	 * @param string $tool_name Tool name.
	 * @param array  $params Tool parameters.
	 * @return array|WP_Error Tool result or error.
	 */
	public function execute_tool( $tool_name, $params = array() ) {
		$data = array(
			'tool'   => $tool_name,
			'params' => $params,
		);

		return $this->request( 'POST', 'tools/execute', $data );
	}

	/**
	 * Get MCP server capabilities
	 *
	 * @return array|WP_Error Capabilities or error.
	 */
	public function get_capabilities() {
		return $this->request( 'GET', 'capabilities' );
	}

	/**
	 * Subscribe to MCP events
	 *
	 * @param string $event_type Event type.
	 * @param string $callback_url Callback URL.
	 * @return array|WP_Error Subscription data or error.
	 */
	public function subscribe( $event_type, $callback_url ) {
		$data = array(
			'event'    => $event_type,
			'callback' => $callback_url,
		);

		return $this->request( 'POST', 'subscribe', $data );
	}

	/**
	 * Unsubscribe from MCP events
	 *
	 * @param string $subscription_id Subscription ID.
	 * @return array|WP_Error Response or error.
	 */
	public function unsubscribe( $subscription_id ) {
		return $this->request( 'DELETE', 'subscribe/' . $subscription_id );
	}

	/**
	 * Send evidence to MCP for analysis
	 *
	 * @param array $evidence Evidence data.
	 * @return array|WP_Error Analysis result or error.
	 */
	public function analyze_evidence( $evidence ) {
		$data = array(
			'evidence' => $evidence,
			'type'     => 'legal_document',
		);

		return $this->request( 'POST', 'analyze/evidence', $data );
	}

	/**
	 * Request citation suggestions from MCP
	 *
	 * @param string $context Context text.
	 * @param string $jurisdiction Jurisdiction (e.g., '9th Circuit').
	 * @return array|WP_Error Citation suggestions or error.
	 */
	public function suggest_citations( $context, $jurisdiction = '9th Circuit' ) {
		$data = array(
			'context'      => $context,
			'jurisdiction' => $jurisdiction,
		);

		return $this->request( 'POST', 'suggest/citations', $data );
	}

	/**
	 * Request outline enhancement from MCP
	 *
	 * @param array $outline Outline data.
	 * @param array $rules Enhancement rules.
	 * @return array|WP_Error Enhanced outline or error.
	 */
	public function enhance_outline( $outline, $rules = array() ) {
		$data = array(
			'outline' => $outline,
			'rules'   => $rules,
		);

		return $this->request( 'POST', 'enhance/outline', $data );
	}

	/**
	 * Get MCP server status
	 *
	 * @return array Status information.
	 */
	public function get_status() {
		$connected = $this->test_connection();

		$status = array(
			'connected' => $connected,
			'endpoint'  => $this->endpoint,
			'version'   => null,
		);

		if ( $connected ) {
			$capabilities = $this->get_capabilities();
			if ( ! is_wp_error( $capabilities ) && isset( $capabilities['version'] ) ) {
				$status['version'] = $capabilities['version'];
			}
		}

		return $status;
	}

	/**
	 * Set endpoint
	 *
	 * @param string $endpoint Endpoint URL.
	 */
	public function set_endpoint( $endpoint ) {
		$this->endpoint = $endpoint;
	}

	/**
	 * Get endpoint
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * Set timeout
	 *
	 * @param int $timeout Timeout in seconds.
	 */
	public function set_timeout( $timeout ) {
		$this->timeout = max( 1, min( 60, (int) $timeout ) );
	}

	/**
	 * Log info message
	 *
	 * @param string $message Message.
	 */
	private function log_info( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[NCT MCP] ' . $message );
		}

		do_action( 'nct_mcp_log', 'info', $message );
	}

	/**
	 * Log error message
	 *
	 * @param string $message Message.
	 */
	private function log_error( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[NCT MCP ERROR] ' . $message );
		}

		do_action( 'nct_mcp_log', 'error', $message );
	}

	/**
	 * Get connection logs
	 *
	 * @param int $limit Number of logs to retrieve.
	 * @return array Logs.
	 */
	public function get_logs( $limit = 50 ) {
		$logs = get_option( 'nct_mcp_logs', array() );

		return array_slice( $logs, -$limit );
	}

	/**
	 * Clear connection logs
	 */
	public function clear_logs() {
		delete_option( 'nct_mcp_logs' );
	}

	/**
	 * WebSocket support check
	 *
	 * @return bool
	 */
	public function supports_websocket() {
		// Check if the server supports WebSocket connections
		$capabilities = $this->get_capabilities();

		if ( is_wp_error( $capabilities ) ) {
			return false;
		}

		return isset( $capabilities['websocket'] ) && $capabilities['websocket'];
	}

	/**
	 * Initialize WebSocket connection (placeholder for future implementation)
	 *
	 * @return bool|WP_Error
	 */
	public function init_websocket() {
		if ( ! $this->supports_websocket() ) {
			return new WP_Error( 'no_websocket', 'WebSocket not supported by MCP server' );
		}

		// WebSocket implementation would go here
		// This is a placeholder for future real-time communication features

		return new WP_Error( 'not_implemented', 'WebSocket support not yet implemented' );
	}
}
