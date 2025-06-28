<?php
/**
 * Subjects Controller
 * کنترلر دروس
 */
class SubjectsController extends BaseController 
{
    private Subject $subjectModel;
    
    public function __construct() 
    {
        $this->subjectModel = new Subject();
    }
    
    /**
     * دریافت لیست دروس
     */
    public function index(): void 
    {
        try {
            $user = $this->getCurrentUser();
            
            $subjects = $this->subjectModel->getUserSubjects($user['id']);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $subjects
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * ایجاد درس جدید
     */
    public function create(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $data = $this->getJsonInput();
            
            // اعتبارسنجی
            if (empty($data['name'])) {
                throw new Exception('نام درس الزامی است');
            }
            
            $subjectData = [
                'user_id' => $user['id'],
                'name' => trim($data['name']),
                'description' => $data['description'] ?? '',
                'color' => $data['color'] ?? '#3498db',
                'is_active' => 1
            ];
            
            $subjectId = $this->subjectModel->create($subjectData);
            $subject = $this->subjectModel->find($subjectId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'درس جدید ایجاد شد',
                'data' => $subject
            ], 201);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * بروزرسانی درس
     */
    public function update(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $subjectId = $this->getRouteParam('id');
            $data = $this->getJsonInput();
            
            $subject = $this->subjectModel->find($subjectId);
            
            if (!$subject || $subject['user_id'] != $user['id']) {
                throw new Exception('درس یافت نشد');
            }
            
            $updateData = [];
            $allowedFields = ['name', 'description', 'color', 'is_active'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                throw new Exception('هیچ داده‌ای برای بروزرسانی ارسال نشده');
            }
            
            $this->subjectModel->update($subjectId, $updateData);
            $updatedSubject = $this->subjectModel->find($subjectId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'درس بروزرسانی شد',
                'data' => $updatedSubject
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * حذف درس
     */
    public function delete(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $subjectId = $this->getRouteParam('id');
            
            $subject = $this->subjectModel->find($subjectId);

            if (!$subject || $subject['user_id'] != $user['id']) {
                throw new Exception('درس یافت نشد');
            }
            
            // بررسی وجود جلسات مطالعه برای این درس
            $sessionsCount = $this->subjectModel->getSubjectSessionsCount($subjectId);
            
            if ($sessionsCount > 0) {
                // غیرفعال کردن به جای حذف
                $this->subjectModel->update($subjectId, ['is_active' => 0]);
                $message = 'درس غیرفعال شد (به دلیل وجود جلسات مطالعه)';
            } else {
                // حذف کامل
                $this->subjectModel->delete($subjectId);
                $message = 'درس حذف شد';
            }
            
            $this->jsonResponse([
                'success' => true,
                'message' => $message
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * دریافت آمار درس
     */
    public function stats(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $subjectId = $this->getRouteParam('id');
            
            $subject = $this->subjectModel->find($subjectId);
            
            if (!$subject || $subject['user_id'] != $user['id']) {
                throw new Exception('درس یافت نشد');
            }
            
            $stats = $this->subjectModel->getSubjectStats($subjectId, $user['id']);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

