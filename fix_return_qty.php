<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "Modifying purchase_return_items...\n";
try {
    // using raw SQL to be 100% sure for MariaDB/MySQL
    DB::statement("ALTER TABLE purchase_return_items MODIFY COLUMN qty DECIMAL(12, 2) NOT NULL DEFAULT 0");
    echo "Purchase return items modified.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
