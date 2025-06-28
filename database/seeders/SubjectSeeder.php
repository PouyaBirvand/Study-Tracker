<?php
/**
 * Subject Seeder
 * داده‌های اولیه دروس
 */
class SubjectSeeder 
{
    private PDO $db;
    
    public function __construct() 
    {
        $this->db = DatabaseConfig::getInstance()->getConnection();
    }
    
    public function run(): void 
    {
        $subjects = [
            ['name' => 'ریاضی', 'color' => '#e74c3c', 'description' => 'ریاضیات عمومی'],
            ['name' => 'فیزیک', 'color' => '#3498db', 'description' => 'فیزیک پایه'],
            ['name' => 'شیمی', 'color' => '#2ecc71', 'description' => 'شیمی عمومی'],
            ['name' => 'زبان انگلیسی', 'color' => '#f39c12', 'description' => 'زبان انگلیسی'],
            ['name' => 'ادبیات فارسی', 'color' => '#9b59b6', 'description' => 'ادبیات و زبان فارسی'],
            ['name' => 'تاریخ', 'color' => '#34495e', 'description' => 'تاریخ ایران و جهان'],
            ['name' => 'جغرافیا', 'color' => '#16a085', 'description' => 'جغرافیای طبیعی و انسانی'],
            ['name' => 'علوم اجتماعی', 'color' => '#e67e22', 'description' => 'علوم اجتماعی']
        ];
        
        // دریافت کاربران
        $users = $this->db->query("SELECT id FROM users")->fetchAll();
        
        $stmt = $this->db->prepare("
            INSERT INTO subjects (user_id, name, description, color, created_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($users as $user) {
            foreach ($subjects as $subject) {
                $stmt->execute([
                    $user['id'],
                    $subject['name'],
                    $subject['description'],
                    $subject['color'],
                    date('Y-m-d H:i:s')
                ]);
            }
        }
        
        echo "دروس نمونه ایجاد شدند.\n";
    }
}
