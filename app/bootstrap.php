<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const DATA_DIR = APP_ROOT . '/data';
const PUBLIC_DIR = APP_ROOT . '/public';
const DB_FILE = DATA_DIR . '/gaigu.sqlite';
const PER_PAGE = 56;

date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/db.php';

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Danh sách tỉnh/thành theo slug của site gốc (giữ nguyên slug của họ để URL khớp). */
const PROVINCES = [
    'sai-gon' => 'Sài Gòn', 'ha-noi' => 'Hà Nội', 'da-nang' => 'Đà Nẵng',
    'hai-phong' => 'Hải Phòng', 'can-tho' => 'Cần Thơ', 'n-giang' => 'An Giang',
    'ba-ria-vung-tau' => 'Bà Rịa - Vũng Tàu', 'bac-giang' => 'Bắc Giang',
    'bac-kan' => 'Bắc Kạn', 'bac-lieu' => 'Bạc Liêu', 'bac-ninh' => 'Bắc Ninh',
    'ben-tre' => 'Bến Tre', 'binh-dinh' => 'Bình Định', 'binh-duong' => 'Bình Dương',
    'binh-phuoc' => 'Bình Phước', 'binh-thuan' => 'Bình Thuận', 'ca-mau' => 'Cà Mau',
    'cao-bang' => 'Cao Bằng', 'dak-lak' => 'Đắk Lắk', 'dak-nong' => 'Đắk Nông',
    'dien-bien' => 'Điện Biên', 'dong-nai' => 'Đồng Nai', 'dong-thap' => 'Đồng Tháp',
    'lai' => 'Gia Lai', 'ha-giang' => 'Hà Giang', 'ha-nam' => 'Hà Nam',
    'ha-tinh' => 'Hà Tĩnh', 'hai-duong' => 'Hải Dương', 'hau-giang' => 'Hậu Giang',
    'hoa-binh' => 'Hòa Bình', 'hung-yen' => 'Hưng Yên', 'khanh-hoa' => 'Khánh Hòa',
    'kien-giang' => 'Kiên Giang', 'kon-tum' => 'Kon Tum', 'lai-chau' => 'Lai Châu',
    'lam-dong' => 'Lâm Đồng', 'lang-son' => 'Lạng Sơn', 'lao-cai' => 'Lào Cai',
    'long-an' => 'Long An', 'nam-dinh' => 'Nam Định', 'nghe-an' => 'Nghệ An',
    'ninh-binh' => 'Ninh Bình', 'ninh-thuan' => 'Ninh Thuận', 'phu-tho' => 'Phú Thọ',
    'phu-yen' => 'Phú Yên', 'quang-binh' => 'Quảng Bình', 'quang-nam' => 'Quảng Nam',
    'quang-ngai' => 'Quảng Ngãi', 'quang-ninh' => 'Quảng Ninh', 'quang-tri' => 'Quảng Trị',
    'soc-trang' => 'Sóc Trăng', 'son-la' => 'Sơn La', 'tay-ninh' => 'Tây Ninh',
    'thai-binh' => 'Thái Bình', 'thai-nguyen' => 'Thái Nguyên', 'thanh-hoa' => 'Thanh Hóa',
    'thua-thien-hue' => 'Thừa Thiên Huế', 'tien-giang' => 'Tiền Giang',
    'tra-vinh' => 'Trà Vinh', 'tuyen-quang' => 'Tuyên Quang', 'vinh-long' => 'Vĩnh Long',
    'vinh-phuc' => 'Vĩnh Phúc', 'yen-bai' => 'Yên Bái',
];

function province_name(string $slug): ?string
{
    return PROVINCES[$slug] ?? null;
}

function province_counts(): array
{
    static $counts = null;
    if ($counts === null) {
        $counts = [];
        foreach (db()->query('SELECT province_slug, COUNT(*) c FROM products WHERE province_slug <> "" GROUP BY province_slug') as $r) {
            $counts[$r['province_slug']] = (int)$r['c'];
        }
    }
    return $counts;
}

function stars_html(float $rating): string
{
    $filled = (int)round($rating);
    $html = '<span style="display:flex;align-items:center;gap:0;font-size:0">';
    for ($i = 1; $i <= 5; $i++) {
        $fill = $i <= $filled ? '#e02020' : '#5a5b5c';
        $html .= '<svg width="12.5" height="12.5" viewBox="0 0 24 24" fill="' . $fill . '"><path d="M12 2l2.9 6.3 6.8.6-5.1 4.5 1.5 6.7L12 17.3 5.9 20.6l1.5-6.7L2.3 8.9l6.8-.6z"/></svg>';
    }
    $html .= '</span>';
    return $html;
}

function card_html(array $p, bool $lazy = true): string
{
    $img = $p['image'] ?: '';
    $href = '/gai-goi/' . (int)$p['id'] . '/' . e($p['slug']);
    $name = e($p['name']);
    $loc = e($p['district'] ?: $p['province']);
    $price = e($p['price'] ?: '');
    $rc = (int)$p['review_count'];
    $views = e($p['views'] ?: '');
    $stars = stars_html((float)$p['rating']);
    $load = $lazy ? 'loading="lazy"' : 'loading="eager" fetchpriority="high"';
    $video = (int)$p['has_video'] ? '
        <div style="position:absolute;bottom:6px;right:6px">
            <span style="display:flex;align-items:center;justify-content:center" title="Có video">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="#ff5722" style="display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.7))" aria-hidden="true"><path d="M17 10.5V7a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3.5l4 4v-11l-4 4z"/></svg>
            </span>
        </div>' : '';

    return <<<HTML
<a href="{$href}" class="product-card">
    <div style="position:relative;overflow:hidden">
        <div class="img-wrap">
            <img src="{$img}" alt="{$name}" {$load} decoding="async" style="width:100%;height:100%;object-fit:cover;display:block">
        </div>{$video}
    </div>
    <div class="product-info">
        <div class="product-name">{$name}</div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:6px;white-space:nowrap">
            <span style="display:flex;align-items:center;gap:4px;color:var(--blue);font-size:12.5px;font-weight:600;overflow:hidden;min-width:0"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg><span style="overflow:hidden;text-overflow:ellipsis">{$loc}</span></span>
            <span style="display:flex;align-items:center;gap:5px;color:#b0b3b8;font-size:13px;font-weight:400;flex-shrink:0">{$price}</span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:4px;white-space:nowrap">
            <span style="display:flex;align-items:center;gap:4px">{$stars}<span style="color:var(--text3);font-size:11.5px">({$rc})</span></span>
            <span style="display:flex;align-items:center;gap:4px;color:#b0b3b8;font-size:13px;font-weight:400;flex-shrink:0"><svg width="15" height="15" viewBox="0 0 24 24" fill="#b0b3b8"><path d="M12 4.5C6.5 4.5 2 9.2 1 12c1 2.8 5.5 7.5 11 7.5s10-4.7 11-7.5c-1-2.8-5.5-7.5-11-7.5zm0 11.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/><circle cx="12" cy="12" r="2.2"/></svg>{$views}</span>
        </div>
    </div>
</a>
HTML;
}
