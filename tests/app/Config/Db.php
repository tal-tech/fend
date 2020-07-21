<?php
/**
 * Created by PhpStorm.
 * User: fress
 * Date: 18/3/19
 * Time: 14:42
 */

//是否记录sql log
//define(DBLOG,1);

//数据库配置
return array(
    'default' => array(
        'w' => array(
            'host' => '127.0.0.1',
            'port' => 3306,
            'name' => 'fend_test',
            'user' => 'root',
            'pwd'  => '123456',
            'lang' => 'utf8mb4'
        ),
        'r' => array(
            'host' => '127.0.0.1',
            'port' => 3306,
            'name' => 'fend_test',
            'user' => 'root',
            'pwd'  => '123456',
            'lang' => 'utf8mb4'
        ),
    ),
    'fend_test' => array(
        'w' => array(
            'host' => '127.0.0.1',
            'port' => 3306,
            'name' => 'fend_test',
            'user' => 'root',
            'pwd'  => '123456',
            'lang' => 'utf8mb4'
        ),
        'r' => array(
            "type" => "random",
            "config" => array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'name' => 'fend_test',
                    'user' => 'root',
                    'pwd'  => '123456',
                    'lang' => 'utf8mb4'
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'name' => 'fend_test',
                    'user' => 'root',
                    'pwd'  => '123456',
                    'lang' => 'utf8mb4'
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'name' => 'fend_test',
                    'user' => 'root',
                    'pwd'  => '123456',
                    'lang' => 'utf8mb4'
                ),
            )
        ),
    ),
    'fend_test_broken' => array(
        'w' => array(
            'host' => '227.0.0.1',
            'port' => 3306,
            'name' => 'fend_test',
            'user' => 'root',
            'pwd'  => '',
            'lang' => 'utf8mb4'
        ),
        'r' => array(
            'host' => '227.0.0.1',
            'port' => 3306,
            'name' => 'fend_test',
            'user' => 'root',
            'pwd'  => '',
            'lang' => 'utf8mb4'
        ),
    ),
    'fend_test_broken_user' => array(
        'w' => array(
            'host' => '127.0.0.1',
            'port' => 3306,
            'name' => 'fend_test',
            'user' => 'root',
            'pwd'  => '123',
            'lang' => 'utf8mb4'
        ),
        'r' => array(
            'host' => '127.0.0.1',
            'port' => 3306,
            'name' => 'fend_test',
            'user' => 'root',
            'pwd'  => '123',
            'lang' => 'utf8mb4'
        ),
    )
);

