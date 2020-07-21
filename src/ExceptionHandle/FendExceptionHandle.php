<?php
namespace Fend\ExceptionHandle;

use Fend\Di;
use Fend\Log\EagleEye;
use Fend\Logger;

class FendExceptionHandle implements ExceptionHandleInterface
{

    /**
     * @param \Throwable $e 异常
     * @param string $result 返回值
     *
     * @return string result结果
     * @throws \Throwable
     */
    public static function handle(\Throwable $e, string $result): string
    {
        $request = Di::factory()->getRequest();
        $response = Di::factory()->getResponse();

        $response->status(500);

        //record exception log
        Logger::exception(basename($e->getFile()), $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode(), $e->getTraceAsString(), array(
            "action"    => $request->header("HOST") . $request->server("REQUEST_URI"),
            "server_ip" => gethostname(),
        ));

        EagleEye::isEnable() && EagleEye::setRequestLogInfo("code", 500);
        EagleEye::isEnable() && EagleEye::setRequestLogInfo("backtrace", $e->getMessage() . "\r\n" . $e->getTraceAsString());
        return $result;
    }
}