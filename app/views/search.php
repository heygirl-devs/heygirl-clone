<?php
/** Trang tìm kiếm: dùng chung lưới sản phẩm + phân trang. */

function render_search_page(string $q, array $items, int $page, int $totalPages, string $baseUrl): void
{
    ob_start();
    $q = trim($q);
    echo '<h1 style="font-size:15px;font-weight:700;color:var(--text);margin:0 0 10px;line-height:1.4">Kết quả tìm kiếm cho "' . e($q) . '"</h1>';
    if (!$items) {
        echo '<div style="padding:40px 16px;text-align:center;color:var(--text3);font-size:14px">Không tìm thấy kết quả phù hợp.</div>';
    } else {
        echo '<div class="product-grid">';
        foreach ($items as $p) {
            echo card_html($p);
        }
        echo '</div>';
        echo pagination_html($page, $totalPages, $baseUrl);
    }
    $content = ob_get_clean();
    render_layout('Tìm kiếm: ' . $q, $content, '');
}
