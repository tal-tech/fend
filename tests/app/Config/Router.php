<?php
//新路由解析
////////////////////////////////////////////////////////////
//注意：新路由修改后,记得清空cache/router内缓存文件
////////////////////////////////////////////////////////////

return [

    //fast router配置
    //不同域名可以映射不同App\Http下子目录,若没有，默认以\app\http为开始查找
    //限制规则：App\Http 内文件名必须首字母大写，其他字母皆为小写
    //启用fastrouter，composer需要引入这个组件方可使用 composer require nikic/fast-route 1.3
    "map" => [
        //没有指定域名的请求访问的路径
        'default'     => [
            'root'       => "\\Test\\App\\Http",//namespace
            'direct'     => true,//如果没有router匹配，那么继续按路径进行路由
            "fastrouter" => false,
        ],
        'www.fend.com' => [
            'root'       => "\\Test\\App\\Http",
            'direct'     => false,//如果没有router匹配，那么继续按路径进行路由
            "fastrouter" => true,//启用fastrouter
            'open_cache'  => false,

            //fastrouter映射
            'router'     => [
                ['GET', '/index', '\Example\FastRouter\Http\Index@index'],
                ['POST', '/exception', function() {
                    throw new Exception("test");
                }],
                ['POST', '/test', 123],
                ['POST', '/class', '\Example\FastRouter\Http\Hello'],
                ['POST', '/class1', '\Example\FastRouter\Http\Hello@test'],
                ['POST', '/class2', '\Example\FastRouter\Http\Index@hello'],
            ],
        ],
    ],
];