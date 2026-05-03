<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

$pid = 3768;
$start = '2026-04-01';
$end = '2026-04-30';

$total = DB::table('inward_gatepass_items')
    ->join('inward_gatepasses', 'inward_gatepasses.id', '=', 'inward_gatepass_items.inward_gatepass_id')
    ->where('inward_gatepass_items.product_id', $pid)
    ->whereBetween('inward_gatepasses.gatepass_date', [$start, $end])
    ->sum('qty');

echo "Total Inward for Product $pid in April: $total\n";

$all = DB::table('inward_gatepass_items')
    ->join('inward_gatepasses', 'inward_gatepasses.id', '=', 'inward_gatepass_items.inward_gatepass_id')
    ->where('inward_gatepass_items.product_id', $pid)
    ->select('inward_gatepasses.id', 'inward_gatepasses.gatepass_date', 'inward_gatepass_items.qty')
    ->get();

echo "All Inwards for Product $pid:\n";
foreach($all as $row) {
    echo "ID: {$row->id}, Date: {$row->gatepass_date}, Qty: {$row->qty}\n";
}
