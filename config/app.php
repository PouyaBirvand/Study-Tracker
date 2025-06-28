<?php
/**
 * Application Configuration
 * تنظیمات اپلیکیشن
 */

return [
    'name' => 'Study Tracker API',
    'version' => '1.0.0',
    'debug' => true,
    'timezone' => 'Asia/Tehran',
    
    // JWT Settings
    'jwt' => [
        'secret' => 'your-super-secret-jwt-key-here',
        'algorithm' => 'HS256',
        'expiration' => 86400 * 7, // 7 days
    ],
    
    // Password Settings
    'password' => [
        'min_length' => 6,
        'require_uppercase' => false,
        'require_lowercase' => false,
        'require_numbers' => false,
        'require_symbols' => false,
    ],
    
    // Pagination
    'pagination' => [
        'default_limit' => 20,
        'max_limit' => 100,
    ],
    
    // File Upload
    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'path' => __DIR__ . '/../public/uploads/',
        'url' => '/uploads/',
    ],
    
    // Email Settings (for password reset)
    'mail' => [
        'driver' => 'smtp',
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-password',
        'encryption' => 'tls',
        'from' => [
            'address' => 'noreply@studytracker.com',
            'name' => 'Study Tracker'
        ]
    ],
    
    // Gamification Settings
    'gamification' => [
        'points' => [
            'session_start' => 10,
            'session_complete' => 20,
            'productivity_bonus' => 5, // per productivity point above 7
            'daily_goal' => 50,
            'weekly_streak' => 100,
            'achievement' => 200,
        ],
        'levels' => [
            1 => 0,
            2 => 1000,
            3 => 2500,
            4 => 5000,
            5 => 10000,
            6 => 20000,
            7 => 35000,
            8 => 55000,
            9 => 80000,
            10 => 120000,
        ]
    ]
];

