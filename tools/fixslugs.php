<?php
require __DIR__ . '/../app/bootstrap.php';
init_schema();
$db = db();
$n = $db->exec("UPDATE products SET province_slug = 'n-giang', province = 'An Giang' WHERE province_slug = 'an-giang'");
$n2 = $db->exec("UPDATE products SET province_slug = 'lai', province = 'Gia Lai' WHERE province_slug = 'gia-lai'");
echo "fixed an-giang: $n, gia-lai: $n2\n";
