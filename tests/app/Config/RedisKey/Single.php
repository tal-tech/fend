<?php


return [

    'single_key_1' => [
        'key' => 'single_string_key_%d',      // key的字符串
        'params' => [                       // key的参数
            'id',
        ],
        'type' => 'string',                 // redis 存储类型
        'instance' => 'default'             // redis 配置实例
    ],
];