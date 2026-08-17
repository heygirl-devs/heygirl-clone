<?php
declare(strict_types=1);
/** Xem chi tiết 1 hồ sơ trong DB — chạy: php tools/show.php <id> */
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db.php';
init_schema();
$id = (int)($argv[1] ?? 4953);
$r = db()->prepare('SELECT id, slug, name, price, province, district, phone, address, rating, review_count, views, has_video, description, attrs, image, gallery FROM products WHERE id = ?');
$r->execute([$id]);
$row = $r->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "not found\n";
    exit(1);
}
echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
