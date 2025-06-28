<?php
/**
 * StudySession Model
 * مدل جلسات مطالعه
 */
class StudySession extends BaseModel 
{
    protected string $table = 'study_sessions';
    protected array $fillable = [
        'user_id', 'subject_id', 'title', 'duration', 'break_time', 
        'notes', 'mood', 'productivity_score', 'session_date'
    ];
    
    /**
     * دریافت جلسات مطالعه کاربر
     */
    public function getUserSessions(int $userId, int $page = 1, int $limit = 10): array 
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT ss.*, s.name as subject_name, s.color as subject_color
                FROM {$this->table} ss
                LEFT JOIN subjects s ON ss.subject_id = s.id
                WHERE ss.user_id = ? AND ss.deleted_at IS NULL
                ORDER BY ss.session_date DESC, ss.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->query($sql, [$userId, $limit, $offset]);
    }
    
    /**
     * دریافت آمار روزانه
     */
    public function getDailyStats(int $userId, string $date): array 
    {
        $sql = "SELECT 
                    COUNT(*) as sessions_count,
                    SUM(duration) as total_duration,
                    SUM(break_time) as total_breaks,
                    AVG(productivity_score) as avg_productivity,
                    GROUP_CONCAT(DISTINCT s.name) as subjects
                FROM {$this->table} ss
                LEFT JOIN subjects s ON ss.subject_id = s.id
                WHERE ss.user_id = ? 
                AND DATE(ss.session_date) = ? 
                AND ss.deleted_at IS NULL";
        
        $result = $this->queryOne($sql, [$userId, $date]);
        
        return [
            'sessions_count' => (int) ($result['sessions_count'] ?? 0),
            'total_duration' => (int) ($result['total_duration'] ?? 0),
            'total_breaks' => (int) ($result['total_breaks'] ?? 0),
            'avg_productivity' => round((float) ($result['avg_productivity'] ?? 0), 1),
            'subjects' => $result['subjects'] ? explode(',', $result['subjects']) : []
        ];
    }
    
    /**
     * دریافت آمار هفتگی
     */
    public function getWeeklyStats(int $userId, string $startDate): array 
    {
        $sql = "SELECT 
                    DATE(session_date) as date,
                    COUNT(*) as sessions,
                    SUM(duration) as total_minutes
                FROM {$this->table}
                WHERE user_id = ? 
                AND session_date >= ? 
                AND session_date < DATE_ADD(?, INTERVAL 7 DAY)
                AND deleted_at IS NULL
                GROUP BY DATE(session_date)
                ORDER BY date";
        
        return $this->query($sql, [$userId, $startDate, $startDate]);
    }
    
    /**
     * دریافت آمار ماهانه
     */
    public function getMonthlyStats(int $userId, int $year, int $month): array 
    {
        $sql = "SELECT 
                    DAY(session_date) as day,
                    COUNT(*) as sessions,
                    SUM(duration) as total_minutes,
                    AVG(productivity_score) as avg_productivity
                FROM {$this->table}
                WHERE user_id = ? 
                AND YEAR(session_date) = ? 
                AND MONTH(session_date) = ?
                AND deleted_at IS NULL
                GROUP BY DAY(session_date)
                ORDER BY day";
        
        return $this->query($sql, [$userId, $year, $month]);
    }
    
    /**
     * دریافت بهترین روزهای مطالعه
     */
    public function getBestStudyDays(int $userId, int $limit = 5): array 
    {
        $sql = "SELECT 
                    DATE(session_date) as date,
                    COUNT(*) as sessions_count,
                    SUM(duration) as total_duration,
                    AVG(productivity_score) as avg_productivity
                FROM {$this->table}
                WHERE user_id = ? AND deleted_at IS NULL
                GROUP BY DATE(session_date)
                ORDER BY total_duration DESC, avg_productivity DESC
                LIMIT ?";
        
        return $this->query($sql, [$userId, $limit]);
    }
}
