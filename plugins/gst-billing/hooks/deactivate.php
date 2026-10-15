<?php

use Illuminate\Support\Facades\Cache;

// Clean up cached settings and temporary calculation caches
Cache::forget('gst_settings');
