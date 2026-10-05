<?php
/**
 * ZHS Site Audit and SEO Diagnostics - REST API Endpoints
 *
 * Exposes REST API routes for executing audits and retrieving results asynchronously.
 *
 * @package    ZHS_Site_Audit_SEO_Diagnostics
 * @subpackage ZHS_Site_Audit_SEO_Diagnostics/includes
 * @author     MD. Ziaul Hasan <https://mdziaulhasan.com/>
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZHS_Audit_Rest_API {

	/**
	 * REST route namespace.
	 */
	const REST_NAMESPACE = 'zhs-audit/v1';

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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register plugin REST routes.
	 */
	public function register_routes() {
		// GET /zhs-audit/v1/audit - Retrieve latest cached audit
		register_rest_route(
			self::REST_NAMESPACE,
			'/audit',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_audit' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// POST /zhs-audit/v1/audit/run - Force live audit re-check
		register_rest_route(
			self::REST_NAMESPACE,
			'/audit/run',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'run_audit' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// POST /zhs-audit/v1/audit/clear - Clear cached audit
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
				'zhs_audit_forbidden',
				__( 'You do not have permission to access SEO Inspector audit diagnostics.', 'zhs-site-audit-seo-diagnostics' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Enforce CSRF nonce validation for state-changing requests
		if ( in_array( $request->get_method(), array( 'POST', 'PUT', 'DELETE', 'PATCH' ), true ) ) {
			$nonce = $request->get_header( 'x_wp_nonce' );
			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error(
					'zhs_audit_invalid_nonce',
					__( 'Invalid or missing CSRF token (X-WP-Nonce).', 'zhs-site-audit-seo-diagnostics' ),
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
		$mode        = sanitize_key( $request->get_param( 'mode' ) ?: 'single' );
		$target_url  = '';
		$target_urls = array();

		$url_param = $request->get_param( 'url' );
		if ( ! empty( $url_param ) ) {
			$target_url = esc_url_raw( $url_param );
		}

		$urls_param = $request->get_param( 'urls' );
		if ( ! empty( $urls_param ) ) {
			if ( is_array( $urls_param ) ) {
				$target_urls = array_map( 'esc_url_raw', $urls_param );
			} elseif ( is_string( $urls_param ) ) {
				$target_urls = array_map( 'esc_url_raw', array_filter( array_map( 'trim', explode( ',', $urls_param ) ) ) );
			}
		}

		$audit = $this->engine->run_audit( false, $target_url, $mode, $target_urls );
		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $audit,
				'message' => __( 'Audit data retrieved successfully.', 'zhs-site-audit-seo-diagnostics' ),
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
		$target_url  = '';
		$target_urls = array();
		$mode        = 'single';

		$json_params = $request->get_json_params();
		if ( is_array( $json_params ) ) {
			if ( ! empty( $json_params['mode'] ) ) {
				$mode = sanitize_key( $json_params['mode'] );
			}
			if ( ! empty( $json_params['url'] ) ) {
				$target_url = esc_url_raw( $json_params['url'] );
			}
			if ( ! empty( $json_params['urls'] ) && is_array( $json_params['urls'] ) ) {
				$target_urls = array_map( 'esc_url_raw', $json_params['urls'] );
			}
		}

		// Fallback to request parameters
		if ( empty( $target_url ) && ! empty( $request->get_param( 'url' ) ) ) {
			$target_url = esc_url_raw( $request->get_param( 'url' ) );
		}
		if ( empty( $target_urls ) && ! empty( $request->get_param( 'urls' ) ) ) {
			$urls_param = $request->get_param( 'urls' );
			if ( is_array( $urls_param ) ) {
				$target_urls = array_map( 'esc_url_raw', $urls_param );
			} elseif ( is_string( $urls_param ) ) {
				$target_urls = array_map( 'esc_url_raw', array_filter( array_map( 'trim', explode( ',', $urls_param ) ) ) );
			}
		}
		if ( $mode === 'single' && ! empty( $request->get_param( 'mode' ) ) ) {
			$mode = sanitize_key( $request->get_param( 'mode' ) );
		}

		$audit = $this->engine->run_audit( true, $target_url, $mode, $target_urls );
		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $audit,
				'message' => __( 'Live audit completed successfully.', 'zhs-site-audit-seo-diagnostics' ),
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
		delete_option( ZHS_Audit_Engine::OPTION_CACHE_KEY );
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Audit cache cleared successfully.', 'zhs-site-audit-seo-diagnostics' ),
			),
			200
		);
	}
}
