<?php

return [
    'super_admin_login_path' => trim(env('SUPER_ADMIN_LOGIN_PATH', 'platform/login'), '/'),
    'contact_email' => env('EDUCORE_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
    'school_onboarding_email' => env('EDUCORE_SCHOOL_ONBOARDING_EMAIL', env('EDUCORE_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com'))),
];
