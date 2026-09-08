<?php
$req = new \Illuminate\Http\Request(['start_date'=>'2000-01-01', 'end_date'=>'2099-12-31', 'vendor_id'=>3]);
$res = app(\App\Http\Controllers\ReportingController::class)->fetch_vendor_ledger($req);
print_r($res->getData(true));
