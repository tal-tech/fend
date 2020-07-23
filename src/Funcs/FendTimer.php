<?php
namespace Fend\Funcs;

/**
 * 时间的处理
**/
class FendTimer
{
    /**
     * 计算机某年某月的天数
     * @param $year 当前年份
     * @param $month 当前月份
     * @return string
     */
    public static function getYearMonthDay($year, $month)
    {
        if (in_array($month, array('1', '3', '5', '7', '8', '01', '03', '05', '07', '08', '10', '12'))) {
            return 31;
        } elseif ($month == 2) {
            //判断是否是闰年
            if ($year % 400 === 0 || ($year % 4 === 0 && $year % 100 !== 0)) {
                return 29;
            } else {
                return 28;
            }
        } else {
            return 30;
        }
    }

    /**
     * 格式化时间
     * @param        $t 时间戳
     * @param string $f 格式化格式
     *
     * @return string
     */
    public static function formatTime($t, $f = 'Y-m-d')
    {
        $m = empty($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] - $t : $t;
        if ($m <= 60) {
            return '刚刚';
        } elseif ($m > 60 and $m <= 3600) {
            $m = ceil($m / 60);
            return $m . '分钟前';
        } elseif ($m > 3600 and $m <= 86400) {
            $m = ceil($m / 3600);
            return $m . '小时前';
        } elseif ($m > 86400 and $m <= 2592000) {
            $m = ceil($m / 86400);
            return $m . '天前';
        } else /* if ($m > 30 * 3600 * 24) */ {
            $m = ceil($m / (30 * 3600 * 24));
            return $m . '月前';
        }
    }

    /**
     * 日期格式--1 2015/06/07 15:30:23
     * @param $timestamp
     *
     * @return bool|string
     */
    public static function getDateFromStamp($timestamp)
    {
        if (!$timestamp) {
            return false;
        }
        $str = date("Y/m/d H:i:s", $timestamp);
        return $str;
    }
    /**
     * 日期格式-2
     * @param $timestamp
     *
     * @return bool|string
     */
    public static function getShortDateFromStamp($timestamp)
    {
        if (!$timestamp) {
            return false;
        }
        $str = date("m/d H:i", $timestamp);
        return $str;
    }

    /**
     * 时间格式化
     *
     * @param
     * @param
     * @return int
     * */
    public static function secToTime($lastTime)
    {
        $period = time() - ((is_numeric($lastTime)) ? $lastTime : strtotime($lastTime));
        if ($period < 0) {
            return "刚刚发布";
        } elseif ($period < 60) {
            return ($period <= 0 ? 1 : $period) . "秒前";
        } elseif ($period < 3600) {
            return round($period / 60, 0) . "分钟前";
        } elseif ($period < 86400) {
            return round($period / 3600, 0) . "小时前";
        } elseif ($period < 86400 * 30) {
            return date('n月d日 H:i', $lastTime);
        } else {
            return date('n月d日 H:i', $lastTime);
        }
    }

    /**
     * 计算时间返回当前时间秒数
     * @return float
     */
    public static function getTime()
    {
        //list($usec, $sec) = explode(" ", microtime());
        return microtime(true);
    }

    /**
     * 获取时间的秒数
     * @param string $time 时:分:秒
     * @return int
     */
    public static function getSecondTime($time = '01:01:01')
    {
        if (empty($time)) {
            return 0;
        }
        $times = explode(':', $time);
        $timer = 0;
        if (count($times) === 3) {
            $timer = intval($times[0]) * 3600 + intval($times[1]) * 60 + $times[2];
        }
        return $timer;
    }

    /**
     * 根据时间搓获取格式化后的时间
     * @param int $time
     * @return string
     */
    public static function getFormatSecondTime($time = 600)
    {
        if (empty($time)) {
            return false;
        }
        $h  = floor($time / 3600);
        $hm = $time % 3600;
        $i  = floor($hm / 60);
        $m  = $hm % 60;
        $h  = ($h < 10) ? '0' . $h : $h;
        $i  = ($i < 10) ? '0' . $i : $i;
        $m  = ($m < 10) ? '0' . $m : $m;
        return "{$h}:{$i}:{$m}";
    }

    /**
     * 获取时间戳
     * @param string $date
     * @return int
     */
    public function getUnixTimeStamp($date = "00-00-00 00:00:00")
    {
        if (!$date) {
            return 0;
        }
        list($d, $t) = explode(" ", $date);
        list($year, $month, $day) = explode("-", $d);
        list($hour, $minute, $second) = explode(":", $t);
        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    /**
     * 日期格式--1 2015/06/07 15:30:23
     * @param int $type 0-今天开始，1-今天结束
     *
     * @return bool|string
     */
    public static function getTodayTime($type = 0)
    {
        $arr = getdate();
        return !$type ? mktime(00, 00, 00, $arr['mon'], $arr['mday'], $arr['year']) : mktime(23, 59, 59, $arr['mon'], $arr['mday'], $arr['year']);
    }
}
