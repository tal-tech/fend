<?php
namespace Fend\Funcs;

/**
 * 数组的处理
 *
 **/
class FendArray
{
    /**
     * 去除值为零或空的键值
     * @param $array
     * @return array
     */
    public static function getFilterArray($array)
    {
        if (empty($array)) {
            return $array;
        }
        foreach ($array as $key=> &$value) {
            if (empty($value)) {
                unset($array[$key]);
            }
        }
        return $array;
    }
    /**
     * 判断一个值是否在数组中
     * @param $find
     * @param $array
     * @param bool $type 是否区分大小写 1-区分大小写
     * @return bool
     */
    public static function isInArray($find, $array, $type=true)
    {
        if (empty($array) || empty($find)) {
            return false;
        }
        foreach ($array as $value) {
            switch ($type) {
                case 1:
                    if ($find == $value) {
                        return true;
                    }
                    break;
                default:
                    if ($find==$value || strtolower($find) === strtolower($value)) {
                        return true;
                    }
                    break;
            }
        }
        return false;
    }
    /**
     * 获取数组某一键值数据
     * @param $array
     * @param $key
     *
     * @return array
     */
    public static function getInArray($array, $key)
    {
        if (!empty($array) && is_array($array)) {
            foreach ($array as $k => $value) {
                if (is_object($value)) {
                    $value = self::objectToArray($value);
                }
                $arrayId[] = $value[$key];
            }
            return $arrayId;
        }
        return [];
    }
    /**
     * 将对象转换为数组
     *
     * @param obj $object
     * @return array
     */
    public static function objectToArray($object)
    {
        $arr = array();
        if (!empty($object)) {
            foreach ($object as $key => $value) {
                if (is_object($value)) {
                    $arr[$key] = self::objectToArray($value); //判断类型是不是object
                } else {
                    $arr[$key] = $value;
                }
            }
        }
        return $arr;
    }

    //获取数组某一部分字段数据
    public static function getArrayByFields($array, $fields)
    {
        $dataAry = array();
        if (!empty($array)) {
            foreach ($array as $k => $value) {
                foreach ($fields as $field) {
                    $dataAry[$k][$field] = $value[$field];
                }
            }
        }
        return $dataAry;
    }
    //获取数组某一部分字段数据
    public static function getFromOneArrayByKeys($array, $fields)
    {
        $dataAry = array();
        if (!empty($array)) {
            foreach ($fields as $field) {
                if (isset($array[$field])) {
                    $dataAry[$field] = $array[$field];
                }
            }
        }
        return $dataAry;
    }


    //获取数组某一键值数据
    public static function getArrayBykey($array, $key)
    {
        $newArray = array();
        if (!empty($array)) {
            foreach ($array as $k => $value) {
                $newArray[$value[$key]] = $value;
            }
        }
        return $newArray;
    }

    /**
     * 对一个二维数组自定义排序
     *
     * @param array $ary
     * @param string $compareField
     * @param string $seq = 'DESC'|'ASC'
     * @param int $sortFlag = SORT_NUMERIC | SORT_REGULAR | SORT_STRING
     * @return array
     */
    public static function sort(&$ary, $compareField, $seq = 'DESC', $sortFlag = SORT_NUMERIC)
    {
        if (empty($ary)) {
            return array();
        }

        $sortData = array();
        foreach ($ary as $key => $value) {
            $sortData[$key] = $value[$compareField];
        }
        ($seq === 'DESC') ? arsort($sortData, $sortFlag) : asort($sortData, $sortFlag);

        $ret = array();
        foreach ($sortData as $key => $value) {
            $ret[$key] = $ary[$key];
        }
        $ary = $ret;
        return $ary;
    }

    //按数组值出现次数计数并降序排序返回结果
    public static function sortCountArray($array)
    {
        $countAry = array();
        if (!empty($array)) {
            foreach ($array as $id) {
                if (!isset($countAry[$id])) {
                    $countAry[$id] = 1;
                    continue;
                }
                $countAry[$id] ++;
            }
        }
        !empty($countAry) && arsort($countAry);

        return $countAry;
    }

    /**
     * 多维数组排序
     * @param array $array 需要排序的数组
     * @param string $key 根据哪个字段排序
     * @param string $type 排序的方式
     * @return array|string
     **/
    public static function arraySort($array, $keys, $type='asc')
    {
        if (!isset($array) || !is_array($array) || empty($array)) {
            return '';
        }
        if (!isset($keys) || trim($keys)=== '') {
            return '';
        }
        if (!isset($type) || $type=='' || !in_array(strtolower($type), array('asc','desc'))) {
            return '';
        }
        $keysvalue=array();
        foreach ($array as $key=>$val) {
            if (isset($val[$keys])) {
                $val[$keys] = str_replace('-', '', $val[$keys]);
                $val[$keys] = str_replace(' ', '', $val[$keys]);
                $val[$keys] = str_replace(':', '', $val[$keys]);
                $keysvalue[] =$val[$keys];
            } else {
                return false;
            }
        }
        asort($keysvalue); //key值排序
        reset($keysvalue); //指针重新指向数组第一个
        foreach ($keysvalue as $key=>$vals) {
            $keysort[] = $key;
        }
        $keysvalue = array();
        $count=count($keysort);
        if (strtolower($type) != 'asc') {
            for ($i=$count-1; $i>=0; $i--) {
                $keysvalue[] = $array[$keysort[$i]];
            }
        } else {
            for ($i=0; $i<$count; $i++) {
                $keysvalue[] = $array[$keysort[$i]];
            }
        }
        return $keysvalue;
    }
}
