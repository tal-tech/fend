<?php


namespace Example\RedisModel\Model\Redis;

use Fend\Cache;
use Fend\Redis\RedisModel;

class TestModel extends RedisModel
{
    protected $configName = 'Test.Test';

    protected $dbType = Cache::CACHE_TYPE_REDIS;
    /**
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function getTestKey(int $id)
    {
        $result = $this->get('test_key_1', [
            'id'    => $id
        ]);
        return $result;
    }

    /**
     * @param array $ids
     * @return array
     * @throws \Exception
     */
    public function mGetTestKey(array $ids)
    {
        $list = [];
        foreach ($ids as $id) {
            $list[] = [
                'id'    => $id,
            ];
        }

        $result = $this->get('test_key_1', $list);
        return $result;
    }

    /**
     * @param int $id
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function setTestKey(int $id, $value)
    {
        $result = $this->set('test_key_1', [
            'id'    => $id
        ], $value);
        return $result;
    }

    /**
     * @param array $ids
     * @return array
     * @throws \Exception
     */
    public function hashGet(array $ids)
    {
        $list = [];
        foreach ($ids as $id) {
            $list[$id] = [
                'id'    => $id
            ];
        }
        $result = $this->hGet('test_key_3', $list, [
            'name',
            'mobile'
        ], 1);
        return $result;
    }

    /**
     * @param int $id
     * @param $dataSet
     * @return bool
     * @throws \Exception
     */
    public function hashSet(int $id, array $dataSet)
    {
        $result = $this->hSet('test_key_3', [
            'id'    => $id
        ], $dataSet);
        return $result;
    }

    /**
     * @param array $ids
     * @return bool
     * @throws \Exception
     */
    public function testZCard(array $ids)
    {
        $list = [];
        foreach ($ids as $id) {
            $list[$id] = [
                'id'    => $id
            ];
        }

        $result = $this->zCard('test_key_3', $list, 1);
        return $result;
    }
}