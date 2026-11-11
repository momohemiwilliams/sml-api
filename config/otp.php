<?php

return [
    'type' => env('OTP_TYPE', 'numeric'),
    'length' => env('OTP_LENGTH', 6),
    'app_id' => env('OTP_APP_ID'),
    'username' => env('OTP_USERNAME'),
    'password' => env('OTP_PASSWORD'),
    'url' => env('OTP_URL'),
];
