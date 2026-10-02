# SEO Inspector (SEO Inspector ZHS)

[![WordPress Tested](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![PHP Tested](https://img.shields.io/badge/PHP-7.4%20--%208.3-777bb4.svg)](https://php.net)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](http://www.gnu.org/licenses/gpl-2.0.txt)
[![Developer](https://img.shields.io/badge/Author-MD.%20Ziaul%20Hasan-0284c7.svg)](https://www.instagram.com/zhsaikot/)

A production-ready, modular WordPress SEO Audit & Analytics Dashboard plugin engineered with a clean SaaS aesthetic, live DOM/database diagnostics, real-time re-checks via WordPress REST API, and client-ready export reports.

---

## 🌟 Highlights & Key Features

- **Direct ZIP Upload Ready**: Zip the plugin folder directly, upload and activate straight in WordPress without manual directory shifting.
- **Modern SaaS UI**: Styled with clean white cards, soft 12px+ rounded corners, subtle drop shadows, fluid typography, and animated SVG progress dials.
- **Dynamic 17-Point Audit Engine**: Analyzes your live front-end DOM, HTTP response headers, meta tags, and WordPress database records.
- **Four Core Audit Pillars**:
  1. **Usability Diagnostics** (SSL/HTTPS, Mobile Viewport, Multi-channel contact links, Popups, and Footer trust links).
  2. **Accessibility Compliance** (WCAG skip-to-content links, Image alt tags, Form label pairings, and Heading hierarchy).
  3. **SEO Essentials** (SERP Title tag lengths, Meta description optimization, Canonical & Robots directives, Open Graph / Twitter cards, XML Sitemaps, and Indexable content depth).
  4. **GEO & AI-Readiness** (Schema.org JSON-LD graph analysis, `sameAs` entity reconciliation, and cross-channel brand disambiguation).
- **Asynchronous Live Re-Checks**: Trigger fresh scans with live visual loaders via the WordPress REST API (`/wp-json/seo-inspector/v1/audit/run`).
- **Interactive Filtering & Search**: Instant real-time filtering by status (Passed, Warnings, Critical), category tabs, and keyword search.
- **Client Presentation Ready**: 1-click **Export Report** with print-optimized CSS for executive PDF generation, plus structured **JSON Export**.

---

## 📁 Plugin Folder & Upload Structure

```text
seo-inspector-zhs/
├── seo-inspector-zhs.php       # Main plugin bootstrap file with headers
├── README.md                   # Complete documentation
├── includes/
│   ├── class-audit-engine.php  # Audit scanner, 17 checks & scoring logic
│   ├── class-admin-page.php    # Admin menu, settings & dashboard view loader
│   └── class-rest-api.php      # REST endpoints for live re-checking/fetching
├── assets/
│   ├── css/
│   │   └── dashboard.css       # Modern SaaS UI styling & print stylesheet
│   └── js/
│       └── dashboard.js        # REST triggers, chart rendering & re-check actions
└── templates/
    └── dashboard-view.php      # Main dashboard HTML structure
```

---

## 🚀 Installation & Quick Start

### Option A: Upload ZIP via WordPress Admin (Recommended)
1. Compress the `seo-inspector-zhs` directory into a `.zip` archive.
2. In WordPress Admin, navigate to **Plugins > Add New > Upload Plugin**.
3. Select the `seo-inspector-zhs.zip` file and click **Install Now**.
4. Click **Activate Plugin**.
5. Access the dashboard from the left admin menu: **SEO Inspector**.

### Option B: Direct Directory Deployment
1. Copy the `seo-inspector-zhs` folder directly into your site's `wp-content/plugins/` directory.
2. Navigate to **Plugins > Installed Plugins** in WordPress Admin.
3. Locate **SEO Inspector** and click **Activate**.

---

## 🔍 Core Audit Verification Engine (17 Checks)

The audit engine (`includes/class-audit-engine.php`) inspects your site dynamically:

### 1. Usability Checks
1. **HTTPS & SSL Security**: Verifies if the site is securely served over SSL/HTTPS and checks for HSTS transport security headers.
2. **Mobile Viewport Meta**: Validates `<meta name="viewport">` attributes (`width=device-width`, `initial-scale=1`) and warns if user zooming is blocked.
3. **Contact Options**: Verifies multi-channel accessibility by scanning for `tel:` phone links, `mailto:` links, social profiles, and interactive form blocks.
4. **Entry Popup & Interstitials**: Detects disruptive modal overlays and verifies the presence of accessible dismissal controls.
5. **Footer Trust & Legal Links**: Verifies active links to Privacy Policy and Terms of Service, flagging broken `#` placeholder anchors.

### 2. Accessibility Checks (WCAG Standards)
6. **Skip-to-Content Navigation**: Validates bypass blocks (`#content`, `#main`, skip links) targeting existing DOM IDs.
7. **Image Alt Text Optimization**: Audits all `<img>` tags for missing alt attributes, empty tags, generic file names, and junk placeholders.
8. **Form Input Labels**: Checks if form controls have explicit `<label for="...">` pairings, enclosing labels, or ARIA attributes.
9. **Heading Hierarchy (H1-H6)**: Validates exactly one H1 tag per page, checks for skipped levels (e.g., H2 straight to H5), and flags empty heading tags.

### 3. SEO Essentials
10. **HTML Title Tag**: Checks presence, detects default taglines ("Just another WordPress site"), and evaluates length against optimal SERP limits (50–60 chars).
11. **Meta Description**: Validates meta description presence and optimal length (140–160 chars) for maximum search result CTR.
12. **Canonical & Robots Indexing**: Verifies self-referencing canonical tags, checks for blocking `noindex` directives, and monitors WordPress Search Engine Visibility settings.
13. **Open Graph & Twitter Cards**: Scans for `og:title`, `og:description`, `og:image`, and `twitter:card` tags for social preview rendering.
14. **XML Sitemap Reachability**: Verifies reachability of common WordPress XML sitemaps (`/wp-sitemap.xml`, `/sitemap_index.xml`).
15. **Indexable Content Depth**: Queries published post/page volume in the database and analyzes front-page text density to avoid thin-content penalties.

### 4. GEO & AI-Readiness (Search 2.0 / Knowledge Graphs)
16. **Schema.org JSON-LD Structured Data**: Deep-scans `<script type="application/ld+json">` graphs for `Organization`, `LocalBusiness`, `RealEstateAgent`, physical addresses, and `sameAs` entity reconciliation profiles.
17. **AI & Entity Brand Readiness**: Verifies uniform brand consistency across site titles, Open Graph tags, Schema entities, and copyright statements so LLM search engines (ChatGPT, Gemini, Perplexity) disambiguate your brand.

---

## 📊 Scoring Methodology & Grade Bands

Each check yields a granular score:
- **PASS**: `1.0` points
- **PARTIAL / WARNING**: `0.5` points
- **FAIL**: `0.0` points

$$\text{Overall Score} = \left(\frac{\sum \text{Check Scores}}{17}\right) \times 100\%$$

### Grade Bands:
- **Grade A (90% - 100%)**: Optimal performance across all dimensions.
- **Grade B (80% - 89%)**: Solid baseline with minor optimization opportunities.
- **Grade C+ / C (70% - 79%)**: Average; key SEO or accessibility gaps detected.
- **Grade D (60% - 69%)**: Sub-optimal; missing vital metadata or mobile directives.
- **Grade F (< 60%)**: Critical technical issues requiring immediate intervention.

---

## 🔌 REST API Endpoints

All endpoints are registered under the `seo-inspector/v1` namespace and require `manage_options` permissions:

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/wp-json/seo-inspector/v1/audit` | Retrieves cached audit results (or initial scan). |
| `POST` | `/wp-json/seo-inspector/v1/audit/run` | Triggers a fresh live audit scan and updates cache. |
| `POST` | `/wp-json/seo-inspector/v1/audit/clear` | Clears stored audit transient/options cache. |

---

## 👨‍💻 Developer & Attribution

- **Plugin Name**: SEO Inspector (SEO Inspector ZHS)
- **Author**: MD. Ziaul Hasan
- **Author URI**: [https://www.instagram.com/zhsaikot/](https://www.instagram.com/zhsaikot/)
- **Text Domain**: `seo-inspector-zhs`

---

## 📜 License

This plugin is licensed under the **GNU General Public License v2.0 or later** (GPL-2.0+). See `LICENSE` for details.