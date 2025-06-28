<?php
/**
 * Statistics Service
 * سرویس آمار و گزارش‌گیری
 */
class StatisticsService 
{
    private StudySession $studySessionModel;
    private Subject $subjectModel;
    private Goal $goalModel;
    private User $userModel;
    
    public function __construct() 
    {
        $this->studySessionModel = new StudySession();
        $this->subjectModel = new Subject();
        $this->goalModel = new Goal();
        $this->userModel = new User();
    }
    
    /**
     * دریافت داشبورد کاربر
     */
    public function getUserDashboard(int $userId): array 
    {
        $today = date('Y-m-d');
        $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
        $thisMonthStart = date('Y-m-01');
        
        return [
            'today_stats' => $this->studySessionModel->getDailyStats($userId, $today),
            'weekly_stats' => $this->getWeeklyOverview($userId, $thisWeekStart),
            'monthly_stats' => $this->getMonthlyOverview($userId, date('Y'), date('n')),
            'recent_sessions' => $this->studySessionModel->getUserSessions($userId, 1, 5),
            'active_goals' => $this->goalModel->getUserGoals($userId, 'in_progress'),
            'subject_progress' => $this->subjectModel->getWeeklyProgress($userId),
            'achievements' => $this->getRecentAchievements($userId),
            'productivity_trend' => $this->getProductivityTrend($userId, 7)
        ];
    }
    
    /**
     * آمار هفتگی کلی
     */
    public function getWeeklyOverview(int $userId, string $startDate): array 
    {
        $weeklyData = $this->studySessionModel->getWeeklyStats($userId, $startDate);
        
        // ایجاد آرایه برای تمام روزهای هفته
        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($startDate . " +{$i} days"));
            $weekDays[$date] = [
                'date' => $date,
                'day_name' => $this->getPersianDayName($date),
                'sessions' => 0,
                'total_minutes' => 0
            ];
        }
        
        // پر کردن داده‌های واقعی
        foreach ($weeklyData as $day) {
            if (isset($weekDays[$day['date']])) {
                $weekDays[$day['date']]['sessions'] = (int) $day['sessions'];
                $weekDays[$day['date']]['total_minutes'] = (int) $day['total_minutes'];
            }
        }
        
        $totalSessions = array_sum(array_column($weekDays, 'sessions'));
        $totalMinutes = array_sum(array_column($weekDays, 'total_minutes'));
        
        return [
            'days' => array_values($weekDays),
            'total_sessions' => $totalSessions,
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 1),
            'average_per_day' => round($totalMinutes / 7, 1),
            'most_productive_day' => $this->getMostProductiveDay($weekDays)
        ];
    }
    
    /**
     * آمار ماهانه کلی
     */
    public function getMonthlyOverview(int $userId, int $year, int $month): array 
    {
        $monthlyData = $this->studySessionModel->getMonthlyStats($userId, $year, $month);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        // ایجاد آرایه برای تمام روزهای ماه
        $monthDays = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $monthDays[$day] = [
                'day' => $day,
                'sessions' => 0,
                'total_minutes' => 0,
                'avg_productivity' => 0
            ];
        }
        
        // پر کردن داده‌های واقعی
        foreach ($monthlyData as $dayData) {
            $day = (int) $dayData['day'];
            $monthDays[$day] = [
                'day' => $day,
                'sessions' => (int) $dayData['sessions'],
                'total_minutes' => (int) $dayData['total_minutes'],
                'avg_productivity' => round((float) $dayData['avg_productivity'], 1)
            ];
        }
        
        $totalSessions = array_sum(array_column($monthDays, 'sessions'));
        $totalMinutes = array_sum(array_column($monthDays, 'total_minutes'));
        $activeDays = count(array_filter($monthDays, fn($day) => $day['sessions'] > 0));
        
        return [
            'days' => array_values($monthDays),
            'total_sessions' => $totalSessions,
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 1),
            'active_days' => $activeDays,
            'average_per_active_day' => $activeDays > 0 ? round($totalMinutes / $activeDays, 1) : 0,
            'consistency_percentage' => round(($activeDays / $daysInMonth) * 100, 1)
        ];
    }
    
    /**
     * روند بهره‌وری
     */
    public function getProductivityTrend(int $userId, int $days = 30): array 
    {
        $sql = "SELECT 
                    DATE(session_date) as date,
                    AVG(productivity_score) as avg_productivity,
                    COUNT(*) as sessions_count,
                    SUM(duration) as total_minutes
                FROM study_sessions 
                WHERE user_id = ? 
                AND session_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND deleted_at IS NULL
                GROUP BY DATE(session_date)
                ORDER BY date";
        
        $data = $this->studySessionModel->query($sql, [$userId, $days]);
        
        $trend = [];
        foreach ($data as $row) {
            $trend[] = [
                'date' => $row['date'],
                'productivity' => round((float) $row['avg_productivity'], 1),
                'sessions' => (int) $row['sessions_count'],
                'minutes' => (int) $row['total_minutes']
            ];
        }
        
        return [
            'data' => $trend,
            'average_productivity' => $this->calculateAverageProductivity($trend),
            'trend_direction' => $this->calculateTrendDirection($trend)
        ];
    }
    
    /**
     * آمار دروس تفصیلی
     */
    public function getSubjectDetailedStats(int $userId): array 
    {
        $subjects = $this->subjectModel->getUserSubjects($userId);
        
        foreach ($subjects as &$subject) {
            $subjectStats = $this->subjectModel->getSubjectStats($subject['id'], $userId);
            $subject = array_merge($subject, $subjectStats);
            
            // آمار هفتگی درس
            $subject['weekly_sessions'] = $this->getSubjectWeeklySessions($subject['id'], $userId);
        }
        
        return $subjects;
    }
    
    /**
     * مقایسه عملکرد با دوره قبل
     */
    public function getPerformanceComparison(int $userId, string $period = 'week'): array 
    {
        $currentPeriod = $this->getPeriodStats($userId, $period, 0);
        $previousPeriod = $this->getPeriodStats($userId, $period, 1);
        
        return [
            'current' => $currentPeriod,
            'previous' => $previousPeriod,
            'comparison' => [
                'sessions_change' => $this->calculatePercentageChange(
                    $previousPeriod['total_sessions'], 
                    $currentPeriod['total_sessions']
                ),
                'minutes_change' => $this->calculatePercentageChange(
                    $previousPeriod['total_minutes'], 
                    $currentPeriod['total_minutes']
                ),
                'productivity_change' => $this->calculatePercentageChange(
                    $previousPeriod['avg_productivity'], 
                    $currentPeriod['avg_productivity']
                )
            ]
        ];
    }
    
    /**
     * دریافت دستاوردهای اخیر
     */
    private function getRecentAchievements(int $userId, int $limit = 3): array 
    {
        $achievementModel = new Achievement();
        $achievements = $achievementModel->getUserAchievements($userId);
        
        return array_slice($achievements, 0, $limit);
    }

        /**
     * دریافت نام روز به فارسی
     */
    private function getPersianDayName(string $date): string 
    {
        $dayNames = [
            'Saturday' => 'شنبه',
            'Sunday' => 'یکشنبه',
            'Monday' => 'دوشنبه',
            'Tuesday' => 'سه‌شنبه',
            'Wednesday' => 'چهارشنبه',
            'Thursday' => 'پنج‌شنبه',
            'Friday' => 'جمعه'
        ];
        
        $englishDay = date('l', strtotime($date));
        return $dayNames[$englishDay] ?? $englishDay;
    }
    
    /**
     * یافتن پربازده‌ترین روز هفته
     */
    private function getMostProductiveDay(array $weekDays): ?array 
    {
        $maxMinutes = 0;
        $mostProductiveDay = null;
        
        foreach ($weekDays as $day) {
            if ($day['total_minutes'] > $maxMinutes) {
                $maxMinutes = $day['total_minutes'];
                $mostProductiveDay = $day;
            }
        }
        
        return $mostProductiveDay;
    }
    
    /**
     * محاسبه میانگین بهره‌وری
     */
    private function calculateAverageProductivity(array $trendData): float 
    {
        if (empty($trendData)) {
            return 0;
        }
        
        $totalProductivity = array_sum(array_column($trendData, 'productivity'));
        return round($totalProductivity / count($trendData), 1);
    }
    
    /**
     * محاسبه جهت روند
     */
    private function calculateTrendDirection(array $trendData): string 
    {
        if (count($trendData) < 2) {
            return 'stable';
        }
        
        $firstHalf = array_slice($trendData, 0, ceil(count($trendData) / 2));
        $secondHalf = array_slice($trendData, floor(count($trendData) / 2));
        
        $firstAvg = array_sum(array_column($firstHalf, 'productivity')) / count($firstHalf);
        $secondAvg = array_sum(array_column($secondHalf, 'productivity')) / count($secondHalf);
        
        $difference = $secondAvg - $firstAvg;
        
        if ($difference > 0.5) {
            return 'increasing';
        } elseif ($difference < -0.5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
    
    /**
     * دریافت جلسات هفتگی درس
     */
    private function getSubjectWeeklySessions(int $subjectId, int $userId): array 
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        
        $sql = "SELECT 
                    DATE(session_date) as date,
                    COUNT(*) as sessions,
                    SUM(duration) as total_minutes
                FROM study_sessions 
                WHERE subject_id = ? 
                AND user_id = ? 
                AND session_date >= ? 
                AND deleted_at IS NULL
                GROUP BY DATE(session_date)
                ORDER BY date";
        
        return $this->studySessionModel->query($sql, [$subjectId, $userId, $startOfWeek]);
    }
    
    /**
     * دریافت آمار دوره (هفته/ماه)
     */
    private function getPeriodStats(int $userId, string $period, int $offset): array 
    {
        switch ($period) {
            case 'week':
                $startDate = date('Y-m-d', strtotime("monday this week -{$offset} week"));
                $endDate = date('Y-m-d', strtotime("sunday this week -{$offset} week"));
                break;
            case 'month':
                $startDate = date('Y-m-01', strtotime("-{$offset} month"));
                $endDate = date('Y-m-t', strtotime("-{$offset} month"));
                break;
            default:
                throw new Exception('نوع دوره نامعتبر است');
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_sessions,
                    SUM(duration) as total_minutes,
                    AVG(productivity_score) as avg_productivity
                FROM study_sessions 
                WHERE user_id = ? 
                AND DATE(session_date) BETWEEN ? AND ?
                AND deleted_at IS NULL";
        
        $result = $this->studySessionModel->queryOne($sql, [$userId, $startDate, $endDate]);
        
        return [
            'total_sessions' => (int) ($result['total_sessions'] ?? 0),
            'total_minutes' => (int) ($result['total_minutes'] ?? 0),
            'avg_productivity' => round((float) ($result['avg_productivity'] ?? 0), 1),
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }
    
    /**
     * محاسبه درصد تغییر
     */
    private function calculatePercentageChange(float $oldValue, float $newValue): float 
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }
}
