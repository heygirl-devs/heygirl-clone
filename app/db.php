<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0777, true);
        }
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000;');
    }
    return $pdo;
}

function init_schema(): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY,
  slug TEXT NOT NULL DEFAULT '',
  name TEXT NOT NULL DEFAULT '',
  price TEXT NOT NULL DEFAULT '',
  province TEXT NOT NULL DEFAULT '',
  province_slug TEXT NOT NULL DEFAULT '',
  district TEXT NOT NULL DEFAULT '',
  phone TEXT NOT NULL DEFAULT '',
  address TEXT NOT NULL DEFAULT '',
  rating REAL NOT NULL DEFAULT 0,
  review_count INTEGER NOT NULL DEFAULT 0,
  views TEXT NOT NULL DEFAULT '',
  has_video INTEGER NOT NULL DEFAULT 0,
  description TEXT NOT NULL DEFAULT '',
  image TEXT NOT NULL DEFAULT '',
  gallery TEXT NOT NULL DEFAULT '[]',
  attrs TEXT NOT NULL DEFAULT '[]',
  crawled_at TEXT NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS idx_products_province ON products(province_slug, id DESC);
CREATE INDEX IF NOT EXISTS idx_products_name ON products(name);
SQL;
    db()->exec($sql);

    // migration nhẹ (idempotent): price_num + status — chạy lại an toàn mỗi lần
    $cols = [];
    foreach (db()->query('PRAGMA table_info(products)') as $c) {
        $cols[$c['name']] = true;
    }
    if (!isset($cols['price_num'])) {
        db()->exec('ALTER TABLE products ADD COLUMN price_num INTEGER');
    }
    if (!isset($cols['status'])) {
        db()->exec("ALTER TABLE products ADD COLUMN status TEXT NOT NULL DEFAULT 'active'");
    }
    // backfill price_num từ price text (chỉ dòng còn thiếu) — cần parse_price_to_num từ bootstrap
    if (function_exists('parse_price_to_num')) {
        $rows = db()->query("SELECT id, price FROM products WHERE price_num IS NULL AND price <> ''")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $upd = db()->prepare('UPDATE products SET price_num = ? WHERE id = ?');
            foreach ($rows as $r) {
                $n = parse_price_to_num($r['price']);
                if ($n !== null) {
                    $upd->execute([$n, (int)$r['id']]);
                }
            }
        }
    }
}
