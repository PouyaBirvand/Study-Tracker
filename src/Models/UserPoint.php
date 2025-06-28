<?php
/**
 * User Point Model
 * مدل امتیاز کاربر
 */
require_once __DIR__ . '/BaseModel.php';

class UserPoint extends BaseModel 
{
    protected string $table = 'user_points';
    
    public function addPoints(int $userId, int $points, string $reason, string $type = 'earned'): bool 
    {
        $sql = "INSERT INTO {$this->table} (user_id, points, reason, type, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([$userId, $points, $reason, $type]);
        
        if ($result) {
            $this->updateUserTotalPoints($userId);
        }
        
        return $result;
    }
    
    public function getUserTotalPoints(int $userId): int 
    {
        $sql = "SELECT COALESCE(SUM(CASE WHEN type = 'earned' THEN points ELSE -points END), 0) as total_points 
                FROM {$this->table} WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        return (int)$result['total_points'];
    }
    
    public function getUserPointsHistory(int $userId, int $limit = 20): array 
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    public function getPointsBreakdown(int $userId): array 
    {
        $sql = "SELECT 
                    reason,
                    SUM(CASE WHEN type = 'earned' THEN points ELSE -points END) as total_points,
                    COUNT(*) as count
                FROM {$this->table} 
                WHERE user_id = ? 
                GROUP BY reason 
                ORDER BY total_points DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }
    
    public function getLeaderboard(int $limit = 10): array 
    {
        $sql = "SELECT 
                    u.id,
                    u.name,
                    u.email,
                    COALESCE(SUM(CASE WHEN up.type = 'earned' THEN up.points ELSE -up.points END), 0) as total_points
                FROM users u
                LEFT JOIN {$this->table} up ON u.id = up.user_id
                GROUP BY u.id, u.name, u.email
                ORDER BY total_points DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
    
    private function updateUserTotalPoints(int $userId): void 
    {
        $totalPoints = $this->getUserTotalPoints($userId);
        
        $sql = "UPDATE users SET total_points = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$totalPoints, $userId]);
    }
    
    public function getMonthlyPoints(int $userId, string $month = null): array 
    {
        $month = $month ?: date('Y-m');
        
        $sql = "SELECT 
                    DATE(created_at) as date,
                    SUM(CASE WHEN type = 'earned' THEN points ELSE -points END) as daily_points
                FROM {$this->table} 
                WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?
                GROUP BY DATE(created_at)
                ORDER BY date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $month]);
        
        return $stmt->fetchAll();
    }
}
