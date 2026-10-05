<?php
$files = [
    'zhs-site-audit-seo-diagnostics.php',
    'includes/class-audit-engine.php',
    'includes/class-admin-page.php',
    'includes/class-rest-api.php',
    'templates/dashboard-view.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $tokens = token_get_all($content);
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (is_array($tok) && in_array($tok[1], ['__', 'esc_html__', 'esc_attr__', '_e', 'esc_html_e'])) {
            $line = $tok[2];
            // Check if argument has placeholders
            // scan forward for string
            $arg = '';
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
                    $arg = $tokens[$j][1];
                    break;
                }
                if ($tokens[$j] === ')') break;
            }

            $clean = str_replace(['%%', '\$'], ['', '$'], $arg);
            if (preg_match('/(%[0-9]+\$[a-zA-Z]|%[a-zA-Z])/', $clean)) {
                // Find preceding comment token
                $hasComment = false;
                $trappedInString = false;
                for ($p = $i - 1; $p >= max(0, $i - 30); $p--) {
                    $ptok = $tokens[$p];
                    if (is_array($ptok)) {
                        if ($ptok[0] === T_COMMENT && stripos($ptok[1], 'translators:') !== false) {
                            $hasComment = true;
                            break;
                        }
                        if ($ptok[0] === T_CONSTANT_ENCAPSED_STRING && stripos($ptok[1], 'translators:') !== false) {
                            $trappedInString = true;
                            break;
                        }
                        if ($ptok[0] === T_WHITESPACE) continue;
                        if (in_array($ptok[1], ['sprintf', '(', '=', 'echo', 'return', '=>', '.', '[', ']'])) continue;
                    }
                }
                if ($trappedInString) {
                    echo "TRAPPED COMMENT: $file line $line: $arg\n";
                } elseif (!$hasComment) {
                    echo "MISSING COMMENT: $file line $line: $arg\n";
                }
            }
        }
    }
}
