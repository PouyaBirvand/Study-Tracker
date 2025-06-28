#!/usr/bin/env php
<?php
/**
 * Test Runner
 * اجراکننده تست‌ها
 */

require_once __DIR__ . '/AuthServiceTest.php';
require_once __DIR__ . '/ApiTest.php';

echo "🚀 شروع اجرای تست‌ها...\n";
echo str_repeat('=', 60) . "\n";

// Unit Tests
echo "📋 تست‌های واحد:\n";
$authTest = new AuthServiceTest();
$authTest->run();

// Integration Tests
echo "\n🔗 تست‌های یکپارچگی:\n";
$apiTest = new ApiTest();
$apiTest->run();

echo "\n🎉 تست‌ها تکمیل شدند!\n";
