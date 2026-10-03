<?php
/**
 * SEO Inspector ZHS - Audit Engine
 *
 * Core scanning and scoring engine inspecting front-end DOM,
 * meta tags, headers, and WordPress database entities.
 * Supports per-page auditing with explicit page names and URLs in diagnostics.
 *
 * @package    SEO_Inspector_ZHS
 * @subpackage SEO_Inspector_ZHS/includes
 * @author     MD. Ziaul Hasan <https://mdziaulhasan.com/>
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SEO_Inspector_ZHS_Audit_Engine {

	/**
	 * Option key used for transient/cached audit results.
	 */
	const OPTION_CACHE_KEY = 'seo_inspector_zhs_audit_cache';

	/**
	 * Currently audited page name / title.
	 *
	 * @var string
	 */
	private $current_page_name = '';

	/**
	 * Currently audited page URL.
	 *
	 * @var string
	 */
	private $current_page_url = '';

	/**
	 * Resolve human-readable page name and normalized URL.
	 *
	 * @param string $url Page URL.
	 * @return array Array with 'name' and 'url'.
	 */
	public function resolve_page_info( $url = '' ) {
		$home_url = home_url( '/' );
		$target   = ! empty( $url ) ? esc_url_raw( $url ) : $home_url;

		// Normalize trailing slashes for comparison
		if ( empty( $url ) || trailingslashit( $target ) === trailingslashit( $home_url ) ) {
			$front_page_id = (int) get_option( 'page_on_front' );
			if ( $front_page_id > 0 ) {
				$name = get_the_title( $front_page_id ) . ' (' . __( 'Front Page', 'seo-inspector-zhs' ) . ')';
			} else {
				$site_name = get_bloginfo( 'name' );
				$name = ! empty( $site_name ) ? $site_name . ' (' . __( 'Front Page', 'seo-inspector-zhs' ) . ')' : __( 'Front Page (Homepage)', 'seo-inspector-zhs' );
			}
			return array(
				'name' => $name,
				'url'  => $home_url,
			);
		}

		// Try resolving via WordPress url_to_postid
		$post_id = url_to_postid( $target );
		if ( $post_id > 0 ) {
			$post_type  = get_post_type_object( get_post_type( $post_id ) );
			$type_label = $post_type ? $post_type->labels->singular_name : __( 'Page', 'seo-inspector-zhs' );
			return array(
				'name' => get_the_title( $post_id ) . ' (' . $type_label . ')',
				'url'  => get_permalink( $post_id ),
			);
		}

		// Fallback: parse URL path
		$path = trim( (string) wp_parse_url( $target, PHP_URL_PATH ), '/' );
		$name = ! empty( $path ) ? ucwords( str_replace( array( '-', '_', '/' ), ' ', $path ) ) : __( 'Webpage', 'seo-inspector-zhs' );
		return array(
			'name' => $name,
			'url'  => $target,
		);
	}

	/**
	 * Retrieve list of scannable pages and posts for the audit selector.
	 *
	 * @return array List of pages with id, title, and url.
	 */
	public function get_scannable_pages() {
		$pages_list = array();

		// 1. Front page
		$front_id    = (int) get_option( 'page_on_front' );
		$front_title = ( $front_id > 0 ) ? get_the_title( $front_id ) . ' (' . __( 'Front Page', 'seo-inspector-zhs' ) . ')' : __( 'Front Page (Homepage)', 'seo-inspector-zhs' );
		$pages_list[] = array(
			'id'    => 'front',
			'title' => $front_title,
			'url'   => home_url( '/' ),
		);

		// 2. Published Pages
		$wp_pages = get_pages( array(
			'post_status' => 'publish',
			'sort_column' => 'post_title',
			'sort_order'  => 'ASC',
			'number'      => 30,
		) );

		if ( ! empty( $wp_pages ) ) {
			foreach ( $wp_pages as $p ) {
				if ( $front_id > 0 && $p->ID === $front_id ) {
					continue;
				}
				$pages_list[] = array(
					'id'    => $p->ID,
					'title' => $p->post_title . ' (' . __( 'Page', 'seo-inspector-zhs' ) . ')',
					'url'   => get_permalink( $p->ID ),
				);
			}
		}

		// 3. Recent Published Posts
		$recent_posts = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		if ( ! empty( $recent_posts ) ) {
			foreach ( $recent_posts as $post ) {
				$pages_list[] = array(
					'id'    => $post->ID,
					'title' => $post->post_title . ' (' . __( 'Post', 'seo-inspector-zhs' ) . ')',
					'url'   => get_permalink( $post->ID ),
				);
			}
		}

		return $pages_list;
	}

	/**
	 * Run a full audit scan on a specific page or the front page.
	 *
	 * @param bool   $force      Force fresh audit bypass cache.
	 * @param string $target_url Specific page URL to audit (defaults to site home).
	 * @return array Structured audit results.
	 */
	public function run_audit( $force = false, $target_url = '' ) {
		$home_url   = home_url( '/' );
		$target_url = ! empty( $target_url ) ? esc_url_raw( $target_url ) : $home_url;
		$page_info  = $this->resolve_page_info( $target_url );

		$this->current_page_name = $page_info['name'];
		$this->current_page_url  = $page_info['url'];

		$is_front_page = ( trailingslashit( $this->current_page_url ) === trailingslashit( $home_url ) );
		$cache_key     = self::OPTION_CACHE_KEY;
		if ( ! $is_front_page ) {
			$cache_key .= '_' . substr( md5( $this->current_page_url ), 0, 12 );
		}

		if ( ! $force ) {
			$cached = get_option( $cache_key );
			if ( ! empty( $cached ) && is_array( $cached ) && isset( $cached['timestamp'] ) ) {
				// Return cached data if younger than 12 hours
				if ( ( time() - $cached['timestamp'] ) < ( 12 * HOUR_IN_SECONDS ) ) {
					return $cached;
				}
			}
		}

		// Perform remote request to inspect front-end DOM
		$fetch_result = $this->fetch_site_html( $this->current_page_url );

		$html             = $fetch_result['html'];
		$response_headers = $fetch_result['headers'];
		$status_code      = $fetch_result['status_code'];
		$fetch_error      = $fetch_result['error'];

		// Initialize DOMDocument and DOMXPath
		$xpath = null;
		$dom   = null;

		if ( ! empty( $html ) ) {
			$dom = new DOMDocument();
			libxml_use_internal_errors( true );
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$encoded_html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );
			} else {
				$encoded_html = htmlspecialchars_decode( utf8_decode( htmlentities( $html, ENT_COMPAT, 'utf-8', false ) ) );
			}
			@$dom->loadHTML( $encoded_html, LIBXML_NOWARNING | LIBXML_NOERROR );
			libxml_clear_errors();
			$xpath = new DOMXPath( $dom );
		}

		// Run all 17 diagnostic checks
		$checks = array();

		// 1. Usability Checks (5)
		$checks['check_https']              = $this->check_https( $this->current_page_url, $response_headers );
		$checks['check_mobile_viewport']    = $this->check_mobile_viewport( $xpath );
		$checks['check_contact_options']    = $this->check_contact_options( $xpath, $html );
		$checks['check_entry_popup']        = $this->check_entry_popup( $xpath, $html );
		$checks['check_footer_trust_links'] = $this->check_footer_trust_links( $xpath );

		// 2. Accessibility Checks (4)
		$checks['check_skip_to_content']   = $this->check_skip_to_content( $xpath, $dom );
		$checks['check_image_alt_text']    = $this->check_image_alt_text( $xpath );
		$checks['check_form_labels']       = $this->check_form_labels( $xpath );
		$checks['check_heading_structure'] = $this->check_heading_structure( $xpath );

		// 3. SEO Checks (6)
		$checks['check_title_tag']               = $this->check_title_tag( $xpath );
		$checks['check_meta_description']        = $this->check_meta_description( $xpath );
		$checks['check_canonical_and_robots']    = $this->check_canonical_and_robots( $xpath );
		$checks['check_open_graph_twitter']      = $this->check_open_graph_twitter( $xpath );
		$checks['check_xml_sitemap']             = $this->check_xml_sitemap();
		$checks['check_indexable_content_depth'] = $this->check_indexable_content_depth( $xpath, $html );

		// 4. GEO & AI-Readiness Checks (2)
		$checks['check_structured_data_json_ld'] = $this->check_structured_data_json_ld( $xpath, $html );
		$checks['check_ai_entity_readiness']     = $this->check_ai_entity_readiness( $xpath, $html );

		// Calculate total scores and grades
		$score_data = $this->calculate_scores( $checks );

		$audit_payload = array(
			'site_url'        => $this->current_page_url,
			'page_name'       => $this->current_page_name,
			'page_url'        => $this->current_page_url,
			'status_code'     => $status_code,
			'fetch_error'     => $fetch_error,
			'timestamp'       => time(),
			'formatted_date'  => current_time( 'mysql' ),
			'score'           => $score_data['overall_score'],
			'grade'           => $score_data['grade'],
			'grade_label'     => $score_data['grade_label'],
			'passed_count'    => $score_data['passed_count'],
			'partial_count'   => $score_data['partial_count'],
			'failed_count'    => $score_data['failed_count'],
			'total_checks'    => count( $checks ),
			'category_scores' => $score_data['category_scores'],
			'checks'          => array_values( $checks ),
			'scannable_pages' => $this->get_scannable_pages(),
		);

		update_option( $cache_key, $audit_payload, false );

		return $audit_payload;
	}

	/**
	 * Fetch target HTML with WordPress HTTP API.
	 *
	 * @param string $url Target URL to fetch.
	 * @return array HTML string, headers, HTTP status, and error message.
	 */
	private function fetch_site_html( $url ) {
		$args = array(
			'timeout'     => 15,
			'redirection' => 5,
			'sslverify'   => false, // Disabled for local dev/self-signed cert support
			'user-agent'  => 'SEO-Inspector-ZHS/1.0 (WordPress/' . get_bloginfo( 'version' ) . '; +https://mdziaulhasan.com/)',
			'headers'     => array(
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			),
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$synthetic_html = $this->generate_synthetic_dom( $url );

			return array(
				'html'        => $synthetic_html,
				'headers'     => array(),
				'status_code' => 0,
				'error'       => sprintf( __( 'Loopback HTTP request failed: %s. Using internal template diagnostics.', 'seo-inspector-zhs' ), $error_message ),
			);
		}

		return array(
			'html'        => wp_remote_retrieve_body( $response ),
			'headers'     => wp_remote_retrieve_headers( $response ),
			'status_code' => wp_remote_retrieve_response_code( $response ),
			'error'       => null,
		);
	}

	/**
	 * Generate synthetic DOM representation when server blocks self-HTTP requests.
	 *
	 * @param string $url Audited URL.
	 * @return string Minimal HTML markup.
	 */
	private function generate_synthetic_dom( $url = '' ) {
		$site_title = get_bloginfo( 'name' );
		$site_desc  = get_bloginfo( 'description' );
		$page_info  = $this->resolve_page_info( $url );
		$title_text = ( ! empty( $page_info['name'] ) && strpos( $page_info['name'], 'Front Page' ) === false )
			? $page_info['name'] . ' &#8211; ' . $site_title
			: $site_title . ' &#8211; ' . $site_desc;

		return '<!DOCTYPE html><html lang="' . esc_attr( get_locale() ) . '"><head><meta charset="UTF-8">' .
			'<meta name="viewport" content="width=device-width, initial-scale=1">' .
			'<title>' . esc_html( $title_text ) . '</title>' .
			'<meta name="description" content="' . esc_attr( $site_desc ) . '">' .
			'<link rel="canonical" href="' . esc_url( $page_info['url'] ) . '">' .
			'</head><body><a class="skip-link screen-reader-text" href="#content">Skip to content</a>' .
			'<header><h1>' . esc_html( $page_info['name'] ) . '</h1></header>' .
			'<main id="content"><p>' . esc_html( $site_desc ) . '</p></main>' .
			'<footer><a href="' . esc_url( home_url( '/privacy-policy' ) ) . '">Privacy Policy</a></footer>' .
			'</body></html>';
	}

	/* -------------------------------------------------------------------------
	 * 1. USABILITY CHECKS
	 * ------------------------------------------------------------------------- */

	/**
	 * Check 1: HTTPS & SSL Security.
	 */
	private function check_https( $site_url, $headers ) {
		$is_https_url  = ( 0 === strpos( $site_url, 'https://' ) );
		$is_ssl_active = is_ssl() || $is_https_url;

		// Check HSTS header
		$has_hsts = false;
		if ( ! empty( $headers ) ) {
			foreach ( $headers as $key => $val ) {
				if ( strtolower( $key ) === 'strict-transport-security' ) {
					$has_hsts = true;
					break;
				}
			}
		}

		if ( $is_ssl_active && $is_https_url ) {
			$status  = 'pass';
			$score   = 1.0;
			$summary = __( 'Page is securely delivered over HTTPS with active SSL.', 'seo-inspector-zhs' );
			$details = sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Protocol: HTTPS. %3$s', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url,
				$has_hsts ? __( 'HSTS header detected.', 'seo-inspector-zhs' ) : __( 'Consider adding Strict-Transport-Security (HSTS) headers for maximum security.', 'seo-inspector-zhs' )
			);
			$recommendation = __( 'Keep your SSL certificate auto-renewing and consider setting the HSTS header via your server or security plugin.', 'seo-inspector-zhs' );
		} elseif ( $is_https_url ) {
			$status  = 'partial';
			$score   = 0.5;
			$summary = __( 'HTTPS configured, but SSL verification may have mixed content or missing redirects.', 'seo-inspector-zhs' );
			$details = sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Ensure all HTTP traffic enforces 301 redirects to HTTPS.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url
			);
			$recommendation = __( 'Ensure automatic 301 redirection from HTTP to HTTPS in .htaccess, Nginx, or via a security plugin.', 'seo-inspector-zhs' );
		} else {
			$status  = 'fail';
			$score   = 0.0;
			$summary = __( 'Insecure HTTP protocol detected! Search engines penalize non-HTTPS websites.', 'seo-inspector-zhs' );
			$details = sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Page is currently served over insecure HTTP.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url
			);
			$recommendation = __( 'Install a valid SSL certificate (e.g. Let\'s Encrypt) and update WordPress Address and Site Address to https:// under Settings > General.', 'seo-inspector-zhs' );
		}

		return array(
			'id'             => 'check_https',
			'title'          => __( 'HTTPS & SSL Security', 'seo-inspector-zhs' ),
			'category'       => 'usability',
			'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
			'status'         => $status,
			'score'          => $score,
			'impact'         => 'high',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => $summary,
			'details'        => $details,
			'recommendation' => $recommendation,
		);
	}

	/**
	 * Check 2: Mobile Viewport Meta Tag.
	 */
	private function check_mobile_viewport( $xpath ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_mobile_viewport', __( 'Mobile Viewport Meta', 'seo-inspector-zhs' ), 'usability', 'high' );
		}

		$nodes = $xpath->query( '//meta[@name="viewport"]' );

		if ( $nodes->length === 0 ) {
			return array(
				'id'             => 'check_mobile_viewport',
				'title'          => __( 'Mobile Viewport Meta', 'seo-inspector-zhs' ),
				'category'       => 'usability',
				'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
				'status'         => 'fail',
				'score'          => 0.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Missing <meta name="viewport"> tag! Mobile browsers will render desktop scale.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'No viewport tag was located in the document <head>. This hurts mobile UX and fails Google Mobile-First Indexing standards.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url
				),
				'recommendation' => __( 'Add <meta name="viewport" content="width=device-width, initial-scale=1"> into your theme header.php or child theme.', 'seo-inspector-zhs' ),
			);
		}

		$content          = $nodes->item(0)->getAttribute( 'content' );
		$has_width_device = ( stripos( $content, 'width=device-width' ) !== false );
		$blocks_zoom      = ( stripos( $content, 'user-scalable=no' ) !== false || stripos( $content, 'maximum-scale=1' ) !== false );

		if ( $has_width_device && ! $blocks_zoom ) {
			return array(
				'id'             => 'check_mobile_viewport',
				'title'          => __( 'Mobile Viewport Meta', 'seo-inspector-zhs' ),
				'category'       => 'usability',
				'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Responsive mobile viewport meta tag correctly configured.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Detected: <meta name="viewport" content="%3$s">. User zooming is preserved.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					esc_attr( $content )
				),
				'recommendation' => __( 'Perfect! No changes required for mobile viewport settings.', 'seo-inspector-zhs' ),
			);
		}

		if ( $blocks_zoom ) {
			return array(
				'id'             => 'check_mobile_viewport',
				'title'          => __( 'Mobile Viewport Meta', 'seo-inspector-zhs' ),
				'category'       => 'usability',
				'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Viewport found, but user zooming is disabled (violates WCAG accessibility).', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Detected: content="%3$s". Properties like user-scalable=no or maximum-scale=1 prevent visually impaired users from zooming.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					esc_attr( $content )
				),
				'recommendation' => __( 'Remove user-scalable=no and maximum-scale=1 to comply with mobile accessibility standards.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_mobile_viewport',
			'title'          => __( 'Mobile Viewport Meta', 'seo-inspector-zhs' ),
			'category'       => 'usability',
			'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
			'status'         => 'partial',
			'score'          => 0.5,
			'impact'         => 'medium',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => __( 'Viewport meta tag is non-standard or missing width=device-width.', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Detected content: "%3$s".', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url,
				esc_attr( $content )
			),
			'recommendation' => __( 'Standardize viewport tag to: content="width=device-width, initial-scale=1".', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Check 3: Contact Options & Multi-Channel Availability.
	 */
	private function check_contact_options( $xpath, $html ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_contact_options', __( 'Contact Options & Availability', 'seo-inspector-zhs' ), 'usability', 'medium' );
		}

		$has_tel    = ( $xpath->query( '//a[starts-with(@href, "tel:")]' )->length > 0 );
		$has_mailto = ( $xpath->query( '//a[starts-with(@href, "mailto:")]' )->length > 0 );

		// Check for social profile links
		$social_patterns = array( 'facebook.com', 'twitter.com', 'x.com', 'linkedin.com', 'instagram.com', 'youtube.com', 'tiktok.com', 'github.com' );
		$found_socials   = array();
		$all_links       = $xpath->query( '//a[@href]' );
		foreach ( $all_links as $link ) {
			$href = strtolower( $link->getAttribute( 'href' ) );
			foreach ( $social_patterns as $social ) {
				if ( strpos( $href, $social ) !== false && ! in_array( $social, $found_socials, true ) ) {
					$found_socials[] = $social;
				}
			}
		}

		// Check for interactive contact form presence
		$has_form = (
			$xpath->query( '//form[contains(@class, "wpcf7") or contains(@class, "wpforms") or contains(@class, "gform") or contains(@class, "fluentform") or contains(@class, "elementor-form")]' )->length > 0
			|| $xpath->query( '//form//input[@type="email"]' )->length > 0
			|| stripos( $html, 'contact-form' ) !== false
		);

		$channels_found = array();
		if ( $has_tel ) $channels_found[] = __( 'Phone (tel: link)', 'seo-inspector-zhs' );
		if ( $has_mailto ) $channels_found[] = __( 'Email (mailto: link)', 'seo-inspector-zhs' );
		if ( ! empty( $found_socials ) ) $channels_found[] = sprintf( __( 'Social Profiles (%s)', 'seo-inspector-zhs' ), implode( ', ', array_map( 'ucfirst', array_slice( $found_socials, 0, 3 ) ) ) );
		if ( $has_form ) $channels_found[] = __( 'Contact Form Block', 'seo-inspector-zhs' );

		// Detect if a dedicated Contact page exists in WordPress
		$contact_page = get_page_by_path( 'contact' ) ?: get_page_by_path( 'contact-us' );
		$contact_page_str = '';
		if ( $contact_page && $contact_page->post_status === 'publish' ) {
			$contact_page_str = "
" . sprintf( __( 'Dedicated Contact Page found on site: "%1$s" (URL: %2$s)', 'seo-inspector-zhs' ), get_the_title( $contact_page->ID ), get_permalink( $contact_page->ID ) );
		}

		$count = count( $channels_found );

		if ( $count >= 3 ) {
			$status  = 'pass';
			$score   = 1.0;
			$summary = sprintf( __( 'Excellent contact accessibility (%d communication channels discovered).', 'seo-inspector-zhs' ), $count );
			$rec     = __( 'Great job providing diverse touchpoints for customers and search engine trust signals.', 'seo-inspector-zhs' );
		} elseif ( $count >= 1 ) {
			$status  = 'partial';
			$score   = 0.5;
			$summary = sprintf( __( 'Basic contact channel detected (%d channels found), but could be improved.', 'seo-inspector-zhs' ), $count );
			$rec     = __( 'Add direct clickable phone (tel:) links, an interactive inquiry form, or active social media links.', 'seo-inspector-zhs' );
		} else {
			$status  = 'fail';
			$score   = 0.0;
			$summary = __( 'No active phone, email, contact form, or social touchpoints detected on this page.', 'seo-inspector-zhs' );
			$rec     = __( 'Add clear contact methods in the header/footer (clickable phone number, email, and contact form) to establish business trust.', 'seo-inspector-zhs' );
		}

		$details = sprintf(
			__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . '%3$s%4$s', 'seo-inspector-zhs' ),
			$this->current_page_name,
			$this->current_page_url,
			! empty( $channels_found ) ? sprintf( __( 'Active channels on this page: %s', 'seo-inspector-zhs' ), implode( ' • ', $channels_found ) ) : __( 'No direct communication channels detected on this page.', 'seo-inspector-zhs' ),
			$contact_page_str
		);

		return array(
			'id'             => 'check_contact_options',
			'title'          => __( 'Contact Options & Availability', 'seo-inspector-zhs' ),
			'category'       => 'usability',
			'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
			'status'         => $status,
			'score'          => $score,
			'impact'         => 'medium',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => $summary,
			'details'        => $details,
			'recommendation' => $rec,
		);
	}

	/**
	 * Check 4: Entry Popup & Interstitial UX Check.
	 */
	private function check_entry_popup( $xpath, $html ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_entry_popup', __( 'Entry Popup & Modals', 'seo-inspector-zhs' ), 'usability', 'medium' );
		}

		$popup_patterns = array(
			'pum-', 'elementor-popup', 'sg-popup', 'hustle-modal',
			'optinmonster', 'wp-popup', 'spu-box', 'fancybox', 'magnific-popup'
		);

		$detected_popups = array();
		foreach ( $popup_patterns as $pattern ) {
			if ( stripos( $html, $pattern ) !== false ) {
				$detected_popups[] = $pattern;
			}
		}

		$modal_nodes = $xpath->query( '//*[contains(@class, "modal") or contains(@class, "popup") or contains(@id, "modal") or contains(@id, "popup")]' );
		$has_close_button = false;

		if ( $modal_nodes->length > 0 ) {
			$close_nodes = $xpath->query( '//*[contains(@class, "close") or @aria-label="Close" or contains(@class, "dismiss")]' );
			$has_close_button = ( $close_nodes->length > 0 );
		}

		if ( empty( $detected_popups ) && $modal_nodes->length === 0 ) {
			return array(
				'id'             => 'check_entry_popup',
				'title'          => __( 'Entry Popup & Interstitials', 'seo-inspector-zhs' ),
				'category'       => 'usability',
				'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'No intrusive entry popups or blocking overlays detected on initial load.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Clean viewport allows visitors to access primary content immediately without intrusive interstitials.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url
				),
				'recommendation' => __( 'Continue avoiding immediate full-screen interstitials to maintain positive Google Page Experience scores.', 'seo-inspector-zhs' ),
			);
		}

		if ( $has_close_button || count( $detected_popups ) <= 1 ) {
			return array(
				'id'             => 'check_entry_popup',
				'title'          => __( 'Entry Popup & Interstitials', 'seo-inspector-zhs' ),
				'category'       => 'usability',
				'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Popup or modal overlay containers detected on page.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Detected popup hooks/containers: %3$s. An accessible dismiss mechanism was found.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					implode( ', ', $detected_popups )
				),
				'recommendation' => __( 'Verify that popups are triggered on intent/exit or after significant scrolling, and not blocking mobile viewport on load.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_entry_popup',
			'title'          => __( 'Entry Popup & Interstitials', 'seo-inspector-zhs' ),
			'category'       => 'usability',
			'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
			'status'         => 'fail',
			'score'          => 0.0,
			'impact'         => 'high',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => __( 'Potential intrusive interstitial detected without clear accessible dismissal.', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Multiple popup classes identified: %3$s.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url,
				implode( ', ', $detected_popups )
			),
			'recommendation' => __( 'Ensure popups can be easily closed on mobile touchscreens with a minimum 48x48px tap target and aria-label="Close".', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Check 5: Footer Trust Links & Dead '#' Links.
	 */
	private function check_footer_trust_links( $xpath ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_footer_trust_links', __( 'Footer Trust & Legal Links', 'seo-inspector-zhs' ), 'usability', 'medium' );
		}

		$footer_nodes = $xpath->query( '//footer | //*[contains(@class, "footer") or contains(@id, "footer")]' );
		if ( $footer_nodes->length === 0 ) {
			$footer_context = $xpath->query( '//a' );
		} else {
			$footer_context = $xpath->query( './/a', $footer_nodes->item(0) );
		}

		$has_privacy = false;
		$has_terms   = false;
		$dead_links  = 0;
		$total_links = 0;

		$privacy_id    = (int) get_option( 'wp_page_for_privacy_policy' );
		$privacy_title = $privacy_id ? get_the_title( $privacy_id ) : __( 'Privacy Policy', 'seo-inspector-zhs' );
		$privacy_url   = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
		if ( ! empty( $privacy_url ) ) {
			$has_privacy = true;
		}

		foreach ( $footer_context as $link ) {
			$total_links++;
			$href = trim( $link->getAttribute( 'href' ) );
			$text = strtolower( trim( $link->textContent ) );

			if ( $href === '#' || $href === '' ) {
				$dead_links++;
			}

			if ( stripos( $href, 'privacy' ) !== false || stripos( $text, 'privacy' ) !== false || stripos( $text, 'datenschutz' ) !== false ) {
				$has_privacy = true;
			}
			if ( stripos( $href, 'terms' ) !== false || stripos( $text, 'terms' ) !== false || stripos( $text, 'conditions' ) !== false || stripos( $text, 'tos' ) !== false ) {
				$has_terms = true;
			}
		}

		$privacy_evidence = $has_privacy
			? sprintf( __( 'Privacy Policy detected ("%1$s" - URL: %2$s).', 'seo-inspector-zhs' ), $privacy_title, $privacy_url ?: __( 'Linked in footer', 'seo-inspector-zhs' ) )
			: __( 'No active Privacy Policy page linked in footer.', 'seo-inspector-zhs' );

		if ( $has_privacy && $dead_links === 0 ) {
			return array(
				'id'             => 'check_footer_trust_links',
				'title'          => __( 'Footer Trust & Legal Links', 'seo-inspector-zhs' ),
				'category'       => 'usability',
				'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Footer contains valid legal trust links without broken "#" placeholders.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . '%3$s Inspected %4$d footer links; zero dead hash "#" anchors found.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$privacy_evidence,
					$total_links
				),
				'recommendation' => __( 'Great! Keep legal policies updated annually to protect user trust and satisfy search engine E-E-A-T standards.', 'seo-inspector-zhs' ),
			);
		}

		if ( $has_privacy && $dead_links > 0 ) {
			return array(
				'id'             => 'check_footer_trust_links',
				'title'          => __( 'Footer Trust & Legal Links', 'seo-inspector-zhs' ),
				'category'       => 'usability',
				'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'Privacy link present, but %d dead dummy "#" link(s) found in footer.', 'seo-inspector-zhs' ), $dead_links ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . '%3$s Found %4$d links pointing to empty "#". This signals unfinished web design to search engine crawlers.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$privacy_evidence,
					$dead_links
				),
				'recommendation' => __( 'Replace or remove dead "#" placeholder links in your footer menus and widgets.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_footer_trust_links',
			'title'          => __( 'Footer Trust & Legal Links', 'seo-inspector-zhs' ),
			'category'       => 'usability',
			'category_label' => __( 'Usability', 'seo-inspector-zhs' ),
			'status'         => 'fail',
			'score'          => 0.0,
			'impact'         => 'high',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => __( 'Missing explicit Privacy Policy or Terms of Service links in footer.', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Search engine quality raters and ad networks require explicit privacy and compliance links in global site footers.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url
			),
			'recommendation' => __( 'Assign a Privacy Policy page under Settings > Privacy and add it to your primary footer menu.', 'seo-inspector-zhs' ),
		);
	}

	/* -------------------------------------------------------------------------
	 * 2. ACCESSIBILITY CHECKS
	 * ------------------------------------------------------------------------- */

	/**
	 * Check 6: Skip-to-content Link.
	 */
	private function check_skip_to_content( $xpath, $dom ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_skip_to_content', __( 'Skip-to-Content Navigation', 'seo-inspector-zhs' ), 'accessibility', 'medium' );
		}

		$skip_nodes = $xpath->query( '//a[starts-with(@href, "#") and (contains(@class, "skip") or contains(@class, "screen-reader-text") or contains(translate(text(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "skip") or contains(@href, "content") or contains(@href, "main"))]' );

		if ( $skip_nodes->length === 0 ) {
			return array(
				'id'             => 'check_skip_to_content',
				'title'          => __( 'Skip-to-Content Navigation', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'fail',
				'score'          => 0.0,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'No skip-to-content link found at top of page (WCAG 2.4.1 Bypass Blocks).', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Keyboard and screen reader users must navigate through the entire header on this page without a skip link.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url
				),
				'recommendation' => __( 'Add <a class="skip-link screen-reader-text" href="#content">Skip to content</a> right after <body>.', 'seo-inspector-zhs' ),
			);
		}

		$first_skip = $skip_nodes->item(0);
		$target_id  = ltrim( $first_skip->getAttribute( 'href' ), '#' );

		$target_found = false;
		if ( ! empty( $target_id ) ) {
			$target_nodes = $xpath->query( '//*[@id="' . esc_attr( $target_id ) . '"]' );
			if ( $target_nodes && $target_nodes->length > 0 ) {
				$target_found = true;
			}
		}

		if ( $target_found ) {
			return array(
				'id'             => 'check_skip_to_content',
				'title'          => __( 'Skip-to-Content Navigation', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Skip-to-content link exists and connects to a valid anchor ID.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Found skip link "%3$s" targeting matching element with id="%4$s".', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					esc_html( trim( $first_skip->textContent ) ),
					esc_attr( $target_id )
				),
				'recommendation' => __( 'All set! Keyboard accessibility navigation meets standard guidelines.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_skip_to_content',
			'title'          => __( 'Skip-to-Content Navigation', 'seo-inspector-zhs' ),
			'category'       => 'accessibility',
			'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
			'status'         => 'partial',
			'score'          => 0.5,
			'impact'         => 'medium',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => __( 'Skip link exists, but target anchor ID does not exist in DOM.', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Skip link points to "#%3$s", but no HTML element possesses id="%4$s".', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url,
				esc_attr( $target_id ),
				esc_attr( $target_id )
			),
			'recommendation' => sprintf( __( 'Add id="%s" to your main content <main> wrapper in your theme.', 'seo-inspector-zhs' ), esc_attr( $target_id ) ),
		);
	}

	/**
	 * Check 7: Image Alt Text & Media Optimization.
	 */
	private function check_image_alt_text( $xpath ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_image_alt_text', __( 'Image Alt Attributes', 'seo-inspector-zhs' ), 'accessibility', 'high' );
		}

		$img_nodes  = $xpath->query( '//img' );
		$total_imgs = $img_nodes->length;

		if ( $total_imgs === 0 ) {
			return array(
				'id'             => 'check_image_alt_text',
				'title'          => __( 'Image Alt Attributes', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'No inline <img> tags detected on this page.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'No image accessibility issues detected on this page.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url
				),
				'recommendation' => __( 'When uploading images to media library, always supply meaningful descriptive alternative text.', 'seo-inspector-zhs' ),
			);
		}

		$missing_alt     = 0;
		$junk_alt        = 0;
		$has_alt         = 0;
		$redundant_title = 0;
		$flagged_images  = array();

		$junk_keywords = array( 'image', 'photo', 'picture', 'pic', 'untitled', 'img', 'dsc_', 'screenshot' );

		foreach ( $img_nodes as $img ) {
			$has_alt_attr = $img->hasAttribute( 'alt' );
			$alt_val      = trim( $img->getAttribute( 'alt' ) );
			$title_val    = trim( $img->getAttribute( 'title' ) );
			$src          = $img->getAttribute( 'src' );
			$img_filename = ! empty( $src ) ? basename( (string) wp_parse_url( $src, PHP_URL_PATH ) ) : __( 'Inline image', 'seo-inspector-zhs' );

			if ( ! $has_alt_attr ) {
				$missing_alt++;
				$flagged_images[] = $img_filename . ' [missing alt]';
			} elseif ( $alt_val === '' ) {
				$has_alt++;
			} else {
				$lower_alt = strtolower( $alt_val );
				$is_junk   = false;
				if ( preg_match( '/.(jpg|jpeg|png|webp|gif|svg)$/i', $alt_val ) ) {
					$is_junk = true;
				}
				foreach ( $junk_keywords as $junk ) {
					if ( $lower_alt === $junk ) {
						$is_junk = true;
						break;
					}
				}

				if ( $is_junk ) {
					$junk_alt++;
					$flagged_images[] = $img_filename . ' [generic alt: "' . $alt_val . '"]';
				} else {
					$has_alt++;
				}

				if ( ! empty( $title_val ) && strtolower( $title_val ) === $lower_alt ) {
					$redundant_title++;
				}
			}
		}

		$flagged_details = '';
		if ( ! empty( $flagged_images ) ) {
			$sample = array_slice( $flagged_images, 0, 5 );
			$flagged_details = "
" . sprintf( __( 'Flagged image elements on this page: %s', 'seo-inspector-zhs' ), implode( ', ', $sample ) );
			if ( count( $flagged_images ) > 5 ) {
				$flagged_details .= sprintf( __( ' (+%d more)', 'seo-inspector-zhs' ), count( $flagged_images ) - 5 );
			}
		}

		// Also check WordPress media library for items attached to other pages
		$db_cross_check = '';
		$db_imgs = get_posts( array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => 3,
			'meta_query'     => array(
				array(
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				),
			),
		) );
		if ( ! empty( $db_imgs ) ) {
			$db_items = array();
			foreach ( $db_imgs as $dimg ) {
				$parent_id = $dimg->post_parent;
				if ( $parent_id > 0 ) {
					$db_items[] = sprintf( '%s (Page: "%s" - %s)', basename( (string) get_attached_file( $dimg->ID ) ), get_the_title( $parent_id ), get_permalink( $parent_id ) );
				}
			}
			if ( ! empty( $db_items ) ) {
				$db_cross_check = "
" . sprintf( __( 'Other pages with uncaptioned Media Library images: %s', 'seo-inspector-zhs' ), implode( '; ', $db_items ) );
			}
		}

		$valid_ratio = ( $total_imgs - $missing_alt - $junk_alt ) / $total_imgs;

		if ( $missing_alt === 0 && $junk_alt === 0 ) {
			return array(
				'id'             => 'check_image_alt_text',
				'title'          => __( 'Image Alt Attributes', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'All %d images on this page have proper alt attributes.', 'seo-inspector-zhs' ), $total_imgs ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Inspected %3$d images on this page. 0 missing alt tags; 0 junk placeholders found.%4$s', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$total_imgs,
					$db_cross_check
				),
				'recommendation' => __( 'Great image accessibility! Keep providing descriptive context for visual assets.', 'seo-inspector-zhs' ),
			);
		}

		if ( $valid_ratio >= 0.70 ) {
			return array(
				'id'             => 'check_image_alt_text',
				'title'          => __( 'Image Alt Attributes', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( '%d of %d images missing alt text or have generic file names.', 'seo-inspector-zhs' ), ( $missing_alt + $junk_alt ), $total_imgs ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Found %3$d missing alt tags, %4$d generic/file-name alts on this page.%5$s%6$s', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$missing_alt,
					$junk_alt,
					$flagged_details,
					$db_cross_check
				),
				'recommendation' => __( 'Edit images in the WordPress Media Library or page editor and fill in the "Alternative Text" field with concise descriptions.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_image_alt_text',
			'title'          => __( 'Image Alt Attributes', 'seo-inspector-zhs' ),
			'category'       => 'accessibility',
			'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
			'status'         => 'fail',
			'score'          => 0.0,
			'impact'         => 'high',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => sprintf( __( 'Significant image accessibility issues: %d missing alt tags out of %d images on this page.', 'seo-inspector-zhs' ), $missing_alt, $total_imgs ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Over 30%% of images lack alt attributes (%3$d missing, %4$d junk).%5$s%6$s', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url,
				$missing_alt,
				$junk_alt,
				$flagged_details,
				$db_cross_check
			),
			'recommendation' => __( 'Add descriptive alt text to all informative images to satisfy WCAG 1.1.1 and Google Image Search ranking factors.', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Check 8: Accessible Form Labels.
	 */
	private function check_form_labels( $xpath ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_form_labels', __( 'Form Input Labels', 'seo-inspector-zhs' ), 'accessibility', 'high' );
		}

		$inputs       = $xpath->query( '//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="image")] | //textarea | //select' );
		$total_inputs = $inputs->length;

		if ( $total_inputs === 0 ) {
			return array(
				'id'             => 'check_form_labels',
				'title'          => __( 'Form Input Labels', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'No input form controls on this page to evaluate.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'No forms detected requiring explicit label pairing.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url
				),
				'recommendation' => __( 'Ensure any forms added in the future include explicit <label for="id"> elements.', 'seo-inspector-zhs' ),
			);
		}

		$unlabeled_count  = 0;
		$placeholder_only = 0;
		$flagged_inputs   = array();

		foreach ( $inputs as $input ) {
			$id              = $input->getAttribute( 'id' );
			$name            = $input->getAttribute( 'name' );
			$type            = $input->getAttribute( 'type' ) ?: 'text';
			$aria_label      = $input->getAttribute( 'aria-label' );
			$aria_labelledby = $input->getAttribute( 'aria-labelledby' );
			$placeholder     = $input->getAttribute( 'placeholder' );

			$has_label = false;

			if ( ! empty( $aria_label ) || ! empty( $aria_labelledby ) ) {
				$has_label = true;
			} elseif ( ! empty( $id ) ) {
				$labels = $xpath->query( '//label[@for="' . esc_attr( $id ) . '"]' );
				if ( $labels && $labels->length > 0 ) {
					$has_label = true;
				}
			}

			if ( ! $has_label ) {
				$parent_label = $xpath->query( 'ancestor::label', $input );
				if ( $parent_label && $parent_label->length > 0 ) {
					$has_label = true;
				}
			}

			if ( ! $has_label ) {
				$tag_id = 'input[' . $type . ']' . ( $name ? '[name="' . $name . '"]' : ( $id ? '#' . $id : '' ) );
				if ( ! empty( $placeholder ) ) {
					$placeholder_only++;
					$flagged_inputs[] = $tag_id . ' (placeholder: "' . $placeholder . '")';
				} else {
					$unlabeled_count++;
					$flagged_inputs[] = $tag_id;
				}
			}
		}

		$flagged_text = '';
		if ( ! empty( $flagged_inputs ) ) {
			$sample = array_slice( $flagged_inputs, 0, 4 );
			$flagged_text = "
" . sprintf( __( 'Flagged fields: %s', 'seo-inspector-zhs' ), implode( ', ', $sample ) );
		}

		if ( $unlabeled_count === 0 && $placeholder_only === 0 ) {
			return array(
				'id'             => 'check_form_labels',
				'title'          => __( 'Form Input Labels', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'All %d form inputs on this page have explicit accessible labels.', 'seo-inspector-zhs' ), $total_inputs ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Inspected %3$d interactive fields on this page; 100%% have associated <label> or aria-label attributes.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$total_inputs
				),
				'recommendation' => __( 'Excellent accessibility practice! Screen readers can identify all form inputs.', 'seo-inspector-zhs' ),
			);
		}

		if ( $unlabeled_count === 0 && $placeholder_only > 0 ) {
			return array(
				'id'             => 'check_form_labels',
				'title'          => __( 'Form Input Labels', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( '%d form fields on this page rely solely on placeholders instead of proper labels.', 'seo-inspector-zhs' ), $placeholder_only ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Placeholders disappear upon typing and are not reliably announced by assistive screen readers.%3$s', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$flagged_text
				),
				'recommendation' => __( 'Add explicit <label for="..."> tags or aria-label attributes to all input fields.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_form_labels',
			'title'          => __( 'Form Input Labels', 'seo-inspector-zhs' ),
			'category'       => 'accessibility',
			'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
			'status'         => 'fail',
			'score'          => 0.0,
			'impact'         => 'high',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => sprintf( __( '%d form inputs completely lack labels and accessible descriptions on this page.', 'seo-inspector-zhs' ), ( $unlabeled_count + $placeholder_only ) ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Detected %3$d fields without any label or aria-label attribute on this page.%4$s', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url,
				$unlabeled_count,
				$flagged_text
			),
			'recommendation' => __( 'Pair each input field with an explicit <label for="field-id"> to satisfy WCAG 3.3.2 standards.', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Check 9: Heading Structure & H1-H6 Hierarchy.
	 */
	private function check_heading_structure( $xpath ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_heading_structure', __( 'Heading Hierarchy (H1-H6)', 'seo-inspector-zhs' ), 'accessibility', 'high' );
		}

		$h1_nodes = $xpath->query( '//h1' );
		$h1_count = $h1_nodes->length;

		$headings        = $xpath->query( '//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]' );
		$hierarchy       = array();
		$empty_headings  = 0;
		$irregular_jumps = array();
		$prev_level      = 0;

		$h1_samples = array();
		foreach ( $h1_nodes as $h1 ) {
			$txt = trim( $h1->textContent );
			if ( ! empty( $txt ) ) {
				$h1_samples[] = '"' . mb_substr( $txt, 0, 45 ) . '"';
			}
		}

		foreach ( $headings as $h ) {
			$tag   = strtolower( $h->nodeName );
			$level = (int) substr( $tag, 1 );
			$text  = trim( $h->textContent );

			if ( empty( $text ) ) {
				$empty_headings++;
			}

			if ( $prev_level > 0 && ( $level - $prev_level ) > 1 ) {
				$irregular_jumps[] = "H{$prev_level} &rarr; H{$level}";
			}

			$prev_level  = $level;
			$hierarchy[] = $tag;
		}

		$h1_evidence = ! empty( $h1_samples ) ? implode( ', ', $h1_samples ) : __( 'None', 'seo-inspector-zhs' );

		if ( $h1_count === 1 && empty( $irregular_jumps ) && $empty_headings === 0 ) {
			return array(
				'id'             => 'check_heading_structure',
				'title'          => __( 'Heading Hierarchy (H1-H6)', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Flawless heading hierarchy: Exactly one H1 and sequential progression.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'H1: %3$s. Total headings: %4$d (%5$s). No skipped levels or empty headings on this page.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$h1_evidence,
					count( $hierarchy ),
					implode( ' > ', array_slice( $hierarchy, 0, 6 ) )
				),
				'recommendation' => __( 'Heading outline is properly structured for screen readers and search bots.', 'seo-inspector-zhs' ),
			);
		}

		if ( $h1_count > 1 || ! empty( $irregular_jumps ) ) {
			$issues = array();
			if ( $h1_count > 1 ) $issues[] = sprintf( __( '%d H1 tags detected: %s (recommended: exactly 1)', 'seo-inspector-zhs' ), $h1_count, $h1_evidence );
			if ( ! empty( $irregular_jumps ) ) $issues[] = sprintf( __( 'Skipped heading levels: %s', 'seo-inspector-zhs' ), implode( ', ', array_unique( $irregular_jumps ) ) );
			if ( $empty_headings > 0 ) $issues[] = sprintf( __( '%d empty heading tag(s)', 'seo-inspector-zhs' ), $empty_headings );

			return array(
				'id'             => 'check_heading_structure',
				'title'          => __( 'Heading Hierarchy (H1-H6)', 'seo-inspector-zhs' ),
				'category'       => 'accessibility',
				'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'Heading order needs optimization on this page (%d H1 tags or skipped levels).', 'seo-inspector-zhs' ), $h1_count ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Issues found: %3$s.' . "
" . 'Headings outline: %4$s.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					implode( ' • ', $issues ),
					implode( ' > ', array_slice( $hierarchy, 0, 8 ) )
				),
				'recommendation' => __( 'Consolidate multiple H1 tags into a single primary H1, and ensure headings don\'t skip levels (e.g. H2 should precede H3, not H4).', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_heading_structure',
			'title'          => __( 'Heading Hierarchy (H1-H6)', 'seo-inspector-zhs' ),
			'category'       => 'accessibility',
			'category_label' => __( 'Accessibility', 'seo-inspector-zhs' ),
			'status'         => 'fail',
			'score'          => 0.0,
			'impact'         => 'high',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => __( 'Missing <h1> tag entirely! Page lacks a primary topic heading.', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'A single H1 heading is vital for search engine topic comprehension and screen reader document navigation.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url
			),
			'recommendation' => __( 'Ensure your page template renders a prominent <h1> containing the page primary keyword.', 'seo-inspector-zhs' ),
		);
	}

	/* -------------------------------------------------------------------------
	 * 3. SEO CHECKS
	 * ------------------------------------------------------------------------- */

	/**
	 * Check 10: Title Tag Optimization.
	 */
	private function check_title_tag( $xpath ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_title_tag', __( 'HTML Title Tag', 'seo-inspector-zhs' ), 'seo', 'high' );
		}

		$title_nodes = $xpath->query( '//head/title | //title' );

		if ( $title_nodes->length === 0 || empty( trim( $title_nodes->item(0)->textContent ) ) ) {
			return array(
				'id'             => 'check_title_tag',
				'title'          => __( 'HTML Title Tag', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'fail',
				'score'          => 0.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Missing <title> tag! This is a critical SEO penalty.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'No <title> element was found in the document head for this page.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url
				),
				'recommendation' => __( 'Configure your SEO plugin (Yoast, Rank Math, AIOSEO) or add add_theme_support("title-tag") in functions.php.', 'seo-inspector-zhs' ),
			);
		}

		$title      = trim( $title_nodes->item(0)->textContent );
		$length     = mb_strlen( $title );
		$is_generic = ( stripos( $title, 'Just another WordPress site' ) !== false );

		if ( $is_generic ) {
			return array(
				'id'             => 'check_title_tag',
				'title'          => __( 'HTML Title Tag', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'fail',
				'score'          => 0.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Default "Just another WordPress site" detected in title tag!', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Current Title: "%3$s" (%4$d characters).', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					esc_html( $title ),
					$length
				),
				'recommendation' => __( 'Update your site tagline under Settings > General or configure your SEO plugin title template.', 'seo-inspector-zhs' ),
			);
		}

		if ( $length >= 45 && $length <= 65 ) {
			return array(
				'id'             => 'check_title_tag',
				'title'          => __( 'HTML Title Tag', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'Optimal title tag length (%d characters).', 'seo-inspector-zhs' ), $length ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Title: "%3$s" (%4$d chars). Falls into optimal SERP display window (50-60 chars).', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					esc_html( $title ),
					$length
				),
				'recommendation' => __( 'Title tag is in optimal shape for Google Search snippet display.', 'seo-inspector-zhs' ),
			);
		}

		$summary = ( $length < 45 )
			? sprintf( __( 'Title is too short (%d characters). Optimal: 50-60 characters.', 'seo-inspector-zhs' ), $length )
			: sprintf( __( 'Title is too long (%d characters). It will be truncated in Google search results.', 'seo-inspector-zhs' ), $length );

		return array(
			'id'             => 'check_title_tag',
			'title'          => __( 'HTML Title Tag', 'seo-inspector-zhs' ),
			'category'       => 'seo',
			'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
			'status'         => 'partial',
			'score'          => 0.5,
			'impact'         => 'medium',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => $summary,
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Title: "%3$s" (%4$d chars). Target range: 45 to 65 characters.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url,
				esc_html( $title ),
				$length
			),
			'recommendation' => __( 'Refine title tag to be between 50 and 60 characters with primary brand and relevant keywords.', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Check 11: Meta Description Tag.
	 */
	private function check_meta_description( $xpath ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_meta_description', __( 'Meta Description Tag', 'seo-inspector-zhs' ), 'seo', 'high' );
		}

		$meta_nodes = $xpath->query( '//head/meta[@name="description"] | //meta[@name="description"]' );

		if ( $meta_nodes->length === 0 ) {
			return array(
				'id'             => 'check_meta_description',
				'title'          => __( 'Meta Description Tag', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'fail',
				'score'          => 0.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Missing meta description on this page! Search engines will generate automated snippets.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'No <meta name="description"> tag was discovered in the document head for this page.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url
				),
				'recommendation' => __( 'Add a persuasive 140-160 character meta description using your SEO plugin to boost SERP click-through rates (CTR).', 'seo-inspector-zhs' ),
			);
		}

		$desc   = trim( $meta_nodes->item(0)->getAttribute( 'content' ) );
		$length = mb_strlen( $desc );

		if ( $length >= 120 && $length <= 165 ) {
			return array(
				'id'             => 'check_meta_description',
				'title'          => __( 'Meta Description Tag', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'Optimal meta description length (%d characters).', 'seo-inspector-zhs' ), $length ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Content: "%3$s" (%4$d chars). Displays cleanly across desktop and mobile SERPs.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					esc_html( $desc ),
					$length
				),
				'recommendation' => __( 'Meta description is well within ideal parameters (140-160 characters).', 'seo-inspector-zhs' ),
			);
		}

		$summary = ( $length < 120 )
			? sprintf( __( 'Meta description is short (%d characters). Recommended: 140-160 characters.', 'seo-inspector-zhs' ), $length )
			: sprintf( __( 'Meta description is too long (%d characters) and risks truncation on mobile devices.', 'seo-inspector-zhs' ), $length );

		return array(
			'id'             => 'check_meta_description',
			'title'          => __( 'Meta Description Tag', 'seo-inspector-zhs' ),
			'category'       => 'seo',
			'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
			'status'         => 'partial',
			'score'          => 0.5,
			'impact'         => 'medium',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => $summary,
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Content: "%3$s" (%4$d chars). Ideal length is 140 to 160 characters.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url,
				esc_html( $desc ),
				$length
			),
			'recommendation' => __( 'Adjust your meta description to between 140-160 characters, including a clear call to action.', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Check 12: Canonical Tag & Robots Meta.
	 */
	private function check_canonical_and_robots( $xpath ) {
		$is_public = (bool) get_option( 'blog_public', 1 );

		if ( ! $is_public ) {
			return array(
				'id'             => 'check_canonical_and_robots',
				'title'          => __( 'Canonical & Robots Indexing', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'fail',
				'score'          => 0.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'CRITICAL: Site is blocking search engines (Search Engine Visibility is disabled)!', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'WordPress option blog_public is 0. "Discourage search engines from indexing this site" is enabled.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url
				),
				'recommendation' => __( 'Go to WordPress Settings > Reading and uncheck "Discourage search engines from indexing this site".', 'seo-inspector-zhs' ),
			);
		}

		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_canonical_and_robots', __( 'Canonical & Robots Indexing', 'seo-inspector-zhs' ), 'seo', 'high' );
		}

		$canonical_nodes = $xpath->query( '//head/link[@rel="canonical"] | //link[@rel="canonical"]' );
		$robots_nodes    = $xpath->query( '//head/meta[@name="robots"] | //meta[@name="robots"]' );

		$has_canonical  = ( $canonical_nodes->length > 0 );
		$canonical_href = $has_canonical ? $canonical_nodes->item(0)->getAttribute( 'href' ) : '';

		$has_noindex = false;
		$robots_content = '';
		if ( $robots_nodes->length > 0 ) {
			$robots_content = strtolower( $robots_nodes->item(0)->getAttribute( 'content' ) );
			if ( strpos( $robots_content, 'noindex' ) !== false ) {
				$has_noindex = true;
			}
		}

		if ( $has_noindex ) {
			return array(
				'id'             => 'check_canonical_and_robots',
				'title'          => __( 'Canonical & Robots Indexing', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'fail',
				'score'          => 0.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( '<meta name="robots" content="noindex"> tag is instructing crawlers not to index this page!', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'A "noindex" directive was found in the meta robots tag: "%3$s".', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					esc_html( $robots_content )
				),
				'recommendation' => __( 'Remove the noindex directive in your SEO plugin or theme header unless this page is deliberately private.', 'seo-inspector-zhs' ),
			);
		}

		if ( $has_canonical ) {
			return array(
				'id'             => 'check_canonical_and_robots',
				'title'          => __( 'Canonical & Robots Indexing', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Canonical URL is active and search engine indexing is permitted.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Canonical link: <link rel="canonical" href="%3$s">. Robots allows indexing.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					esc_url( $canonical_href )
				),
				'recommendation' => __( 'Canonical link is configured properly to prevent duplicate content penalties.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_canonical_and_robots',
			'title'          => __( 'Canonical & Robots Indexing', 'seo-inspector-zhs' ),
			'category'       => 'seo',
			'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
			'status'         => 'partial',
			'score'          => 0.5,
			'impact'         => 'medium',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => __( 'Page is indexable, but missing self-referencing canonical <link> tag.', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Without a canonical link, tracking parameters (?utm_source=) can cause duplicate content issues.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url
			),
			'recommendation' => __( 'Add a canonical link tag using WordPress Core wp_head hooks or an SEO plugin.', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Check 13: Open Graph & Twitter Card Meta Tags.
	 */
	private function check_open_graph_twitter( $xpath ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_open_graph_twitter', __( 'Social Meta (Open Graph & Twitter)', 'seo-inspector-zhs' ), 'seo', 'medium' );
		}

		$og_tags = array(
			'og:title'       => $xpath->query( '//meta[@property="og:title"]' ),
			'og:description' => $xpath->query( '//meta[@property="og:description"]' ),
			'og:image'       => $xpath->query( '//meta[@property="og:image"]' ),
			'og:url'         => $xpath->query( '//meta[@property="og:url"]' ),
			'twitter:card'   => $xpath->query( '//meta[@name="twitter:card"] | //meta[@property="twitter:card"]' ),
			'twitter:image'  => $xpath->query( '//meta[@name="twitter:image"] | //meta[@property="twitter:image"]' ),
		);

		$detected = array();
		foreach ( $og_tags as $key => $node_list ) {
			if ( $node_list && $node_list->length > 0 ) {
				$detected[ $key ] = $node_list->item(0)->getAttribute( 'content' );
			}
		}

		$has_og_image = ! empty( $detected['og:image'] ) || ! empty( $detected['twitter:image'] );
		$has_og_title = ! empty( $detected['og:title'] );
		$has_og_desc  = ! empty( $detected['og:description'] );
		$has_twitter  = ! empty( $detected['twitter:card'] );

		if ( $has_og_image && $has_og_title && $has_og_desc && $has_twitter ) {
			return array(
				'id'             => 'check_open_graph_twitter',
				'title'          => __( 'Social Meta (Open Graph & Twitter)', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Complete social sharing tags configured (OG Title, Desc, Image & Twitter Card).', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'OG Image: %3$s. Twitter card: %4$s. Previews will display rich media on LinkedIn, Facebook, and X.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					esc_url( $detected['og:image'] ?? $detected['twitter:image'] ),
					esc_html( $detected['twitter:card'] )
				),
				'recommendation' => __( 'Social cards are in great shape for maximum viral engagement.', 'seo-inspector-zhs' ),
			);
		}

		if ( $has_og_title || $has_og_desc ) {
			$missing = array();
			if ( ! $has_og_image ) $missing[] = 'og:image';
			if ( ! $has_twitter ) $missing[] = 'twitter:card';

			return array(
				'id'             => 'check_open_graph_twitter',
				'title'          => __( 'Social Meta (Open Graph & Twitter)', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'Partial social metadata on this page. Missing: %s.', 'seo-inspector-zhs' ), implode( ', ', $missing ) ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Found %3$d of 6 recommended social tags. Missing high-res social thumbnail image hurts click rates.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					count( $detected )
				),
				'recommendation' => __( 'Upload a dedicated social share image (1200x630px) in your SEO plugin social settings.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_open_graph_twitter',
			'title'          => __( 'Social Meta (Open Graph & Twitter)', 'seo-inspector-zhs' ),
			'category'       => 'seo',
			'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
			'status'         => 'fail',
			'score'          => 0.0,
			'impact'         => 'medium',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => __( 'No Open Graph or Twitter Card tags found on this page.', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Links shared on social media will appear unformatted without rich imagery or tailored summaries.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url
			),
			'recommendation' => __( 'Activate Open Graph and Twitter Card features in Yoast SEO, Rank Math, or SEOPress.', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Check 14: XML Sitemap Reachability.
	 */
	private function check_xml_sitemap() {
		$home = home_url( '/' );
		$sitemap_candidates = array(
			$home . 'wp-sitemap.xml',
			$home . 'sitemap_index.xml',
			$home . 'sitemap.xml',
		);

		$found_url   = null;
		$status_code = null;

		foreach ( $sitemap_candidates as $candidate ) {
			$res = wp_remote_head( $candidate, array( 'timeout' => 8, 'sslverify' => false ) );
			if ( ! is_wp_error( $res ) ) {
				$code = wp_remote_retrieve_response_code( $res );
				if ( $code === 200 ) {
					$found_url   = $candidate;
					$status_code = $code;
					break;
				}
			}
		}

		// Fallback: check if WordPress core sitemaps are active
		if ( ! $found_url && function_exists( 'wp_sitemaps_get_server' ) ) {
			$found_url   = $home . 'wp-sitemap.xml';
			$status_code = 200;
		}

		if ( $found_url && $status_code === 200 ) {
			return array(
				'id'             => 'check_xml_sitemap',
				'title'          => __( 'XML Sitemap Reachability', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => __( 'Site XML Sitemap', 'seo-inspector-zhs' ),
				'page_url'       => $found_url,
				'summary'        => __( 'Active and reachable XML sitemap discovered.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Sitemap Index URL: %1$s' . "
" . 'Status: HTTP %2$s OK. XML structure is reachable by search crawlers.', 'seo-inspector-zhs' ),
					esc_url( $found_url ),
					$status_code
				),
				'recommendation' => __( 'Submit this sitemap URL into Google Search Console and Bing Webmaster Tools.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_xml_sitemap',
			'title'          => __( 'XML Sitemap Reachability', 'seo-inspector-zhs' ),
			'category'       => 'seo',
			'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
			'status'         => 'fail',
			'score'          => 0.0,
			'impact'         => 'high',
			'page_name'      => __( 'Site XML Sitemap', 'seo-inspector-zhs' ),
			'page_url'       => $home . 'wp-sitemap.xml',
			'summary'        => __( 'No active XML sitemap detected at standard endpoints (/wp-sitemap.xml or /sitemap_index.xml).', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Tested standard paths on site (%1$s): /wp-sitemap.xml, /sitemap_index.xml, /sitemap.xml. None returned HTTP 200.', 'seo-inspector-zhs' ),
				$home
			),
			'recommendation' => __( 'Enable WordPress core sitemaps or activate sitemap generation in an SEO plugin.', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Check 15: Indexable Content Depth & Word Count.
	 */
	private function check_indexable_content_depth( $xpath, $html ) {
		// Count published pages & posts in database
		$published_posts = wp_count_posts( 'post' );
		$published_pages = wp_count_posts( 'page' );
		$total_published = ( $published_posts->publish ?? 0 ) + ( $published_pages->publish ?? 0 );

		// Sample list of published pages from database
		$sample_posts = get_posts( array(
			'post_type'      => array( 'page', 'post' ),
			'post_status'    => 'publish',
			'posts_per_page' => 4,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		$sample_pages = array();
		if ( ! empty( $sample_posts ) ) {
			foreach ( $sample_posts as $sp ) {
				$sample_pages[] = sprintf( '"%s" (%s)', get_the_title( $sp->ID ), get_permalink( $sp->ID ) );
			}
		}
		$sample_list_str = ! empty( $sample_pages ) ? "
" . sprintf( __( 'Sample published pages in database: %s', 'seo-inspector-zhs' ), implode( '; ', $sample_pages ) ) : '';

		// Calculate clean word count from front-end body
		$word_count = 0;
		if ( $xpath ) {
			$body_nodes = $xpath->query( '//body' );
			if ( $body_nodes->length > 0 ) {
				$raw_text   = $body_nodes->item(0)->textContent;
				$clean_text = preg_replace( '/s+/', ' ', trim( $raw_text ) );
				$word_count = str_word_count( $clean_text );
			}
		}

		if ( $word_count >= 350 && $total_published >= 4 ) {
			return array(
				'id'             => 'check_indexable_content_depth',
				'title'          => __( 'Indexable Content Depth', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'Substantial content depth: %d words on this page across %d published pages.', 'seo-inspector-zhs' ), $word_count, $total_published ),
				'details'        => sprintf(
					__( 'Audited Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Word count on this page: %3$d words. Site catalog: %4$d published items (%5$d posts, %6$d pages).%7$s', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$word_count,
					$total_published,
					( $published_posts->publish ?? 0 ),
					( $published_pages->publish ?? 0 ),
					$sample_list_str
				),
				'recommendation' => __( 'Healthy text density provides search engines with context to index relevant keywords.', 'seo-inspector-zhs' ),
			);
		}

		if ( $word_count >= 150 || $total_published >= 1 ) {
			return array(
				'id'             => 'check_indexable_content_depth',
				'title'          => __( 'Indexable Content Depth', 'seo-inspector-zhs' ),
				'category'       => 'seo',
				'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'Thin content risk: %d words on this page with %d total published posts/pages.', 'seo-inspector-zhs' ), $word_count, $total_published ),
				'details'        => sprintf(
					__( 'Audited Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Word count: %3$d words. Search engines favor comprehensive topic coverage (minimum 350-500 words for primary landing pages).%4$s', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$word_count,
					$sample_list_str
				),
				'recommendation' => __( 'Expand your page copy with descriptive service details, FAQs, testimonials, and value propositions.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_indexable_content_depth',
			'title'          => __( 'Indexable Content Depth', 'seo-inspector-zhs' ),
			'category'       => 'seo',
			'category_label' => __( 'SEO', 'seo-inspector-zhs' ),
			'status'         => 'fail',
			'score'          => 0.0,
			'impact'         => 'high',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => sprintf( __( 'Critically thin or empty content (%d words detected).', 'seo-inspector-zhs' ), $word_count ),
			'details'        => sprintf(
				__( 'Audited Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Search engines may classify pages with under 150 words as thin or low-value content.%3$s', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url,
				$sample_list_str
			),
			'recommendation' => __( 'Add descriptive paragraphs, headings, and clear introductory explanations to this page.', 'seo-inspector-zhs' ),
		);
	}

	/* -------------------------------------------------------------------------
	 * 4. GEO & AI-READINESS CHECKS
	 * ------------------------------------------------------------------------- */

	/**
	 * Check 16: Structured Data (JSON-LD & Schema.org).
	 */
	private function check_structured_data_json_ld( $xpath, $html ) {
		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_structured_data_json_ld', __( 'Schema.org JSON-LD Structured Data', 'seo-inspector-zhs' ), 'geo_ai', 'high' );
		}

		$scripts       = $xpath->query( '//script[@type="application/ld+json"]' );
		$total_schemas = $scripts->length;

		if ( $total_schemas === 0 ) {
			return array(
				'id'             => 'check_structured_data_json_ld',
				'title'          => __( 'Schema.org JSON-LD Structured Data', 'seo-inspector-zhs' ),
				'category'       => 'geo_ai',
				'category_label' => __( 'GEO & AI-Readiness', 'seo-inspector-zhs' ),
				'status'         => 'fail',
				'score'          => 0.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'No JSON-LD structured data detected! AI engines cannot parse entity knowledge graph.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Zero <script type="application/ld+json"> blocks were found in the page markup.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url
				),
				'recommendation' => __( 'Implement Schema.org JSON-LD (Organization, LocalBusiness, WebSite) via an SEO plugin or custom script.', 'seo-inspector-zhs' ),
			);
		}

		$schema_types = array();
		$has_same_as  = false;
		$has_address  = false;

		for ( $i = 0; $i < $total_schemas; $i++ ) {
			$raw_json = trim( $scripts->item( $i )->textContent );
			$data     = json_decode( $raw_json, true );

			if ( is_array( $data ) ) {
				$this->extract_schema_properties( $data, $schema_types, $has_same_as, $has_address );
			}
		}

		$types_list = array_unique( $schema_types );

		$has_business_entity = (
			in_array( 'Organization', $types_list, true ) ||
			in_array( 'LocalBusiness', $types_list, true ) ||
			in_array( 'RealEstateAgent', $types_list, true ) ||
			in_array( 'Corporation', $types_list, true )
		);

		if ( $has_business_entity && $has_same_as ) {
			return array(
				'id'             => 'check_structured_data_json_ld',
				'title'          => __( 'Schema.org JSON-LD Structured Data', 'seo-inspector-zhs' ),
				'category'       => 'geo_ai',
				'category_label' => __( 'GEO & AI-Readiness', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'Rich entity Schema.org detected: %s with sameAs social profiles.', 'seo-inspector-zhs' ), implode( ', ', $types_list ) ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Found %3$d JSON-LD block(s). Types: %4$s. sameAs entity links confirmed.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$total_schemas,
					implode( ', ', $types_list )
				),
				'recommendation' => __( 'Outstanding structured data setup! Helps AI search engines understand your exact brand identity.', 'seo-inspector-zhs' ),
			);
		}

		if ( ! empty( $types_list ) ) {
			return array(
				'id'             => 'check_structured_data_json_ld',
				'title'          => __( 'Schema.org JSON-LD Structured Data', 'seo-inspector-zhs' ),
				'category'       => 'geo_ai',
				'category_label' => __( 'GEO & AI-Readiness', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => sprintf( __( 'Basic Schema detected (%s), but missing sameAs entity reconciliation or address.', 'seo-inspector-zhs' ), implode( ', ', $types_list ) ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Detected types: %3$s. Adding sameAs social links and physical location links boosts AI credibility.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					implode( ', ', $types_list )
				),
				'recommendation' => __( 'Enrich your Organization/LocalBusiness schema with sameAs URLs (Wikipedia, LinkedIn, X, Facebook) and postal address.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_structured_data_json_ld',
			'title'          => __( 'Schema.org JSON-LD Structured Data', 'seo-inspector-zhs' ),
			'category'       => 'geo_ai',
			'category_label' => __( 'GEO & AI-Readiness', 'seo-inspector-zhs' ),
			'status'         => 'partial',
			'score'          => 0.5,
			'impact'         => 'medium',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => __( 'JSON-LD script tags found, but unparseable or missing recognized Schema.org types.', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Please validate your JSON-LD using Google\'s Rich Results Test tool.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url
			),
			'recommendation' => __( 'Test your homepage on validator.schema.org to fix syntax errors.', 'seo-inspector-zhs' ),
		);
	}

	/**
	 * Recursive helper to parse nested Schema.org structures and @graph arrays.
	 */
	private function extract_schema_properties( $node, &$types, &$has_same_as, &$has_address ) {
		if ( ! is_array( $node ) ) return;

		if ( isset( $node['@type'] ) ) {
			if ( is_array( $node['@type'] ) ) {
				foreach ( $node['@type'] as $t ) { $types[] = $t; }
			} else {
				$types[] = $node['@type'];
			}
		}

		if ( ! empty( $node['sameAs'] ) ) {
			$has_same_as = true;
		}

		if ( ! empty( $node['address'] ) || ! empty( $node['geo'] ) ) {
			$has_address = true;
		}

		if ( isset( $node['@graph'] ) && is_array( $node['@graph'] ) ) {
			foreach ( $node['@graph'] as $child ) {
				$this->extract_schema_properties( $child, $types, $has_same_as, $has_address );
			}
		}

		foreach ( $node as $val ) {
			if ( is_array( $val ) ) {
				$this->extract_schema_properties( $val, $types, $has_same_as, $has_address );
			}
		}
	}

	/**
	 * Check 17: AI & Entity Brand Readiness.
	 */
	private function check_ai_entity_readiness( $xpath, $html ) {
		$site_title       = get_bloginfo( 'name' );
		$is_default_title = ( empty( $site_title ) || stripos( $site_title, 'WordPress' ) !== false );

		if ( ! $xpath ) {
			return $this->build_missing_dom_check( 'check_ai_entity_readiness', __( 'AI Entity & Brand Readiness', 'seo-inspector-zhs' ), 'geo_ai', 'high' );
		}

		$og_site_name_nodes = $xpath->query( '//meta[@property="og:site_name"]' );
		$og_site_name       = $og_site_name_nodes->length > 0 ? trim( $og_site_name_nodes->item(0)->getAttribute( 'content' ) ) : '';

		$has_og_brand = ( ! empty( $og_site_name ) && stripos( $og_site_name, $site_title ) !== false );

		$has_about_link = ( $xpath->query( '//a[contains(@href, "about") or contains(translate(text(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "about")]' )->length > 0 );
		$has_copyright  = ( stripos( $html, '&copy;' ) !== false || stripos( $html, '©' ) !== false || stripos( $html, 'all rights reserved' ) !== false );

		$signals = array();
		if ( ! $is_default_title ) $signals[] = sprintf( __( 'Custom Brand Name: "%s"', 'seo-inspector-zhs' ), esc_html( $site_title ) );
		if ( $has_og_brand ) $signals[] = __( 'OG Site Name Aligned', 'seo-inspector-zhs' );
		if ( $has_about_link ) $signals[] = __( 'Discovered "About Us" Entity Link', 'seo-inspector-zhs' );
		if ( $has_copyright ) $signals[] = __( 'Footer Copyright & Ownership Statement', 'seo-inspector-zhs' );

		$signal_count = count( $signals );

		if ( $signal_count >= 3 && ! $is_default_title ) {
			return array(
				'id'             => 'check_ai_entity_readiness',
				'title'          => __( 'AI Entity & Brand Readiness', 'seo-inspector-zhs' ),
				'category'       => 'geo_ai',
				'category_label' => __( 'GEO & AI-Readiness', 'seo-inspector-zhs' ),
				'status'         => 'pass',
				'score'          => 1.0,
				'impact'         => 'high',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'High AI & Knowledge Graph entity consistency detected.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Discovered %3$d entity signals: %4$s. LLMs (ChatGPT, Gemini, Perplexity) can unambiguously identify your brand.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					$signal_count,
					implode( ' • ', $signals )
				),
				'recommendation' => __( 'Maintain uniform naming across Google Business Profile, Wikidata, and major social directories.', 'seo-inspector-zhs' ),
			);
		}

		if ( $signal_count >= 1 && ! $is_default_title ) {
			return array(
				'id'             => 'check_ai_entity_readiness',
				'title'          => __( 'AI Entity & Brand Readiness', 'seo-inspector-zhs' ),
				'category'       => 'geo_ai',
				'category_label' => __( 'GEO & AI-Readiness', 'seo-inspector-zhs' ),
				'status'         => 'partial',
				'score'          => 0.5,
				'impact'         => 'medium',
				'page_name'      => $this->current_page_name,
				'page_url'       => $this->current_page_url,
				'summary'        => __( 'Partial entity identity signals found across meta tags and markup.', 'seo-inspector-zhs' ),
				'details'        => sprintf(
					__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Active signals: %3$s. Lacks explicit og:site_name consistency or dedicated About Us entity link.', 'seo-inspector-zhs' ),
					$this->current_page_name,
					$this->current_page_url,
					implode( ' • ', $signals )
				),
				'recommendation' => __( 'Include a clear "About Us" link in your main navigation and explicitly define og:site_name in your SEO plugin.', 'seo-inspector-zhs' ),
			);
		}

		return array(
			'id'             => 'check_ai_entity_readiness',
			'title'          => __( 'AI Entity & Brand Readiness', 'seo-inspector-zhs' ),
			'category'       => 'geo_ai',
			'category_label' => __( 'GEO & AI-Readiness', 'seo-inspector-zhs' ),
			'status'         => 'fail',
			'score'          => 0.0,
			'impact'         => 'high',
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => __( 'Weak brand entity definition! AI engines will struggle to identify your organization.', 'seo-inspector-zhs' ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'Generic or missing site branding, lack of og:site_name, and no about or entity ownership markers found.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url
			),
			'recommendation' => __( 'Specify your brand name under Settings > General, create a comprehensive About Us page, and establish brand social profiles.', 'seo-inspector-zhs' ),
		);
	}

	/* -------------------------------------------------------------------------
	 * SCORING & UTILITY METHODS
	 * ------------------------------------------------------------------------- */

	/**
	 * Calculate composite and category scores.
	 *
	 * @param array $checks Array of check results.
	 * @return array Calculated metrics.
	 */
	private function calculate_scores( $checks ) {
		$total_score   = 0.0;
		$passed_count  = 0;
		$partial_count = 0;
		$failed_count  = 0;

		$categories = array(
			'usability'     => array( 'sum' => 0.0, 'total' => 0, 'passed' => 0, 'partial' => 0, 'failed' => 0 ),
			'accessibility' => array( 'sum' => 0.0, 'total' => 0, 'passed' => 0, 'partial' => 0, 'failed' => 0 ),
			'seo'           => array( 'sum' => 0.0, 'total' => 0, 'passed' => 0, 'partial' => 0, 'failed' => 0 ),
			'geo_ai'        => array( 'sum' => 0.0, 'total' => 0, 'passed' => 0, 'partial' => 0, 'failed' => 0 ),
		);

		foreach ( $checks as $check ) {
			$score = floatval( $check['score'] );
			$total_score += $score;

			$cat = $check['category'];
			if ( isset( $categories[ $cat ] ) ) {
				$categories[ $cat ]['sum'] += $score;
				$categories[ $cat ]['total']++;
			}

			if ( $check['status'] === 'pass' ) {
				$passed_count++;
				if ( isset( $categories[ $cat ] ) ) $categories[ $cat ]['passed']++;
			} elseif ( $check['status'] === 'partial' ) {
				$partial_count++;
				if ( isset( $categories[ $cat ] ) ) $categories[ $cat ]['partial']++;
			} else {
				$failed_count++;
				if ( isset( $categories[ $cat ] ) ) $categories[ $cat ]['failed']++;
			}
		}

		$total_checks = count( $checks );
		$overall_score = ( $total_checks > 0 ) ? (int) round( ( $total_score / $total_checks ) * 100 ) : 0;

		// Letter Grade Assignment
		if ( $overall_score >= 90 ) {
			$grade       = 'A';
			$grade_label = __( 'Excellent (Grade A)', 'seo-inspector-zhs' );
		} elseif ( $overall_score >= 80 ) {
			$grade       = 'B';
			$grade_label = __( 'Good (Grade B)', 'seo-inspector-zhs' );
		} elseif ( $overall_score >= 70 ) {
			$grade       = 'C+';
			$grade_label = __( 'Average (Grade C+)', 'seo-inspector-zhs' );
		} elseif ( $overall_score >= 60 ) {
			$grade       = 'D';
			$grade_label = __( 'Needs Work (Grade D)', 'seo-inspector-zhs' );
		} else {
			$grade       = 'F';
			$grade_label = __( 'Critical Issues (Grade F)', 'seo-inspector-zhs' );
		}

		// Calculate category fractions and percentages
		$category_scores = array();
		foreach ( $categories as $key => $data ) {
			$pct      = ( $data['total'] > 0 ) ? (int) round( ( $data['sum'] / $data['total'] ) * 100 ) : 0;
			$score_10 = ( $data['total'] > 0 ) ? round( ( $data['sum'] / $data['total'] ) * 10, 1 ) : 0.0;

			$category_scores[ $key ] = array(
				'score'      => $data['sum'],
				'max'        => $data['total'],
				'score_10'   => $score_10,
				'percentage' => $pct,
				'passed'     => $data['passed'],
				'partial'    => $data['partial'],
				'failed'     => $data['failed'],
			);
		}

		return array(
			'overall_score'   => $overall_score,
			'grade'           => $grade,
			'grade_label'     => $grade_label,
			'passed_count'    => $passed_count,
			'partial_count'   => $partial_count,
			'failed_count'    => $failed_count,
			'category_scores' => $category_scores,
		);
	}

	/**
	 * Helper for handling missing DOM documents.
	 */
	private function build_missing_dom_check( $id, $title, $category, $impact ) {
		return array(
			'id'             => $id,
			'title'          => $title,
			'category'       => $category,
			'category_label' => ucfirst( $category ),
			'status'         => 'partial',
			'score'          => 0.5,
			'impact'         => $impact,
			'page_name'      => $this->current_page_name,
			'page_url'       => $this->current_page_url,
			'summary'        => sprintf( __( 'Unable to parse DOM markup for page: %s.', 'seo-inspector-zhs' ), $this->current_page_name ),
			'details'        => sprintf(
				__( 'Page: "%1$s"' . "
" . 'URL: %2$s' . "
" . 'The target page could not be parsed via DOMDocument during the scan.', 'seo-inspector-zhs' ),
				$this->current_page_name,
				$this->current_page_url
			),
			'recommendation' => __( 'Check server error logs or loopback connection settings.', 'seo-inspector-zhs' ),
		);
	}
}
