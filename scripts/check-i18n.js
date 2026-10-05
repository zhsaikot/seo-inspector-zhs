const fs = require('fs');
const path = require('path');

function scanFile(filePath) {
	const content = fs.readFileSync(filePath, 'utf8');
	const fnNames = ['__', '_e', 'esc_html__', 'esc_attr__', 'esc_html_e', 'esc_attr_e', '_x', '_ex', '_n'];
	const pattern = new RegExp('\\b(' + fnNames.join('|') + ')\\s*\\(', 'g');
	
	let match;
	let issues = [];

	while ((match = pattern.exec(content)) !== null) {
		const startIdx = match.index;
		const lineNum = content.substring(0, startIdx).split('\n').length;
		
		let openParens = 1;
		let inSingleQuote = false;
		let inDoubleQuote = false;
		let isEscaped = false;
		let arg1 = '';
		let idx = startIdx + match[0].length;
		
		while (idx < content.length && openParens > 0) {
			const ch = content[idx];
			if (isEscaped) {
				arg1 += ch;
				isEscaped = false;
			} else if (ch === '\\') {
				arg1 += ch;
				isEscaped = true;
			} else if (ch === "'" && !inDoubleQuote) {
				inSingleQuote = !inSingleQuote;
				arg1 += ch;
			} else if (ch === '"' && !inSingleQuote) {
				inDoubleQuote = !inDoubleQuote;
				arg1 += ch;
			} else if (!inSingleQuote && !inDoubleQuote) {
				if (ch === '(') openParens++;
				else if (ch === ')') {
					openParens--;
					if (openParens === 0) break;
				} else if (ch === ',' && openParens === 1) {
					break;
				}
				arg1 += ch;
			} else {
				arg1 += ch;
			}
			idx++;
		}
		
		let outsideQuotes = '';
		inSingleQuote = false;
		inDoubleQuote = false;
		isEscaped = false;
		for (let i = 0; i < arg1.length; i++) {
			const ch = arg1[i];
			if (isEscaped) {
				isEscaped = false;
			} else if (ch === '\\') {
				isEscaped = true;
			} else if (ch === "'" && !inDoubleQuote) {
				inSingleQuote = !inSingleQuote;
			} else if (ch === '"' && !inSingleQuote) {
				inDoubleQuote = !inDoubleQuote;
			} else if (!inSingleQuote && !inDoubleQuote) {
				outsideQuotes += ch;
			}
		}

		if (outsideQuotes.includes('.')) {
			issues.push({
				file: filePath,
				line: lineNum,
				fn: match[1],
				type: 'concatenation',
				arg: arg1.trim()
			});
		} else if (outsideQuotes.includes('$')) {
			issues.push({
				file: filePath,
				line: lineNum,
				fn: match[1],
				type: 'variable',
				arg: arg1.trim()
			});
		}
	}
	return issues;
}

const files = [
	'zhs-site-audit-seo-diagnostics.php',
	'includes/class-audit-engine.php',
	'includes/class-admin-page.php',
	'includes/class-rest-api.php',
	'templates/dashboard-view.php'
];

let allIssues = [];
for (const f of files) {
	if (fs.existsSync(f)) {
		const issues = scanFile(f);
		allIssues.push(...issues);
	}
}

fs.writeFileSync(path.join(__dirname, 'i18n-issues.json'), JSON.stringify(allIssues, null, 2));
console.log(`Scan completed: Found ${allIssues.length} issues. Saved to scripts/i18n-issues.json`);
