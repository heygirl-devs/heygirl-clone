<?php
/** Trang chi tiết gái: breadcrumb, tên, đánh giá, album ảnh, mô tả, thuộc tính. */

function render_detail_page(array $p): void
{
    $attrs = json_decode($p['attrs'] ?: '[]', true) ?: [];
    $gallery = json_decode($p['gallery'] ?: '[]', true) ?: [];
    $images = $gallery ?: ($p['image'] ? [$p['image']] : []);

    // hàng breadcrumb
    $provUrl = $p['province_slug'] ? '/gai-goi/' . e($p['province_slug']) : '/';
    $provName = $p['province'] ?: 'Danh sách';
    $bread = '<a href="' . e($provUrl) . '" style="color:var(--text);text-decoration:none;display:inline-flex;align-items:center;gap:8px"><span style="font-size:22px;line-height:1;flex:0 0 auto;filter:grayscale(1) brightness(2.2)">👈</span>Gái gọi ' . e($provName) . '</a>';

    $ratingRow = '<div style="display:flex;align-items:center;gap:6px;margin-bottom:12px">'
        . '<span style="display:flex;align-items:center;gap:5px">'
        . stars_big_html((float)$p['rating'])
        . '<span style="color:#facc15;font-size:15px;font-weight:700">' . rtrim(rtrim(number_format((float)$p['rating'], 1), '0'), '.') . '</span>'
        . '<span style="color:var(--text3);font-size:13px">(' . (int)$p['review_count'] . ' đánh giá)</span></span>'
        . '<span style="margin-left:auto;display:flex;align-items:center;gap:5px;color:var(--text2);font-size:13px;font-weight:600">'
        . '<svg width="15" height="15" viewBox="0 0 24 24" fill="#b0b3b8"><path d="M12 4.5C6.5 4.5 2 9.2 1 12c1 2.8 5.5 7.5 11 7.5s10-4.7 11-7.5c-1-2.8-5.5-7.5-11-7.5zm0 11.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/><circle cx="12" cy="12" r="2.2"/></svg>' . e($p['views']) . '</span></div>';

    // album ảnh
    $galleryHtml = '';
    if ($images) {
        $main = $images[0];
        $thumbs = '';
        foreach ($images as $i => $img) {
            $thumbs .= '<img src="' . e($img) . '" alt="thumb ' . ($i + 1) . '" loading="lazy" decoding="async" class="album-thumb" data-idx="' . $i . '" style="flex:0 0 auto;width:54px;height:54px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid transparent;opacity:0.6;transition:all .15s">';
        }
        $galleryHtml = '<div style="margin-bottom:14px">'
            . '<div style="border-radius:10px;overflow:hidden;background:var(--bg3);cursor:zoom-in" onclick="gaiguLightbox(\'' . e($main) . '\')"><img src="' . e($main) . '" id="detail-main-img" alt="' . e($p['name']) . '" style="width:100%;display:block;aspect-ratio:6/9;object-fit:cover"></div>'
            . '<div id="album-wrap" style="margin-top:8px;overflow-x:auto;display:flex;gap:6px;padding-bottom:4px">' . $thumbs . '</div></div>'
            . '<div id="gaigu-lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out" onclick="this.style.display=\'none\'"><img src="" id="gaigu-lightbox-img" style="max-width:94vw;max-height:92vh;border-radius:8px"></div>'
            . '<script>'
            . 'var gaiguAlbumMain=document.getElementById("detail-main-img");'
            . 'document.querySelectorAll(".album-thumb").forEach(function(t){ t.addEventListener("click", function(){'
            . 'var src=t.src; gaiguAlbumMain.src=src;'
            . 'document.querySelectorAll(".album-thumb").forEach(function(x){ x.style.opacity="0.6"; x.style.borderColor="transparent"; });'
            . 't.style.opacity="1"; t.style.borderColor="var(--blue)"; }); });'
            . 'function gaiguLightbox(src){ var lb=document.getElementById("gaigu-lightbox"); document.getElementById("gaigu-lightbox-img").src=src; lb.style.display="flex"; }'
            . '</script>';
    }

    // thuộc tính
    $attrRows = '';
    foreach ($attrs as $row) {
        if (!is_array($row) || count($row) < 2) {
            continue;
        }
        [$label, $value] = $row;
        $value = trim((string)$value);
        if ($value === '' || $value === null) {
            continue;
        }
        if (strcasecmp($label, 'SĐT') === 0 && preg_match('/^\d+$/', $value)) {
            $valHtml = '<a href="tel:' . e($value) . '" style="color:#4caf50;font-size:14px;font-weight:600;text-decoration:none">' . e($value) . '</a>';
        } else {
            $valHtml = '<span style="color:var(--text);font-size:14px;font-weight:600;word-break:break-word">' . e($value) . '</span>';
        }
        $attrRows .= '<div class="attr-row" style="display:flex;align-items:center;gap:11px;padding:12px 14px;border-bottom:1px solid var(--border)">'
            . '<span style="color:var(--text3);display:flex;flex-shrink:0"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg></span>'
            . '<span style="color:var(--text2);font-size:14px;min-width:120px;flex-shrink:0">' . e($label) . '</span>'
            . $valHtml . '</div>';
    }
    $attrsBox = $attrRows
        ? '<div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:12px">' . $attrRows . '</div>'
        : '';

    ob_start();
    echo $bread;
    echo '<h1 style="font-size:26px;font-weight:800;line-height:1.3;margin-bottom:6px;color:var(--text)">' . e($p['name']) . '</h1>';
    echo $ratingRow;
    echo $galleryHtml;
    if ($p['description'] !== '') {
        echo '<p style="font-size:14px;color:var(--text2);line-height:1.6;margin-bottom:14px;white-space:pre-line">' . nl2br(e($p['description'])) . '</p>';
    }
    echo $attrsBox;
    echo '<div style="border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:12px">'
        . '<div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:10px">Bình luận</div>'
        . '<div style="font-size:13px;color:var(--text3)">Chưa có bình luận nào.</div></div>';
    $content = ob_get_clean();
    render_layout($p['name'], $content, '', true);
}

function stars_big_html(float $rating): string
{
    $filled = (int)round($rating);
    $html = '<span style="display:flex;align-items:center;gap:2px">';
    for ($i = 1; $i <= 5; $i++) {
        $fill = $i <= $filled ? '#facc15' : '#5a5b5c';
        $html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="' . $fill . '" style="display:block"><path d="M12 2l2.9 6.3 6.8.6-5.1 4.5 1.5 6.7L12 17.3 5.9 20.6l1.5-6.7L2.3 8.9l6.8-.6z"/></svg>';
    }
    return $html . '</span>';
}
