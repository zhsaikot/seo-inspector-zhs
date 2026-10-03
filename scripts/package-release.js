/**
 * SEO Inspector ZHS - Production Release Packaging Script
 *
 * Uses 'archiver' to generate a 100% WordPress-compliant ZIP archive
 * strictly enforcing forward slashes ('/') in all entry paths to prevent
 * the WordPress "Could not copy file. seo-inspector-zhs\assets\" error.
 *
 * @author MD. Ziaul Hasan <https://mdziaulhasan.com/>
 */

const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');
const DIST_DIR = path.join(ROOT_DIR, 'dist');
const ZIP_FILE = path.join(DIST_DIR, 'seo-inspector-zhs.zip');
const STAGE_DIR = path.join(DIST_DIR, 'seo-inspector-zhs');

// Targets to copy for user convenience
const USER_DESKTOP = path.join(process.env.USERPROFILE || 'C:\\Users\\MD. Ziaul Hasan', 'Desktop', 'seo-inspector-zhs.zip');
const USER_DOWNLOADS = path.join(process.env.USERPROFILE || 'C:\\Users\\MD. Ziaul Hasan', 'Downloads', 'seo-inspector-zhs.zip');
const SCRATCH_ZIP = path.join(process.env.USERPROFILE || 'C:\\Users\\MD. Ziaul Hasan', '.gemini', 'antigravity', 'scratch', 'seo-inspector-zhs.zip');

async function main() {
	console.log('==========================================================');
	console.log(' Packaging SEO Inspector for WordPress (Cross-Platform ZIP)');
	console.log('==========================================================\n');

	// 1. Clean and prepare dist directory
	if (fs.existsSync(DIST_DIR)) {
		fs.rmSync(DIST_DIR, { recursive: true, force: true });
	}
	fs.mkdirSync(STAGE_DIR, { recursive: true });

	// 2. Stage production files
	console.log('📦 Staging production files...');
	const filesToCopy = ['seo-inspector-zhs.php', 'readme.txt', 'LICENSE'];
	const dirsToCopy = ['includes', 'assets', 'templates', 'languages'];

	for (const file of filesToCopy) {
		const src = path.join(ROOT_DIR, file);
		if (fs.existsSync(src)) {
			fs.copyFileSync(src, path.join(STAGE_DIR, file));
			console.log(`   + ${file}`);
		}
	}

	for (const dir of dirsToCopy) {
		const src = path.join(ROOT_DIR, dir);
		const dest = path.join(STAGE_DIR, dir);
		if (fs.existsSync(src)) {
			copyDirRecursive(src, dest);
			console.log(`   + ${dir}/`);
		}
	}

	// 3. Build ZIP Archive with explicit forward-slash paths
	console.log('\n🗜️  Building WordPress-compliant ZIP archive with forward slashes...');
	await createStandardZip(STAGE_DIR, ZIP_FILE, 'seo-inspector-zhs');

	const stats = fs.statSync(ZIP_FILE);
	const sizeKb = (stats.size / 1024).toFixed(2);
	console.log(`   ✅ Built release archive: dist/seo-inspector-zhs.zip (${sizeKb} KB)`);

	// 4. Distribute to Desktop, Downloads, and Scratch
	console.log('\n🚀 Copying verified ZIP to convenient locations...');
	try {
		fs.copyFileSync(ZIP_FILE, USER_DESKTOP);
		console.log(`   ✅ Desktop:   ${USER_DESKTOP}`);
	} catch (e) {
		console.warn(`   ⚠️ Could not copy to Desktop: ${e.message}`);
	}

	try {
		fs.copyFileSync(ZIP_FILE, USER_DOWNLOADS);
		console.log(`   ✅ Downloads: ${USER_DOWNLOADS}`);
	} catch (e) {
		console.warn(`   ⚠️ Could not copy to Downloads: ${e.message}`);
	}

	try {
		const scratchDir = path.dirname(SCRATCH_ZIP);
		if (fs.existsSync(scratchDir)) {
			fs.copyFileSync(ZIP_FILE, SCRATCH_ZIP);
			console.log(`   ✅ Scratch:   ${SCRATCH_ZIP}`);
		}
	} catch (e) {
		// Ignore scratch copy error if not accessible
	}

	console.log('\n==========================================================');
	console.log('🎉 Packaging Complete! Ready for 1-Click WordPress Upload!');
	console.log('==========================================================');
}

/**
 * Copy directory recursively excluding dev artifacts.
 */
function copyDirRecursive(src, dest) {
	fs.mkdirSync(dest, { recursive: true });
	const entries = fs.readdirSync(src, { withFileTypes: true });

	for (const entry of entries) {
		const srcPath = path.join(src, entry.name);
		const destPath = path.join(dest, entry.name);

		if (entry.name === '.DS_Store' || entry.name === 'Thumbs.db' || entry.name.endsWith('.map')) {
			continue;
		}

		if (entry.isDirectory()) {
			copyDirRecursive(srcPath, destPath);
		} else {
			fs.copyFileSync(srcPath, destPath);
		}
	}
}

/**
 * Create ZIP using archiver with explicit forward slashes.
 */
async function createStandardZip(sourceDir, outPath, rootFolderName) {
	const { ZipArchive } = await import('archiver');
	return new Promise((resolve, reject) => {
		const output = fs.createWriteStream(outPath);
		const archive = new ZipArchive({
			zlib: { level: 9 }, // Maximum compression
		});

		output.on('close', resolve);
		archive.on('error', reject);

		archive.pipe(output);

		// Built-in archiver directory method: automatically normalizes all slashes to POSIX '/'
		archive.directory(sourceDir, rootFolderName);

		archive.finalize();
	});
}

main().catch((err) => {
	console.error('❌ Error during packaging:', err);
	process.exit(1);
});
