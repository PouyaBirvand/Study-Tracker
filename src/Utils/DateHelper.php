<?php
/**
 * Date Helper
 * کلاس کمکی تاریخ
 */
class DateHelper 
{
    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    public static function toJalali(string $gregorianDate): string 
    {
        $timestamp = strtotime($gregorianDate);
        return jdate('Y/m/d', $timestamp);
    }
    
    /**
     * تبدیل تاریخ شمسی به میلادی
     */
    public static function toGregorian(string $jalaliDate): string 
    {
        // پیاده‌سازی تبدیل تاریخ شمسی به میلادی
        // این بخش نیاز به کتابخانه تبدیل تاریخ دارد
        return $jalaliDate; // موقتی
    }
    
    /**
     * فرمت زمان نسبی (مثل "2 ساعت پیش")
     */
    public static function timeAgo(string $datetime): string 
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'همین الان';
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return $minutes . ' دقیقه پیش';
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return $hours . ' ساعت پیش';
        } elseif ($time < 2592000) {
            $days = floor($time / 86400);
            return $days . ' روز پیش';
        } elseif ($time < 31536000) {
            $months = floor($time / 2592000);
            return $months . ' ماه پیش';
        } else {
            $years = floor($time / 31536000);
            return $years . ' سال پیش';
        }
    }
    
    /**
     * دریافت شروع و پایان هفته
     */
    public static function getWeekRange(string $date = null): array 
    {
        $timestamp = $date ? strtotime($date) : time();
        $dayOfWeek = date('w', $timestamp);
        
        // شنبه = 6, یکشنبه = 0, دوشنبه = 1, ...
        // در ایران هفته از شنبه شروع می‌شود
        $startOfWeek = $timestamp - (($dayOfWeek + 1) % 7) * 86400;
        $endOfWeek = $startOfWeek + 6 * 86400;
        
        return [
            'start' => date('Y-m-d', $startOfWeek),
            'end' => date('Y-m-d', $endOfWeek)
        ];
    }
    
    /**
     * دریافت شروع و پایان ماه
     */
    public static function getMonthRange(string $date = null): array 
    {
        $timestamp = $date ? strtotime($date) : time();
        
        return [
            'start' => date('Y-m-01', $timestamp),
            'end' => date('Y-m-t', $timestamp)
        ];
    }
    
    /**
     * محاسبه تفاوت روزها
     */
    public static function daysDifference(string $date1, string $date2): int 
    {
        $timestamp1 = strtotime($date1);
        $timestamp2 = strtotime($date2);
        
        return abs(($timestamp2 - $timestamp1) / 86400);
    }
    
    /**
     * فرمت مدت زمان (دقیقه به ساعت و دقیقه)
     */
    public static function formatDuration(int $minutes): string 
    {
        if ($minutes < 60) {
            return $minutes . ' دقیقه';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes === 0) {
            return $hours . ' ساعت';
        }
        
        return $hours . ' ساعت و ' . $remainingMinutes . ' دقیقه';
    }
}
