<?php
/**
 * Template Hot Bar Tab
 * Quick-load case law, evidence, positions by code
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="nct-hotbar-container">
	<h3 style="color: var(--nct-text-primary); margin-top: 0; font-size: 18px;">Template Hot Bar</h3>

	<p style="color: var(--nct-text-secondary); margin-bottom: 20px; line-height: 1.6;">
		Quick-load content into templates. Type a code (e.g., 933-CL1) or use the builder below.
	</p>

	<!-- Quick Load Input -->
	<div style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 12px; border: 1px solid var(--nct-glass-border); margin-bottom: 20px;">
		<h4 style="color: var(--nct-text-primary); margin-top: 0; margin-bottom: 16px; font-size: 16px;">Quick Load</h4>

		<div style="display: flex; gap: 12px; margin-bottom: 16px;">
			<input type="text" id="nct-hotbar-code" placeholder="e.g., 933-CL1" style="flex: 1; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px; font-family: monospace;" />
			<button id="nct-hotbar-load" class="nct-send-btn" style="padding: 12px 24px;">Load</button>
		</div>

		<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
			<button class="nct-code-example" data-code="933-CL1" style="padding: 6px 12px; background: rgba(59, 130, 246, 0.2); border: 1px solid var(--nct-glow-primary); border-radius: 6px; color: var(--nct-text-primary); cursor: pointer; font-size: 12px; font-family: monospace;">933-CL1</button>
			<button class="nct-code-example" data-code="111-E1" style="padding: 6px 12px; background: rgba(59, 130, 246, 0.2); border: 1px solid var(--nct-glow-primary); border-radius: 6px; color: var(--nct-text-primary); cursor: pointer; font-size: 12px; font-family: monospace;">111-E1</button>
			<button class="nct-code-example" data-code="234-P" style="padding: 6px 12px; background: rgba(59, 130, 246, 0.2); border: 1px solid var(--nct-glow-primary); border-radius: 6px; color: var(--nct-text-primary); cursor: pointer; font-size: 12px; font-family: monospace;">234-P</button>
			<button class="nct-code-example" data-code="324-DP" style="padding: 6px 12px; background: rgba(59, 130, 246, 0.2); border: 1px solid var(--nct-glow-primary); border-radius: 6px; color: var(--nct-text-primary); cursor: pointer; font-size: 12px; font-family: monospace;">324-DP</button>
		</div>

		<div style="color: var(--nct-text-secondary); font-size: 12px;">
			<strong>Code Format:</strong> UID-TYPE[NUMBER]
			<br>
			<strong>Types:</strong> CL (case law), E (evidence), P (plaintiff), D (defendant), DP (defendant position), ECF (their quote)
		</div>
	</div>

	<!-- Code Builder -->
	<div style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 12px; border: 1px solid var(--nct-glass-border); margin-bottom: 20px;">
		<h4 style="color: var(--nct-text-primary); margin-top: 0; margin-bottom: 16px; font-size: 16px;">Code Builder</h4>

		<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
			<div>
				<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">UID</label>
				<input type="text" id="nct-builder-uid" placeholder="e.g., 933" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px; font-family: monospace;" />
			</div>

			<div>
				<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">Type</label>
				<select id="nct-builder-type" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;">
					<option value="">Select...</option>
					<option value="CL">CL - Case Law</option>
					<option value="E">E - Evidence</option>
					<option value="P">P - Plaintiff Position</option>
					<option value="D">D - Defendant Claim</option>
					<option value="DP">DP - Defendant Position</option>
					<option value="ECF">ECF - Their Quote</option>
				</select>
			</div>

			<div>
				<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">Number (optional)</label>
				<input type="number" id="nct-builder-number" placeholder="1, 2, 3..." style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;" />
			</div>
		</div>

		<div style="display: flex; gap: 12px;">
			<button id="nct-builder-build" class="nct-send-btn" style="flex: 1;">Build Code</button>
			<button id="nct-builder-load" class="nct-send-btn" style="flex: 1;">Build & Load</button>
		</div>

		<div id="nct-builder-output" style="margin-top: 12px; padding: 12px; background: rgba(15, 23, 42, 0.6); border-radius: 8px; font-family: monospace; font-size: 14px; color: var(--nct-glow-primary); display: none;"></div>
	</div>

	<!-- Loaded Content Display -->
	<div id="nct-loaded-content" style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 12px; border: 1px solid var(--nct-glass-border); display: none;">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
			<h4 style="color: var(--nct-text-primary); margin: 0; font-size: 16px;">Loaded Content</h4>
			<div style="display: flex; gap: 8px;">
				<button id="nct-content-copy" class="nct-control-btn" style="padding: 6px 12px; font-size: 12px;">📋 Copy</button>
				<button id="nct-content-insert" class="nct-control-btn" style="padding: 6px 12px; font-size: 12px;">↓ Insert</button>
				<button id="nct-content-close" class="nct-control-btn" style="padding: 6px 12px; font-size: 12px;">✕ Close</button>
			</div>
		</div>

		<div id="nct-content-code" style="margin-bottom: 12px; padding: 8px 12px; background: rgba(59, 130, 246, 0.2); border-radius: 6px; font-family: monospace; font-size: 14px; color: var(--nct-glow-primary);"></div>

		<div id="nct-content-data" style="padding: 16px; background: rgba(15, 23, 42, 0.6); border-radius: 8px; max-height: 400px; overflow-y: auto;"></div>
	</div>

	<!-- UID Content Registry -->
	<div style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 12px; border: 1px solid var(--nct-glass-border); margin-top: 20px;">
		<h4 style="color: var(--nct-text-primary); margin-top: 0; margin-bottom: 16px; font-size: 16px;">UID Registry</h4>

		<div style="margin-bottom: 16px;">
			<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">View All Content for UID:</label>
			<div style="display: flex; gap: 12px;">
				<input type="text" id="nct-registry-uid" placeholder="e.g., 933" style="flex: 1; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px; font-family: monospace;" />
				<button id="nct-registry-view" class="nct-send-btn" style="padding: 10px 24px;">View</button>
			</div>
		</div>

		<div id="nct-registry-output" style="display: none;"></div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Quick Load
	$('#nct-hotbar-load').on('click', function() {
		const code = $('#nct-hotbar-code').val().trim();
		if (!code) {
			alert('Enter a code (e.g., 933-CL1)');
			return;
		}
		loadContent(code);
	});

	// Example codes
	$('.nct-code-example').on('click', function() {
		const code = $(this).data('code');
		$('#nct-hotbar-code').val(code);
		loadContent(code);
	});

	// Code Builder - Build
	$('#nct-builder-build').on('click', function() {
		const code = buildCode();
		if (code) {
			$('#nct-builder-output').text(code).show();
			$('#nct-hotbar-code').val(code);
		}
	});

	// Code Builder - Build & Load
	$('#nct-builder-load').on('click', function() {
		const code = buildCode();
		if (code) {
			$('#nct-builder-output').text(code).show();
			$('#nct-hotbar-code').val(code);
			loadContent(code);
		}
	});

	// Build code from form
	function buildCode() {
		const uid = $('#nct-builder-uid').val().trim();
		const type = $('#nct-builder-type').val();
		const number = $('#nct-builder-number').val();

		if (!uid || !type) {
			alert('Enter UID and select Type');
			return null;
		}

		return number ? `${uid}-${type}${number}` : `${uid}-${type}`;
	}

	// Load content
	function loadContent(code) {
		$.ajax({
			url: nctData.restUrl + 'template/load/' + code,
			method: 'GET',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nctData.nonce);
			},
			success: function(response) {
				if (response.success) {
					displayContent(code, response.content);
					window.NinthCircuitTools.log('Loaded: ' + code, 'success');
				} else {
					alert('Error: ' + response.error);
				}
			},
			error: function() {
				alert('Failed to load content for ' + code);
			}
		});
	}

	// Display loaded content
	function displayContent(code, content) {
		$('#nct-content-code').text(code);
		$('#nct-content-data').html(formatContent(content));
		$('#nct-loaded-content').show();

		// Store for copy/insert
		window.nctLoadedContent = {code: code, content: content};
	}

	// Format content for display
	function formatContent(content) {
		let html = '';

		for (let key in content) {
			const value = content[key];
			html += `<div style="margin-bottom: 16px;">`;
			html += `<div style="color: var(--nct-glow-primary); font-size: 12px; font-weight: 600; margin-bottom: 4px; text-transform: uppercase;">${key}</div>`;
			html += `<div style="color: var(--nct-text-primary); line-height: 1.6;">${typeof value === 'object' ? JSON.stringify(value, null, 2) : value}</div>`;
			html += `</div>`;
		}

		return html;
	}

	// Copy to clipboard
	$('#nct-content-copy').on('click', function() {
		if (!window.nctLoadedContent) return;

		const text = JSON.stringify(window.nctLoadedContent.content, null, 2);
		navigator.clipboard.writeText(text).then(() => {
			alert('Copied to clipboard!');
		});
	});

	// Close content display
	$('#nct-content-close').on('click', function() {
		$('#nct-loaded-content').hide();
	});

	// View UID registry
	$('#nct-registry-view').on('click', function() {
		const uid = $('#nct-registry-uid').val().trim();
		if (!uid) {
			alert('Enter a UID');
			return;
		}

		$.ajax({
			url: nctData.restUrl + 'template/uid/' + uid,
			method: 'GET',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nctData.nonce);
			},
			success: function(response) {
				if (response.success) {
					displayRegistry(uid, response.content);
				}
			},
			error: function() {
				alert('Failed to load registry for UID ' + uid);
			}
		});
	});

	// Display registry
	function displayRegistry(uid, content) {
		if (Object.keys(content).length === 0) {
			$('#nct-registry-output').html(`<div style="color: var(--nct-text-secondary); font-style: italic; padding: 16px; background: rgba(15, 23, 42, 0.6); border-radius: 8px;">No content registered for UID ${uid}</div>`).show();
			return;
		}

		let html = '<div style="background: rgba(15, 23, 42, 0.6); padding: 16px; border-radius: 8px;">';
		html += `<h5 style="color: var(--nct-text-primary); margin-top: 0; margin-bottom: 12px;">Available Codes for ${uid}:</h5>`;
		html += '<div style="display: flex; flex-wrap: wrap; gap: 8px;">';

		for (let key in content) {
			const code = `${uid}-${key}`;
			html += `<button class="nct-registry-code" data-code="${code}" style="padding: 8px 16px; background: rgba(59, 130, 246, 0.2); border: 1px solid var(--nct-glow-primary); border-radius: 6px; color: var(--nct-text-primary); cursor: pointer; font-size: 13px; font-family: monospace;">${code}</button>`;
		}

		html += '</div></div>';
		$('#nct-registry-output').html(html).show();
	}

	// Load from registry buttons
	$(document).on('click', '.nct-registry-code', function() {
		const code = $(this).data('code');
		$('#nct-hotbar-code').val(code);
		loadContent(code);
	});
});
</script>
