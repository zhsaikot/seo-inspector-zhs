const fs = require('fs');
const path = require('path');

const projectRoot = path.resolve(__dirname, '..');
const svnRoot = 'C:\\Users\\MD. Ziaul Hasan\\zhs-site-audit-seo-diagnostics-svn';

console.log('Project root:', projectRoot);
console.log('SVN root:', svnRoot);

function copyRecursive(src, dest) {
    if (!fs.existsSync(src)) return;
    const stats = fs.statSync(src);
    if (stats.isDirectory()) {
        if (!fs.existsSync(dest)) {
            fs.mkdirSync(dest, { recursive: true });
        }
        for (const item of fs.readdirSync(src)) {
            copyRecursive(path.join(src, item), path.join(dest, item));
        }
    } else {
        fs.copyFileSync(src, dest);
    }
}

// 1. Trunk
console.log('Copying to trunk...');
const trunkFiles = ['zhs-site-audit-seo-diagnostics.php', 'readme.txt', 'LICENSE'];
const trunkDirs = ['includes', 'assets', 'templates', 'languages'];

for (const file of trunkFiles) {
    const src = path.join(projectRoot, file);
    if (fs.existsSync(src)) {
        fs.copyFileSync(src, path.join(svnRoot, 'trunk', file));
        console.log(`  + trunk/${file}`);
    }
}

for (const dir of trunkDirs) {
    const src = path.join(projectRoot, dir);
    if (fs.existsSync(src)) {
        copyRecursive(src, path.join(svnRoot, 'trunk', dir));
        console.log(`  + trunk/${dir}/`);
    }
}

// 2. Assets (WordPress.org banners & icons)
console.log('Copying to assets...');
const wpAssets = path.join(projectRoot, '.wordpress-org-assets');
if (fs.existsSync(wpAssets)) {
    for (const item of fs.readdirSync(wpAssets)) {
        fs.copyFileSync(path.join(wpAssets, item), path.join(svnRoot, 'assets', item));
        console.log(`  + assets/${item}`);
    }
}

// 3. Tags 1.0.0
console.log('Creating tags/1.0.0...');
const tagDir = path.join(svnRoot, 'tags', '1.0.0');
if (!fs.existsSync(tagDir)) {
    fs.mkdirSync(tagDir, { recursive: true });
}
copyRecursive(path.join(svnRoot, 'trunk'), tagDir);
console.log('  + Copied trunk to tags/1.0.0/');

console.log('\nAll files prepared successfully for SVN!');

