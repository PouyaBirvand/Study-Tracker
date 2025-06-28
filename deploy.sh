#!/bin/bash

# Study Tracker Deployment Script
# اسکریپت استقرار Study Tracker

set -e

echo "🚀 شروع فرآیند استقرار..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/study-tracker"
BACKUP_DIR="/var/backups/study-tracker"
DB_NAME="study_tracker"

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Create backup
create_backup() {
    log_info "ایجاد پشتیبان..."
    
    # Create backup directory
    mkdir -p $BACKUP_DIR/$(date +%Y%m%d_%H%M%S)
        CURRENT_BACKUP=$BACKUP_DIR/$(date +%Y%m%d_%H%M%S)
    
    # Backup files
    if [ -d "$PROJECT_DIR" ]; then
        cp -r $PROJECT_DIR $CURRENT_BACKUP/files
        log_info "فایل‌ها پشتیبان‌گیری شدند"
    fi
    
    # Backup database
    mysqldump -u root -p $DB_NAME > $CURRENT_BACKUP/database.sql
    log_info "دیتابیس پشتیبان‌گیری شد"
}

# Update code
update_code() {
    log_info "به‌روزرسانی کد..."
    
    cd $PROJECT_DIR
    
    # Pull latest changes
    git pull origin main
    
    # Install/update dependencies
    if [ -f "composer.json" ]; then
        composer install --no-dev --optimize-autoloader
        log_info "وابستگی‌های PHP نصب شدند"
    fi
    
    # Build frontend if exists
    if [ -f "package.json" ]; then
        npm install
        npm run build
        log_info "فرانت‌اند ساخته شد"
    fi
}

# Update database
update_database() {
    log_info "به‌روزرسانی دیتابیس..."
    
    cd $PROJECT_DIR
    
    # Run migrations
    php cli/migrate.php
    
    log_info "مایگریشن‌ها اجرا شدند"
}

# Set permissions
set_permissions() {
    log_info "تنظیم مجوزها..."
    
    # Set ownership
    chown -R www-data:www-data $PROJECT_DIR
    
    # Set permissions
    find $PROJECT_DIR -type f -exec chmod 644 {} \;
    find $PROJECT_DIR -type d -exec chmod 755 {} \;
    
    # Make CLI scripts executable
    chmod +x $PROJECT_DIR/cli/*.php
    
    log_info "مجوزها تنظیم شدند"
}

# Restart services
restart_services() {
    log_info "راه‌اندازی مجدد سرویس‌ها..."
    
    # Restart Apache
    systemctl restart apache2
    
    # Restart MySQL if needed
    # systemctl restart mysql
    
    log_info "سرویس‌ها راه‌اندازی شدند"
}

# Health check
health_check() {
    log_info "بررسی سلامت سیستم..."
    
    # Check if website is accessible
    if curl -f -s http://localhost/api/health > /dev/null; then
        log_info "✅ سایت در دسترس است"
    else
        log_error "❌ سایت در دسترس نیست"
        exit 1
    fi
    
    # Check database connection
    if php $PROJECT_DIR/cli/health-check.php; then
        log_info "✅ اتصال دیتابیس سالم است"
    else
        log_error "❌ مشکل در اتصال دیتابیس"
        exit 1
    fi
}

# Main deployment process
main() {
    log_info "شروع استقرار Study Tracker..."
    
    # Check if running as root
    if [ "$EUID" -ne 0 ]; then
        log_error "لطفاً با دسترسی root اجرا کنید"
        exit 1
    fi
    
    # Create backup
    create_backup
    
    # Update code
    update_code
    
    # Update database
    update_database
    
    # Set permissions
    set_permissions
    
    # Restart services
    restart_services
    
    # Health check
    health_check
    
    log_info "🎉 استقرار با موفقیت تکمیل شد!"
}

# Run main function
main "$@"

