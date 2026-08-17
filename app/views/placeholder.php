<?php
/** Trang tạm cho các tính năng ngoài MVP. */

function render_placeholder(string $title, string $msg): void
{
    ob_start();
    echo '<h1 style="font-size:20px;font-weight:800;color:var(--text);margin:0 0 10px">' . e($title) . '</h1>';
    echo '<div style="padding:32px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:10px;text-align:center;color:var(--text2);font-size:14px">'
        . e($msg) . '<br><br><a href="/" style="color:var(--blue);text-decoration:none;font-weight:600">← Về trang chủ</a></div>';
    $content = ob_get_clean();
    render_layout($title, $content);
}

function render_404(): void
{
    http_response_code(404);
    ob_start();
    echo '<div style="padding:60px 16px;text-align:center;color:var(--text3);font-size:15px">Trang không tồn tại.<br><br><a href="/" style="color:var(--blue);text-decoration:none">← Về trang chủ</a></div>';
    render_layout('404', ob_get_clean());
}
