<?php
/**
 * Floating Glass Panel Template
 * The main UI component for Ninth Circuit Tools
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="nct-floating-panel nct-collapsed">
	<!-- Toggle Button (visible when collapsed) -->
	<button class="nct-toggle-button" title="Open Ninth Circuit Tools">
		⚖
	</button>

	<!-- Glass Panel Container -->
	<div class="nct-glass-panel">
		<!-- Panel Header -->
		<div class="nct-panel-header">
			<h2 class="nct-panel-title">Ninth Circuit Tools</h2>

			<div class="nct-panel-controls">
				<button class="nct-control-btn nct-expand-btn" title="Expand" aria-label="Expand panel">
					<span>⛶</span>
				</button>
				<button class="nct-control-btn nct-collapse-btn" title="Collapse" aria-label="Collapse panel">
					<span>⊟</span>
				</button>
				<button class="nct-control-btn nct-minimize-btn" title="Minimize" aria-label="Minimize panel">
					<span>–</span>
				</button>
			</div>
		</div>

		<!-- MCP Server Status -->
		<div class="nct-mcp-status" style="margin: 16px 24px 0;">
			<span class="nct-status-indicator disconnected"></span>
			<span>MCP Server: Checking...</span>
		</div>

		<!-- Tab Navigation -->
		<div class="nct-tab-nav">
			<button class="nct-tab-btn active" data-tab="chat">
				<span>💬 Chat</span>
			</button>
			<button class="nct-tab-btn" data-tab="tools">
				<span>🔧 Tools</span>
			</button>
			<button class="nct-tab-btn" data-tab="evidence">
				<span>📋 Evidence</span>
			</button>
			<button class="nct-tab-btn" data-tab="citations">
				<span>📚 Citations</span>
			</button>
			<button class="nct-tab-btn" data-tab="outline">
				<span>📝 Outline</span>
			</button>
			<button class="nct-tab-btn" data-tab="logs">
				<span>📊 Logs</span>
			</button>
		</div>

		<!-- Panel Content -->
		<div class="nct-panel-content">
			<!-- Chat Tab -->
			<div id="nct-tab-chat" class="nct-tab-content active">
				<div class="nct-chat-container">
					<div class="nct-chat-messages">
						<div class="nct-chat-message system">
							<div class="nct-message-content">
								Welcome to Ninth Circuit Tools! This is your command center for legal research, evidence collection, and case development.
								<br><br>
								<strong>Quick tips:</strong>
								<ul style="margin: 8px 0 0; padding-left: 20px;">
									<li>Use tabs to switch between Evidence, Citations, and Outline tools</li>
									<li>Press Ctrl/Cmd + Shift + N to toggle this panel</li>
									<li>MCP Server connectivity enables advanced features</li>
								</ul>
							</div>
						</div>
					</div>

					<div class="nct-chat-input-container">
						<input type="text" class="nct-chat-input" placeholder="Type your message or command..." />
						<button class="nct-send-btn">Send</button>
					</div>
				</div>
			</div>

			<!-- Tools Tab -->
			<div id="nct-tab-tools" class="nct-tab-content">
				<div class="nct-tools-container">
					<h3 style="color: var(--nct-text-primary); margin-top: 0; font-size: 18px;">Micro-Tools</h3>

					<p style="color: var(--nct-text-secondary); margin-bottom: 20px; line-height: 1.6;">
						Simple, focused tools that do one thing well. Click any tool to use it.
					</p>

					<!-- Tool Categories -->
					<div id="nct-tool-categories" style="margin-bottom: 20px;">
						<button class="nct-category-filter active" data-category="all" style="margin: 4px; padding: 8px 16px; background: rgba(59, 130, 246, 0.2); border: 1px solid var(--nct-glow-primary); border-radius: 20px; color: var(--nct-text-primary); cursor: pointer; font-size: 13px;">
							All Tools
						</button>
						<button class="nct-category-filter" data-category="pdf" style="margin: 4px; padding: 8px 16px; background: rgba(30, 41, 59, 0.5); border: 1px solid var(--nct-glass-border); border-radius: 20px; color: var(--nct-text-secondary); cursor: pointer; font-size: 13px;">
							PDF Tools
						</button>
						<button class="nct-category-filter" data-category="text" style="margin: 4px; padding: 8px 16px; background: rgba(30, 41, 59, 0.5); border: 1px solid var(--nct-glass-border); border-radius: 20px; color: var(--nct-text-secondary); cursor: pointer; font-size: 13px;">
							Text Tools
						</button>
						<button class="nct-category-filter" data-category="facts" style="margin: 4px; padding: 8px 16px; background: rgba(30, 41, 59, 0.5); border: 1px solid var(--nct-glass-border); border-radius: 20px; color: var(--nct-text-secondary); cursor: pointer; font-size: 13px;">
							Fact Pool
						</button>
					</div>

					<!-- Tools Grid -->
					<div id="nct-tools-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
						<div class="nct-loading" style="grid-column: 1 / -1; text-align: center; color: var(--nct-text-secondary); padding: 40px;">
							Loading tools...
						</div>
					</div>

					<!-- Tool Execution Panel (shown when tool is selected) -->
					<div id="nct-tool-executor" style="display: none; margin-top: 24px; padding: 20px; background: rgba(30, 41, 59, 0.4); border: 1px solid var(--nct-glass-border); border-radius: 12px;">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
							<h4 id="nct-tool-name" style="color: var(--nct-text-primary); margin: 0;"></h4>
							<button id="nct-tool-close" style="background: none; border: none; color: var(--nct-text-secondary); cursor: pointer; font-size: 20px;">✕</button>
						</div>
						<p id="nct-tool-description" style="color: var(--nct-text-secondary); margin-bottom: 16px;"></p>
						<div id="nct-tool-params"></div>
						<div style="margin-top: 16px; display: flex; gap: 12px;">
							<button id="nct-tool-execute" class="nct-send-btn" style="flex: 1;">Run Tool</button>
							<button id="nct-tool-cancel" class="nct-send-btn" style="flex: 1; background: rgba(100, 116, 139, 0.5);">Cancel</button>
						</div>
						<div id="nct-tool-result" style="margin-top: 16px; display: none;"></div>
					</div>
				</div>
			</div>

			<!-- Evidence Tab -->
			<div id="nct-tab-evidence" class="nct-tab-content">
			<?php include NCT_PLUGIN_DIR . 'templates/evidence-card-tab.php'; ?>
			</div>

			<!-- Citations Tab -->
			<div id="nct-tab-citations" class="nct-tab-content">
				<div class="nct-citations-container">
					<h3 style="color: var(--nct-text-primary); margin-top: 0; font-size: 18px;">Citation Management</h3>

					<p style="color: var(--nct-text-secondary); margin-bottom: 20px; line-height: 1.6;">
						Manage legal citations with automatic formatting. Supports Bluebook and other citation styles for consistency.
					</p>

					<!-- Citation Form -->
					<div style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 12px; border: 1px solid var(--nct-glass-border); margin-bottom: 20px;">
						<div style="margin-bottom: 16px;">
							<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500;">Case Name</label>
							<input type="text" id="nct-citation-case" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;" placeholder="e.g., Miranda v. Arizona" />
						</div>

						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
							<div>
								<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500;">Court</label>
								<input type="text" id="nct-citation-court" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;" placeholder="e.g., 9th Cir." />
							</div>

							<div>
								<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500;">Year</label>
								<input type="text" id="nct-citation-year" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;" placeholder="e.g., 1966" />
							</div>
						</div>

						<div style="margin-bottom: 16px;">
							<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500;">Citation Style</label>
							<select id="nct-citation-style" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;">
								<option value="bluebook">Bluebook</option>
								<option value="alwd">ALWD</option>
								<option value="mla">MLA</option>
								<option value="apa">APA</option>
							</select>
						</div>

						<button id="nct-add-citation" class="nct-send-btn" style="width: 100%;">Generate Citation</button>
					</div>

					<!-- Citations List -->
					<div id="nct-citations-list" style="margin-top: 20px;">
						<h4 style="color: var(--nct-text-primary); margin-bottom: 12px; font-size: 16px;">Saved Citations</h4>
						<div style="color: var(--nct-text-secondary); font-style: italic;">No citations yet. Generate your first citation above.</div>
					</div>
				</div>
			</div>

			<!-- Outline Tab -->
			<div id="nct-tab-outline" class="nct-tab-content">
				<div class="nct-outline-container">
					<h3 style="color: var(--nct-text-primary); margin-top: 0; font-size: 18px;">Rule-Based Outline Builder</h3>

					<p style="color: var(--nct-text-secondary); margin-bottom: 20px; line-height: 1.6;">
						Build your case outline using rule-based auto-population. Define key points and let the system organize them intelligently.
					</p>

					<!-- Outline Builder -->
					<div style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 12px; border: 1px solid var(--nct-glass-border); margin-bottom: 20px;">
						<div style="margin-bottom: 16px;">
							<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500;">Outline Title</label>
							<input type="text" id="nct-outline-title" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;" placeholder="Enter outline title..." />
						</div>

						<div style="margin-bottom: 16px;">
							<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500;">Key Points (one per line)</label>
							<textarea id="nct-outline-keypoints" rows="6" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px; font-family: monospace; resize: vertical;" placeholder="Enter your key points, one per line..."></textarea>
						</div>

						<div style="margin-bottom: 16px;">
							<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500;">
								<input type="checkbox" id="nct-outline-auto" style="margin-right: 8px;" />
								Auto-populate with related evidence and citations
							</label>
						</div>

						<button id="nct-build-outline" class="nct-send-btn" style="width: 100%;">Build Outline</button>
					</div>

					<!-- Outline Preview -->
					<div id="nct-outline-preview" style="margin-top: 20px; background: rgba(30, 41, 59, 0.3); padding: 20px; border-radius: 12px; border: 1px solid var(--nct-glass-border); min-height: 200px;">
						<h4 style="color: var(--nct-text-primary); margin-top: 0; font-size: 16px;">Outline Preview</h4>
						<div style="color: var(--nct-text-secondary); font-style: italic;">Your outline will appear here...</div>
					</div>
				</div>
			</div>

			<!-- Logs Tab -->
			<div id="nct-tab-logs" class="nct-tab-content">
				<div class="nct-logs-container">
					<h3 style="color: var(--nct-text-primary); margin-top: 0; font-size: 18px;">System Logs</h3>

					<p style="color: var(--nct-text-secondary); margin-bottom: 20px; line-height: 1.6;">
						View real-time system logs and activity. Useful for debugging and tracking operations.
					</p>

					<div id="nct-logs-container" style="background: rgba(15, 23, 42, 0.6); padding: 16px; border-radius: 12px; border: 1px solid var(--nct-glass-border); max-height: 400px; overflow-y: auto; font-family: 'Monaco', 'Menlo', monospace; font-size: 12px;">
						<div class="nct-log-entry info">
							<span class="nct-log-timestamp">[<?php echo esc_html( gmdate( 'H:i:s' ) ); ?>]</span>
							<span class="nct-log-message">Ninth Circuit Tools initialized</span>
						</div>
					</div>

					<div style="margin-top: 16px; display: flex; gap: 12px;">
						<button id="nct-clear-logs" class="nct-send-btn" style="flex: 1;">Clear Logs</button>
						<button id="nct-export-logs" class="nct-send-btn" style="flex: 1;">Export Logs</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
// Additional functionality for Evidence, Citations, and Outline tabs
jQuery(document).ready(function($) {
	// Evidence: Add evidence button
	$('#nct-add-evidence').on('click', function() {
		const title = $('#nct-evidence-title').val();
		const description = $('#nct-evidence-description').val();
		const tags = $('#nct-evidence-tags').val();

		if (!title) {
			alert('Please enter an evidence title');
			return;
		}

		window.NinthCircuitTools.evidence.collect({
			title: title,
			description: description,
			tags: tags
		}).done(function(response) {
			window.NinthCircuitTools.log('Evidence added: ' + title, 'success');
			// Clear form
			$('#nct-evidence-title, #nct-evidence-description, #nct-evidence-tags').val('');
			// Refresh list (to be implemented)
		}).fail(function() {
			alert('Error adding evidence. Please try again.');
		});
	});

	// Citations: Generate citation button
	$('#nct-add-citation').on('click', function() {
		const caseName = $('#nct-citation-case').val();
		const court = $('#nct-citation-court').val();
		const year = $('#nct-citation-year').val();
		const style = $('#nct-citation-style').val();

		if (!caseName) {
			alert('Please enter a case name');
			return;
		}

		window.NinthCircuitTools.citations.create({
			case_name: caseName,
			court: court,
			year: year,
			style: style
		}).done(function(response) {
			window.NinthCircuitTools.log('Citation generated: ' + caseName, 'success');
			// Clear form
			$('#nct-citation-case, #nct-citation-court, #nct-citation-year').val('');
		}).fail(function() {
			alert('Error generating citation. Please try again.');
		});
	});

	// Outline: Build outline button
	$('#nct-build-outline').on('click', function() {
		const title = $('#nct-outline-title').val();
		const keyPoints = $('#nct-outline-keypoints').val().split('\n').filter(p => p.trim());
		const autopopulate = $('#nct-outline-auto').is(':checked');

		if (!title || keyPoints.length === 0) {
			alert('Please enter a title and at least one key point');
			return;
		}

		window.NinthCircuitTools.outline.build(keyPoints, {
			autopopulate: autopopulate
		}).done(function(response) {
			window.NinthCircuitTools.log('Outline built: ' + title, 'success');
			$('#nct-outline-preview').html('<h4 style="color: var(--nct-text-primary); margin-top: 0;">Outline Preview</h4>' + response.html);
		}).fail(function() {
			alert('Error building outline. Please try again.');
		});
	});

	// Logs: Clear logs button
	$('#nct-clear-logs').on('click', function() {
		$('#nct-logs-container').html('');
		window.NinthCircuitTools.log('Logs cleared', 'info');
	});

	// Logs: Export logs button
	$('#nct-export-logs').on('click', function() {
		const logs = window.NinthCircuitTools.state.logs;
		const blob = new Blob([JSON.stringify(logs, null, 2)], { type: 'application/json' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = 'nct-logs-' + Date.now() + '.json';
		a.click();
		URL.revokeObjectURL(url);
		window.NinthCircuitTools.log('Logs exported', 'success');
	});

	// ===== MICRO-TOOLS SYSTEM =====
	let allTools = {};
	let currentTool = null;

	// Load tools on init
	function loadTools() {
		$.ajax({
			url: nctData.restUrl + 'tools',
			method: 'GET',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nctData.nonce);
			},
			success: function(response) {
				if (response.success && response.tools) {
					allTools = response.tools;
					renderTools();
					window.NinthCircuitTools.log('Loaded ' + Object.keys(allTools).length + ' micro-tools', 'success');
				}
			},
			error: function() {
				$('#nct-tools-grid').html('<div style="color: var(--nct-text-secondary); padding: 20px; text-align: center;">Failed to load tools</div>');
			}
		});
	}

	// Render tools grid
	function renderTools(category = 'all') {
		const $grid = $('#nct-tools-grid');
		$grid.empty();

		const filteredTools = Object.keys(allTools).filter(id => {
			const tool = allTools[id];
			return category === 'all' || tool.category === category;
		});

		if (filteredTools.length === 0) {
			$grid.html('<div style="color: var(--nct-text-secondary); padding: 20px; text-align: center;">No tools in this category</div>');
			return;
		}

		filteredTools.forEach(id => {
			const tool = allTools[id];
			const $card = $('<div>').addClass('nct-tool-card').attr('data-tool-id', id).css({
				'padding': '16px',
				'background': 'rgba(30, 41, 59, 0.4)',
				'border': '1px solid var(--nct-glass-border)',
				'border-radius': '10px',
				'cursor': 'pointer',
				'transition': 'all 0.2s ease'
			}).html(`
				<div style="font-size: 32px; margin-bottom: 8px;">${tool.icon}</div>
				<div style="font-weight: 600; color: var(--nct-text-primary); margin-bottom: 4px; font-size: 14px;">${tool.name}</div>
				<div style="color: var(--nct-text-secondary); font-size: 12px; line-height: 1.4;">${tool.description}</div>
			`);

			$card.on('mouseenter', function() {
				$(this).css({
					'background': 'rgba(59, 130, 246, 0.2)',
					'border-color': 'var(--nct-glow-primary)',
					'transform': 'translateY(-2px)',
					'box-shadow': '0 4px 12px rgba(59, 130, 246, 0.3)'
				});
			}).on('mouseleave', function() {
				$(this).css({
					'background': 'rgba(30, 41, 59, 0.4)',
					'border-color': 'var(--nct-glass-border)',
					'transform': 'translateY(0)',
					'box-shadow': 'none'
				});
			}).on('click', function() {
				showToolExecutor(id);
			});

			$grid.append($card);
		});
	}

	// Show tool executor
	function showToolExecutor(toolId) {
		const tool = allTools[toolId];
		currentTool = toolId;

		$('#nct-tool-name').text(tool.icon + ' ' + tool.name);
		$('#nct-tool-description').text(tool.description);

		// Build params form
		const $paramsContainer = $('#nct-tool-params');
		$paramsContainer.empty();

		if (tool.params && Object.keys(tool.params).length > 0) {
			Object.keys(tool.params).forEach(paramName => {
				const param = tool.params[paramName];
				const $paramGroup = $('<div>').css('margin-bottom', '16px');

				$paramGroup.append(
					$('<label>').text(param.description || paramName).css({
						'display': 'block',
						'color': 'var(--nct-text-primary)',
						'margin-bottom': '8px',
						'font-weight': '500',
						'font-size': '14px'
					})
				);

				let $input;
				if (param.type === 'array') {
					$input = $('<textarea>').attr({
						'name': paramName,
						'placeholder': 'One item per line',
						'rows': 3
					});
				} else {
					$input = $('<input>').attr({
						'type': param.type === 'integer' ? 'number' : 'text',
						'name': paramName,
						'placeholder': param.default || ''
					});
				}

				$input.css({
					'width': '100%',
					'padding': '10px',
					'background': 'rgba(15, 23, 42, 0.6)',
					'border': '1px solid var(--nct-glass-border)',
					'border-radius': '8px',
					'color': 'var(--nct-text-primary)',
					'font-size': '14px'
				});

				if (!param.required) {
					$paramGroup.append($('<small>').text('Optional').css({
						'color': 'var(--nct-text-secondary)',
						'margin-left': '8px',
						'font-size': '12px'
					}));
				}

				$paramGroup.append($input);
				$paramsContainer.append($paramGroup);
			});
		} else {
			$paramsContainer.html('<div style="color: var(--nct-text-secondary); font-style: italic;">No parameters required</div>');
		}

		$('#nct-tool-result').hide();
		$('#nct-tool-executor').slideDown();
		window.NinthCircuitTools.log('Opened tool: ' + tool.name, 'info');
	}

	// Execute tool
	$('#nct-tool-execute').on('click', function() {
		if (!currentTool) return;

		const params = {};
		$('#nct-tool-params input, #nct-tool-params textarea').each(function() {
			const name = $(this).attr('name');
			let value = $(this).val();

			if ($(this).is('textarea') && allTools[currentTool].params[name].type === 'array') {
				value = value.split('\n').filter(v => v.trim());
			}

			params[name] = value;
		});

		$('#nct-tool-execute').prop('disabled', true).text('Running...');

		$.ajax({
			url: nctData.restUrl + 'tools/execute',
			method: 'POST',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nctData.nonce);
			},
			data: {
				tool_id: currentTool,
				params: params
			},
			success: function(response) {
				displayToolResult(response);
				window.NinthCircuitTools.log('Tool executed: ' + allTools[currentTool].name, response.success ? 'success' : 'error');
			},
			error: function(xhr) {
				displayToolResult({
					success: false,
					error: 'Failed to execute tool'
				});
			},
			complete: function() {
				$('#nct-tool-execute').prop('disabled', false).text('Run Tool');
			}
		});
	});

	// Display tool result
	function displayToolResult(result) {
		const $resultContainer = $('#nct-tool-result');
		$resultContainer.empty();

		if (result.success) {
			$resultContainer.append(
				$('<div>').css({
					'padding': '12px',
					'background': 'rgba(16, 185, 129, 0.1)',
					'border': '1px solid rgba(16, 185, 129, 0.3)',
					'border-radius': '8px',
					'color': 'var(--nct-text-primary)',
					'margin-bottom': '12px'
				}).html('<strong>✓ Success:</strong> ' + (result.message || 'Tool executed successfully'))
			);

			// Show result data
			if (result.file || result.files || result.data || result.facts) {
				const $data = $('<div>').css({
					'padding': '12px',
					'background': 'rgba(15, 23, 42, 0.6)',
					'border-radius': '8px',
					'font-family': 'monospace',
					'font-size': '12px',
					'color': 'var(--nct-text-secondary)',
					'white-space': 'pre-wrap',
					'max-height': '300px',
					'overflow-y': 'auto'
				}).text(JSON.stringify(result, null, 2));

				$resultContainer.append($data);
			}
		} else {
			$resultContainer.append(
				$('<div>').css({
					'padding': '12px',
					'background': 'rgba(239, 68, 68, 0.1)',
					'border': '1px solid rgba(239, 68, 68, 0.3)',
					'border-radius': '8px',
					'color': '#ef4444'
				}).html('<strong>✗ Error:</strong> ' + (result.error || 'Unknown error'))
			);
		}

		$resultContainer.slideDown();
	}

	// Close tool executor
	$('#nct-tool-close, #nct-tool-cancel').on('click', function() {
		$('#nct-tool-executor').slideUp();
		currentTool = null;
	});

	// Category filters
	$('.nct-category-filter').on('click', function() {
		$('.nct-category-filter').removeClass('active').css({
			'background': 'rgba(30, 41, 59, 0.5)',
			'border-color': 'var(--nct-glass-border)',
			'color': 'var(--nct-text-secondary)'
		});

		$(this).addClass('active').css({
			'background': 'rgba(59, 130, 246, 0.2)',
			'border-color': 'var(--nct-glow-primary)',
			'color': 'var(--nct-text-primary)'
		});

		const category = $(this).data('category');
		renderTools(category);
	});

	// Load tools when Tools tab is first opened
	let toolsLoaded = false;
	$(document).on('click', '[data-tab="tools"]', function() {
		if (!toolsLoaded) {
			loadTools();
			toolsLoaded = true;
		}
	});
});
</script>
