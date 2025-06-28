<?php
/**
 * Achievement Seeder
 * داده‌های اولیه دستاوردها
 */
class AchievementSeeder 
{
    private PDO $db;
    
    public function __construct() 
    {
        $this->db = DatabaseConfig::getInstance()->getConnection();
    }
    
    public function run(): void 
    {
        $achievements = [
            [
                'name' => 'اولین قدم',
                'description' => 'اولین جلسه مطالعه خود را تکمیل کنید',
                'type' => 'session_count',
                'target_value' => 1,
                'points' => 50,
                'icon' => '🎯'
            ],
            [
                'name' => 'مطالعه‌گر مداوم',
                'description' => '10 جلسه مطالعه تکمیل کنید',
                'type' => 'session_count',
                'target_value' => 10,
                'points' => 200,
                'icon' => '📚'
            ],
            [
                'name' => 'استاد زمان',
                'description' => '100 ساعت مطالعه تکمیل کنید',
                'type' => 'total_hours',
                'target_value' => 100,
                'points' => 500,
                'icon' => '⏰'
            ],
            [
                'name' => 'هفت روز پیاپی',
                'description' => '7 روز متوالی مطالعه کنید',
                'type' => 'daily_streak',
                'target_value' => 7,
                'points' => 300,
                'icon' => '🔥'
            ],
            [
                'name' => 'ماراتن مطالعه',
                'description' => 'یک جلسه 4 ساعته تکمیل کنید',
                'type' => 'single_session',
                'target_value' => 240,
                'points' => 400,
                'icon' => '🏃‍♂️'
            ],
            [
                'name' => 'بهره‌وری بالا',
                'description' => '10 جلسه با امتیاز بهره‌وری 9 یا بالاتر',
                'type' => 'high_productivity',
                'target_value' => 10,
                'points' => 350,
                'icon' => '⭐'
            ],
            [
                'name' => 'تنوع در مطالعه',
                'description' => 'در 5 درس مختلف مطالعه کنید',
                'type' => 'subject_variety',
                'target_value' => 5,
                'points' => 250,
                'icon' => '🌈'
            ],
            [
                'name' => 'هدف‌گذار موفق',
                'description' => '5 هدف تکمیل کنید',
                'type' => 'goals_completed',
                'target_value' => 5,
                'points' => 300,
                'icon' => '🎯'
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO achievements (name, description, type, target_value, points, icon, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($achievements as $achievement) {
            $stmt->execute([
                $achievement['name'],
                $achievement['description'],
                $achievement['type'],
                $achievement['target_value'],
                $achievement['points'],
                $achievement['icon'],
                date('Y-m-d H:i:s')
            ]);
        }
        
        echo "دستاوردها ایجاد شدند.\n";
    }
}
