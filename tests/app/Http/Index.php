<?php

namespace Test\App\Http;

use Fend\Request;
use Fend\Response;

/**
 * Class Index
 * 首页
 * @author gary
 */
class Index
{

    public function Init(Request $request, Response $response)
    {
        $request->setQueryString("yes", 1);
    }

    /**
     * 首页演示
     * @param Request $request
     * @param Response $response
     * @return mixed
     * @throws \Exception
     */
    public function index(Request $request, Response $response)
    {
        return "index";
    }

    /**
     * 500错误演示
     * 如果请求querystring带wxdebug=1会看到更多信息
     *
     * @param Request $request
     * @param Response $response
     * @throws \Exception
     */
    public function exception(Request $request, Response $response)
    {
        throw new \Exception("test", 1231);
    }

    public function UnInit(Request $request, Response $response, &$result)
    {
        //$result = $result . "\r\n Uninit append text";
    }
}
