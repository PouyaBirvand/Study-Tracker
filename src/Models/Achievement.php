<?php
/**
 * Achievement Model
 * مدل دستاوردها
 */
class Achievement extends BaseModel 
{
    protected string $table = 'achievements';
    protected array $fillable = [
        'name', 'description', 'icon', 'type', 'condition_type', 
        'condition_value', 'points', 'badge_color', 'is_active'
    ];
    
    /**
     * دریافت تمام دستاوردهای فعال
     */
    public function getActiveAchievements(): array 
    {
        return $this->findAll(1, 100, ['is_active' => 1]);
    }
    
    /**
     * بررسی و اعطای دستاوردهای جدید به کاربر
     */
    public function checkAndGrantAchievements(int $userId): array 
    {
        $newAchievements = [];
        $achievements = $this->getActiveAchievements();
        $userStats = (new User())->getUserStats($userId);
        
        foreach ($achievements as $achievement) {
            if (!$this->userHasAchievement($userId, $achievement['id'])) {
                if ($this->checkAchievementCondition($achievement, $userStats, $userId)) {
                    $this->grantAchievementToUser($userId, $achievement['id']);
                    $newAchievements[] = $achievement;
                }
            }
        }
        
        return $newAchievements;
    }
    
    /**
     * بررسی اینکه کاربر دستاورد را دارد یا نه
     */
    private function userHasAchievement(int $userId, int $achievementId): bool 
    {
        $sql = "SELECT COUNT(*) FROM user_achievements 
                WHERE user_id = ? AND achievement_id = ? AND deleted_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $achievementId]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * بررسی شرایط دستاورد
     */
    private function checkAchievementCondition(array $achievement, array $userStats, int $userId): bool 
    {
        switch ($achievement['condition_type']) {
            case 'total_sessions':
                return $userStats['total_sessions'] >= $achievement['condition_value'];
                
            case 'total_minutes':
                return $userStats['total_minutes'] >= $achievement['condition_value'];
                
            case 'consecutive_days':
                return $this->getConsecutiveStudyDays($userId) >= $achievement['condition_value'];
                
            case 'daily_goal':
                return $this->checkDailyGoalAchievement($userId, $achievement['condition_value']);
                
            case 'subject_mastery':
                return $this->checkSubjectMastery($userId, $achievement['condition_value']);
                
            default:
                return false;
        }
    }
    
    /**
     * اعطای دستاورد به کاربر
     */
    private function grantAchievementToUser(int $userId, int $achievementId): void 
    {
        $sql = "INSERT INTO user_achievements (user_id, achievement_id, earned_at, created_at, updated_at) 
                VALUES (?, ?, NOW(), NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $achievementId]);
        
        // اضافه کردن امتیاز به کاربر
        $achievement = $this->find($achievementId);
        if ($achievement && $achievement['points'] > 0) {
            $this->addPointsToUser($userId, $achievement['points'], 'achievement', $achievementId);
        }
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
                ORDER BY study_date DESC";
        
        $dates = $this->query($sql, [$userId]);
        
        if (empty($dates)) {
            return 0;
        }
        
        $consecutive = 0;
        $currentDate = new DateTime();
        
        foreach ($dates as $dateRow) {
            $studyDate = new DateTime($dateRow['study_date']);
            $diff = $currentDate->diff($studyDate)->days;
            
            if ($diff === $consecutive) {
                $consecutive++;
                $currentDate = $studyDate;
            } else {
                break;
            }
        }
        
        return $consecutive;
    }
    
    /**
     * بررسی دستیابی به هدف روزانه
     */
    private function checkDailyGoalAchievement(int $userId, int $requiredDays): bool 
    {
        $sql = "SELECT COUNT(*) as achieved_days
                FROM (
                    SELECT DATE(session_date) as study_date, SUM(duration) as daily_minutes
                    FROM study_sessions 
                    WHERE user_id = ? AND deleted_at IS NULL
                    GROUP BY DATE(session_date)
                    HAVING daily_minutes >= 60
                ) as daily_stats";
        
        $result = $this->queryOne($sql, [$userId]);
        return ($result['achieved_days'] ?? 0) >= $requiredDays;
    }
    
    /**
     * بررسی تسلط بر درس
     */
    private function checkSubjectMastery(int $userId, int $requiredHours): bool 
    {
        $sql = "SELECT MAX(total_minutes) as max_subject_minutes
                FROM (
                    SELECT subject_id, SUM(duration) as total_minutes
                    FROM study_sessions 
                    WHERE user_id = ? AND deleted_at IS NULL
                    GROUP BY subject_id
                ) as subject_stats";
        
        $result = $this->queryOne($sql, [$userId]);
        $maxMinutes = $result['max_subject_minutes'] ?? 0;
        
        return ($maxMinutes / 60) >= $requiredHours;
    }
    
    /**
     * اضافه کردن امتیاز به کاربر
     */
    private function addPointsToUser(int $userId, int $points, string $type, int $referenceId): void 
    {
        $sql = "INSERT INTO user_points (user_id, points, type, reference_id, earned_at, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $points, $type, $referenceId]);
    }
    
    /**
     * دریافت دستاوردهای کاربر
     */
    public function getUserAchievements(int $userId): array 
    {
        $sql = "SELECT a.*, ua.earned_at
                FROM achievements a
                INNER JOIN user_achievements ua ON a.id = ua.achievement_id
                WHERE ua.user_id = ? AND ua.deleted_at IS NULL
                ORDER BY ua.earned_at DESC";
        
        return $this->query($sql, [$userId]);
    }
}
