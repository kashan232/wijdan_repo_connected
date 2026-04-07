<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesReturn;
use Illuminate\Http\Request;

use App\Models\CustomerLedger;
use App\Models\ProductBooking;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            
            // 🔹 Base Query
            $query = Sale::with(['customer_relation', 'user']);

            // 🔹 Restrict non-admin users to their own sales
            // Exempt User ID 1 (Super Admin) to ensure they can see/filter everything
            if (auth()->id() !== 1 && !auth()->user()->hasRole('Admin')) {
                 $query->where('user_id', auth()->id());
            }

            // 🔹 Apply Filters
            if ($request->filled('from_date')) {
                $query->where('created_at', '>=', \Carbon\Carbon::parse($request->from_date)->startOfDay());
            }
            if ($request->filled('to_date')) {
                $query->where('created_at', '<=', \Carbon\Carbon::parse($request->to_date)->endOfDay());
            }
            if ($request->filled('filter_user')) {
                 $query->where('user_id', $request->filter_user);
            }

            // 🔹 Search Filter
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%")
                      ->orWhere('total_bill_amount', 'like', "%{$search}%") // Search Amount
                      ->orWhere('product_code', 'like', "%{$search}%")      // Search Barcode/Item Code
                      ->orWhereHas('customer_relation', function($c) use ($search) {
                          $c->where('customer_name', 'like', "%{$search}%");
                      });

                    // Search by Product Name (indirectly via IDs)
                    // 1. Find IDs of products matching the search name
                    $matchingProductIds = \App\Models\Product::where('item_name', 'like', "%{$search}%")
                        ->orWhere('barcode_path', 'like', "%{$search}%") // Extra safety
                        ->pluck('id')
                        ->toArray();
                    
                    if (!empty($matchingProductIds)) {
                        foreach ($matchingProductIds as $pid) {
                            // Search for this ID in the comma-separated product column
                            // We use precise matching to avoid finding '1' in '10', '21', etc.
                            // Patterns: "1", "1,2", "2,1", "3,1,4"
                            $q->orWhereRaw("FIND_IN_SET(?, product)", [$pid]);
                        }
                    }
                });
            }

            // 🔹 Total Records (before filtering)
            $totalRecords = auth()->user()->hasRole('Admin')
                ? Sale::count()
                : Sale::where('user_id', auth()->id())->count();
            
            // 🔹 Filtered Records
            $filteredRecords = $query->count();
            $totalFilteredSale = $query->sum('total_bill_amount');

            // 🔹 Sorting
            if ($request->has('order')) {
                $orderColumnIndex = $request->order[0]['column'];
                $orderDir = $request->order[0]['dir'];
                
                // Map column index to database column (adjust as needed)
                $columns = [
                    1 => 'invoice_no',
                    2 => 'customer',
                    3 => 'reference',
                    10 => 'total_bill_amount',
                    11 => 'created_at',
                    12 => 'sale_status'
                ]; 
                
                $colName = $columns[$orderColumnIndex] ?? 'id';
                
                // Fix: Invoice No (col 1) string sorting issue (999 > 1000). 
                // Use ID instead for correct numeric order.
                if ($colName === 'invoice_no') {
                     $query->orderBy('id', $orderDir);
                } elseif($colName == 'customer') {
                     // Sorting by relation is complex, fallback to id
                     $query->orderBy('id', $orderDir);
                } else {
                     $query->orderBy($colName, $orderDir);
                }
            } else {
                $query->orderBy('id', 'desc');
            }

            // 🔹 Pagination
            $skip = $request->start ?? 0;
            $take = $request->length ?? 10;
            
            $sales = $query->skip($skip)->take($take)->get();

            // 🔹 Optimized Product Fetching (Avoid N+1)
            // Extract all unique product IDs from the current page sales
            $allProductIds = [];
            foreach ($sales as $sale) {
                if(!empty($sale->product)) {
                    $ids = explode(',', $sale->product);
                    foreach($ids as $id) $allProductIds[] = trim($id);
                }
            }
            $allProductIds = array_unique($allProductIds);
            
            // Fetch all needed products in one query
            $productsMap = \App\Models\Product::whereIn('id', $allProductIds)->get()->keyBy('id');

            // 🔹 Transform Data
            $data = [];
            foreach ($sales as $index => $sale) {
                
                // Parse CSV columns
                $prodIds = explode(',', $sale->product);
                $qtys = explode(',', $sale->qty);
                $prices = explode(',', $sale->per_price);
                $discounts = explode(',', $sale->per_discount);
                $totals = explode(',', $sale->per_total);
                
                // Build HTML for products column
                $barcodeHtml = '';
                $productHtml = '';
                
                foreach($prodIds as $pid) {
                    $p = $productsMap[$pid] ?? null;
                    $barcodeHtml .= ($p ? $p->barcode_path : 'N/A') . '<br>';
                    $productHtml .= ($p ? $p->item_name : 'N/A') . '<br>';
                }

                // Helper closure for formatting
                $fmt = function($val) {
                    $val = (float)$val;
                    return ($val == (int)$val) ? number_format($val, 0) : number_format($val, 2);
                };

                // Build HTML for other list columns
                $cleanedQtys = array_map(function($q) use ($fmt) { return $fmt($q); }, $qtys);
                $qtyHtml = implode('<br>', $cleanedQtys);

                $priceHtml = '';
                foreach($prices as $pr) $priceHtml .= $fmt($pr) . '<br>';
                
                $discHtml = '';
                foreach($discounts as $d) $discHtml .= $fmt($d) . '<br>';

                $totalHtml = '';
                foreach($totals as $t) $totalHtml .= $fmt($t) . '<br>';

                // Status Badge
                $statusBadge = '<span class="badge bg-secondary">Unknown</span>';
                if($sale->sale_status === null) 
                    $statusBadge = '<span class="badge bg-success">Sale</span>';
                elseif($sale->sale_status == 1) 
                    $statusBadge = '<span class="badge bg-danger">Return</span>';

                if($sale->card > 0) {
                    $statusBadge .= ' <span class="badge bg-info">Card</span>';
                }

                // Action Buttons
                $actions = '<div class="btn-group" role="group">
                    <a href="'.route('sales.return.create', $sale->id).'" class="btn btn-sm btn-warning">Return</a>
                    <a href="'.route('sales.edit', $sale->id).'" class="btn btn-sm btn-primary">Edit</a>
                    <a href="'.route('sales.invoice', $sale->id).'" class="btn btn-sm btn-info text-white">Invoice</a>
                    <a href="'.route('sales.dc', $sale->id).'" class="btn btn-sm btn-success text-white">DC</a>
                </div>';
                
                // Date formatting
                $date = \Carbon\Carbon::parse($sale->created_at)->format('d-m-Y h:i A');

                $data[] = [
                    $skip + $index + 1, // S.No
                    $sale->user ? $sale->user->name : 'N/A', // User Name
                    $sale->invoice_no,
                    $sale->customer_relation->customer_name ?? 'Walk-in Customer',
                    $sale->reference,
                    $barcodeHtml,
                    $productHtml,
                    $qtyHtml,
                    $priceHtml,
                    $discHtml,
                    $totalHtml,
                    '<span class="fw-bold fs-5">' . $fmt($sale->total_bill_amount) . '</span>', // Bold Total Amount
                    $date,
                    $statusBadge,
                    $actions
                ];
            }

            return response()->json([
                "draw" => intval($request->draw),
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $filteredRecords,
                "data" => $data,
                "totalFilteredSale" => $totalFilteredSale
            ]);
        }
        $openingBalance = \App\Models\UserOpeningBalance::where('user_id', auth()->id())
            ->where('date', '<=', date('Y-m-d'))
            ->orderBy('date', 'desc')
            ->value('amount') ?? 0;

        // Calculate today's sales for the logged-in user
        $todaySales = Sale::where('user_id', auth()->id())
            ->whereDate('created_at', date('Y-m-d'))
            ->sum('total_bill_amount');

        $netCash = $openingBalance + $todaySales;

        return view('admin_panel.sale.index', compact('openingBalance', 'todaySales', 'netCash'));
    }

    public function addsale()
    {
        $Customer = Customer::get();
        return view('admin_panel.sale.add_sale', compact('Customer'));
    }

    public function getAllProductsForSearch()
    {
        $products = Product::with(['brand', 'activeDiscount'])
            ->get()
            ->map(function ($product) {
                $price = (float) $product->price;
                if ($product->activeDiscount) {
                    $price = (float) $product->activeDiscount->final_price;
                }

                return [
                    'id'               => $product->id,
                    'item_name'        => $product->item_name,
                    'item_code'        => $product->item_code,
                    'barcode'          => $product->barcode_path, // Included for client-side barcode lookup
                    'brand'            => $product->brand?->name,
                    'unit_id'          => $product->unit_id,
                    'note'             => $product->note,
                    'wholesale_price'  => $product->wholesale_price,
                    'price'            => $price,
                    'original_price'   => $product->price,
                    'discount_percent' => $product->activeDiscount?->discount_percentage ?? 0,
                    'discount_amount'  => $product->activeDiscount?->total_discount ?? 0,
                    'has_discount'     => $product->activeDiscount ? true : false,
                ];
            });

        return response()->json($products);
    }

    public function searchpname(Request $request)
    {
        $q = trim($request->q);

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        // 🔹 Step 1: Brand search
        $brandIds = \App\Models\Brand::where('name', 'like', "%{$q}%")
            ->pluck('id')
            ->toArray();

        // 🔹 Step 2: Product search (item + brand based)
        $products = Product::with(['brand', 'activeDiscount'])
            ->where(function ($query) use ($q, $brandIds) {

                // Normal product search
                $query->where('item_code', 'like', "{$q}%")
                    ->orWhere('barcode_path', 'like', "{$q}%")
                    ->orWhere('item_name', 'like', "%{$q}%");

                // 🔥 Brand based product search
                if (!empty($brandIds)) {
                    $query->orWhereIn('brand_id', $brandIds);
                }
            })
            ->get()
            ->map(function ($product) {

                // 🔹 Price handling
                $price = (float) $product->price;

                if ($product->activeDiscount) {
                    $price = (float) $product->activeDiscount->final_price;
                }

                return [
                    'id'               => $product->id,
                    'item_name'        => $product->item_name,
                    'item_code'        => $product->item_code,
                    'brand'            => $product->brand?->name,
                    'unit_id'          => $product->unit_id,
                    'note'              => $product->note,
                    'wholesale_price'              => $product->wholesale_price,
                    'price'            => $price,
                    'original_price'   => $product->price,
                    'discount_percent' => $product->activeDiscount?->discount_percentage ?? 0,
                    'discount_amount'  => $product->activeDiscount?->total_discount ?? 0,
                    'has_discount'     => $product->activeDiscount ? true : false,
                ];
            });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $action = $request->input('action'); // 'booking' or 'sale'
        $booking_id = $request->booking_id; // <-- existing booking ID if editing

        // --- Basic validation: require customer, reference, and at least one valid product row ---
        $validator = \Validator::make($request->all(), [
            'customer' => 'required',
            // We'll validate products manually below (because arrays mixed)
        ]);

        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        // Validate there's at least one filled product row
        $product_ids = is_array($request->product_id) ? $request->product_id : [];
        $qtys = is_array($request->qty) ? $request->qty : [];
        $prices = is_array($request->price) ? $request->price : [];

        $hasRow = false;
        foreach ($product_ids as $i => $pid) {
            $q = isset($qtys[$i]) ? floatval($qtys[$i]) : 0;
            $p = isset($prices[$i]) ? floatval($prices[$i]) : 0;
            if (!empty($pid) && $q > 0 && $p > 0) {
                $hasRow = true;
                break;
            }
        }

        if (! $hasRow) {
            return back()->withInput()->with('error', 'Please add at least one product with quantity and price.');
        }

        DB::beginTransaction();

        try {
            // --- Input arrays (safely handle missing keys) ---
            $product_ids     = is_array($request->product_id) ? $request->product_id : [];
            $product_names   = is_array($request->product_name) ? $request->product_name : [];
            $product_codes   = is_array($request->item_code) ? $request->item_code : [];
            $brands          = is_array($request->uom) ? $request->uom : [];
            $units           = is_array($request->unit) ? $request->unit : [];
            $prices          = is_array($request->price) ? $request->price : [];
            $discounts       = is_array($request->item_disc) ? $request->item_disc : [];
            $quantities      = is_array($request->qty) ? $request->qty : [];
            $totals          = is_array($request->total) ? $request->total : [];
            $colors          = is_array($request->color) ? $request->color : [];

            // Arrays to be saved
            $combined_product_ids   = [];
            $combined_product_names = [];
            $combined_codes         = [];
            $combined_brands        = [];
            $combined_units         = [];
            $combined_prices        = [];
            $combined_discounts     = [];
            $combined_qtys          = [];
            $combined_totals        = [];
            $combined_colors        = [];

            $total_items = 0;

            // Pre-fetch all necessary models to avoid N+1 queries inside the loop
            $unique_product_ids = array_unique(array_filter($product_ids));
            
            // Fetch Products keyed by ID
            $productsMap = \App\Models\Product::whereIn('id', $unique_product_ids)->get()->keyBy('id');
            
            // Fetch Stocks keyed by product_id
            // Using logic from loop: we need stock for each product
            $stocksMap = \App\Models\Stock::whereIn('product_id', $unique_product_ids)->get()->keyBy('product_id');

            foreach ($product_ids as $index => $product_id) {
                $qty   = isset($quantities[$index]) ? $quantities[$index] : 0;
                $price = isset($prices[$index]) ? $prices[$index] : 0;

                // skip incomplete rows
                if (empty($product_id) || $qty <= 0 || $price <= 0) {
                    continue;
                }

                $combined_product_ids[] = $product_id;

                $pname = $product_names[$index] ?? null;
                if (empty($pname)) {
                    // Use pre-fetched product
                    $prodModel = $productsMap[$product_id] ?? null;
                    $pname = $prodModel ? $prodModel->item_name : '';
                }
                $combined_product_names[] = $pname;

                $combined_codes[]      = $product_codes[$index] ?? '';
                $combined_brands[]     = $brands[$index] ?? '';
                $combined_units[]      = $units[$index] ?? '';
                $combined_prices[]     = $prices[$index] ?? 0;
                $combined_discounts[]  = $discounts[$index] ?? 0;
                $combined_qtys[]       = $quantities[$index] ?? 0;
                $combined_totals[]     = $totals[$index] ?? 0;
                $combined_colors[]     = json_encode($colors[$index] ?? []);

                // Only Sale updates stock
                if ($action === 'sale') {
                    $stock = $stocksMap[$product_id] ?? null;
                    if ($stock) {
                        $stock->qty = $stock->qty - $qty;
                        // $stock->save(); // Optimized: Batch save outside loop
                    } else {
                        $newStock = \App\Models\Stock::create([
                            'product_id' => $product_id,
                            'qty'        => -$qty,
                        ]);
                        $stocksMap[$product_id] = $newStock;
                    }
                }

                $total_items += $qty;
            }

            // --- Choose model ---
            if ($action === 'booking') {
                $model = $booking_id ? \App\Models\ProductBooking::findOrFail($booking_id) : new \App\Models\ProductBooking();
            } else {
                $model = new \App\Models\Sale();
                $model->invoice_no = \App\Models\Sale::generateInvoiceNo();
            }

            // --- Fill common fields ---
            $model->customer             = $request->customer;
            $model->reference            = $request->reference;
            $model->product              = implode(',', $combined_product_ids);
            $model->product_code         = implode(',', $combined_codes);
            $model->brand                = implode(',', $combined_brands);
            $model->unit                 = implode(',', $combined_units);
            $model->per_price            = implode(',', $combined_prices);
            $model->per_discount         = implode(',', $combined_discounts);
            $model->qty                  = implode(',', $combined_qtys);
            $model->per_total            = implode(',', $combined_totals);
            $model->color                = json_encode($combined_colors);
            $model->total_amount_Words   = $request->total_amount_Words;
            $model->total_bill_amount    = $request->total_subtotal;
            $model->total_extradiscount  = $request->total_extra_cost;
            $model->total_net            = $request->total_net;
            $model->cash                 = $request->cash;
            $model->card                 = $request->card;
            $model->change               = $request->change;
            $model->total_items          = $total_items;
            $model->total_pieces          = $request->total_pieces;
            $model->total_meter          = $request->total_meter;

            if ($model instanceof \App\Models\Sale) {
                $model->user_id = auth()->id(); // Save User ID only for Sales
            }

            // Booking-specific field
            if ($action === 'booking') {
                $model->advance_payment = isset($request->advance_payment) ? floatval($request->advance_payment) : 0;
                if (empty($model->booking_date)) {
                    $model->booking_date = now();
                }
            }

            $model->save();

            // If this request is confirming a booking (we came from bookings -> Confirm)
            // and action is 'sale' and booking_id present, mark the original booking as sold.
            if ($action === 'sale' && !empty($booking_id)) {
                $booking = \App\Models\ProductBooking::find($booking_id);
                if ($booking) {
                    $booking->sale_date = now();
                    // keep any previously stored advance_payment but allow overriding from request.cash or request.advance_payment
                    if ($request->has('advance_payment')) {
                        $booking->advance_payment = floatval($request->advance_payment);
                    } elseif ($request->has('cash') && floatval($request->cash) > 0) {
                        // if user put cash in confirm form and booking had advance, you may want to add or replace.
                        // here we set booking cash to the cash given at confirm (simple approach)
                        $booking->advance_payment = floatval($request->cash);
                    }
                    $booking->save();
                }
            }

            // ledger update for sale
            if ($action === 'sale') {
                $customer_id = $request->customer;
                if ($customer_id !== 'Walk-in Customer') {
                    $ledger = \App\Models\CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
                    if ($ledger) {
                        $ledger->previous_balance = $ledger->closing_balance;
                        $ledger->closing_balance += $request->total_net;
                        $ledger->save();
                    } else {
                        \App\Models\CustomerLedger::create([
                            'customer_id'      => $customer_id,
                            'admin_or_user_id' => auth()->id(),
                            'previous_balance' => 0,
                            'closing_balance'  => $request->total_net,
                            'opening_balance'  => $request->total_net,
                        ]);
                    }
                }
            }

            // 🚀 Batch save only dirty stocks for maximum speed
            foreach ($stocksMap as $s) {
                if ($s->isDirty()) {
                    $s->save();
                }
            }

            DB::commit();

            if ($action === 'sale') {
                $returnTo = route('sale.add');
                $invoiceUrl = route('sales.invoice', $model->id) . '?return_to=' . urlencode($returnTo) . '&autoprint=1';
                
                if ($request->ajax()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Sale completed.',
                        'invoice_url' => $invoiceUrl
                    ]);
                }
                
                return redirect()->to($invoiceUrl)->with('success', 'Sale completed.');
            }

            if ($action === 'booking') {
                $returnTo = route('sale.add');
                $receiptUrl = route('booking.receipt', $model->id)
                    . '?return_to=' . urlencode($returnTo)
                    . '&autoprint=1';

                if ($request->ajax()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Booking created successfully.',
                        'receipt_url' => $receiptUrl
                    ]);
                }

                return redirect()->to($receiptUrl)->with('success', 'Booking created successfully.');
            }

            if ($request->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Saved.']);
            }

            return back()->with('success', 'Saved.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }




    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */


    public function convertFromBooking($id)
    {
        $booking = ProductBooking::findOrFail($id);
        $customers = Customer::all();

        // Decode fields
        $products     = explode(',', $booking->product);
        $codes        = explode(',', $booking->product_code);
        $brands       = explode(',', $booking->brand);
        $units        = explode(',', $booking->unit);
        $prices       = explode(',', $booking->per_price);
        $discounts    = explode(',', $booking->per_discount);
        $qtys         = explode(',', $booking->qty);
        $totals       = explode(',', $booking->per_total);

        // Colors: double JSON decode fix
        $colors_json  = json_decode($booking->color, true); // this gives array of encoded strings

        $items = [];

        foreach ($products as $index => $p) {
            // Get product details
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            // Fix color decoding
            $rawColor = $colors_json[$index] ?? null;
            $availableColors = [];

            if (is_string($rawColor)) {
                $decoded = json_decode($rawColor, true);

                if (is_array($decoded)) {
                    $availableColors = $decoded;
                } elseif (!is_null($decoded)) {
                    $availableColors = [$decoded];
                }
            } elseif (is_array($rawColor)) {
                $availableColors = $rawColor;
            }

            $items[] = [
                'product_id'        => $product->id ?? '',
                'item_name'         => $product->item_name ?? $p,
                'item_code'         => $product->item_code ?? ($codes[$index] ?? ''),
                'uom'               => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'              => $product->unit_id ?? ($units[$index] ?? ''),
                'price'             => floatval($prices[$index] ?? 0),
                'discount'          => floatval($discounts[$index] ?? 0),
                'qty'               => intval($qtys[$index] ?? 1),
                'total'             => floatval($totals[$index] ?? 0),
                'available_colors'  => $availableColors,                  // 👈 list of all dropdown options
                'color'             => $availableColors[0] ?? null,       // 👈 selected option
            ];
        }

        return view('admin_panel.sale.booking_edit', [
            'Customer' => $customers,
            'booking' => $booking,
            'bookingItems' => $items,
        ]);
    }


    // sale return start
    public function saleretun($id)
    {
        $sale = \App\Models\Sale::findOrFail($id);
        $customers = \App\Models\Customer::all();

        // Split comma-based fields from the sale row
        $products  = array_map('trim', explode(',', $sale->product ?? ''));
        $codes     = array_map('trim', explode(',', $sale->product_code ?? ''));
        $brands    = array_map('trim', explode(',', $sale->brand ?? ''));
        $units     = array_map('trim', explode(',', $sale->unit ?? ''));
        $prices    = array_map('trim', explode(',', $sale->per_price ?? ''));
        $discounts = array_map('trim', explode(',', $sale->per_discount ?? ''));
        $qtys      = array_map('trim', explode(',', $sale->qty ?? ''));
        $totals    = array_map('trim', explode(',', $sale->per_total ?? ''));

        // decode color JSON array (if stored like ["[]","[]"])
        $colors_json = json_decode($sale->color ?? '[]', true);
        if (!is_array($colors_json)) {
            $colors_json = [];
        }

        // Fetch all previous returns for this sale from sales_returns table
        $previousReturns = \DB::table('sales_returns')->where('sale_id', $sale->id)->get();

        // Build an aggregated map: returnedQtyByProductIdOrCode[productId_or_code] = totalReturnedQty
        $returnedQtyMap = [];

        foreach ($previousReturns as $ret) {
            // ret->product and ret->qty are comma separated strings, same shape as sale
            $retProducts = array_map('trim', explode(',', $ret->product ?? ''));
            $retQtys     = array_map('trim', explode(',', $ret->qty ?? ''));

            // loop indices and accumulate
            foreach ($retProducts as $ri => $rprod) {
                $rqty = isset($retQtys[$ri]) ? floatval($retQtys[$ri]) : 0;
                if ($rqty <= 0) continue;

                // try to treat rprod as product id (numeric) else treat as code
                $keyId = null;
                if (is_numeric($rprod)) {
                    $keyId = 'id_' . intval($rprod);
                    if (!isset($returnedQtyMap[$keyId])) $returnedQtyMap[$keyId] = 0;
                    $returnedQtyMap[$keyId] += $rqty;
                } else {
                    // store by code string
                    $keyCode = 'code_' . $rprod;
                    if (!isset($returnedQtyMap[$keyCode])) $returnedQtyMap[$keyCode] = 0;
                    $returnedQtyMap[$keyCode] += $rqty;
                }
            }
        }

        $items = [];

        foreach ($products as $index => $p) {
            // try to find product model by id (if value numeric) or by code fallback
            $product = null;
            $productIdCandidate = null;
            $itemCodeCandidate = $codes[$index] ?? '';

            if (is_numeric($p) && intval($p) > 0) {
                $productIdCandidate = intval($p);
                $product = \App\Models\Product::find($productIdCandidate);
            }

            if (!$product && !empty($itemCodeCandidate)) {
                $product = \App\Models\Product::where('item_code', trim($itemCodeCandidate))->first();
                if ($product) {
                    $productIdCandidate = $product->id;
                }
            }

            // ---------- NOTE parsing (previously color) ----------
            $note_value = '';
            if (isset($colors_json[$index])) {
                $maybe = $colors_json[$index];
                if (is_string($maybe)) {
                    $try = json_decode($maybe, true);
                    if ($try !== null) {
                        if (is_array($try)) {
                            $note_value = implode("\n", $try);
                        } else {
                            $note_value = (string)$try;
                        }
                    } else {
                        $note_value = $maybe;
                    }
                } elseif (is_array($maybe)) {
                    $note_value = implode("\n", $maybe);
                } else {
                    $note_value = (string)$maybe;
                }
            }
            // ---------- end note parsing ----------

            $soldQty = isset($qtys[$index]) && is_numeric($qtys[$index]) ? floatval($qtys[$index]) : 0;

            // compute returned qty using our map:
            $returnedQty = 0;
            if ($productIdCandidate) {
                $k = 'id_' . $productIdCandidate;
                if (isset($returnedQtyMap[$k])) {
                    // take as much as needed from the map to reduce this row's available qty
                    // but we must not consume more than soldQty for this specific row if we want to be safe, 
                    // OR we consume across multiple rows. The map is global for the sale.
                    // Strategy: We deduct from the map as we iterate.
                    $deduct = min($returnedQtyMap[$k], $soldQty);
                    $returnedQty += $deduct;
                    $returnedQtyMap[$k] -= $deduct; 
                }
            }
            if ($returnedQty == 0 && !empty($itemCodeCandidate)) { // fallback to code if id didn't match
                $kc = 'code_' . $itemCodeCandidate;
                if (isset($returnedQtyMap[$kc])) {
                    $deduct = min($returnedQtyMap[$kc], $soldQty);
                    $returnedQty += $deduct;
                    $returnedQtyMap[$kc] -= $deduct;
                }
            }

            $available = max(0, $soldQty - $returnedQty);

            $items[] = [
                'product_id'    => $product->id ?? ($productIdCandidate ?? ''),
                'item_name'     => $product->item_name ?? (string)($p),
                'item_code'     => $product->item_code ?? ($itemCodeCandidate ?? ''),
                'brand'         => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'          => $product->unit ?? ($units[$index] ?? ''),
                'price'         => floatval($prices[$index] ?? 0),
                'discount'      => floatval($discounts[$index] ?? 0),
                'qty'           => $soldQty,
                'total'         => floatval($totals[$index] ?? 0),
                // send note (plain text) so blade can show it
                'note'          => $note_value,
                'available_qty' => $available,
            ];
        }

        return view('admin_panel.sale.return.create', [
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
        ]);
    }



    // public function storeSaleReturn(Request $request)
    // {
    //     // dd($request->all());
    //     DB::beginTransaction();

    //     try {
    //         $product_ids     = $request->product_id;
    //         $product_names   = $request->product;
    //         $product_codes   = $request->item_code;
    //         $brands          = $request->uom;
    //         $units           = $request->unit;
    //         $prices          = $request->price;
    //         $discounts       = $request->item_disc;
    //         $quantities      = $request->qty;
    //         $totals          = $request->total;
    //         $colors          = $request->color;

    //         $combined_products   = [];
    //         $combined_codes      = [];
    //         $combined_brands     = [];
    //         $combined_units      = [];
    //         $combined_prices     = [];
    //         $combined_discounts  = [];
    //         $combined_qtys       = [];
    //         $combined_totals     = [];
    //         $combined_colors     = [];

    //         $total_items = 0;

    //         foreach ($product_ids as $index => $product_id) {
    //             $qty   = $quantities[$index] ?? 0;
    //             $price = $prices[$index] ?? 0;

    //             if (!$product_id || !$qty || !$price) continue;

    //             $combined_products[]   = $product_names[$index] ?? '';
    //             $combined_codes[]      = $product_codes[$index] ?? '';
    //             $combined_brands[]     = $brands[$index] ?? '';
    //             $combined_units[]      = $units[$index] ?? '';
    //             $combined_prices[]     = $price;
    //             $combined_discounts[]  = $discounts[$index] ?? 0;
    //             $combined_qtys[]       = $qty;
    //             $combined_totals[]     = $totals[$index] ?? 0;

    //             // Convert color to valid JSON array
    //             $decodedColor = $colors[$index] ?? [];
    //             if (is_array($decodedColor)) {
    //                 $combined_colors[] = json_encode($decodedColor);
    //             } else {
    //                 $decoded = json_decode($decodedColor, true);
    //                 $combined_colors[] = json_encode(is_array($decoded) ? $decoded : []);
    //             }

    //             // ➕ Restore stock
    //             $stock = \App\Models\Stock::where('product_id', $product_id)->first();
    //             if ($stock) {
    //                 $stock->qty += $qty;
    //                 $stock->save();
    //             }

    //             $total_items += $qty;
    //         }

    //         // ➕ Create Sale Return
    //         $saleReturn = new \App\Models\SalesReturn();
    //         $saleReturn->sale_id              = $request->sale_id;
    //         $saleReturn->customer             = $request->customer;
    //         $saleReturn->reference            = $request->reference;

    //         $saleReturn->product              = implode(',', $combined_products);
    //         $saleReturn->product_code         = implode(',', $combined_codes);
    //         $saleReturn->brand                = implode(',', $combined_brands);
    //         $saleReturn->unit                 = implode(',', $combined_units);
    //         $saleReturn->per_price            = implode(',', $combined_prices);
    //         $saleReturn->per_discount         = implode(',', $combined_discounts);
    //         $saleReturn->qty                  = implode(',', $combined_qtys);
    //         $saleReturn->per_total            = implode(',', $combined_totals);
    //         $saleReturn->color                = json_encode($combined_colors);

    //         $saleReturn->total_amount_Words   = $request->total_amount_Words;
    //         $saleReturn->total_bill_amount    = $request->total_subtotal;
    //         $saleReturn->total_extradiscount  = $request->total_extra_cost;
    //         $saleReturn->total_net            = $request->total_net;

    //         $saleReturn->cash                 = $request->cash;
    //         $saleReturn->card                 = $request->card;
    //         $saleReturn->change               = $request->change;

    //         $saleReturn->total_items          = $total_items;
    //         $saleReturn->return_note          = $request->return_note;

    //         $saleReturn->save();

    //         DB::commit();

    //         return redirect()->route('sale.index')->with('success', 'Sale return saved successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Sale return failed: ' . $e->getMessage());
    //     }
    // }
    public function storeSaleReturn(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            // the arrays may be posted only for selected items, but validate generically
            'product'    => 'required|array',
            'product.*'  => 'nullable|string',
            'item_code'  => 'required|array',
            'item_code.*' => 'nullable|string',
            'unit'       => 'required|array',
            'unit.*'     => 'nullable|string',
            'price'      => 'required|array',
            'price.*'    => 'nullable|numeric',
            'item_disc'  => 'required|array',
            'item_disc.*' => 'nullable|numeric',
            'qty'        => 'required|array',
            'qty.*'      => 'nullable|numeric|min:0',
            'total'      => 'required|array',
            'total.*'    => 'nullable|numeric',
            'color'      => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $saleId = $request->sale_id;
            $sale = \App\Models\Sale::findOrFail($saleId);

            // Incoming arrays (may contain only selected return rows)
            $product_names = $request->input('product', []);     // product name or id text
            $product_ids   = $request->input('product_id', []);  // may be empty strings for some rows
            $product_codes = $request->input('item_code', []);
            $brands        = $request->input('brand', $request->input('uom', [])); // some forms call it uom
            $units         = $request->input('unit', []);
            $prices        = $request->input('price', []);
            $discounts     = $request->input('item_disc', []);
            $quantities    = $request->input('qty', []);
            $totals        = $request->input('total', []);
            $colors        = $request->input('color', []); // may be array of single selected color value or json

            // We'll build combined arrays to save in sales_returns
            $combined_products   = [];
            $combined_codes      = [];
            $combined_brands     = [];
            $combined_units      = [];
            $combined_prices     = [];
            $combined_discounts  = [];
            $combined_qtys       = [];
            $combined_totals     = [];
            $combined_colors     = [];

            $total_items = 0;

            // Use length of product_names (should match other arrays); be defensive
            $rows = max(
                count($product_names),
                count($product_codes),
                count($quantities),
                count($prices)
            );

            // Build a map of returns by code for updating original sale quantities
            $returnByCode = []; // code => qty to reduce

            for ($i = 0; $i < $rows; $i++) {
                $name = isset($product_names[$i]) ? trim($product_names[$i]) : '';
                $pid  = isset($product_ids[$i]) ? trim($product_ids[$i]) : '';
                $code = isset($product_codes[$i]) ? trim($product_codes[$i]) : '';
                $brand = isset($brands[$i]) ? trim($brands[$i]) : '';
                $unit  = isset($units[$i]) ? trim($units[$i]) : '';
                $price = isset($prices[$i]) ? floatval($prices[$i]) : 0;
                $disc  = isset($discounts[$i]) ? floatval($discounts[$i]) : 0;
                $qty   = isset($quantities[$i]) ? floatval($quantities[$i]) : 0;
                $total = isset($totals[$i]) ? floatval($totals[$i]) : ($price * $qty);
                $colorRaw = $colors[$i] ?? null;

                // Skip rows with zero qty (not selected)
                if ($qty <= 0) continue;

                // Push to combined arrays (preserve name/code even if id missing)
                $combined_products[]  = $name;
                $combined_codes[]     = $code;
                $combined_brands[]    = $brand;
                $combined_units[]     = $unit;
                $combined_prices[]    = (string)$price;
                $combined_discounts[] = (string)$disc;
                $combined_qtys[]      = (string)$qty;
                $combined_totals[]    = (string)$total;

                // Normalize color: if array => json_encode; if string that's JSON => try decode
                if (is_array($colorRaw)) {
                    $combined_colors[] = json_encode($colorRaw);
                } else {
                    // try parse string -> if valid json array keep, else wrap single value
                    $decoded = null;
                    if (is_string($colorRaw)) {
                        $decoded = json_decode($colorRaw, true);
                    }
                    if (is_array($decoded)) {
                        $combined_colors[] = json_encode($decoded);
                    } elseif (!empty($colorRaw)) {
                        $combined_colors[] = json_encode([$colorRaw]);
                    } else {
                        $combined_colors[] = json_encode([]);
                    }
                }

                // Stock update: increase stock for returned items if we can find product by ID or code
                $foundProduct = null;
                if ($pid !== '') {
                    // If numeric id provided, try find by id
                    if (is_numeric($pid)) {
                        $foundProduct = \App\Models\Product::find(intval($pid));
                    } else {
                        // sometimes product_id may come as name; try to find by id or code fallback
                        $maybe = \App\Models\Product::find($pid);
                        if (!$maybe && $code) {
                            $maybe = \App\Models\Product::where('item_code', $code)->first();
                        }
                        $foundProduct = $maybe;
                    }
                } else if (!empty($code)) {
                    $foundProduct = \App\Models\Product::where('item_code', $code)->first();
                } else if (!empty($name)) {
                    $foundProduct = \App\Models\Product::where('item_name', $name)->first();
                }

                if ($foundProduct) {
                    // Update stock: find stock row for product in same branch/warehouse (use sale's warehouse if available)
                    // If you use branch_id or auth()->id() use appropriate field
                    $stockQuery = \App\Models\Stock::where('product_id', $foundProduct->id);
                    // if your sale has warehouse info use that, else we skip warehouse filter
                    if (!empty($sale->warehouse_id)) {
                        $stockQuery->where('warehouse_id', $sale->warehouse_id);
                    }
                    // optionally branch filter
                    // $stockQuery->where('branch_id', auth()->id());

                    $stock = $stockQuery->first();
                    if ($stock) {
                        $stock->qty = $stock->qty + $qty;
                        $stock->save();
                    }
                }

                // accumulate for sale update
                $key = $code ?: ($foundProduct ? ('ID_' . $foundProduct->id) : $name);
                if (!isset($returnByCode[$key])) $returnByCode[$key] = 0;
                $returnByCode[$key] += $qty;

                $total_items += $qty;
            }

            if (empty($combined_products)) {
                return redirect()->back()->with('error', 'No items selected for return.');
            }

            // Save sales_returns row (CSV arrays + json color array)
            $saleReturn = new \App\Models\SalesReturn();
            $saleReturn->sale_id = $saleId;
            $saleReturn->customer = $request->customer;
            $saleReturn->reference = $request->reference;
            $saleReturn->product = implode(',', $combined_products);
            $saleReturn->product_code = implode(',', $combined_codes);
            $saleReturn->brand = implode(',', $combined_brands);
            $saleReturn->unit = implode(',', $combined_units);
            $saleReturn->per_price = implode(',', $combined_prices);
            $saleReturn->per_discount = implode(',', $combined_discounts);
            $saleReturn->qty = implode(',', $combined_qtys);
            $saleReturn->per_total = implode(',', $combined_totals);
            // colors as JSON array of json-encoded color-arrays (to keep compatible with your existing format)
            $saleReturn->color = json_encode($combined_colors);
            $saleReturn->total_amount_Words = $request->total_amount_Words ?? '';
            $saleReturn->total_bill_amount = $request->total_subtotal ?? array_sum($combined_totals);
            $saleReturn->total_extradiscount = $request->total_extra_cost ?? 0;
            $saleReturn->total_net = $request->total_net ?? array_sum($combined_totals);
            $saleReturn->cash = $request->cash ?? 0;
            $saleReturn->card = $request->card ?? 0;
            $saleReturn->change = $request->change ?? 0;
            $saleReturn->total_items = $total_items;
            $saleReturn->return_note = $request->return_note ?? null;
            $saleReturn->save();

            // -----------------------
            // Update original Sale quantities by matching product_code positions.
            // We will try to reduce quantities based on product_code matching. This handles multi-item sales correctly.
            // -----------------------
            // Convert sale comma fields to arrays
            $sale_products  = array_map('trim', explode(',', $sale->product ?? ''));
            $sale_codes     = array_map('trim', explode(',', $sale->product_code ?? ''));
            $sale_qtys      = array_map('trim', explode(',', $sale->qty ?? ''));
            $sale_prices    = array_map('trim', explode(',', $sale->per_price ?? ''));
            $sale_totals    = array_map('trim', explode(',', $sale->per_total ?? ''));

            // initialize numeric arrays safely
            $sale_qtys = array_map(function ($v) {
                return is_numeric($v) ? floatval($v) : 0;
            }, $sale_qtys);
            $sale_totals = array_map(function ($v) {
                return is_numeric($v) ? floatval($v) : 0;
            }, $sale_totals);
            $sale_prices = array_map(function ($v) {
                return is_numeric($v) ? floatval($v) : 0;
            }, $sale_prices);

            // For each returnByCode entry, reduce sale_qtys in first matching positions until consumed
            foreach ($returnByCode as $codeKey => $qtyToReduce) {
                // We stored keys as actual code string or 'ID_xxx' or name; prefer matching code
                $matchCode = $codeKey;
                if (strpos($codeKey, 'ID_') === 0) {
                    // try to resolve to item_code via product id
                    $pid = intval(substr($codeKey, 3));
                    $prod = \App\Models\Product::find($pid);
                    if ($prod) $matchCode = $prod->item_code;
                }

                // search through sale_codes left-to-right and reduce where equal
                for ($j = 0; $j < count($sale_codes) && $qtyToReduce > 0; $j++) {
                    $sc = trim($sale_codes[$j] ?? '');
                    if ($sc === $matchCode) {
                        $availableHere = $sale_qtys[$j] ?? 0;
                        if ($availableHere <= 0) continue;
                        $deduct = min($availableHere, $qtyToReduce);
                        $sale_qtys[$j] = max(0, $sale_qtys[$j] - $deduct);

                        // recalc per_total for that line using sale_prices[$j]
                        $priceHere = $sale_prices[$j] ?? 0;
                        $sale_totals[$j] = $priceHere * $sale_qtys[$j];

                        $qtyToReduce -= $deduct;
                    }
                }
                // if qtyToReduce still >0, we attempted best-effort; ignore remainder
            }

            // update sale fields back
            $sale->qty = implode(',', $sale_qtys);
            $sale->per_total = implode(',', $sale_totals);
            $sale->total_net = array_sum($sale_totals);
            $sale->total_bill_amount = $sale->total_net;
            $sale->total_items = array_sum($sale_qtys);
            // optionally update sale_status: mark as partially returned (1) or fully returned
            $sale->sale_status = 1;
            $sale->save();

            // -----------------------
            // Customer ledger update (simple)
            // -----------------------
            $customer_id = $request->customer;
            $netAmount = $saleReturn->total_net;

            // Only update ledger if customer is numeric (i.e., normal customer)
            if (is_numeric($customer_id) && $customer_id > 0) {
                $ledger = \App\Models\CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance = $ledger->closing_balance - $netAmount;
                    $ledger->save();
                } else {
                    \App\Models\CustomerLedger::create([
                        'customer_id' => $customer_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'opening_balance' => 0 - $netAmount,
                        'closing_balance' => 0 - $netAmount,
                    ]);
                }
            } else {
                // Walk-in Customer: do nothing in ledger
            }
            DB::commit();
            return redirect()->route('sale.index')->with('success', 'Sale return saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Sale return failed: ' . $e->getMessage());
        }
    }


    public function salereturnview()
    {
        // Fetch all sale returns with the original sale and customer info
        $salesReturns = SalesReturn::with('sale.customer_relation')->orderBy('created_at', 'desc')->get();
        return view('admin_panel.sale.return.index', [
            'salesReturns' => $salesReturns,
        ]);
    }

    public function saleinvoice($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);

        $saleReturn = \App\Models\SalesReturn::where('sale_id', $sale->id)->first();

        // 🔥 IMPORTANT: decide source
        $bill = $sale;
        // items
        $products  = explode(',', $sale->product);
        $codes     = explode(',', $sale->product_code);
        $brands    = explode(',', $sale->brand);
        $units     = explode(',', $sale->unit);
        $prices    = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys      = explode(',', $sale->qty);
        $totals    = explode(',', $sale->per_total);

        $items = [];
        $productIds = array_unique($products);
        $productMap = Product::whereIn('id', $productIds)
            ->pluck('item_name', 'id'); // [id => item_name]
        foreach ($products as $index => $p) {

            $qty = (float) ($qtys[$index] ?? 0);

            // ❌ returned item → skip
            if ($qty <= 0) {
                continue;
            }

            $items[] = [
                'item_name' => $productMap[$p] ?? $p,
                'item_code' => $codes[$index] ?? '',
                'brand'     => $brands[$index] ?? '',
                'unit'      => $units[$index] ?? '',
                'price'     => (float) ($prices[$index] ?? 0),
                'discount'  => (float) ($discounts[$index] ?? 0),
                'qty'       => $qty,
                'total'     => (float) ($totals[$index] ?? 0),
            ];
        }

        return view('admin_panel.sale.saleinvoice', [
            'sale'       => $sale,
            'bill'       => $bill,        // 👈 unified object
            'saleItems'  => $items,
            'saleReturn' => $saleReturn,
        ]);
    }
    public function saleedit($id)
    {
        $sale = Sale::findOrFail($id);

        $customers = Customer::all();

        $products   = explode(',', $sale->product);
        $codes      = explode(',', $sale->product_code);
        $brands     = explode(',', $sale->brand);
        $units      = explode(',', $sale->unit);
        $prices     = explode(',', $sale->per_price);
        $discounts  = explode(',', $sale->per_discount);
        $qtys       = explode(',', $sale->qty);
        $totals     = explode(',', $sale->per_total);

        // Expecting sale->color to be JSON array (each element a JSON-encoded note or plain string)
        $colors_json = json_decode($sale->color, true);
        if (!is_array($colors_json)) {
            $colors_json = [];
        }

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            // Get note safely:
            $note_value = '';
            if (isset($colors_json[$index])) {
                // If stored as JSON-encoded string, decode; else use directly
                $maybe = $colors_json[$index];

                if (is_string($maybe)) {
                    // try json decode in case it's JSON string
                    $try = json_decode($maybe, true);
                    if ($try !== null) {
                        // decoded OK (could be array or string)
                        if (is_array($try)) {
                            // join array into newline-separated text
                            $note_value = implode("\n", $try);
                        } else {
                            $note_value = (string)$try;
                        }
                    } else {
                        // plain string note
                        $note_value = $maybe;
                    }
                } elseif (is_array($maybe)) {
                    // join into text
                    $note_value = implode("\n", $maybe);
                } else {
                    $note_value = (string)$maybe;
                }
            }

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => floatval($qtys[$index] ?? 1), // <-- use floatval
                'total'      => floatval($totals[$index] ?? 0),
                'note'       => $note_value,
            ];
        }

        return view('admin_panel.sale.saleedit', [
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
        ]);
    }

    public function updatesale(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // --- Arrays from request ---
            $product_ids    = $request->product_id ?? [];
            $product_names  = $request->product_name ?? [];
            $product_codes  = $request->item_code ?? [];
            $brands         = $request->brand ?? [];
            $units          = $request->unit ?? [];
            $prices         = $request->price ?? [];
            $discounts      = $request->item_disc ?? [];
            $quantities     = $request->qty ?? [];
            $totals         = $request->total ?? [];
            $colors         = $request->color ?? [];

            $combined_products  = [];
            $combined_codes     = [];
            $combined_brands    = [];
            $combined_units     = [];
            $combined_prices    = [];
            $combined_discounts = [];
            $combined_qtys      = [];
            $combined_totals    = [];
            $combined_colors    = [];

            $total_items = 0;

            // --- Get old sale to update stock differences ---
            $sale = Sale::findOrFail($id);
            $old_quantities = explode(',', $sale->qty);
            $old_product_ids = explode(',', $sale->product_code); // we track by code for safety

            foreach ($product_ids as $index => $product_id) {
                $qty   = isset($quantities[$index]) ? floatval($quantities[$index]) : 0;
                $price = isset($prices[$index]) ? floatval($prices[$index]) : 0;

                if (!$product_id || $qty <= 0 || $price <= 0) continue;

                // --- Product ID hi store karna hai, name nahi ---
                $combined_products[]  = $product_id;
                $combined_codes[]     = $product_codes[$index] ?? '';
                $combined_brands[]    = $brands[$index] ?? '';
                $combined_units[]     = $units[$index] ?? '';
                $combined_prices[]    = $prices[$index];
                $combined_discounts[] = $discounts[$index] ?? 0;
                $combined_qtys[]      = $qty;
                $combined_totals[]    = $totals[$index] ?? 0;
                $combined_colors[]    = json_encode($colors[$index] ?? []);

                $total_items += $qty;

                // --- Update stock ---
                $stock = \App\Models\Stock::where('product_id', $product_id)->first();
                $old_index = array_search($product_codes[$index], $old_product_ids);
                $old_qty = $old_index !== false ? floatval($old_quantities[$old_index]) : 0;
                $qty_diff = $qty - $old_qty;

                if ($stock) {
                    $stock->qty -= $qty_diff;
                    $stock->save();
                } else {
                    \App\Models\Stock::create([
                        'product_id' => $product_id,
                        'qty' => -$qty_diff,
                    ]);
                }
            }

            // --- Save updated Sale ---
            $old_total = $sale->total_net;

            $sale->customer            = $request->customer;
            $sale->reference           = $request->reference;
            $sale->product             = implode(',', $combined_products);
            $sale->product_code        = implode(',', $combined_codes);
            $sale->brand               = implode(',', $combined_brands);
            $sale->unit                = implode(',', $combined_units);
            $sale->per_price           = implode(',', $combined_prices);
            $sale->per_discount        = implode(',', $combined_discounts);
            $sale->qty                 = implode(',', $combined_qtys);
            $sale->per_total           = implode(',', $combined_totals);
            $sale->color               = json_encode($combined_colors);
            $sale->total_amount_Words  = $request->total_amount_Words;
            $sale->total_bill_amount   = $request->total_subtotal;
            $sale->total_extradiscount = $request->total_extra_cost;
            $sale->total_net           = $request->total_net;
            $sale->cash                = $request->cash;
            $sale->card                = $request->card;
            $sale->change              = $request->change;
            $sale->total_items         = $total_items;
            $sale->save();

            // --- Ledger Update ---
            $customer_id = $request->customer;

            if ($customer_id !== 'Walk-in Customer') { // ✅ Only update ledger for registered customers
                $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
                $difference = $request->total_net - $old_total;

                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  += $difference;
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $customer_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'closing_balance'  => $request->total_net,
                        'opening_balance'  => $request->total_net,
                    ]);
                }
            }

            DB::commit();
            $returnTo   = route('sale.add'); // ya route('sale.edit', $sale->id) agar wapas edit chaho
            $invoiceUrl = route('sales.invoice', $sale->id)
                . '?return_to=' . urlencode($returnTo)
                . '&autoprint=1';

            return redirect()->to($invoiceUrl)
                ->with('success', 'Sale updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }


    public function saledc($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);

        // Decode sale pivot or comma fields
        $products = explode(',', $sale->product);
        $codes    = explode(',', $sale->product_code);
        $brands   = explode(',', $sale->brand);
        $units    = explode(',', $sale->unit);
        $prices   = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys     = explode(',', $sale->qty);
        $totals   = explode(',', $sale->per_total);
        $colors_json = json_decode($sale->color, true);

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => intval($qtys[$index] ?? 1),
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.saledc', [
            'sale' => $sale,
            'saleItems' => $items,
        ]);
    }

    public function salerecepit($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);

        // Decode sale pivot or comma fields
        $products = explode(',', $sale->product);
        $codes    = explode(',', $sale->product_code);
        $brands   = explode(',', $sale->brand);
        $units    = explode(',', $sale->unit);
        $prices   = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys     = explode(',', $sale->qty);
        $totals   = explode(',', $sale->per_total);
        $colors_json = json_decode($sale->color, true);

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => intval($qtys[$index] ?? 1),
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.salerecepit', [
            'sale' => $sale,
            'saleItems' => $items,
        ]);
    }

    public function retrninvoice($id)
    {
        $return = \App\Models\SalesReturn::with('sale.customer_relation')->findOrFail($id);

        $products   = explode(',', $return->product);
        $codes      = explode(',', $return->product_code);
        $brands     = explode(',', $return->brand);
        $units      = explode(',', $return->unit);
        $prices     = explode(',', $return->per_price);
        $discounts  = explode(',', $return->per_discount);
        $qtys       = explode(',', $return->qty);
        $totals     = explode(',', $return->per_total);
        $colors_json = json_decode($return->color, true);

        $items = [];

        foreach ($products as $index => $p) {

            $qty = intval($qtys[$index] ?? 0);

            // ❌ qty 0 ya empty ho to skip
            if ($qty <= 0) {
                continue;
            }

            $product = \App\Models\Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => $qty,
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index])
                    ? json_decode($colors_json[$index], true)
                    : [],
            ];
        }

        $unitTotals = [
            'Pc'  => 0,
            'Mtr' => 0,
            'Yd'  => 0,
        ];

        foreach ($items as $item) {
            $unit = strtolower($item['unit']);

            if (in_array($unit, ['pc', 'pcs', 'piece'])) {
                $unitTotals['Pc'] += $item['qty'];
            }

            if (in_array($unit, ['mtr', 'meter'])) {
                $unitTotals['Mtr'] += $item['qty'];
            }

            if (in_array($unit, ['yd', 'yard'])) {
                $unitTotals['Yd'] += $item['qty'];
            }
        }

        return view('admin_panel.sale.return.salereturnrecepit', [
            'return' => $return,
            'returnItems' => $items,
            'unitTotals'   => $unitTotals,
        ]);
    }
}
