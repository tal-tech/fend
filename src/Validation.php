<?php

namespace Fend;

use Fend\Exception\FendException;

/**
 * Class Validation
 *
 * validation 输入数据验证类
 * 参数key如带.符号则会按数组某节点处理
 * 为了效率后续验证命令没有对类型进行检测，range检测之前请设置格式类型检测
 *
 * 预处理 ------------------
 *  require     设置为必填
 *  default     未填写设置默认值
 *
 * Type -------------------
 *  bool    是否是bool类型
 *  number  是否是数值类型
 *  string  是否是字符串类型
 *  array   是否是数组
 *
 * Format -----------------
 *  json        合法json
 *  email       合法邮件格式
 *  alpha       字符为全英文字符组成
 *  alpha_num   字符为英文字符、数字组成
 *  alpha_dash  英文字符数字-_符号组成
 *  url         内容为合法网址
 *  timestamp   内容为合法时间戳，目前只是检测是否为数值、正数，支持毫秒timestamp
 *  date        内容可以被str2time转换
 *  ip          内容为合法ip，支持v4 v6
 *  ipv4        内容为合法ipv4
 *  ipv6        内容为合法ipv6
 *  start_with  内容以指定字符串开始
 *  end_with    内容以指定字符串结束
 *  in_str      内含指定字符串、目前只支持一个参数
 *
 * Range -----------------
 *  min:m           必须大与指定值
 *  max:m           不能大于指定值
 *  range:m,n       数值取值范围（含）
 *  length:m,n      字符串字节长度
 *  count:m         array数组最大子项个数
 *  in:x,y,z..      内容必须在规定枚举内，强类型匹配
 *  not_in:x,y..    内容不在规定列表内，强类型匹配，非string类型必须使用数组指定xyz
 *  regex:/[0-9]+/  执行自定义正则表达式验证
 *
 * Validate ---------------
 *  callback =>function($param){} 自定义回调处理异常
 *  require_if:anotherField,value1,value2...      如果指定的其它字段（ anotherfield ）等于任何一个 value 时，被验证的字段必须存在且不为空。
 *  require_with:field1,field2...       只要在指定的其他字段中有任意一个字段存在时，被验证的字段就必须存在并且不能为空。
 *  require_with_all:field1,field2...       只有当所有的其他指定字段全部存在时，被验证的字段才必须存在并且不能为空。
 *  require_without:field1,field2..        只要在其他指定的字段中有任意一个字段不存在，被验证的字段就必须存在且不为空。
 *  require_without_all:field1,field2...    只有当所有的其他指定的字段都不存在时，被验证的字段才必须存在且不为空。
 *  same:field1,field2...                    给定字段必须与验证的字段匹配。
 *
 * 设置规则有两种可选方式：
 *
 * 1.数组方式：、正则、回调必须使用此方式
 * $rules = ['field1' => ["same" => ["a", "b"]] ];
 *
 * 2.字符串方式：
 * $rules = ['field1' => 'require|url|length:10,255'];
 *
 * @package Fend
 */
class Validation
{

    protected static $message = [
        "param" => ":key :cmd param is wrong",
        "require" => ":key is required",
        "bool" => ":key must be bool true false 1 0",
        "number" => ":key must be number",
        "string" => ":key must be string",
        "array" => ":key must be array",
        "json" => ":key must be json",
        "email" => ":key wrong format email",
        "alpha" => ":key wrong format of alpha",
        "alpha_num" => ":key wrong format of alpha num",
        "alpha_dash" => ":key wrong format dash",
        "url" => ":key wrong url format",
        "timestamp" => ":key wrong timestamp format",
        "date" => ":key wrong date format",
        "ip" => ":key is not ip",
        "ipv4" => ":key is not ipv4",
        "ipv6" => ":key is not ipv6",
        "start_with" => ":key is wrong start format",
        "end_with" => ":key is wrong end format",
        "in_str" => ":key is not in str range",
        "min" => ":key is less than min",
        "max" => ":key is big than max",
        "range" => ":key is wrong range",
        "length" => ":key is wrong length of range",
        "count" => ":key is wrong count of array",
        "in" => ":key is not in the option",
        "not_in" => ":key is not allow use this val",
        "regex" => ":key is not validate",
        "callback" => ":key is not validate callback",
        "require_if" => ":key is require when the special key :anotherKey setup",
        "require_with" => ":key is require when the special key :anotherKey setup",
        "required_with_all" => ":key is require when the special key all setup",
        "required_without" => ":key is require when the special key not full fill",
        "required_without_all" => ":key is require when the special key not exist",
        "same" => ":key is must same the all special key value",
        "rule" => ":key rule :cmd was not define",
    ];

    protected static $paramRule = [
        "start_with" => ["cmd", 1, "string"],
        "end_with" => ["cmd", 1, "string"],
        "in_str" => ["cmd", 1, "string"],
        "min" => ["cmd", 1, "number"],
        "max" => ["cmd", 1, "number"],
        "range" => ["cmd", 2, "number"],
        "length" => ["cmd", 2, "number"],
        "count" => ["cmd", 2, "number"],
        "in" => ["cmd", -1, "mixed"],
        "not_in" => ["cmd", -1, "mixed"],
        "regex" => ["cmd", 1, "string"],
        "callback" => ["cmd", 1, "callback"],
        "require_if" => ["cmd", -2, "mixed"],
    ];

    /**
     * 递归实现多维数组 xpath提取数据
     * @param string|array $key 数组部分用*号表示的搜索路径如："user.*.id"返回所有id部分 "user.*"返回所有user内数组
     * @param mixed $value 要检索的数组
     * @param string $xpath 切勿填写
     * @return array 返回至少二维数组
     */
    public static function eachArray($key, $value, $xpath = "")
    {
        $result = [];

        if (is_string($key)) {
            $key = explode(".", $key);
        }

        //next point

        while (($nowKey = array_shift($key)) !== "") {

            if (empty($key)) {

                if ($nowKey === "*") {
                    $result[substr($xpath . ".*", 1)] = $value;
                    return $result;
                } elseif (isset($value[$nowKey])) {
                    $result[substr($xpath . "." . $nowKey, 1)] = $value[$nowKey];

                    return $result;
                }

                return $result;

            } elseif ($nowKey === "*") {

                foreach ($value as $dataIndex => $valueItem) {
                    $ret = self::eachArray($key, $valueItem, $xpath . "." . $dataIndex);
                    $result = array_merge($result, $ret);
                }
                return $result;
            } elseif ($nowKey !== "*") {

                if (isset($value[$nowKey])) {
                    $xpath = $xpath . "." . $nowKey;

                    return self::eachArray($key, $value[$nowKey], $xpath);
                }
                return $result;
            }

        }

        return $result;
    }

    /**
     * 检测一维数组个数及类型
     * @param array $param 要检测的cmd参数数组
     * @param int $count 指定参数数组个数 0 0个参数，负数代表至少一个指定的绝对值个数，指定数值必填多少个参数
     * @param string $type 数据类型 string,mixed,number
     * @return bool 符合返回true、否则返回false
     */
    public static function checkCmdParam($param, $count, $type = "string")
    {
        //ultimate count but must have
        if (is_array($param) && $count < 0 && count($param) < abs($count)) {
            return false;
        }

        //special count of param
        if (is_array($param) && $count >= 0 && count($param) != $count) {
            return false;
        }

        if ($type === "string") {
            foreach ($param as $paramItem) {
                if ($paramItem === "" || !is_string($paramItem)) {
                    return false;
                }
            }
        } elseif ($type === "number") {
            foreach ($param as $paramItem) {
                if ($paramItem === "" || !is_numeric($paramItem)) {
                    return false;
                }
            }
        } elseif ($type === "callback") {
            foreach ($param as $paramItem) {
                if ($paramItem === "" || !is_callable($paramItem)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 根据规则验证
     * todo:抛异常可选
     * @param array $input
     * @param array $rules
     * @param array $message
     * @return array
     */

    public static function make($input, $rules, $message = [])
    {
        $result = [
            "error" => [],
            "passed" => true,
        ];

        //value array
        $valueArray = [];

        //pre process the value
        foreach ($rules as $fieldName => $rule) {
            //process the user.*.info for array
            $tempValue = self::eachArray($fieldName, $input);
            if (empty($tempValue)) {
                $tempValue = [$fieldName => []];
            }
            $valueArray[$fieldName] = array_merge($valueArray[$fieldName] ?? [], $tempValue);
        }

        //param key and rule loop
        foreach ($rules as $fieldName => $rule) {

            //ignore the empty rule
            if (empty($rule)) {
                continue;
            }

            //multiple of the input for array and other
            foreach ($valueArray[$fieldName] as $inputVal) {
                $inputVal = [$fieldName => $inputVal];

                //string rule to multiple cmd
                $rule = is_string($rule) ? explode("|", $rule) : $rule;

                //cmd process
                foreach ($rule as $cmdIndex => $cmdItem) {
                    //string rule
                    //first value is cmd
                    if (is_string($cmdItem)) {
                        $info = preg_split("/[:,]+/", $cmdItem, -1, PREG_SPLIT_NO_EMPTY);
                        $cmd = array_shift($info);
                        $cmdParam = $info;
                    } elseif (is_array($cmdItem) || is_callable($cmdItem)) {
                        //when the rule is array the index is cmd
                        $cmd = $cmdIndex;
                        $cmdParam = $cmdItem;
                    } else {
                        throw new FendException("$fieldName rule is not validate type!", -3999);
                    }

                    //make case lower
                    $cmd = strtolower(trim($cmd));

                    //cmd param check
                    if (isset(self::$paramRule[$cmd])) {
                        $cmdRule = self::$paramRule[$cmd];
                        if ($cmdRule[0] === "cmd") {
                            $ret = self::checkCmdParam($cmdParam, $cmdRule[1], $cmdRule[2]);
                            if (!$ret) {
                                $result["passed"] = false;
                                $result["error"][$fieldName] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName]]);
                                continue;
                            }
                        }
                    }

                    //first level filter
                    if ($cmd === "default") {
                        //not set or real empty set default
                        if (self::is_empty($inputVal, [$fieldName])) {
                            $inputVal[$fieldName] = $cmdParam[0];
                        }
                        continue;
                    } elseif ($cmd === "require") {

                        //not set or empty will error
                        if (self::is_empty($inputVal, [$fieldName])) {
                            $result["passed"] = false;
                            $result["error"][$fieldName][] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName]]);
                        }
                        continue;
                    } elseif ($cmd === "require_if") {
                        $anotherKey = array_shift($cmdParam);

                        //have the suitable value
                        if (isset($input[$anotherKey]) && in_array($input[$anotherKey], $cmdParam, true)) {
                            if (self::is_empty($inputVal, [$fieldName])) {
                                $result["passed"] = false;
                                $result["error"][$fieldName][] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName], ":anotherKey" => $anotherKey]);
                                continue;
                            }
                        }

                        //passed will not continue
                        continue;
                    } elseif ($cmd === "require_with") {
                        //if have will check require
                        foreach ($cmdParam as $anotherKey) {
                            if (!self::is_empty($input, [$anotherKey]) && self::is_empty($inputVal, [$fieldName])) {
                                $result["passed"] = false;
                                $result["error"][$fieldName][] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName], ":anotherKey" => $anotherKey]);;
                                break;
                            }
                        }

                        //passed will not continue
                        continue;
                    } elseif ($cmd === "required_with_all") {
                        $enable = true;
                        foreach ($cmdParam as $anotherKey) {
                            if (self::is_empty($input, [$anotherKey])) {
                                $enable = false;
                            }
                        }

                        if ($enable) {
                            if (self::is_empty($inputVal, [$fieldName])) {
                                $result["passed"] = false;
                                $result["error"][$fieldName][] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName]]);;
                            }
                        }
                        continue;
                    } elseif ($cmd === "required_without") {
                        $enable = true;
                        foreach ($cmdParam as $anotherKey) {
                            if (self::is_empty($input, [$anotherKey])) {
                                $enable = false;
                            }
                        }

                        if (!$enable) {
                            if (self::is_empty($inputVal, [$fieldName])) {
                                $result["passed"] = false;
                                $result["error"][$fieldName][] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName]]);;
                            }
                        }
                        continue;
                    } elseif ($cmd === "required_without_all") {
                        $enable = true;
                        foreach ($cmdParam as $anotherKey) {
                            if (!self::is_empty($input, [$anotherKey])) {
                                $enable = false;
                            }
                        }

                        if ($enable) {
                            if (self::is_empty($inputVal, [$fieldName])) {
                                $result["passed"] = false;
                                $result["error"][$fieldName][] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName]]);;
                            }
                        }
                        continue;
                    } elseif ($cmd === "same") {
                        if (self::is_empty($inputVal, [$fieldName])) {
                            $result["passed"] = false;
                            $result["error"][$fieldName][] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName]]);;
                            continue;
                        }

                        $sameCmpValue = $inputVal[$fieldName];
                        foreach ($cmdParam as $anotherKey) {
                            if (self::is_empty($input, [$anotherKey]) || $input[$anotherKey] !== $sameCmpValue) {
                                $result["passed"] = false;
                                $result["error"][$fieldName][] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName]]);
                            }
                        }

                        continue;
                    }

                    //ignore the empty param
                    if (self::is_empty($inputVal, [$fieldName])) {
                        continue;
                    }

                    //check cmd process exist?
                    if (!is_callable("self::is_" . $cmd)) {
                        $result["passed"] = false;
                        $result["error"][$fieldName][] = self::getMessage($fieldName, "rule", [":key" => $fieldName, ":cmd" => $cmd]);
                        continue;
                    }


                    //second level cmd filter
                    //call custom func
                    try {

                        $ret = call_user_func_array("self::is_" . $cmd,
                            [$inputVal[$fieldName], $cmdParam]);
                        if (!$ret) {
                            $result["passed"] = false;
                            $result["error"][$fieldName][] = self::getMessage($fieldName, $cmd, [":key" => $fieldName, ":cmd" => $cmd, ":val" => $inputVal[$fieldName]]);;
                        }
                    } catch (\Throwable $e) {
                        $result["passed"] = false;
                        $result["error"][$fieldName][] = $e->getMessage() . " code:" . $e->getCode();
                        continue;
                    }

                } //cmd process
            }// more value


        }// rule loop
        return $result;
    }

    public static function getMessage($key, $type, $param)
    {
        if (isset(self::$message[$key . "." . $type])) {
            return strtr(self::$message[$key . "." . $type], $param);
        }

        if (isset(self::$message[$type])) {
            return strtr(self::$message[$type], $param);
        }

        throw new FendException("Message not found!", -3998);
    }

    /**
     * 设置提示语
     * @param $messageArray
     */
    public static function setMessage($messageArray)
    {
        self::$message = $messageArray;
    }

    public static function is_empty($input, $param)
    {
        $key = $param[0];

        if (!isset($input[$key]) || is_array($input[$key]) && empty($input[$key]) || $input[$key] === "" || is_null($input[$key])) {
            return true;
        }
        return false;
    }


    public static function is_bool($input, $param)
    {
        if (!in_array($input, ["0", "1", "true", "false", true, false, 0, 1], true)) {
            return false;
        }
        return true;
    }

    public static function is_number($input, $param)
    {

        if (!is_numeric($input)) {
            return false;
        }
        return true;
    }

    public static function is_string($input, $param)
    {

        if (!is_string($input)) {
            return false;
        }
        return true;
    }

    public static function is_array($input, $param)
    {
        if (!is_array($input)) {
            return false;
        }
        return true;
    }

    public static function is_json($input, $param)
    {
        @json_decode($input);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public static function is_email($input, $param)
    {
        if ($input === false || $input !== filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    public static function is_alpha($input, $param)
    {
        if (!is_string($input) || !preg_match("/^[a-zA-Z]+$/", $input)) {
            return false;
        }
        return true;
    }

    public static function is_alpha_num($input, $param)
    {

        if (!is_string($input) || !preg_match("/^[0-9a-zA-Z]+$/", $input)) {
            return false;
        }
        return true;
    }

    public static function is_alpha_dash($input, $param)
    {
        if (!is_string($input) || !preg_match("/^[a-zA-Z0-9_\-]+$/", $input)) {
            return false;
        }
        return true;
    }

    public static function is_url($input, $param)
    {
        if (filter_var($input, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        return true;
    }

    public static function is_timestamp($input, $param)
    {
        if (!is_numeric($input) || $input <= 0) {
            return false;
        }
        return true;
    }

    public static function is_date($input, $param)
    {
        if (strtotime($input) === false) {
            return false;
        }
        return true;
    }

    public static function is_ip($input, $param)
    {
        if (filter_var($input, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        return true;
    }

    public static function is_ipv4($input, $param)
    {
        if (filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }
        return true;
    }

    public static function is_ipv6($input, $param)
    {
        if (filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return false;
        }
        return true;
    }

    public static function is_start_with($input, $param)
    {
        if (substr($input, 0, strlen($param[0])) !== $param[0]) {
            return false;
        }
        return true;
    }

    public static function is_end_with($input, $param)
    {
        if (substr($input, -strlen($param[0])) !== $param[0]) {
            return false;
        }
        return true;
    }

    public static function is_in_str($input, $param)
    {
        if (stripos($input, $param[0]) === false) {
            return false;
        }
        return true;
    }

    public static function is_min($input, $param)
    {
        if ($input < $param[0]) {
            return false;
        }

        return true;
    }

    public static function is_max($input, $param)
    {
        if ($input > $param[0]) {
            return false;
        }

        return true;
    }

    public static function is_range($input, $param)
    {
        if ($input < $param[0] || $input > $param[1]) {
            return false;
        }

        return true;
    }

    public static function is_length($input, $param)
    {
        $length = strlen($input);
        if ($length < $param[0] || $length > $param[1]) {
            return false;
        }

        return true;
    }

    public static function is_count($input, $param)
    {
        $count = is_array($input) ? count($input) : 0;
        if ($count < $param[0] || $count > $param[1]) {
            return false;
        }

        return true;
    }

    public static function is_in($input, $param)
    {
        //compare string len
        if (!in_array($input, $param, true)) {
            return false;
        }

        return true;
    }

    public static function is_not_in($input, $param)
    {
        //compare string len
        if (in_array($input, $param, true)) {
            return false;
        }

        return true;
    }

    public static function is_regex($input, $param)
    {

        if (!preg_match($param[0], $input)) {
            return false;
        }

        return true;
    }

    public static function is_callback($input, $param)
    {
        //call the custom func
        return $param($input);
    }


}
