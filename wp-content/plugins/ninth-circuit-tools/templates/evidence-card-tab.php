<?php
/**
 * Evidence Card Tab Template
 *
 * @package NinthCircuitTools
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="nct-evidence-container">
	<h3 style="color: var(--nct-text-primary); margin-top: 0; font-size: 18px;">Evidence Cards</h3>

	<p style="color: var(--nct-text-secondary); margin-bottom: 20px; line-height: 1.6;">
		Create evidence cards linked to UIDs. Each card can have up to 3 UIDs and includes case law, significance, and source information.
	</p>

	<!-- UID Builder -->
	<div style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 12px; border: 1px solid var(--nct-glass-border); margin-bottom: 20px;">
		<h4 style="color: var(--nct-text-primary); margin-top: 0; margin-bottom: 16px; font-size: 16px;">UID Builder (up to 3 UIDs)</h4>

		<div id="nct-uid-builder">
			<!-- UID 1 -->
			<div class="nct-uid-row" data-uid-index="1" style="margin-bottom: 16px; padding: 12px; background: rgba(15, 23, 42, 0.4); border-radius: 8px;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<strong style="color: var(--nct-text-primary); font-size: 13px;">UID #1</strong>
					<span class="nct-uid-display" data-uid-index="1" style="color: var(--nct-glow-primary); font-family: monospace; font-size: 14px; font-weight: bold;">---</span>
				</div>
				<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
					<select class="nct-uid-claim" data-uid-index="1" style="width: 100%; padding: 8px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 6px; color: var(--nct-text-primary); font-size: 12px;">
						<option value="">Claim</option>
					</select>
					<select class="nct-uid-element" data-uid-index="1" style="width: 100%; padding: 8px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 6px; color: var(--nct-text-primary); font-size: 12px;" disabled>
						<option value="">Element</option>
					</select>
					<select class="nct-uid-defendant" data-uid-index="1" style="width: 100%; padding: 8px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 6px; color: var(--nct-text-primary); font-size: 12px;">
						<option value="">Defendant</option>
					</select>
				</div>
			</div>

			<!-- Add UID Buttons -->
			<div style="display: flex; gap: 8px;">
				<button id="nct-add-uid-2" class="nct-add-uid-btn" style="flex: 1; padding: 8px; background: rgba(59, 130, 246, 0.2); border: 1px solid var(--nct-glow-primary); border-radius: 6px; color: var(--nct-text-primary); cursor: pointer; font-size: 12px;">+ Add UID #2</button>
				<button id="nct-add-uid-3" class="nct-add-uid-btn" style="flex: 1; padding: 8px; background: rgba(59, 130, 246, 0.2); border: 1px solid var(--nct-glow-primary); border-radius: 6px; color: var(--nct-text-primary); cursor: pointer; font-size: 12px; display: none;">+ Add UID #3</button>
			</div>
		</div>
	</div>

	<!-- Evidence Card Form -->
	<div style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 12px; border: 1px solid var(--nct-glass-border); margin-bottom: 20px;">
		<h4 style="color: var(--nct-text-primary); margin-top: 0; margin-bottom: 16px; font-size: 16px;">Evidence Card Details</h4>

		<div style="margin-bottom: 16px;">
			<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">Claim</label>
			<textarea id="nct-card-claim" rows="2" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px; resize: vertical;" placeholder="What is being claimed?"></textarea>
		</div>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
			<div>
				<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">Evidence Date</label>
				<input type="date" id="nct-card-date" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;" />
			</div>
			<div>
				<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">Source</label>
				<input type="text" id="nct-card-source" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;" placeholder="e.g., Deposition, Email" />
			</div>
		</div>

		<div style="margin-bottom: 16px;">
			<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">Description</label>
			<textarea id="nct-card-description" rows="3" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px; resize: vertical;" placeholder="Describe the evidence..."></textarea>
		</div>

		<div style="margin-bottom: 16px;">
			<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">Significance</label>
			<textarea id="nct-card-significance" rows="3" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px; resize: vertical;" placeholder="Why is this evidence significant?"></textarea>
		</div>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
			<div>
				<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">Page Number</label>
				<input type="number" id="nct-card-page" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;" placeholder="Page #" />
			</div>
			<div>
				<label style="display: block; color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500; font-size: 13px;">Source File</label>
				<input type="text" id="nct-card-file" style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 8px; color: var(--nct-text-primary); font-size: 14px;" placeholder="Filename" />
			</div>
		</div>

		<button id="nct-create-evidence-card" class="nct-send-btn" style="width: 100%;">Create Evidence Card</button>
	</div>

	<!-- Evidence Cards List -->
	<div id="nct-evidence-cards-list" style="margin-top: 20px;">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
			<h4 style="color: var(--nct-text-primary); margin: 0; font-size: 16px;">Evidence Cards</h4>
			<button id="nct-export-cards-csv" class="nct-send-btn" style="padding: 6px 12px; font-size: 12px;">Export CSV</button>
		</div>
		<div id="nct-cards-container" style="color: var(--nct-text-secondary); font-style: italic;">No evidence cards yet. Create your first card above.</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// ===== UID REGISTRY DATA =====
	const uidRegistry = {
		claims: <?php echo wp_json_encode( NCT_UID_Registry::get_claims() ); ?>,
		defendants: <?php echo wp_json_encode( NCT_UID_Registry::get_defendants() ); ?>,
		elements: <?php
		// Build elements map for all claims
		$elements_map = array();
		for ( $i = 1; $i <= 14; $i++ ) {
			$elements_map[ $i ] = NCT_UID_Registry::get_elements( $i );
		}
		echo wp_json_encode( $elements_map );
		?>
	};

	// ===== UID BUILDER LOGIC =====
	let activeUIDs = [1]; // Track which UID rows are active

	// Initialize UID Builder
	function initUIDBuilder() {
		// Populate Claims dropdown for UID #1
		const $claimSelect = $('.nct-uid-claim[data-uid-index="1"]');
		Object.keys(uidRegistry.claims).forEach(num => {
			$claimSelect.append(`<option value="${num}">${num}. ${uidRegistry.claims[num]}</option>`);
		});

		// Populate Defendants dropdown for UID #1
		const $defendantSelect = $('.nct-uid-defendant[data-uid-index="1"]');
		$defendantSelect.append(`<option value="0">0. Group/Multiple</option>`);
		Object.keys(uidRegistry.defendants).forEach(num => {
			$defendantSelect.append(`<option value="${num}">${num}. ${uidRegistry.defendants[num]}</option>`);
		});
	}

	// Handle Claim selection
	$(document).on('change', '.nct-uid-claim', function() {
		const index = $(this).data('uid-index');
		const claimNum = $(this).val();
		const $elementSelect = $(`.nct-uid-element[data-uid-index="${index}"]`);

		if (claimNum) {
			// Populate elements for this claim
			$elementSelect.empty().append('<option value="">Element</option>').prop('disabled', false);
			const elements = uidRegistry.elements[claimNum] || {};
			Object.keys(elements).forEach(num => {
				$elementSelect.append(`<option value="${num}">${num}. ${elements[num]}</option>`);
			});
		} else {
			$elementSelect.empty().append('<option value="">Element</option>').prop('disabled', true);
		}

		updateUID(index);
	});

	// Handle Element selection
	$(document).on('change', '.nct-uid-element', function() {
		const index = $(this).data('uid-index');
		updateUID(index);
	});

	// Handle Defendant selection
	$(document).on('change', '.nct-uid-defendant', function() {
		const index = $(this).data('uid-index');
		updateUID(index);
	});

	// Update UID display
	function updateUID(index) {
		const claim = $(`.nct-uid-claim[data-uid-index="${index}"]`).val();
		const element = $(`.nct-uid-element[data-uid-index="${index}"]`).val();
		const defendant = $(`.nct-uid-defendant[data-uid-index="${index}"]`).val();

		if (claim && element && defendant !== '') {
			const elementDigit = parseInt(element) / 10;
			const uid = `${claim}${elementDigit}${defendant}`;
			$(`.nct-uid-display[data-uid-index="${index}"]`).text(uid);
		} else {
			$(`.nct-uid-display[data-uid-index="${index}"]`).text('---');
		}
	}

	// Add UID #2
	$('#nct-add-uid-2').on('click', function() {
		addUIDRow(2);
		$(this).hide();
		$('#nct-add-uid-3').show();
		activeUIDs.push(2);
	});

	// Add UID #3
	$('#nct-add-uid-3').on('click', function() {
		addUIDRow(3);
		$(this).hide();
		activeUIDs.push(3);
	});

	// Add a new UID row
	function addUIDRow(index) {
		const $newRow = $(`
			<div class="nct-uid-row" data-uid-index="${index}" style="margin-bottom: 16px; padding: 12px; background: rgba(15, 23, 42, 0.4); border-radius: 8px;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<strong style="color: var(--nct-text-primary); font-size: 13px;">UID #${index}</strong>
					<div style="display: flex; gap: 8px; align-items: center;">
						<span class="nct-uid-display" data-uid-index="${index}" style="color: var(--nct-glow-primary); font-family: monospace; font-size: 14px; font-weight: bold;">---</span>
						<button class="nct-remove-uid" data-uid-index="${index}" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px;">Remove</button>
					</div>
				</div>
				<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
					<select class="nct-uid-claim" data-uid-index="${index}" style="width: 100%; padding: 8px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 6px; color: var(--nct-text-primary); font-size: 12px;">
						<option value="">Claim</option>
					</select>
					<select class="nct-uid-element" data-uid-index="${index}" style="width: 100%; padding: 8px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 6px; color: var(--nct-text-primary); font-size: 12px;" disabled>
						<option value="">Element</option>
					</select>
					<select class="nct-uid-defendant" data-uid-index="${index}" style="width: 100%; padding: 8px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--nct-glass-border); border-radius: 6px; color: var(--nct-text-primary); font-size: 12px;">
						<option value="">Defendant</option>
					</select>
				</div>
			</div>
		`);

		$('#nct-uid-builder').prepend($newRow);

		// Populate dropdowns
		const $claimSelect = $newRow.find('.nct-uid-claim');
		Object.keys(uidRegistry.claims).forEach(num => {
			$claimSelect.append(`<option value="${num}">${num}. ${uidRegistry.claims[num]}</option>`);
		});

		const $defendantSelect = $newRow.find('.nct-uid-defendant');
		$defendantSelect.append(`<option value="0">0. Group/Multiple</option>`);
		Object.keys(uidRegistry.defendants).forEach(num => {
			$defendantSelect.append(`<option value="${num}">${num}. ${uidRegistry.defendants[num]}</option>`);
		});
	}

	// Remove UID row
	$(document).on('click', '.nct-remove-uid', function() {
		const index = $(this).data('uid-index');
		$(`.nct-uid-row[data-uid-index="${index}"]`).remove();
		activeUIDs = activeUIDs.filter(i => i !== index);

		if (index === 2) {
			$('#nct-add-uid-2').show();
		} else if (index === 3) {
			$('#nct-add-uid-3').show();
		}
	});

	// ===== EVIDENCE CARD CREATION =====
	$('#nct-create-evidence-card').on('click', function() {
		// Gather UIDs
		const uids = [];
		activeUIDs.forEach(index => {
			const uid = $(`.nct-uid-display[data-uid-index="${index}"]`).text();
			if (uid !== '---') {
				uids.push(uid);
			}
		});

		if (uids.length === 0) {
			alert('Please build at least one UID');
			return;
		}

		// Gather form data
		const cardData = {
			uids: uids,
			claim: $('#nct-card-claim').val(),
			date: $('#nct-card-date').val(),
			description: $('#nct-card-description').val(),
			significance: $('#nct-card-significance').val(),
			source: $('#nct-card-source').val(),
			pageNumber: $('#nct-card-page').val() ? parseInt($('#nct-card-page').val()) : null,
			sourceFileName: $('#nct-card-file').val(),
			causeOfActionIds: uids.map(uid => parseInt(uid[0])),
			defendantIds: uids.map(uid => parseInt(uid[2]))
		};

		if (!cardData.claim || !cardData.date || !cardData.description || !cardData.significance) {
			alert('Please fill in all required fields (Claim, Date, Description, Significance)');
			return;
		}

		$(this).prop('disabled', true).text('Creating...');

		$.ajax({
			url: nctData.restUrl + 'evidence-cards',
			method: 'POST',
			contentType: 'application/json',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nctData.nonce);
			},
			data: JSON.stringify(cardData),
			success: function(response) {
				if (response.success) {
					window.NinthCircuitTools.log('Evidence card created: ' + response.id, 'success');
					alert('Evidence card created successfully!');

					// Clear form
					$('#nct-card-claim, #nct-card-description, #nct-card-significance, #nct-card-source, #nct-card-file').val('');
					$('#nct-card-date, #nct-card-page').val('');
					$('.nct-uid-claim, .nct-uid-element, .nct-uid-defendant').val('');
					$('.nct-uid-display').text('---');

					// Reload cards
					loadEvidenceCards();
				} else {
					alert('Error: ' + (response.error || 'Unknown error'));
				}
			},
			error: function() {
				alert('Failed to create evidence card. Please try again.');
			},
			complete: function() {
				$('#nct-create-evidence-card').prop('disabled', false).text('Create Evidence Card');
			}
		});
	});

	// ===== LOAD EVIDENCE CARDS =====
	function loadEvidenceCards() {
		$.ajax({
			url: nctData.restUrl + 'evidence-cards',
			method: 'GET',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nctData.nonce);
			},
			success: function(response) {
				if (response.success && response.cards) {
					displayEvidenceCards(response.cards);
				}
			},
			error: function() {
				$('#nct-cards-container').html('<div style="color: #ef4444;">Failed to load evidence cards</div>');
			}
		});
	}

	// Display evidence cards
	function displayEvidenceCards(cards) {
		if (cards.length === 0) {
			$('#nct-cards-container').html('<div style="color: var(--nct-text-secondary); font-style: italic;">No evidence cards yet. Create your first card above.</div>');
			return;
		}

		const $container = $('#nct-cards-container');
		$container.empty();

		cards.forEach(card => {
			const cardDate = new Date(card.evidence_date);
			const $card = $(`
				<div class="nct-evidence-card" data-card-id="${card.id}" style="background: rgba(30, 41, 59, 0.4); padding: 16px; border-radius: 10px; border: 1px solid var(--nct-glass-border); margin-bottom: 12px;">
					<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
						<div>
							<div style="display: flex; gap: 6px; margin-bottom: 8px;">
								${card.uids.map(uid => `<span style="background: rgba(59, 130, 246, 0.3); padding: 4px 10px; border-radius: 12px; font-family: monospace; font-size: 13px; font-weight: bold; color: var(--nct-glow-primary);">${uid}</span>`).join('')}
							</div>
							<div style="color: var(--nct-text-secondary); font-size: 12px;">
								${cardDate.toLocaleDateString()} • ${card.source || 'No source'}
							</div>
						</div>
						<button class="nct-delete-card" data-card-id="${card.id}" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px;">Delete</button>
					</div>
					<div style="color: var(--nct-text-primary); margin-bottom: 8px; font-weight: 500;">
						${card.claim}
					</div>
					<div style="color: var(--nct-text-secondary); font-size: 13px; margin-bottom: 8px;">
						${card.description}
					</div>
					<div style="background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 6px; border-left: 3px solid rgba(16, 185, 129, 0.5);">
						<strong style="color: var(--nct-text-primary); font-size: 12px;">Significance:</strong>
						<div style="color: var(--nct-text-secondary); font-size: 12px; margin-top: 4px;">${card.significance}</div>
					</div>
				</div>
			`);

			$container.append($card);
		});
	}

	// Delete evidence card
	$(document).on('click', '.nct-delete-card', function() {
		if (!confirm('Are you sure you want to delete this evidence card?')) {
			return;
		}

		const cardId = $(this).data('card-id');

		$.ajax({
			url: nctData.restUrl + 'evidence-cards/' + cardId,
			method: 'DELETE',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nctData.nonce);
			},
			success: function(response) {
				if (response.success) {
					window.NinthCircuitTools.log('Evidence card deleted', 'info');
					loadEvidenceCards();
				} else {
					alert('Failed to delete card');
				}
			}
		});
	});

	// Export CSV
	$('#nct-export-cards-csv').on('click', function() {
		window.open(nctData.restUrl + 'evidence-cards/export/csv?_wpnonce=' + nctData.nonce, '_blank');
		window.NinthCircuitTools.log('Exported evidence cards to CSV', 'success');
	});

	// Initialize on Evidence tab load
	$(document).on('click', '[data-tab="evidence"]', function() {
		if (!window.nctEvidenceInitialized) {
			initUIDBuilder();
			loadEvidenceCards();
			window.nctEvidenceInitialized = true;
		}
	});
});
</script>
