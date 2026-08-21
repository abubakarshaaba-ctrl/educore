<?php

return [
    // Only meaningful on a LOCAL/LAN instance: where to push finished exam
    // sessions once this machine regains internet access.
    'cloud_url' => env('CBT_LAN_CLOUD_URL', 'https://educoreng.online'),

    // Passwordless admission-number access is only exposed when a current
    // package has been imported from a private/local network host.
    'admission_number_login' => env('CBT_LAN_ADMISSION_LOGIN', true),
];
