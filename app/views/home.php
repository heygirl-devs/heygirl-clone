<?php
/** Trang danh sách: filter tỉnh/thành + lưới sản phẩm + phân trang. */

function province_select_html(?string $selected): string
{
    $counts = province_counts();
    $opts = '<option value="">Chọn tỉnh/thành phố...</option>';
    foreach (PROVINCES as $slug => $name) {
        $cnt = isset($counts[$slug]) ? ' (' . $counts[$slug] . ')' : '';
        $sel = $selected === $slug ? ' selected' : '';
        $opts .= '<option value="/gai-goi/' . e($slug) . '"' . $sel . '>' . e($name . $cnt) . '</option>';
    }
    $onchange = "location.href=this.value;";
    return '<form class="fhrow" method="get"><div class="fhprov-sel"><select name="tinh" onchange="' . $onchange . '">' . $opts . '</select></div></form>';
}

function pagination_html(int $page, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $links = '';
    $add = static function (int $p, bool $current) use (&$links, $baseUrl) {
        $style = $current
            ? 'background:var(--blue);color:#fff'
            : 'background:var(--bg3);color:var(--text2)';
        $links .= '<a href="' . e($baseUrl . ($p > 1 ? '?page=' . $p : '')) . '" style="min-width:36px;text-align:center;padding:7px 10px;border-radius:7px;text-decoration:none;font-size:13px;font-weight:600;' . $style . '">' . $p . '</a>';
    };
    $add(1, $page === 1);
    if ($page > 3) {
        $links .= '<span style="padding:7px 4px;color:var(--text3)">…</span>';
    }
    for ($p = max(2, $page - 1); $p <= min($totalPages - 1, $page + 1); $p++) {
        $add($p, $p === $page);
    }
    if ($page < $totalPages - 2) {
        $links .= '<span style="padding:7px 4px;color:var(--text3)">…</span>';
    }
    if ($totalPages > 1) {
        $add($totalPages, $page === $totalPages);
    }
    $more = $page < $totalPages
        ? '<a href="' . e($baseUrl . '?page=' . ($page + 1)) . '" rel="next" style="display:inline-block;padding:12px 28px;background:#1565c0;color:#fff;font-size:14px;font-weight:700;border-radius:10px;text-decoration:none">Xem thêm bài đăng →</a>'
        : '';
    return '<div style="margin-top:24px;display:flex;flex-direction:column;align-items:center;gap:14px">' . $more . '<div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center">' . $links . '</div></div>';
}

function render_list_page(string $title, string $h1, array $items, int $page, int $totalPages, string $baseUrl, ?string $selectedProvince = null): void
{
    ob_start();
    echo '<h1 style="font-size:15px;font-weight:700;color:var(--text);margin:0 0 10px;line-height:1.4">' . e($h1) . '</h1>';
    echo province_select_html($selectedProvince);
    if (!$items) {
        echo '<div style="padding:40px 16px;text-align:center;color:var(--text3);font-size:14px">Chưa có dữ liệu. Chạy <code>php tools/crawl.php --lists</code> để nạp dữ liệu.</div>';
    } else {
        echo '<div class="product-grid">';
        foreach ($items as $p) {
            echo card_html($p);
        }
        echo '</div>';
        echo pagination_html($page, $totalPages, $baseUrl);
    }
    $content = ob_get_clean();
    render_layout($title, $content, 'home');
}
