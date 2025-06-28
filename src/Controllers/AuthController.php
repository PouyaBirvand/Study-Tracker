<?php
/**
 * Auth Controller
 * کنترلر احراز هویت
 */
class AuthController extends BaseController 
{
    private AuthService $authService;
    
    public function __construct() 
    {
        $this->authService = new AuthService();
    }
    
    /**
     * ثبت نام
     */
    public function register(): void 
    {
        try {
            $data = $this->getJsonInput();
            
            $result = $this->authService->register($data);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'ثبت نام با موفقیت انجام شد',
                'data' => $result
            ], 201);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * ورود
     */
    public function login(): void 
    {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['identifier']) || empty($data['password'])) {
                throw new Exception('نام کاربری/ایمیل و رمز عبور الزامی است');
            }
            
            $result = $this->authService->login($data['identifier'], $data['password']);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'ورود با موفقیت انجام شد',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }
    
    /**
     * دریافت اطلاعات کاربر فعلی
     */
    public function me(): void 
    {
        try {
            $user = $this->getCurrentUser();
            
            $this->jsonResponse([
                'success' => true,
                'data' => $user
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }
    
    /**
     * تغییر رمز عبور
     */
    public function changePassword(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $data = $this->getJsonInput();
            
            if (empty($data['current_password']) || empty($data['new_password'])) {
                throw new Exception('رمز عبور فعلی و جدید الزامی است');
            }
            
            $this->authService->changePassword(
                $user['id'], 
                $data['current_password'], 
                $data['new_password']
            );
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'رمز عبور با موفقیت تغییر کرد'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * بازنشانی رمز عبور
     */
    public function resetPassword(): void 
    {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['email'])) {
                throw new Exception('ایمیل الزامی است');
            }
            
            $this->authService->resetPassword($data['email']);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'رمز عبور جدید به ایمیل شما ارسال شد'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
