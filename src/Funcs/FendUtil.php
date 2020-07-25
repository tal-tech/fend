<?php

namespace Fend\Funcs;

class FendUtil
{

    /**
     * 判断变量是否为空
     * 特殊的地方在于，0 '0' '000' 也会被判定非空
     * @param $param
     * @return bool
     */
    public static function realEmpty($param)
    {
        if ($param === null
            || (is_array($param) && empty($param))
            || (is_string($param) && $param === '')) {
            return true;
        }
        return false;
    }

}