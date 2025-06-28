<?php
/**
 * API Documentation Generator
 * تولیدکننده مستندات API
 */

class ApiDocGenerator 
{
    private array $endpoints = [];
    
    public function __construct() 
    {
        $this->loadEndpoints();
    }
    
    public function generate(): void 
    {
        $html = $this->generateHtml();
        file_put_contents(__DIR__ . '/api-docs.html', $html);
        echo "📚 مستندات API تولید شد: docs/api-docs.html\n";
    }
    
    private function loadEndpoints(): void 
    {
        $this->endpoints = [
            'authentication' => [
                'title' => 'احراز هویت',
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/auth/register',
                        'title' => 'ثبت نام',
                        'description' => 'ثبت نام کاربر جدید',
                        'body' => [
                            'name' => 'string (required) - نام کاربر',
                            'email' => 'string (required) - ایمیل',
                            'password' => 'string (required) - رمز عبور (حداقل 6 کاراکتر)'
                        ],
                        'response' => [
                            'success' => true,
                            'message' => 'ثبت نام با موفقیت انجام شد',
                            'data' => [
                                'user' => '...',
                                'token' => 'JWT_TOKEN'
                            ]
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/auth/login',
                        'title' => 'ورود',
                        'description' => 'ورود کاربر',
                        'body' => [
                            'email' => 'string (required) - ایمیل',
                            'password' => 'string (required) - رمز عبور'
                        ],
                        'response' => [
                            'success' => true,
                            'message' => 'ورود موفقیت‌آمیز',
                            'data' => [
                                'user' => '...',
                                'token' => 'JWT_TOKEN'
                            ]
                        ]
                    ]
                ]
            ],
            'dashboard' => [
                'title' => 'داشبورد',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/dashboard',
                        'title' => 'اطلاعات داشبورد',
                        'description' => 'دریافت آمار کلی داشبورد',
                        'headers' => [
                            'Authorization' => 'Bearer JWT_TOKEN'
                        ],
                        'response' => [
                            'success' => true,
                            'data' => [
                                'total_study_time' => 'کل زمان مطالعه (دقیقه)',
                                'total_sessions' => 'تعداد کل جلسات',
                                'average_productivity' => 'میانگین بهره‌وری',
                                'current_streak' => 'روزهای متوالی مطالعه',
                                'level' => 'سطح کاربر',
                                'points' => 'امتیاز کل',
                                'recent_sessions' => 'جلسات اخیر',
                                'achievements' => 'دستاوردهای اخیر'
                            ]
                        ]
                    ]
                ]
            ],
            'study_sessions' => [
                'title' => 'جلسات مطالعه',
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/study-sessions/start',
                        'title' => 'شروع جلسه مطالعه',
                        'description' => 'شروع جلسه مطالعه جدید',
                        'headers' => [
                            'Authorization' => 'Bearer JWT_TOKEN'
                        ],
                        'body' => [
                            'subject_id' => 'integer (required) - شناسه درس',
                            'planned_duration' => 'integer (optional) - مدت زمان برنامه‌ریزی شده (دقیقه)'
                        ],
                        'response' => [
                            'success' => true,
                            'message' => 'جلسه مطالعه شروع شد',
                            'data' => [
                                'session_id' => 'شناسه جلسه',
                                'start_time' => 'زمان شروع'
                            ]
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/study-sessions/end',
                        'title' => 'پایان جلسه مطالعه',
                        'description' => 'پایان دادن به جلسه مطالعه فعال',
                        'headers' => [
                            'Authorization' => 'Bearer JWT_TOKEN'
                        ],
                        'body' => [
                            'productivity_score' => 'integer (1-10) - امتیاز بهره‌وری',
                            'notes' => 'string (optional) - یادداشت'
                        ],
                        'response' => [
                            'success' => true,
                            'message' => 'جلسه مطالعه پایان یافت',
                            'data' => [
                                'session' => 'اطلاعات جلسه',
                                'points_earned' => 'امتیاز کسب شده',
                                'achievements' => 'دستاوردهای جدید'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    private function generateHtml(): string 
    {
        $html = '<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مستندات API - Study Tracker</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 30px; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .section { margin-bottom: 40px; }
        .section-title { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; }
        .endpoint { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; margin-bottom: 20px; overflow: hidden; }
        .endpoint-header { background: #343a40; color: white; padding: 15px; }
        .endpoint-body { padding: 20px; }
        .method { display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: bold; margin-left: 10px; }
        .method.POST { background: #28a745; color: white; }
        .method.GET { background: #007bff; color: white; }
        .method.PUT { background: #ffc107; color: black; }
        .method.DELETE { background: #dc3545; color: white; }
        .code { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 15px; font-family: monospace; overflow-x: auto; }
        .param { margin-bottom: 10px; }
        .param-name { font-weight: bold; color: #495057; }
        .param-type { color: #6c757d; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 مستندات API - Study Tracker</h1>
            <p>مستندات کامل API سیستم ردیابی مطالعه</p>
        </div>
        <div class="content">';
        
        foreach ($this->endpoints as $sectionKey => $section) {
            $html .= '<div class="section">';
            $html .= '<h2 class="section-title">' . $section['title'] . '</h2>';
            
            foreach ($section['endpoints'] as $endpoint) {
                $html .= '<div class="endpoint">';
                $html .= '<div class="endpoint-header">';
                $html .= '<span class="method ' . $endpoint['method'] . '">' . $endpoint['method'] . '</span>';
                $html .= '<strong>' . $endpoint['path'] . '</strong> - ' . $endpoint['title'];
                $html .= '</div>';
                $html .= '<div class="endpoint-body">';
                $html .= '<p>' . $endpoint['description'] . '</p>';
                
                if (isset($endpoint['headers'])) {
                    $html .= '<h4>Headers:</h4>';
                    $html .= '<div class="code">';
                    foreach ($endpoint['headers'] as $key => $value) {
                        $html .= $key . ': ' . $value . '<br>';
                    }
                    $html .= '</div>';
                }
                
                if (isset($endpoint['body'])) {
                    $html .= '<h4>Request Body:</h4>';
                    $html .= '<div class="code">';
                    $html .= json_encode($endpoint['body'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $html .= '</div>';
                }
                
                if (isset($endpoint['response'])) {
                    $html .= '<h4>Response:</h4>';
                    $html .= '<div class="code">';
                    $html .= json_encode($endpoint['response'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $html .= '</div>';
                }
                
                $html .= '</div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>
    </div>
</body>
</html>';
        
        return $html;
    }
}

// تولید مستندات
$generator = new ApiDocGenerator();
$generator->generate();
