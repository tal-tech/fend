<?php
declare(strict_types=1);

namespace App\Test\Fend\Db;

use Fend\Cache;
use PHPUnit\Framework\TestCase;

class redisTest extends TestCase
{
    public function testRedis()
    {
        $redis = Cache::factory(Cache::CACHE_TYPE_REDIS, "fend_test");

        $val   = "string test random:".mt_rand(1, 12312312);
        $redis->set("haha0", $val, $redis->setLifeTime("1i"));
        $redis->set("haha1", $val, $redis->setLifeTime("1w"));
        $redis->set("haha2", $val, $redis->setLifeTime("1d"));
        $redis->set("haha3", $val, $redis->setLifeTime("1h"));
        $redis->set("haha4", $val, $redis->setLifeTime(15));
        $redis->set("haha5", $val, $redis->setLifeTime("17"));

        $ret = $redis->get("haha0");

        self::assertEquals($val,$ret);

        $test = 0;

        try{
            Cache::factory(Cache::CACHE_TYPE_REDIS, "fend_test_broken");
        }catch (\RedisException $e){
            $test = 1;
        }
        self::assertEquals($test, 1);

        $test = 0;
        try {
            Cache::factory(Cache::CACHE_TYPE_REDIS, "fend_test_pwd");
        }catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals($test, 1);

        $test = 0;
        try {
            Cache::factory(Cache::CACHE_TYPE_REDIS, "wwww");
        }catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals($test, 1);
    }

}