<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    if (!Schema::hasIndex('purchase_items', 'idx_pi_product_id')) {
        DB::statement('CREATE INDEX idx_pi_product_id ON purchase_items(product_id)');
    }
    if (!Schema::hasIndex('inward_gatepass_items', 'idx_igi_product_id')) {
        DB::statement('CREATE INDEX idx_igi_product_id ON inward_gatepass_items(product_id)');
    }
    if (!Schema::hasIndex('stocks', 'idx_stocks_product_id')) {
        DB::statement('CREATE INDEX idx_stocks_product_id ON stocks(product_id)');
    }
    if (!Schema::hasIndex('warehouse_stocks', 'idx_ws_product_id')) {
        DB::statement('CREATE INDEX idx_ws_product_id ON warehouse_stocks(product_id)');
    }
    echo "Indexes added successfully!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
