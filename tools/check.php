<?php
declare(strict_types=1);
/** Kiểm tra trạng thái DB — chạy: php tools/check.php */
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db.php';
init_schema();

$db = db();
$n = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$nDet = (int)$db->query('SELECT COUNT(*) FROM products WHERE crawled_at <> ""')->fetchColumn();
$nImg = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PUBLIC_DIR . '/uploads', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->isFile()) {
        $nImg++;
    }
}
echo "products_total=$n details=$nDet images_downloaded=$nImg\n";
$rows = $db->query('SELECT id, slug, name, price, district, province, phone, rating, review_count, views, has_video, image FROM products ORDER BY id DESC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}
$prov = $db->query('SELECT province_slug, COUNT(*) c FROM products WHERE province_slug <> "" GROUP BY province_slug ORDER BY c DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
echo "top_provinces=" . json_encode($prov, JSON_UNESCAPED_UNICODE) . "\n";
$agg = $db->query('SELECT COUNT(*) n, MIN(id) mn, MAX(id) mx, COUNT(DISTINCT id) d FROM products')->fetch(PDO::FETCH_ASSOC);
echo "agg=" . json_encode($agg) . "\n";
$empty = $db->query('SELECT COUNT(*) FROM products WHERE price = ""')->fetchColumn();
echo "rows_without_price=$empty\n";
