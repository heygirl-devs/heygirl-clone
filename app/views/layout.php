<?php
/** Layout chung: header (top-nav + thanh tìm kiếm), nội dung, footer, bottom-nav. */
if (!function_exists('render_layout')) {
    function render_layout(string $title, string $content, string $activeNav = '', bool $detailCss = false): void
    {
        $searchBtn = '<button type="button" onclick="fhSearchToggle()" style="background:none;border:none;cursor:pointer;padding:4px;display:flex;align-items:center" title="Tìm kiếm" aria-label="Tìm kiếm"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text2)" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg></button>';
        $active = static fn(string $k): string => $activeNav === $k ? ' class="active"' : '';
        echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f0f10">
    <title>' . e($title) . '</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="preload" as="style" href="/fonts/inter.css" onload="this.onload=null;this.rel=\'stylesheet\'">
    <noscript><link rel="stylesheet" href="/fonts/inter.css"></noscript>
    <link rel="preload" href="/fonts/black-rose.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/css/site.css">' . ($detailCss ? "\n    <link rel=\"stylesheet\" href=\"/css/detail.css\">" : '') . '
</head>
<body>
<header class="app-header">
    <div class="top-nav">
        <button type="button" class="gami-btn" onclick="gamiToggle(event)"><span class="gb-ico">🌟</span><span>Nhận sao đổi quà</span></button>
        <a href="/" class="logo">gaigu</a>
        <div style="display:flex;align-items:center;gap:10px;margin-left:auto">' . $searchBtn . '</div>
    </div>
    <div id="fhSearchBar" style="display:none;background:var(--nav);padding:10px 16px;border-bottom:1px solid rgba(255,255,255,0.06)">
        <form method="GET" action="/tim-kiem" style="display:flex;gap:8px;max-width:640px;margin:0 auto">
            <input type="text" name="q" id="fhSearchInput" value="" placeholder="Tìm theo tên, nghệ danh hoặc SĐT..." style="flex:1;font-size:14px;padding:10px 14px;border-radius:10px;background:var(--bg3);border:1px solid var(--border);color:var(--text)">
            <button type="submit" style="background:var(--blue);color:#fff;border:none;border-radius:10px;padding:0 18px;font-size:14px;font-weight:700;cursor:pointer">Tìm</button>
        </form>
    </div>
</header>
<main class="page-main">
' . $content . '
</main>
<footer style="margin-top:16px;border-top:1px solid var(--border);background:var(--bg2);padding:24px 16px 90px">
    <div style="max-width:640px;margin:0 auto;font-size:13px;color:var(--text3);line-height:1.7">Gái Gú - Gái Tơ</div>
</footer>
<nav class="bottom-nav">
    <a href="/"' . $active('home') . '><span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5"/><path d="M9.5 21v-6h5v6"/></svg></span><span>Trang chủ</span></a>
    <a href="/khu-vuc"' . $active('kv') . '><span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span><span>Khu vực</span></a>
    <a href="/thao-luan"' . $active('dl') . '><span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8 8 0 0 1-11.6 7.1L4 20l1.4-5.4A8 8 0 1 1 21 11.5z"/></svg></span><span>Diễn đàn</span></a>
    <a href="/chat-sex"' . $active('cs') . '><span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 10h8M8 14h5"/><path d="M21 11.5a8.5 8.5 0 0 1-12.3 7.6L3 20.5l1.4-5.2A8.5 8.5 0 1 1 21 11.5z"/></svg></span><span>Chat Sex</span></a>
    <a href="/login"><span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span><span>Đăng nhập</span></a>
</nav>
<script>
function gamiToggle(e){ e.stopPropagation(); var m=document.getElementById("gamiMenu"); if(m) m.classList.toggle("open"); }
document.addEventListener("click", function(ev){ var w=document.getElementById("gamiWrap"); if(w && !w.contains(ev.target)){ var m=document.getElementById("gamiMenu"); if(m) m.classList.remove("open"); } });
function fhSearchToggle(){ var b=document.getElementById("fhSearchBar"); if(!b) return; var show=b.style.display==="none"; b.style.display=show?"block":"none"; if(show){ var i=document.getElementById("fhSearchInput"); if(i) i.focus(); } }
</script>
</body>
</html>';
    }
}
