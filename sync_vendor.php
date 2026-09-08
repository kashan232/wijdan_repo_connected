<?php
$vendors = DB::table('vendors')->pluck('id');
foreach($vendors as $v) {
    $req = new \Illuminate\Http\Request(['start_date'=>'2000-01-01', 'end_date'=>'2099-12-31', 'vendor_id'=>$v]);
    $res = app(\App\Http\Controllers\ReportingController::class)->fetch_vendor_ledger($req);
    $data = $res->getData(true);
    $calcClosing = 0;
    if (isset($data['transactions']) && count($data['transactions']) > 0) {
        $lastT = end($data['transactions']);
        $calcClosing = $lastT['balance'];
    } else {
        $calcClosing = $data['opening_balance'] ?? 0;
    }
    DB::table('vendor_ledgers')->updateOrInsert(['vendor_id' => $v], ['closing_balance' => $calcClosing]);
}
echo "Vendor Ledgers synced successfully!\n";
