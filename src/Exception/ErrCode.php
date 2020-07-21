<?php



namespace Fend\Exception;


use Fend\Config;

class ErrCode
{
    protected static $FILE_LIST = [];

    protected static $MODULE_LIST = null;

    /**
     * @param $classCode
     * @param $code
     * @param array $params
     * @throws BizException
     * @throws SystemException
     */
    public static function throws($classCode, $code, array $params = [])
    {
        if (self::$MODULE_LIST == null) {
            $path = Config::get('fend_err_code_file');
            self::$MODULE_LIST = include "$path";
        }

        $file = self::$MODULE_LIST[$classCode];

        if (!isset(static::$FILE_LIST[$file])) {
            static::$FILE_LIST[$file] = include "$file";
        }

        $msg = static::$FILE_LIST[$file][$code] ?? '';
        if (empty($msg)) {
            throw new SystemException('错误码未定义', -6001);
        }
        if (!empty($params)) {
            $msg = sprintf($msg, ... $params);
        }

        $code = $classCode . $code;

        throw new BizException($msg, $code);
    }
}