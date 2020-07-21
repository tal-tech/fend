<?php
namespace Fend\ExceptionHandle;

interface ExceptionHandleInterface {

    /**
     * 处理异常
     * @param \Throwable $e 错误异常
     * @param string $result 目前返回值
     * @return string
     */
    public static function handle(\Throwable $e, string $result) : string;

}