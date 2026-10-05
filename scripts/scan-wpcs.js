const fs = require('fs');
const path = require('path');

const files = [
	'zhs-site-audit-seo-diagnostics.php',
	'includes/class-audit-engine.php',
	'includes/class-admin-page.php',
	'includes/class-rest-api.php',
	'templates/dashboard-view.php'
];

function scanTranslatorsComments() {
	const results = [];

	files.forEach(f => {
		if (!fs.existsSync(f)) return;
		const content = fs.readFileSync(f, 'utf8');
		const lines = content.split('\n');

		const fnRegex = /\b(__|_e|esc_html__|esc_attr__|esc_html_e|esc_attr_e|_x|_ex|_n)\s*\(/g;
		let match;

		while ((match = fnRegex.exec(content)) !== null) {
			const fnName = match[1];
			const lineIndex = content.substring(0, match.index).split('\n').length - 1;

			let openParens = 1;
			let idx = match.index + match[0].length;
			let arg1 = '';
			let inQuote = false;
			let quoteChar = '';
			let isEsc = false;

			while (idx < content.length && openParens > 0) {
				const ch = content[idx];
				if (isEsc) {
					arg1 += ch;
					isEsc = false;
				} else if (ch === '\\') {
					arg1 += ch;
					isEsc = true;
				} else if ((ch === "'" || ch === '"') && !inQuote) {
					inQuote = true;
					quoteChar = ch;
					arg1 += ch;
				} else if (ch === quoteChar && inQuote) {
					inQuote = false;
					arg1 += ch;
				} else if (!inQuote) {
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

			// Clean double percents (escaped %%) and unescape \$
			const cleanStr = arg1.replace(/%%/g, '').replace(/\\\$/g, '$');
			const hasPlaceholder = /(%[0-9]+\$[a-zA-Z]|%[a-zA-Z])/.test(cleanStr);

			if (hasPlaceholder) {
				let hasComment = false;
				// Check preceding lines up to 4 lines for translators:
				for (let prev = Math.max(0, lineIndex - 4); prev <= lineIndex; prev++) {
					if (lines[prev].toLowerCase().includes('translators:')) {
						hasComment = true;
						break;
					}
				}
				results.push({
					file: f,
					line: lineIndex + 1,
					fn: fnName,
					hasComment,
					arg: arg1.trim().replace(/\s+/g, ' ')
				});
			}
		}
	});

	return results;
}

const allWithPlaceholders = scanTranslatorsComments();
const missing = allWithPlaceholders.filter(r => !r.hasComment);
console.log(`Total placeholder translations (including escaped \\$): ${allWithPlaceholders.length}`);
console.log(`Missing translators comments: ${missing.length}`);

fs.writeFileSync(path.join(__dirname, 'wpcs-report.json'), JSON.stringify(missing, null, 2));
