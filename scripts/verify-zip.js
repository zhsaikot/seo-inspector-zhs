const fs = require('fs');
const path = require('path');

// Read ZIP central directory records
const zipBuffer = fs.readFileSync(path.join(__dirname, '../dist/seo-inspector-zhs.zip'));

console.log('--- Inspecting ZIP File Entries ---');
let offset = 0;
const entries = [];

while (offset < zipBuffer.length - 4) {
	// Look for local file header signature 0x04034b50
	if (zipBuffer.readUInt32LE(offset) === 0x04034b50) {
		const nameLen = zipBuffer.readUInt16LE(offset + 26);
		const extraLen = zipBuffer.readUInt16LE(offset + 28);
		const name = zipBuffer.toString('utf8', offset + 30, offset + 30 + nameLen);
		entries.push(name);
		offset += 30 + nameLen + extraLen;
	} else {
		offset++;
	}
}

console.log('Total entries found:', entries.length);
entries.forEach((e) => console.log('  ', e));

const hasBackslash = entries.some((e) => e.includes('\\'));
console.log('\nHas any backslash (\\)?', hasBackslash ? '❌ YES (INCOMPATIBLE WITH WP)' : '✅ NO (100% CLEAN POSIX FORWARD SLASHES)');
