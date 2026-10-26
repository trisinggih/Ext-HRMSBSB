<?php
$directory = new RecursiveDirectoryIterator('src');
$iterator = new RecursiveIteratorIterator($directory);

$count = 0;
foreach ($iterator as $info) {
    if ($info->isFile() && $info->getExtension() === 'vue') {
        $path = $info->getPathname();
        $content = file_get_contents($path);
        
        $original = $content;
        
        // 1. toLocaleTimeString('id-ID') => toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' })
        $content = preg_replace(
            "/\.toLocaleTimeString\('id-ID'\)/", 
            ".toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' })", 
            $content
        );

        // 2. toLocaleDateString('id-ID') => toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' })
        $content = preg_replace(
            "/\.toLocaleDateString\('id-ID'\)/", 
            ".toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' })", 
            $content
        );
        
        // 3. toLocaleString('id-ID') => toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })
        // ONLY if it's on a Date object, not a Number. Numbers use it too for currency!
        // So we look for Date(...).toLocaleString('id-ID') or d.toLocaleString('id-ID')
        // Actually, a safer way is to match (new Date(...)).toLocaleString('id-ID') or d.toLocaleString('id-ID')
        $content = preg_replace(
            "/\.toLocaleString\('id-ID'\)/",
            ".toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })",
            $content
        );

        // 4. toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
        $content = preg_replace(
            "/\.toLocaleTimeString\('id-ID', \{ hour: '2-digit', minute: '2-digit' \}\)/",
            ".toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit' })",
            $content
        );
        
        // 5. LiveTracking.vue has:
        // toLocaleString('id-ID', { \n        day: '2-digit', month: 'short', year: 'numeric', \n        hour: '2-digit', minute: '2-digit' \n      })
        $content = preg_replace(
            "/\.toLocaleString\('id-ID', \{\s*day: '2-digit', month: 'short', year: 'numeric',\s*hour: '2-digit', minute: '2-digit'\s*\}\)/",
            ".toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })",
            $content
        );

        // 6. Approval.vue has:
        // d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
        $content = preg_replace(
            "/\.toLocaleDateString\('id-ID', \{ day: '2-digit', month: 'short' \}\)/",
            ".toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short' })",
            $content
        );

        // 7. Notifications.vue has:
        // d.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
        $content = preg_replace(
            "/\.toLocaleString\('id-ID', \{ day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' \}\)/",
            ".toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })",
            $content
        );

        // 8. KpiAssessmentForm.vue has:
        // toLocaleDateString('id')
        $content = preg_replace(
            "/\.toLocaleDateString\('id'\)/",
            ".toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' })",
            $content
        );

        // 9. CandidateDetail.vue has:
        // toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })
        $content = preg_replace(
            "/\.toLocaleString\('id-ID', \{ dateStyle: 'short', timeStyle: 'short' \}\)/",
            ".toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', dateStyle: 'short', timeStyle: 'short' })",
            $content
        );
        
        // Let's fix CanvassingMonitor.vue
        $content = preg_replace(
            "/\.toLocaleString\('id-ID', \{\s*day: '2-digit', month: 'short', year: 'numeric',\s*hour: '2-digit', minute: '2-digit'\s*\}\)/m",
            ".toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })",
            $content
        );

        // Fix the Numbers accidentally affected by 3 (Number(val).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }))
        // Rollback Numbers formatting
        $content = preg_replace(
            "/Number\((.*?)\)\.toLocaleString\('id-ID', \{ timeZone: 'Asia\/Jakarta' \}\)/",
            "Number($1).toLocaleString('id-ID')",
            $content
        );

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated: $path\n";
            $count++;
        }
    }
}
echo "Total updated files: $count\n";
