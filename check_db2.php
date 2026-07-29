<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = \App\Models\Product::where('item_name', 'like', '%Zarif Bayaan 03%')
    ->orWhere('item_code', '908211')
    ->orWhere('item_code', 'like', '%908211%')
    ->first();
if ($product) {
    echo "Product found: {$product->id}, Barcode: {$product->barcode_path}, ItemCode: {$product->item_code}\n";
    $shop = \Illuminate\Support\Facades\DB::table('stocks')->where('product_id', $product->id)->first();
    $warehouse = \Illuminate\Support\Facades\DB::table('warehouse_stocks')->where('product_id', $product->id)->get();
    echo "Shop: " . json_encode($shop) . "\n";
    echo "Warehouse: " . json_encode($warehouse) . "\n";
} else {
    echo "Product not found.\n";
}
