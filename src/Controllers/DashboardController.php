<?php
/**
 * Dashboard Controller
 * کنترلر داشبورد
 */
class DashboardController extends BaseController 
{
    private StatisticsService $statisticsService;
    private GamificationService $gamificationService;
    
    public function __construct() 
    {
        $this->statisticsService = new StatisticsService();
        $this->gamificationService = new GamificationService();
    }
    
    /**
     * دریافت داده‌های داشبورد
     */
    public function index(): void 
    {
        try {
            $user = $this->getCurrentUser();
            
            $dashboard = $this->statisticsService->getUserDashboard($user['id']);
            $progress = $this->gamificationService->getUserProgress($user['id']);
            $challenges = $this->gamificationService->getDailyChallenges($user['id']);
            
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'dashboard' => $dashboard,
                    'user_progress' => $progress,
                    'daily_challenges' => $challenges
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
     * دریافت آمار هفتگی
     */
    public function weeklyStats(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
            
            $stats = $this->statisticsService->getWeeklyOverview($user['id'], $startDate);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * دریافت آمار ماهانه
     */
    public function monthlyStats(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $year = (int) ($_GET['year'] ?? date('Y'));
            $month = (int) ($_GET['month'] ?? date('n'));
            
            $stats = $this->statisticsService->getMonthlyOverview($user['id'], $year, $month);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * دریافت روند بهره‌وری
     */
    public function productivityTrend(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $days = (int) ($_GET['days'] ?? 30);
            
            $trend = $this->statisticsService->getProductivityTrend($user['id'], $days);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $trend
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * مقایسه عملکرد
     */
    public function performanceComparison(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $period = $_GET['period'] ?? 'week'; // week, month
            
            $comparison = $this->statisticsService->getPerformanceComparison($user['id'], $period);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $comparison
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * دریافت جدول امتیازات
     */
    public function leaderboard(): void 
    {
        try {
            $user = $this->getCurrentUser();
            $limit = (int) ($_GET['limit'] ?? 10);
            
            $leaderboard = $this->gamificationService->getLeaderboard($limit, $user['id']);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $leaderboard
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
