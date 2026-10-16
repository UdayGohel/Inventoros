<?php

require_once __DIR__ . '/src/GstStateCodes.php';
require_once __DIR__ . '/src/GstTaxEngine.php';
require_once __DIR__ . '/src/GstNumberToWords.php';
require_once __DIR__ . '/src/GstInvoiceSequenceService.php';
require_once __DIR__ . '/src/GstPdfService.php';
require_once __DIR__ . '/src/GstInvoiceController.php';
require_once __DIR__ . '/src/GstSettingsController.php';
require_once __DIR__ . '/src/GstProductController.php';
require_once __DIR__ . '/src/GstCustomerProfile.php';

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

return function (string $slug, array $manifest): void {
    // 1. Register blade view namespace
    View::addNamespace($slug, __DIR__ . '/views');

    // 2. Register Navigation Menu Items
    // The sidebar renders top-level items only (no submenus), so each page is its own item.
    foreach ([
        ['label' => 'GST Invoices', 'route' => 'plg.gst-billing.invoices', 'permission' => 'view_orders'],
        ['label' => 'HSN & Tax Slabs', 'route' => 'plg.gst-billing.hsn', 'permission' => 'edit_products'],
        ['label' => 'GSTR Reports', 'route' => 'plg.gst-billing.reports', 'permission' => 'view_reports'],
        ['label' => 'GST Settings', 'route' => 'plg.gst-billing.settings', 'permission' => 'edit_settings'],
    ] as $i => $item) {
        register_menu_item($item + ['icon' => 'FileText', 'position' => 60 + $i]);
    }

    // 3. Register Custom Routes
    // Plugin Pages (Inertia UI)
    register_page('plg.gst-billing.invoices', 'Plugin::gst-billing/Invoices', ['permission' => 'view_orders']);
    register_page('plg.gst-billing.hsn', 'Plugin::gst-billing/HsnList', [
        'title' => 'HSN & Tax Slabs',
        'permission' => 'edit_products',
        'props' => fn () => [
            'products' => \App\Models\Inventory\Product::query()
                ->leftJoin('gst_product_rates', 'products.id', '=', 'gst_product_rates.product_id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.sku',
                    'products.price',
                    'products.currency',
                    'gst_product_rates.hsn_sac_code',
                    'gst_product_rates.gst_rate'
                )
                ->orderBy('products.name')
                ->get(),
        ],
    ]);
    register_page('plg.gst-billing.reports', 'Plugin::gst-billing/Reports', ['permission' => 'view_reports']);
    register_page('plg.gst-billing.settings', 'Plugin::gst-billing/Settings', [
        'title' => 'GST Settings',
        'permission' => 'edit_settings',
        'props' => fn () => [
            'settings' => \Illuminate\Support\Facades\DB::table('gst_settings')
                ->where('organization_id', app(\App\Support\Tenancy\OrganizationContext::class)->id())
                ->first() ?? (object) [
                'gstin' => '',
                'legal_name' => '',
                'trade_name' => '',
                'state_code' => '24',
                'registered_address' => '',
                'bank_name' => '',
                'bank_account_no' => '',
                'bank_ifsc' => '',
                'bank_branch' => '',
                'upi_id' => '',
                'is_composition_scheme' => false,
                'invoice_prefix' => 'INV',
                'invoice_item_grouping' => 'matrix',
                'terms_conditions' => '',
            ],
            'stateCodes' => \Plugins\GstBilling\Src\GstStateCodes::getAll(),
        ],
    ]);

    // Backend API/Action Routes under /p/{slug}
    Route::middleware(['web', 'auth'])->prefix('p/' . $slug)->group(function () use ($slug) {
        Route::get('orders/{order}/invoice', [\Plugins\GstBilling\Src\GstInvoiceController::class, 'downloadInvoice'])->name('plg.' . $slug . '.orders.invoice')->middleware('permission:view_orders');
        Route::get('orders/{order}/invoice/preview', [\Plugins\GstBilling\Src\GstInvoiceController::class, 'previewInvoice'])->name('plg.' . $slug . '.orders.invoice.preview')->middleware('permission:view_orders');
        Route::get('orders/{order}/breakdown', [\Plugins\GstBilling\Src\GstInvoiceController::class, 'getBreakdown'])->name('plg.' . $slug . '.orders.breakdown')->middleware('permission:view_orders');
        Route::post('orders/{order}/generate', [\Plugins\GstBilling\Src\GstInvoiceController::class, 'generateInvoice'])->name('plg.' . $slug . '.orders.generate')->middleware('permission:edit_orders');
        
        Route::post('settings', [\Plugins\GstBilling\Src\GstSettingsController::class, 'saveSettings'])->name('plg.' . $slug . '.settings.save')->middleware('permission:edit_settings');
        
        Route::get('products/{product}/gst', [\Plugins\GstBilling\Src\GstProductController::class, 'getGst'])->name('plg.' . $slug . '.products.get_gst')->middleware('permission:view_products');
        Route::post('products/{product}/gst', [\Plugins\GstBilling\Src\GstProductController::class, 'saveGst'])->name('plg.' . $slug . '.products.gst')->middleware('permission:edit_products');
        Route::post('products/bulk-gst', [\Plugins\GstBilling\Src\GstProductController::class, 'bulkSaveGst'])->name('plg.' . $slug . '.products.bulk_gst')->middleware('permission:edit_products');
    });

    // 4. Inject UI slot components
    add_page_component('orders.show', 'actions', [
        'plugin' => $slug,
        'component' => 'OrderInvoiceAction',
        'position' => 10,
    ]);

    add_page_component('orders.show', 'sidebar', [
        'plugin' => $slug,
        'component' => 'OrderGstBreakdown',
        'position' => 20,
    ]);

    // Customer GST profile: fields in the customer form's Business Details card,
    // saved with the customer, and shown in the same card on the customer page.
    add_page_component('customers.create', 'business-details', [
        'plugin' => $slug,
        'component' => 'CustomerGstFields',
        'permission' => 'create_customers',
        'data' => fn () => ['profile' => null, 'stateCodes' => \Plugins\GstBilling\Src\GstStateCodes::getAll()],
    ]);

    add_page_component('customers.edit', 'business-details', [
        'plugin' => $slug,
        'component' => 'CustomerGstFields',
        'permission' => 'edit_customers',
        'data' => fn () => [
            'profile' => \Plugins\GstBilling\Src\GstCustomerProfile::find(request()->route('customer')?->id),
            'stateCodes' => \Plugins\GstBilling\Src\GstStateCodes::getAll(),
        ],
    ]);

    add_page_component('customers.show', 'business-details', [
        'plugin' => $slug,
        'component' => 'CustomerGstDetails',
        'permission' => 'view_customers',
        'data' => function () {
            $profile = \Plugins\GstBilling\Src\GstCustomerProfile::find(request()->route('customer')?->id);

            return [
                'profile' => $profile,
                'stateName' => $profile?->state_code ? \Plugins\GstBilling\Src\GstStateCodes::getName($profile->state_code) : null,
            ];
        },
    ]);

    $gstRules = function (array $rules) {
        // Load the validation lines first: adding to a group that is not loaded yet
        // would stop Laravel loading its own messages for this request.
        app('translator')->get('validation.attributes');
        app('translator')->addLines(\Plugins\GstBilling\Src\GstCustomerProfile::attributeNames(), app()->getLocale());

        return $rules + \Plugins\GstBilling\Src\GstCustomerProfile::rules();
    };
    add_filter('customer_store_validation_rules', $gstRules);
    add_filter('customer_update_validation_rules', $gstRules);

    $saveGstProfile = fn ($customer, $request) => \Plugins\GstBilling\Src\GstCustomerProfile::saveFromRequest($customer->id, $request->validated());
    add_action('customer_after_create', $saveGstProfile);
    add_action('customer_after_update', $saveGstProfile);

    add_page_component('products.show', 'sidebar', [
        'plugin' => $slug,
        'component' => 'ProductGstBadge',
        'position' => 20,
    ]);

    add_page_component('products.create', 'after-form', [
        'plugin' => $slug,
        'component' => 'ProductGstForm',
        'position' => 20,
    ]);

    add_page_component('products.edit', 'after-form', [
        'plugin' => $slug,
        'component' => 'ProductGstForm',
        'position' => 20,
    ]);
};
