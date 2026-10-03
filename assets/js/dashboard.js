/**
 * SEO Inspector ZHS - Interactive Dashboard Controller
 *
 * Handles AJAX/REST live audits, filtering, search, animations,
 * accordion mechanics, print export, and JSON downloads.
 *
 * @package    SEO_Inspector_ZHS
 * @subpackage SEO_Inspector_ZHS/assets/js
 * @author     MD. Ziaul Hasan <https://mdziaulhasan.com/>
 * @license    GPL-2.0+
 */

(function () {
	'use strict';

	// Validate configuration injected via wp_localize_script
	if (typeof window.seoInspectorConfig === 'undefined') {
		console.warn('SEO Inspector: Config object not detected.');
		return;
	}

	const config = window.seoInspectorConfig;

	// Component State
	const state = {
		auditData: config.initialData || null,
		activeCategory: 'all',
		activeStatus: 'all',
		searchQuery: '',
		isAllExpanded: false,
		isAuditing: false,
	};

	// DOM Elements Cache
	let elements = {};

	/**
	 * Initialize Dashboard once DOM is ready.
	 */
	function init() {
		cacheElements();
		bindEvents();

		if (state.auditData) {
			renderDashboard(state.auditData, false);
		} else {
			triggerLiveAudit();
		}
	}

	/**
	 * Cache required DOM nodes.
	 */
	function cacheElements() {
		elements = {
			app: document.getElementById('seo-inspector-app'),
			btnRecheck: document.getElementById('si-btn-recheck'),
			btnExport: document.getElementById('si-btn-export'),
			btnJson: document.getElementById('si-btn-json'),
			btnToggleAll: document.getElementById('si-toggle-all-btn'),
			toggleAllText: document.getElementById('si-toggle-all-text'),
			banner: document.getElementById('si-notification-banner'),
			bannerMsg: document.getElementById('si-notification-msg'),
			lastAuditTime: document.getElementById('si-last-audit-time'),

			// KPIs
			scoreDialProgress: document.getElementById('si-dial-progress'),
			scoreValue: document.getElementById('si-score-value'),
			gradeBadge: document.getElementById('si-grade-badge'),
			gradeDesc: document.getElementById('si-grade-desc'),
			passedCount: document.getElementById('si-passed-count'),
			partialCount: document.getElementById('si-partial-count'),
			failedCount: document.getElementById('si-failed-count'),

			// Categories
			categoryBarsList: document.getElementById('si-category-bars'),

			// Filter Controls
			categoryTabs: document.querySelectorAll('#si-category-tabs .si-tab-btn'),
			statusPills: document.querySelectorAll('#si-status-pills .si-pill-btn'),
			searchInput: document.getElementById('si-search-input'),
			statCards: document.querySelectorAll('.si-kpi-stat-card'),

			// Checks
			checksList: document.getElementById('si-checks-list'),
		};
	}

	/**
	 * Bind event listeners.
	 */
	function bindEvents() {
		// Recheck Audit Button
		if (elements.btnRecheck) {
			elements.btnRecheck.addEventListener('click', function (e) {
				e.preventDefault();
				triggerLiveAudit();
			});
		}

		// Export Report (Print/PDF)
		if (elements.btnExport) {
			elements.btnExport.addEventListener('click', function (e) {
				e.preventDefault();
				exportReport();
			});
		}

		// JSON Export
		if (elements.btnJson) {
			elements.btnJson.addEventListener('click', function (e) {
				e.preventDefault();
				downloadJsonReport();
			});
		}

		// Category Tabs
		if (elements.categoryTabs) {
			elements.categoryTabs.forEach(function (tab) {
				tab.addEventListener('click', function () {
					elements.categoryTabs.forEach(function (t) { t.classList.remove('active'); });
					tab.classList.add('active');
					state.activeCategory = tab.getAttribute('data-category');
					applyFilters();
				});
			});
		}

		// Status Pills
		if (elements.statusPills) {
			elements.statusPills.forEach(function (pill) {
				pill.addEventListener('click', function () {
					elements.statusPills.forEach(function (p) { p.classList.remove('active'); });
					pill.classList.add('active');
					state.activeStatus = pill.getAttribute('data-status');
					applyFilters();
				});
			});
		}

		// Stat Cards Click to Filter
		if (elements.statCards) {
			elements.statCards.forEach(function (card) {
				card.addEventListener('click', function () {
					const targetStatus = card.getAttribute('data-status-filter');
					if (targetStatus) {
						elements.statusPills.forEach(function (p) {
							if (p.getAttribute('data-status') === targetStatus) {
								p.click();
							}
						});
					}
				});
			});
		}

		// Search Input
		if (elements.searchInput) {
			elements.searchInput.addEventListener('input', function (e) {
				state.searchQuery = e.target.value.trim().toLowerCase();
				applyFilters();
			});
		}

		// Toggle All Button
		if (elements.btnToggleAll) {
			elements.btnToggleAll.addEventListener('click', function () {
				state.isAllExpanded = !state.isAllExpanded;
				toggleAllCheckCards(state.isAllExpanded);
			});
		}

		// Delegate Accordion Header Clicks
		if (elements.checksList) {
			elements.checksList.addEventListener('click', function (e) {
				const header = e.target.closest('.si-check-header');
				if (header) {
					const card = header.closest('.si-check-card');
					toggleCardAccordion(card);
				}
			});

			// Keyboard accessibility (Enter/Space on header)
			elements.checksList.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					const header = e.target.closest('.si-check-header');
					if (header) {
						e.preventDefault();
						const card = header.closest('.si-check-card');
						toggleCardAccordion(card);
					}
				}
			});
		}
	}

	/**
	 * Trigger live audit via WP REST API.
	 */
	function triggerLiveAudit() {
		if (state.isAuditing) return;
		state.isAuditing = true;

		// UI Loading State
		setLoadingState(true);
		showNotification(config.i18n.runningAudit || 'Auditing...', 'loading');

		fetch(config.restUrl + 'audit/run', {
			method: 'POST',
			headers: {
				'X-WP-Nonce': config.nonce,
				'Content-Type': 'application/json',
			},
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Server returned HTTP ' + response.status);
				}
				return response.json();
			})
			.then(function (res) {
				if (res && res.success && res.data) {
					state.auditData = res.data;
					renderDashboard(res.data, true);
					showNotification(config.i18n.auditComplete || 'Audit complete!', 'success');
					setTimeout(hideNotification, 3500);
				} else {
					throw new Error(res.message || 'Audit payload malformed');
				}
			})
			.catch(function (err) {
				console.error('SEO Inspector Error:', err);
				showNotification(config.i18n.auditError || 'Audit failed.', 'error');
				setTimeout(hideNotification, 4000);
			})
			.finally(function () {
				state.isAuditing = false;
				setLoadingState(false);
			});
	}

	/**
	 * Toggle loading appearance on action buttons.
	 */
	function setLoadingState(isLoading) {
		if (!elements.btnRecheck) return;

		const label = elements.btnRecheck.querySelector('.si-btn-label');
		const icon = elements.btnRecheck.querySelector('.si-btn-icon');

		if (isLoading) {
			elements.btnRecheck.disabled = true;
			if (label) label.textContent = config.i18n.rechecking || 'Auditing...';
			if (icon) icon.classList.add('si-spin');
		} else {
			elements.btnRecheck.disabled = false;
			if (label) label.textContent = config.i18n.recheckBtn || 'Re-check Audit';
			if (icon) icon.classList.remove('si-spin');
		}
	}

	/**
	 * Render and animate all dashboard components.
	 */
	function renderDashboard(data, animate) {
		if (!data) return;

		// 1. Audit Time
		if (elements.lastAuditTime && data.formatted_date) {
			elements.lastAuditTime.textContent = data.formatted_date;
		}

		// 2. Score Dial Animation
		const score = parseInt(data.score, 10) || 0;
		renderScoreDial(score, animate);

		// 3. Grade Pill & Label
		if (elements.gradeBadge && data.grade) {
			elements.gradeBadge.textContent = 'Grade ' + data.grade;
			elements.gradeBadge.className = 'si-grade-pill grade-' + data.grade.charAt(0).toLowerCase();
		}
		if (elements.gradeDesc && data.grade_label) {
			elements.gradeDesc.textContent = data.grade_label;
		}

		// 4. Counts
		animateCounter(elements.passedCount, data.passed_count || 0, animate);
		animateCounter(elements.partialCount, data.partial_count || 0, animate);
		animateCounter(elements.failedCount, data.failed_count || 0, animate);

		// 5. Category Bars
		if (data.category_scores) {
			renderCategoryBars(data.category_scores);
		}

		// 6. Update Checks List
		if (data.checks && Array.isArray(data.checks)) {
			renderChecksList(data.checks);
		}

		// Re-apply any active filters
		applyFilters();
	}

	/**
	 * Render Circular Score Dial.
	 */
	function renderScoreDial(score, animate) {
		const circumference = 314.159; // 2 * pi * 50
		const targetOffset = circumference * (1 - (score / 100));

		if (elements.scoreDialProgress) {
			// Select stroke color based on score band
			let strokeColor = '#0284c7';
			if (score >= 90) strokeColor = '#10b981';
			else if (score >= 80) strokeColor = '#0284c7';
			else if (score >= 70) strokeColor = '#f59e0b';
			else strokeColor = '#ef4444';

			elements.scoreDialProgress.style.stroke = strokeColor;
			elements.scoreDialProgress.style.strokeDashoffset = targetOffset;
		}

		if (elements.scoreValue) {
			animateCounter(elements.scoreValue, score, animate);
		}
	}

	/**
	 * Animate numeric counter value.
	 */
	function animateCounter(element, targetValue, animate) {
		if (!element) return;
		const target = parseInt(targetValue, 10) || 0;

		if (!animate) {
			element.textContent = target;
			return;
		}

		const duration = 800;
		const start = parseInt(element.textContent, 10) || 0;
		const diff = target - start;
		const startTime = performance.now();

		function update(time) {
			const elapsed = time - startTime;
			const progress = Math.min(elapsed / duration, 1);
			// Ease out quad
			const easeProgress = 1 - (1 - progress) * (1 - progress);
			const current = Math.round(start + (diff * easeProgress));

			element.textContent = current;

			if (progress < 1) {
				requestAnimationFrame(update);
			} else {
				element.textContent = target;
			}
		}

		requestAnimationFrame(update);
	}

	/**
	 * Update Category progress bars and scores.
	 */
	function renderCategoryBars(catScores) {
		const cats = ['usability', 'accessibility', 'seo', 'geo_ai'];
		cats.forEach(function (cat) {
			const data = catScores[cat];
			if (!data) return;

			const scoreEl = document.getElementById('score-val-' + cat);
			const fillEl = document.getElementById('bar-fill-' + cat);

			if (scoreEl) {
				scoreEl.textContent = data.score_10 + '/10';
			}
			if (fillEl) {
				fillEl.style.width = data.percentage + '%';
				// Tint category progress bars
				if (data.percentage >= 80) {
					fillEl.style.background = 'linear-gradient(90deg, #10b981 0%, #34d399 100%)';
				} else if (data.percentage >= 60) {
					fillEl.style.background = 'linear-gradient(90deg, #0284c7 0%, #38bdf8 100%)';
				} else {
					fillEl.style.background = 'linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%)';
				}
			}
		});
	}

	/**
	 * Re-render Checks List items dynamically.
	 */
	function renderChecksList(checks) {
		if (!elements.checksList) return;

		let html = '';

		checks.forEach(function (c) {
			const status = c.status || 'fail';
			const cat = c.category || 'seo';
			const catLabel = c.category_label || cat;
			const impact = c.impact || 'medium';
			const title = escapeHtml(c.title || '');
			const summary = escapeHtml(c.summary || '');
			const details = escapeHtml(c.details || '');
			const rec = escapeHtml(c.recommendation || '');

			let badgeIcon = '';
			if (status === 'pass') {
				badgeIcon = '<span class="si-status-badge pass" title="Passed"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span>';
			} else if (status === 'partial') {
				badgeIcon = '<span class="si-status-badge partial" title="Warning"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></span>';
			} else {
				badgeIcon = '<span class="si-status-badge fail" title="Failed"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg></span>';
			}

			html += `
				<div class="si-check-card status-${status}"
					 data-category="${cat}"
					 data-status="${status}"
					 data-search="${(title + ' ' + summary + ' ' + catLabel).toLowerCase()}">
					<div class="si-check-header" tabindex="0" role="button" aria-expanded="false">
						<div class="si-check-status-col">${badgeIcon}</div>
						<div class="si-check-info-col">
							<div class="si-check-title-row">
								<h3 class="si-check-title">${title}</h3>
								<span class="si-badge-cat cat-${cat}">${catLabel}</span>
								<span class="si-badge-impact impact-${impact}">${impact.charAt(0).toUpperCase() + impact.slice(1)} Impact</span>
							</div>
							<p class="si-check-summary">${summary}</p>
						</div>
						<div class="si-check-toggle-col">
							<svg class="si-chevron-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
								<polyline points="6 9 12 15 18 9"></polyline>
							</svg>
						</div>
					</div>
					<div class="si-check-body" style="display: none;">
						<div class="si-check-detail-grid">
							<div class="si-detail-block findings-block">
								<div class="si-detail-heading">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
									Detected Findings &amp; DOM Evidence
								</div>
								<div class="si-detail-content"><p>${details}</p></div>
							</div>
							<div class="si-detail-block rec-block">
								<div class="si-detail-heading">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
									Actionable Recommendation
								</div>
								<div class="si-detail-content"><p>${rec}</p></div>
							</div>
						</div>
					</div>
				</div>
			`;
		});

		elements.checksList.innerHTML = html;
	}

	/**
	 * Toggle individual accordion card open/close.
	 */
	function toggleCardAccordion(card) {
		if (!card) return;
		const body = card.querySelector('.si-check-body');
		const header = card.querySelector('.si-check-header');
		const isOpen = card.classList.contains('open');

		if (isOpen) {
			card.classList.remove('open');
			if (body) body.style.display = 'none';
			if (header) header.setAttribute('aria-expanded', 'false');
		} else {
			card.classList.add('open');
			if (body) body.style.display = 'block';
			if (header) header.setAttribute('aria-expanded', 'true');
		}
	}

	/**
	 * Expand or collapse all cards.
	 */
	function toggleAllCheckCards(shouldExpand) {
		const cards = elements.checksList.querySelectorAll('.si-check-card');
		cards.forEach(function (card) {
			const body = card.querySelector('.si-check-body');
			const header = card.querySelector('.si-check-header');

			if (shouldExpand) {
				card.classList.add('open');
				if (body) body.style.display = 'block';
				if (header) header.setAttribute('aria-expanded', 'true');
			} else {
				card.classList.remove('open');
				if (body) body.style.display = 'none';
				if (header) header.setAttribute('aria-expanded', 'false');
			}
		});

		if (elements.toggleAllText) {
			elements.toggleAllText.textContent = shouldExpand ? 'Collapse All' : 'Expand All';
		}
	}

	/**
	 * Apply active Category, Status, and Search text filters.
	 */
	function applyFilters() {
		const cards = elements.checksList.querySelectorAll('.si-check-card');
		let visibleCount = 0;

		cards.forEach(function (card) {
			const cardCat = card.getAttribute('data-category');
			const cardStatus = card.getAttribute('data-status');
			const cardSearch = card.getAttribute('data-search') || '';

			const matchCat = (state.activeCategory === 'all' || state.activeCategory === cardCat);
			const matchStatus = (state.activeStatus === 'all' || state.activeStatus === cardStatus);
			const matchSearch = (!state.searchQuery || cardSearch.indexOf(state.searchQuery) !== -1);

			if (matchCat && matchStatus && matchSearch) {
				card.style.display = 'block';
				visibleCount++;
			} else {
				card.style.display = 'none';
			}
		});
	}

	/**
	 * Export clean printable report / Trigger browser print dialog.
	 */
	function exportReport() {
		// Expand all visible cards so full details show on print/PDF
		toggleAllCheckCards(true);
		showNotification(config.i18n.exporting || 'Preparing report preview...', 'info');

		setTimeout(function () {
			hideNotification();
			window.print();
		}, 600);
	}

	/**
	 * Export raw audit payload as a downloadable JSON file.
	 */
	function downloadJsonReport() {
		if (!state.auditData) {
			alert('No audit data available to download.');
			return;
		}

		const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(state.auditData, null, 2));
		const downloadAnchor = document.createElement('a');
		const dateStamp = new Date().toISOString().slice(0, 10);

		downloadAnchor.setAttribute('href', dataStr);
		downloadAnchor.setAttribute('download', 'seo-inspector-audit-' + dateStamp + '.json');
		document.body.appendChild(downloadAnchor);
		downloadAnchor.click();
		downloadAnchor.remove();
	}

	/**
	 * Show notification banner.
	 */
	function showNotification(message, type) {
		if (!elements.banner || !elements.bannerMsg) return;

		elements.bannerMsg.textContent = message;
		elements.banner.style.display = 'flex';

		const icon = elements.banner.querySelector('.si-notification-icon');
		if (icon) {
			if (type === 'loading') {
				icon.innerHTML = '<svg class="si-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>';
				icon.style.color = '#38bdf8';
			} else if (type === 'success') {
				icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
				icon.style.color = '#34d399';
			} else if (type === 'error') {
				icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
				icon.style.color = '#f87171';
			} else {
				icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
				icon.style.color = '#94a3b8';
			}
		}
	}

	/**
	 * Hide notification banner.
	 */
	function hideNotification() {
		if (elements.banner) {
			elements.banner.style.display = 'none';
		}
	}

	/**
	 * Escape HTML entities.
	 */
	function escapeHtml(str) {
		const div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	// Initialize on DOM Ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
