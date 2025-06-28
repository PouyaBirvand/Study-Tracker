<?php
/**
 * Authentication Service
 * سرویس احراز هویت
 */
class AuthService 
{
    private User $userModel;
    
    public function __construct() 
    {
        $this->userModel = new User();
    }
    
    /**
     * ثبت نام کاربر جدید
     */
    public function register(array $data): array 
    {
        // اعتبارسنجی
        $validator = new Validator($data);
        $validator->required('username', 'نام کاربری الزامی است')
                 ->required('email', 'ایمیل الزامی است')
                 ->required('password', 'رمز عبور الزامی است')
                 ->required('first_name', 'نام الزامی است')
                 ->required('last_name', 'نام خانوادگی الزامی است')
                 ->email('email')
                 ->minLength('password', AppConfig::PASSWORD_MIN_LENGTH)
                 ->minLength('username', 3, 'نام کاربری باید حداقل 3 کاراکتر باشد');
        
        if (!$validator->isValid()) {
            throw new Exception($validator->getFirstError());
        }
        
        // بررسی یکتا بودن نام کاربری و ایمیل
        if (!$this->userModel->isUsernameUnique($data['username'])) {
            throw new Exception('نام کاربری قبلاً استفاده شده است');
        }
        
        if (!$this->userModel->isEmailUnique($data['email'])) {
            throw new Exception('ایمیل قبلاً ثبت شده است');
        }
        
        // هش کردن رمز عبور
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['status'] = 'active';
        
        try {
            $userId = $this->userModel->create($data);
            $user = $this->userModel->find($userId);
            
            // ایجاد توکن
            $token = $this->generateToken($user);
            
            return [
                'user' => $user,
                'token' => $token
            ];
        } catch (Exception $e) {
            throw new Exception('خطا در ثبت نام: ' . $e->getMessage());
        }
    }
    
    /**
     * ورود کاربر
     */
    public function login(string $identifier, string $password): array 
    {
        // یافتن کاربر
        $user = $this->userModel->findByCredentials($identifier);
        
        if (!$user) {
            throw new Exception('نام کاربری یا رمز عبور اشتباه است');
        }
        
        // بررسی رمز عبور
        if (!password_verify($password, $user['password'])) {
            throw new Exception('نام کاربری یا رمز عبور اشتباه است');
        }
        
        // بررسی وضعیت کاربر
        if ($user['status'] !== 'active') {
            throw new Exception('حساب کاربری غیرفعال است');
        }
        
        // بروزرسانی آخرین ورود
        $this->userModel->updateLastLogin($user['id']);
        
        // حذف رمز عبور از اطلاعات کاربر
        unset($user['password']);
        
        // ایجاد توکن
        $token = $this->generateToken($user);
        
        return [
            'user' => $user,
            'token' => $token
        ];
    }
    
    /**
     * تولید توکن JWT
     */
    private function generateToken(array $user): string 
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => time() + AppConfig::JWT_EXPIRE
        ]);
        
        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, AppConfig::JWT_SECRET, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }
    
    /**
     * اعتبارسنجی توکن
     */
    public function validateToken(string $token): ?array 
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
        
        // بررسی امضا
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, AppConfig::JWT_SECRET, true);
        $expectedSignature = $this->base64UrlEncode($signature);
        
        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return null;
        }
        
        // بررسی payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        
        if (!$payload || $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * دریافت کاربر فعلی از توکن
     */
    public function getCurrentUser(string $token): ?array 
    {
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            return null;
        }
        
        return $this->userModel->find($payload['user_id']);
    }
    
    /**
     * تغییر رمز عبور
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool 
    {
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            throw new Exception('کاربر یافت نشد');
        }
        
        // دریافت رمز عبور فعلی (بدون مخفی کردن)
        $userWithPassword = $this->userModel->queryOne(
            "SELECT password FROM users WHERE id = ?", 
            [$userId]
        );
        
        if (!password_verify($currentPassword, $userWithPassword['password'])) {
            throw new Exception('رمز عبور فعلی اشتباه است');
        }
        
        // اعتبارسنجی رمز عبور جدید
        if (strlen($newPassword) < AppConfig::PASSWORD_MIN_LENGTH) {
            throw new Exception('رمز عبور جدید باید حداقل ' . AppConfig::PASSWORD_MIN_LENGTH . ' کاراکتر باشد');
        }
        
        return $this->userModel->changePassword($userId, $newPassword);
    }
    
    /**
     * بازنشانی رمز عبور
     */
    public function resetPassword(string $email): bool 
    {
        $user = $this->userModel->queryOne(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL", 
            [$email]
        );
        
        if (!$user) {
            throw new Exception('کاربری با این ایمیل یافت نشد');
        }
        
        // تولید رمز عبور موقت
        $tempPassword = $this->generateTempPassword();
        
        // ذخیره رمز عبور موقت
        $this->userModel->changePassword($user['id'], $tempPassword);
        
        // ارسال ایمیل (در اینجا فقط لاگ می‌کنیم)
        error_log("Reset password for {$email}: {$tempPassword}");
        
        return true;
    }
    
    /**
     * تولید رمز عبور موقت
     */
    private function generateTempPassword(): string 
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
    }
    
    /**
     * کدگذاری Base64 URL Safe
     */
    private function base64UrlEncode(string $data): string 
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * رمزگشایی Base64 URL Safe
     */
    private function base64UrlDecode(string $data): string 
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
