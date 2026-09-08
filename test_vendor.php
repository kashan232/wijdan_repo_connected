<?php
$vendors = DB::table('vendors')->pluck('id');
foreach($vendors as $v) {
    $req = new \Illuminate\Http\Request();
    $req->replace(['start_date' => '2000-01-01', 'end_date' => '2099-12-31', 'vendor_id' => $v]);
    $res = app(\App\Http\Controllers\ReportingController::class)->fetch_vendor_ledger($req);
    $data = $res->getData(true);
    $ledgerClosing = DB::table('vendor_ledgers')->where('vendor_id', $v)->value('closing_balance');
    $calcClosing = 0;
    if (isset($data['transactions']) && count($data['transactions']) > 0) {
        $lastT = end($data['transactions']);
        $calcClosing = $lastT['balance'];
    } else {
        $calcClosing = $data['opening_balance'] ?? 0;
    }
    if (round((float)$ledgerClosing, 2) != round((float)$calcClosing, 2)) {
        echo "Vendor $v: Ledger=$ledgerClosing, Calc=$calcClosing\n";
    }
}
