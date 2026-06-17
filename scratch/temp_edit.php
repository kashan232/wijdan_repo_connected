    public function editSaleReturn($id)
    {
        $salesReturn = \App\Models\SalesReturn::findOrFail($id);
        $sale = \App\Models\Sale::findOrFail($salesReturn->sale_id);
        $customers = \App\Models\Customer::all();

        // SPLIT SALE ROW
        $products  = array_map('trim', explode(',', $sale->product ?? ''));
        $codes     = array_map('trim', explode(',', $sale->product_code ?? ''));
        $brands    = array_map('trim', explode(',', $sale->brand ?? ''));
        $units     = array_map('trim', explode(',', $sale->unit ?? ''));
        $prices    = array_map('trim', explode(',', $sale->per_price ?? ''));
        $discounts = array_map('trim', explode(',', $sale->per_discount ?? ''));
        $qtys      = array_map('trim', explode(',', $sale->qty ?? ''));
        $totals    = array_map('trim', explode(',', $sale->per_total ?? ''));
        
        $colors_json = json_decode($sale->color ?? '[]', true);
        if (!is_array($colors_json)) {
            $colors_json = [];
        }

        // Fetch all previous returns for this sale from sales_returns table EXCLUDING this current one
        $previousReturns = \DB::table('sales_returns')->where('sale_id', $sale->id)->where('id', '!=', $salesReturn->id)->get();

        $returnedQtyMap = [];
        foreach ($previousReturns as $ret) {
            $retProducts = array_map('trim', explode(',', $ret->product ?? ''));
            $retQtys     = array_map('trim', explode(',', $ret->qty ?? ''));
            foreach ($retProducts as $ri => $rprod) {
                $rqty = isset($retQtys[$ri]) ? floatval($retQtys[$ri]) : 0;
                if ($rqty <= 0) continue;
                if (is_numeric($rprod)) {
                    $keyId = 'id_' . intval($rprod);
                    if (!isset($returnedQtyMap[$keyId])) $returnedQtyMap[$keyId] = 0;
                    $returnedQtyMap[$keyId] += $rqty;
                } else {
                    $keyCode = 'code_' . $rprod;
                    if (!isset($returnedQtyMap[$keyCode])) $returnedQtyMap[$keyCode] = 0;
                    $returnedQtyMap[$keyCode] += $rqty;
                }
            }
        }

        // Parse THIS sales_return to pre-fill
        $thisRetProducts = array_map('trim', explode(',', $salesReturn->product ?? ''));
        $thisRetCodes    = array_map('trim', explode(',', $salesReturn->product_code ?? ''));
        $thisRetQtys     = array_map('trim', explode(',', $salesReturn->qty ?? ''));
        $thisRetPrices   = array_map('trim', explode(',', $salesReturn->per_price ?? ''));
        $thisRetDiscs    = array_map('trim', explode(',', $salesReturn->per_discount ?? ''));
        $thisRetBrands   = array_map('trim', explode(',', $salesReturn->brand ?? ''));
        $thisRetUnits    = array_map('trim', explode(',', $salesReturn->unit ?? ''));
        $thisRetColors   = json_decode($salesReturn->color ?? '[]', true);
        if (!is_array($thisRetColors)) $thisRetColors = [];

        $items = [];
        $returnItems = []; // items to populate the lower table
        
        foreach ($products as $index => $p) {
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

            $note_value = '';
            if (isset($colors_json[$index])) {
                $maybe = $colors_json[$index];
                if (is_string($maybe)) {
                    $try = json_decode($maybe, true);
                    if ($try !== null) {
                        if (is_array($try)) $note_value = implode("\n", $try);
                        else $note_value = (string)$try;
                    } else {
                        $note_value = $maybe;
                    }
                } elseif (is_array($maybe)) {
                    $note_value = implode("\n", $maybe);
                } else {
                    $note_value = (string)$maybe;
                }
            }

            $soldQty = isset($qtys[$index]) && is_numeric($qtys[$index]) ? floatval($qtys[$index]) : 0;
            $returnedQty = 0;
            if ($productIdCandidate) {
                $k = 'id_' . $productIdCandidate;
                if (isset($returnedQtyMap[$k])) {
                    $deduct = min($returnedQtyMap[$k], $soldQty);
                    $returnedQty += $deduct;
                    $returnedQtyMap[$k] -= $deduct; 
                }
            }
            if ($returnedQty == 0 && !empty($itemCodeCandidate)) {
                $kc = 'code_' . $itemCodeCandidate;
                if (isset($returnedQtyMap[$kc])) {
                    $deduct = min($returnedQtyMap[$kc], $soldQty);
                    $returnedQty += $deduct;
                    $returnedQtyMap[$kc] -= $deduct;
                }
            }

            $available = max(0, $soldQty - $returnedQty);

            // Check if this product was returned in THIS return record
            $thisReturnQty = 0;
            $thisReturnPrice = floatval($prices[$index] ?? 0);
            $thisReturnDisc = floatval($discounts[$index] ?? 0);
            $thisReturnNote = $note_value;
            
            // Match by id or code
            foreach ($thisRetProducts as $ri => $rprod) {
                $match = false;
                if ($productIdCandidate && is_numeric($rprod) && intval($rprod) == $productIdCandidate) {
                    $match = true;
                } elseif (!empty($itemCodeCandidate) && isset($thisRetCodes[$ri]) && $thisRetCodes[$ri] == $itemCodeCandidate) {
                    $match = true;
                } elseif ($rprod == $p) {
                    $match = true;
                }
                
                if ($match && floatval($thisRetQtys[$ri] ?? 0) > 0) {
                    $thisReturnQty = floatval($thisRetQtys[$ri]);
                    $thisReturnPrice = floatval($thisRetPrices[$ri] ?? $thisReturnPrice);
                    $thisReturnDisc = floatval($thisRetDiscs[$ri] ?? $thisReturnDisc);
                    
                    // Parse note
                    if (isset($thisRetColors[$ri])) {
                        $maybeNote = $thisRetColors[$ri];
                        if (is_string($maybeNote)) {
                            $tryNote = json_decode($maybeNote, true);
                            if ($tryNote !== null) {
                                if (is_array($tryNote)) $thisReturnNote = implode("\n", $tryNote);
                                else $thisReturnNote = (string)$tryNote;
                            } else {
                                $thisReturnNote = $maybeNote;
                            }
                        } elseif (is_array($maybeNote)) {
                            $thisReturnNote = implode("\n", $maybeNote);
                        } else {
                            $thisReturnNote = (string)$maybeNote;
                        }
                    }
                    
                    // remove it so we don't match it again if there are duplicates (rare but possible)
                    $thisRetQtys[$ri] = 0; 
                    break;
                }
            }

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
                'note'          => $note_value,
                'available_qty' => max(0, $available - $thisReturnQty), // What's actually available left to return AFTER this return
            ];

            if ($thisReturnQty > 0) {
                $returnItems[] = [
                    'product_id'    => $product->id ?? ($productIdCandidate ?? ''),
                    'item_name'     => $product->item_name ?? (string)($p),
                    'item_code'     => $product->item_code ?? ($itemCodeCandidate ?? ''),
                    'brand'         => $thisRetBrands[$ri] ?? ($product->brand->name ?? ($brands[$index] ?? '')),
                    'unit'          => $thisRetUnits[$ri] ?? ($product->unit ?? ($units[$index] ?? '')),
                    'price'         => $thisReturnPrice,
                    'discount'      => $thisReturnDisc,
                    'return_qty'    => $thisReturnQty,
                    'note'          => $thisReturnNote,
                    'available_qty' => max(0, $available - $thisReturnQty),
                ];
            }
        }

        return view('admin_panel.sale.return.edit', [
            'salesReturn' => $salesReturn,
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
            'returnItems' => $returnItems,
        ]);
    }
