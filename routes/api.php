<?php
/**
 * API Routes
 * تعریف مسیرهای API
 */

require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/DashboardController.php';
require_once __DIR__ . '/../src/Controllers/StudySessionsController.php';
require_once __DIR__ . '/../src/Controllers/SubjectsController.php';
require_once __DIR__ . '/../src/Controllers/GoalsController.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/Middleware/CorsMiddleware.php';

$router = new Router();

// مسیرهای عمومی (بدون احراز هویت)
$router->post('/auth/register', 'AuthController@register', ['CorsMiddleware']);
$router->post('/auth/login', 'AuthController@login', ['CorsMiddleware']);
$router->post('/auth/reset-password', 'AuthController@resetPassword', ['CorsMiddleware']);

// مسیرهای محافظت شده (نیاز به احراز هویت)
$router->get('/auth/me', 'AuthController@me', ['CorsMiddleware', 'AuthMiddleware']);
$router->post('/auth/change-password', 'AuthController@changePassword', ['CorsMiddleware', 'AuthMiddleware']);

// داشبورد
$router->get('/dashboard', 'DashboardController@index', ['CorsMiddleware', 'AuthMiddleware']);
$router->get('/dashboard/weekly-stats', 'DashboardController@weeklyStats', ['CorsMiddleware', 'AuthMiddleware']);
$router->get('/dashboard/monthly-stats', 'DashboardController@monthlyStats', ['CorsMiddleware', 'AuthMiddleware']);
$router->get('/dashboard/productivity-trend', 'DashboardController@productivityTrend', ['CorsMiddleware', 'AuthMiddleware']);
$router->get('/dashboard/performance-comparison', 'DashboardController@performanceComparison', ['CorsMiddleware', 'AuthMiddleware']);
$router->get('/dashboard/leaderboard', 'DashboardController@leaderboard', ['CorsMiddleware', 'AuthMiddleware']);

// جلسات مطالعه
$router->get('/study-sessions', 'StudySessionsController@index', ['CorsMiddleware', 'AuthMiddleware']);
$router->post('/study-sessions/start', 'StudySessionsController@start', ['CorsMiddleware', 'AuthMiddleware']);
$router->post('/study-sessions/end', 'StudySessionsController@end', ['CorsMiddleware', 'AuthMiddleware']);
$router->get('/study-sessions/active', 'StudySessionsController@active', ['CorsMiddleware', 'AuthMiddleware']);
$router->put('/study-sessions/{id}', 'StudySessionsController@update', ['CorsMiddleware', 'AuthMiddleware']);
$router->delete('/study-sessions/{id}', 'StudySessionsController@delete', ['CorsMiddleware', 'AuthMiddleware']);

// دروس
$router->get('/subjects', 'SubjectsController@index', ['CorsMiddleware', 'AuthMiddleware']);
$router->post('/subjects', 'SubjectsController@create', ['CorsMiddleware', 'AuthMiddleware']);
$router->put('/subjects/{id}', 'SubjectsController@update', ['CorsMiddleware', 'AuthMiddleware']);
$router->delete('/subjects/{id}', 'SubjectsController@delete', ['CorsMiddleware', 'AuthMiddleware']);
$router->get('/subjects/{id}/stats', 'SubjectsController@stats', ['CorsMiddleware', 'AuthMiddleware']);

// اهداف
$router->get('/goals', 'GoalsController@index', ['CorsMiddleware', 'AuthMiddleware']);
$router->post('/goals', 'GoalsController@create', ['CorsMiddleware', 'AuthMiddleware']);
$router->put('/goals/{id}', 'GoalsController@update', ['CorsMiddleware', 'AuthMiddleware']);
$router->delete('/goals/{id}', 'GoalsController@delete', ['CorsMiddleware', 'AuthMiddleware']);
$router->post('/goals/{id}/complete', 'GoalsController@complete', ['CorsMiddleware', 'AuthMiddleware']);

// اجرای مسیریاب
$router->dispatch();
