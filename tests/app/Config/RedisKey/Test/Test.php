<?php


return [

    'test_key_1' => [
        'key' => 'test_string_key_%d',      // key的字符串
        'params' => [                       // key的参数
            'id',
        ],
        'type' => 'string',                 // redis 存储类型
        'instance' => 'default'             // redis 配置实例
    ],

    'test_key_2' => [
        'key' => 'test_set_key_%d_%d',      // key的字符串
        'params' => [                       // key的参数
            'id',
            'index'
        ],
        'type' => 'zset',                   // redis 存储类型
        'instance' => 'default'             // redis 配置实例
    ],

    'test_key_3' => [
        'key' => 'test_hash_key_%d',        // key的字符串
        'params' => [                       // key的参数
            'id',
        ],
        'type' => 'hash',                   // redis 存储类型
        'field_list'    => [                // hash table 的子字段列表
            'name',
            'mobile',
        ],
        'instance' => 'default'             // redis 配置实例
    ],

    'test_key_4' => [
        'key' => 'test_set_key_%d',        // key的字符串
        'params' => [                       // key的参数
            'id',
        ],
        'type' => 'set',                   // redis 存储类型
        'instance' => 'default'             // redis 配置实例
    ],

    'test_key_5' => [
        'key' => 'test_zset_key_%d',        // key的字符串
        'params' => [                       // key的参数
            'id',
        ],
        'type' => 'zset',                   // redis 存储类型
        'instance' => 'default'             // redis 配置实例
    ],

    'test_key_6' => [
        'key' => 'test_incr_key_%d',        // key的字符串
        'params' => [                       // key的参数
            'id',
        ],
        'type' => 'incr',                   // redis 存储类型
        'instance' => 'default'             // redis 配置实例
    ],

    'test_key_7' => [
        'key' => 'test_list_key_%d',        // key的字符串
        'params' => [                       // key的参数
            'id',
        ],
        'type' => 'list',                   // redis 存储类型
        'instance' => 'default'             // redis 配置实例
    ],

    'test_key_8' => [
        'key' => 'test_channel_key',        // key的字符串
        'type' => 'channel',                // redis 存储类型
        'instance' => 'default'             // redis 配置实例
    ],
];