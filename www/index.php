<?php
require_once "../init.php";

use Fend\Config;
use Fend\Debug;
use Fend\Di;
use Fend\ExceptionHandle\FendExceptionHandle;
use Fend\Funcs\FendHttp;
use Fend\Log\EagleEye;
use Fend\Log\LogAgent;
use Fend\Logger;
use Fend\Router\Dispatcher;
use Fend\Router\RouterException;

//获取域名
$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

//获取method以及uri
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri        = $_SERVER['REQUEST_URI'];

//去掉uri内参数
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

//init debug
Debug::Init("fpm");

//fpm debug 模式开启
if (!empty($_GET['wxdebug'])) {
    Debug::enableDebug($_GET['wxdebug']);
}

//request
$request = new \Fend\Request("fpm");
Di::factory()->setRequest($request);

//response
$response = new \Fend\Response("fpm");
Di::factory()->setResponse($response);

//record this request
LogAgent::setDumpLogMode(LogAgent::LOGAGENT_DUMP_LOG_MODE_BUFFER);

//prepare the traceid
$traceId = "";
$rpcId   = "";

if(EagleEye::isEnable()){

    if (!empty($request->header("traceId"))) {
        $traceId = $request->header("traceId");
    }

    if (!empty($request->header("rpcId"))) {
        $rpcId = $request->header("rpcId");
    }
    EagleEye::requestStart($traceId, $rpcId);

    EagleEye::setMultiRequestLogInfo([
        "client_ip"=> FendHttp::getIp(),
        "action" => $domain . $uri,
        "param" => json_encode([
            "post" => $request->post(),
            "get"  => $request->get(),
            "body" => $request->getRaw(),
        ]),
        "source" => $request->header("referer"),
        "user_agent" => $request->header("user-agent"),
        "code" => 200,
    ]);

    //response header
    $response->header("traceid", EagleEye::getTraceId());
    $response->header("rpcid", EagleEye::getReciveRpcId());
}


ob_start();

$result = "";

try {
    //router init
    $router = new Dispatcher();
    Di::factory()->set("router", $router);

    //start controller
    $result = $router->dispatch($domain, $httpMethod, $uri);
    Debug::appendDebugOutput(ob_get_clean());

} catch (\Fend\Exception\ExitException $e) {
    Debug::appendDebugOutput(ob_get_clean());

    //人工截断后续执行
    //输出传递结果结束
    $result = $e->getData();

} catch (RouterException $e) {
    Debug::appendDebugOutput(ob_get_clean());

    //return code by error code
    switch ($e->getCode()) {
        case 404:
            $response->status(404);
            break;
        case 405:
            $response->status(405);
            break;
        default:
            $response->status(500);
            break;
    }
    //record exception log
    Logger::exception(basename($e->getFile()), $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode(), $e->getTraceAsString(), array(
        "action"    => $_SERVER['HTTP_HOST'] . $uri,
        "server_ip" => gethostname(),
    ));

    EagleEye::isEnable() && EagleEye::setRequestLogInfo("backtrace", $e->getMessage() . "\r\n" . $e->getTraceAsString());

    //record exception
    Debug::appendException($e);

} catch (\Throwable $e) {
    Debug::appendDebugOutput(ob_get_clean());

    //record exception
    Debug::appendException($e);

    //custom exception handle
    $exceptionHandleName = Config::get("Fend.exceptionHandle", FendExceptionHandle::class);
    if(class_exists($exceptionHandleName) && is_callable([$exceptionHandleName, "handle"])) {
        $result = call_user_func_array([$exceptionHandleName, "handle"], [$e, $result]);
    } else {
        exit("config of exception Handle is wrong.");
    }
}

if(EagleEye::isEnable())
{
    EagleEye::setMultiRequestLogInfo([
        "response" => $result,
        "response_length" => strlen($result)
    ]);
    EagleEye::requestFinished();
}

//show debug info
if (Debug::isDebug()) {
    echo Debug::show($result);
}else{
    echo $result;
}
