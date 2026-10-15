<?php

use Illuminate\Support\Facades\Schema;

// Safely drop plugin-created tables upon plugin deletion
Schema::dropIfExists('gst_invoices');
Schema::dropIfExists('gst_customer_profiles');
Schema::dropIfExists('gst_product_rates');
Schema::dropIfExists('gst_settings');
