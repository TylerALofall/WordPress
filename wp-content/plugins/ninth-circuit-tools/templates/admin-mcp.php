<?php
/**
 * Admin MCP Server Page Template
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mcp_connector = new NCT_MCP_Connector();
$mcp_enabled   = get_option( 'nct_mcp_enabled', false );
$mcp_endpoint  = get_option( 'nct_mcp_endpoint', '' );
$mcp_status    = $mcp_connector->get_status();

// Handle form submission
if ( isset( $_POST['nct_mcp_save'] ) && check_admin_referer( 'nct_mcp_settings' ) ) {
	$endpoint = sanitize_text_field( $_POST['nct_mcp_endpoint'] );
	$api_key  = sanitize_text_field( $_POST['nct_mcp_api_key'] );

	update_option( 'nct_mcp_endpoint', $endpoint );
	update_option( 'nct_mcp_api_key', $api_key );

	if ( ! empty( $endpoint ) ) {
		$test = $mcp_connector->test_connection( $endpoint );
		if ( $test ) {
			update_option( 'nct_mcp_enabled', true );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'MCP server connected successfully!', 'ninth-circuit-tools' ) . '</p></div>';
		} else {
			update_option( 'nct_mcp_enabled', false );
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Could not connect to MCP server. Please check your endpoint.', 'ninth-circuit-tools' ) . '</p></div>';
		}
	} else {
		update_option( 'nct_mcp_enabled', false );
	}

	$mcp_enabled  = get_option( 'nct_mcp_enabled', false );
	$mcp_endpoint = get_option( 'nct_mcp_endpoint', '' );
	$mcp_status   = $mcp_connector->get_status();
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'MCP Server Settings', 'ninth-circuit-tools' ); ?></h1>
	<p><?php esc_html_e( 'Configure your Model Context Protocol (MCP) server connection for advanced AI features.', 'ninth-circuit-tools' ); ?></p>

	<div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 20px 0;">
		<h2><?php esc_html_e( 'Connection Status', 'ninth-circuit-tools' ); ?></h2>
		<div style="padding: 16px; background: <?php echo $mcp_status['connected'] ? '#ecfdf5' : '#fef2f2'; ?>; border-left: 4px solid <?php echo $mcp_status['connected'] ? '#10b981' : '#ef4444'; ?>; border-radius: 4px;">
			<strong><?php echo $mcp_status['connected'] ? '✓ Connected' : '✗ Disconnected'; ?></strong>
			<?php if ( $mcp_status['connected'] && $mcp_status['version'] ) : ?>
				<br><small><?php echo esc_html( sprintf( 'Version: %s', $mcp_status['version'] ) ); ?></small>
			<?php endif; ?>
		</div>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'nct_mcp_settings' ); ?>

		<div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 20px 0;">
			<h2><?php esc_html_e( 'MCP Server Configuration', 'ninth-circuit-tools' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="nct_mcp_endpoint"><?php esc_html_e( 'MCP Server Endpoint', 'ninth-circuit-tools' ); ?></label>
					</th>
					<td>
						<input type="url" id="nct_mcp_endpoint" name="nct_mcp_endpoint" value="<?php echo esc_attr( $mcp_endpoint ); ?>" class="regular-text" placeholder="https://your-mcp-server.com/api" />
						<p class="description"><?php esc_html_e( 'Enter the URL of your MCP server endpoint.', 'ninth-circuit-tools' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="nct_mcp_api_key"><?php esc_html_e( 'API Key (Optional)', 'ninth-circuit-tools' ); ?></label>
					</th>
					<td>
						<input type="password" id="nct_mcp_api_key" name="nct_mcp_api_key" value="<?php echo esc_attr( get_option( 'nct_mcp_api_key', '' ) ); ?>" class="regular-text" placeholder="your-api-key" />
						<p class="description"><?php esc_html_e( 'If your MCP server requires authentication, enter your API key here.', 'ninth-circuit-tools' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'ninth-circuit-tools' ), 'primary', 'nct_mcp_save' ); ?>
		</div>
	</form>

	<div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05)); padding: 20px; border-radius: 8px; border: 1px solid rgba(148, 163, 184, 0.2); margin: 30px 0;">
		<h3 style="margin-top: 0;">ℹ️ About MCP</h3>
		<p style="line-height: 1.6;">
			<?php esc_html_e( 'The Model Context Protocol (MCP) is an open standard that enables seamless integration between AI applications and data sources. By connecting to an MCP server, you can unlock advanced AI features like:', 'ninth-circuit-tools' ); ?>
		</p>
		<ul>
			<li><?php esc_html_e( 'Intelligent evidence analysis', 'ninth-circuit-tools' ); ?></li>
			<li><?php esc_html_e( 'Citation suggestions based on context', 'ninth-circuit-tools' ); ?></li>
			<li><?php esc_html_e( 'Enhanced outline building with AI assistance', 'ninth-circuit-tools' ); ?></li>
			<li><?php esc_html_e( 'Real-time chat with AI models', 'ninth-circuit-tools' ); ?></li>
		</ul>
	</div>
</div>
