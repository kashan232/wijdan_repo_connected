<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class DiscountController extends Controller
{
    // Discount List Page
    public function index()
    {
        $discounts = ProductDiscount::with('product.category_relation', 'product.sub_category_relation', 'product.unit', 'product.brand')
            ->orderByDesc('id')->get();

        return view('admin_panel.product.discount.discount_index', compact('discounts'));
    }

    // Show Create Discount Page
    public function create(Request $request)
    {
        $productIds = $request->products ? explode(',', $request->products) : [];
        $products = Product::with(['category_relation', 'sub_category_relation', 'unit', 'brand', 'stock'])
            ->whereIn('id', $productIds)->get();

        return view('admin_panel.product.discount.discount_create', compact('products'));
    }

    /**
     * Generate a 6-digit code (leading zeros preserved).
     */
    private function makeSixDigitCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get a unique 6-digit code with small retry loop.
     */
    private function generateUniqueDiscountCode(int $maxRetries = 5): string
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $code = $this->makeSixDigitCode();
            if (!ProductDiscount::where('discount_code', $code)->exists()) {
                return $code;
            }
        }
        // final attempt even if exists-check collided; insert will catch if duplicate
        return $this->makeSixDigitCode();
    }

    // Store Discount
    public function store(Request $request)
    {
        
        $request->validate([
            'product_id.*'          => ['required','integer','exists:products,id'],
            'discount_percentage.*' => ['nullable','numeric','min:0','max:100'],
            'discount_amount.*'     => ['nullable','numeric','min:0'],
            'date.*'                => ['required','date'],
            'status.*'              => ['required','in:0,1'],
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->product_id as $key => $productId) {
                $product = Product::findOrFail($productId);

                $discountPercentage = (float)($request->discount_percentage[$key] ?? 0);
                $discountAmount     = (float)($request->discount_amount[$key] ?? 0);
                $status             = (int)($request->status[$key] ?? 1);
                $date               = $request->date[$key];

                $percDiscount  = round($product->price * $discountPercentage / 100, 2);
                $totalDiscount = round($percDiscount + $discountAmount, 2);

                if ($totalDiscount > $product->price) {
                    return back()
                        ->withErrors([
                            "discount_percentage.$key" => "Total discount exceeds original price for '{$product->item_name}'.",
                            "discount_amount.$key"     => "Total discount exceeds original price for '{$product->item_name}'.",
                        ])
                        ->withInput();
                }

                $finalPrice = round($product->price - $totalDiscount, 2);

                // retry on rare unique collision at DB level
                $retries = 0;
                while (true) {
                    try {
                        ProductDiscount::updateOrCreate(
                            ['product_id' => $productId],
                            [
                                'discount_code'       => !empty($product->barcode_path) ? $product->barcode_path : $this->generateUniqueDiscountCode(),
                                'actual_price'        => $product->price,
                                'discount_percentage' => $discountPercentage,
                                'discount_amount'     => $discountAmount,
                                'total_discount'      => $totalDiscount,
                                'final_price'         => $finalPrice,
                                'date'                => $date,
                                'status'              => $status,
                            ]
                        );
                        break;
                    } catch (QueryException $e) {
                        // Duplicate code? retry few times.
                        $isDuplicate = str_contains($e->getMessage(), 'Duplicate entry') // MySQL
                                   || ($e->errorInfo[0] ?? null) === '23505';             // Postgres
                        if ($isDuplicate && $retries < 5) {
                            $retries++;
                            continue;
                        }
                        throw $e;
                    }
                }
            }

            DB::commit();
            return redirect()->route('discount.index')->with('success', 'Discounts saved successfully with unique 6-digit barcodes.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->withErrors('Failed to save discounts: '.$th->getMessage())->withInput();
        }
    }

    // Toggle Status Active/Inactive
    public function toggleStatus($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        $discount->status = !$discount->status;
        $discount->save();

        return redirect()->back()->with('success', 'Discount status updated.');
    }

    // Discount Barcode Page
    public function barcode($id)
    {
        $discount = ProductDiscount::with('product')->findOrFail($id);
        return view('admin_panel.product.discount.discount_barcode', compact('discount'));
    }
}
