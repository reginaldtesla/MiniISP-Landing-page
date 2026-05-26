<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom “pay what you have” data (Non-Overlap Policy: 2–5 GB band)
    |--------------------------------------------------------------------------
    |
    | Students enter a GHS amount; allocated data = amount / rate_per_gb.
    | Result must fall between min_gb and max_gb (avoids overlapping fixed packs).
    |
    */

    'pool_total_gb' => (int) env('CUSTOM_DATA_POOL_GB', 217),

    'raw_cost_per_gb' => (float) env('CUSTOM_DATA_RAW_COST_PER_GB', 1.84),

    'rate_per_gb' => (float) env('CUSTOM_DATA_RATE_PER_GB', 4.50),

    'min_gb' => (float) env('CUSTOM_DATA_MIN_GB', 2),

    'max_gb' => (float) env('CUSTOM_DATA_MAX_GB', 5),

    'speed_mbps' => (int) env('CUSTOM_DATA_SPEED_MBPS', 60),

    // UI range for the slider (GHS).
    'slider_min_amount' => (float) env('CUSTOM_DATA_SLIDER_MIN_AMOUNT', 1),
    'slider_max_amount' => (float) env('CUSTOM_DATA_SLIDER_MAX_AMOUNT', 100),

    // Minimum data granted for any successful custom purchase (GB).
    'min_allocatable_gb' => (float) env('CUSTOM_DATA_MIN_ALLOCATABLE_GB', 0.01),

];
