<?php

return [
    'override' => env('WEBMAIL_URL_OVERRIDE'),

    'map' => [
        'gmail.com' => 'https://mail.google.com/',
        'yahoo.co.jp' => 'https://mail.yahoo.co.jp/',
        'outlook.com' => 'https://outlook.live.com/mail/',
        'icloud.com' => 'https://www.icloud.com/mail',
    ],

    'fallback' => 'https://mail.google.com/',
];
