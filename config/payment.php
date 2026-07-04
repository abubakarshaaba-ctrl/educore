<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Currency
    |--------------------------------------------------------------------------
    | All financial transactions on this platform use this currency.
    | Change here to affect the entire application.
    */
    'currency'      => env('PLATFORM_CURRENCY', 'NGN'),
    'currency_symbol' => env('PLATFORM_CURRENCY_SYMBOL', '₦'),
    'currency_name' => env('PLATFORM_CURRENCY_NAME', 'Nigerian Naira'),

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans (display pricing for marketing pages)
    |--------------------------------------------------------------------------
    | Actual billing amounts are stored in the subscription_plans database table.
    | These values are used only on the public marketing/landing pages.
    | Update the database records via Super Admin → Plans & Pricing for billing.
    */
    'plans' => [
        'basic' => [
            'name'          => 'Basic',
            'monthly_price' => env('PLAN_BASIC_MONTHLY', 10000),
            'annual_price'  => env('PLAN_BASIC_ANNUAL', 100000),
            'max_students'  => env('PLAN_BASIC_MAX_STUDENTS', 200),
            'max_staff'     => env('PLAN_BASIC_MAX_STAFF', 20),
        ],
        'standard' => [
            'name'          => 'Standard',
            'monthly_price' => env('PLAN_STANDARD_MONTHLY', 30000),
            'annual_price'  => env('PLAN_STANDARD_ANNUAL', 300000),
            'max_students'  => env('PLAN_STANDARD_MAX_STUDENTS', 500),
            'max_staff'     => env('PLAN_STANDARD_MAX_STAFF', 50),
        ],
        'premium' => [
            'name'          => 'Premium',
            'monthly_price' => env('PLAN_PREMIUM_MONTHLY', 45000),
            'annual_price'  => env('PLAN_PREMIUM_ANNUAL', 450000),
            'max_students'  => env('PLAN_PREMIUM_MAX_STUDENTS', 2000),
            'max_staff'     => env('PLAN_PREMIUM_MAX_STAFF', 200),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Payment Gateways
    |--------------------------------------------------------------------------
    | These are the gateways the platform supports. Each tenant configures
    | their own keys via the school settings panel.
    */
    'gateways' => ['paystack', 'monnify', 'flutterwave'],

    /*
    |--------------------------------------------------------------------------
    | Platform Commission / Agent Rates
    |--------------------------------------------------------------------------
    */
    'agent_default_commission_rate' => env('AGENT_DEFAULT_COMMISSION_RATE', 10), // percent
];
