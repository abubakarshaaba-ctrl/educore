<?php

return [
    // Small requests survive unreliable connections and common proxy timeouts.
    'upload_chunk_size' => (int) env('ACADEMIC_REPOSITORY_CHUNK_SIZE', 2 * 1024 * 1024),

    'max_upload_size' => (int) env('ACADEMIC_REPOSITORY_MAX_UPLOAD_SIZE', 2 * 1024 * 1024 * 1024),

    // Incomplete uploads can be resumed after a browser refresh or reconnect.
    'upload_expiry_hours' => (int) env('ACADEMIC_REPOSITORY_UPLOAD_EXPIRY_HOURS', 48),
];
