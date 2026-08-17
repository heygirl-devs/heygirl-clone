# heygirl (clone từ gaigu)

Bản dựng lại (clone) MVP của site gaigu — **đã đổi tên thương hiệu thành "heygirl"**: **PHP + SQLite**, giao diện dùng CSS gốc của site (đã trích xuất), dữ liệu crawl từ site gốc.

## Trạng thái & phạm vi

- ✅ Trang chủ (lưới sản phẩm, phân trang)
- ✅ Lọc theo tỉnh/thành `/gai-goi/{tinh}` (+ quận `/gai-goi/{tinh}/{quan}`)
- ✅ **Tìm quanh đây** — nút định vị → xếp theo khoảng cách thực tế (badge "487 m" / "1.6 km" trên mỗi card, giữ nguyên bộ lọc + phân trang)
- ✅ Chi tiết gái `/gai-goi/{id}/{slug}` (album ảnh, mô tả, bảng thuộc tính: SĐT, giá, địa chỉ, năm sinh…)
- ✅ Tìm kiếm `/tim-kiem?q=...` (theo tên / nghệ danh / SĐT)
- ⏳ Ngoài MVP (đang là trang placeholder): chat-sex, đổi sao, diễn đàn, đăng ký/login, bình luận, admin

## Chạy

```bash
# dev server (cổng 8080 — đã giải phóng khỏi container heygirl-nginx-1)
php -S 127.0.0.1:8080 public/index.php

# kiểm tra trạng thái DB / toạ độ
php tools/check.php
php tools/locations.php --status
# xem chi tiết 1 hồ sơ
php tools/show.php 4953
```

Truy cập: <http://127.0.0.1:8080>

## Tìm quanh đây (cách hoạt động)

- Nút "Tìm quanh đây" (cạnh ô chọn tỉnh) dùng `navigator.geolocation`, gắn `user_lat`/`user_lng` vào URL rồi reload — giữ nguyên bộ lọc hiện tại.
- Server tính khoảng cách **Haversine** từ vị trí bạn tới toạ độ **quận/huyện** của từng hồ sơ (fallback centroid tỉnh), sắp xếp gần → xa.
- Bảng toạ độ: `data/gaigu.sqlite` bảng `locations` (63 centroid tỉnh tĩnh + 359 quận/huyện geocode qua Nominatim).
- Test nhanh: `/?user_lat=10.8230989&user_lng=106.6296643`

## Crawl dữ liệu

```bash
php tools/crawl.php --sitemap    # lấy sitemap.xml -> thêm id/slug còn thiếu
php tools/crawl.php --lists      # trang chủ, đi hết phân trang (~141 trang)
php tools/crawl.php --details    # từng trang chi tiết (SĐT, địa chỉ, mô tả, ảnh gallery)
php tools/crawl.php --images     # tải ảnh về public/uploads/...
php tools/crawl.php --all        # chạy cả 4 phase
# tuỳ chọn: --delay 0.25  --limit-pages 10  --limit-details 100  --limit-images 500
```

Crawl **resumable**: trạng thái lưu tại `data/crawl_state.json`; ảnh đã có thì bỏ qua; detail chỉ chạy cho hồ sơ chưa có `crawled_at`.

## Số liệu hiện tại (lần crawl gần nhất)

- **5.067 hồ sơ** (100% đã crawl chi tiết: SĐT, địa chỉ, mô tả, bảng thuộc tính, gallery)
- **65.186 file ảnh** (~1.2 GB) tại `public/uploads/products/public/`
- Phân bố khớp site gốc: Sài Gòn 2.255, Bình Dương 490, Hà Nội 469, Đồng Nai 257, Đà Nẵng 215…
- Toạ độ: 63 centroid tỉnh + **359/359 quận/huyện** geocode thành công
- 11 file ảnh không tải được (đã bị xoá khỏi site gốc) — bỏ qua
- Lưu ý: server gốc throttle tải hàng loạt → crawler đã có backoff thích ứng; nếu chạy lại, chạy `--images` 2 lần để bù file rớt

Ghi chú kỹ thuật từ site gốc:

- Site gốc là **PHP render server-side** (template Blade/Laravel), không phải SPA → không có source map. Giao diện dựng lại từ HTML/CSS quan sát được.
- Phân trang trang chủ của site gốc **không ổn định** (xáo theo hoạt động, các trang chồng lấn) → dùng `sitemap.xml` (2.876 URL ổn định) làm danh sách chính + đi hết phân trang trang chủ để bù phần còn lại.
- robots.txt của site gốc: `Allow: /` với `Content-Signal: search=yes, ai-train=no, use=reference` (Cloudflare managed) — nếu bạn không phải chủ site, cần đảm bảo quyền sử dụng nội dung trước khi phát hành bản clone.

## Cấu trúc

```
public/            # front controller + assets (css/js/fonts/images/uploads — CSS gốc trích xuất)
app/               # bootstrap (haversine, toạ độ, card), db (PDO SQLite), views
tools/             # crawl.php, locations.php, check.php, show.php, districts.php, slugcheck.php
data/              # gaigu.sqlite + sitemap.xml + crawl_state.json (không commit)
```

## Nâng cấp chất lượng (bước tiếp theo)

- Export **HAR** từ site gốc (F12 → Network → login, gửi đánh giá, chat-sex, đổi sao…) để tái dựng đúng hợp đồng API `/api-internal-v2` — hiện tại route này trả 403 nếu không có context/session.
- Tài khoản test nếu các luồng trên cần đăng nhập.
- Muốn đổi cổng chạy: dừng `php -S` cũ rồi `php -S 127.0.0.1:8080 public/index.php` (nếu 8080 bận do heygirl-nginx-1: `docker start heygirl-nginx-1` để chạy lại, hoặc `docker stop` để trả cổng).
