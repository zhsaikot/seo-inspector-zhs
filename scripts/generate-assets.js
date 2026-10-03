/**
 * SEO Inspector ZHS - WordPress.org SVN Assets Generator
 *
 * Generates all official WordPress.org plugin directory assets:
 * - screenshot-1.png (840x600)
 * - screenshot-2.png (840x750)
 * - icon-128x128.png & icon-256x256.png
 * - banner-772x250.png & banner-1544x500.png
 *
 * @author MD. Ziaul Hasan <https://mdziaulhasan.com/>
 */

const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const ROOT_DIR = path.resolve(__dirname, '..');
const ASSETS_DIR = path.join(ROOT_DIR, '.wordpress-org-assets');
const SOURCE_SCREENSHOT = path.join(__dirname, 'source-screenshot.png');

// Ensure destination directory exists
if (!fs.existsSync(ASSETS_DIR)) {
	fs.mkdirSync(ASSETS_DIR, { recursive: true });
}

async function run() {
	console.log('🚀 Starting WordPress.org Asset Generation for SEO Inspector ZHS...\n');

	// 1. Process Screenshots from Source Image
	if (fs.existsSync(SOURCE_SCREENSHOT)) {
		console.log('📸 Found source screenshot. Extracting crisp dashboard views...');
		const image = sharp(SOURCE_SCREENSHOT);
		const metadata = await image.metadata();
		console.log(`   Source image dimensions: ${metadata.width}x${metadata.height}`);

		// screenshot-1.png: Crop top overview (header, score dial, KPI stat cards, category breakdown)
		const crop1Left = 70;
		const crop1Top = 15;
		const crop1Width = Math.min(metadata.width - crop1Left - 8, 396);
		const crop1Height = 265;

		await sharp(SOURCE_SCREENSHOT)
			.extract({
				left: crop1Left,
				top: crop1Top,
				width: crop1Width,
				height: crop1Height,
			})
			.resize(840, 600, {
				fit: 'contain',
				background: { r: 248, g: 250, b: 252, alpha: 1 },
			})
			.png({ quality: 95, compressionLevel: 8 })
			.toFile(path.join(ASSETS_DIR, 'screenshot-1.png'));

		console.log('   ✅ Generated: .wordpress-org-assets/screenshot-1.png (840x600)');

		// screenshot-2.png: Crop 17-point audit checks section
		const crop2Left = 70;
		const crop2Top = 275;
		const crop2Width = Math.min(metadata.width - crop2Left - 8, 396);
		const crop2Height = Math.min(metadata.height - crop2Top - 25, 675);

		await sharp(SOURCE_SCREENSHOT)
			.extract({
				left: crop2Left,
				top: crop2Top,
				width: crop2Width,
				height: crop2Height,
			})
			.resize(840, 750, {
				fit: 'contain',
				background: { r: 248, g: 250, b: 252, alpha: 1 },
			})
			.png({ quality: 95, compressionLevel: 8 })
			.toFile(path.join(ASSETS_DIR, 'screenshot-2.png'));

		console.log('   ✅ Generated: .wordpress-org-assets/screenshot-2.png (840x750)');
	} else {
		console.warn('⚠️ Source screenshot not found at:', SOURCE_SCREENSHOT);
	}

	// 2. Generate Modern App Icons (icon-128x128.png & icon-256x256.png)
	console.log('\n🎨 Generating modern SVG vector icons (Navy #0f172a + Cyan #0284c7)...');
	const iconSvg = `
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="256" height="256">
		<defs>
			<linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
				<stop offset="0%" stop-color="#0f172a" />
				<stop offset="50%" stop-color="#1e293b" />
				<stop offset="100%" stop-color="#0284c7" />
			</linearGradient>
			<linearGradient id="cyanGrad" x1="0%" y1="0%" x2="100%" y2="100%">
				<stop offset="0%" stop-color="#38bdf8" />
				<stop offset="100%" stop-color="#0284c7" />
			</linearGradient>
			<linearGradient id="accentGrad" x1="0%" y1="0%" x2="100%" y2="100%">
				<stop offset="0%" stop-color="#10b981" />
				<stop offset="100%" stop-color="#06b6d4" />
			</linearGradient>
			<filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
				<feGaussianBlur stdDeviation="8" result="blur" />
				<feComposite in="SourceGraphic" in2="blur" operator="over" />
			</filter>
		</defs>

		<!-- Card Background with soft rounded corners -->
		<rect x="8" y="8" width="240" height="240" rx="52" fill="url(#bgGrad)" stroke="#38bdf8" stroke-width="4" stroke-opacity="0.3" />

		<!-- Outer decorative radar rings -->
		<circle cx="128" cy="116" r="68" fill="none" stroke="#38bdf8" stroke-width="2.5" stroke-opacity="0.25" stroke-dasharray="6 4" />
		<circle cx="128" cy="116" r="48" fill="none" stroke="#38bdf8" stroke-width="2" stroke-opacity="0.4" />

		<!-- Magnifying Glass / Audit Lens -->
		<circle cx="118" cy="106" r="38" fill="none" stroke="url(#cyanGrad)" stroke-width="12" filter="url(#glow)" />
		<line x1="146" y1="134" x2="182" y2="170" stroke="url(#cyanGrad)" stroke-width="14" stroke-linecap="round" />

		<!-- Checkmark inside lens -->
		<polyline points="104,106 114,116 134,96" fill="none" stroke="#10b981" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" />

		<!-- Brand Monogram Tag: SEO AUDIT -->
		<rect x="42" y="196" width="172" height="34" rx="17" fill="#0f172a" fill-opacity="0.85" stroke="#0284c7" stroke-width="1.8" />
		<text x="128" y="219" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="14.5" font-weight="900" fill="#ffffff" text-anchor="middle" letter-spacing="2.2">
			SEO <tspan fill="#38bdf8">AUDIT</tspan>
		</text>
	</svg>
	`;

	const iconBuffer = Buffer.from(iconSvg);

	// Generate 256x256
	await sharp(iconBuffer)
		.resize(256, 256)
		.png({ quality: 100 })
		.toFile(path.join(ASSETS_DIR, 'icon-256x256.png'));
	console.log('   ✅ Generated: .wordpress-org-assets/icon-256x256.png (256x256)');

	// Generate 128x128
	await sharp(iconBuffer)
		.resize(128, 128)
		.png({ quality: 100 })
		.toFile(path.join(ASSETS_DIR, 'icon-128x128.png'));
	console.log('   ✅ Generated: .wordpress-org-assets/icon-128x128.png (128x128)');

	// 3. Generate High-Res Banners (banner-772x250.png & banner-1544x500.png)
	console.log('\n🌟 Generating high-res branded banners (1544x500 & 772x250)...');
	const bannerSvg = `
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1544 500" width="1544" height="500">
		<defs>
			<linearGradient id="bannerBg" x1="0%" y1="0%" x2="100%" y2="100%">
				<stop offset="0%" stop-color="#090d16" />
				<stop offset="45%" stop-color="#0f172a" />
				<stop offset="85%" stop-color="#0c2340" />
				<stop offset="100%" stop-color="#0369a1" />
			</linearGradient>
			<linearGradient id="cyanText" x1="0%" y1="0%" x2="100%" y2="0%">
				<stop offset="0%" stop-color="#38bdf8" />
				<stop offset="100%" stop-color="#818cf8" />
			</linearGradient>
			<linearGradient id="emeraldText" x1="0%" y1="0%" x2="100%" y2="0%">
				<stop offset="0%" stop-color="#34d399" />
				<stop offset="100%" stop-color="#38bdf8" />
			</linearGradient>
			<filter id="bannerGlow" x="-20%" y="-20%" width="140%" height="140%">
				<feGaussianBlur stdDeviation="16" result="blur" />
				<feComposite in="SourceGraphic" in2="blur" operator="over" />
			</filter>
		</defs>

		<!-- Background -->
		<rect width="1544" height="500" fill="url(#bannerBg)" />

		<!-- Subtle Background Tech Grid Lines -->
		<g stroke="#38bdf8" stroke-opacity="0.05" stroke-width="1">
			<line x1="0" y1="100" x2="1544" y2="100" />
			<line x1="0" y1="200" x2="1544" y2="200" />
			<line x1="0" y1="300" x2="1544" y2="300" />
			<line x1="0" y1="400" x2="1544" y2="400" />
			<line x1="200" y1="0" x2="200" y2="500" />
			<line x1="400" y1="0" x2="400" y2="500" />
			<line x1="600" y1="0" x2="600" y2="500" />
			<line x1="800" y1="0" x2="800" y2="500" />
			<line x1="1000" y1="0" x2="1000" y2="500" />
			<line x1="1200" y1="0" x2="1200" y2="500" />
			<line x1="1400" y1="0" x2="1400" y2="500" />
		</g>

		<!-- Glowing Accent Circles in background -->
		<circle cx="1350" cy="140" r="180" fill="#0284c7" fill-opacity="0.18" filter="url(#bannerGlow)" />
		<circle cx="180" cy="380" r="140" fill="#10b981" fill-opacity="0.12" filter="url(#bannerGlow)" />

		<!-- Left Icon Emblem -->
		<g transform="translate(100, 110)">
			<rect x="0" y="0" width="240" height="240" rx="52" fill="#0f172a" stroke="#38bdf8" stroke-width="3" stroke-opacity="0.4" />
			<!-- Outer Radar -->
			<circle cx="120" cy="120" r="76" fill="none" stroke="#38bdf8" stroke-width="2.5" stroke-opacity="0.3" stroke-dasharray="8 6" />
			<circle cx="120" cy="120" r="54" fill="none" stroke="#38bdf8" stroke-width="2" stroke-opacity="0.45" />

			<!-- Search Lens -->
			<circle cx="110" cy="110" r="42" fill="none" stroke="#38bdf8" stroke-width="12" />
			<line x1="140" y1="140" x2="178" y2="178" stroke="#38bdf8" stroke-width="14" stroke-linecap="round" />
			<polyline points="94,110 106,122 128,100" fill="none" stroke="#34d399" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" />

			<!-- 100% Score Tag -->
			<rect x="42" y="196" width="156" height="34" rx="17" fill="#0f172a" stroke="#10b981" stroke-width="2" />
			<text x="120" y="219" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="14" font-weight="900" fill="#34d399" text-anchor="middle" letter-spacing="1.5">
				17 CHECKS &#8226; PASS
			</text>
		</g>

		<!-- Right Side Hero Typography -->
		<g transform="translate(400, 120)">
			<!-- Pre-Title Pill -->
			<rect x="0" y="0" width="220" height="34" rx="17" fill="#0369a1" fill-opacity="0.35" stroke="#38bdf8" stroke-width="1.5" />
			<text x="110" y="22" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="13" font-weight="800" fill="#38bdf8" text-anchor="middle" letter-spacing="1.5">
				OFFICIAL WORDPRESS SUITE
			</text>

			<!-- Main Title -->
			<text x="0" y="98" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="64" font-weight="900" fill="#ffffff" letter-spacing="-1.5">
				SEO <tspan fill="url(#cyanText)">Inspector</tspan>
			</text>

			<!-- Subtitle -->
			<text x="0" y="145" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="22" font-weight="500" fill="#94a3b8" letter-spacing="-0.2">
				17-Point Modern SEO, Usability &amp; GEO AI Diagnostics Suite
			</text>

			<!-- 4 Core Pillars Pills -->
			<g transform="translate(0, 185)">
				<!-- Usability -->
				<rect x="0" y="0" width="135" height="32" rx="16" fill="#0f172a" stroke="#0284c7" stroke-width="1.5" />
				<text x="67" y="21" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="13" font-weight="700" fill="#38bdf8" text-anchor="middle">Usability</text>

				<!-- Accessibility -->
				<rect x="147" y="0" width="155" height="32" rx="16" fill="#0f172a" stroke="#7e22ce" stroke-width="1.5" />
				<text x="224" y="21" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="13" font-weight="700" fill="#c084fc" text-anchor="middle">Accessibility</text>

				<!-- SEO Essentials -->
				<rect x="314" y="0" width="165" height="32" rx="16" fill="#0f172a" stroke="#4338ca" stroke-width="1.5" />
				<text x="396" y="21" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="13" font-weight="700" fill="#818cf8" text-anchor="middle">SEO Essentials</text>

				<!-- GEO & AI Readiness -->
				<rect x="491" y="0" width="205" height="32" rx="16" fill="#0f172a" stroke="#059669" stroke-width="1.5" />
				<text x="593" y="21" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="13" font-weight="700" fill="#34d399" text-anchor="middle">GEO &amp; AI-Readiness</text>
			</g>

			<!-- Author Credit Footer inside Banner -->
			<g transform="translate(0, 245)">
				<text x="0" y="22" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="15" font-weight="600" fill="#64748b">
					Architected by <tspan fill="#ffffff" font-weight="700">MD. Ziaul Hasan</tspan> (<tspan fill="#38bdf8">zhsaikot</tspan>) &#8226; <tspan fill="#34d399">WordPress.org Verified</tspan>
				</text>
			</g>
		</g>
	</svg>
	`;

	const bannerBuffer = Buffer.from(bannerSvg);

	// Generate 1544x500 (Retina)
	await sharp(bannerBuffer)
		.resize(1544, 500)
		.png({ quality: 95, compressionLevel: 8 })
		.toFile(path.join(ASSETS_DIR, 'banner-1544x500.png'));
	console.log('   ✅ Generated: .wordpress-org-assets/banner-1544x500.png (1544x500)');

	// Generate 772x250 (Standard)
	await sharp(bannerBuffer)
		.resize(772, 250)
		.png({ quality: 95, compressionLevel: 8 })
		.toFile(path.join(ASSETS_DIR, 'banner-772x250.png'));
	console.log('   ✅ Generated: .wordpress-org-assets/banner-772x250.png (772x250)');

	console.log('\n✨ All WordPress.org assets generated successfully into: .wordpress-org-assets/\n');
}

run().catch((err) => {
	console.error('❌ Error generating assets:', err);
	process.exit(1);
});
