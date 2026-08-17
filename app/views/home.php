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
    $pin = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
    $msg = '<span id="nearbyMsg" style="display:block;font-size:12px;color:var(--text3);margin:-8px 0 12px"></span>';
    $js = '<script>
function findNearby(){
    var btn = document.getElementById("nearbyBtn");
    var msg = document.getElementById("nearbyMsg");
    if (!navigator.geolocation){ msg.textContent = "Trình duyệt không hỗ trợ định vị."; return; }
    btn.disabled = true;
    msg.textContent = "Đang lấy vị trí của bạn...";
    navigator.geolocation.getCurrentPosition(function(pos){
        var lat = pos.coords.latitude, lng = pos.coords.longitude;
        var u = new URL(window.location.href);
        u.searchParams.set("user_lat", lat.toFixed(7));
        u.searchParams.set("user_lng", lng.toFixed(7));
        window.location.href = u.toString();
    }, function(err){
        btn.disabled = false;
        msg.textContent = "Không lấy được vị trí. Cần dùng HTTPS hoặc localhost (hoặc bật quyền định vị).";
    });
}
</script>';
    return '<div class="fhrow">'
        . '<div class="fhprov-sel"><select name="tinh" onchange="location.href=this.value;">' . $opts . '</select></div>'
        . '<button type="button" id="nearbyBtn" onclick="findNearby()" class="fhnear">' . $pin . '<span class="fhnear-txt">Tìm quanh đây</span></button>'
        . '</div>' . $msg . $js;
}

function pagination_html(int $page, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $links = '';
    $sep = strpos($baseUrl, '?') !== false ? '&' : '?';
    $add = static function (int $p, bool $current) use (&$links, $baseUrl, $sep) {
        $style = $current
            ? 'background:var(--blue);color:#fff'
            : 'background:var(--bg3);color:var(--text2)';
        $links .= '<a href="' . e($baseUrl . ($p > 1 ? $sep . 'page=' . $p : '')) . '" style="min-width:36px;text-align:center;padding:7px 10px;border-radius:7px;text-decoration:none;font-size:13px;font-weight:600;' . $style . '">' . $p . '</a>';
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
        ? '<a href="' . e($baseUrl . $sep . 'page=' . ($page + 1)) . '" rel="next" style="display:inline-block;padding:12px 28px;background:#1565c0;color:#fff;font-size:14px;font-weight:700;border-radius:10px;text-decoration:none">Xem thêm bài đăng →</a>'
        : '';
    return '<div style="margin-top:24px;display:flex;flex-direction:column;align-items:center;gap:14px">' . $more . '<div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center">' . $links . '</div></div>';
}

function render_list_page(string $title, string $h1, array $items, int $page, int $totalPages, string $baseUrl, ?string $selectedProvince = null, bool $nearby = false, array $distanceById = []): void
{
    ob_start();
    echo '<h1 style="font-size:15px;font-weight:700;color:var(--text);margin:0 0 10px;line-height:1.4">' . e($h1) . '</h1>';
    echo province_select_html($selectedProvince);
    if ($nearby) {
        echo '<div style="font-size:12.5px;color:var(--blue);font-weight:600;display:flex;align-items:center;gap:5px;margin:-8px 0 14px">'
            . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'
            . ' Đang xếp theo khoảng cách gần bạn</div>';
    }
    if (!$items) {
        echo '<div style="padding:40px 16px;text-align:center;color:var(--text3);font-size:14px">Chưa có dữ liệu. Chạy <code>php tools/crawl.php --lists</code> để nạp dữ liệu.</div>';
    } else {
        echo '<div class="product-grid">';
        foreach ($items as $p) {
            $dist = $nearby ? ($distanceById[$p['id']] ?? null) : null;
            echo card_html($p, true, $dist);
        }
        echo '</div>';
        echo pagination_html($page, $totalPages, $baseUrl);
    }
    $content = ob_get_clean();
    render_layout($title, $content, 'home');
}
