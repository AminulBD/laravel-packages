<?php

return [
    'roots' => [
        'default' => [
            'forced' => false,
            'location' => '/packages',
        ],
    ],

    // this can be array of package ids, callable function that returns array of package ids, or class that implements \AminulBD\Package\Laravel\PackageActivationHandler
    'enabled' => [
        'yourdomain.sample',
    ],
];
