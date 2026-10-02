<?php
/**
 * SEO Inspector ZHS - Dashboard View Template
 *
 * Modern SaaS UI template for the SEO Inspector & Site Audit dashboard.
 *
 * @package    SEO_Inspector_ZHS
 * @subpackage SEO_Inspector_ZHS/templates
 * @author     MD. Ziaul Hasan <https://www.instagram.com/zhsaikot/>
 * @license    GPL-2.0+
 */

defined( 'ABSPATH' ) || exit;

// Retrieve initial pre-calculated data
$initial_audit = $this->engine->run_audit( false );
$overall_score = $initial_audit['score'] ?? 0;
$grade         = $initial_audit['grade'] ?? 'N/A';
$grade_label   = $initial_audit['grade_label'] ?? '';
$passed_count  = $initial_audit['passed_count'] ?? 0;
$partial_count = $initial_audit['partial_count'] ?? 0;
$failed_count  = $initial_audit['failed_count'] ?? 0;
$categories    = $initial_audit['category_scores'] ?? array();
$checks        = $initial_audit['checks'] ?? array();
$site_url      = $initial_audit['site_url'] ?? home_url( '/' );
$last_audit    = ! empty( $initial_audit['formatted_date'] ) ? $initial_audit['formatted_date'] : current_time( 'mysql' );
?>

<div id="seo-inspector-app" class="si-dashboard-wrap">

	<!-- Top Notification Banner (Live Scanning / Errors) -->
	<div id="si-notification-banner" class="si-notification" style="display: none;">
		<div class="si-notification-icon">
			<svg class="si-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
		</div>
		<div class="si-notification-text" id="si-notification-msg"><?php esc_html_e( 'Running live SEO audit...', 'seo-inspector-zhs' ); ?></div>
	</div>

	<!-- SaaS Top Bar Header -->
	<header class="si-topbar">
		<div class="si-topbar-brand">
			<div class="si-brand-icon">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="11" cy="11" r="8"></circle>
					<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
					<path d="M11 8v6"></path>
					<path d="M8 11h6"></path>
				</svg>
			</div>
			<div class="si-brand-info">
				<div class="si-brand-title-row">
					<h1 class="si-brand-title"><?php esc_html_e( 'SEO Inspector &amp; Site Audit', 'seo-inspector-zhs' ); ?></h1>
					<span class="si-badge-version">v1.0.0</span>
				</div>
				<div class="si-brand-meta">
					<span class="si-site-url">
						<span class="si-status-dot online"></span>
						<a href="<?php echo esc_url( $site_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $site_url ); ?></a>
					</span>
					<span class="si-meta-divider">&bull;</span>
					<span class="si-last-scan">
						<?php esc_html_e( 'Last Audited:', 'seo-inspector-zhs' ); ?>
						<strong id="si-last-audit-time"><?php echo esc_html( $last_audit ); ?></strong>
					</span>
				</div>
			</div>
		</div>

		<div class="si-topbar-actions">
			<!-- Re-check Audit Button -->
			<button id="si-btn-recheck" class="si-btn si-btn-primary" type="button" title="<?php esc_attr_e( 'Trigger a fresh live scan of front-end DOM and database', 'seo-inspector-zhs' ); ?>">
				<svg class="si-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
				</svg>
				<span class="si-btn-label"><?php esc_html_e( 'Re-check Audit', 'seo-inspector-zhs' ); ?></span>
			</button>

			<!-- Export Report Button (Print/PDF) -->
			<button id="si-btn-export" class="si-btn si-btn-secondary" type="button" title="<?php esc_attr_e( 'Generate clean printable report / PDF for clients', 'seo-inspector-zhs' ); ?>">
				<svg class="si-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<span class="si-btn-label"><?php esc_html_e( 'Export Report', 'seo-inspector-zhs' ); ?></span>
			</button>

			<!-- Export JSON Button -->
			<button id="si-btn-json" class="si-btn si-btn-outline" type="button" title="<?php esc_attr_e( 'Download structured JSON audit report', 'seo-inspector-zhs' ); ?>">
				<svg class="si-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
					<polyline points="7 10 12 15 17 10"></polyline>
					<line x1="12" y1="15" x2="12" y2="3"></line>
				</svg>
				<span class="si-btn-label"><?php esc_html_e( 'JSON', 'seo-inspector-zhs' ); ?></span>
			</button>

			<!-- Author Profile Attribution -->
			<div class="si-author-badge">
				<a href="https://www.instagram.com/zhsaikot/" target="_blank" rel="noopener noreferrer" class="si-author-link" title="<?php esc_attr_e( 'Lead Developer: MD. Ziaul Hasan', 'seo-inspector-zhs' ); ?>">
					<div class="si-author-avatar">ZH</div>
					<div class="si-author-text">
						<span class="si-author-role"><?php esc_html_e( 'Plugin Author', 'seo-inspector-zhs' ); ?></span>
						<span class="si-author-name">
							MD. Ziaul Hasan
							<svg class="si-verified-check" width="13" height="13" viewBox="0 0 24 24" fill="#0ea5e9"><circle cx="12" cy="12" r="10"></circle><polyline points="8 12 11 15 16 9" stroke="#ffffff" stroke-width="2.5" fill="none"></polyline></svg>
						</span>
					</div>
				</a>
			</div>
		</div>
	</header>

	<!-- KPI Summary Cards Grid -->
	<section class="si-kpi-grid">
		<!-- KPI 1: Overall Audit Score & Donut Dial -->
		<div class="si-kpi-card si-kpi-score-card">
			<div class="si-score-dial-wrap">
				<svg class="si-score-dial" viewBox="0 0 120 120">
					<circle class="si-dial-bg" cx="60" cy="60" r="50"></circle>
					<circle id="si-dial-progress" class="si-dial-stroke" cx="60" cy="60" r="50" style="stroke-dashoffset: <?php echo esc_attr( 314.159 * ( 1 - ( $overall_score / 100 ) ) ); ?>;"></circle>
				</svg>
				<div class="si-score-number-overlay">
					<span id="si-score-value" class="si-score-digit"><?php echo esc_html( $overall_score ); ?></span>
					<span class="si-score-percent">%</span>
				</div>
			</div>
			<div class="si-kpi-score-content">
				<span class="si-kpi-label"><?php esc_html_e( 'Overall Audit Score', 'seo-inspector-zhs' ); ?></span>
				<div class="si-grade-pill-row">
					<span id="si-grade-badge" class="si-grade-pill grade-<?php echo esc_attr( strtolower( substr( $grade, 0, 1 ) ) ); ?>">
						<?php echo esc_html( 'Grade ' . $grade ); ?>
					</span>
				</div>
				<p id="si-grade-desc" class="si-grade-desc"><?php echo esc_html( $grade_label ); ?></p>
			</div>
		</div>

		<!-- KPI 2: Passed Checks -->
		<div class="si-kpi-card si-kpi-stat-card pass-stat" data-status-filter="pass" role="button" tabindex="0" title="<?php esc_attr_e( 'Click to filter passed checks', 'seo-inspector-zhs' ); ?>">
			<div class="si-stat-header">
				<span class="si-stat-icon pass">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
				</span>
				<span class="si-badge-pill pass-pill"><?php esc_html_e( 'PASS', 'seo-inspector-zhs' ); ?></span>
			</div>
			<div class="si-stat-body">
				<div class="si-stat-count" id="si-passed-count"><?php echo esc_html( $passed_count ); ?></div>
				<div class="si-stat-title"><?php esc_html_e( 'Passed Checks', 'seo-inspector-zhs' ); ?></div>
				<div class="si-stat-subtext"><?php esc_html_e( 'Optimal compliance with SEO standards', 'seo-inspector-zhs' ); ?></div>
			</div>
		</div>

		<!-- KPI 3: Partial / Warnings -->
		<div class="si-kpi-card si-kpi-stat-card partial-stat" data-status-filter="partial" role="button" tabindex="0" title="<?php esc_attr_e( 'Click to filter partial warnings', 'seo-inspector-zhs' ); ?>">
			<div class="si-stat-header">
				<span class="si-stat-icon partial">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
				</span>
				<span class="si-badge-pill partial-pill"><?php esc_html_e( 'PARTIAL', 'seo-inspector-zhs' ); ?></span>
			</div>
			<div class="si-stat-body">
				<div class="si-stat-count" id="si-partial-count"><?php echo esc_html( $partial_count ); ?></div>
				<div class="si-stat-title"><?php esc_html_e( 'Warnings / Partial', 'seo-inspector-zhs' ); ?></div>
				<div class="si-stat-subtext"><?php esc_html_e( 'Minor tweaks required for full score', 'seo-inspector-zhs' ); ?></div>
			</div>
		</div>

		<!-- KPI 4: Failed Checks -->
		<div class="si-kpi-card si-kpi-stat-card fail-stat" data-status-filter="fail" role="button" tabindex="0" title="<?php esc_attr_e( 'Click to filter failed checks', 'seo-inspector-zhs' ); ?>">
			<div class="si-stat-header">
				<span class="si-stat-icon fail">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
				</span>
				<span class="si-badge-pill fail-pill"><?php esc_html_e( 'FAIL', 'seo-inspector-zhs' ); ?></span>
			</div>
			<div class="si-stat-body">
				<div class="si-stat-count" id="si-failed-count"><?php echo esc_html( $failed_count ); ?></div>
				<div class="si-stat-title"><?php esc_html_e( 'Critical Issues', 'seo-inspector-zhs' ); ?></div>
				<div class="si-stat-subtext"><?php esc_html_e( 'High-impact bottlenecks to resolve', 'seo-inspector-zhs' ); ?></div>
			</div>
		</div>

		<!-- KPI 5: Category Mini-Scores Overview Card -->
		<div class="si-kpi-card si-kpi-categories-card">
			<div class="si-categories-header">
				<span class="si-kpi-label"><?php esc_html_e( 'Category Breakdown', 'seo-inspector-zhs' ); ?></span>
				<span class="si-badge-pill info-pill">4 Core Pillars</span>
			</div>

			<div class="si-category-bars-list" id="si-category-bars">
				<!-- Usability -->
				<?php
				$usab = $categories['usability'] ?? array( 'score_10' => 0, 'percentage' => 0 );
				$acce = $categories['accessibility'] ?? array( 'score_10' => 0, 'percentage' => 0 );
				$seop = $categories['seo'] ?? array( 'score_10' => 0, 'percentage' => 0 );
				$geop = $categories['geo_ai'] ?? array( 'score_10' => 0, 'percentage' => 0 );
				?>
				<div class="si-cat-bar-item" data-category="usability">
					<div class="si-cat-bar-meta">
						<span class="si-cat-name"><?php esc_html_e( 'Usability', 'seo-inspector-zhs' ); ?></span>
						<span class="si-cat-score" id="score-val-usability"><?php echo esc_html( $usab['score_10'] ); ?>/10</span>
					</div>
					<div class="si-progress-track">
						<div class="si-progress-fill" id="bar-fill-usability" style="width: <?php echo esc_attr( $usab['percentage'] ); ?>%;"></div>
					</div>
				</div>

				<!-- Accessibility -->
				<div class="si-cat-bar-item" data-category="accessibility">
					<div class="si-cat-bar-meta">
						<span class="si-cat-name"><?php esc_html_e( 'Accessibility', 'seo-inspector-zhs' ); ?></span>
						<span class="si-cat-score" id="score-val-accessibility"><?php echo esc_html( $acce['score_10'] ); ?>/10</span>
					</div>
					<div class="si-progress-track">
						<div class="si-progress-fill" id="bar-fill-accessibility" style="width: <?php echo esc_attr( $acce['percentage'] ); ?>%;"></div>
					</div>
				</div>

				<!-- SEO -->
				<div class="si-cat-bar-item" data-category="seo">
					<div class="si-cat-bar-meta">
						<span class="si-cat-name"><?php esc_html_e( 'SEO Essentials', 'seo-inspector-zhs' ); ?></span>
						<span class="si-cat-score" id="score-val-seo"><?php echo esc_html( $seop['score_10'] ); ?>/10</span>
					</div>
					<div class="si-progress-track">
						<div class="si-progress-fill" id="bar-fill-seo" style="width: <?php echo esc_attr( $seop['percentage'] ); ?>%;"></div>
					</div>
				</div>

				<!-- GEO & AI Readiness -->
				<div class="si-cat-bar-item" data-category="geo_ai">
					<div class="si-cat-bar-meta">
						<span class="si-cat-name"><?php esc_html_e( 'GEO &amp; AI-Readiness', 'seo-inspector-zhs' ); ?></span>
						<span class="si-cat-score" id="score-val-geo_ai"><?php echo esc_html( $geop['score_10'] ); ?>/10</span>
					</div>
					<div class="si-progress-track">
						<div class="si-progress-fill" id="bar-fill-geo_ai" style="width: <?php echo esc_attr( $geop['percentage'] ); ?>%;"></div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Main Audit Checks Section -->
	<section class="si-checks-section">
		<!-- Toolbar & Filters -->
		<div class="si-toolbar">
			<!-- Category Tabs -->
			<div class="si-filter-tabs" id="si-category-tabs">
				<button class="si-tab-btn active" data-category="all" type="button">
					<?php esc_html_e( 'All Checks', 'seo-inspector-zhs' ); ?>
					<span class="si-tab-count" id="tab-count-all">17</span>
				</button>
				<button class="si-tab-btn" data-category="usability" type="button">
					<?php esc_html_e( 'Usability', 'seo-inspector-zhs' ); ?>
					<span class="si-tab-count" id="tab-count-usability">5</span>
				</button>
				<button class="si-tab-btn" data-category="accessibility" type="button">
					<?php esc_html_e( 'Accessibility', 'seo-inspector-zhs' ); ?>
					<span class="si-tab-count" id="tab-count-accessibility">4</span>
				</button>
				<button class="si-tab-btn" data-category="seo" type="button">
					<?php esc_html_e( 'SEO Essentials', 'seo-inspector-zhs' ); ?>
					<span class="si-tab-count" id="tab-count-seo">6</span>
				</button>
				<button class="si-tab-btn" data-category="geo_ai" type="button">
					<?php esc_html_e( 'GEO &amp; AI-Readiness', 'seo-inspector-zhs' ); ?>
					<span class="si-tab-count" id="tab-count-geo_ai">2</span>
				</button>
			</div>

			<!-- Search and Status Controls -->
			<div class="si-toolbar-controls">
				<!-- Search Filter -->
				<div class="si-search-input-wrap">
					<svg class="si-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					<input type="text" id="si-search-input" class="si-search-input" placeholder="<?php esc_attr_e( 'Search checks...', 'seo-inspector-zhs' ); ?>" autocomplete="off" />
				</div>

				<!-- Status Filter Pills -->
				<div class="si-status-pills" id="si-status-pills">
					<button class="si-pill-btn active" data-status="all" type="button"><?php esc_html_e( 'All', 'seo-inspector-zhs' ); ?></button>
					<button class="si-pill-btn" data-status="pass" type="button"><?php esc_html_e( 'Passed', 'seo-inspector-zhs' ); ?></button>
					<button class="si-pill-btn" data-status="partial" type="button"><?php esc_html_e( 'Warnings', 'seo-inspector-zhs' ); ?></button>
					<button class="si-pill-btn" data-status="fail" type="button"><?php esc_html_e( 'Failed', 'seo-inspector-zhs' ); ?></button>
				</div>

				<!-- Expand/Collapse Toggle -->
				<button id="si-toggle-all-btn" class="si-toggle-all-btn" type="button" title="<?php esc_attr_e( 'Expand or collapse all check detail panels', 'seo-inspector-zhs' ); ?>">
					<span id="si-toggle-all-text"><?php esc_html_e( 'Expand All', 'seo-inspector-zhs' ); ?></span>
				</button>
			</div>
		</div>

		<!-- Checks Accordion List Container -->
		<div class="si-checks-list" id="si-checks-list">
			<?php if ( ! empty( $checks ) ) : ?>
				<?php foreach ( $checks as $check ) :
					$status      = $check['status'] ?? 'fail';
					$cat         = $check['category'] ?? 'seo';
					$cat_label   = $check['category_label'] ?? ucfirst( $cat );
					$impact      = $check['impact'] ?? 'medium';
					$title       = $check['title'] ?? '';
					$summary     = $check['summary'] ?? '';
					$details     = $check['details'] ?? '';
					$rec         = $check['recommendation'] ?? '';
				?>
					<div class="si-check-card status-<?php echo esc_attr( $status ); ?>"
						 data-category="<?php echo esc_attr( $cat ); ?>"
						 data-status="<?php echo esc_attr( $status ); ?>"
						 data-search="<?php echo esc_attr( strtolower( $title . ' ' . $summary . ' ' . $cat_label ) ); ?>">

						<div class="si-check-header" tabindex="0" role="button" aria-expanded="false">
							<div class="si-check-status-col">
								<?php if ( $status === 'pass' ) : ?>
									<span class="si-status-badge pass" title="<?php esc_attr_e( 'Passed Check (Score 1.0)', 'seo-inspector-zhs' ); ?>">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
									</span>
								<?php elseif ( $status === 'partial' ) : ?>
									<span class="si-status-badge partial" title="<?php esc_attr_e( 'Partial Warning (Score 0.5)', 'seo-inspector-zhs' ); ?>">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
									</span>
								<?php else : ?>
									<span class="si-status-badge fail" title="<?php esc_attr_e( 'Failed Check (Score 0.0)', 'seo-inspector-zhs' ); ?>">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
									</span>
								<?php endif; ?>
							</div>

							<div class="si-check-info-col">
								<div class="si-check-title-row">
									<h3 class="si-check-title"><?php echo esc_html( $title ); ?></h3>
									<span class="si-badge-cat cat-<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat_label ); ?></span>
									<span class="si-badge-impact impact-<?php echo esc_attr( $impact ); ?>"><?php echo esc_html( ucfirst( $impact ) . ' Impact' ); ?></span>
								</div>
								<p class="si-check-summary"><?php echo esc_html( $summary ); ?></p>
							</div>

							<div class="si-check-toggle-col">
								<svg class="si-chevron-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
									<polyline points="6 9 12 15 18 9"></polyline>
								</svg>
							</div>
						</div>

						<div class="si-check-body" style="display: none;">
							<div class="si-check-detail-grid">
								<!-- Detected Findings -->
								<div class="si-detail-block findings-block">
									<div class="si-detail-heading">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
										<?php esc_html_e( 'Detected Findings &amp; DOM Evidence', 'seo-inspector-zhs' ); ?>
									</div>
									<div class="si-detail-content">
										<p><?php echo esc_html( $details ); ?></p>
									</div>
								</div>

								<!-- Recommendation / Action Item -->
								<div class="si-detail-block rec-block">
									<div class="si-detail-heading">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
										<?php esc_html_e( 'Actionable Recommendation', 'seo-inspector-zhs' ); ?>
									</div>
									<div class="si-detail-content">
										<p><?php echo esc_html( $rec ); ?></p>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="si-empty-state">
					<p><?php esc_html_e( 'No audit data available. Click "Re-check Audit" to perform your first scan.', 'seo-inspector-zhs' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- Clean Footer -->
	<footer class="si-dashboard-footer">
		<div class="si-footer-left">
			<span><?php esc_html_e( 'SEO Inspector &bull; Production-grade WordPress SEO Audit Engine', 'seo-inspector-zhs' ); ?></span>
		</div>
		<div class="si-footer-right">
			<span><?php esc_html_e( 'Engineered by', 'seo-inspector-zhs' ); ?></span>
			<a href="https://www.instagram.com/zhsaikot/" target="_blank" rel="noopener noreferrer" class="si-footer-author">MD. Ziaul Hasan</a>
			<span class="si-footer-sep">&bull;</span>
			<a href="https://github.com/zhsaikot/seo-inspector-zhs" target="_blank" rel="noopener noreferrer" class="si-footer-repo">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
				GitHub
			</a>
		</div>
	</footer>

</div>
