<?php
/**
 * User Controller
 * کنترلر مدیریت کاربر
 */
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/UserPoint.php';

class UserController extends BaseController 
{
    private User $userModel;
    private UserPoint $userPointModel;
    
    public function __construct() 
    {
        parent::__construct();
        $this->userModel = new User();
        $this->userPointModel = new UserPoint();
    }
    
    /**
     * دریافت پروفایل کاربر
     */
    public function getProfile(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $user = $this->userModel->findById($userId);
            
            if (!$user) {
                Response::error('کاربر یافت نشد', [], 404);
                return;
            }
            
            // حذف رمز عبور از پاسخ
            unset($user['password']);
            
            // اضافه کردن اطلاعات تکمیلی
            $user['total_points'] = $this->userPointModel->getUserTotalPoints($userId);
            $user['level'] = $this->calculateUserLevel($user['total_points']);
            $user['next_level_points'] = $this->getNextLevelPoints($user['level']);
            
            Response::success('پروفایل کاربر دریافت شد', $user);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت پروفایل: ' . $e->getMessage());
        }
    }
    
    /**
     * به‌روزرسانی پروفایل کاربر
     */
    public function updateProfile(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $data = $this->getJsonInput();
            
            // اعتبارسنجی
            $errors = [];
            
            if (isset($data['name']) && empty(trim($data['name']))) {
                $errors[] = 'نام نمی‌تواند خالی باشد';
            }
            
            if (isset($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'فرمت ایمیل صحیح نیست';
                }
                
                // بررسی تکراری نبودن ایمیل
                $existingUser = $this->userModel->findByEmail($data['email']);
                if ($existingUser && $existingUser['id'] != $userId) {
                    $errors[] = 'این ایمیل قبلاً استفاده شده است';
                }
            }
            
            if (!empty($errors)) {
                Response::error('خطاهای اعتبارسنجی', $errors, 400);
                return;
            }
            
            // به‌روزرسانی اطلاعات
            $updateData = [];
            $allowedFields = ['name', 'email', 'bio', 'avatar', 'timezone', 'language'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                Response::error('هیچ داده‌ای برای به‌روزرسانی ارسال نشده', [], 400);
                return;
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $result = $this->userModel->update($userId, $updateData);
            
            if ($result) {
                Response::success('پروفایل با موفقیت به‌روزرسانی شد');
            } else {
                Response::error('خطا در به‌روزرسانی پروفایل');
            }
            
        } catch (Exception $e) {
            Response::error('خطا در به‌روزرسانی پروفایل: ' . $e->getMessage());
        }
    }
    
    /**
     * تغییر رمز عبور
     */
    public function changePassword(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $data = $this->getJsonInput();
            
            // اعتبارسنجی
            if (empty($data['current_password'])) {
                Response::error('رمز عبور فعلی الزامی است', [], 400);
                return;
            }
            
            if (empty($data['new_password'])) {
                Response::error('رمز عبور جدید الزامی است', [], 400);
                return;
            }
            
            if (strlen($data['new_password']) < 6) {
                Response::error('رمز عبور جدید باید حداقل 6 کاراکتر باشد', [], 400);
                return;
            }
            
            // بررسی رمز عبور فعلی
            $user = $this->userModel->findById($userId);
            if (!password_verify($data['current_password'], $user['password'])) {
                Response::error('رمز عبور فعلی صحیح نیست', [], 400);
                return;
            }
            
            // به‌روزرسانی رمز عبور
            $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $result = $this->userModel->update($userId, [
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                Response::success('رمز عبور با موفقیت تغییر کرد');
            } else {
                Response::error('خطا در تغییر رمز عبور');
            }
            
        } catch (Exception $e) {
            Response::error('خطا در تغییر رمز عبور: ' . $e->getMessage());
        }
    }
    
    /**
     * دریافت تاریخچه امتیازات
     */
    public function getPointsHistory(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $limit = (int)($_GET['limit'] ?? 20);
            
            $history = $this->userPointModel->getUserPointsHistory($userId, $limit);
            
            Response::success('تاریخچه امتیازات دریافت شد', $history);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت تاریخچه امتیازات: ' . $e->getMessage());
        }
    }
    
    /**
     * دریافت تفکیک امتیازات
     */
    public function getPointsBreakdown(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $breakdown = $this->userPointModel->getPointsBreakdown($userId);
            
            Response::success('تفکیک امتیازات دریافت شد', $breakdown);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت تفکیک امتیازات: ' . $e->getMessage());
        }
    }
    
    /**
     * دریافت جدول امتیازات
     */
    public function getLeaderboard(): void 
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $leaderboard = $this->userPointModel->getLeaderboard($limit);
            
            Response::success('جدول امتیازات دریافت شد', $leaderboard);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت جدول امتیازات: ' . $e->getMessage());
        }
    }
    
    /**
     * دریافت تنظیمات کاربر
     */
    public function getSettings(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $user = $this->userModel->findById($userId);
            
            $settings = [
                'timezone' => $user['timezone'] ?? 'Asia/Tehran',
                'language' => $user['language'] ?? 'fa',
                'notifications' => [
                    'email' => $user['email_notifications'] ?? true,
                    'push' => $user['push_notifications'] ?? true,
                    'study_reminders' => $user['study_reminders'] ?? true
                ],
                'privacy' => [
                    'show_in_leaderboard' => $user['show_in_leaderboard'] ?? true,
                    'share_statistics' => $user['share_statistics'] ?? false
                ]
            ];
            
            Response::success('تنظیمات کاربر دریافت شد', $settings);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت تنظیمات: ' . $e->getMessage());
        }
    }
    
    /**
     * به‌روزرسانی تنظیمات کاربر
     */
    public function updateSettings(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $data = $this->getJsonInput();
            
            $updateData = [];
            
            if (isset($data['timezone'])) {
                $updateData['timezone'] = $data['timezone'];
            }
            
            if (isset($data['language'])) {
                $updateData['language'] = $data['language'];
            }
            
            if (isset($data['notifications'])) {
                $notifications = $data['notifications'];
                $updateData['email_notifications'] = $notifications['email'] ?? true;
                $updateData['push_notifications'] = $notifications['push'] ?? true;
                $updateData['study_reminders'] = $notifications['study_reminders'] ?? true;
            }
            
            if (isset($data['privacy'])) {
                $privacy = $data['privacy'];
                $updateData['show_in_leaderboard'] = $privacy['show_in_leaderboard'] ?? true;
                $updateData['share_statistics'] = $privacy['share_statistics'] ?? false;
            }
            
            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $result = $this->userModel->update($userId, $updateData);
                
                if ($result) {
                    Response::success('تنظیمات با موفقیت به‌روزرسانی شد');
                } else {
                    Response::error('خطا در به‌روزرسانی تنظیمات');
                }
            } else {
                Response::error('هیچ تنظیماتی برای به‌روزرسانی ارسال نشده', [], 400);
            }
            
        } catch (Exception $e) {
            Response::error('خطا در به‌روزرسانی تنظیمات: ' . $e->getMessage());
        }
    }
    
    /**
     * حذف حساب کاربری
     */
    public function deleteAccount(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $data = $this->getJsonInput();
            
            // تأیید رمز عبور
            if (empty($data['password'])) {
                Response::error('رمز عبور برای حذف حساب الزامی است', [], 400);
                return;
            }
            
            $user = $this->userModel->findById($userId);
            if (!password_verify($data['password'], $user['password'])) {
                Response::error('رمز عبور صحیح نیست', [], 400);
                return;
            }
            
            // حذف حساب (soft delete)
            $result = $this->userModel->update($userId, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'email' => $user['email'] . '_deleted_' . time()
            ]);
            
            if ($result) {
                Response::success('حساب کاربری با موفقیت حذف شد');
            } else {
                Response::error('خطا در حذف حساب کاربری');
            }
            
        } catch (Exception $e) {
            Response::error('خطا در حذف حساب: ' . $e->getMessage());
        }
    }
    
    /**
     * محاسبه سطح کاربر بر اساس امتیاز
     */
    private function calculateUserLevel(int $points): int 
    {
        if ($points < 100) return 1;
        if ($points < 300) return 2;
        if ($points < 600) return 3;
        if ($points < 1000) return 4;
        if ($points < 1500) return 5;
        if ($points < 2100) return 6;
        if ($points < 2800) return 7;
        if ($points < 3600) return 8;
        if ($points < 4500) return 9;
        
        return 10;
    }
    
    /**
     * محاسبه امتیاز مورد نیاز برای سطح بعدی
     */
    private function getNextLevelPoints(int $level): int 
    {
        $levelPoints = [
            1 => 100,
            2 => 300,
            3 => 600,
            4 => 1000,
            5 => 1500,
            6 => 2100,
            7 => 2800,
            8 => 3600,
            9 => 4500,
            10 => 5500
        ];
        
        return $levelPoints[$level + 1] ?? $levelPoints[10];
    }
}

