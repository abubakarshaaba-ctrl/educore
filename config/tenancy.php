<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local central hosts
    |--------------------------------------------------------------------------
    |
    | These hosts are treated as platform-level hosts and must never resolve to
    | a tenant. Keep this local-only for XAMPP development.
    |
    */
    'central_hosts' => array_values(array_unique(array_filter([
        env('TENANT_BASE_DOMAIN', 'educore.test'),
        'educore.test',
        'localhost',
        '127.0.0.1',
    ]))),

    /*
    |--------------------------------------------------------------------------
    | Tenant subdomain base domain
    |--------------------------------------------------------------------------
    |
    | Set TENANT_BASE_DOMAIN in .env to your production domain.
    | e.g. TENANT_BASE_DOMAIN=educoreng.online
    | gives: greenfield.educoreng.online, skyline.educoreng.online, etc.
    |
    | Locally (default): greenfield.educore.test
    |
    */
    'base_domain'       => env('TENANT_BASE_DOMAIN', 'educore.test'),
    'local_base_domain' => env('TENANT_BASE_DOMAIN', 'educore.test'),

    'scheme' => env('TENANT_SCHEME', env('TENANT_LOCAL_SCHEME', 'https')),

    /*
    |--------------------------------------------------------------------------
    | Host-route pattern
    |--------------------------------------------------------------------------
    |
    | The route pattern intentionally excludes central hosts. Unknown hosts are
    | still resolved by TenantHostResolver and will receive the generic
    | unavailable page; tenants are never auto-created from hostnames.
    |
    */
    'host_route_pattern' => '^(?!educore\.test$)(?!localhost$)(?!127\.0\.0\.1$)[A-Za-z0-9][A-Za-z0-9.-]*[A-Za-z0-9]$',

    'reserved_hosts' => [
        'login',
        'logout',
        'register',
        'password',
        'super',
        'admin',
        'administrator',
        'portal',
        'agent',
        'apply',
        'school',
        'schools',
        'student',
        'students',
        'parent',
        'parents',
        'staff',
        'settings',
        'api',
        'storage',
        'assets',
        'vendor',
        'dashboard',
        'home',
        'public',
    ],
];
