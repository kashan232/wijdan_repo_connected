<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ProductDiscount;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\Stock\PostClosingStockAdjustmentService;
use Illuminate\Support\Facades\DB;
// use App\Models\Size;
use Carbon\Carbon;
use Milon\Barcode\DNS1D;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{

    public function searchProducts(Request $request)
    {
        $q = $request->get('q');

        $products = Product::with('brand')->where(function ($query) use ($q) {
            $query->where('item_name', 'like', "%{$q}%")
                ->orWhere('item_code', 'like', "%{$q}%")
                ->orWhere('barcode_path', 'like', "%{$q}%");
        })->get();

        return response()->json($products);
    }
    // public function searchProducts(Request $request)
    // {
    //     $q = $request->get('q');

    //     $products = Product::with(['brand', 'activeDiscount'])
    //         ->whereHas('activeDiscount') // only products with active discount
    //         ->where(function ($query) use ($q) {
    //             $query->where('item_name', 'like', "%{$q}%")
    //                   ->orWhere('item_code', 'like', "%{$q}%")
    //                   ->orWhere('barcode_path', 'like', "%{$q}%");
    //         })
    //         ->get();

    //     return response()->json($products);
    // }


    public function product(Request $request)
    {
        $search = $request->search;

        $products = Product::with([
            'category_relation',
            'sub_category_relation',
            'unit',
            'brand',
            'stock',
            'discountProduct'
        ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('item_name', 'like', "%{$search}%")
                        ->orWhere('item_code', 'like', "%{$search}%")
                        ->orWhere('barcode_path', 'like', "%{$search}%")
                        ->orWhereHas('brand', function ($b) use ($search) {
                            $b->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('category_relation', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('item_code', 'desc')
            ->paginate(100);


        // 🔧 THIS LINE FIXES EVERYTHING
        $categories = Category::orderBy('id', 'desc')->get();
        $warehouses = Warehouse::orderBy('id')->get();
        $defaultStockAsOfDate = config('stock.yearly_closing_date', date('Y-m-d'));
        if ($request->ajax()) {
            return view('admin_panel.product.index', compact('products', 'categories', 'warehouses', 'defaultStockAsOfDate'))->render();
        }
        return view('admin_panel.product.index', compact('products', 'categories', 'warehouses', 'defaultStockAsOfDate'));
    }





    public function view_store()
    {
        $categories = Category::select('id', 'name')->get();
        $units = Unit::select('id', 'name')->get();
        $brands = Brand::select('id', 'name')->get();
        return view('admin_panel.product.create', compact('categories', 'units', 'brands'));
    }

    public function getSubcategories($category_id)
    {
        $subcategories = SubCategory::where('category_id', $category_id)->get();
        return response()->json($subcategories);
    }
    public function generateBarcode(Request $request)
    {
        // normalize to exactly 6 digits if provided
        $candidate = null;
        if ($request->filled('code')) {
            $digits   = preg_replace('/\D+/', '', $request->query('code')); // keep only digits
            $digits   = substr($digits, 0, 6);
            $candidate = str_pad($digits, 6, '0', STR_PAD_LEFT);             // ensure 6 digits
        }

        $maxRetries = 10;
        $code = $candidate;

        for ($i = 0; $i < $maxRetries; $i++) {
            if (!$code || $this->codeExists($code)) {
                // either not provided OR collision found → generate new
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                if ($this->codeExists($code)) {
                    $code = null; // loop again
                    continue;
                }
            }
            // unique mil gaya
            break;
        }

        if (!$code || $this->codeExists($code)) {
            return response()->json([
                'message' => 'Could not generate a unique 6-digit barcode. Please try again.'
            ], 409);
        }

        // Barcode image (CODE128 recommended; C39 bhi chalega)
        $png = (new \Milon\Barcode\DNS1D)->getBarcodePNG($code, 'C128', 2, 50);
        $barcodeImage = 'data:image/png;base64,' . $png;

        return response()->json([
            'barcode_number' => $code,
            'barcode_image'  => $barcodeImage,
        ]);
    }

    /** Check uniqueness across products & discounts */
    private function codeExists(string $code): bool
    {
        return Product::where('barcode_path', $code)->exists()
            || ProductDiscount::where('discount_code', $code)->exists();
    }





    public function store_product(Request $request)
    {
        if (!Auth::id()) {
            return redirect()->back();
        }
        $userId = Auth::id();

        // basic validation (adjust rules as needed)
        $request->validate([
            'product_name'   => 'required|string|max:255|unique:products,item_name',
            'category_id'    => 'nullable|integer',
            'barcode_path'    => 'nullable|unique:products,barcode_path',
            'sub_category_id' => 'nullable|integer',
            'unit'           => 'nullable',
            'Stock'          => 'nullable|numeric',
            'wholesale_price' => 'nullable|numeric',
            'retail_price'   => 'nullable|numeric',
        ]);

        // Generate next item code
        $lastProduct = Product::orderBy('id', 'desc')->first();
        $nextCode = 'ITEM-0001';
        if ($lastProduct) {
            $lastId = $lastProduct->id + 1;
            $nextCode = 'ITEM-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);
        }

        // Image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = $filename;
        }

        // Normalize fields: ensure we are not sending arrays where strings/ints expected
        $categoryId = $request->input('category_id') ? (int)$request->input('category_id') : null;
        $subCategoryId = $request->input('sub_category_id') ? (int)$request->input('sub_category_id') : null;

        // unit might come as id or as array if front-end sent multiple — handle both
        $unitInput = $request->input('unit');
        if (is_array($unitInput)) {
            // if it's array, try to take first value (or change logic to what you want)
            $unitInput = reset($unitInput);
        }
        $unit = $unitInput !== null ? $unitInput : null;

        $brandInput = $request->input('brand_id');
        if (is_array($brandInput)) {
            $brandInput = reset($brandInput);
        }
        $brandId = $brandInput !== null ? (int)$brandInput : null;

        // color: if array -> json_encode, if single string -> store as string or json as you prefer
        $colorInput = $request->input('color');
        $colorValue = null;
        if (is_array($colorInput)) {
            $colorValue = json_encode(array_values($colorInput));
        } elseif (!is_null($colorInput) && $colorInput !== '') {
            // keep as JSON array for consistency:
            $colorValue = json_encode([$colorInput]);
        }

        try {
            $product = Product::create([
                'creater_id'      => $userId,
                'category_id'     => $categoryId,
                'sub_category_id' => $subCategoryId,
                'item_code'       => $nextCode,
                'item_name'       => $request->input('product_name'),
                'barcode_path'    => $request->input('barcode_path') ?? rand(100000000000, 999999999999),
                'unit_id'         => $unit,
                'initial_stock'   => $request->input('Stock') ? (float)$request->input('Stock') : 0,
                'brand_id'        => $brandId,
                'wholesale_price' => $request->input('wholesale_price') ? (float)$request->input('wholesale_price') : 0,
                'price'           => $request->input('retail_price') ? (float)$request->input('retail_price') : 0,
                'alert_quantity'  => $request->input('alert_quantity') ? (int)$request->input('alert_quantity') : 0,
                'note'            => $request->input('note'),
                'image'           => $imagePath,
                'color'           => $colorValue,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Stock entry (only if stock > 0)
            if ($request->input('Stock') && floatval($request->input('Stock')) > 0) {
                DB::table('stocks')->insert([
                    'branch_id'    => $request->input('branch_id') ? (int)$request->input('branch_id') : 1,
                    'warehouse_id' => $request->input('warehouse_id') ? (int)$request->input('warehouse_id') : 1,
                    'product_id'   => $product->id,
                    'qty'          => (float)$request->input('Stock'),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            return redirect('Product')->with('success', 'Product created successfully');
        } catch (\Exception $e) {
            // Helpful debug — in production you might log instead
            return back()->withInput()->with('error', 'Error creating product: ' . $e->getMessage());
        }
    }





    public function update(Request $request, $id)
    {
        $product_id = $id;
        $userId = auth()->id();
        $imageFilename = null;

        // Image handling (store only filename to stay consistent with create)
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('uploads/products'), $imageName);
            $imageFilename = $imageName; // ONLY filename
        } else {
            $imageFilename = Product::where('id', $product_id)->value('image');
        }

        // Update product table
        Product::where('id', $product_id)->update([
            'creater_id'      => $userId,
            'category_id'     => $request->category_id,
            'sub_category_id' => $request->sub_category_id,
            'item_code'       => $request->item_code,
            'item_name'       => $request->product_name,
            'barcode_path'    => $request->barcode_path ?? rand(100000000000, 999999999999),
            'unit_id'         => $request->unit,
            'initial_stock'   => $request->Stock,
            'brand_id'        => $request->brand_id,
            'wholesale_price' => $request->wholesale_price,
            'price'           => $request->retail_price,
            'note'            => $request->note,
            'alert_quantity'  => $request->alert_quantity,
            'image'           => $imageFilename,
            'updated_at'      => now(),
        ]);

        // ===== Update or Insert to stocks table =====
        // Determine branch & warehouse (use request or defaults)
        $branchId = $request->branch_id ?? 1;
        $warehouseId = $request->warehouse_id ?? 1;
        $newQty = (int) $request->Stock; // sanitize

        // Try to update existing stock row for this product + branch + warehouse
        $updated = DB::table('stocks')
            ->where('product_id', $product_id)
            ->where('branch_id', $branchId)
            ->where('warehouse_id', $warehouseId)
            ->update([
                'qty' => $newQty,
                'updated_at' => now(),
            ]);

        // If update affected 0 rows, insert a new stock row (only if qty > 0 or if you want to keep zeros too)
        if (!$updated) {
            DB::table('stocks')->insert([
                'branch_id'    => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id'   => $product_id,
                'qty'          => $newQty,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Product updated successfully');
    }

    public function edit($id)
    {

        $product = Product::with('category_relation', 'sub_category_relation', 'unit', 'brand')->findOrFail($id);

        // dd($product->toArray());
        $categories = Category::all();


        $subcategories = SubCategory::all();
        $brands = Brand::all();
        return view('admin_panel.product.edit', compact('product', 'categories', 'subcategories', 'brands'));
    }

    // Add function in ProductController.php
    public function barcode($id)
    {
        $product = Product::with('activeDiscount')->findOrFail($id);
        return view('admin_panel.product.barcode', compact('product'));
    }

    public function getAllProductsExport()
    {
        $products = Product::with([
            'category_relation',
            'sub_category_relation',
            'unit',
            'brand',
            'stock',
            'discountProduct'
        ])
        ->orderBy('item_code', 'desc')
        ->get()
        ->map(function ($product) {
            $price = (float)$product->price;
            if ($product->discountProduct) {
                $price = (float)$product->discountProduct->final_price;
            }

            return [
                $product->item_code ?? '',
                $product->barcode_path ?? '—',
                $product->category_relation->name ?? '-',
                $product->sub_category_relation->name ?? '-',
                $product->item_name ?? '',
                $product->unit_id ?? '-',
                $price,
                $product->stock->qty ?? 0,
                $product->brand->name ?? '-',
                $product->note ?? '-'
            ];
        });

        return response()->json($products);
    }

    public function downloadImportTemplate()
    {
        $headers = [
            'Barcode',
            'Category',
            'Sub-Category',
            'Item Name',
            'Brand',
            'Unit',
            'Wholesale Price',
            'Retail Price',
            'Shop Qty',
            'W/H Qty',
            'Alert Qty',
            'Note',
        ];

        $sample = [
            '313250',
            'Women',
            'Unstitch Casual',
            'Sample Product Name',
            'HZ textile',
            'Piece',
            '5000',
            '5300',
            '1',
            '0',
            '5',
            'Sample remarks',
        ];

        $callback = function () use ($headers, $sample) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            fputcsv($handle, $sample);
            fclose($handle);
        };

        return response()->streamDownload($callback, 'product_import_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadCategoryReference()
    {
        $headers = ['Category', 'Sub-Category'];

        $rows = Subcategory::with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get()
            ->map(function ($sub) {
                return [
                    $sub->category->name ?? '',
                    $sub->name,
                ];
            })
            ->all();

        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, 'category_subcategory_list.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadUpdateTemplate()
    {
        $headers = [
            'Barcode',
            'Item Name',
            'Retail Price',
            'Shop Qty',
            'W/H Qty',
        ];

        $sample = [
            '313250',
            'Hz NakshKari V3',
            '5300',
            '1',
            '0',
        ];

        $callback = function () use ($headers, $sample) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            fputcsv($handle, $sample);
            fclose($handle);
        };

        return response()->streamDownload($callback, 'product_update_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'update_file' => 'required|file|mimes:csv,txt|max:20480',
            'stock_as_of_date' => 'required|date',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        if (!Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $path = $request->file('update_file')->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return redirect()->back()->with('error', 'Could not read the uploaded file.');
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV file is empty.');
        }

        // Remove UTF-8 BOM if present in the first header
        $headerRow[0] = preg_replace('/^[\xef\xbb\xbf]+/', '', $headerRow[0]);

        $columnMap = $this->buildImportColumnMap($headerRow);
        if (!isset($columnMap['barcode']) && !isset($columnMap['item_name'])) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV must contain a Barcode or Item Name column.');
        }

        $hasRetail = isset($columnMap['retail_price']);
        $hasShop = isset($columnMap['shop_qty']);
        $hasWarehouse = isset($columnMap['warehouse_qty']);

        if (!$hasRetail && !$hasShop && !$hasWarehouse) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV must contain at least one of: Retail Price, Shop Qty, W/H Qty.');
        }

        $warehouseId = (int) ($request->warehouse_id ?? DB::table('warehouses')->orderBy('id')->value('id') ?? 1);
        $stockAsOfDate = $request->stock_as_of_date;
        /** @var PostClosingStockAdjustmentService $adjustmentService */
        $adjustmentService = app(PostClosingStockAdjustmentService::class);
        $postClosingAdjustments = $adjustmentService->calculate($stockAsOfDate);

        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isImportRowEmpty($row)) {
                    continue;
                }

                $data = $this->parseImportRow($row, $columnMap);
                $barcode = trim((string) ($data['barcode'] ?? ''));
                $itemName = trim((string) ($data['item_name'] ?? ''));

                if ($barcode === '' && $itemName === '') {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: barcode or item name is required.";
                    continue;
                }

                $product = $this->findProductForBulkUpdate($barcode, $itemName);
                if (!$product) {
                    $skipped++;
                    $label = $barcode !== '' ? "barcode {$barcode}" : "name \"{$itemName}\"";
                    if ($barcode !== '' && $itemName !== '') {
                        $label = "barcode {$barcode} / name \"{$itemName}\"";
                    }
                    $errors[] = "Row {$rowNumber}: product not found ({$label}).";
                    continue;
                }

                $productUpdates = [];
                $didUpdate = false;

                if ($hasRetail && $data['retail_price'] !== null && $data['retail_price'] !== '') {
                    $productUpdates['price'] = (float) $data['retail_price'];
                    $didUpdate = true;
                }

                if ($hasShop || $hasWarehouse) {
                    $excelShop = ($hasShop && $data['shop_qty'] !== null && $data['shop_qty'] !== '')
                        ? max(0, (float) $data['shop_qty'])
                        : null;
                    $excelWh = ($hasWarehouse && $data['warehouse_qty'] !== null && $data['warehouse_qty'] !== '')
                        ? max(0, (float) $data['warehouse_qty'])
                        : null;

                    $currentShop = (float) (DB::table('stocks')
                        ->where('product_id', $product->id)
                        ->where('branch_id', 1)
                        ->where('warehouse_id', 1)
                        ->value('qty') ?? 0);

                    $currentWh = (float) (DB::table('warehouse_stocks')
                        ->where('product_id', $product->id)
                        ->where('warehouse_id', $warehouseId)
                        ->sum('quantity'));

                    $newShop = $excelShop !== null
                        ? $adjustmentService->storedShopFromExcelBaseline($excelShop, $postClosingAdjustments, (int) $product->id)
                        : $currentShop;
                    $newWh = $excelWh !== null
                        ? $adjustmentService->storedWarehouseFromExcelBaseline($excelWh, $postClosingAdjustments, (int) $product->id, $warehouseId)
                        : $currentWh;

                    $this->syncImportedStocks($product->id, $newShop, $newWh, $warehouseId);
                    $productUpdates['initial_stock'] = $newShop + $newWh;
                    $didUpdate = true;
                }

                if (!$didUpdate) {
                    $skipped++;
                    $ref = $product->barcode_path ?: $product->item_name;
                    $errors[] = "Row {$rowNumber}: no update values for product ({$ref}).";
                    continue;
                }

                $productUpdates['updated_at'] = now();
                $product->update($productUpdates);
                $updated++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return redirect()->route('product')->with('error', 'Update failed: ' . $e->getMessage());
        }

        fclose($handle);

        $message = "Bulk update complete: {$updated} products updated (stock as of {$stockAsOfDate}, post-date movements added)";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }

        return redirect()->route('product')
            ->with('success', $message)
            ->with('import_errors', array_slice($errors, 0, 30));
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if (!Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $path = $request->file('import_file')->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return redirect()->back()->with('error', 'Could not read the uploaded file.');
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV file is empty.');
        }

        $columnMap = $this->buildImportColumnMap($headerRow);
        if (!isset($columnMap['item_name'])) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV must contain an item_name column.');
        }

        $lookups = $this->buildImportLookups();
        $userId = Auth::id();
        $nextItemNumber = ((int) Product::withTrashed()->max('id')) + 1;

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isImportRowEmpty($row)) {
                    continue;
                }

                $data = $this->parseImportRow($row, $columnMap);

                if (empty($data['item_name'])) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: item_name is required.";
                    continue;
                }

                $categoryId = $this->resolveImportCategoryId($data, $lookups);
                if (!$categoryId && (!empty($data['category']) || !empty($data['category_id']))) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: category not found ({$data['category']}).";
                    continue;
                }

                $subCategoryId = $this->resolveImportSubCategoryId($data, $lookups, $categoryId);
                if (!$subCategoryId && (!empty($data['sub_category']) || !empty($data['sub_category_id']))) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: sub-category not found ({$data['sub_category']}).";
                    continue;
                }

                $brandId = null;
                if (!empty($data['brand'])) {
                    $brand = Brand::firstOrCreate(['name' => trim($data['brand'])]);
                    $brandId = $brand->id;
                }

                $unit = $this->normalizeImportUnit($data['unit'] ?? 'Piece');
                $barcode = trim((string) ($data['barcode'] ?? ''));
                if ($barcode === '') {
                    $barcode = (string) random_int(100000000000, 999999999999);
                }

                $shopQtyProvided = isset($data['shop_qty']) && $data['shop_qty'] !== '';
                $warehouseQtyProvided = isset($data['warehouse_qty']) && $data['warehouse_qty'] !== '';

                $shopQty = $shopQtyProvided ? max(0, (float) $data['shop_qty']) : null;
                $warehouseQty = $warehouseQtyProvided ? max(0, (float) $data['warehouse_qty']) : null;

                $productData = [];
                if (!empty($data['item_name'])) $productData['item_name'] = $data['item_name'];
                if ($categoryId) $productData['category_id'] = $categoryId;
                if ($subCategoryId) $productData['sub_category_id'] = $subCategoryId;
                if ($brandId) $productData['brand_id'] = $brandId;
                if (isset($data['unit']) && $data['unit'] !== '') $productData['unit_id'] = $unit;
                if (isset($data['wholesale_price']) && $data['wholesale_price'] !== '') $productData['wholesale_price'] = (float) $data['wholesale_price'];
                if (isset($data['retail_price']) && $data['retail_price'] !== '') $productData['price'] = (float) $data['retail_price'];
                if (isset($data['alert_quantity']) && $data['alert_quantity'] !== '') $productData['alert_quantity'] = (int) $data['alert_quantity'];
                if (isset($data['note']) && $data['note'] !== '') $productData['note'] = $data['note'];
                $productData['updated_at'] = now();

                $existing = Product::where('barcode_path', $barcode)->first();

                if ($existing) {
                    // Fetch current stocks if not provided
                    $currentShop = DB::table('stocks')
                        ->where('product_id', $existing->id)
                        ->where('branch_id', 1)
                        ->where('warehouse_id', 1)
                        ->value('qty') ?? 0;
                    
                    $currentWh = DB::table('warehouse_stocks')
                        ->where('product_id', $existing->id)
                        ->where('warehouse_id', $lookups['default_warehouse_id'])
                        ->value('quantity') ?? 0;
                        
                    $finalShopQty = $shopQtyProvided ? $shopQty : $currentShop;
                    $finalWarehouseQty = $warehouseQtyProvided ? $warehouseQty : $currentWh;
                    
                    $productData['initial_stock'] = $finalShopQty + $finalWarehouseQty;

                    $existing->update($productData);
                    $product = $existing;
                    $updated++;
                    
                    $this->syncImportedStocks($product->id, $finalShopQty, $finalWarehouseQty, $lookups['default_warehouse_id']);
                } else {
                    $finalShopQty = $shopQtyProvided ? $shopQty : 0;
                    $finalWarehouseQty = $warehouseQtyProvided ? $warehouseQty : 0;
                    
                    $productData['initial_stock'] = $finalShopQty + $finalWarehouseQty;
                    $productData['creater_id'] = $userId;
                    $productData['barcode_path'] = $barcode;
                    $productData['item_code'] = 'ITEM-' . str_pad((string) $nextItemNumber, 4, '0', STR_PAD_LEFT);
                    $productData['created_at'] = now();
                    
                    $product = Product::create($productData);
                    $nextItemNumber++;
                    $created++;
                    
                    $this->syncImportedStocks($product->id, $finalShopQty, $finalWarehouseQty, $lookups['default_warehouse_id']);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return redirect()->route('product')->with('error', 'Import failed: ' . $e->getMessage());
        }

        fclose($handle);

        $message = "Import complete: {$created} created, {$updated} updated";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }

        return redirect()->route('product')
            ->with('success', $message)
            ->with('import_errors', array_slice($errors, 0, 30));
    }

    private function findProductForBulkUpdate(string $barcode, string $itemName): ?Product
    {
        $barcode = trim($barcode);
        if ($barcode !== '') {
            $product = Product::where('barcode_path', $barcode)->first();
            if ($product) {
                return $product;
            }
            $product = Product::where('item_code', $barcode)->first();
            if ($product) {
                return $product;
            }
        }

        if ($itemName !== '') {
            $normalized = strtolower(trim($itemName));
            $product = Product::whereRaw('LOWER(TRIM(item_name)) = ?', [$normalized])->first();
            if ($product) {
                return $product;
            }
        }

        return null;
    }

    private function buildImportColumnMap(array $headerRow): array
    {
        $aliases = [
            'barcode' => ['barcode', 'bar code'],
            'category_id' => ['category_id', 'category id', 'categoryid'],
            'category' => ['category'],
            'sub_category_id' => ['sub_category_id', 'sub category id', 'subcategory_id', 'subcategory id'],
            'sub_category' => ['sub_category', 'sub-category', 'sub category', 'subcategory'],
            'item_name' => ['item_name', 'item name', 'product_name', 'product name', 'name'],
            'brand' => ['brand', 'brand name'],
            'unit' => ['unit', 'uom'],
            'wholesale_price' => ['wholesale_price', 'wholesale price', 'cost price', 'cost'],
            'retail_price' => ['retail_price', 'retail price', 'price', 'sale price'],
            'shop_qty' => ['shop_qty', 'shop qty', 'shop quantity', 'shop stock', 'stock'],
            'warehouse_qty' => ['warehouse_qty', 'warehouse qty', 'w/h qty', 'wh qty', 'warehouse quantity', 'warehouse stock'],
            'alert_quantity' => ['alert_quantity', 'alert quantity', 'alert qty'],
            'note' => ['note', 'remarks', 'remark'],
        ];

        $map = [];
        foreach ($headerRow as $index => $heading) {
            $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string) $heading)));
            foreach ($aliases as $field => $names) {
                if (in_array($normalized, $names, true)) {
                    $map[$field] = $index;
                }
            }
        }

        return $map;
    }

    private function buildImportLookups(): array
    {
        $categoryMap = [];
        foreach (Category::all() as $category) {
            $categoryMap[$this->normalizeImportKey($category->name)] = (int) $category->id;
        }

        $subCategoryMap = [];
        foreach (Subcategory::all() as $subCategory) {
            $subCategoryMap[$this->normalizeImportKey($subCategory->name)] = [
                'id' => (int) $subCategory->id,
                'category_id' => (int) $subCategory->category_id,
            ];
        }

        return [
            'categories' => $categoryMap,
            'subcategories' => $subCategoryMap,
            'default_warehouse_id' => (int) (DB::table('warehouses')->orderBy('id')->value('id') ?? 1),
        ];
    }

    private function parseImportRow(array $row, array $columnMap): array
    {
        $get = function (string $field) use ($row, $columnMap) {
            if (!isset($columnMap[$field])) {
                return null;
            }
            $value = $row[$columnMap[$field]] ?? null;
            return is_string($value) ? trim($value) : $value;
        };

        return [
            'barcode' => $get('barcode'),
            'category_id' => $get('category_id'),
            'category' => $get('category'),
            'sub_category_id' => $get('sub_category_id'),
            'sub_category' => $get('sub_category'),
            'item_name' => $get('item_name'),
            'brand' => $get('brand'),
            'unit' => $get('unit'),
            'wholesale_price' => $get('wholesale_price'),
            'retail_price' => $get('retail_price'),
            'shop_qty' => $get('shop_qty'),
            'warehouse_qty' => $get('warehouse_qty'),
            'alert_quantity' => $get('alert_quantity'),
            'note' => $get('note'),
        ];
    }

    private function isImportRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function normalizeImportKey(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    private function normalizeImportUnit(?string $unit): string
    {
        $unit = $this->normalizeImportKey((string) $unit);
        if (in_array($unit, ['yard', 'yards'], true)) {
            return 'Yards';
        }
        if ($unit === 'meter') {
            return 'Meter';
        }
        return 'Piece';
    }

    private function resolveImportCategoryId(array $data, array $lookups): ?int
    {
        if (!empty($data['category_id']) && is_numeric($data['category_id'])) {
            return (int) $data['category_id'];
        }
        if (!empty($data['category'])) {
            return $lookups['categories'][$this->normalizeImportKey($data['category'])] ?? null;
        }
        return null;
    }

    private function resolveImportSubCategoryId(array $data, array $lookups, ?int $categoryId): ?int
    {
        if (!empty($data['sub_category_id']) && is_numeric($data['sub_category_id'])) {
            return (int) $data['sub_category_id'];
        }
        if (empty($data['sub_category'])) {
            return null;
        }

        $key = $this->normalizeImportKey($data['sub_category']);
        $sub = $lookups['subcategories'][$key] ?? null;

        if (!$sub) {
            foreach ($lookups['subcategories'] as $name => $info) {
                if (str_contains($name, $key) || str_contains($key, $name)) {
                    $sub = $info;
                    break;
                }
            }
        }

        if (!$sub) {
            return null;
        }

        if ($categoryId && (int) $sub['category_id'] !== (int) $categoryId) {
            return null;
        }

        return (int) $sub['id'];
    }

    private function syncImportedStocks(int $productId, float $shopQty, float $warehouseQty, int $warehouseId): void
    {
        $branchId = 1;
        $shopQtyInt = (int) round($shopQty);
        $warehouseQtyInt = (int) round($warehouseQty);

        $shopRow = DB::table('stocks')
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('warehouse_id', 1)
            ->first();

        if ($shopRow) {
            DB::table('stocks')
                ->where('id', $shopRow->id)
                ->update([
                    'qty' => $shopQtyInt,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('stocks')->insert([
                'branch_id' => $branchId,
                'warehouse_id' => 1,
                'product_id' => $productId,
                'qty' => $shopQtyInt,
                'reserved_qty' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $whIds = DB::table('warehouse_stocks')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('id')
            ->pluck('id');

        if ($whIds->isNotEmpty()) {
            $keepId = $whIds->first();
            if ($whIds->count() > 1) {
                DB::table('warehouse_stocks')->whereIn('id', $whIds->slice(1)->values())->delete();
            }
            DB::table('warehouse_stocks')
                ->where('id', $keepId)
                ->update([
                    'quantity' => $warehouseQtyInt,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('warehouse_stocks')->insert([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => $warehouseQtyInt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // public function searchProducts(Request $request)
    // {
    //     $query = $request->get('q');

    //     \Log::info("Search query: " . $query); // Debug log

    //     $products = Product::where('item_name', 'like', '%' . $query . '%')
    //         ->get(['id', 'item_name', 'item_code', 'retail_price', 'uom', 'measurement', 'unit']);

    //     if ($products->isEmpty()) {
    //         return response()->json(['message' => 'Product not found'], 404);
    //     }

    //     $products = $products->map(function ($product) {
    //         return [
    //             'id' => $product->id,
    //             'name' => $product->item_name,
    //             'code' => $product->item_code,
    //             'price' => $product->retail_price,
    //             'uom' => $product->uom,
    //             'measurement' => $product->measurement,
    //             'unit' => $product->unit,
    //         ];
    //     });

    //     return response()->json($products);
    // }


}
