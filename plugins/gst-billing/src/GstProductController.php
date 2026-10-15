<?php

namespace Plugins\GstBilling\Src;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory\Product;
use Illuminate\Support\Facades\DB;

class GstProductController extends Controller
{
    public function getGst(Product $product)
    {
        $rate = DB::table('gst_product_rates')->where('product_id', $product->id)->first();
        return response()->json($rate);
    }

    public function saveGst(Request $request, Product $product)
    {
        $validated = $request->validate([
            'hsn_sac_code' => 'nullable|string|max:10',
            'gst_rate' => 'required|numeric|min:0|max:100',
            'cess_percent' => 'nullable|numeric|min:0|max:100',
            'uom' => 'nullable|string|max:10',
        ]);

        DB::table('gst_product_rates')->updateOrInsert(
            ['product_id' => $product->id],
            [
                'hsn_sac_code' => $validated['hsn_sac_code'] ?? null,
                'gst_rate' => $validated['gst_rate'],
                'cess_percent' => $validated['cess_percent'] ?? 0,
                'uom' => $validated['uom'] ?? 'NOS',
                'updated_at' => now(),
            ]
        );

        return back()->with('success', 'GST details saved for product.');
    }

    public function bulkSaveGst(Request $request)
    {
        $rates = $request->validate([
            'rates' => 'required|array',
            'rates.*.product_id' => 'required|integer',
            'rates.*.hsn_sac_code' => 'nullable|string|max:10',
            'rates.*.gst_rate' => 'required|numeric|min:0|max:100',
        ]);

        foreach ($rates['rates'] as $rate) {
            DB::table('gst_product_rates')->updateOrInsert(
                ['product_id' => $rate['product_id']],
                [
                    'hsn_sac_code' => $rate['hsn_sac_code'] ?? null,
                    'gst_rate' => $rate['gst_rate'],
                    'updated_at' => now(),
                ]
            );
        }

        return back()->with('success', 'All product GST rates updated successfully.');
    }
}
