<?php
namespace Fend;

class Filter {

    /**
     * 使用purifer Html 默认配置过滤html
     * 会过滤掉多余css、脚本、只保留结构主体、文字、链接
     * 需要引入组件才可以工作 composer require ezyang/htmlpurifier 4.12
     * @param string $html
     * @return string
     */
    public static function  purifierHtml($html) {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', "utf-8");
        $config->set('Cache.SerializerPath', SYS_CACHE);
        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($html);
    }

    /**
     * 过滤字符串,特殊字符转义，规范配对tag
     * @param string $string 输入string
     * @param int $flag 附加选项flat
     * @return mixed 过滤结果
     */
    public static function filterString($string, $flag = null) {
        return filter_var($string, FILTER_SANITIZE_STRING, $flag);
    }

    /**
     * 过滤特殊字符串，不可见字符，html标志<>&
     * @param string $string 输入string
     * @param int $flag 附加选项flat
     * @return mixed 过滤结果
     */
    public static function filterSpecialChars($string, $flag = null) {
        return filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS, $flag);
    }

    /**
     * 使用Sanitize过滤网址内非法字符串
     * @param string $url
     * @return mixed
     */
    public static function filterUrl($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * 过滤email地址中非法字符
     * @param string $email
     * @return mixed
     */
    public static function filterEmail($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * 过滤成float
     * @param string $float
     * @return mixed
     */
    public static function filterFloat($float) {
        return (float)$float;
    }


    /**
     * 过滤成int
     * @param string $float
     * @return mixed
     */
    public static function filterInt($int) {
        return (int)$int;
    }
}