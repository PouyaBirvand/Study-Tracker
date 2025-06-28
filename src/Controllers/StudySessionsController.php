<?php
/**
 * Study Sessions Controller
 * کنترلر جلسات مطالعه
 */
class StudySessionsController extends BaseController 
{
    private StudySession $studySessionModel;
    private GamificationService $gamificationService;
    
    public function __construct() 
    {
        $this->studySessionModel = new StudySession();
        $this->gamificationService = new GamificationService();
    }
    
    /**
     * دریافت لیست جلسات
     */
    public function index(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $page = (int) ($_GET['page'] ?? 1);
            $limit = (int) ($_GET['limit'] ?? 20);
            
            $sessions = $this->studySessionModel->getUserSessions($user['id'], $page, $limit);
            $total = $this->studySessionModel->getUserSessionsCount($user['id']);
            
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'sessions' => $sessions,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * شروع جلسه جدید
     */
    public function start(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $data = $this->getJsonInput();
            
            // اعتبارسنجی
            if (empty($data['subject_id'])) {
                throw new Exception('انتخاب درس الزامی است');
            }
            
            // بررسی وجود جلسه فعال
            $activeSession = $this->studySessionModel->getActiveSession($user['id']);
            if ($activeSession) {
                throw new Exception('شما یک جلسه فعال دارید. ابتدا آن را پایان دهید');
            }
            
            $sessionData = [
                'user_id' => $user['id'],
                'subject_id' => $data['subject_id'],
                'session_date' => date('Y-m-d H:i:s'),
                'notes' => $data['notes'] ?? '',
                'status' => 'active'
            ];
            
            $sessionId = $this->studySessionModel->create($sessionData);
            $session = $this->studySessionModel->find($sessionId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'جلسه مطالعه شروع شد',
                'data' => $session
            ], 201);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * پایان جلسه
     */
    public function end(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $data = $this->getJsonInput();
            
            if (empty($data['session_id'])) {
                throw new Exception('شناسه جلسه الزامی است');
            }
            
            $session = $this->studySessionModel->find($data['session_id']);
            
            if (!$session || $session['user_id'] != $user['id']) {
                throw new Exception('جلسه یافت نشد');
            }
            
            if ($session['status'] !== 'active') {
                throw new Exception('این جلسه قبلاً پایان یافته است');
            }
            
            // محاسبه مدت زمان
            $startTime = new DateTime($session['session_date']);
            $endTime = new DateTime();
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();
            $durationMinutes = round($duration / 60);
            
            // بروزرسانی جلسه
            $updateData = [
                'duration' => $durationMinutes,
                'productivity_score' => $data['productivity_score'] ?? 5,
                'notes' => $data['notes'] ?? $session['notes'],
                'status' => 'completed',
                'end_time' => $endTime->format('Y-m-d H:i:s')
            ];
            
            $this->studySessionModel->update($data['session_id'], $updateData);
            
            // پردازش پاداش‌ها
            $sessionData = array_merge($session, $updateData, ['session_id' => $data['session_id']]);
            $rewards = $this->gamificationService->processStudySessionRewards($user['id'], $sessionData);
            
            $updatedSession = $this->studySessionModel->find($data['session_id']);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'جلسه مطالعه با موفقیت پایان یافت',
                'data' => [
                    'session' => $updatedSession,
                    'rewards' => $rewards
                ]
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * دریافت جلسه فعال
     */
    public function active(): void 
    {
        try {
            $user = $this->getCurrentUser();
            
            $activeSession = $this->studySessionModel->getActiveSession($user['id']);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $activeSession
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * حذف جلسه
     */
    public function delete(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $sessionId = $this->getRouteParam('id');
            
            $session = $this->studySessionModel->find($sessionId);
            
            if (!$session || $session['user_id'] != $user['id']) {
                throw new Exception('جلسه یافت نشد');
            }
            
            $this->studySessionModel->delete($sessionId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'جلسه حذف شد'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * بروزرسانی جلسه
     */
    public function update(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $sessionId = $this->getRouteParam('id');
            $data = $this->getJsonInput();
            
            $session = $this->studySessionModel->find($sessionId);
            
            if (!$session || $session['user_id'] != $user['id']) {
                throw new Exception('جلسه یافت نشد');
            }
            
            // فقط فیلدهای قابل ویرایش
            $allowedFields = ['notes', 'productivity_score'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                throw new Exception('هیچ داده‌ای برای بروزرسانی ارسال نشده');
            }
            
            $this->studySessionModel->update($sessionId, $updateData);
            $updatedSession = $this->studySessionModel->find($sessionId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'جلسه بروزرسانی شد',
                'data' => $updatedSession
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
