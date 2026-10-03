=== SEO Inspector ZHS ===
Contributors: zhsaikot
Donate link: https://mdziaulhasan.com/
Tags: seo, audit, accessibility, site-health, json-ld
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-ready SEO audit & analytics engine with live front-end DOM diagnostics, accessibility, and GEO/AI-readiness verification.

== Description ==

**SEO Inspector ZHS** is a production-grade, modular WordPress SEO audit and site-health analytics suite. Engineered with a sleek SaaS interface, it evaluates your live website against a comprehensive 17-point checklist covering modern search engine indexing, Core Web Usability, WCAG 2.4.1 Accessibility, and Generative Engine Optimization (GEO / AI-Readiness).

Unlike conventional tools that merely inspect static post meta, SEO Inspector ZHS executes real-time DOM parsing, validates HTTP transport headers, analyzes Schema.org JSON-LD knowledge graphs, and inspects database indexable content depth—delivering actionable, developer-grade insights directly within your WordPress administration dashboard.

### Why SEO Inspector ZHS?
* **Zero External Cloud Dependencies:** All diagnostics run entirely on your server; your site metrics remain 100% private.
* **Instant Asynchronous Re-checks:** Execute live re-audits via WordPress REST API with animated visual progress meters.
* **Client Presentation Ready:** 1-click executive report export featuring print-optimized CSS for PDF delivery, plus structured JSON downloads.
* **Generative Engine (GEO) Ready:** Evaluates Schema.org entity disambiguation, `sameAs` reconciliation, and cross-channel brand coherence so AI search engines (ChatGPT, Google Gemini, Perplexity) accurately identify your business.

== Features ==

SEO Inspector ZHS divides your site's health into four core strategic pillars across 17 automated checks:

### 1. Usability Diagnostics
* **HTTPS & SSL Transport Security:** Checks active SSL protocol enforcement and detects Strict-Transport-Security (HSTS) headers.
* **Mobile Viewport Optimization:** Validates responsive `<meta name="viewport">` attributes and flags accessibility-blocking parameters like `user-scalable=no`.
* **Multi-Channel Contact Options:** Scans for clickable `tel:` links, `mailto:` links, social profiles, and interactive contact form blocks.
* **Entry Popup & Interstitials:** Detects invasive overlays and verifies accessible dismiss controls to safeguard mobile page experience scores.
* **Footer Trust & Legal Links:** Validates Privacy Policy, Terms of Service links, and flags dead `#` dummy anchors.

### 2. Accessibility (WCAG 2.4.1 Standards)
* **Skip-to-Content Navigation:** Checks for bypass navigation links and confirms their target IDs exist in the DOM.
* **Image Alt Text Diagnostics:** Audits images for missing alt tags, junk filenames (e.g. `IMG_001.jpg`), and redundant title mirrors.
* **Form Input Labels:** Verifies that form inputs have explicit `<label for="...">` pairings or ARIA labels.
* **Heading Hierarchy (H1-H6):** Enforces a single H1 tag, prevents skipped heading levels, and flags empty heading tags.

### 3. SEO Essentials
* **HTML Title Tag:** Assesses SERP character length (optimal 50-60 characters) and flags default WordPress taglines.
* **Meta Description:** Evaluates SERP preview snippet length (optimal 140-160 characters) to maximize organic CTR.
* **Canonical & Robots Indexing:** Validates self-referencing canonical tags, inspects `noindex` flags, and checks search engine visibility.
* **Open Graph & Twitter Cards:** Ensures social preview cards (`og:image`, `og:title`, `twitter:card`) render correctly when shared.
* **XML Sitemap Reachability:** Probes core WordPress and SEO plugin sitemap endpoints (`/wp-sitemap.xml`, `/sitemap_index.xml`).
* **Indexable Content Depth:** Analyzes front-page text density and calculates database published content volume to prevent thin-content penalties.

### 4. GEO & AI-Readiness (Search 2.0 / Knowledge Graphs)
* **Schema.org JSON-LD Structured Data:** Deep-parses JSON-LD graphs for `Organization`, `LocalBusiness`, physical addresses, and `sameAs` entity authority profiles.
* **AI & Entity Brand Readiness:** Audits brand consistency across site titles, Open Graph tags, Schema entities, and copyright notices for LLM search indexing.

== Installation ==

### Automatic Installation (Recommended)
1. Log in to your WordPress administrator dashboard.
2. Navigate to **Plugins > Add New**.
3. Search for `SEO Inspector ZHS`.
4. Click **Install Now**, then click **Activate**.
5. Access the suite from the left menu: **SEO Inspector**.

### Manual Installation
1. Download the `seo-inspector-zhs.zip` file.
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Select the zip file and click **Install Now**.
4. Activate the plugin and open **SEO Inspector** from your admin menu.

== Frequently Asked Questions ==

= Does running an audit slow down my site or affect visitor performance? =
No. Audits run on-demand only when an administrator views the dashboard or clicks "Re-check Audit". Results are cached in WordPress transients for 12 hours to eliminate redundant queries. Your front-end visitors experience zero overhead.

= Who can access the SEO Inspector dashboard and REST endpoints? =
Access is strictly restricted to authenticated users with the `manage_options` capability (typically site Administrators). All API routes are protected by capability checks and CSRF nonces (`X-WP-Nonce`).

= What is GEO / AI-Readiness? =
Generative Engine Optimization (GEO) ensures AI search engines like ChatGPT, Google Gemini, and Perplexity can parse and identify your brand's knowledge graph entity. The plugin validates Schema.org JSON-LD types and `sameAs` profiles to help AI platforms recognize your business.

= Does this plugin replace plugins like Yoast SEO or Rank Math? =
SEO Inspector ZHS complements any SEO plugin. It acts as an independent diagnostic auditor, verifying that whatever SEO or theme setup you have is actually functioning correctly on the live front-end DOM.

= Can I export audit reports for my clients? =
Yes. Click the **Export Report** button to launch a print-optimized executive report ready for direct printing or saving as a clean PDF. You can also export structured JSON data via the **JSON** button.

= Is any data sent to third-party external servers? =
No. All scanning, parsing, and scoring logic runs 100% locally on your WordPress installation. No telemetry, tracking, or external API calls are made.

== Screenshots ==

1. SaaS analytics dashboard displaying overall grade dial, KPI stat cards, and core pillar scores.
2. Dynamic 17-point diagnostic audit engine showing granular pass/warning results and contextual inline recommendations.

== Changelog ==

= 1.0.0 =
* Initial official release for WordPress.org.
* 17-point dynamic DOM and database audit engine.
* Core modules: Usability, WCAG 2.4.1 Accessibility, SEO Essentials, and GEO/AI-Readiness.
* Asynchronous REST API integration with real-time audit triggers.
* Modern SaaS UI with animated circular score progress dial and KPI stat counters.
* Executive printable report stylesheet and JSON export functionality.
