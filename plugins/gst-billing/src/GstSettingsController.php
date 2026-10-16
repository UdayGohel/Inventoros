<?php

namespace Plugins\GstBilling\Src;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GstSettingsController extends Controller
{
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'gstin' => 'nullable|string|max:15',
            'legal_name' => 'nullable|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'state_code' => 'nullable|string|max:2',
            'registered_address' => 'nullable|string',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:20',
            'bank_branch' => 'nullable|string|max:255',
            'upi_id' => 'nullable|string|max:100',
            'invoice_prefix' => 'required|string|max:10',
            'invoice_item_grouping' => 'nullable|string|in:matrix,itemized',
            'is_composition_scheme' => 'boolean',
            'terms_conditions' => 'nullable|string',
        ]);

        $organizationId = app(\App\Support\Tenancy\OrganizationContext::class)->id();
        $settings = \Illuminate\Support\Facades\DB::table('gst_settings')->where('organization_id', $organizationId)->first();

        if ($settings) {
            \Illuminate\Support\Facades\DB::table('gst_settings')
                ->where('id', $settings->id)
                ->update(array_merge($validated, ['updated_at' => now()]));
        } else {
            \Illuminate\Support\Facades\DB::table('gst_settings')->insert(
                array_merge($validated, [
                    'organization_id' => $organizationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        return back()->with('success', 'GST Settings updated successfully.');
    }
}
