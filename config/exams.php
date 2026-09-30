<?php

return [
    // Grace period (seconds) allowing late submission after duration ends.
    'attempt_grace_seconds' => (int) env('EXAM_ATTEMPT_GRACE_SECONDS', 60),
];
