<?php
declare(strict_types=1);
/**
 * Bảng toạ độ cho tính năng "Tìm quanh đây".
 *
 * Cách dùng:
 *   php tools/locations.php --seed-provinces   # nạp centroid 63 tỉnh/thành (tĩnh, chuẩn)
 *   php tools/locations.php --geocode          # geocode quận/huyện thiếu qua Nominatim (1 req/s)
 *   php tools/locations.php --status           # thống kê
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db.php';

init_schema();
$args = getopt('', ['seed-provinces', 'geocode', 'status']);

/** Centroid các tỉnh/thành (slug gốc => [lat, lng]) — độ chính xác ~5-15 km, dùng làm fallback. */
const PROVINCE_COORDS = [
    'sai-gon' => [10.8231, 106.6297], 'ha-noi' => [21.0278, 105.8342],
    'da-nang' => [16.0544, 108.2022], 'hai-phong' => [20.8449, 106.6881],
    'can-tho' => [10.0452, 105.7469], 'n-giang' => [10.3844, 105.4323],
    'ba-ria-vung-tau' => [10.5417, 107.2428], 'bac-giang' => [21.2755, 106.1947],
    'bac-kan' => [22.1470, 105.8347], 'bac-lieu' => [9.2940, 105.7216],
    'bac-ninh' => [21.1861, 106.0763], 'ben-tre' => [10.2333, 106.3767],
    'binh-dinh' => [13.7849, 109.2218], 'binh-duong' => [11.0556, 106.6675],
    'binh-phuoc' => [11.7512, 106.7232], 'binh-thuan' => [11.0908, 108.0721],
    'ca-mau' => [9.1769, 105.1536], 'cao-bang' => [22.6670, 106.2573],
    'dak-lak' => [12.7101, 108.2382], 'dak-nong' => [11.9854, 107.8382],
    'dien-bien' => [21.3856, 103.0196], 'dong-nai' => [11.0216, 107.1406],
    'dong-thap' => [10.4494, 105.7649], 'lai' => [13.9833, 108.0000],
    'ha-giang' => [22.8026, 104.9784], 'ha-nam' => [20.5833, 105.9833],
    'ha-tinh' => [18.3550, 105.8876], 'hai-duong' => [20.9409, 106.3330],
    'hau-giang' => [9.7833, 105.4667], 'hoa-binh' => [20.8176, 105.3377],
    'hung-yen' => [20.6464, 106.0511], 'khanh-hoa' => [12.2388, 109.1967],
    'kien-giang' => [10.0125, 105.0809], 'kon-tum' => [14.3499, 107.9979],
    'lai-chau' => [22.3864, 103.4698], 'lam-dong' => [11.5757, 108.1469],
    'lang-son' => [21.8527, 106.7613], 'lao-cai' => [22.4810, 103.9758],
    'long-an' => [10.5390, 106.4159], 'nam-dinh' => [20.4388, 106.1621],
    'nghe-an' => [19.2349, 104.9200], 'ninh-binh' => [20.2500, 105.9667],
    'ninh-thuan' => [11.5823, 108.9884], 'phu-tho' => [21.4017, 105.2255],
    'phu-yen' => [13.0955, 109.3158], 'quang-binh' => [17.5394, 106.3487],
    'quang-nam' => [15.5394, 108.0191], 'quang-ngai' => [15.1214, 108.8048],
    'quang-ninh' => [21.0064, 107.2931], 'quang-tri' => [16.7403, 107.1853],
    'soc-trang' => [9.6039, 105.9800], 'son-la' => [21.3273, 103.9140],
    'tay-ninh' => [11.3352, 106.1103], 'thai-binh' => [20.4460, 106.3367],
    'thai-nguyen' => [21.5942, 105.8482], 'thanh-hoa' => [19.8074, 105.7764],
    'thua-thien-hue' => [16.4637, 107.5909], 'tien-giang' => [10.4494, 106.3424],
    'tra-vinh' => [9.9348, 106.3453], 'tuyen-quang' => [21.8231, 105.2202],
    'vinh-long' => [10.2541, 105.9722], 'vinh-phuc' => [21.3085, 105.6049],
    'yen-bai' => [21.7074, 104.8755],
];

function seed_provinces(): int
{
    $db = db();
    $db->exec('CREATE TABLE IF NOT EXISTS locations (
        key TEXT PRIMARY KEY,
        province_slug TEXT NOT NULL DEFAULT "",
        district TEXT NOT NULL DEFAULT "",
        lat REAL NOT NULL DEFAULT 0,
        lng REAL NOT NULL DEFAULT 0,
        src TEXT NOT NULL DEFAULT "province"
    )');
    $ins = $db->prepare('INSERT OR REPLACE INTO locations (key, province_slug, district, lat, lng, src) VALUES (?,?,?,?,?,?)');
    $n = 0;
    foreach (PROVINCE_COORDS as $slug => [$lat, $lng]) {
        $name = province_name($slug) ?? $slug;
        $ins->execute([loc_key($slug, ''), $slug, '', $lat, $lng, 'province']);
        $n++;
    }
    return $n;
}

function geocode_districts(): void
{
    $db = db();
    $rows = $db->query(
        "SELECT DISTINCT province_slug, district FROM products WHERE district <> '' ORDER BY province_slug, district"
    )->fetchAll(PDO::FETCH_ASSOC);
    $ins = $db->prepare('INSERT OR REPLACE INTO locations (key, province_slug, district, lat, lng, src) VALUES (?,?,?,?,?,?)');
    $done = 0;
    $fail = 0;
    foreach ($rows as $r) {
        $prov = $r['province_slug'];
        $dist = $r['district'];
        $key = loc_key($prov, $dist);
        // đã có toạ độ chuẩn (nominatim) thì bỏ qua
        $cur = $db->prepare('SELECT src FROM locations WHERE key = ?');
        $cur->execute([$key]);
        $existing = $cur->fetchColumn();
        if ($existing === 'nominatim') {
            continue;
        }
        // thử nhiều dạng query, ưu tiên "quận/huyện" trong tỉnh
        $provName = province_name($prov) ?? $prov;
        $queries = [
            $dist . ', ' . $provName . ', Vietnam',
            'Huyện ' . $dist . ', ' . $provName . ', Vietnam',
            'Quận ' . $dist . ', ' . $provName . ', Vietnam',
            $dist . ', Vietnam',
        ];
        [$lat, $lng] = try_geocode($queries);
        if ($lat !== null && $lng !== null) {
            $ins->execute([$key, $prov, $dist, $lat, $lng, 'nominatim']);
            $done++;
        } else {
            $fail++;
            fwrite(STDERR, "geocode FAIL: $prov | $dist\n");
        }
        usleep(1_100_000); // Nominatim: tối đa 1 req/s
    }
    fwrite(STDOUT, "== geocode xong: ok=$done fail=$fail\n");
}

function try_geocode(array $queries): array
{
    foreach ($queries as $i => $q) {
        $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&accept-language=vi&q=' . urlencode($q);
        $ctx = stream_context_create(['http' => [
            'timeout' => 20,
            'header' => "User-Agent: HeygirlClone/1.0 (dev; +https://localhost)\r\n",
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body !== false) {
            $arr = json_decode($body, true);
            if (is_array($arr) && isset($arr[0]['lat'], $arr[0]['lon'])) {
                return [(float)$arr[0]['lat'], (float)$arr[0]['lon']];
            }
        }
        if ($i < count($queries) - 1) {
            usleep(1_100_000);
        }
    }
    return [null, null];
}

function location_status(): void
{
    $db = db();
    $n = (int)$db->query('SELECT COUNT(*) FROM locations')->fetchColumn();
    $nom = (int)$db->query("SELECT COUNT(*) FROM locations WHERE src='nominatim'")->fetchColumn();
    $prov = (int)$db->query("SELECT COUNT(*) FROM locations WHERE src='province'")->fetchColumn();
    echo "locations_total=$n nominatim=$nom province_centroid=$prov\n";
}

if (isset($args['seed-provinces'])) {
    $n = seed_provinces();
    echo "seed provinces xong: $n\n";
}
if (isset($args['geocode'])) {
    geocode_districts();
}
if (isset($args['status'])) {
    location_status();
}
if (!$args) {
    fwrite(STDOUT, "Xem hướng dẫn ở đầu file.\n");
}
