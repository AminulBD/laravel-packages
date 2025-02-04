<?php

return [
    'id' => 'yourdomain.sample',
    'autoload' => [
        'YourDomain\\Sample\\' => 'src/',
    ],
    'provider' => YourDomain\Sample\SampleServiceProvider::class
];