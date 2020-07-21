<?php

use Fend\Exception\BizException;

class ErrorCodeTest extends \PHPUnit\Framework\TestCase
{
    public function testErrorCode()
    {
        \Fend\Config::set('fend_err_code_file', SYS_ROOTDIR . '/app/Const/ModuleDefine.php');

        $code = '';
        try {
            \Fend\Exception\ErrCode::throws('001', '0001', []);
        } catch (BizException $e) {
            $code = $e->getCode();
        }

        self::assertEquals('0010001',$code);

        $msg = '';
        try {
            \Fend\Exception\ErrCode::throws('001', '0002', ['hello']);
        } catch (BizException $e) {
            $msg = $e->getMessage();
        }

        self::assertEquals('hello not set',$msg);

        $test = '';
        try {
            \Fend\Exception\ErrCode::throws('001', '0003', ['hello']);
        } catch (\Fend\Exception\SystemException $e) {
            $test = 1;
        }

        self::assertEquals('1',$test);
    }
}