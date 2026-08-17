<?php
require __DIR__ . '/../app/bootstrap.php';
init_schema();
$rows = db()->query("SELECT DISTINCT province_slug, province, district FROM products WHERE district <> '' ORDER BY province_slug, district")->fetchAll(PDO::FETCH_ASSOC);
echo "distinct district names: " . count($rows) . "\n";
foreach (array_slice($rows, 0, 20) as $r) {
    echo "{$r['province_slug']} | {$r['province']} | {$r['district']}\n";
}
