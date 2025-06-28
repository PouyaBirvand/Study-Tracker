<?php
/**
 * Subject Model
 * مدل دروس
 */
class Subject extends BaseModel 
{
    protected string $table = 'subjects';
    protected array $fillable = [
        'user_id', 'name', 'description', 'color', 'icon', 
        'target_hours_per_week', 'priority', 'status'
    ];
    
    /**
     * دریافت دروس کاربر
     */
    public function getUserSubjects(int $userId): array 
    {
        $sql = "SELECT s.*, 
                    COALESCE(SUM(ss.duration), 0) as total_studied_minutes,
                    COUNT(ss.id) as total_sessions
                FROM {$this->table} s
                LEFT JOIN study_sessions ss ON s.id = ss.subject_id AND ss.deleted_at IS NULL
                WHERE s.user_id = ? AND s.deleted_at IS NULL
                GROUP BY s.id
                ORDER BY s.priority DESC, s.name";
        
        return $this->query($sql, [$userId]);
    }
    
    /**
     * دریافت آمار درس
     */
    public function getSubjectStats(int $subjectId, int $userId): array 
    {
        $sql = "SELECT 
                    COUNT(ss.id) as total_sessions,
                    SUM(ss.duration) as total_minutes,
                    AVG(ss.productivity_score) as avg_productivity,
                    MAX(ss.session_date) as last_study_date,
                    s.target_hours_per_week
                FROM subjects s
                LEFT JOIN study_sessions ss ON s.id = ss.subject_id AND ss.deleted_at IS NULL
                WHERE s.id = ? AND s.user_id = ? AND s.deleted_at IS NULL
                GROUP BY s.id";
        
        $result = $this->queryOne($sql, [$subjectId, $userId]);
        
        if (!$result) {
            return [];
        }
        
        $targetMinutesPerWeek = ($result['target_hours_per_week'] ?? 0) * 60;
        $totalMinutes = (int) ($result['total_minutes'] ?? 0);
        
        return [
            'total_sessions' => (int) ($result['total_sessions'] ?? 0),
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 1),
            'avg_productivity' => round((float) ($result['avg_productivity'] ?? 0), 1),
            'last_study_date' => $result['last_study_date'],
            'target_minutes_per_week' => $targetMinutesPerWeek,
            'progress_percentage' => $targetMinutesPerWeek > 0 ? 
                round(($totalMinutes / $targetMinutesPerWeek) * 100, 1) : 0
        ];
    }
    
    /**
     * دریافت پیشرفت هفتگی دروس
     */
    public function getWeeklyProgress(int $userId): array 
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        
        $sql = "SELECT 
                    s.id,
                    s.name,
                    s.color,
                    s.target_hours_per_week,
                    COALESCE(SUM(ss.duration), 0) as studied_minutes
                FROM subjects s
                LEFT JOIN study_sessions ss ON s.id = ss.subject_id 
                    AND ss.session_date >= ? 
                    AND ss.deleted_at IS NULL
                WHERE s.user_id = ? AND s.deleted_at IS NULL
                GROUP BY s.id
                ORDER BY s.priority DESC";
        
        $results = $this->query($sql, [$startOfWeek, $userId]);
        
        foreach ($results as &$subject) {
            $targetMinutes = ($subject['target_hours_per_week'] ?? 0) * 60;
            $studiedMinutes = (int) $subject['studied_minutes'];
            
            $subject['target_minutes'] = $targetMinutes;
            $subject['progress_percentage'] = $targetMinutes > 0 ? 
                round(($studiedMinutes / $targetMinutes) * 100, 1) : 0;
            $subject['remaining_minutes'] = max(0, $targetMinutes - $studiedMinutes);
        }
        
        return $results;
    }
}
