<?php
/**
 * Admin Main Page Template
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get statistics
$evidence_collector = new NCT_Evidence_Collector();
$citation_manager   = new NCT_Citation_Manager();
$outline_builder    = new NCT_Outline_Builder();
$mcp_connector      = new NCT_MCP_Connector();

$evidence_count = $evidence_collector->get_count();
$citation_count = $citation_manager->get_count();
$outline_count  = $outline_builder->get_count();
$mcp_status     = $mcp_connector->get_status();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Ninth Circuit Tools', 'ninth-circuit-tools' ); ?></h1>

	<div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1)); padding: 24px; border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.2); margin: 20px 0;">
		<h2 style="margin-top: 0;">⚖ Welcome to Ninth Circuit Tools</h2>
		<p style="font-size: 16px; line-height: 1.6;">
			Your comprehensive legal toolkit for 9th Circuit case development. Collect evidence, manage citations, build outlines, and connect to MCP servers for advanced AI-powered features.
		</p>
	</div>

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
		<!-- Evidence Stats -->
		<div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6;">
			<div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">📋 Evidence Collected</div>
			<div style="font-size: 32px; font-weight: bold; color: #1e293b;"><?php echo esc_html( $evidence_count ); ?></div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nct-evidence' ) ); ?>" class="button button-secondary" style="margin-top: 12px;">Manage Evidence</a>
		</div>

		<!-- Citations Stats -->
		<div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #8b5cf6;">
			<div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">📚 Citations Created</div>
			<div style="font-size: 32px; font-weight: bold; color: #1e293b;"><?php echo esc_html( $citation_count ); ?></div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nct-citations' ) ); ?>" class="button button-secondary" style="margin-top: 12px;">Manage Citations</a>
		</div>

		<!-- Outlines Stats -->
		<div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
			<div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">📝 Outlines Built</div>
			<div style="font-size: 32px; font-weight: bold; color: #1e293b;"><?php echo esc_html( $outline_count ); ?></div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nct-outline' ) ); ?>" class="button button-secondary" style="margin-top: 12px;">Build Outline</a>
		</div>

		<!-- MCP Status -->
		<div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $mcp_status['connected'] ? '#10b981' : '#ef4444'; ?>;">
			<div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">🔌 MCP Server</div>
			<div style="font-size: 24px; font-weight: bold; color: #1e293b;">
				<?php echo $mcp_status['connected'] ? '✓ Connected' : '✗ Disconnected'; ?>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nct-mcp' ) ); ?>" class="button button-secondary" style="margin-top: 12px;">MCP Settings</a>
		</div>
	</div>

	<div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 30px 0;">
		<h2><?php esc_html_e( 'Getting Started', 'ninth-circuit-tools' ); ?></h2>

		<div style="display: grid; gap: 16px;">
			<div style="border-left: 3px solid #3b82f6; padding-left: 16px;">
				<h3 style="margin: 0 0 8px 0; color: #1e293b;">1. Collect Evidence</h3>
				<p style="margin: 0; color: #64748b;">Start by collecting and organizing evidence documents. Use the Evidence tab to upload and categorize your materials.</p>
			</div>

			<div style="border-left: 3px solid #8b5cf6; padding-left: 16px;">
				<h3 style="margin: 0 0 8px 0; color: #1e293b;">2. Manage Citations</h3>
				<p style="margin: 0; color: #64748b;">Create properly formatted legal citations. The system supports Bluebook, ALWD, MLA, and APA formats.</p>
			</div>

			<div style="border-left: 3px solid #10b981; padding-left: 16px;">
				<h3 style="margin: 0 0 8px 0; color: #1e293b;">3. Build Your Outline</h3>
				<p style="margin: 0; color: #64748b;">Use the rule-based outline builder to organize your arguments. Key points auto-populate with relevant evidence and citations.</p>
			</div>

			<div style="border-left: 3px solid #f59e0b; padding-left: 16px;">
				<h3 style="margin: 0 0 8px 0; color: #1e293b;">4. Connect MCP Server (Optional)</h3>
				<p style="margin: 0; color: #64748b;">Enable advanced AI features by connecting to an MCP (Model Context Protocol) server for intelligent assistance.</p>
			</div>
		</div>
	</div>

	<div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05)); padding: 20px; border-radius: 8px; border: 1px solid rgba(148, 163, 184, 0.2); margin: 30px 0;">
		<h3 style="margin-top: 0;">💡 Quick Tip</h3>
		<p style="margin: 0; line-height: 1.6;">
			The floating glass panel is always accessible via the button in the bottom-right corner. Press <kbd>Ctrl/Cmd + Shift + N</kbd> to toggle it quickly.
			Use it for quick access to chat, logs, and all your tools.
		</p>
	</div>

	<div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 30px 0;">
		<h2><?php esc_html_e( 'Features', 'ninth-circuit-tools' ); ?></h2>

		<ul style="list-style: none; padding: 0; margin: 0;">
			<li style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
				<strong>🎨 Futuristic Glass UI</strong> - Dark theme with soft backlight perimeter glows and frosted glass effects
			</li>
			<li style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
				<strong>📋 Evidence Collection</strong> - Organize and catalog all your evidence documents with tags and descriptions
			</li>
			<li style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
				<strong>📚 Citation Management</strong> - Automatic formatting in multiple legal citation styles
			</li>
			<li style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
				<strong>📝 Rule-Based Outline Builder</strong> - Intelligent outline construction with auto-population
			</li>
			<li style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
				<strong>🔌 MCP Server Integration</strong> - Connect to AI servers for advanced features
			</li>
			<li style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
				<strong>💬 Real-Time Chat</strong> - Chat interface with VTT support and logging
			</li>
			<li style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
				<strong>📊 Activity Logs</strong> - Track all system activities and export logs
			</li>
			<li style="padding: 12px 0;">
				<strong>⌨️ Keyboard Shortcuts</strong> - Quick access with Ctrl/Cmd + Shift + N
			</li>
		</ul>
	</div>

	<div style="text-align: center; padding: 20px; color: #64748b;">
		<p>
			<?php esc_html_e( 'Ninth Circuit Tools v', 'ninth-circuit-tools' ); ?>
			<?php echo esc_html( NCT_VERSION ); ?>
			 |
			<a href="https://github.com/TylerALofall/WordPress" target="_blank"><?php esc_html_e( 'Documentation', 'ninth-circuit-tools' ); ?></a>
			 |
			<a href="https://github.com/TylerALofall/WordPress/issues" target="_blank"><?php esc_html_e( 'Report an Issue', 'ninth-circuit-tools' ); ?></a>
		</p>
	</div>
</div>

<style>
kbd {
	background: #f1f5f9;
	border: 1px solid #cbd5e1;
	border-radius: 4px;
	padding: 2px 6px;
	font-family: monospace;
	font-size: 12px;
}
</style>
