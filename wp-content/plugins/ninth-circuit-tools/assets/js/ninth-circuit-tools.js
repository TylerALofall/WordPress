/**
 * Ninth Circuit Tools - JavaScript
 * Handles floating panel interactions, MCP connectivity, and real-time updates
 */

(function($) {
	'use strict';

	const NCT = {
		// State management
		state: {
			isExpanded: false,
			activeTab: 'chat',
			mcpConnected: false,
			logs: [],
			chatMessages: [],
		},

		// Configuration
		config: {
			mcpEndpoint: null,
			restUrl: nctData.restUrl,
			nonce: nctData.nonce,
		},

		/**
		 * Initialize the plugin
		 */
		init: function() {
			console.log('🚀 Ninth Circuit Tools initializing...');

			this.cacheElements();
			this.bindEvents();
			this.initializeTabs();
			this.loadStoredState();
			this.checkMCPConnection();

			console.log('✅ Ninth Circuit Tools ready');
		},

		/**
		 * Cache DOM elements
		 */
		cacheElements: function() {
			this.$panel = $('.nct-floating-panel');
			this.$toggleBtn = $('.nct-toggle-button');
			this.$expandBtn = $('.nct-expand-btn');
			this.$collapseBtn = $('.nct-collapse-btn');
			this.$minimizeBtn = $('.nct-minimize-btn');
			this.$tabBtns = $('.nct-tab-btn');
			this.$tabContents = $('.nct-tab-content');
			this.$chatInput = $('.nct-chat-input');
			this.$sendBtn = $('.nct-send-btn');
			this.$chatMessages = $('.nct-chat-messages');
			this.$mcpStatus = $('.nct-mcp-status');
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Panel expansion/collapse
			this.$toggleBtn.on('click', this.togglePanel.bind(this));
			this.$expandBtn.on('click', this.expandPanel.bind(this));
			this.$collapseBtn.on('click', this.collapsePanel.bind(this));
			this.$minimizeBtn.on('click', this.collapsePanel.bind(this));

			// Tab switching
			this.$tabBtns.on('click', this.switchTab.bind(this));

			// Chat functionality
			this.$sendBtn.on('click', this.sendMessage.bind(this));
			this.$chatInput.on('keypress', function(e) {
				if (e.which === 13 && !e.shiftKey) {
					e.preventDefault();
					this.sendMessage();
				}
			}.bind(this));

			// Keyboard shortcuts
			$(document).on('keydown', this.handleKeyboard.bind(this));

			// Window resize
			$(window).on('resize', this.handleResize.bind(this));

			// Draggable panel (when collapsed)
			this.makePanelDraggable();
		},

		/**
		 * Toggle panel expansion
		 */
		togglePanel: function(e) {
			if (e) e.preventDefault();

			if (this.state.isExpanded) {
				this.collapsePanel();
			} else {
				this.expandPanel();
			}
		},

		/**
		 * Expand panel to fullscreen
		 */
		expandPanel: function(e) {
			if (e) e.preventDefault();

			this.$panel.removeClass('nct-collapsed').addClass('nct-expanded');
			this.state.isExpanded = true;
			this.saveState();

			// Animate smoothly
			this.$panel.css({
				transition: 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)'
			});

			this.log('Panel expanded to fullscreen', 'info');
		},

		/**
		 * Collapse panel to 1/3 size
		 */
		collapsePanel: function(e) {
			if (e) e.preventDefault();

			this.$panel.removeClass('nct-expanded').addClass('nct-collapsed');
			this.state.isExpanded = false;
			this.saveState();

			this.log('Panel collapsed', 'info');
		},

		/**
		 * Initialize tabs
		 */
		initializeTabs: function() {
			// Show first tab by default
			this.$tabContents.first().addClass('active');
			this.$tabBtns.first().addClass('active');
		},

		/**
		 * Switch between tabs
		 */
		switchTab: function(e) {
			e.preventDefault();

			const $clickedTab = $(e.currentTarget);
			const targetTab = $clickedTab.data('tab');

			// Update button states
			this.$tabBtns.removeClass('active');
			$clickedTab.addClass('active');

			// Update content visibility
			this.$tabContents.removeClass('active');
			$(`#nct-tab-${targetTab}`).addClass('active');

			this.state.activeTab = targetTab;
			this.saveState();

			this.log(`Switched to ${targetTab} tab`, 'info');
		},

		/**
		 * Send chat message
		 */
		sendMessage: function() {
			const message = this.$chatInput.val().trim();

			if (!message) {
				return;
			}

			// Add user message to chat
			this.addChatMessage(message, 'user');
			this.$chatInput.val('');

			// Send to MCP server if connected
			if (this.state.mcpConnected) {
				this.sendToMCP(message);
			} else {
				// Fallback to REST API
				this.sendToAPI(message);
			}

			this.log(`Sent message: ${message.substring(0, 50)}...`, 'info');
		},

		/**
		 * Add message to chat interface
		 */
		addChatMessage: function(message, type = 'system') {
			const timestamp = new Date().toLocaleTimeString();
			const messageHtml = `
				<div class="nct-chat-message ${type}">
					<div class="nct-message-content">${this.escapeHtml(message)}</div>
				</div>
			`;

			this.$chatMessages.append(messageHtml);
			this.scrollChatToBottom();

			// Store in state
			this.state.chatMessages.push({
				message: message,
				type: type,
				timestamp: timestamp
			});
		},

		/**
		 * Scroll chat to bottom
		 */
		scrollChatToBottom: function() {
			this.$chatMessages.scrollTop(this.$chatMessages[0].scrollHeight);
		},

		/**
		 * Send message to MCP server
		 */
		sendToMCP: function(message) {
			if (!this.config.mcpEndpoint) {
				this.log('MCP endpoint not configured', 'warning');
				return;
			}

			// This will be implemented with actual MCP protocol
			// For now, we'll use a placeholder
			this.log('MCP message sent (placeholder)', 'info');

			// Simulate response
			setTimeout(() => {
				this.addChatMessage('MCP server functionality coming soon. This is a placeholder response.', 'system');
			}, 500);
		},

		/**
		 * Send message to REST API
		 */
		sendToAPI: function(message) {
			const data = {
				message: message,
				context: this.state.activeTab
			};

			$.ajax({
				url: this.config.restUrl + 'chat',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', this.config.nonce);
				}.bind(this),
				data: data,
				success: function(response) {
					if (response.reply) {
						this.addChatMessage(response.reply, 'system');
					}
				}.bind(this),
				error: function(xhr, status, error) {
					this.log(`API error: ${error}`, 'error');
					this.addChatMessage('Error communicating with server. Please try again.', 'system');
				}.bind(this)
			});
		},

		/**
		 * Check MCP server connection
		 */
		checkMCPConnection: function() {
			$.ajax({
				url: this.config.restUrl + 'mcp/status',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', this.config.nonce);
				}.bind(this),
				success: function(response) {
					this.updateMCPStatus(response.connected, response.endpoint);
				}.bind(this),
				error: function() {
					this.updateMCPStatus(false);
				}.bind(this)
			});

			// Recheck every 30 seconds
			setTimeout(this.checkMCPConnection.bind(this), 30000);
		},

		/**
		 * Update MCP connection status
		 */
		updateMCPStatus: function(connected, endpoint = null) {
			this.state.mcpConnected = connected;
			this.config.mcpEndpoint = endpoint;

			const statusClass = connected ? 'connected' : 'disconnected';
			const statusText = connected ? 'Connected' : 'Disconnected';

			const statusHtml = `
				<span class="nct-status-indicator ${statusClass}"></span>
				<span>MCP Server: ${statusText}</span>
			`;

			this.$mcpStatus.html(statusHtml);

			this.log(`MCP Server ${statusText}`, connected ? 'success' : 'warning');
		},

		/**
		 * Add log entry
		 */
		log: function(message, level = 'info') {
			const timestamp = new Date().toLocaleTimeString();
			const logEntry = {
				message: message,
				level: level,
				timestamp: timestamp
			};

			this.state.logs.push(logEntry);

			// Add to logs tab if it exists
			const $logsContainer = $('#nct-logs-container');
			if ($logsContainer.length) {
				const logHtml = `
					<div class="nct-log-entry ${level}">
						<span class="nct-log-timestamp">[${timestamp}]</span>
						<span class="nct-log-message">${this.escapeHtml(message)}</span>
					</div>
				`;
				$logsContainer.append(logHtml);
				$logsContainer.scrollTop($logsContainer[0].scrollHeight);
			}

			// Console output
			const consoleMethod = level === 'error' ? 'error' :
			                      level === 'warning' ? 'warn' : 'log';
			console[consoleMethod](`[NCT] ${message}`);
		},

		/**
		 * Handle keyboard shortcuts
		 */
		handleKeyboard: function(e) {
			// Ctrl/Cmd + Shift + N: Toggle panel
			if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'N') {
				e.preventDefault();
				this.togglePanel();
			}

			// Escape: Collapse panel
			if (e.key === 'Escape' && this.state.isExpanded) {
				this.collapsePanel();
			}
		},

		/**
		 * Handle window resize
		 */
		handleResize: function() {
			// Ensure panel stays within viewport
			if (this.state.isExpanded) {
				// Already handled by CSS
				return;
			}
		},

		/**
		 * Make panel draggable when collapsed
		 */
		makePanelDraggable: function() {
			let isDragging = false;
			let currentX;
			let currentY;
			let initialX;
			let initialY;
			let xOffset = 0;
			let yOffset = 0;

			this.$panel.on('mousedown', '.nct-panel-header', function(e) {
				if (this.state.isExpanded) return;

				initialX = e.clientX - xOffset;
				initialY = e.clientY - yOffset;
				isDragging = true;
			}.bind(this));

			$(document).on('mousemove', function(e) {
				if (isDragging) {
					e.preventDefault();
					currentX = e.clientX - initialX;
					currentY = e.clientY - initialY;
					xOffset = currentX;
					yOffset = currentY;

					this.$panel.css({
						right: 'auto',
						bottom: 'auto',
						left: (e.clientX - initialX) + 'px',
						top: (e.clientY - initialY) + 'px'
					});
				}
			}.bind(this));

			$(document).on('mouseup', function() {
				isDragging = false;
			});
		},

		/**
		 * Save state to localStorage
		 */
		saveState: function() {
			try {
				localStorage.setItem('nct_panel_state', JSON.stringify({
					isExpanded: this.state.isExpanded,
					activeTab: this.state.activeTab
				}));
			} catch (e) {
				console.warn('Could not save state to localStorage', e);
			}
		},

		/**
		 * Load state from localStorage
		 */
		loadStoredState: function() {
			try {
				const stored = localStorage.getItem('nct_panel_state');
				if (stored) {
					const state = JSON.parse(stored);

					// Don't auto-expand on load (less intrusive)
					// this.state.isExpanded = state.isExpanded;

					if (state.activeTab) {
						this.state.activeTab = state.activeTab;
						$(`.nct-tab-btn[data-tab="${state.activeTab}"]`).trigger('click');
					}
				}
			} catch (e) {
				console.warn('Could not load state from localStorage', e);
			}
		},

		/**
		 * Escape HTML for XSS protection
		 */
		escapeHtml: function(text) {
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, m => map[m]);
		},

		/**
		 * Evidence Collection Methods
		 */
		evidence: {
			collect: function(data) {
				return $.ajax({
					url: NCT.config.restUrl + 'evidence',
					method: 'POST',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', NCT.config.nonce);
					},
					data: data
				});
			},

			list: function(filters = {}) {
				return $.ajax({
					url: NCT.config.restUrl + 'evidence',
					method: 'GET',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', NCT.config.nonce);
					},
					data: filters
				});
			},

			delete: function(id) {
				return $.ajax({
					url: NCT.config.restUrl + 'evidence/' + id,
					method: 'DELETE',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', NCT.config.nonce);
					}
				});
			}
		},

		/**
		 * Citation Management Methods
		 */
		citations: {
			create: function(data) {
				return $.ajax({
					url: NCT.config.restUrl + 'citations',
					method: 'POST',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', NCT.config.nonce);
					},
					data: data
				});
			},

			format: function(citation, style = 'bluebook') {
				return $.ajax({
					url: NCT.config.restUrl + 'citations/format',
					method: 'POST',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', NCT.config.nonce);
					},
					data: {
						citation: citation,
						style: style
					}
				});
			}
		},

		/**
		 * Outline Builder Methods
		 */
		outline: {
			create: function(data) {
				return $.ajax({
					url: NCT.config.restUrl + 'outline',
					method: 'POST',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', NCT.config.nonce);
					},
					data: data
				});
			},

			build: function(keyPoints, rules) {
				return $.ajax({
					url: NCT.config.restUrl + 'outline/build',
					method: 'POST',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', NCT.config.nonce);
					},
					data: {
						keyPoints: keyPoints,
						rules: rules
					}
				});
			}
		}
	};

	// Initialize when document is ready
	$(document).ready(function() {
		NCT.init();
	});

	// Expose NCT globally for external access
	window.NinthCircuitTools = NCT;

})(jQuery);
