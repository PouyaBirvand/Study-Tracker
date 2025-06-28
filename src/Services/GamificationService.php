<?php
/**
 * Gamification Service
 * سرویس بازی‌سازی و انگیزش
 */
class GamificationService 
{
    private Achievement $achievementModel;
    private User $userModel;
    private StudySession $studySessionModel;
    
    public function __construct() 
    {
        $this->achievementModel = new Achievement();
        $this->userModel = new User();
        $this->studySessionModel = new StudySession();
    }
    
    /**
     * پردازش امتیازات پس از جلسه مطالعه
     */
    public function processStudySessionRewards(int $userId, array $sessionData): array 
    {
        $rewards = [];
        
        // امتیاز پایه بر اساس مدت زمان
        $basePoints = $this->calculateBasePoints($sessionData['duration']);
        
        // امتیاز اضافی بر اساس بهره‌وری
        $productivityBonus = $this->calculateProductivityBonus(
            $sessionData['productivity_score'] ?? 5, 
            $basePoints
        );
        
        // امتیاز اضافی برای تداوم
        $streakBonus = $this->calculateStreakBonus($userId);
        
        $totalPoints = $basePoints + $productivityBonus + $streakBonus;
        
        // ثبت امتیازات
        $this->addPointsToUser($userId, $totalPoints, 'study_session', $sessionData['session_id'] ?? 0);
        
        $rewards['points'] = [
            'base' => $basePoints,
            'productivity_bonus' => $productivityBonus,
            'streak_bonus' => $streakBonus,
            'total' => $totalPoints
        ];
        
        // بررسی دستاوردهای جدید
        $newAchievements = $this->achievementModel->checkAndGrantAchievements($userId);
        $rewards['achievements'] = $newAchievements;
        
        // بررسی ارتقای سطح
        $levelUp = $this->checkLevelUp($userId);
        if ($levelUp) {
            $rewards['level_up'] = $levelUp;
        }
        
        return $rewards;
    }
    
    /**
     * محاسبه امتیاز پایه
     */
    private function calculateBasePoints(int $duration): int 
    {
        // هر دقیقه = 1 امتیاز، با حداکثر 120 امتیاز در جلسه
        return min(120, $duration);
    }
    
    /**
     * محاسبه امتیاز اضافی بهره‌وری
     */
    private function calculateProductivityBonus(float $productivityScore, int $basePoints): int 
    {
        if ($productivityScore >= 8) {
            return (int) ($basePoints * 0.5); // 50% اضافه
        } elseif ($productivityScore >= 6) {
            return (int) ($basePoints * 0.25); // 25% اضافه
        }
        
        return 0;
    }
    
    /**
     * محاسبه امتیاز اضافی تداوم
     */
    private function calculateStreakBonus(int $userId): int 
    {
        $consecutiveDays = $this->getConsecutiveStudyDays($userId);
        
        if ($consecutiveDays >= 30) {
            return 50; // یک ماه تداوم
        } elseif ($consecutiveDays >= 14) {
            return 30; // دو هفته تداوم
        } elseif ($consecutiveDays >= 7) {
            return 20; // یک هفته تداوم
        } elseif ($consecutiveDays >= 3) {
            return 10; // سه روز تداوم
        }
        
        return 0;
    }
    
    /**
     * محاسبه روزهای متوالی مطالعه
     */
    private function getConsecutiveStudyDays(int $userId): int 
    {
        $sql = "SELECT DATE(session_date) as study_date
                FROM study_sessions 
                WHERE user_id = ? AND deleted_at IS NULL
                GROUP BY DATE(session_date)
                ORDER BY study_date DESC
                LIMIT 30"; // بررسی 30 روز اخیر
        
        $dates = $this->studySessionModel->query($sql, [$userId]);
        
        if (empty($dates)) {
            return 0;
        }
        
        $consecutive = 0;
        $expectedDate = new DateTime();
        
        foreach ($dates as $dateRow) {
            $studyDate = new DateTime($dateRow['study_date']);
            
            if ($studyDate->format('Y-m-d') === $expectedDate->format('Y-m-d')) {
                $consecutive++;
                $expectedDate->modify('-1 day');
            } else {
                break;
            }
        }
        
        return $consecutive;
    }
    
    /**
     * اضافه کردن امتیاز به کاربر
     */
    private function addPointsToUser(int $userId, int $points, string $type, int $referenceId): void 
    {
        $sql = "INSERT INTO user_points (user_id, points, type, reference_id, earned_at, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())";
        
        $db = DatabaseConfig::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $points, $type, $referenceId]);
    }
    
    /**
     * بررسی ارتقای سطح
     */
    private function checkLevelUp(int $userId): ?array 
    {
        $currentLevel = $this->getUserLevel($userId);
        $totalPoints = $this->getUserTotalPoints($userId);
        $newLevel = $this->calculateLevelFromPoints($totalPoints);
        
        if ($newLevel > $currentLevel['level']) {
            // بروزرسانی سطح کاربر
            $this->updateUserLevel($userId, $newLevel);
            
            return [
                'old_level' => $currentLevel['level'],
                'new_level' => $newLevel,
                'level_name' => $this->getLevelName($newLevel),
                'bonus_points' => $newLevel * 100 // امتیاز اضافی برای ارتقا
            ];
        }
        
        return null;
    }
    
    /**
     * دریافت سطح فعلی کاربر
     */
    private function getUserLevel(int $userId): array 
    {
        $sql = "SELECT level, level_points FROM users WHERE id = ?";
        $db = DatabaseConfig::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return [
            'level' => (int) ($result['level'] ?? 1),
            'level_points' => (int) ($result['level_points'] ?? 0)
        ];
    }
    
    /**
     * دریافت مجموع امتیازات کاربر
     */
    private function getUserTotalPoints(int $userId): int 
    {
        $sql = "SELECT COALESCE(SUM(points), 0) as total_points 
                FROM user_points 
                WHERE user_id = ? AND deleted_at IS NULL";
        
        $db = DatabaseConfig::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * محاسبه سطح بر اساس امتیازات
     */
    private function calculateLevelFromPoints(int $totalPoints): int 
    {
        // فرمول: هر سطح نیاز به (سطح × 1000) امتیاز دارد
        // سطح 1: 0-999، سطح 2: 1000-2999، سطح 3: 3000-5999، ...
        
        if ($totalPoints < 1000) return 1;
        
        $level = 1;
        $requiredPoints = 0;
        
        while ($totalPoints >= $requiredPoints) {
            $level++;
            $requiredPoints += $level * 1000;
        }
        
        return $level - 1;
    }
    
    /**
     * بروزرسانی سطح کاربر
     */
    private function updateUserLevel(int $userId, int $newLevel): void 
    {
        $sql = "UPDATE users SET level = ?, updated_at = NOW() WHERE id = ?";
        $db = DatabaseConfig::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$newLevel, $userId]);
        
        // اضافه کردن امتیاز اضافی برای ارتقا
        $bonusPoints = $newLevel * 100;
        $this->addPointsToUser($userId, $bonusPoints, 'level_up', $newLevel);
    }
    
    /**
     * دریافت نام سطح
     */
    private function getLevelName(int $level): string 
    {
        $levelNames = [
            1 => 'مبتدی',
            2 => 'دانش‌آموز',
            3 => 'محصل',
            4 => 'دانشجو',
            5 => 'متخصص',
            6 => 'کارشناس',
            7 => 'استاد',
            8 => 'خبره',
            9 => 'نابغه',
            10 => 'استاد بزرگ'
        ];
        
        return $levelNames[$level] ?? "سطح {$level}";
    }
    
    /**
     * دریافت جدول امتیازات
     */
    public function getLeaderboard(int $limit = 10, int $userId = null): array 
    {
        $sql = "SELECT 
                    u.id,
                    u.username,
                    u.first_name,
                    u.last_name,
                    u.avatar,
                    u.level,
                    COALESCE(SUM(up.points), 0) as total_points,
                    COUNT(DISTINCT DATE(ss.session_date)) as study_days
                FROM users u
                LEFT JOIN user_points up ON u.id = up.user_id AND up.deleted_at IS NULL
                LEFT JOIN study_sessions ss ON u.id = ss.user_id AND ss.deleted_at IS NULL
                WHERE u.deleted_at IS NULL AND u.status = 'active'
                GROUP BY u.id
                ORDER BY total_points DESC, u.level DESC
                LIMIT ?";
        
        $db = DatabaseConfig::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        $leaderboard = $stmt->fetchAll();
        
        // اضافه کردن رتبه
        foreach ($leaderboard as $index => &$user) {
            $user['rank'] = $index + 1;
            $user['level_name'] = $this->getLevelName((int) $user['level']);
            $user['is_current_user'] = $userId && $user['id'] == $userId;
        }
        
        // اگر کاربر فعلی در لیست نیست، رتبه او را پیدا کن
        $currentUserRank = null;
        if ($userId && !in_array($userId, array_column($leaderboard, 'id'))) {
            $currentUserRank = $this->getUserRank($userId);
        }
        
        return [
            'leaderboard' => $leaderboard,
            'current_user_rank' => $currentUserRank
        ];
    }
    
    /**
     * دریافت رتبه کاربر
     */
    private function getUserRank(int $userId): ?array 
    {
        $sql = "SELECT 
                    user_rank.rank,
                    u.username,
                    u.first_name,
                    u.last_name,
                    u.level,
                    user_rank.total_points
                FROM (
                    SELECT 
                        u.id,
                        COALESCE(SUM(up.points), 0) as total_points,
                        ROW_NUMBER() OVER (ORDER BY COALESCE(SUM(up.points), 0) DESC, u.level DESC) as rank
                    FROM users u
                    LEFT JOIN user_points up ON u.id = up.user_id AND up.deleted_at IS NULL
                    WHERE u.deleted_at IS NULL AND u.status = 'active'
                    GROUP BY u.id
                ) user_rank
                JOIN users u ON user_rank.id = u.id
                WHERE u.id = ?";
        
        $db = DatabaseConfig::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if ($result) {
            $result['level_name'] = $this->getLevelName((int) $result['level']);
        }
        
        return $result ?: null;
    }
    
    /**
     * دریافت چالش‌های روزانه
     */
    public function getDailyChallenges(int $userId): array 
    {
        $today = date('Y-m-d');
        $todayStats = $this->studySessionModel->getDailyStats($userId, $today);
        
        $challenges = [
            [
                'id' => 'daily_session',
                'title' => 'جلسه روزانه',
                'description' => 'حداقل یک جلسه مطالعه داشته باش',
                'target' => 1,
                'current' => $todayStats['sessions_count'],
                'unit' => 'جلسه',
                'points' => 50,
                'completed' => $todayStats['sessions_count'] >= 1
            ],
            [
                'id' => 'daily_minutes',
                'title' => 'مطالعه 60 دقیقه‌ای',
                'description' => 'حداقل 60 دقیقه مطالعه کن',
                'target' => 60,
                'current' => $todayStats['total_duration'],
                'unit' => 'دقیقه',
                'points' => 100,
                'completed' => $todayStats['total_duration'] >= 60
            ],
            [
                'id' => 'productivity_goal',
                'title' => 'بهره‌وری بالا',
                'description' => 'میانگین بهره‌وری بالای 7 داشته باش',
                'target' => 7,
                'current' => $todayStats['avg_productivity'],
                'unit' => 'امتیاز',
                'points' => 75,
                'completed' => $todayStats['avg_productivity'] >= 7
            ]
        ];
        
        // اضافه کردن چالش تداوم
        $streakDays = $this->getConsecutiveStudyDays($userId);
        $challenges[] = [
            'id' => 'study_streak',
            'title' => 'تداوم هفتگی',
            'description' => '7 روز متوالی مطالعه کن',
            'target' => 7,
            'current' => $streakDays,
            'unit' => 'روز',
            'points' => 200,
            'completed' => $streakDays >= 7
        ];
        
        return $challenges;
    }
    
    /**
     * دریافت پیشرفت کاربر
     */
    public function getUserProgress(int $userId): array 
    {
        $userStats = $this->userModel->getUserStats($userId);
        $currentLevel = $this->getUserLevel($userId);
        $totalPoints = $this->getUserTotalPoints($userId);
        $nextLevelPoints = ($currentLevel['level'] + 1) * 1000;
        $currentLevelPoints = $currentLevel['level'] * 1000;
        
        return [
            'level' => $currentLevel['level'],
            'level_name' => $this->getLevelName($currentLevel['level']),
            'total_points' => $totalPoints,
            'points_to_next_level' => max(0, $nextLevelPoints - $totalPoints),
            'level_progress_percentage' => $nextLevelPoints > $currentLevelPoints ? 
                round((($totalPoints - $currentLevelPoints) / ($nextLevelPoints - $currentLevelPoints)) * 100, 1) : 100,
            'total_sessions' => $userStats['total_sessions'],
            'total_minutes' => $userStats['total_minutes'],
            'total_hours' => round($userStats['total_minutes'] / 60, 1),
            'achievements_count' => $userStats['total_achievements'],
            'consecutive_days' => $this->getConsecutiveStudyDays($userId),
            'rank' => $this->getUserRank($userId)
        ];
    }
}
