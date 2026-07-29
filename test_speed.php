<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$start = microtime(true);
$controller = app(\App\Http\Controllers\WarehouseStockController::class);
$request = \Illuminate\Http\Request::create('/warehouse_stocks', 'GET');
$request->setUserResolver(function() { return \App\Models\User::find(1); }); // Admin
$reflection = new ReflectionMethod($controller, 'getAdjustedStocks');
$reflection->setAccessible(true);
$stocks = $reflection->invoke($controller, $request);
$time = microtime(true) - $start;

echo "Loaded " . count($stocks) . " products in " . round($time, 2) . " seconds.\n";
