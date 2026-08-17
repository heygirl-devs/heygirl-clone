<?php
declare(strict_types=1);
/**
 * Crawler gaigu — nạp dữ liệu từ site gốc vào SQLite + public/uploads.
 *
 * Cách dùng:
 *   php tools/crawl.php --sitemap               # lấy sitemap.xml -> thêm id/slug còn thiếu
 *   php tools/crawl.php --lists                 # trang chủ, tất cả phân trang (liệt kê toàn bộ hồ sơ)
 *   php tools/crawl.php --details [--limit-details N]
 *   php tools/crawl.php --images [--limit-images N]
 *   php tools/crawl.php --all [--limit-pages N] [--limit-details N]
 * Tuỳ chọn: --base https://gaigu2.fit  --delay 0.2  --dry
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db.php';

init_schema();

$args = getopt('', ['sitemap', 'lists', 'details', 'images', 'all', 'dry', 'base:', 'delay:', 'limit-pages:', 'limit-details:', 'limit-images:', 'page:']);
$BASE = rtrim($args['base'] ?? 'https://gaigu2.fit', '/');
$DELAY = (float)($args['delay'] ?? 0.2);
$UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
$DRY = isset($args['dry']);
$STATE_FILE = DATA_DIR . '/crawl_state.json';

function state(): array
{
    global $STATE_FILE;
    return is_file($STATE_FILE) ? (json_decode((string)file_get_contents($STATE_FILE), true) ?: []) : [];
}
function save_state(array $s): void
{
    global $STATE_FILE;
    file_put_contents($STATE_FILE, json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function fetch_url(string $url): ?string
{
    global $UA;
    $ctx = stream_context_create(['http' => [
        'method' => 'GET',
        'timeout' => 25,
        'header' => "User-Agent: {$UA}\r\nAccept: text/html,application/xhtml+xml\r\nAccept-Language: vi-VN,vi;q=0.9,en;q=0.8\r\n",
        'ignore_errors' => true,
    ]]);
    for ($try = 1; $try <= 3; $try++) {
        $body = @file_get_contents($url, false, $ctx);
        if ($body !== false) {
            return $body;
        }
        sleep(1 + $try);
    }
    fwrite(STDERR, "FAIL $url\n");
    return null;
}

function rate_limit(): void
{
    global $DELAY;
    usleep((int)($DELAY * 1_000_000));
}

/** Trích card từ một trang danh sách. */
function parse_cards(string $html): array
{
    $cards = [];
    if (preg_match_all('#<a href="/gai-goi/(\d+)/([^"]+)" class="product-card".*?</a>#s', $html, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $m) {
            $block = $m[0];
            $name = '';
            if (preg_match('#<div class="product-name">(.*?)</div>#s', $block, $nm)) {
                $name = trim(html_entity_decode(strip_tags($nm[1])));
            }
            $loc = '';
            if (preg_match('#<span style="overflow:hidden;text-overflow:ellipsis">(.*?)</span>#s', $block, $lm)) {
                $loc = trim(html_entity_decode(strip_tags($lm[1])));
            }
            $price = '';
            if (preg_match('~<span style="display:flex;align-items:center;gap:5px;color:#b0b3b8;font-size:13px;font-weight:400;flex-shrink:0">(.*?)</span>~s', $block, $pm)) {
                $price = trim(html_entity_decode(strip_tags($pm[1])));
            }
            $rc = 0;
            if (preg_match('#color:var\(--text3\);font-size:11\.5px;margin-left:3px">\((\d+)\)</span>#', $block, $rm)) {
                $rc = (int)$rm[1];
            }
            $views = '';
            if (preg_match('~<span style="display:flex;align-items:center;gap:4px;color:#b0b3b8;font-size:13px;font-weight:400;flex-shrink:0">.*?<path d="M12 4\.5.*?</svg>([^<]*)</span>~s', $block, $vm)) {
                $views = trim($vm[1]);
            }
            $img = '';
            if (preg_match('#<img src="(/uploads/[^"]+)"#', $block, $im)) {
                $img = $im[1];
            }
            $cards[] = [
                'id' => (int)$m[1],
                'slug' => $m[2],
                'name' => $name,
                'price' => $price,
                'district' => $loc,
                'rating' => 0.0,
                'review_count' => $rc,
                'views' => $views,
                'image' => $img,
                'has_video' => strpos($block, 'title="Có video"') !== false ? 1 : 0,
            ];
        }
    }
    return $cards;
}

function upsert_card(array $c): void
{
    $stmt = db()->prepare(
        'INSERT INTO products (id, slug, name, price, district, rating, review_count, views, image, has_video)
         VALUES (:id,:slug,:name,:price,:district,:rating,:review_count,:views,:image,:has_video)
         ON CONFLICT(id) DO UPDATE SET
           slug=excluded.slug, name=excluded.name, price=excluded.price, district=excluded.district,
           rating=excluded.rating, review_count=excluded.review_count, views=excluded.views,
           image=excluded.image, has_video=excluded.has_video'
    );
    $stmt->execute($c);
}

/* ---------------- phase: --sitemap ---------------- */
function phase_sitemap(): void
{
    global $BASE, $DRY;
    $xml = fetch_url($BASE . '/sitemap.xml');
    if ($xml === null) {
        fwrite(STDERR, "sitemap FAIL\n");
        return;
    }
    file_put_contents(DATA_DIR . '/sitemap.xml', $xml);
    $n = 0;
    $ins = db()->prepare('INSERT OR IGNORE INTO products (id, slug) VALUES (?, ?)');
    if (preg_match_all('#<loc>https?://[^<]*/gai-goi/(\d+)/([^<]+)</loc>#', $xml, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $m) {
            if (!$DRY) {
                $ins->execute([(int)$m[1], $m[2]]);
            }
            $n++;
        }
    }
    fwrite(STDOUT, "== sitemap xong: $n URL sản phẩm\n");
}

/* ---------------- phase: --lists ---------------- */
function phase_lists(?int $limitPages): void
{
    global $BASE, $DRY;
    $st = state();
    $start = (int)($st['lists_done'] ?? 0) + 1;
    $total = 0;
    for ($p = $start; ; $p++) {
        if ($limitPages !== null && $p > $limitPages) {
            break;
        }
        $url = $BASE . ($p === 1 ? '/' : '/?page=' . $p);
        $html = fetch_url($url);
        if ($html === null) {
            fwrite(STDERR, "page $p FAIL — dừng (resume từ đây)\n");
            break;
        }
        $cards = parse_cards($html);
        if (!$cards) {
            fwrite(STDERR, "page $p hết dữ liệu (0 card) — kết thúc lists\n");
            break;
        }
        if (!$DRY) {
            foreach ($cards as $c) {
                upsert_card($c);
            }
            $st['lists_done'] = $p;
            save_state($st);
        }
        $total += count($cards);
        fwrite(STDOUT, "page $p: " . count($cards) . " card (tổng $total)\n");
        rate_limit();
    }
    fwrite(STDOUT, "== lists xong: $total hồ sơ\n");
}

/* ---------------- phase: --details ---------------- */
function parse_detail(string $html, int $id): array
{
    $d = ['description' => '', 'attrs' => [], 'gallery' => [], 'phone' => '', 'address' => '',
          'province' => '', 'province_slug' => '', 'district' => '', 'rating' => 0.0, 'review_count' => 0, 'has_video' => 0];

    // rating từ JSON-LD aggregateRating
    if (preg_match('#"aggregateRating":\{"@type":"AggregateRating","ratingValue":([\d.]+),"reviewCount":(\d+)#', $html, $rm)) {
        $d['rating'] = (float)$rm[1];
        $d['review_count'] = (int)$rm[2];
    }
    // breadcrumb tỉnh/quận: <a href="/gai-goi/{tinh}[/{quan}]"
    if (preg_match('#href="/gai-goi/([a-z0-9-]+)(?:/([a-z0-9-]+))?"#', $html, $bm)) {
        $d['province_slug'] = $bm[1];
        $d['province'] = province_name($bm[1]) ?? $bm[1];
        $d['district'] = isset($bm[2]) ? ucwords(str_replace('-', ' ', $bm[2])) : '';
    }
    // mô tả
    if (preg_match('#<p style="font-size:14px;color:var\(--text2\);line-height:1\.6[^>]*>(.*?)</p>#s', $html, $dm)) {
        $d['description'] = trim(html_entity_decode(strip_tags($dm[1])));
    }
    // gallery (ảnh /uploads/products/public/... theo thứ tự xuất hiện)
    $gallery = [];
    if (preg_match_all('#src="(/uploads/products/public/[^"]+)"#', $html, $gm)) {
        foreach ($gm[1] as $g) {
            if (!in_array($g, $gallery, true)) {
                $gallery[] = $g;
            }
        }
    }
    // album chỉ dùng ảnh md (bản lớn), bỏ biến thể xs/sm trùng ảnh
    $md = array_values(array_filter($gallery, static fn($g) => strpos($g, 'thumb_md_') !== false));
    if ($md) {
        $gallery = $md;
    }
    $d['gallery'] = array_slice($gallery, 0, 8);
    // attr rows
    if (preg_match_all('#<div class="attr-row"[^>]*>(.*?)</div>#s', $html, $am)) {
        foreach ($am[1] as $row) {
            if (preg_match('#<span style="color:var\(--text2\);font-size:14px;min-width:120px;flex-shrink:0">(.*?)</span>(.*)$#s', $row, $rm)) {
                $label = trim(html_entity_decode(strip_tags($rm[1])));
                $value = trim(html_entity_decode(strip_tags($rm[2])));
                $value = preg_replace('/\s+/u', ' ', $value);
                if (preg_match('#href="tel:(\d+)"#', $rm[2], $tm)) {
                    $d['phone'] = $tm[1];
                    $value = $tm[1];
                }
                if ($label === 'Địa chỉ') {
                    $d['address'] = $value;
                }
                $d['attrs'][] = [$label, $value];
            }
        }
    }
    $d['has_video'] = (strpos($html, 'title="Có video"') !== false || stripos($html, 'clip') !== false && strpos($html, 'album') === false) ? 1 : 0;
    return $d;
}

function phase_details(?int $limit): void
{
    global $BASE, $DRY;
    $stmt = db()->prepare('SELECT id, slug FROM products WHERE crawled_at = "" ORDER BY id DESC');
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $done = 0;
    $upd = db()->prepare(
        'UPDATE products SET province=:province, province_slug=:pslug, district=:district, phone=:phone,
           address=:address, rating=:rating, review_count=:rc, has_video=:hv,
           description=:description, gallery=:gallery, attrs=:attrs, crawled_at=:now
         WHERE id=:id'
    );
    foreach ($rows as $r) {
        if ($limit !== null && $done >= $limit) {
            break;
        }
        $html = fetch_url($BASE . '/gai-goi/' . $r['id'] . '/' . urlencode($r['slug']));
        if ($html === null) {
            continue;
        }
        $d = parse_detail($html, (int)$r['id']);
        if (!$DRY) {
            $upd->execute([
                ':province' => $d['province'], ':pslug' => $d['province_slug'], ':district' => $d['district'],
                ':phone' => $d['phone'], ':address' => $d['address'], ':rating' => $d['rating'],
                ':rc' => $d['review_count'], ':hv' => $d['has_video'], ':description' => $d['description'],
                ':gallery' => json_encode($d['gallery'], JSON_UNESCAPED_UNICODE),
                ':attrs' => json_encode($d['attrs'], JSON_UNESCAPED_UNICODE),
                ':now' => date('c'), ':id' => (int)$r['id'],
            ]);
        }
        $done++;
        if ($done % 50 === 0) {
            fwrite(STDOUT, "details: $done/" . count($rows) . "\n");
        }
        rate_limit();
    }
    fwrite(STDOUT, "== details xong: $done hồ sơ\n");
}

/* ---------------- phase: --images ---------------- */
function dl_file(string $path): bool
{
    global $BASE, $UA;
    $dest = PUBLIC_DIR . $path;
    if (is_file($dest) && filesize($dest) > 0) {
        return true;
    }
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'GET', 'timeout' => 30,
        'header' => "User-Agent: {$UA}\r\nReferer: {$BASE}/\r\n",
        'ignore_errors' => true,
    ]]);
    $data = @file_get_contents($BASE . $path, false, $ctx);
    if ($data === false || $data === '') {
        return false;
    }
    file_put_contents($dest, $data);
    return true;
}

/** Tải song song nhiều ảnh bằng curl_multi — nhanh hơn nhiều so với tuần tự. */
function dl_files_parallel(array $paths, int $concurrency = 6): array
{
    global $BASE, $UA;
    $todo = [];
    foreach ($paths as $p) {
        $dest = PUBLIC_DIR . $p;
        if (!is_file($dest) || filesize($dest) === 0) {
            $todo[$p] = $dest;
        }
    }
    if (!$todo) {
        return ['ok' => 0, 'fail' => []];
    }
    if (!function_exists('curl_multi_init')) {
        // fallback tuần tự
        $ok = 0;
        $fail = [];
        foreach ($todo as $p => $dest) {
            if (dl_file($p)) {
                $ok++;
            } else {
                $fail[] = $p;
            }
        }
        return ['ok' => $ok, 'fail' => $fail];
    }

    $mh = curl_multi_init();
    $map = []; // id => ['ch'=>..., 'path'=>..., 'dest'=>...]
    $ok = 0;
    $fail = [];

    $add = static function (string $p, string $dest) use ($mh, &$map, $BASE, $UA): void {
        $ch = curl_init($BASE . $p);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => $UA,
            CURLOPT_REFERER => $BASE . '/',
        ]);
        $map[spl_object_id($ch)] = ['ch' => $ch, 'path' => $p, 'dest' => $dest];
        curl_multi_add_handle($mh, $ch);
    };

    $runBatch = static function () use ($mh, &$map, &$ok, &$fail): void {
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 0.2);
            }
        } while ($running > 0);
        foreach ($map as $info) {
            $body = curl_multi_getcontent($info['ch']);
            $err = curl_error($info['ch']);
            $code = curl_getinfo($info['ch'], CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $info['ch']);
            $okBody = $err === '' && $code === 200 && is_string($body) && $body !== '' && strpos($body, '<!DOCTYPE') === false;
            if ($okBody) {
                $dir = dirname($info['dest']);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($info['dest'], $body);
                $ok++;
            } else {
                $fail[] = $info['path'];
            }
        }
        $map = [];
    };

    foreach ($todo as $p => $dest) {
        $add($p, $dest);
        if (count($map) >= $concurrency) {
            $runBatch();
        }
    }
    $runBatch();
    curl_multi_close($mh);
    return ['ok' => $ok, 'fail' => $fail];
}

function phase_images(?int $limit): void
{
    $stmt = db()->query('SELECT image, gallery FROM products');
    $paths = [];
    while ($r = $stmt->fetch()) {
        if ($limit !== null && count($paths) >= $limit) {
            break;
        }
        if ($r['image']) {
            $paths[] = $r['image'];
        }
        foreach (json_decode($r['gallery'] ?: '[]', true) ?: [] as $g) {
            $paths[] = $g;
        }
    }
    $paths = array_values(array_unique($paths));
    fwrite(STDOUT, "images: tổng " . count($paths) . " file cần kiểm tra\n");
    $totalOk = 0;
    $chunks = array_chunk($paths, 200);
    foreach ($chunks as $i => $chunk) {
        $res = dl_files_parallel($chunk, 6);
        $totalOk += $res['ok'];
        fwrite(STDOUT, "images: chunk " . ($i + 1) . "/" . count($chunks) . " ok=" . $res['ok'] . " fail=" . count($res['fail']) . "\n");
        if ($res['fail']) {
            fwrite(STDERR, "img FAIL: " . implode(' ', array_slice($res['fail'], 0, 5)) . "\n");
        }
    }
    fwrite(STDOUT, "== images xong: tải mới $totalOk file\n");
}

/* ---------------- main ---------------- */
$doSitemap = isset($args['sitemap']) || isset($args['all']);
$doLists = isset($args['lists']) || isset($args['all']);
$doDetails = isset($args['details']) || isset($args['all']);
$doImages = isset($args['images']);

$limitPages = isset($args['limit-pages']) ? (int)$args['limit-pages'] : null;
$limitDetails = isset($args['limit-details']) ? (int)$args['limit-details'] : null;
$limitImages = isset($args['limit-images']) ? (int)$args['limit-images'] : null;

fwrite(STDOUT, "base=$BASE delay={$DELAY}s dry=" . ($DRY ? 'yes' : 'no') . "\n");
if ($doSitemap) {
    phase_sitemap();
}
if ($doLists) {
    phase_lists($limitPages);
}
if ($doDetails) {
    phase_details($limitDetails);
}
if ($doImages) {
    phase_images($limitImages);
}
if (!$doSitemap && !$doLists && !$doDetails && !$doImages) {
    fwrite(STDOUT, "Không có phase nào được chọn. Xem hướng dẫn ở đầu file.\n");
}
