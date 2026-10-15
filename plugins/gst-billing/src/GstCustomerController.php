<?php

namespace Plugins\GstBilling\Src;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class GstCustomerController extends Controller
{
    public function getGst(Customer $customer)
    {
        $profile = DB::table('gst_customer_profiles')->where('customer_id', $customer->id)->first();
        return response()->json([
            'profile' => $profile,
            'stateCodes' => GstStateCodes::getAll(),
        ]);
    }

    public function saveGst(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'gstin' => 'nullable|string|max:15',
            'trade_name' => 'nullable|string|max:255',
            'state_code' => 'required|string|max:2',
            'registration_type' => 'required|string|in:Regular,Composition,Unregistered,SEZ',
        ]);

        DB::table('gst_customer_profiles')->updateOrInsert(
            ['customer_id' => $customer->id],
            [
                'gstin' => $validated['gstin'] ?? null,
                'trade_name' => $validated['trade_name'] ?? null,
                'state_code' => $validated['state_code'],
                'registration_type' => $validated['registration_type'],
                'updated_at' => now(),
            ]
        );

        return back()->with('success', 'Customer GST profile saved.');
    }
}
