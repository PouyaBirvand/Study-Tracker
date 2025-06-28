<?php
/**
 * Statistics Controller
 * کنترلر آمار و گزارشات
 */
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/StatisticsService.php';

class StatisticsController extends BaseController 
{
    private StatisticsService $statisticsService;
    
    public function __construct() 
    {
        parent::__construct();
        $this->statisticsService = new StatisticsService();
    }
    
    /**
     * دریافت آمار کلی
     */
    public function getOverallStats(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $stats = $this->statisticsService->getOverallStatistics($userId);
            
            Response::success('آمار کلی دریافت شد', $stats);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت آمار: ' . $e->getMessage());
        }
    }
    
    /**
     * آمار هفتگی
     */
    public function getWeeklyStats(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $stats = $this->statisticsService->getWeeklyStatistics($userId);
            
            Response::success('آمار هفتگی دریافت شد', $stats);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت آمار هفتگی: ' . $e->getMessage());
        }
    }
    
    /**
     * آمار ماهانه
     */
    public function getMonthlyStats(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $month = $_GET['month'] ?? date('Y-m');
            
            $stats = $this->statisticsService->getMonthlyStatistics($userId, $month);
            
            Response::success('آمار ماهانه دریافت شد', $stats);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت آمار ماهانه: ' . $e->getMessage());
        }
    }
    
    /**
     * آمار بر اساس درس
     */
    public function getSubjectStats(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $stats = $this->statisticsService->getSubjectStatistics($userId);
            
            Response::success('آمار دروس دریافت شد', $stats);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت آمار دروس: ' . $e->getMessage());
        }
    }
    
    /**
     * نمودار بهره‌وری
     */
    public function getProductivityChart(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $period = $_GET['period'] ?? 'week'; // week, month, year
            
            $data = $this->statisticsService->getProductivityChart($userId, $period);
            
            Response::success('نمودار بهره‌وری دریافت شد', $data);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت نمودار بهره‌وری: ' . $e->getMessage());
        }
    }
    
    /**
     * نمودار زمان مطالعه روزانه
     */
    public function getDailyStudyChart(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $days = (int)($_GET['days'] ?? 30);
            
            $data = $this->statisticsService->getDailyStudyChart($userId, $days);
            
            Response::success('نمودار مطالعه روزانه دریافت شد', $data);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت نمودار مطالعه: ' . $e->getMessage());
        }
    }
    
    /**
     * مقایسه با دوره قبل
     */
    public function getComparison(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $period = $_GET['period'] ?? 'month'; // week, month
            
            $comparison = $this->statisticsService->getPeriodComparison($userId, $period);
            
            Response::success('مقایسه دوره‌ای دریافت شد', $comparison);
            
        } catch (Exception $e) {
            Response::error('خطا در مقایسه: ' . $e->getMessage());
        }
    }
    
    /**
     * گزارش تفصیلی
     */
    public function getDetailedReport(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            
            $report = $this->statisticsService->getDetailedReport($userId, $startDate, $endDate);
            
            Response::success('گزارش تفصیلی دریافت شد', $report);
            
        } catch (Exception $e) {
            Response::error('خطا در تولید گزارش: ' . $e->getMessage());
        }
    }
    
    /**
     * آمار عملکرد هدف‌ها
     */
    public function getGoalsPerformance(): void 
    {
        try {
            $userId = $this->getCurrentUserId();
            $stats = $this->statisticsService->getGoalsPerformance($userId);
            
            Response::success('آمار عملکرد هدف‌ها دریافت شد', $stats);
            
        } catch (Exception $e) {
            Response::error('خطا در دریافت آمار هدف‌ها: ' . $e->getMessage());
        }
    }
}
