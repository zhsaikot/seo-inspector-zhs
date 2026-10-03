<?php
/**
 * SEO Inspector ZHS - REST API Endpoints
 *
 * Exposes REST API routes for executing audits and retrieving results asynchronously.
 *
 * @package    SEO_Inspector_ZHS
 * @subpackage SEO_Inspector_ZHS/includes
 * @author     MD. Ziaul Hasan <https://mdziaulhasan.com/>
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SEO_Inspector_ZHS_Rest_API {

	/**
	 * REST route namespace.
	 */
	const REST_NAMESPACE = 'seo-inspector/v1';

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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register plugin REST routes.
	 */
	public function register_routes() {
		// GET /seo-inspector/v1/audit - Retrieve latest cached audit
		register_rest_route(
			self::REST_NAMESPACE,
			'/audit',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_audit' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// POST /seo-inspector/v1/audit/run - Force live audit re-check
		register_rest_route(
			self::REST_NAMESPACE,
			'/audit/run',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'run_audit' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// POST /seo-inspector/v1/audit/clear - Clear cached audit
		register_rest_route(
			self::REST_NAMESPACE,
			'/audit/clear',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'clear_cache' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Permission check: Only administrators / users with manage_options capability,
	 * with strict CSRF verification for write requests.
	 *
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public function check_permissions( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'seo_inspector_zhs_forbidden',
				__( 'You do not have permission to access SEO Inspector audit diagnostics.', 'seo-inspector-zhs' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Enforce CSRF nonce validation for state-changing requests
		if ( in_array( $request->get_method(), array( 'POST', 'PUT', 'DELETE', 'PATCH' ), true ) ) {
			$nonce = $request->get_header( 'x_wp_nonce' );
			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error(
					'seo_inspector_zhs_invalid_nonce',
					__( 'Invalid or missing CSRF token (X-WP-Nonce).', 'seo-inspector-zhs' ),
					array( 'status' => 403 )
				);
			}
		}

		return true;
	}

	/**
	 * GET callback: Retrieve cached audit data or trigger initial audit if empty.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_audit( $request ) {
		$audit = $this->engine->run_audit( false );
		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $audit,
				'message' => __( 'Audit data retrieved successfully.', 'seo-inspector-zhs' ),
			),
			200
		);
	}

	/**
	 * POST callback: Force a fresh live audit scan.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function run_audit( $request ) {
		$audit = $this->engine->run_audit( true );
		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $audit,
				'message' => __( 'Live audit completed successfully.', 'seo-inspector-zhs' ),
			),
			200
		);
	}

	/**
	 * POST callback: Clear audit cache.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function clear_cache( $request ) {
		delete_option( SEO_Inspector_ZHS_Audit_Engine::OPTION_CACHE_KEY );
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Audit cache cleared successfully.', 'seo-inspector-zhs' ),
			),
			200
		);
	}
}
