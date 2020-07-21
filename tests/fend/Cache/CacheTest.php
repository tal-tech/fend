<?php


use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCacheFactory()
    {
        $instance = \Fend\Cache::factory(Fend\Cache::CACHE_TYPE_REDIS, 'default');

        self::assertEquals(get_class($instance), \Fend\Cache\Redis::class);

        $instance = \Fend\Cache::factory(Fend\Cache::CACHE_TYPE_MEMCACHE, 'default');

        self::assertEquals(get_class($instance), \Fend\Cache\Memcache::class);

        $test = 0;

        try {
            \Fend\Cache::factory(2);
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);
    }
}