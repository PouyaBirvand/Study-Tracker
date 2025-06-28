<?php
/**
 * User Seeder
 * داده‌های اولیه کاربران
 */
class UserSeeder 
{
    private PDO $db;
    
    public function __construct() 
    {
        $this->db = DatabaseConfig::getInstance()->getConnection();
    }
    
    public function run(): void 
    {
        $users = [
            [
                'name' => 'کاربر تست',
                'email' => 'test@example.com',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'علی احمدی',
                'email' => 'ali@example.com',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'فاطمه محمدی',
                'email' => 'fateme@example.com',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, created_at) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($users as $user) {
            $stmt->execute([
                $user['name'],
                $user['email'],
                $user['password'],
                $user['created_at']
            ]);
        }
        
        echo "کاربران نمونه ایجاد شدند.\n";
    }
}
