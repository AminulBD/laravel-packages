<?php

return [
    'id' => 'yourdomain.sample',
    'autoload' => [
        'YourDomain\\Sample\\' => 'src/',
        'YourDomain\\Sample\\Seeders\\' => 'seeders/',
        'YourDomain\\Sample\\Factories\\' => 'factories/',
    ],
    'provider' => YourDomain\Sample\SampleServiceProvider::class
];