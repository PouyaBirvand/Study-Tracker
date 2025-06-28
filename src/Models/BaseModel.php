<?php
/**
 * Base Model Class
 * کلاس پایه برای تمام مدل‌ها
 */
abstract class BaseModel 
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = ['password'];
    
    public function __construct() 
    {
        $this->db = DatabaseConfig::getInstance()->getConnection();
    }
    
    /**
     * یافتن رکورد بر اساس ID
     */
    public function find(int $id): ?array 
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            return $result ? $this->hideFields($result) : null;
        } catch (PDOException $e) {
            throw new Exception("خطا در یافتن رکورد: " . $e->getMessage());
        }
    }
    
    /**
     * یافتن همه رکوردها
     */
    public function findAll(int $page = 1, int $limit = 10, array $conditions = []): array 
    {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE deleted_at IS NULL";
            $params = [];
            
            // اضافه کردن شرایط
            foreach ($conditions as $field => $value) {
                $whereClause .= " AND {$field} = ?";
                $params[] = $value;
            }
            
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            return array_map([$this, 'hideFields'], $results);
        } catch (PDOException $e) {
            throw new Exception("خطا در دریافت رکوردها: " . $e->getMessage());
        }
    }
    
    /**
     * ایجاد رکورد جدید
     */
    public function create(array $data): int 
    {
        try {
            // فیلتر کردن فیلدهای مجاز
            $filteredData = $this->filterFillable($data);
            $filteredData['created_at'] = date('Y-m-d H:i:s');
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
            
            $fields = implode(', ', array_keys($filteredData));
            $placeholders = ':' . implode(', :', array_keys($filteredData));
            
            $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})");
            $stmt->execute($filteredData);
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("خطا در ایجاد رکورد: " . $e->getMessage());
        }
    }
    
    /**
     * بروزرسانی رکورد
     */
    public function update(int $id, array $data): bool 
    {
        try {
            $filteredData = $this->filterFillable($data);
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
            
            $setClause = [];
            foreach (array_keys($filteredData) as $field) {
                $setClause[] = "{$field} = :{$field}";
            }
            
            $filteredData['id'] = $id;
            $stmt = $this->db->prepare("UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = :id");
            
            return $stmt->execute($filteredData);
        } catch (PDOException $e) {
            throw new Exception("خطا در بروزرسانی رکورد: " . $e->getMessage());
        }
    }
    
    /**
     * حذف نرم رکورد
     */
    public function delete(int $id): bool 
    {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = ? WHERE {$this->primaryKey} = ?");
            return $stmt->execute([date('Y-m-d H:i:s'), $id]);
        } catch (PDOException $e) {
            throw new Exception("خطا در حذف رکورد: " . $e->getMessage());
        }
    }
    
    /**
     * شمارش رکوردها
     */
    public function count(array $conditions = []): int 
    {
        try {
            $whereClause = "WHERE deleted_at IS NULL";
            $params = [];
            
            foreach ($conditions as $field => $value) {
                $whereClause .= " AND {$field} = ?";
                $params[] = $value;
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} {$whereClause}");
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("خطا در شمارش رکوردها: " . $e->getMessage());
        }
    }
    
    /**
     * فیلتر کردن فیلدهای مجاز
     */
    protected function filterFillable(array $data): array 
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * مخفی کردن فیلدهای حساس
     */
    protected function hideFields(array $data): array 
    {
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }
        return $data;
    }
    
    /**
     * اجرای کوئری سفارشی
     */
    protected function query(string $sql, array $params = []): array 
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("خطا در اجرای کوئری: " . $e->getMessage());
        }
    }
    
    /**
     * اجرای کوئری و دریافت یک رکورد
     */
    protected function queryOne(string $sql, array $params = []): ?array 
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            throw new Exception("خطا در اجرای کوئری: " . $e->getMessage());
        }
    }
    
    /**
     * شروع تراکنش
     */
    protected function beginTransaction(): void 
    {
        $this->db->beginTransaction();
    }
    
    /**
     * تایید تراکنش
     */
    protected function commit(): void 
    {
        $this->db->commit();
    }
    
    /**
     * لغو تراکنش
     */
    protected function rollback(): void 
    {
        $this->db->rollback();
    }
}
