<?php
/**
 * SEO Inspector ZHS - Admin Page & Assets Loader
 *
 * Registers the administration menu, enqueues SaaS UI styles/scripts,
 * and renders the dashboard template.
 *
 * @package    SEO_Inspector_ZHS
 * @subpackage SEO_Inspector_ZHS/includes
 * @author     MD. Ziaul Hasan <https://mdziaulhasan.com/>
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SEO_Inspector_ZHS_Admin_Page {

	/**
	 * Hook suffix for the admin menu page.
	 *
	 * @var string
	 */
	private $page_hook;

	/**
	 * Audit engine instance.
	 *
	 * @var SEO_Inspector_ZHS_Audit_Engine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @param SEO_Inspector_ZHS_Audit_Engine $engine
	 */
	public function __construct( SEO_Inspector_ZHS_Audit_Engine $engine ) {
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
			__( 'SEO Inspector & Site Audit', 'seo-inspector-zhs' ),
			__( 'SEO Inspector', 'seo-inspector-zhs' ),
			'manage_options',
			'seo-inspector-zhs',
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
			'seo-inspector-zhs-admin-css',
			SEO_INSPECTOR_ZHS_URL . 'assets/css/dashboard.css',
			array(),
			SEO_INSPECTOR_ZHS_VERSION
		);


		// JavaScript logic
		wp_enqueue_script(
			'seo-inspector-zhs-admin-js',
			SEO_INSPECTOR_ZHS_URL . 'assets/js/dashboard.js',
			array(),
			SEO_INSPECTOR_ZHS_VERSION,
			true
		);

		// Preload initial audit state so the dashboard renders instantly without waiting for extra network round-trips
		$initial_audit = $this->engine->run_audit( false, '', 'full_site' );

		wp_localize_script(
			'seo-inspector-zhs-admin-js',
			'seoInspectorConfig',
			array(
				'restUrl'            => esc_url_raw( rest_url( 'seo-inspector/v1/' ) ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'siteUrl'            => home_url( '/' ),
				'authorName'         => 'MD. Ziaul Hasan',
				'authorUri'          => 'https://mdziaulhasan.com/',
				'initialData'        => $initial_audit,
				'scannablePages'     => $this->engine->get_scannable_pages(),
				'auditMode'          => $initial_audit['audit_mode'] ?? 'full_site',
				'scopeLabel'         => $initial_audit['scope_label'] ?? __( 'Full Website Audit', 'seo-inspector-zhs' ),
				'targetUrl'          => $initial_audit['page_url'] ?? home_url( '/' ),
				'currentPageName'    => $initial_audit['page_name'] ?? __( 'Full Website Audit', 'seo-inspector-zhs' ),
				'scannedUrls'        => $initial_audit['scanned_urls'] ?? array(),
				'i18n'               => array(
					'runningAudit'   => __( 'Running live audit & inspecting DOM...', 'seo-inspector-zhs' ),
					'auditComplete'  => __( 'SEO Audit completed successfully!', 'seo-inspector-zhs' ),
					'auditError'     => __( 'Audit encountered a network error. Please try again.', 'seo-inspector-zhs' ),
					'recheckBtn'     => __( 'Re-check Audit', 'seo-inspector-zhs' ),
					'rechecking'     => __( 'Auditing...', 'seo-inspector-zhs' ),
					'exporting'      => __( 'Preparing print report...', 'seo-inspector-zhs' ),
					'copySuccess'    => __( 'JSON report copied to clipboard!', 'seo-inspector-zhs' ),
					'allChecks'      => __( 'All Checks', 'seo-inspector-zhs' ),
					'pass'           => __( 'Pass', 'seo-inspector-zhs' ),
					'partial'        => __( 'Warning', 'seo-inspector-zhs' ),
					'fail'           => __( 'Fail', 'seo-inspector-zhs' ),
					'fullWebsite'    => __( 'Full Website Audit', 'seo-inspector-zhs' ),
					'selectAll'      => __( 'Select All', 'seo-inspector-zhs' ),
					'clearAll'       => __( 'Clear All', 'seo-inspector-zhs' ),
					'runAudit'       => __( 'Run Audit', 'seo-inspector-zhs' ),
					'pagesSelected'  => __( '%d Pages Selected', 'seo-inspector-zhs' ),
					'affectedPages'  => __( 'Affected Pages', 'seo-inspector-zhs' ),
				),
			)
		);
	}

	/**
	 * Render the primary admin dashboard template.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-inspector-zhs' ) );
		}

		$template_path = SEO_INSPECTOR_ZHS_DIR . 'templates/dashboard-view.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'SEO Inspector dashboard template missing.', 'seo-inspector-zhs' ) . '</p></div>';
		}
	}
}
