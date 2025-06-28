<?php
/**
 * User Model
 * مدل کاربران
 */
class User extends BaseModel 
{
    protected string $table = 'users';
    protected array $fillable = [
        'username', 'email', 'password', 'first_name', 'last_name', 
        'grade', 'school', 'avatar', 'status', 'last_login_at'
    ];
    protected array $hidden = ['password'];
    
    /**
     * یافتن کاربر بر اساس نام کاربری یا ایمیل
     */
    public function findByCredentials(string $identifier): ?array 
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (username = ? OR email = ?) 
                AND deleted_at IS NULL 
                AND status = 'active'";
        
        return $this->queryOne($sql, [$identifier, $identifier]);
    }
    
    /**
     * بررسی یکتا بودن نام کاربری
     */
    public function isUsernameUnique(string $username, int $excludeId = null): bool 
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE username = ? AND deleted_at IS NULL";
        $params = [$username];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() == 0;
    }
    
    /**
     * بررسی یکتا بودن ایمیل
     */
    public function isEmailUnique(string $email, int $excludeId = null): bool 
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE email = ? AND deleted_at IS NULL";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() == 0;
    }
    
    /**
     * بروزرسانی آخرین زمان ورود
     */
    public function updateLastLogin(int $userId): bool 
    {
        return $this->update($userId, ['last_login_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * تغییر رمز عبور
     */
    public function changePassword(int $userId, string $newPassword): bool 
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }
    
    /**
     * دریافت آمار کاربر
     */
    public function getUserStats(int $userId): array 
    {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM study_sessions WHERE user_id = ? AND deleted_at IS NULL) as total_sessions,
                    (SELECT SUM(duration) FROM study_sessions WHERE user_id = ? AND deleted_at IS NULL) as total_minutes,
                    (SELECT COUNT(*) FROM user_achievements WHERE user_id = ? AND deleted_at IS NULL) as total_achievements,
                    (SELECT SUM(points) FROM user_points WHERE user_id = ? AND deleted_at IS NULL) as total_points";
        
        $result = $this->queryOne($sql, [$userId, $userId, $userId, $userId]);
        
        return [
            'total_sessions' => (int) ($result['total_sessions'] ?? 0),
            'total_minutes' => (int) ($result['total_minutes'] ?? 0),
            'total_achievements' => (int) ($result['total_achievements'] ?? 0),
            'total_points' => (int) ($result['total_points'] ?? 0)
        ];
    }
}
