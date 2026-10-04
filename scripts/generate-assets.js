/**
 * ZHS Site Audit and SEO Diagnostics - WordPress.org SVN Assets Generator
 *
 * Generates all official WordPress.org plugin directory assets:
 * - banner-772x250.png (Standard banner)
 * - banner-1544x500.png (Retina banner)
 * - icon-128x128.png (Standard icon)
 * - icon-256x256.png (Retina icon)
 * - screenshot-1.png (Dashboard Overview)
 * - screenshot-2.png (17-Point Audit Diagnostics)
 *
 * Distributes to:
 * - .wordpress-org-assets/ (Local repository)
 * - Desktop/wordpress-org-assets/ & Desktop/wordpress-org-assets.zip
 * - Downloads/wordpress-org-assets/ & Downloads/wordpress-org-assets.zip
 *
 * @author MD. Ziaul Hasan <https://mdziaulhasan.com/>
 */

const fs = require('fs');
const path = require('path');
const sharp = require('sharp');
const archiver = require('archiver');

const ROOT_DIR = path.resolve(__dirname, '..');
const ASSETS_DIR = path.join(ROOT_DIR, '.wordpress-org-assets');
const SOURCE_DIR = path.join(__dirname, 'source-assets');

const USER_DESKTOP_DIR = path.join(process.env.USERPROFILE || 'C:\\Users\\MD. Ziaul Hasan', 'Desktop', 'wordpress-org-assets');
const USER_DOWNLOADS_DIR = path.join(process.env.USERPROFILE || 'C:\\Users\\MD. Ziaul Hasan', 'Downloads', 'wordpress-org-assets');
const USER_DESKTOP_ZIP = path.join(process.env.USERPROFILE || 'C:\\Users\\MD. Ziaul Hasan', 'Desktop', 'wordpress-org-assets.zip');
const USER_DOWNLOADS_ZIP = path.join(process.env.USERPROFILE || 'C:\\Users\\MD. Ziaul Hasan', 'Downloads', 'wordpress-org-assets.zip');

async function createZip(sourceDir, zipFilePath) {
	const { ZipArchive } = await import('archiver');
	return new Promise((resolve, reject) => {
		const output = fs.createWriteStream(zipFilePath);
		const archive = new ZipArchive({ zlib: { level: 9 } });

		output.on('close', resolve);
		archive.on('error', reject);

		archive.pipe(output);
		archive.directory(sourceDir, false);
		archive.finalize();
	});
}

async function run() {
	console.log('==========================================================');
	console.log(' Generating WordPress.org Assets for ZHS Site Audit');
	console.log('==========================================================\n');

	// 1. Clean previous assets
	if (fs.existsSync(ASSETS_DIR)) {
		console.log('🗑️  Deleting previous assets in .wordpress-org-assets/ ...');
		const oldFiles = fs.readdirSync(ASSETS_DIR);
		for (const file of oldFiles) {
			fs.unlinkSync(path.join(ASSETS_DIR, file));
			console.log(`   - Deleted: ${file}`);
		}
	} else {
		fs.mkdirSync(ASSETS_DIR, { recursive: true });
	}

	const bannerSrc = path.join(SOURCE_DIR, 'banner.png');
	const iconSrc = path.join(SOURCE_DIR, 'icon.png');
	const ss1Src = path.join(SOURCE_DIR, 'screenshot-1.png');
	const ss2Src = path.join(SOURCE_DIR, 'screenshot-2.png');

	if (!fs.existsSync(bannerSrc) || !fs.existsSync(iconSrc) || !fs.existsSync(ss1Src) || !fs.existsSync(ss2Src)) {
		console.error('❌ Missing source assets in scripts/source-assets/');
		process.exit(1);
	}

	// 2. Generate Banners
	console.log('\n🎨 Generating Banners from source master banner...');
	await sharp(bannerSrc)
		.resize(772, 250, { fit: 'cover', position: 'center', kernel: sharp.kernel.lanczos3 })
		.png({ quality: 100, compressionLevel: 9 })
		.toFile(path.join(ASSETS_DIR, 'banner-772x250.png'));
	console.log('   ✅ Generated: banner-772x250.png (772x250)');

	await sharp(bannerSrc)
		.resize(1544, 500, { fit: 'cover', position: 'center', kernel: sharp.kernel.lanczos3 })
		.png({ quality: 100, compressionLevel: 9 })
		.toFile(path.join(ASSETS_DIR, 'banner-1544x500.png'));
	console.log('   ✅ Generated: banner-1544x500.png (1544x500 Retina)');

	// 3. Generate Icons
	console.log('\n🔮 Generating Icons from source master icon...');
	await sharp(iconSrc)
		.resize(256, 256, { fit: 'contain' })
		.png({ quality: 100, compressionLevel: 9 })
		.toFile(path.join(ASSETS_DIR, 'icon-256x256.png'));
	console.log('   ✅ Generated: icon-256x256.png (256x256 Retina)');

	await sharp(iconSrc)
		.resize(128, 128, { fit: 'contain', kernel: sharp.kernel.lanczos3 })
		.png({ quality: 100, compressionLevel: 9 })
		.toFile(path.join(ASSETS_DIR, 'icon-128x128.png'));
	console.log('   ✅ Generated: icon-128x128.png (128x128)');

	// 4. Generate Screenshots
	console.log('\n📸 Generating Screenshots...');
	await sharp(ss1Src)
		.png({ quality: 100, compressionLevel: 9 })
		.toFile(path.join(ASSETS_DIR, 'screenshot-1.png'));
	console.log('   ✅ Generated: screenshot-1.png (Dashboard Overview)');

	await sharp(ss2Src)
		.png({ quality: 100, compressionLevel: 9 })
		.toFile(path.join(ASSETS_DIR, 'screenshot-2.png'));
	console.log('   ✅ Generated: screenshot-2.png (17-Point Audit Diagnostics)');

	// 5. Copy to Desktop & Downloads
	console.log('\n🚀 Exporting assets to Desktop & Downloads...');
	const copyDir = (src, dest) => {
		if (fs.existsSync(dest)) fs.rmSync(dest, { recursive: true, force: true });
		fs.mkdirSync(dest, { recursive: true });
		for (const file of fs.readdirSync(src)) {
			fs.copyFileSync(path.join(src, file), path.join(dest, file));
		}
	};

	try {
		copyDir(ASSETS_DIR, USER_DESKTOP_DIR);
		console.log(`   ✅ Desktop Folder:   ${USER_DESKTOP_DIR}`);
	} catch (e) {
		console.warn(`   ⚠️ Desktop Folder error: ${e.message}`);
	}

	try {
		copyDir(ASSETS_DIR, USER_DOWNLOADS_DIR);
		console.log(`   ✅ Downloads Folder: ${USER_DOWNLOADS_DIR}`);
	} catch (e) {
		console.warn(`   ⚠️ Downloads Folder error: ${e.message}`);
	}

	// 6. Create convenient ZIP archive on Desktop & Downloads
	try {
		await createZip(ASSETS_DIR, USER_DESKTOP_ZIP);
		console.log(`   ✅ Desktop ZIP:      ${USER_DESKTOP_ZIP}`);
	} catch (e) {
		console.warn(`   ⚠️ Desktop ZIP error: ${e.message}`);
	}

	try {
		await createZip(ASSETS_DIR, USER_DOWNLOADS_ZIP);
		console.log(`   ✅ Downloads ZIP:    ${USER_DOWNLOADS_ZIP}`);
	} catch (e) {
		console.warn(`   ⚠️ Downloads ZIP error: ${e.message}`);
	}

	console.log('\n==========================================================');
	console.log('🎉 Asset Generation & Distribution Completed Successfully!');
	console.log('==========================================================\n');
}

run().catch((err) => {
	console.error('Fatal error during asset generation:', err);
	process.exit(1);
});
