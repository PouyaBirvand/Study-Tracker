<?php
/**
 * Goal Model
 * مدل اهداف
 */
class Goal extends BaseModel 
{
    protected string $table = 'goals';
    protected array $fillable = [
        'user_id', 'title', 'description', 'type', 'target_value', 
        'current_value', 'unit', 'deadline', 'priority', 'status'
    ];
    
    /**
     * دریافت اهداف کاربر
     */
    public function getUserGoals(int $userId, string $status = null): array 
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? AND deleted_at IS NULL";
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY priority DESC, deadline ASC";
        
        $goals = $this->query($sql, $params);
        
        // محاسبه درصد پیشرفت
        foreach ($goals as &$goal) {
            $goal['progress_percentage'] = $this->calculateProgress($goal);
            $goal['days_remaining'] = $this->calculateDaysRemaining($goal['deadline']);
        }
        
        return $goals;
    }
    
    /**
     * بروزرسانی پیشرفت هدف
     */
    public function updateProgress(int $goalId, float $newValue): bool 
    {
        $goal = $this->find($goalId);
        if (!$goal) {
            return false;
        }
        
        $status = $newValue >= $goal['target_value'] ? 'completed' : 'in_progress';
        
        return $this->update($goalId, [
            'current_value' => $newValue,
            'status' => $status,
            'completed_at' => $status === 'completed' ? date('Y-m-d H:i:s') : null
        ]);
    }
    
    /**
     * دریافت اهداف منقضی شده
     */
    public function getExpiredGoals(int $userId): array 
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? 
                AND deadline < NOW() 
                AND status != 'completed' 
                AND deleted_at IS NULL
                ORDER BY deadline DESC";
        
        return $this->query($sql, [$userId]);
    }
    
    /**
     * دریافت اهداف امروز
     */
    public function getTodayGoals(int $userId): array 
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? 
                AND DATE(deadline) = CURDATE() 
                AND status != 'completed' 
                AND deleted_at IS NULL
                ORDER BY priority DESC";
        
        return $this->query($sql, [$userId]);
    }
    
    /**
     * محاسبه درصد پیشرفت
     */
    private function calculateProgress(array $goal): float 
    {
        if ($goal['target_value'] <= 0) {
            return 0;
        }
        
        $progress = ($goal['current_value'] / $goal['target_value']) * 100;
        return min(100, max(0, round($progress, 1)));
    }
    
    /**
     * محاسبه روزهای باقی‌مانده
     */
    private function calculateDaysRemaining(string $deadline): int 
    {
        $deadlineDate = new DateTime($deadline);
        $today = new DateTime();
        $diff = $today->diff($deadlineDate);
        
        return $diff->invert ? -$diff->days : $diff->days;
    }
}
