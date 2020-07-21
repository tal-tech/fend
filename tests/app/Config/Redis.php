<?php
/**
 * Redis配置
 */

return array(
    'key_map_path' => SYS_ROOTDIR . 'tests/app/Config/RedisKey',

    'default' => ['host' => '127.0.0.1', 'port' => 6379, 'pre' => 'ts_', 'pwd' => ''],
    'fend_test' => ['host' => '127.0.0.1', 'port' => 6379, 'pre' => 'f_', 'pwd' => '', 'db'=> 1],
    'fend_test_broken' => ['host' => '227.0.0.1', 'port' => 6379, 'pre' => 'f_', 'pwd' => ''],
    'fend_test_pwd' => ['host' => '127.0.0.1', 'port' => 6379, 'pre' => 'f_', 'pwd' => '123']
);