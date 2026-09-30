<?php

return [
    // S3 disk used for video uploads / playback + certificate PDFs.
    'video_disk' => env('CONTENT_VIDEO_DISK', 's3'),

    // Temporary signed URL lifetime in seconds.
    'signed_url_ttl' => (int) env('CONTENT_SIGNED_URL_TTL', 3600),
];
