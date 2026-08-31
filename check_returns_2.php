<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sales = DB::table('sales')->whereExists(function($q) { 
    $q->select(DB::raw(1))->from('sales_returns')->whereRaw('sales_returns.sale_id = sales.id'); 
})->get();

foreach($sales as $s) {
    echo "Sale ID: " . $s->id . " Net: " . $s->total_net . "\n";
}
