<?php
declare(strict_types=1);
/** Test parse_detail trên file HTML mẫu — chạy: php tools/test_parse.php <file.html> */
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db.php';

// nạp lại hàm parse_detail từ crawl.php bằng cách include một phần
$src = file_get_contents(__DIR__ . '/crawl.php');
$start = strpos($src, 'function parse_detail');
$end = strpos($src, 'function phase_details');
eval(substr($src, $start, $end - $start));

$file = $argv[1] ?? '/tmp/gaigu/detail.html';
$html = (string)file_get_contents($file);
$d = parse_detail($html, 4953);
echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
