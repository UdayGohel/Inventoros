<?php

require_once __DIR__ . '/src/GstStateCodes.php';
require_once __DIR__ . '/src/GstTaxEngine.php';
require_once __DIR__ . '/src/GstNumberToWords.php';
require_once __DIR__ . '/src/GstInvoiceSequenceService.php';
require_once __DIR__ . '/src/GstPdfService.php';
require_once __DIR__ . '/src/GstInvoiceController.php';
require_once __DIR__ . '/src/GstSettingsController.php';
require_once __DIR__ . '/src/GstProductController.php';
require_once __DIR__ . '/src/GstCustomerController.php';

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

return function (string $slug, array $manifest): void {
    // 1. Register blade view namespace
    View::addNamespace($slug, __DIR__ . '/views');

    // 2. Register Navigation Menu Items
    register_menu_item([
        'label' => 'GST Invoicing',
        'icon' => 'FileText',
        'route' => 'plg.gst-billing.invoices', // Default route for the parent
        'permission' => ['view_orders', 'edit_products', 'view_reports', 'edit_settings'],
        'position' => 60,
        'submenu' => [
            ['label' => 'Invoices', 'route' => 'plg.gst-billing.invoices', 'permission' => 'view_orders', 'position' => 10],
            ['label' => 'HSN & Tax Slabs', 'route' => 'plg.gst-billing.hsn', 'permission' => 'edit_products', 'position' => 20],
            ['label' => 'GSTR Reports', 'route' => 'plg.gst-billing.reports', 'permission' => 'view_reports', 'position' => 30],
            ['label' => 'GST Settings', 'route' => 'plg.gst-billing.settings', 'permission' => 'edit_settings', 'position' => 40],
        ]
    ]);

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
        Route::get('customers/{customer}/gst', [\Plugins\GstBilling\Src\GstCustomerController::class, 'getGst'])->name('plg.' . $slug . '.customers.get_gst')->middleware('permission:view_customers');
        Route::post('customers/{customer}/gst', [\Plugins\GstBilling\Src\GstCustomerController::class, 'saveGst'])->name('plg.' . $slug . '.customers.gst')->middleware('permission:edit_customers');
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

    add_page_component('customers.show', 'actions', [
        'plugin' => $slug,
        'component' => 'CustomerGstProfile',
        'position' => 10,
    ]);

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
