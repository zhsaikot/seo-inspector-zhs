=== ZHS Site Audit and SEO Diagnostics ===
Contributors: zhsaikot
Donate link: https://mdziaulhasan.com/
Tags: site-audit, seo, accessibility, usability, json-ld
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive 17-point site audit and SEO diagnostics suite verifying usability, accessibility, indexing, schema, and GEO/AI readiness.

== Description ==

**ZHS Site Audit and SEO Diagnostics** is a production-grade, modular WordPress website auditing and SEO analytics suite. Engineered with a modern SaaS dashboard interface, it evaluates your live website against a rigorous 17-point diagnostic framework spanning Core Web Usability, WCAG 2.4.1 Accessibility, Technical SEO Essentials, and Generative Engine Optimization (GEO / AI-Readiness).

Unlike basic plugins that only inspect static post meta, ZHS Site Audit executes real-time DOM parsing, validates HTTP security and transport headers, deep-parses Schema.org JSON-LD knowledge graphs, checks XML sitemaps, and analyzes indexable database content depth—delivering actionable, developer-grade insights directly within your WordPress administration dashboard.

### Why ZHS Site Audit and SEO Diagnostics?
* **Multi-Page & Full Website Auditing:** Audit your primary homepage or scan representative published pages and posts across your entire site simultaneously, with granular per-page issue breakdowns.
* **Zero External Cloud Dependencies:** All diagnostics run 100% locally on your WordPress server; your site telemetry and database metrics remain completely private.
* **Instant Asynchronous Re-checks:** Execute live audits on demand via the WordPress REST API (`/wp-json/zhs-audit/v1/`) with animated visual progress meters.
* **Client Presentation Ready:** 1-click executive report export featuring print-optimized CSS for PDF delivery, plus structured JSON downloads.
* **Generative Engine (GEO) Ready:** Evaluates Schema.org entity disambiguation, `sameAs` reconciliation, and cross-channel brand coherence so AI search engines (ChatGPT, Google Gemini, Perplexity) accurately identify and attribute your business.

== Features ==

ZHS Site Audit and SEO Diagnostics divides your site's health into four core strategic pillars across 17 automated checks:

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
3. Search for `ZHS Site Audit and SEO Diagnostics`.
4. Click **Install Now**, then click **Activate**.
5. Access the suite from the left menu: **Site Audit**.

### Manual Installation
1. Download the `zhs-site-audit-seo-diagnostics.zip` file.
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Select the zip file and click **Install Now**.
4. Activate the plugin and open **Site Audit** from your admin menu.

== Frequently Asked Questions ==

= Does running an audit slow down my site or affect visitor performance? =
No. Audits run on-demand only when an administrator views the dashboard or clicks "Re-check Audit". Results are cached in WordPress transients for 12 hours to eliminate redundant queries. Your front-end visitors experience zero overhead.

= Who can access the audit dashboard and REST endpoints? =
Access is strictly restricted to authenticated users with the `manage_options` capability (typically site Administrators). All API routes are protected by capability checks and CSRF nonces (`X-WP-Nonce`).

= What is GEO / AI-Readiness? =
Generative Engine Optimization (GEO) ensures AI search engines like ChatGPT, Google Gemini, and Perplexity can parse and identify your brand's knowledge graph entity. The plugin validates Schema.org JSON-LD types and `sameAs` profiles to help AI platforms recognize your business.

= Does this plugin replace plugins like Yoast SEO or Rank Math? =
ZHS Site Audit complements any SEO plugin. It acts as an independent diagnostic auditor, verifying that whatever SEO or theme setup you have is actually functioning correctly on the live front-end DOM.

= Can I export audit reports for my clients? =
Yes. Click the **Export Report** button to launch a print-optimized executive report ready for direct printing or saving as a clean PDF. You can also export structured JSON data via the **JSON** button.

= Is any data sent to third-party external servers? =
No. All scanning, parsing, and scoring logic runs 100% locally on your WordPress installation. No telemetry, tracking, or external API calls are made.

== Screenshots ==

1. SaaS analytics dashboard displaying overall grade dial, KPI stat cards, and core pillar scores.
2. Dynamic 17-point diagnostic audit engine showing granular pass/warning results and contextual inline recommendations.

== Changelog ==

= 1.0.0 =
* Initial official release of ZHS Site Audit and SEO Diagnostics.
* 17-point dynamic DOM and database audit engine across Usability, Accessibility, SEO Essentials, and GEO / AI-Readiness.
* Multi-Page and Full Website Audit Selector with live filtering and granular per-page diagnostics.
* Real-time asynchronous REST API integration with animated KPI meters and interactive status filters.
* Executive printable report stylesheet and structured JSON export functionality.
