-- Study Tracker Database Schema
-- ساختار دیتابیس سیستم ردیابی مطالعه

SET FOREIGN_KEY_CHECKS = 0;

-- جدول کاربران
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    avatar VARCHAR(255),
    timezone VARCHAR(50) DEFAULT 'Asia/Tehran',
    language VARCHAR(10) DEFAULT 'fa',
    total_points INT DEFAULT 0,
    email_notifications BOOLEAN DEFAULT TRUE,
    push_notifications BOOLEAN DEFAULT TRUE,
    study_reminders BOOLEAN DEFAULT TRUE,
    show_in_leaderboard BOOLEAN DEFAULT TRUE,
    share_statistics BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_total_points (total_points),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول دروس
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3498db',
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول جلسات مطالعه
CREATE TABLE study_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    duration_minutes INT DEFAULT 0,
    productivity_score TINYINT CHECK (productivity_score BETWEEN 1 AND 10),
    notes TEXT,
    break_time_minutes INT DEFAULT 0,
    interruptions_count INT DEFAULT 0,
    mood ENUM('excellent', 'good', 'average', 'poor', 'terrible') DEFAULT 'average',
    environment ENUM('home', 'library', 'cafe', 'office', 'other') DEFAULT 'home',
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_start_time (start_time),
    INDEX idx_is_completed (is_completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول اهداف
CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    target_value INT NOT NULL,
    target_unit ENUM('minutes', 'hours', 'sessions', 'pages', 'chapters') NOT NULL,
    current_progress INT DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'completed', 'paused', 'cancelled') DEFAULT 'active',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    reward TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول دستاوردها
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    badge_color VARCHAR(7) DEFAULT '#f39c12',
    condition_type ENUM('study_time', 'sessions_count', 'streak_days', 'productivity_avg', 'goals_completed') NOT NULL,
    condition_value INT NOT NULL,
    points_reward INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_condition_type (condition_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول دستاوردهای کاربران
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user_id (user_id),
    INDEX idx_earned_at (earned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول امتیازات کاربران
CREATE TABLE user_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,
    reason VARCHAR(200) NOT NULL,
    type ENUM('earned', 'spent') DEFAULT 'earned',
    reference_type ENUM('study_session', 'goal_completion', 'achievement', 'streak', 'other') DEFAULT 'other',
    reference_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول یادداشت‌ها
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT NULL,
    session_id INT NULL,
    title VARCHAR(200),
    content TEXT NOT NULL,
    tags JSON,
    is_favorite BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (session_id) REFERENCES study_sessions(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_is_favorite (is_favorite),
    FULLTEXT idx_content (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تنظیمات پومودورو
CREATE TABLE pomodoro_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    work_duration INT DEFAULT 25,
    short_break_duration INT DEFAULT 5,
    long_break_duration INT DEFAULT 15,
    sessions_until_long_break INT DEFAULT 4,
    auto_start_breaks BOOLEAN DEFAULT FALSE,
    auto_start_work BOOLEAN DEFAULT FALSE,
    sound_enabled BOOLEAN DEFAULT TRUE,
    sound_volume INT DEFAULT 50,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول جلسات پومودورو
CREATE TABLE pomodoro_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    study_session_id INT NULL,
    type ENUM('work', 'short_break', 'long_break') NOT NULL,
    planned_duration INT NOT NULL,
    actual_duration INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (study_session_id) REFERENCES study_sessions(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_study_session_id (study_session_id),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول یادآوری‌ها
CREATE TABLE reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    type ENUM('study', 'break', 'goal', 'custom') DEFAULT 'custom',
    scheduled_at TIMESTAMP NOT NULL,
    is_sent BOOLEAN DEFAULT FALSE,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_pattern VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_is_sent (is_sent),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول گزارشات
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    title VARCHAR(200) NOT NULL,
    data JSON NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- درج داده‌های اولیه برای دستاوردها
INSERT INTO achievements (name, description, icon, condition_type, condition_value, points_reward) VALUES
('مطالعه اول', 'اولین جلسه مطالعه خود را تکمیل کنید', '🎯', 'sessions_count', 1, 10),
('مطالعه‌کار مبتدی', '10 جلسه مطالعه تکمیل کنید', '📚', 'sessions_count', 10, 50),
('مطالعه‌کار حرفه‌ای', '100 جلسه مطالعه تکمیل کنید', '🏆', 'sessions_count', 100, 200),
('ساعت اول', '1 ساعت مطالعه کنید', '⏰', 'study_time', 60, 20),
('روز کامل', '8 ساعت در یک روز مطالعه کنید', '🌟', 'study_time', 480, 100),
('هفته پیوسته', '7 روز پیاپی مطالعه کنید', '🔥', 'streak_days', 7, 150),
('ماه پیوسته', '30 روز پیاپی مطالعه کنید', '💎', 'streak_days', 30, 500),
('بهره‌وری بالا', 'میانگین بهره‌وری 8 یا بالاتر داشته باشید', '⚡', 'productivity_avg', 8, 100),
('هدف‌گذار', 'اولین هدف خود را تکمیل کنید', '🎯', 'goals_completed', 1, 75),
('قهرمان اهداف', '10 هدف تکمیل کنید', '👑', 'goals_completed', 10, 300);

-- ایجاد ویوهای مفید
CREATE VIEW user_statistics AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.total_points,
    COUNT(DISTINCT ss.id) as total_sessions,
    COALESCE(SUM(ss.duration_minutes), 0) as total_study_minutes,
    COALESCE(AVG(ss.productivity_score), 0) as avg_productivity,
    COUNT(DISTINCT g.id) as total_goals,
    COUNT(DISTINCT CASE WHEN g.status = 'completed' THEN g.id END) as completed_goals,
    COUNT(DISTINCT ua.achievement_id) as earned_achievements
FROM users u
LEFT JOIN study_sessions ss ON u.id = ss.user_id AND ss.is_completed = TRUE
LEFT JOIN goals g ON u.id = g.user_id
LEFT JOIN user_achievements ua ON u.id = ua.user_id
WHERE u.deleted_at IS NULL
GROUP BY u.id, u.name, u.email, u.total_points;

-- ویو آمار روزانه
CREATE VIEW daily_statistics AS
SELECT 
    ss.user_id,
    DATE(ss.start_time) as study_date,
    COUNT(*) as sessions_count,
    SUM(ss.duration_minutes) as total_minutes,
    AVG(ss.productivity_score) as avg_productivity,
    COUNT(DISTINCT ss.subject_id) as subjects_studied
FROM study_sessions ss
WHERE ss.is_completed = TRUE
GROUP BY ss.user_id, DATE(ss.start_time);

-- ایجاد تریگرها
DELIMITER //

-- تریگر برای محاسبه مدت زمان جلسه مطالعه
CREATE TRIGGER calculate_session_duration
    BEFORE UPDATE ON study_sessions
    FOR EACH ROW
BEGIN
    IF NEW.end_time IS NOT NULL AND OLD.end_time IS NULL THEN
        SET NEW.duration_minutes = TIMESTAMPDIFF(MINUTE, NEW.start_time, NEW.end_time);
        SET NEW.is_completed = TRUE;
    END IF;
END//

-- تریگر برای اعطای امتیاز پس از تکمیل جلسه
CREATE TRIGGER award_session_points
    AFTER UPDATE ON study_sessions
    FOR EACH ROW
BEGIN
    IF NEW.is_completed = TRUE AND OLD.is_completed = FALSE THEN
        -- امتیاز بر اساس مدت زمان مطالعه
        SET @base_points = FLOOR(NEW.duration_minutes / 15) * 5;
        
        -- امتیاز اضافی بر اساس بهره‌وری
        SET @productivity_bonus = CASE 
            WHEN NEW.productivity_score >= 9 THEN 20
            WHEN NEW.productivity_score >= 7 THEN 10
            WHEN NEW.productivity_score >= 5 THEN 5
            ELSE 0
        END;
        
        SET @total_points = @base_points + @productivity_bonus;
        
        INSERT INTO user_points (user_id, points, reason, type, reference_type, reference_id)
        VALUES (NEW.user_id, @total_points, 'تکمیل جلسه مطالعه', 'earned', 'study_session', NEW.id);
    END IF;
END//

-- تریگر برای اعطای امتیاز پس از تکمیل هدف
CREATE TRIGGER award_goal_completion_points
    AFTER UPDATE ON goals
    FOR EACH ROW
BEGIN
        IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- امتیاز بر اساس نوع و اولویت هدف
        SET @goal_points = CASE NEW.type
            WHEN 'daily' THEN 25
            WHEN 'weekly' THEN 75
            WHEN 'monthly' THEN 200
            WHEN 'custom' THEN 100
        END;
        
        -- امتیاز اضافی بر اساس اولویت
        SET @priority_bonus = CASE NEW.priority
            WHEN 'urgent' THEN 50
            WHEN 'high' THEN 30
            WHEN 'medium' THEN 15
            WHEN 'low' THEN 5
        END;
        
        SET @total_points = @goal_points + @priority_bonus;
        
        INSERT INTO user_points (user_id, points, reason, type, reference_type, reference_id)
        VALUES (NEW.user_id, @total_points, CONCAT('تکمیل هدف: ', NEW.title), 'earned', 'goal_completion', NEW.id);
    END IF;
END//

-- تریگر برای به‌روزرسانی مجموع امتیازات کاربر
CREATE TRIGGER update_user_total_points
    AFTER INSERT ON user_points
    FOR EACH ROW
BEGIN
    UPDATE users 
    SET total_points = (
        SELECT COALESCE(SUM(CASE WHEN type = 'earned' THEN points ELSE -points END), 0)
        FROM user_points 
        WHERE user_id = NEW.user_id
    )
    WHERE id = NEW.user_id;
END//

DELIMITER ;

-- ایجاد اندکس‌های کامپوزیت برای بهبود عملکرد
CREATE INDEX idx_user_date ON study_sessions(user_id, start_time);
CREATE INDEX idx_user_subject_date ON study_sessions(user_id, subject_id, start_time);
CREATE INDEX idx_goal_user_status ON goals(user_id, status);
CREATE INDEX idx_points_user_type ON user_points(user_id, type, created_at);

SET FOREIGN_KEY_CHECKS = 1;


-- چت روم بعدا ساخته میشه...

-- CREATE TABLE chat_rooms (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     name VARCHAR(100) NOT NULL,
--     description TEXT,
--     subject_id INT,
--     created_by INT NOT NULL,
--     is_public BOOLEAN DEFAULT true,
--     max_members INT DEFAULT 50,
--     is_active BOOLEAN DEFAULT true,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
--     FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
--     INDEX idx_subject_id (subject_id),
--     INDEX idx_created_by (created_by),
--     INDEX idx_is_public (is_public)
-- );

-- -- جدول پیام‌ها
-- CREATE TABLE chat_messages (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     room_id INT NOT NULL,
--     user_id INT NOT NULL,
--     message TEXT NOT NULL,
--     message_type ENUM('text', 'image', 'file') DEFAULT 'text',
--     file_url VARCHAR(255),
--     is_edited BOOLEAN DEFAULT false,
--     edited_at TIMESTAMP NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
--     INDEX idx_room_id (room_id),
--     INDEX idx_user_id (user_id),
--     INDEX idx_created_at (created_at)
-- );

-- -- جدول اعضای اتاق
-- CREATE TABLE room_members (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     room_id INT NOT NULL,
--     user_id INT NOT NULL,
--     role ENUM('member', 'moderator', 'admin') DEFAULT 'member',
--     is_muted BOOLEAN DEFAULT false,
--     joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
--     UNIQUE KEY unique_room_user (room_id, user_id),
--     INDEX idx_room_id (room_id),
--     INDEX idx_user_id (user_id)
-- );

-- -- جدول اعلان‌های چت
-- CREATE TABLE chat_notifications (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     user_id INT NOT NULL,
--     room_id INT NOT NULL,
--     message_id INT NOT NULL,
--     is_read BOOLEAN DEFAULT false,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
--     FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
--     FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE,
--     INDEX idx_user_id (user_id),
--     INDEX idx_is_read (is_read)
-- );

-- -- درج اتاق‌های پیش‌فرض
-- INSERT INTO chat_rooms (name, description, created_by, is_public) VALUES
-- ('اتاق عمومی', 'اتاق چت عمومی برای همه کاربران', 1, true),
-- ('کمک و راهنمایی', 'اتاق کمک و پاسخ به سوالات', 1, true),
-- ('انگیزشی', 'اتاق انگیزشی و تشویق یکدیگر', 1, true);