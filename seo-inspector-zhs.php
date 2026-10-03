<?php
/**
 * Plugin Name:       SEO Inspector ZHS
 * Description:       Production-ready, modular WordPress SEO audit and analytics engine with live front-end DOM diagnostics, usability, accessibility, SEO, and GEO/AI readiness verification.
 * Version:           1.0.0
 * Author:            MD. Ziaul Hasan
 * Author URI:        https://mdziaulhasan.com/
 * Text Domain:       seo-inspector-zhs
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Tested up to:      6.7
 * Requires PHP:      7.4
 *
 * @package           SEO_Inspector_ZHS
 * @author            MD. Ziaul Hasan
 * @copyright         Copyright (C) 2026 MD. Ziaul Hasan. All rights reserved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define core plugin constants
define( 'SEO_INSPECTOR_ZHS_VERSION', '1.0.0' );
define( 'SEO_INSPECTOR_ZHS_FILE', __FILE__ );
define( 'SEO_INSPECTOR_ZHS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEO_INSPECTOR_ZHS_URL', plugin_dir_url( __FILE__ ) );
define( 'SEO_INSPECTOR_ZHS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin singleton bootstrap class.
 */
final class SEO_Inspector_ZHS {

	/**
	 * Singleton instance.
	 *
	 * @var SEO_Inspector_ZHS|null
	 */
	private static $instance = null;

	/**
	 * Audit engine instance.
	 *
	 * @var SEO_Inspector_ZHS_Audit_Engine
	 */
	public $audit_engine;

	/**
	 * Admin page loader instance.
	 *
	 * @var SEO_Inspector_ZHS_Admin_Page
	 */
	public $admin_page;

	/**
	 * REST API controller instance.
	 *
	 * @var SEO_Inspector_ZHS_Rest_API
	 */
	public $rest_api;

	/**
	 * Retrieve singleton instance.
	 *
	 * @return SEO_Inspector_ZHS
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
		require_once SEO_INSPECTOR_ZHS_DIR . 'includes/class-audit-engine.php';
		require_once SEO_INSPECTOR_ZHS_DIR . 'includes/class-admin-page.php';
		require_once SEO_INSPECTOR_ZHS_DIR . 'includes/class-rest-api.php';
	}

	/**
	 * Initialize components.
	 */
	private function init_components() {
		$this->audit_engine = new SEO_Inspector_ZHS_Audit_Engine();
		$this->admin_page   = new SEO_Inspector_ZHS_Admin_Page( $this->audit_engine );
		$this->rest_api     = new SEO_Inspector_ZHS_Rest_API( $this->audit_engine );
	}

	/**
	 * Register general hooks.
	 */
	private function register_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_filter( 'plugin_action_links_' . SEO_INSPECTOR_ZHS_BASENAME, array( $this, 'add_action_links' ) );

		register_activation_hook( SEO_INSPECTOR_ZHS_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( SEO_INSPECTOR_ZHS_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Load translation textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'seo-inspector-zhs',
			false,
			dirname( SEO_INSPECTOR_ZHS_BASENAME ) . '/languages'
		);
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
			esc_url( admin_url( 'admin.php?page=seo-inspector-zhs' ) ),
			esc_html__( 'Open Audit Dashboard', 'seo-inspector-zhs' )
		);

		array_unshift( $links, $dashboard_link );
		return $links;
	}

	/**
	 * Plugin activation routine.
	 */
	public function activate() {
		// Run initial audit to prime transient cache if not already set
		if ( ! get_option( SEO_Inspector_ZHS_Audit_Engine::OPTION_CACHE_KEY ) ) {
			$this->audit_engine->run_audit( true );
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
function seo_inspector_zhs() {
	return SEO_Inspector_ZHS::get_instance();
}

// Fire up the plugin
seo_inspector_zhs();
