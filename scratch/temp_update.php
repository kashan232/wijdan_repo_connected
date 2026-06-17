    public function updateSaleReturn(Request $request, $id)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'product'    => 'required|array',
            'product.*'  => 'nullable|string',
            'qty'        => 'required|array',
            'qty.*'      => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $salesReturn = \App\Models\SalesReturn::findOrFail($id);
            $sale = \App\Models\Sale::findOrFail($request->sale_id);

            // 1. REVERT PREVIOUS STOCK
            $oldProducts = explode(',', $salesReturn->product ?? '');
            $oldCodes    = explode(',', $salesReturn->product_code ?? '');
            $oldQtys     = explode(',', $salesReturn->qty ?? '');
            
            foreach ($oldProducts as $i => $oldProd) {
                $oQty = isset($oldQtys[$i]) ? floatval(trim($oldQtys[$i])) : 0;
                if ($oQty <= 0) continue;
                
                $oName = trim($oldProd);
                $oCode = isset($oldCodes[$i]) ? trim($oldCodes[$i]) : '';
                
                $foundProduct = null;
                if (is_numeric($oName)) {
                    $foundProduct = \App\Models\Product::find(intval($oName));
                }
                if (!$foundProduct && $oCode) {
                    $foundProduct = \App\Models\Product::where('item_code', $oCode)->first();
                }
                if (!$foundProduct && $oName) {
                    $foundProduct = \App\Models\Product::where('item_name', $oName)->first();
                }

                if ($foundProduct) {
                    $stockQuery = \App\Models\Stock::where('product_id', $foundProduct->id);
                    if (!empty($sale->warehouse_id)) {
                        $stockQuery->where('warehouse_id', $sale->warehouse_id);
                    }
                    $stockRow = $stockQuery->first();
                    if ($stockRow) {
                        $stockRow->qty -= $oQty; // Revert: reduce stock
                        if ($stockRow->qty < 0) $stockRow->qty = 0; // Prevent negative
                        $stockRow->save();
                    }
                }
            }

            // 2. PARSE NEW ARRAYS
            $product_names = $request->input('product', []);
            $product_ids   = $request->input('product_id', []);
            $product_codes = $request->input('item_code', []);
            $brands        = $request->input('brand', $request->input('uom', []));
            $units         = $request->input('unit', []);
            $prices        = $request->input('price', []);
            $discounts     = $request->input('item_disc', []);
            $quantities    = $request->input('qty', []);
            $totals        = $request->input('total', []);
            $colors        = $request->input('color', []);

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

            $rows = max(count($product_names), count($product_codes), count($quantities), count($prices));

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

                if ($qty <= 0) continue;

                $combined_products[]  = $name;
                $combined_codes[]     = $code;
                $combined_brands[]    = $brand;
                $combined_units[]     = $unit;
                $combined_prices[]    = (string)$price;
                $combined_discounts[] = (string)$disc;
                $combined_qtys[]      = (string)$qty;
                $combined_totals[]    = (string)$total;

                if (is_array($colorRaw)) {
                    $combined_colors[] = json_encode($colorRaw);
                } else {
                    $decoded = null;
                    if (is_string($colorRaw)) $decoded = json_decode($colorRaw, true);
                    if (is_array($decoded)) $combined_colors[] = json_encode($decoded);
                    elseif (!empty($colorRaw)) $combined_colors[] = json_encode([$colorRaw]);
                    else $combined_colors[] = json_encode([]);
                }

                // 3. NEW STOCK UPDATE (Add stock back)
                $foundProduct = null;
                if ($pid !== '') {
                    if (is_numeric($pid)) $foundProduct = \App\Models\Product::find(intval($pid));
                    else {
                        $maybe = \App\Models\Product::find($pid);
                        if (!$maybe && $code) $maybe = \App\Models\Product::where('item_code', $code)->first();
                        $foundProduct = $maybe;
                    }
                } else if (!empty($code)) {
                    $foundProduct = \App\Models\Product::where('item_code', $code)->first();
                } else if (!empty($name)) {
                    $foundProduct = \App\Models\Product::where('item_name', $name)->first();
                }

                if ($foundProduct) {
                    $stockQuery = \App\Models\Stock::where('product_id', $foundProduct->id);
                    if (!empty($sale->warehouse_id)) {
                        $stockQuery->where('warehouse_id', $sale->warehouse_id);
                    }
                    $stockRow = $stockQuery->first();
                    if ($stockRow) {
                        $stockRow->qty += $qty;
                        $stockRow->save();
                    } else {
                        \App\Models\Stock::create([
                            'product_id' => $foundProduct->id,
                            'qty' => $qty,
                            'warehouse_id' => $sale->warehouse_id,
                            'branch_id' => $sale->branch_id ?? 1
                        ]);
                    }
                }

                $total_items += $qty;
            }

            // 4. UPDATE SALES_RETURNS TABLE
            $salesReturn->customer          = $request->customer;
            $salesReturn->reference         = $request->reference;
            $salesReturn->product           = implode(',', $combined_products);
            $salesReturn->product_code      = implode(',', $combined_codes);
            $salesReturn->brand             = implode(',', $combined_brands);
            $salesReturn->unit              = implode(',', $combined_units);
            $salesReturn->per_price         = implode(',', $combined_prices);
            $salesReturn->per_discount      = implode(',', $combined_discounts);
            $salesReturn->qty               = implode(',', $combined_qtys);
            $salesReturn->per_total         = implode(',', $combined_totals);
            $salesReturn->color             = json_encode($combined_colors);

            $salesReturn->total_amount_Words   = $request->total_amount_Words;
            $salesReturn->total_bill_amount    = $request->total_subtotal;
            $salesReturn->total_extradiscount  = $request->total_extra_cost;
            $salesReturn->total_net            = $request->total_net;

            $salesReturn->cash                 = $request->cash;
            $salesReturn->card                 = $request->card;
            $salesReturn->change               = $request->change;

            $salesReturn->total_items          = $total_items;
            $salesReturn->return_note          = $request->return_note;

            $salesReturn->save();

            DB::commit();

            return redirect()->route('sale.returns.index')->with('success', 'Sale return updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Sale return update failed: ' . $e->getMessage());
        }
    }
