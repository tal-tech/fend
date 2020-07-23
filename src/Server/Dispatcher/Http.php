<?php

namespace Fend\Server\Dispatcher;

use Fend\Config;
use Fend\Debug;
use Fend\Di;
use Fend\Exception\ExitException;
use Fend\ExceptionHandle\FendExceptionHandle;
use Fend\Log\EagleEye;

use Fend\Request;
use Fend\Response;
use Fend\Router\Dispatcher;
use Fend\Router\RouterException;

/**
 * Class standard
 *
 * @property  \swoole_http_server $_currentServer
 */
class Http extends BaseInterface
{
    protected $_config = null;

    public function onWorkerStart(\swoole_server $server, $worker_id)
    {
        parent::onWorkerStart($server, $worker_id);

        //init router
        Di::factory()->set("router", new Dispatcher());
    }

    /**
     * 处理 http_server 服务器的 request 请求
     * @param $request
     * @param $response
     * @tutorial 获得 REQUEST_URI 并解析，通过 \Fend\Acl 路由到指定的 controller
     * @throws \Exception
     */
    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        //debug init
        Debug::Init("swoole");

        //request
        Di::factory()->set('http_request', $request);
        $fRequest = new Request("swoole_http");
        Di::factory()->setRequest($fRequest);

        //response
        Di::factory()->set('http_response', $response);
        $fResponse = new Response("swoole_http");
        Di::factory()->setResponse($fResponse);

        $_SERVER                    = array_merge($request->server, array_change_key_case($request->server, CASE_UPPER));
        $_SERVER['HTTP_USER_AGENT'] = !empty($request->header['user-agent']) ? $request->header['user-agent'] : '';

        $host                            = parse_url($request->header['host']);
        $_SERVER['HTTP_HOST']            = !empty($host['path']) ? $host['path'] : $host['host'];
        $_SERVER["REMOTE_ADDR"]          = !empty($request->server["remote_addr"]) ? $request->server["remote_addr"] : '';
        $_SERVER["HTTP_CLIENT_IP"]       = !empty($request->server["client_ip"]) ? $request->server["client_ip"] : '';
        $_SERVER["HTTP_X_FORWARDED_FOR"] = !empty($request->server["x_forwarded_for"]) ? $request->server["x_forwarded_for"] : '';

        //debug info show
        if ($fRequest->get("wxdebug") == 1) {
            Debug::enableDebug();
        }

        //header
        $fResponse->header("Content-Type", "text/html; charset=utf-8");
        $fResponse->header("Server-Version", "1.0.1");

        $domain     = $fRequest->header("HOST");
        $httpMethod = $fRequest->server("REQUEST_METHOD");
        $uri        = $fRequest->server("REQUEST_URI");

        if(EagleEye::isEnable()) {
            //prepare the traceid
            $traceid = "";
            $rpcid   = "";

            if (!empty($fRequest->header("traceid"))) {
                $traceid = $request->header["traceid"];
            }

            if (!empty($fRequest->header("rpcid"))) {
                $rpcid = $request->header["rpcid"];
            }

            //eagle eye request start init
            EagleEye::requestStart($traceid, $rpcid);
            $traceid = EagleEye::getTraceId();
            $rpcid   = EagleEye::getReciveRpcId();

            //set response header contain trace id and rpc id
            $fResponse->header("traceid", $traceid);
            $fResponse->header("rpcid", $rpcid);

            //record this request
            EagleEye::setRequestLogInfo("client_ip", \Fend\Funcs\FendHttp::getIp());
            EagleEye::setRequestLogInfo("action", $domain . $uri);
            EagleEye::setRequestLogInfo("param", json_encode(array(
                "post" => $fRequest->post(),
                "get"  => $fRequest->get(),
                "body" => $fRequest->getRaw(),
            )));
            EagleEye::setRequestLogInfo("source", isset($request->header["referer"]) ? $request->header["referer"] : '');
            EagleEye::setRequestLogInfo("user_agent", isset($request->header["user-agent"]) ? $request->header["user-agent"] : '');
            EagleEye::setRequestLogInfo("code", 200);
        }

        //compress
        if (!empty($request->header["Accept-Encoding"]) && stristr($request->header["Accept-Encoding"], "gzip")) {
            if (SWOOLE_VERSION < "4.1.0") {
                $response->gzip(4);
            }
        }

        $result = "";
        try {
            //fetch router
            $router = Di::factory()->get("router");

            ob_start();
            $result = $router->dispatch($domain, $httpMethod, $uri);
            Debug::appendDebugOutput(ob_get_clean());

        } catch (ExitException | \Swoole\Exception $e) {
            Debug::appendDebugOutput(ob_get_clean());

            if ($e instanceof \Fend\Exception\ExitException) {
                //人工截断后续执行
                //输出传递结果结束
                $result = $e->getData();
            } else if ($e instanceof \Swoole\Exception) {
                $result = $e->getMessage();
            }
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

            //record exception
            Debug::appendException($e);

        } catch (\Throwable $e) {
            Debug::appendDebugOutput(ob_get_clean());

            //debug
            Debug::appendException($e);

            //custom exception handle
            $exceptionHandleName = Config::get("Fend.exceptionHandle", FendExceptionHandle::class);
            if(class_exists($exceptionHandleName) && is_callable([$exceptionHandleName, "handle"])) {
                $result = call_user_func_array([$exceptionHandleName, "handle"], [$e, $result]);
            } else {
                $response->end("config of exception Handle is wrong.");
                return;
            }
        }

        if (Debug::isDebug()) {
            $response->end(Debug::show($result));
        }else{
            //response result
            $response->end($result);
        }

        if(EagleEye::isEnable()) {
            EagleEye::setRequestLogInfo("response", $result);
            EagleEye::setRequestLogInfo("response_length", strlen($result));
            EagleEye::requestFinished();
        }

        //clean up last error before
        error_clear_last();
        clearstatcache();

    }
}
