<?php

return [
    // Only meaningful on a LOCAL/LAN instance: where to push finished exam
    // sessions once this machine regains internet access.
    'cloud_url' => env('CBT_LAN_CLOUD_URL', 'https://educoreng.online'),
];
