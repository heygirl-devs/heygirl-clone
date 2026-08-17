<?php
require __DIR__ . '/../app/bootstrap.php';
init_schema();
$slugs = db()->query("SELECT province_slug, COUNT(*) c FROM products WHERE province_slug <> '' GROUP BY province_slug ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
$known = array_keys(PROVINCES);
foreach ($slugs as $r) {
    $flag = in_array($r['province_slug'], $known, true) ? '' : '  <-- KHÔNG khớp PROVINCES';
    echo str_pad($r['province_slug'], 22) . str_pad((string)$r['c'], 6) . $flag . "\n";
}
