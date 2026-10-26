<?php
$expr = "if(0 == 0, 500000, if(0 == 1, 300000, if(0 == 2, 200000, if(0 == 3, 100000, 0))))";
$safetyLimit = 10;
$prevExpr = null;
while ($prevExpr !== $expr && $safetyLimit-- > 0) {
    $prevExpr = $expr;
    $expr = preg_replace_callback("/\bif\s*\(([^,]+),([^,]+),([^()]+)\)/i", function($m) {
        return "((" . trim($m[1]) . ") ? (" . trim($m[2]) . ") : (" . trim($m[3]) . "))";
    }, $expr);
}
echo "Parsed: " . $expr . "\n";
if (preg_match("#^[0-9+\-*/().<>=! \?\:]+$#", $expr)) {
    echo "regex passed\n";
    echo "result: " . eval("return " . $expr . ";") . "\n";
} else {
    echo "regex failed\n";
}

