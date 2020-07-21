<?php

namespace Fend;

/**
 * 统一验证类，用于统一参数验证及文档生成
 * Class ValidateFilter
 * @package Fend
 */
class ValidateFilter
{

    //api访问网址或uri
    private $api;

    //接口功能介绍
    private $desc;

    //请求类型，可选post get del put rpcx等
    private $method;

    //接口调用演示数据，可多组
    private $demo = [];

    //参数规则
    private $rule = [];

    //错误信息
    private $message = [];

    /**
     * 验证类
     * Validate constructor.
     * @param $api
     * @param $desc
     * @param $method
     */
    public function __construct($api, $desc, $method)
    {
        $this->api = $api;
        $this->desc = $desc;
        $this->method = $method;
    }

    /**
     * 添加演示参数
     * @param array $param 参数数组，可以添加多个
     */
    public function addDemoParameter($param)
    {
        $this->demo[] = $param;
    }

    /**
     * 添加参数规则
     * @param string $key 参数key
     * @param string $type 参数类型
     * @param string $desc 参数用途介绍
     * @param bool $require 是否必填
     * @param string $default 默认参数
     * @param mixed $limit enum为可选项数组，数值时为取值范围，字符串类型时为限制长度
     * @throws \Exception
     */
    public function addRule($key, $type, $desc, $require = false, $default = "", $limit = [])
    {
        //key 必填
        if (empty($key)) {
            throw new \Exception("参数过滤规则必填", 3001);
        }

        //type 必填
        if (empty($type)) {
            throw new \Exception("参数类型必填", 3003);
        }

        //desc 必填
        if (empty($desc)) {
            throw new \Exception("请说明参数用途", 3005);
        }

        //callback 检测
        if ($type === "callback" && (empty($limit) || !is_callable($limit))) {
            throw new \Exception("callable limit参数必填闭包函数", 3007);
        }

        //记录rule规则
        $this->rule[$key] = [$type, $limit, $require, $default, $desc, 3002];
    }

    /**
     * 通过数组批量添加参数验证规则
     * $rules = [
     *   "参数key" => ["类型", "字段介绍", '是否必填', "不填时默认值", "长度限制，或callback函数", "字段错误码"],
     *    "yes" => ["string", "介绍用途", true, "默认值", [1, 100], 232333],
     * ];
     * @param array $rules
     * @throws \Exception 规则异常
     */
    public function addMultiRule($rules)
    {
        foreach ($rules as $key => $val) {
            $this->addRuleEx($key, $val[0], $val[1], $val[2] ?? false, $val[3] ?? "", $val[4] ?? [], $val[5] ?? 3002);
        }
    }

    /**
     * 添加错误提示信息
     * $message = [
     *      "user_id.require" => "user_id必填",
     *      "user_id.int" => "user_id 必须是数值",
     *      "user_id.limit" => ["user_id 取值范围必须是1～299", 3636], //数组类型，第二个参数为自定义exception错误码
     *      "user_name.regx" => "user_name 必须由数字英文字符串组成",
     *      "user_name.string" => "user_name 必须是字符串类型数据",
     * ]
     * @param array $messages
     */
    public function addMessage($messages)
    {
        $this->message = $messages;
    }

    /**
     * 根据定义抛出异常，如果有用户自定义信息使用用户自定义msg及code
     * @param string $key 用户报错字段
     * @param string $type 错误类型关键词，如类型int bool float email,limit
     * @param string $message 如果没找到使用指定message
     * @param int $code 如果没找到使用指定code
     * @throws \Exception
     */
    public function throwException($key, $type, $message, $code)
    {
        if (isset($this->message[$key . "." . $type])) {
            $result = $this->message[$key . "." . $type];
            if (is_array($result)) {
                throw new \Exception($result[0], $result[1]);
            }
            //如果没有code，使用默认code
            throw new \Exception($result, $code);
        }

        //使用默认异常信息
        throw new \Exception($message, $code);
    }

    /**
     * 添加参数规则，扩展版，支持参数自定义错误码
     * @param string $key 参数key
     * @param string $type 参数类型
     * @param string $desc 参数用途介绍
     * @param bool $require 是否必填
     * @param string $default 默认参数
     * @param mixed $limit enum为可选项数组，数值时为取值范围，字符串类型时为限制长度
     * @param int $code 错误码，如果此参数错误那么返回此错误码
     * @throws \Exception
     */
    public function addRuleEx($key, $type, $desc, $require = false, $default = "", $limit = [], $code = 3002)
    {
        //key 必填
        if (empty($key)) {
            throw new \Exception("参数过滤规则必填", 3001);
        }

        //type 必填
        if (empty($type)) {
            throw new \Exception("参数类型必填", 3003);
        }

        //desc 必填
        if (empty($desc)) {
            throw new \Exception("请说明参数用途", 3005);
        }

        //callback 检测
        if ($type === "callback" && (empty($limit) || !is_callable($limit))) {
            throw new \Exception("callable limit参数必填闭包函数", 3007);
        }

        //记录rule规则
        $this->rule[$key] = [$type, $limit, $require, $default, $desc, $code];
    }

    /**
     * 根据规则，验证参数
     * 返回过滤结果
     * 如果有非法参数会抛出异常
     * @param $param
     * @return array
     * @throws \Exception
     */
    public function checkParam($param)
    {
        $result = [];

        //interrupt the rest code
        if (isset($param["tal_sec"]) && $param["tal_sec"] === "show_param_json") {
            Di::factory()->getResponse()->break($this->showDoc());
        }

        //check parameters
        foreach ($this->rule as $key => $rule) {

            //empty
            if (!isset($param[$key]) || is_array($param[$key]) && empty($param[$key]) || $param[$key] === "") {

                //require always exception
                if ($rule[2]) {
                    $this->throwException($key, "require", $key . "参数必填", $rule[5] ?? 3304);
                }

                //default
                if ($rule[3] !== "") {
                    $param[$key] = $rule[3];
                }
            }

            if (isset($param[$key]) && (is_array($param[$key]) && !empty($param[$key]) || $param[$key] !== "")) {
                //default code
                $result[$key] = $this->filterParam($key, $param[$key], $rule[0], $rule[1], $rule[5] ?? 3002);
            }
        }

        return $result;
    }

    /**
     * 显示接口api叙述介绍
     * @param string $format
     * @return false|string
     */
    public function showDoc($format = "json")
    {
        if ($format === "json") {
            return json_encode([
                "api" => $this->api,
                "method" => $this->method,
                "desc" => $this->desc,
                "demo" => $this->demo,
                "param" => $this->rule,
                "message" => $this->message,
            ]);
        }

        return false;
    }


    /**
     * 私有函数，挨个过滤参数，异常直接报错
     * @param string $key 数据key
     * @param string $val 值
     * @param string $type 数据类型
     * @param array|callable $limit 限制范围或可选项
     * @param int $code 错误码
     * @return mixed
     * @throws \Exception
     */
    private function filterParam($key, $val, $type, $limit, $code = 3002)
    {

        //check type
        switch (strtolower($type)) {
            case "bool":
                return filter_var($val, FILTER_VALIDATE_BOOLEAN);
            case "int":
                if (is_array($val) || !is_numeric($val) && strlen($val) > 0) {
                    $this->throwException($key, "int", "参数" . $key . " 只接受数值", $code);
                }
                if (!empty($limit) && ($val < $limit[0] || $val > $limit[1])) {
                    $this->throwException($key, "limit", "参数" . $key . " 值限制在" . $limit[0] . "-" . $limit[1] . "之间", $code);
                }
                return $val;
            case "float":
                if (is_array($val) || !is_numeric($val) && strlen($val) > 0) {
                    $this->throwException($key, "float", "参数" . $key . " 只接受浮点数值", $code);
                }
                if (!empty($limit) && ($val < $limit[0] || $val > $limit[1])) {
                    $this->throwException($key, "limit", "参数" . $key . " 值限制在" . $limit[0] . "-" . $limit[1] . "之间", $code);
                }
                return $val;
            case "double":
                if (is_array($val) || !is_numeric($val) && strlen($val) > 0) {
                    $this->throwException($key, "double", "参数" . $key . " 只接受double数值", $code);
                }
                if (!empty($limit) && ($val < $limit[0] || $val > $limit[1])) {
                    $this->throwException($key, "limit", "参数" . $key . " 值限制在" . $limit[0] . "-" . $limit[1] . "之间", $code);
                }
                return $val;
            case "string":
                if (is_array($val) || !is_string($val)) {
                    $this->throwException($key, "string", "参数" . $key . " 只接受string类型", $code);
                }
                if (!empty($limit) && (strlen($val) < $limit[0] || strlen($val) > $limit[1])) {
                    $this->throwException($key, "limit", "参数" . $key . " 长度限制在" . $limit[0] . "-" . $limit[1] . "之间", $code);
                }
                return $val;
            case "mbstring":
                if (is_array($val) || !is_string($val)) {
                    $this->throwException($key, "mbstring", "参数" . $key . " 只接受string类型", $code);
                }
                if (!empty($limit) && (mb_strlen($val) < $limit[0] || mb_strlen($val) > $limit[1])) {
                    $this->throwException($key, "limit", "参数" . $key . " 长度限制在" . $limit[0] . "-" . $limit[1] . "之间", $code);
                }
                return $val;
            case "email":
                if (is_array($val) || !is_string($val)) {
                    $this->throwException($key, "string", "参数" . $key . " 只接受string类型", $code);
                }
                if (!empty($limit) && (strlen($val) < $limit[0] || strlen($val) > $limit[1])) {
                    $this->throwException($key, "limit", "参数" . $key . " 长度限制在" . $limit[0] . "-" . $limit[1] . "之间", $code);
                }
                if (!($val = filter_var($val, FILTER_VALIDATE_EMAIL))) {
                    $this->throwException($key, "email", "参数" . $key . " 只接受合法email格式数据", $code);
                }
                return $val;
            case "enum":
                if (!in_array("$val", $limit, true)) {
                    $this->throwException($key, "enum", "参数" . $key . " 选项不在有效可选范围内", $code);
                }
                return $val;
            case "array":
                if (!is_array($val)) {
                    $this->throwException($key, "array", "参数" . $key . " 必须是数组", $code);
                }
                return $val;
            case "callback":
                return $limit($key, $val);
            default:
                //regx
                if (strpos($type, "regx:") === 0) {
                    //limit len
                    if (is_array($val) || !empty($limit) && (strlen($val) < $limit[0] || strlen($val) > $limit[1])) {
                        $this->throwException($key, "limit", "参数" . $key . " 长度限制在" . $limit[0] . "-" . $limit[1] . "之间", $code);
                    }

                    $regx = substr($type, 5);
                    if (!preg_match($regx, $val)) {
                        $this->throwException($key, "regx", "参数" . $key . " 只接受符合正则" . $type . "数据", $code);
                    }
                    return $val;
                }

                throw new \Exception("参数" . $key . " 未知" . $type . "类型定义", 3009);
        }
    }


}