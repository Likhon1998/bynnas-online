<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Optional modules
    |--------------------------------------------------------------------------
    |
    | Bynnas Social is online / social commerce first. The legacy physical
    | retail module (POS terminal, counters & cash sessions, BAKI credit,
    | EMI plans, exchanges, offline POS sales, counter staff reports) is kept
    | in the codebase with its data, but is switched off by default.
    |
    | When disabled, retail routes return 404, retail navigation is hidden,
    | staff no longer need a counter / opening cash, and the midnight
    | counter auto-close schedule is not registered.
    |
    */

    'retail' => [
        'enabled' => (bool) env('RETAIL_MODULE_ENABLED', false),
    ],

];
