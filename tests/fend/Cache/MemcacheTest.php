<?php


use Fend\Cache;
use PHPUnit\Framework\TestCase;

class MemcacheTest extends TestCase
{
    public function testMemcache()
    {
        $memcache = Cache::factory(Cache::CACHE_TYPE_MEMCACHE, "default");

        $val = "string test random:" . mt_rand(1, 12312312);
        $ret = $memcache->set("test_0", $val, "1i");
        self::assertTrue($ret);
        $memcache->set("test_1", $val, "1w");
        $memcache->set("test_2", $val, "1d");
        $memcache->set("test_3", $val, "1h");
        $memcache->set("test_4", $val, 15);
        $memcache->set("test_5", $val, "17");
        $memcache->set("test_6", $val, "17m");
        $ret = $memcache->get("test_0");

        self::assertEquals($val, $ret);

        $ret = $memcache->getObj()->get("test_0");

        self::assertEquals($val, $ret);

        $ret = $memcache->del("test_0");

        self::assertEquals(true, $ret);

        $val = [
            'value' => mt_rand(1, 12312312)
        ];
        $ret = $memcache->getOrSet("test_0", function () use ($val) {
            return $val;
        }, "1i");

        self::assertEquals($val, $ret);

        $ret = $memcache->getOrSet("test_0", function () use ($val) {
            return $val;
        }, "1i");

        self::assertEquals($val, $ret);

        $memcache->setPackType(Cache\Memcache::PACK_TYPE_SERIALIZE);

        $val = [
            'value' => mt_rand(1, 12312312)
        ];
        $memcache->set("test_0", $val, "1i");
        $ret = $memcache->get("test_0");

        self::assertEquals($val, $ret);


        $memcache->setPackType(3);

        $test = 0;
        try {
            $memcache->set("test_0", $val, "1i");
        } catch (\Fend\Exception\SystemException $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);

        $test = 0;
        try {
            $ret = $memcache->get("test_0");
        } catch (\Fend\Exception\SystemException $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);
    }

    public function testMemcacheMoreConnection()
    {
        $memcache = Cache::factory(Cache::CACHE_TYPE_MEMCACHE, "memcache_more");
        $ret = $memcache->set("help", "yaya");
        self::assertTrue($ret);

        $ret = $memcache->set("help1", "yaya");
        self::assertTrue($ret);

        $ret = $memcache->set("help2", "yaya");
        self::assertTrue($ret);

        $ret = $memcache->set("help3", "yaya");
        self::assertTrue($ret);

        $ret = $memcache->set("help4", "yaya");
        self::assertTrue($ret);

        $ret = $memcache->get("help");
        self::assertEquals("yaya", $ret);

        $ret = $memcache->get("help1");
        self::assertEquals("yaya", $ret);

        $ret = $memcache->get("help2");
        self::assertEquals("yaya", $ret);

        $ret = $memcache->get("help3");
        self::assertEquals("yaya", $ret);

        $ret = $memcache->get("help4");
        self::assertEquals("yaya", $ret);

        $ret = $memcache->del("help");
        self::assertTrue($ret);

        $ret = $memcache->del("help1");
        self::assertTrue($ret);

        $ret = $memcache->del("help2");
        self::assertTrue($ret);

        $ret = $memcache->del("help3");
        self::assertTrue($ret);

        $ret = $memcache->del("help4");
        self::assertTrue($ret);


    }
}