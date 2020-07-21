#!/usr/bin/env bash
php Bin/phpunit --list-tests

# 启动单元测试
php -d zend_extension=xdebug.so Bin/phpunit -vvv
