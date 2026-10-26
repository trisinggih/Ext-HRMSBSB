<?php
$expr = "if(7 > 6, 1000 / 30 * 24, 1000)";
$expr = preg_replace_callback("/\bif\s*\(([^,]+),([^,]+),([^()]+)\)/i", function($m) {
    return "((" . trim($m[1]) . ") ? (" . trim($m[2]) . ") : (" . trim($m[3]) . "))";
}, $expr);
echo "expr: " . $expr . "\n";
if (preg_match("#^[0-9+\-*/().<>= \?\:]+$#", $expr)) {
    echo "regex passed\n";
    echo "result: " . eval("return " . $expr . ";") . "\n";
} else {
    echo "regex failed\n";
}

