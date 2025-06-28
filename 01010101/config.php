<?php
return [
    'database' => [
        'host' => 'localhost',
        'name' => 'study_tracker',
        'user' => 'root',  // یا نام کاربری MySQL شما
        'pass' => '',      // رمز عبور MySQL شما
        'charset' => 'utf8mb4'
    ],
    'jwt' => [
        'secret' => 'your-secret-key-here-change-this',
        'expire' => 86400 // 24 hours
    ],
    'app' => [
        'name' => 'Study Tracker',
        'url' => 'http://localhost:8000',
        'timezone' => 'Asia/Tehran'
    ]
];
