<?php
declare(strict_types=1);
/** Front controller: phục vụ file tĩnh + routing. */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/views/layout.php';
require_once __DIR__ . '/../app/views/home.php';
require_once __DIR__ . '/../app/views/search.php';
require_once __DIR__ . '/../app/views/detail.php';
require_once __DIR__ . '/../app/views/placeholder.php';

init_schema();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);

/* ---------- file tĩnh (chạy kiểu php -S ... index.php) ---------- */
$file = PUBLIC_DIR . $uri;
if ($uri !== '/' && is_file($file)) {
    $real = realpath($file);
    $root = realpath(PUBLIC_DIR);
    if ($real !== false && $root !== false && str_starts_with($real, $root)) {
        $mime = [
            'css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp', 'ico' => 'image/x-icon', 'svg' => 'image/svg+xml',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
            'webmanifest' => 'application/manifest+json', 'html' => 'text/html',
        ];
        $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mime[$ext] ?? 'application/octet-stream'));
        header('X-Content-Type-Options: nosniff');
        readfile($real);
        exit;
    }
}

/* ---------- routing ---------- */
$path = trim($uri, '/');
$segments = $path === '' ? [] : explode('/', $path);
$page = max(1, (int)($_GET['page'] ?? 1));

$renderList = static function (string $title, string $h1, string $where, array $params, string $baseUrl, ?string $prov = null) use ($page) {
    $countSql = 'SELECT COUNT(*) c FROM products WHERE ' . $where;
    $stmt = db()->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / PER_PAGE));
    $page = min($page, $totalPages);
    $sql = 'SELECT * FROM products WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . PER_PAGE . ' OFFSET ' . (($page - 1) * PER_PAGE);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    render_list_page($title, $h1, $stmt->fetchAll(), $page, $totalPages, $baseUrl, $prov);
};

/* / (trang chủ) */
if ($path === '') {
    $renderList('Gái Gọi Gaigu Có Video', 'Gái Gọi Gaigu Có Video: gái gọi cao cấp toàn quốc, ảnh thật, clip show, kín đáo, uy tín', '1=1', [], '/', null);
    exit;
}

/* /tim-kiem */
if ($segments[0] === 'tim-kiem') {
    $q = trim((string)($_GET['q'] ?? ''));
    if ($q === '') {
        render_placeholder('Tìm kiếm', 'Nhập tên, nghệ danh hoặc SĐT cần tìm.');
        exit;
    }
    $like = '%' . $q . '%';
    $base = '/tim-kiem?q=' . urlencode($q);
    $renderList('Tìm kiếm: ' . $q, 'Kết quả tìm kiếm cho "' . $q . '"', '(name LIKE ? OR phone LIKE ?)', [$like, $like], $base);
    exit;
}

/* /gai-goi/... */
if ($segments[0] === 'gai-goi') {
    $n = count($segments);

    /* /gai-goi/{id}/{slug} — chi tiết */
    if ($n >= 2 && ctype_digit($segments[1])) {
        $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([(int)$segments[1]]);
        $p = $stmt->fetch();
        if (!$p) {
            render_404();
        } else {
            render_detail_page($p);
        }
        exit;
    }

    /* /gai-goi/{tinh}[/{quan}] — danh sách theo tỉnh */
    $prov = $segments[1] ?? '';
    if ($prov === '' || !province_name($prov)) {
        render_404();
        exit;
    }
    $baseUrl = '/gai-goi/' . e($prov);
    if ($n >= 3) {
        $quan = $segments[2];
        $title = 'Gái Gọi ' . province_name($prov) . ' - ' . $quan;
        $renderList($title, 'Gái gọi ' . province_name($prov) . ' - ' . $quan, 'province_slug = ?', [$prov], $baseUrl . '/' . e($quan), $prov);
    } else {
        $title = 'Gái Gọi ' . province_name($prov);
        $renderList($title, 'Gái gọi ' . province_name($prov), 'province_slug = ?', [$prov], $baseUrl, $prov);
    }
    exit;
}

/* các tính năng ngoài MVP */
$out = [
    'khu-vuc', 'thao-luan', 'chat-sex', 'login', 'cap-bac', 'doi-sao', 'nhiem-vu',
    'nhan-sao', 'gai-voucher', 'danh-gia-moi', 'nguoi-dung', 'mention',
];
if (in_array($segments[0], $out, true)) {
    render_placeholder(ucfirst($segments[0]), 'Tính năng "' . $segments[0] . '" đang được xây dựng trong giai đoạn sau của MVP.');
    exit;
}

render_404();
