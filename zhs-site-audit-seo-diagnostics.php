<?php
/**
 * Plugin Name:       ZHS Site Audit and SEO Diagnostics
 * Plugin URI:        https://mdziaulhasan.com/zhs-site-audit-plugin
 * Description:       Comprehensive 17-point site audit and SEO diagnostics suite verifying usability, accessibility, indexing, schema, and GEO/AI readiness.
 * Version:           1.0.0
 * Author:            MD. Ziaul Hasan (zhsaikot)
 * Author URI:        https://mdziaulhasan.com/
 * Text Domain:       zhs-site-audit-seo-diagnostics
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP:      7.4
 *
 * @package           ZHS_Site_Audit_SEO_Diagnostics
 * @author            MD. Ziaul Hasan (zhsaikot)
 * @copyright         Copyright (C) 2026 MD. Ziaul Hasan. All rights reserved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define core plugin constants
define( 'ZHS_AUDIT_VERSION', '1.0.0' );
define( 'ZHS_AUDIT_FILE', __FILE__ );
define( 'ZHS_AUDIT_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZHS_AUDIT_URL', plugin_dir_url( __FILE__ ) );
define( 'ZHS_AUDIT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin singleton bootstrap class.
 */
final class ZHS_Audit {

	/**
	 * Singleton instance.
	 *
	 * @var ZHS_Audit|null
	 */
	private static $instance = null;

	/**
	 * Audit engine instance.
	 *
	 * @var ZHS_Audit_Engine
	 */
	public $audit_engine;

	/**
	 * Admin page loader instance.
	 *
	 * @var ZHS_Audit_Admin_Page
	 */
	public $admin_page;

	/**
	 * REST API controller instance.
	 *
	 * @var ZHS_Audit_Rest_API
	 */
	public $rest_api;

	/**
	 * Retrieve singleton instance.
	 *
	 * @return ZHS_Audit
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: Load dependencies and register hooks.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Load class files.
	 */
	private function load_dependencies() {
		require_once ZHS_AUDIT_DIR . 'includes/class-audit-engine.php';
		require_once ZHS_AUDIT_DIR . 'includes/class-admin-page.php';
		require_once ZHS_AUDIT_DIR . 'includes/class-rest-api.php';
	}

	/**
	 * Initialize components.
	 */
	private function init_components() {
		$this->audit_engine = new ZHS_Audit_Engine();
		$this->admin_page   = new ZHS_Audit_Admin_Page( $this->audit_engine );
		$this->rest_api     = new ZHS_Audit_Rest_API( $this->audit_engine );
	}

	/**
	 * Register general hooks.
	 */
	private function register_hooks() {
		add_filter( 'plugin_action_links_' . ZHS_AUDIT_BASENAME, array( $this, 'add_action_links' ) );

		register_activation_hook( ZHS_AUDIT_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( ZHS_AUDIT_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Add custom action links on the plugins management page.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$dashboard_link = sprintf(
			'<a href="%s" style="font-weight: 600; color: #0284c7;">%s</a>',
			esc_url( admin_url( 'admin.php?page=zhs-site-audit-seo-diagnostics' ) ),
			esc_html__( 'Open Audit Dashboard', 'zhs-site-audit-seo-diagnostics' )
		);

		array_unshift( $links, $dashboard_link );
		return $links;
	}

	/**
	 * Plugin activation routine.
	 * Performs zero blocking remote or loopback HTTP queries to prevent timeout errors.
	 */
	public function activate() {
		// Initialize empty cache placeholder without making loopback HTTP requests
		if ( false === get_option( ZHS_Audit_Engine::OPTION_CACHE_KEY ) ) {
			update_option( ZHS_Audit_Engine::OPTION_CACHE_KEY, array(), false );
		}
	}

	/**
	 * Plugin deactivation routine.
	 */
	public function deactivate() {
		// Flush temporary transient if necessary
	}
}

/**
 * Bootstrap the plugin.
 */
function zhs_audit() {
	return ZHS_Audit::get_instance();
}

// Fire up the plugin
zhs_audit();
