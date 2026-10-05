<?php
/**
 * ZHS Site Audit and SEO Diagnostics - Admin Page & Assets Loader
 *
 * Registers the administration menu, enqueues SaaS UI styles/scripts,
 * and renders the dashboard template.
 *
 * @package    ZHS_Site_Audit_SEO_Diagnostics
 * @subpackage ZHS_Site_Audit_SEO_Diagnostics/includes
 * @author     MD. Ziaul Hasan <https://mdziaulhasan.com/>
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZHS_Audit_Admin_Page {

	/**
	 * Hook suffix for the admin menu page.
	 *
	 * @var string
	 */
	private $page_hook;

	/**
	 * Audit engine instance.
	 *
	 * @var ZHS_Audit_Engine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @param ZHS_Audit_Engine $engine
	 */
	public function __construct( ZHS_Audit_Engine $engine ) {
		$this->engine = $engine;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the WordPress admin menu item.
	 */
	public function register_menu() {
		$svg_icon = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><path d="M11 8v6"></path><path d="M8 11h6"></path></svg>'
		);

		$this->page_hook = add_menu_page(
			__( 'ZHS Site Audit and SEO Diagnostics', 'zhs-site-audit-seo-diagnostics' ),
			__( 'Site Audit', 'zhs-site-audit-seo-diagnostics' ),
			'manage_options',
			'zhs-site-audit-seo-diagnostics',
			array( $this, 'render_dashboard' ),
			$svg_icon,
			75
		);
	}

	/**
	 * Enqueue frontend assets on the SEO Inspector admin screen only.
	 *
	 * @param string $hook_suffix Admin screen identifier.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		// CSS styles
		wp_enqueue_style(
			'zhs-audit-admin-css',
			ZHS_AUDIT_URL . 'assets/css/dashboard.css',
			array(),
			ZHS_AUDIT_VERSION
		);


		// JavaScript logic
		wp_enqueue_script(
			'zhs-audit-admin-js',
			ZHS_AUDIT_URL . 'assets/js/dashboard.js',
			array(),
			ZHS_AUDIT_VERSION,
			true
		);

		// Preload cached audit state for front page (home) without blocking admin page loads
		$initial_audit = $this->engine->get_cached_audit( home_url( '/' ), 'single' );

		wp_localize_script(
			'zhs-audit-admin-js',
			'zhsAuditConfig',
			array(
				'restUrl'            => esc_url_raw( rest_url( 'zhs-audit/v1/' ) ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'siteUrl'            => home_url( '/' ),
				'authorName'         => 'MD. Ziaul Hasan',
				'authorUri'          => 'https://mdziaulhasan.com/',
				'initialData'        => $initial_audit,
				'scannablePages'     => $this->engine->get_scannable_pages(),
				'auditMode'          => 'single',
				'scopeLabel'         => $initial_audit['page_name'] ?? __( 'Front Page (Homepage)', 'zhs-site-audit-seo-diagnostics' ),
				'targetUrl'          => home_url( '/' ),
				'currentPageName'    => $initial_audit['page_name'] ?? __( 'Front Page (Homepage)', 'zhs-site-audit-seo-diagnostics' ),
				'scannedUrls'        => array( home_url( '/' ) ),
				'i18n'               => array(
					'runningAudit'   => __( 'Running live audit & inspecting DOM...', 'zhs-site-audit-seo-diagnostics' ),
					'auditComplete'  => __( 'SEO Audit completed successfully!', 'zhs-site-audit-seo-diagnostics' ),
					'auditError'     => __( 'Audit encountered a network error. Please try again.', 'zhs-site-audit-seo-diagnostics' ),
					'recheckBtn'     => __( 'Re-check Audit', 'zhs-site-audit-seo-diagnostics' ),
					'rechecking'     => __( 'Auditing...', 'zhs-site-audit-seo-diagnostics' ),
					'exporting'      => __( 'Preparing print report...', 'zhs-site-audit-seo-diagnostics' ),
					'copySuccess'    => __( 'JSON report copied to clipboard!', 'zhs-site-audit-seo-diagnostics' ),
					'allChecks'      => __( 'All Checks', 'zhs-site-audit-seo-diagnostics' ),
					'pass'           => __( 'Pass', 'zhs-site-audit-seo-diagnostics' ),
					'partial'        => __( 'Warning', 'zhs-site-audit-seo-diagnostics' ),
					'fail'           => __( 'Fail', 'zhs-site-audit-seo-diagnostics' ),
					'fullWebsite'    => __( 'Full Website Audit', 'zhs-site-audit-seo-diagnostics' ),
					'selectAll'      => __( 'Select All', 'zhs-site-audit-seo-diagnostics' ),
					'clearAll'       => __( 'Clear All', 'zhs-site-audit-seo-diagnostics' ),
					'runAudit'       => __( 'Run Audit', 'zhs-site-audit-seo-diagnostics' ),
					'pagesSelected'  => __( '%d Pages Selected', 'zhs-site-audit-seo-diagnostics' ),
					'affectedPages'  => __( 'Affected Pages', 'zhs-site-audit-seo-diagnostics' ),
				),
			)
		);
	}

	/**
	 * Render the primary admin dashboard template.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'zhs-site-audit-seo-diagnostics' ) );
		}

		$template_path = ZHS_AUDIT_DIR . 'templates/dashboard-view.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'SEO Inspector dashboard template missing.', 'zhs-site-audit-seo-diagnostics' ) . '</p></div>';
		}
	}
}
