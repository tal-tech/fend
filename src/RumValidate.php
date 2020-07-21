<?php

namespace Fend;

use Exception;

/**
 * 参数校验
 * 
 * @Date: 2019-12-11 10:24:45
 */
class RumValidate
{

    /**
     * 终止校验过程，一般用于可选参数且其值为空时
     */
    const BREAK = 1;
    /**
     * 校验失败，使用规则内返回的错误信息，如为空则使用默认信息
     */
    const FAILURE = 2;
    /**
     * 校验通过
     */
    const SUCCESS = 4;


    /**
     * 校验
     * 失败会直接抛出异常
     * @param array $data 参数
     * @param array $rules 规则 
     * @return: array
     * @Date: 2019-12-04 16:28:23
     */
    public static function do($data, $rules, $exceptionclass = Exception::class)
    {
        $res = self::doe($data, $rules);
        if (!empty($res['error'])) {
            throw new $exceptionclass($res['error'], $res['stat']);
        }
        return $res['data'];
    }

    /**
     * 校验
     * @param array $data 参数
     * @param array $rules 规则 
     * @return: array
     * @Date: 2019-12-04 16:28:23
     */
    public static function doe($data, $rules)
    {
        // 没有配置规则，通过
        if (empty($rules)) {
            return ['error' => '', 'stat' => 1, 'data' => []];
        }
        $res = [];
        foreach ($rules as $key => $rule) {
            if (empty($rule[0])) {
                continue;
            }
            $defaultErrMsg = empty($rule[1]) ? "参数{$key}非法" : $rule[1];
            $errCode = empty($rule[2]) ? 0 : $rule[2];
            $funcs = is_array($rule[0]) ? $rule[0] : [$rule[0]];
            foreach ($funcs as $fn) {
                $r = $fn($data[$key] ?? null, $key, $data);
                if ($r['stat'] == self::BREAK) {
                    break;
                }
                if ($r['stat'] == self::FAILURE) {
                    return ['error' => empty($r['msg']) ? $defaultErrMsg : $r['msg'], 'stat' => $errCode, 'data' => []]; // 校验不通过，返回
                }
            }
            // 记录需要校验的字段值
            if (isset($data[$key])) {
                $res[$key] = $data[$key];
            }
        }
        return ['error' => '', 'stat' => 1, 'data' => $res];
    }

    /**
     * 表示字段值是可选的
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:58:14
     */
    public static function optional($msg = '')
    {
        return function ($v, $k, $o) {
            if (isset($v)) {
                return self::succ();
            }
            return self::break();
        };
    }

    /**
     * 允许字段为空字符串
     * 空字符串会终止接下来的校验
     * 如果不为空字符串，那么继续进行后续校验
     * @param string $msg 错误提示消息 
     * @return: 
     * @author: liumurong  <liumurong1@100tal.com>
     * @date: 2020-03-28 20:52:29
     */
    public static function emptystr($msg = '')
    {
        return function ($v, $k, $o) {
            if ($v === '') {
                return self::break();
            }
            return self::succ();
        };
    }

    /**
     * 表示字段值是必须的
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:48:14
     */
    public static function required($msg = '')
    {
        return function ($v, $k, $o) use ($msg) {
            if (isset($v)) {
                return self::succ();
            }
            return self::fail($msg);
        };
    }

    /**
     * 表示字段值类型必须为布尔值
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:47:14
     */
    public static function boolean($msg = '')
    {
        return function ($v, $k, $o) use ($msg) {
            if (is_null(filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
                return self::fail($msg);
            }
            return self::succ();
        };
    }

    /**
     * 表示字段值类型必须为浮点型
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:48:14
     */
    public static function float($msg = '')
    {
        return function ($v, $k, $o) use ($msg) {
            if (filter_var($v, FILTER_VALIDATE_FLOAT) === false) {
                return self::fail($msg);
            }
            return self::succ();
        };
    }

    /**
     * 表示字段值类型必须为整形
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:49:14
     */
    public static function int($msg = '')
    {
        return function ($v, $k, $o) use ($msg) {
            if (filter_var($v, FILTER_VALIDATE_INT) === false) {
                return self::fail($msg);
            }
            return self::succ();
        };
    }

    /**
     * 表示字段值必须是一个有效的邮箱
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:49:14
     */
    public static function email($msg = '')
    {
        return function ($v, $k, $o) use ($msg) {
            if (filter_var($v, FILTER_VALIDATE_EMAIL)) {
                return self::succ();
            }
            return self::fail($msg);
        };
    }

    /**
     * 表示字段值必须是一个有效的11位电话号码
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:50:14
     */
    public static function phone($msg = '')
    {
        $reg = '/^1[3-9]\d{9}$/';
        return self::regex($reg, $msg);
    }

    /**
     * 表示字段值必须是一个有效的IP地址
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:51:14
     */
    public static function ip($msg = '')
    {
        return function ($v, $k, $o) use ($msg) {
            if (filter_var($v, FILTER_VALIDATE_IP)) {
                return self::succ();
            }
            return self::fail($msg);
        };
    }

    /**
     * 表示字段值必须是一个有效的URL地址
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:51:14
     */
    public static function url($msg = '')
    {
        return function ($v, $k, $o) use ($msg) {
            if (filter_var($v, FILTER_VALIDATE_URL)) {
                return self::succ();
            }
            return self::fail($msg);
        };
    }

    /**
     * 表示字段值是一个日期类型
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:52:14
     */
    public static function datetime($msg = '')
    {
        // $reg = '/^\d{4}-\d{1,2}-\d{1,2}[\s]([01]\d|2[0-3])(:[0-5]\d){1,2}$/';  // 日期
        $reg = '/^\d{4}-\d{1,2}-\d{1,2}([\s]([01]\d|2[0-3])(:[0-5]\d){1,2})?$/';
        return self::regex($reg, $msg);
    }

    /**
     * 表示字段值只能包含数字
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:53:14
     */
    public static function num($msg = '')
    {
        $reg = '/^[0-9]+$/';
        return self::regex($reg, $msg);
    }

    /**
     * 表示字段值只能包含字母、数字
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:53:14
     */
    public static function wordnum($msg = '')
    {
        $reg = '/^[A-Za-z0-9]+$/';
        return self::regex($reg, $msg);
    }

    /**
     * 表示字段值只能包含汉字、字母、数字
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:54:14
     */
    public static function cnwordnum($msg = '')
    {
        $reg = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u';
        return self::regex($reg, $msg);
    }

    /**
     * 枚举配置 (支持多值)
     * @param array $enum 枚举配置
     * @param $msg 提示信息
     * @param $useval 是否使用值进行校验(默认使用数组key)
     * @param $valkey 值Key(普通数组不用设置此值)
     * @return: array
     * @Date: 2019-12-16 11:07:29
     */
    public static function enums($enum, $msg = '', $useval = false, $valkey = '')
    {
        return function ($v, $k, $o) use ($enum, $msg, $useval, $valkey) {
            $vals = explode(',', $v);
            $evals = [];
            if (!$useval) {
                $evals = array_keys($enum);
            } else {
                $evals = array_map(function ($vv) use ($valkey) {
                    return empty($vv[$valkey]) ? $vv : $vv[$valkey];
                }, $enum);
            }
            if (count($vals) == count(array_intersect($vals, $evals))) {
                return self::succ();
            }
            return self::fail($msg);
        };
    }


    /**
     * 枚举配置
     * @param array $enum 枚举配置
     * @param $msg 提示信息
     * @param $useval 是否使用值进行校验(默认使用数组key)
     * @param $valkey 值Key(普通数组不用设置此值)
     * @return: array
     * @Date: 2019-12-16 14:03:35
     */
    public static function enum($enum, $msg = '', $useval = false, $valkey = '')
    {
        return function ($v, $k, $o) use ($enum, $msg, $useval, $valkey) {
            foreach ($enum as $ek => $ev) {
                $vv = $useval ? isset($ev[$valkey]) ? $ev[$valkey] : $ev : $ek;
                if (strval($v) == strval($vv)) {
                    return self::succ();
                }
            }
            return self::fail($msg);
        };
    }

    /**
     * 正则匹配
     * @param string $msg 错误提示消息 
     * @return: function
     * @Date: 2019-12-10 20:50:14
     */
    public static function regex($reg, $msg = '')
    {
        return function ($v, $k, $o) use ($reg, $msg) {
            if (preg_match($reg, $v)) {
                return self::succ();
            }
            return self::fail($msg);
        };
    }



    /**
     * 适用于int类型的区间值校验
     * @param $int $min 最小值
     * @param $int $max 最大值
     * @return: function
     * @Date: 2019-12-04 16:32:08
     */
    public static function between($min, $max, $msg = '')
    {
        return function ($v, $k, $o) use ($min, $max, $msg) {
            if ($v >= $min && $v <= $max) {
                return self::succ();
            }
            return self::fail($msg);
        };
    }
    /**
     * 适用于string类型的长度校验
     * @param $int $min 最小值
     * @param $int $max 最大值
     * @return: function
     * @Date: 2019-12-04 16:32:08
     */
    public static function length($min, $max, $msg = '')
    {
        return function ($v, $k, $o) use ($min, $max, $msg) {
            $len = mb_strlen($v);
            if ($len >= $min && $len <= $max) {
                return self::succ();
            }
            return self::fail($msg);
        };
    }
    /**
     * 适用于float，double类型的最多小数点个数校验
     * @param $int $max 小数点后最多包含几位
     * @return: function
     * @Date: 2019-12-04 16:32:08
     */
    public static function maxdeci($max, $msg = '')
    {
        $reg = '/^\d+(\.\d{0,' . $max . '})?$/';
        return self::regex($reg, $msg);
    }

    /**
     * 校验通过
     */
    public static function succ()
    {
        return ['stat' => self::SUCCESS, 'msg' => ''];
    }
    /**
     * 失败
     */
    public static function fail($errmsg = '')
    {
        return ['stat' => self::FAILURE, 'msg' => $errmsg];
    }
    /**
     * 终止流程
     */
    public static function break()
    {
        return ['stat' => self::BREAK, 'msg' => ''];
    }
}
