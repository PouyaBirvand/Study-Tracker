<?php
/**
 * Auth Service Tests
 * تست‌های سرویس احراز هویت
 */

require_once __DIR__ . '/TestFramework.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Models/User.php';

class AuthServiceTest 
{
    private TestFramework $test;
    private AuthService $authService;
    
    public function __construct() 
    {
        $this->test = new TestFramework();
        $this->authService = new AuthService();
    }
    
    public function run(): void 
    {
        $this->test->describe('AuthService Tests', function() {
            $this->testPasswordHashing();
            $this->testTokenGeneration();
            $this->testEmailValidation();
        });
        
        $this->test->summary();
    }
    
    private function testPasswordHashing(): void 
    {
        $this->test->it('should hash password correctly', function() {
            $password = '123456';
            $hash = $this->authService->hashPassword($password);
            
            $this->test->expect(password_verify($password, $hash))->toBeTrue();
            $this->test->expect($hash)->not->toBe($password);
        });
    }
    
    private function testTokenGeneration(): void 
    {
        $this->test->it('should generate valid JWT token', function() {
            $userId = 1;
            $token = $this->authService->generateToken($userId);
            
            $this->test->expect($token)->not->toBeNull();
            $this->test->expect(strlen($token))->toBeGreaterThan(50);
        });
    }
    
    private function testEmailValidation(): void 
    {
        $this->test->it('should validate email format', function() {
            $validEmail = 'test@example.com';
            $invalidEmail = 'invalid-email';
            
            $this->test->expect($this->authService->isValidEmail($validEmail))->toBeTrue();
            $this->test->expect($this->authService->isValidEmail($invalidEmail))->toBeFalse();
        });
    }
}
