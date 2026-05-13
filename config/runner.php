<?php

declare(strict_types=1);

// Single-user defaults; replaced by per-user `runner_profiles` rows in v1.x.
return [
    'resting_hr' => 55,
    'max_hr' => 180,

    // Inclusive lo / exclusive hi (bpm). Edwards TRIMP weights each minute in zone N by N.
    'hr_zones' => [
        'Z1' => ['lo' => 116, 'hi' => 137],
        'Z2' => ['lo' => 138, 'hi' => 153],
        'Z3' => ['lo' => 154, 'hi' => 167],
        'Z4' => ['lo' => 168, 'hi' => 175],
        'Z5' => ['lo' => 176, 'hi' => 999],
    ],

    'optimal_cadence_spm' => 170,
];
