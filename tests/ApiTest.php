<?php
/**
 * API Integration Tests
 * تست‌های یکپارچگی API
 */

require_once __DIR__ . '/TestFramework.php';

class ApiTest 
{
    private TestFramework $test;
    private string $baseUrl;
    private ?string $authToken = null;
    
    public function __construct() 
    {
        $this->test = new TestFramework();
        $this->baseUrl = 'http://localhost/study-tracker/public';
    }
    
    public function run(): void 
    {
        $this->test->describe('API Integration Tests', function() {
            $this->testUserRegistration();
            $this->testUserLogin();
            $this->testDashboardAccess();
            $this->testStudySessionFlow();
        });
        
        $this->test->summary();
    }
    
    private function testUserRegistration(): void 
    {
        $this->test->it('should register new user', function() {
            $data = [
                'name' => 'Test User',
                'email' => 'test' . time() . '@example.com',
                'password' => '123456'
            ];
            
            $response = $this->makeRequest('POST', '/api/auth/register', $data);
            
            $this->test->expect($response['success'])->toBeTrue();
            $this->test->expect($response['data']['token'])->not->toBeNull();
        });
    }
    
    private function testUserLogin(): void 
    {
        $this->test->it('should login user with valid credentials', function() {
            $data = [
                'email' => 'test@example.com',
                'password' => '123456'
            ];
            
            $response = $this->makeRequest('POST', '/api/auth/login', $data);
            
            $this->test->expect($response['success'])->toBeTrue();
            $this->test->expect($response['data']['token'])->not->toBeNull();
            
            $this->authToken = $response['data']['token'];
        });
    }
    
    private function testDashboardAccess(): void 
    {
        $this->test->it('should access dashboard with valid token', function() {
            if (!$this->authToken) {
                throw new Exception('Auth token not available');
            }
            
            $response = $this->makeRequest('GET', '/api/dashboard', null, [
                'Authorization: Bearer ' . $this->authToken
            ]);
            
            $this->test->expect($response['success'])->toBeTrue();
            $this->test->expect($response['data'])->not->toBeNull();
        });
    }
    
    private function testStudySessionFlow(): void 
    {
        $this->test->it('should start and end study session', function() {
            if (!$this->authToken) {
                throw new Exception('Auth token not available');
            }
            
            // Start session
            $startData = ['subject_id' => 1];
            $startResponse = $this->makeRequest('POST', '/api/study-sessions/start', $startData, [
                'Authorization: Bearer ' . $this->authToken
            ]);
            
            $this->test->expect($startResponse['success'])->toBeTrue();
            
            // End session
            $endData = ['productivity_score' => 8];
            $endResponse = $this->makeRequest('POST', '/api/study-sessions/end', $endData, [
                'Authorization: Bearer ' . $this->authToken
            ]);
            
            $this->test->expect($endResponse['success'])->toBeTrue();
        });
    }
    
    private function makeRequest(string $method, string $endpoint, ?array $data = null, array $headers = []): array 
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('cURL request failed');
        }
        
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new Exception('Invalid JSON response');
        }
        
        return $decoded;
    }
}
