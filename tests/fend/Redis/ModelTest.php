<?php


use Example\RedisModel\Model\Redis\TestModel;

class ModelTest extends \PHPUnit\Framework\TestCase
{
    public function testException()
    {
        $test = 0;
        try {
            new \Fend\Redis\RedisModel();
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);

        $config = \Fend\Config::get('Redis');
        \Fend\Config::set('Redis', []);
        $test = 0;
        try {
            new \Fend\Redis\RedisModel();
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);
        \Fend\Config::set('Redis', $config);

        $model = new TestModel();

        $test = 0;
        try {
            $model->zAdd('test_key_2', [
                'index' => 2,
                'id' => 1,
            ], 1,"test");
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(0, $test);

        $model = new TestModel();

        $test = 0;
        try {
            $model->get('test_key_10', [
                'id' => 1,
            ]);
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);

        $test = 0;
        try {
            $model->getDb('test_key_10');
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);

        $test = 0;
        try {
            $model->hGet('test_key_1', [
                'id' => 1,
            ]);
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);
    }

    /**
     * @throws Exception
     */
    public function testModel()
    {
        $model = \Example\RedisModel\Model\Redis\SingleModel::factory();
        $result = $model->set('single_key_1', [
            'id' => 1,
        ], 10);

        self::assertEquals(1, $result);

        $result = $model->get('single_key_1', [
            'id' => 1,
        ]);
        self::assertEquals(10, $result[0]);

        $model = new TestModel();

        $result = $model->set('test_key_1', [
            'id' => 1,
        ], 10);

        self::assertEquals(1, $result);

        $result = $model->get('test_key_1', [
            'id' => 1,
        ]);
        self::assertEquals(10, $result[0]);

        $model->mset('test_key_1', [
            ['id' => 2,],
            ['id' => 3,],
        ], [11, 12]);

        $result = $model->get('test_key_1', [
            ['id' => 1,],
            ['id' => 2,],
        ]);
        self::assertEquals(11, $result[1]);

        $result = $model->get('test_key_1', [
            "test" => ['id' => 1,],
            "hello" => ['id' => 2,],
        ]);
        self::assertEquals(11, $result['hello']);


        $result = $model->set('test_key_1', [
            'id' => 3,
        ], 12, 60);

        self::assertEquals(1, $result);

        $result = $model->setnx('test_key_1', [
            'id' => 4,
        ], 12, 2);

        self::assertEquals(1, $result);

        $result = $model->setnx('test_key_1', [
            'id' => 4,
        ], 12, 2);

        self::assertEquals(0, $result);

        $result = $model->exist('test_key_1', [
            'id' => 3,
        ]);

        self::assertEquals(1, $result);

        $result = $model->del('test_key_1', [
            'id' => 4,
        ]);
        self::assertEquals(1, $result);

        $result = $model->del('test_key_1', [
            ['id' => 4]
        ]);
        self::assertEquals(0, $result);
    }

    public function testIncr()
    {
        $model = new TestModel();

        $model->mset('test_key_6', [
            ['id' => 1,],
            ['id' => 2,],
        ], [0, 0], 60);

        $result = $model->incr('test_key_6', [
            'id' => 1,
        ], 1);
        self::assertEquals(1, $result);

        $result = $model->incr('test_key_6', [
            'id' => 2,
        ], 1.5);
        self::assertEquals(1.5, $result);

        $result = $model->decr('test_key_6', [
            'id' => 1,
        ], 1);

        self::assertEquals(0, $result);
    }

    /**
     * @throws Exception
     */
    public function testZset()
    {
        $model = new TestModel();

        $model->del('test_key_5', [
            'id' => 1,
        ]);

        $result = $model->mzAdd('test_key_5', [
            [],
        ]);
        self::assertEquals(false, $result);

        $result = $model->mzAdd('test_key_5', [
            [
                'params' => [
                    'id' => 1,
                ],
                'score' => 1,
                'value' => '3'
            ],
        ]);
        self::assertEquals([1], $result);

        $result = $model->zAdd('test_key_5', [
            'id' => 1,
        ], "hello", 1);
        self::assertEquals(false, $result);


        $result = $model->zAdd('test_key_5', [
            'id' => 1,
        ], 2, 1);
        self::assertEquals(1, $result);

        $result = $model->zCard('test_key_5', [
            'test' => ['id' => 1]
        ], 1);

        self::assertEquals(['test' => 2], $result);

        $result = $model->zCard('test_key_5', ['id' => 1], 0);

        self::assertEquals(2, $result);

        $result = $model->zCount('test_key_5', [
            'test' => ['id' => 1]
        ], '-inf', '+inf', 1);

        self::assertEquals(['test' => 2], $result);

        $result = $model->zCount('test_key_5', ['id' => 1], '-inf', '+inf', 0);

        self::assertEquals(2, $result);


        $result = $model->zIncrBy('test_key_5', [
            'id' => 1,
        ], 3, 1);
        self::assertEquals(5, $result);

        $result = $model->zIncrBy('test_key_5', [
            'id' => 1,
        ], 'test', 1);
        self::assertEquals(false, $result);

        $result = $model->zRank('test_key_5', [
            'id' => 1,
        ], '1', 1);
        self::assertEquals(1, $result);

        $result = $model->zRank('test_key_5', [
            'id' => 1,
        ], '1', -1);
        self::assertEquals(0, $result);

        $result = $model->zAdd('test_key_5', [
            'id' => 1,
        ], 2, 2);
        self::assertEquals(1, $result);

        $result = $model->zRem('test_key_5', [
            'id' => 1,
        ], 2);
        self::assertEquals(1, $result);

        $result = $model->zAdd('test_key_5', [
            'id' => 1,
        ], 2, 2);
        self::assertEquals(1, $result);

        $result = $model->zRemRangeByRank('test_key_5', [
            'id' => 1,
        ], 1, 1);
        self::assertEquals(1, $result);

        $result = $model->zAdd('test_key_5', [
            'id' => 1,
        ], 2, 2);
        self::assertEquals(1, $result);

        $result = $model->zRemRangeByScore('test_key_5', [
            'id' => 1,
        ], 2, 3);
        self::assertEquals(1, $result);

        $result = $model->zScore('test_key_5', [
            'id' => 1,
        ], 1);
        self::assertEquals(5, $result);

        $result = $model->zRange('test_key_5', [
            'id' => 1,
        ], 0, -1, 1, true, 1);
        self::assertEquals([
            3 => doubleval(1),
            1 => doubleval(5)
        ], $result);

        $result = $model->zRange('test_key_5', [
            'id' => 1,
        ], 0, -1, 2, true, 0);
        self::assertEquals([
            1 => doubleval(5),
            3 => doubleval(1),
        ], $result);

        $result = $model->zRange('test_key_5', [
            ['id' => 1]
        ], 0, -1, 2, true, 0);
        self::assertEquals([[
            1 => doubleval(5),
            3 => doubleval(1),
        ]], $result);

        $result = $model->zRange('test_key_5', [
            ['id' => 1]
        ], 0, -1, 1, true, 1);
        self::assertEquals([[
            3 => doubleval(1),
            1 => doubleval(5),
        ]], $result);

        $result = $model->mZRange('test_key_5', [
            ['id' => 1]
        ], [
            'withscores' => true
        ], 1);
        self::assertEquals([[
            3 => doubleval(1),
            1 => doubleval(5),
        ]], $result);

        $result = $model->zRangeByScore('test_key_5', [
            'id' => 1,
        ], '-inf', '+inf', 1, true, [0, 2], 1);
        self::assertEquals([
            3 => doubleval(1),
            1 => doubleval(5),
        ], $result);

        $result = $model->zRangeByScore('test_key_5', [
            'id' => 1,
        ], '+inf', '-inf', 2, true, [0, 2], 1);
        self::assertEquals([
            1 => doubleval(5),
            3 => doubleval(1),
        ], $result);

        $result = $model->zRangeByScore('test_key_5', [
            ['id' => 1,]
        ], '-inf', '+inf', 1, true, [0, 2], 1);
        self::assertEquals([[
            3 => doubleval(1),
            1 => doubleval(5),
        ]], $result);

        $result = $model->zRangeByScore('test_key_5', [
            ['id' => 1,]
        ], '+inf', '-inf', 2, true, [0, 2], 1);
        self::assertEquals([[
            1 => doubleval(5),
            3 => doubleval(1),
        ]], $result);

        $result = $model->mZRangeByScore('test_key_5', [
            ['id' => 1]
        ], [
            'start' => '-inf',
            'end' => '+inf',
            'withscores' => true,
            'limit' => [0, 2]
        ], 1);
        self::assertEquals([[
            3 => doubleval(1),
            1 => doubleval(5),
        ]], $result);

        $model->del('test_key_5', [
            'id' => 2,
        ]);

        $result = $model->zAdd('test_key_5', [
            'id' => 2,
        ], 2, 1);
        self::assertEquals(1, $result);

        $result = $model->zInters('test_key_5', [
            'id' => 3,
        ], [
            $model->generateKey('test_key_5', [
                'id' => 1,
            ], ['zset']),
            $model->generateKey('test_key_5', [
                'id' => 2,
            ], ['zset'])
        ], [
            1,
            1
        ]);
        self::assertEquals(1, $result);

        $result = $model->zInters('test_key_5', [
            'id' => 3,
        ], [
            $model->generateKey('test_key_5', [
                'id' => 1,
            ], ['zset']),
            $model->generateKey('test_key_5', [
                'id' => 2,
            ], ['zset'])
        ], [
            1,
            1
        ], 'SUM', 2);
        self::assertEquals(2, $result);

        $result = $model->del('test_key_5', [
            'id' => 1,
        ]);
        self::assertEquals(1, $result);

        $result = $model->del('test_key_5', [
            'id' => 2,
        ]);
        self::assertEquals(1, $result);

        $result = $model->del('test_key_5', [
            'id' => 3,
        ]);
        self::assertEquals(1, $result);
    }

    public function testHash()
    {
        $model = new TestModel();

        $model->del('test_key_3', [
            'id' => 1,
        ]);

        $model->del('test_key_3', [
            'id' => 2,
        ]);

        $model->del('test_key_3', [
            'id' => 3,
        ]);

        $model->del('test_key_3', [
            'id' => 4,
        ]);

        $result = $model->hSet('test_key_3', [
            'id' => 1,
        ], [
            'name' => 'hello',
            'mobile' => 123
        ]);
        self::assertEquals(1, $result);

        $result = $model->hSet('test_key_3', [
            'id' => 2,
        ], [
            'name' => 'hello',
            'mobile' => 123,
            'test' => 'hello'
        ], true);
        self::assertEquals(1, $result);

        $result = $model->mhSet('test_key_3', [
            [
                'id' => 3
            ]
        ], [
            [
                'name' => 'hello',
                'mobile' => 10
            ]
        ]);
        self::assertEquals([1], $result);

        $result = $model->mhSet('test_key_3', [
            [
                'id' => 4
            ]
        ], [
            [
                'name' => 'hello',
                'mobile' => 10
            ]
        ], true);
        self::assertEquals([1], $result);

        $result = $model->hIncrBy('test_key_3', [
            'id' => 3
        ], 'mobile', 1);
        self::assertEquals(11, $result);

        $result = $model->hIncrBy('test_key_3', [
            'id' => 3
        ], 'test', 1);
        self::assertEquals(false, $result);

        $result = $model->hGet('test_key_3', [
            'id' => 1
        ], [
            'mobile'
        ], 1);

        self::assertEquals([
            'mobile' => '123'
        ], $result);

        $result = $model->hGet('test_key_3', [
            'id' => 1
        ], [], 1);

        self::assertEquals([
            'name'  => 'hello',
            'mobile' => '123'
        ], $result);

        $result = $model->hGet('test_key_3', [
            ['id' => 1]
        ], [
            'mobile'
        ], 1);

        self::assertEquals([
            ['mobile' => '123']
        ], $result);
    }

    public function testList()
    {
        $model = new TestModel();

        $model->del('test_key_7', [
            'id' => 1,
        ]);

        $result = $model->lPush('test_key_7', [
            'id' => 1,
        ], 1, 1);

        self::assertEquals(1, $result);

        $result = $model->lPush('test_key_7', [
            'id' => 1,
        ], 2, 0);

        self::assertEquals(2, $result);

        $result = $model->lLen('test_key_7', [
            'id' => 1,
        ]);

        self::assertEquals(2, $result);

        $result = $model->lRange('test_key_7', [
            'id' => 1,
        ], 0, 1);
        self::assertEquals([1, 2], $result);

        $result = $model->lPop('test_key_7', [
            'id' => 1,
        ], 1);

        self::assertEquals(1, $result);

        $result = $model->lPop('test_key_7', [
            'id' => 1,
        ], 0);

        self::assertEquals(2, $result);

        $result = $model->mlPush('test_key_7', [
            'id' => 1,
        ], [1 , 2], 1);
        self::assertEquals(2, $result);

        $result = $model->mlPush('test_key_7', [
            'id' => 1,
        ], [1 , 2], 0);
        self::assertEquals(4, $result);

        $result = $model->lRemove('test_key_7', [
            'id' => 1,
        ], 1, 2);
        self::assertEquals(2, $result);

        $result = $model->mlPop('test_key_7', [
            'id' => 1,
        ], 1, 0);
        self::assertEquals([2], $result);

        $result = $model->mlPop('test_key_7', [
            'id' => 1,
        ], 1, 1);
        self::assertEquals([2], $result);

        $result = $model->mlSize('test_key_7', [
            ['id' => 1]
        ]);
        self::assertEquals([0], $result);
    }

    public function testSet()
    {
        $model = TestModel::factory();
        $model = TestModel::factory();

        $model->del('test_key_4', [
            'id' => 1,
        ]);

        $result = $model->sAdd('test_key_4', [
            'id' => 1
        ], 1);
        self::assertEquals(1, $result);

        $result = $model->sAdd('test_key_4', [
            'id' => 1
        ], 2);
        self::assertEquals(1, $result);

        $result = $model->sIsMember('test_key_4', [
            'id' => 1
        ], [2]);
        self::assertEquals([1], $result);

        $result = $model->sMembers('test_key_4', [
            'id' => 1
        ]);
        self::assertEquals([1, 2], $result);

        $result = $model->sRemove('test_key_4', [
            'id' => 1
        ], 2);
        self::assertEquals(1, $result);
    }

    public function testGetOrSet()
    {
        $model = new TestModel();
        $model->del('test_key_1', [
            'id' => 1,
        ]);

        $result = $model->getOrSet('test_key_1', [
            'id' => 1,
        ], function ($params) {
            return 10;
        }, 10);

        self::assertEquals(10, $result);

        $result = $model->getOrSet('test_key_1', [
            'id' => 1,
        ], function ($params) {
            return 11;
        }, 10);

        self::assertEquals(10, $result);

        $result = $model->setExpire('test_key_1', [
            'id' => 1,
        ], 10);
        self::assertEquals(1, $result);

        $model->setTimeOut('test_key_1', 3);
    }
}