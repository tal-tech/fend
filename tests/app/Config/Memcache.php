<?php
/**
 * memcached配置
 */

return array(
    'default' => [
        'hosts' => [
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
        ],
        'persistent_id' => '',
        'pack_type' => \Fend\Cache\Memcache::PACK_TYPE_JSON,
        'prefix' => ''
    ],
    'memcache_broken' => [
        'hosts' => [
            ['host' => '127.0.0.2', 'port' => 11211, 'weight' => 100],
        ],
        'persistent_id' => '',
        'pack_type' => \Fend\Cache\Memcache::PACK_TYPE_SERIALIZE,
        'prefix' => 'ts_'
    ],
    'memcache_more' => [
        'hosts' => [
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 0],
        ],
        'persistent_id' => '',
        'pack_type' => \Fend\Cache\Memcache::PACK_TYPE_JSON,
        'prefix' => 'ts_'
    ]
);