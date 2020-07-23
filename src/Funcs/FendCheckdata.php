<?php
namespace Fend\Funcs;

/**
 * 数据的处理
 *
 **/
class FendCheckdata
{
    /**
     * @abstract 判断是否为整型
     * $options array('min', 'max')
     */
    public static function isInt($data, $options = null)
    {
        $result = preg_match('/^-?\d+$/', $data);
        if ($result == false) {
            return false;
        }

        if (!empty($options)) {
            if (isset($options['min']) && is_int($options['min'])) {
                if ($options['min'] > $data) {
                    return false;
                }
            }
            if (isset($options['max']) && is_int($options['max'])) {
                if ($options['max'] < $data) {
                    return false;
                }
            }
        }
        return $data;
    }

    /**
     * @abstract 判断是否为浮点型
     * @params float $data //float
     * @params $options = array(min, max)
     */
    public static function isFloat($data, $options = null)
    {
        $option = array();
        $var = filter_var($data, FILTER_VALIDATE_FLOAT, $option);
        if ($var !== false) {
            if (!empty($options)) {
                if ((isset($options['min']) && is_float($options['min'])) && $var < $options['min']) {
                    return false;
                }

                if ((isset($options['max']) && is_float($options['max'])) && $var > $options['max']) {
                    return false;
                }
            }
        }
        return $var;
    }

    /**
     * @abstract 判断是否为正确的email格式
     * @params string $data //email
     * @return email, FALSE
     */
    public static function isEmail($data)
    {
        return filter_var($data, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @abstract 判断是否为正确的IP地址
     * @params string $data //ip
     * @return IP地址, FALSE
     */
    public static function isIp($data)
    {
        return filter_var($data, FILTER_VALIDATE_IP);
    }

    /**
     * @abstract 判断是否为正确的url路径
     * @params string $data //url
     * @params $flag = 262144 || 524288 //有路径的url 有查询的url
     * @return $data url地址, FALSE
     */
    public static function isUrl($data, $flag = null)
    {
        if (($flag === FILTER_FLAG_QUERY_REQUIRED) || ($flag === FILTER_FLAG_PATH_REQUIRED)) {
            return filter_var($data, FILTER_VALIDATE_URL, $flag);
        }
        return filter_var($data, FILTER_VALIDATE_URL);
    }

    /**
     * @abstract 判断是否为正确的QQ号
     * @params string $data //qq
     * @return $data qq号, FALSE
     */
    public static function isQq($data)
    {
        if (!isset($data) || !is_numeric($data) || strlen($data) < 4 || strlen($data) > 10 || $data[0] == 0) {
            return false;
        }
        return $data;
    }

    /**
     * @abstract 判断是否为正确的MSN
     * @param string $data //msn
     * @return $data msn, FALSE
     */
    public static function isMsn($data)
    {
        return filter_var($data, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @abstract 判断是否为正确的身份证
     * @param string $data //IDcart（身份证）
     * @return $data 身份证号码, FALSE
     */
    public static function isIdCart($data)
    {
        //长度
        $intStrLen = strlen($data);
        //最后一位
        $strLast = strtoupper(substr($data, 17, 1));
        //前17位
        $strFast = strtoupper(substr($data, 0, 17));
        //加权因子
        $Wi = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        // 校验码对应值
        $checkVaule = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        //十七位数字本体码 * 加权因子的和
        $sum = 0;
        for ($i = 0; $i < $intStrLen - 1; $i++) {
            $sum += substr($data, $i, 1) * $Wi[$i];
        }
        //取余数
        $mod = $sum % 11;
        //得到最后一位
        $idLastValue = $checkVaule[$mod];
        if ((!isset($data) || $strLast != $idLastValue || $intStrLen != 18 || !is_numeric($strFast)) && ($intStrLen != 15 || !is_numeric($data))) {
            return false;
        }
        return $data;
    }

    /**
     * @abstract 判断是否为正确的邮政编码
     * @param $data //ZipCode(邮编)
     * @return $data 邮政编码, FALSE
     */
    public static function isZipCode($data)
    {
        $strLen = strlen($data);
        if (!isset($data) || !is_numeric($data) || $strLen != 6 || !preg_match("/^[0-8]\d{5}$/", $data)) {
            return false;
        }
        return $data;
    }

    /**
     * @abstract 判断是否为正确的电话号码格式
     * @abstract $data //telareacode(电话区号)
     * @return $data 区号, FALSE
     */
    public static function isTelAreaCode($data)
    {
        if (!isset($data) || !is_numeric($data) || !preg_match('/^0\d{2,3}/', $data) || (strlen($data) != 3 && strlen($data) != 4)) {
            return false;
        }
        return $data;
    }

    /**
     * @abstract 判断是否为正确的手机号格式
     * @param $data //MobilePhone (手机)
     * @return $data 手机号, FALSE
     */
    public static function isMobilePhone($data)
    {
        $type = preg_match('/^1[3-9]\d{9}$/', $data);

        if (!isset($data) || !is_numeric($data) || !$type) {
            return false;
        }
        return $data;
    }

    /**
     * @abstract 判断是否为正确的年龄格式
     * @param $data //age (年龄)
     * @return $data 年龄, FALSE
     */
    public static function isAge($data)
    {
        if (!isset($data) || !is_numeric($data) || $data < 0 || $data > 50 || $data[0] == 0) {
            return false;
        }
        return $data;
    }

    /**
     * @abstract 判断是否为正确的用户名格式
     * @param $data //username (用户名)
     * @return $data 用户名, FALSE
     */
    public static function isUserName($data)
    {
        $strLen = strlen($data);
        if (!isset($data) || $strLen < 4 || $strLen > 100) {
            return false;
        }
        return $data;
    }

    /**
     * @abstract 校验日期格式,日期格式 Y-m-d
     * @param string $date	日期
     * @return bool
     */
    public static function checkDate($date)
    {
        $_date = explode('-', $date);
        if (count($_date) != 3) {
            return false;
        }
        if (!checkdate($_date[1], $_date[2], $_date[0])) {
            return false;
        }
        return true;
    }

    /**
     * @abstract 校验日期段有效性
     * 	对开始日期格式，有效性判断
     * 	对结束日期格式，有效性判断
     * 	校验结束日期是否大于等于开始日期
     */
    public static function checkDateStartToEnd($dateStart, $dateEnd)
    {
        if ((!self::checkDate($dateStart)) || (!self::checkDate($dateEnd))) {
            return false;
        }

        if ((strtotime($dateEnd) - strtotime($dateStart)) < 0) {
            return false;
        }
        return true;
    }

    /**
     * @abstract 判断内容是否只有中英文和长度是否符合规定
     * @param <string>$str,<int>$maxLength
     * @return bool
     */
    public static function checkName($str = '', $maxLength = 18)
    {
        $str = trim($str);
        if (empty($str)) {
            return false;
        }
        $_len = mb_strlen($str);
        if ($_len > $maxLength) {
            return false;
        }
        $result = 1;
        $autoLen = 0;
        for ($i = 0; $i < $_len; $i++) {
            $_char = mb_substr($str, $i, 1);
            $c = ord($_char);
            if ($c > 126 || $c < 0x00) {
                $autoLen += 3;
            } elseif (preg_match('/[a-zA-Z0-9.]/', $_char)) {
                $autoLen ++;
            } else {
                $result = 0;
                break;
            }
            if ($autoLen > $maxLength) {
                $result = 0;
                break;
            }
        }

        if (!$result) {
            return false;
        }
        return true;
    }

}
