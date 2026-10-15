<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('gst_settings')) {
    Schema::create('gst_settings', function (Blueprint $table) {
        $table->id();
        $table->string('gstin', 15)->nullable();
        $table->string('legal_name')->nullable();
        $table->string('trade_name')->nullable();
        $table->string('state_code', 2)->nullable();
        $table->text('registered_address')->nullable();
        $table->string('bank_name')->nullable();
        $table->string('bank_account_no')->nullable();
        $table->string('bank_ifsc')->nullable();
        $table->string('bank_branch')->nullable();
        $table->string('upi_id')->nullable();
        $table->boolean('is_composition_scheme')->default(false);
        $table->string('invoice_prefix')->default('INV');
        $table->string('invoice_item_grouping')->default('matrix'); // 'matrix' or 'itemized'
        $table->text('terms_conditions')->nullable();
        $table->timestamps();
    });
}

if (Schema::hasTable('gst_settings') && !Schema::hasColumn('gst_settings', 'invoice_item_grouping')) {
    Schema::table('gst_settings', function (Blueprint $table) {
        $table->string('invoice_item_grouping')->default('matrix');
    });
}

if (!Schema::hasTable('gst_product_rates')) {
    Schema::create('gst_product_rates', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('product_id');
        $table->unsignedBigInteger('variant_id')->nullable();
        $table->string('hsn_sac_code')->nullable();
        $table->decimal('gst_rate', 5, 2)->default(0); // 0, 5, 12, 18, 28
        $table->decimal('cess_percent', 5, 2)->default(0);
        $table->string('uom')->nullable(); // NOS, PCS, KGS, etc.
        $table->timestamps();

        $table->index('product_id');
        $table->index('variant_id');
    });
}

if (!Schema::hasTable('gst_customer_profiles')) {
    Schema::create('gst_customer_profiles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('customer_id');
        $table->string('gstin', 15)->nullable();
        $table->string('trade_name')->nullable();
        $table->string('state_code', 2)->nullable();
        $table->string('registration_type')->default('Unregistered'); // Regular, Composition, Unregistered, SEZ
        $table->timestamps();

        $table->index('customer_id');
    });
}

if (!Schema::hasTable('gst_invoices')) {
    Schema::create('gst_invoices', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->string('invoice_number')->unique();
        $table->date('invoice_date');
        $table->string('place_of_supply', 2)->nullable();
        $table->decimal('taxable_value', 15, 2)->default(0);
        $table->decimal('cgst_amount', 15, 2)->default(0);
        $table->decimal('sgst_amount', 15, 2)->default(0);
        $table->decimal('igst_amount', 15, 2)->default(0);
        $table->decimal('cess_amount', 15, 2)->default(0);
        $table->decimal('round_off', 5, 2)->default(0);
        $table->decimal('total_amount', 15, 2)->default(0);
        $table->json('hsn_summary')->nullable();
        $table->timestamps();

        $table->index('order_id');
    });
}
