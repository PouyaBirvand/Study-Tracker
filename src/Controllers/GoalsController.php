<?php
/**
 * Goals Controller
 * کنترلر اهداف
 */
class GoalsController extends BaseController 
{
    private Goal $goalModel;
    
    public function __construct() 
    {
        $this->goalModel = new Goal();
    }
    
    /**
     * دریافت لیست اهداف
     */
    public function index(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $status = $_GET['status'] ?? 'all'; // all, active, completed, expired
            
            $goals = $this->goalModel->getUserGoals($user['id'], $status);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $goals
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * ایجاد هدف جدید
     */
    public function create(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $data = $this->getJsonInput();
            
            // اعتبارسنجی
            $this->validateGoalData($data);
            
            $goalData = [
                'user_id' => $user['id'],
                'title' => trim($data['title']),
                'description' => $data['description'] ?? '',
                'type' => $data['type'], // daily, weekly, monthly, custom
                'target_value' => $data['target_value'],
                'target_unit' => $data['target_unit'], // minutes, sessions, hours
                'subject_id' => $data['subject_id'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'active'
            ];
            
            $goalId = $this->goalModel->create($goalData);
            $goal = $this->goalModel->find($goalId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'هدف جدید ایجاد شد',
                'data' => $goal
            ], 201);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * بروزرسانی هدف
     */
    public function update(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $goalId = $this->getRouteParam('id');
            $data = $this->getJsonInput();
            
            $goal = $this->goalModel->find($goalId);
            
            if (!$goal || $goal['user_id'] != $user['id']) {
                throw new Exception('هدف یافت نشد');
            }
            
            if ($goal['status'] === 'completed') {
                throw new Exception('نمی‌توان هدف تکمیل شده را ویرایش کرد');
            }
            
            $updateData = [];
            $allowedFields = ['title', 'description', 'target_value', 'end_date'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                throw new Exception('هیچ داده‌ای برای بروزرسانی ارسال نشده');
            }
            
            $this->goalModel->update($goalId, $updateData);
            $updatedGoal = $this->goalModel->find($goalId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'هدف بروزرسانی شد',
                'data' => $updatedGoal
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * حذف هدف
     */
    public function delete(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $goalId = $this->getRouteParam('id');
            
            $goal = $this->goalModel->find($goalId);
            
            if (!$goal || $goal['user_id'] != $user['id']) {
                throw new Exception('هدف یافت نشد');
            }
            
            $this->goalModel->delete($goalId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'هدف حذف شد'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * تکمیل دستی هدف
     */
    public function complete(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $goalId = $this->getRouteParam('id');
            
            $goal = $this->goalModel->find($goalId);
            
            if (!$goal || $goal['user_id'] != $user['id']) {
                throw new Exception('هدف یافت نشد');
            }
            
            if ($goal['status'] !== 'active') {
                throw new Exception('فقط اهداف فعال قابل تکمیل هستند');
            }
            
            $this->goalModel->update($goalId, [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ]);
            
            $updatedGoal = $this->goalModel->find($goalId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'هدف تکمیل شد',
                'data' => $updatedGoal
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * اعتبارسنجی داده‌های هدف
     */
    private function validateGoalData(array $data): void 
    {
        if (empty($data['title'])) {
            throw new Exception('عنوان هدف الزامی است');
        }
        
        if (empty($data['type']) || !in_array($data['type'], ['daily', 'weekly', 'monthly', 'custom'])) {
            throw new Exception('نوع هدف نامعتبر است');
        }
        
        if (empty($data['target_value']) || $data['target_value'] <= 0) {
            throw new Exception('مقدار هدف باید بزرگتر از صفر باشد');
        }
        
        if (empty($data['target_unit']) || !in_array($data['target_unit'], ['minutes', 'sessions', 'hours'])) {
            throw new Exception('واحد هدف نامعتبر است');
        }
        
        if (empty($data['start_date']) || empty($data['end_date'])) {
            throw new Exception('تاریخ شروع و پایان الزامی است');
        }
        
        if (strtotime($data['start_date']) >= strtotime($data['end_date'])) {
            throw new Exception('تاریخ پایان باید بعد از تاریخ شروع باشد');
        }
    }
}
