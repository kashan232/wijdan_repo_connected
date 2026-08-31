<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sr = DB::table('sales_returns')->latest()->first();
echo "RETURN: \n";
echo json_encode($sr, JSON_PRETTY_PRINT);
echo "\nSALE: \n";
echo json_encode(DB::table('sales')->where('id', $sr->sale_id)->first(), JSON_PRETTY_PRINT);
echo "\n";
