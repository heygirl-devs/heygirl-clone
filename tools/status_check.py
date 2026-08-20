import re
for f in ['home.html', 'near.html', 'detail.html', 'prov.html']:
    try:
        h = open('/tmp/gaigu/' + f, encoding='utf-8', errors='replace').read()
    except Exception:
        continue
    for pat in ['tạm nghỉ', 'Tạm nghỉ', 'tam nghi', 'khoá', 'khóa', 'Khoá', 'banned', 'ẩn', 'Ẩn', 'ngừng', 'Ngừng', 'hết hạn', 'Hết hạn']:
        m = list(re.finditer(pat, h))[:1]
        if m:
            i = m[0].start()
            ctx = re.sub(r'\s+', ' ', h[max(0, i - 90):i + 90])
            print(f"[{f}] '{pat}': {ctx[:200]}")
