<?php


namespace Fend\Redis;


use Fend\Cache;
use Fend\Config;

class RedisModel
{
    /**
     * @var array
     */
    protected static $instance = [];

    /**
     * @return static
     * @throws \Exception
     */
    public static function factory()
    {
        $class = static::class;
        if (!isset(static::$instance[$class])) {
            static::$instance[$class] = new static();
        }
        return static::$instance[$class];
    }

    protected static $KEY_MAP = [];

    /**
     * @var int
     */
    protected $dbType = Cache::CACHE_TYPE_REDIS;

    /**
     * @var string
     */
    protected $configName = '';

    /**
     * json_decode返回obj还是array
     * 由于golang对类型要求，这里特别增加此选项
     * @var bool $decode_assoc
     */
    protected $decode_assoc = true;

    /**
     * @var
     */
    private $config;

    /**
     * @var string
     */
    private $path = '';

    /**
     * RedisModel constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $config = Config::get('Redis');
        if (!isset($config['key_map_path'])) {
            throw new \Exception('key_map_path must set');
        }

        $this->path = $config['key_map_path'];

        if (!isset(self::$KEY_MAP[$this->configName])) {
            $keys = explode('.', $this->configName);

            $count = count($keys);

            $file = $this->path;
            $i = 0;
            while ($i < $count) {
                $file .= ('/' . $keys[$i]);
                $i++;
            }

            if (!file_exists($file . '.php')) {
                throw new \Exception("{$file} not exists");
            }
            self::$KEY_MAP[$this->configName] = include "{$file}.php";
        }

        $this->config = self::$KEY_MAP[$this->configName];
    }

    /**
     * 判断key是否存在，只支持单key
     *
     * @param string $key
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function exist(string $key, array $params)
    {
        $type = ['string', 'incr', 'hash', 'zset', 'list', 'set'];

        $dataKey = $this->generateKey($key, $params, $type);

        return $this->getDb($key)->exists($dataKey);
    }


    /**
     * 删除数据，支持多key
     * @param string $key
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function del(string $key, array $params)
    {
        $type = ['string', 'incr', 'hash', 'zset', 'list', 'set'];

        if (count($params) == count($params, 1)) {
            $params = [$params];
        }

        $dataKeys = [];
        foreach ($params as $param) {
            $dataKeys[] = $this->generateKey($key, $param, $type);
        }

        $result = $this->getDb($key)->del($dataKeys);
        return $result;
    }

    /**
     * key的值自增，默认+1，只允许单键操作
     *
     * @param string $key
     * @param array $params
     * @param int $num
     * @return float|int
     * @throws \Exception
     */
    public function incr(string $key, array $params, $num = 1)
    {
        $type = ['incr'];

        $dataKey = $this->generateKey($key, $params, $type);

        if (is_float($num)) {
            $result = $this->getDb($key)->incrByFloat($dataKey, $num);
        } else {
            $result = $this->getDb($key)->incrBy($dataKey, $num);
        }

        return $result;
    }

    /**
     * key的值自减，默认-1，只允许单键操作，int型
     *
     * @param string $key
     * @param array $params
     * @param int $num
     * @return int
     * @throws \Exception
     */
    public function decr(string $key, array $params, $num = 1)
    {
        $type = ['incr'];

        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->decrBy($dataKey, $num);
        return $result;
    }

    /**
     * 获取key的值，字符串型，允许多key操作
     *
     * @param string $key
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function get(string $key, array $params)
    {
        $type = ['string', 'incr'];

        if (count($params) == count($params, 1)) {
            $params = [$params];
        }

        $dataKeys = [];
        foreach ($params as $param) {
            $dataKeys[] = $this->generateKey($key, $param, $type);
        }

        $result = $this->getDb($key)->mGet($dataKeys);

        $config = $this->config[$key];

        $data = [];
        $keyList = array_keys($params);
        foreach ($keyList as $index => $dataKey) {
            $val = $result[$index];
            if ($config['type'] == 'string') {
                $val = json_decode($val, $this->decode_assoc);
            }
            $data[$dataKey] = $val;
        }
        return $data;
    }

    /**
     * 写入key-value
     *
     * @param string $key
     * @param array $params
     * @param mixed $value
     * @param int|array $timeout [optional] Calling setex() is preferred if you want a timeout.<br>
     * Since 2.6.12 it also supports different flags inside an array. Example ['NX', 'EX' => 60]<br>
     *  - EX seconds -- Set the specified expire time, in seconds.<br>
     *  - PX milliseconds -- Set the specified expire time, in milliseconds.<br>
     *  - PX milliseconds -- Set the specified expire time, in milliseconds.<br>
     *  - NX -- Only set the key if it does not already exist.<br>
     *  - XX -- Only set the key if it already exist.<br>
     * <pre>
     * // Simple key -> value set
     * $redis->set('key', 'value');
     *
     * // Will redirect, and actually make an SETEX call
     * $redis->set('key','value', 10);
     *
     * // Will set the key, if it doesn't exist, with a ttl of 10 seconds
     * $redis->set('key', 'value', ['nx', 'ex' => 10]);
     *
     * // Will set a key, if it does exist, with a ttl of 1000 miliseconds
     * $redis->set('key', 'value', ['xx', 'px' => 1000]);
     * </pre>
     * @return bool
     * @throws \Exception
     */
    public function set(string $key, array $params, $value, $timeout = null)
    {
        $type = ['string', 'incr'];

        $dataKey = $this->generateKey($key, $params, $type);

        $config = $this->config[$key];
        if ($config['type'] == 'string') {
            $value = json_encode($value);
        }

        if ($timeout === null) {
            $result = $this->getDb($key)->set($dataKey, $value);
        } else {
            $result = $this->getDb($key)->set($dataKey, $value, $timeout);
        }
        return $result;
    }

    /**
     * 同时给多个Key赋值
     * @param string $key
     * @param array $params
     * @param array $value
     * @param int $seconds
     * @return array
     * @throws \Exception
     */
    public function mset(string $key, array $params, array $value, $seconds = 0)
    {

        $type = ['string', 'incr'];

        $data = [];
        foreach ($params as $index => $param) {
            $dataKey = $this->generateKey($key, $param, $type);
            $val = $value[$index];

            $config = $this->config[$key];
            if ($config['type'] == 'string') {
                $val = json_encode($val);
            }
            $data[$dataKey] = $val;
        }


        if (empty($seconds)) {
            $result = $this->getDb($key)->mset($data);
        } else {
            $result = array();
            foreach ($data as $dataKey => $value) {
                $result[] = $this->getDb($key)->setex($dataKey, $seconds, $value);
            }
        }

        return $result;
    }

    /**
     * string型，不存在，写入并返回true，否则不写入并返回false
     * @param string $key
     * @param array $params
     * @param $value
     * @param int $seconds
     * @return bool
     * @throws \Exception
     */
    public function setnx(string $key, array $params, $value, $seconds = 0)
    {
        $type = ['string'];

        $dataKey = $this->generateKey($key, $params, $type);

        if (empty($seconds)) {
            return $this->getDb($key)->set($dataKey, json_encode($value), ['nx']);
        } else {
            return $this->getDb($key)->set($dataKey, json_encode($value), ['nx', 'ex' => $seconds]);
        }
    }

    /**
     * 有序集合，批量数据写入
     * @param string $key
     * @param array $data
     * @return array|bool
     * @throws \Exception
     */
    public function mzAdd(string $key, $data = array())
    {
        $type = ['zset'];

        //开启管道
        $this->getDb($key)->multi(\Redis::PIPELINE);

        foreach ($data as $val) {
            if (!isset($val['params']) || !isset($val['value']) || !isset($val['score']) || !is_numeric($val['score'])) {
                $this->getDb($key)->discard();
                return false;
            }

            $dataKey = $this->generateKey($key, $val['params'], $type);
            $this->getDb($key)->zAdd($dataKey, [], $val['score'], $val['value']);
        }

        $result = $this->getDb($key)->exec();
        return $result;
    }

    /**
     * 有序集合，单数据写入关系
     *
     * @param string $key
     * @param array $params
     * @param mixed $score
     * @param mixed $value
     * @return bool|int
     * @throws \Exception
     */
    public function zAdd(string $key, array $params, $score, $value)
    {
        if (!is_numeric($score)) {
            return false;
        }

        $type = ['zset'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->zAdd($dataKey, [], $score, $value);
        return $result;
    }

    /**
     * 有序集合，单key下所有元素的总数，支持多key
     *
     * @param string $key
     * @param array $params
     * @param int $idKey 当ids是数组时,是否由id作为返回key
     * @return array|int
     * @throws \Exception
     */
    public function zCard(string $key, array $params, $idKey = 0)
    {
        $type = ['zset'];

        if (count($params) != count($params, 1)) {
            //开启管道
            $this->getDb($key)->multi(\Redis::PIPELINE);

            foreach ($params as $param) {
                $dataKey = $this->generateKey($key, $param, $type);

                $this->getDb($key)->zCard($dataKey);
            }

            $result = $this->getDb($key)->exec();

            //id作为key
            if ($idKey == 1) {
                $_data = $result;

                $result = [];
                $keyList = array_keys($params);
                foreach ($keyList as $index => $dataKey) {
                    $result[$dataKey] = $_data[$index];
                }
            }
        } else {
            $dataKey = $this->generateKey($key, $params, $type);
            $result = $this->getDb($key)->zCard($dataKey);
        }
        return $result;
    }

    /**
     * 有序集合，单key下的元素总数，支持多key
     *
     * @param string $key
     * @param array $params
     * @param string $start
     * @param string $end
     * @param int $idKey 当ids是数组时,是否由id作为返回key
     * @return array|int
     * @throws \Exception
     */
    public function zCount(string $key, array $params, $start = '-inf', $end = '+inf', $idKey = 0)
    {

        $type = ['zset'];

        if (count($params) != count($params, 1)) {
            //开启管道
            $this->getDb($key)->multi(\Redis::PIPELINE);

            foreach ($params as $param) {
                $dataKey = $this->generateKey($key, $param, $type);
                $this->getDb($key)->zCount($dataKey, $start, $end);
            }

            $result = $this->getDb($key)->exec();

            //id作为key
            if ($idKey == 1) {
                $_data = $result;
                $result = [];

                $keyList = array_keys($params);
                foreach ($keyList as $index => $dataKey) {
                    $result[$dataKey] = $_data[$index];
                }
            }
        } else {
            $dataKey = $this->generateKey($key, $params, $type);
            $result = $this->getDb($key)->zCount($dataKey, $start, $end);
        }

        return $result;
    }

    /**
     * 有序集合key的自增
     *
     * @param string $key
     * @param array $params
     * @param mixed $score
     * @param mixed $value
     * @return boolean
     * @throws \Exception
     */
    public function zIncrBy(string $key, array $params, $score, $value)
    {

        if (!is_numeric($score)) {
            return false;
        }

        $type = ['zset'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->zIncrBy($dataKey, $score, $value);
        return $result;
    }

    /**
     * 有序集合，单key下，某元素的排序
     * sort排序：1从小到大，2从大到小
     *
     * @param string $key
     * @param array $params
     * @param mixed $value
     * @param int $sort
     * @return int
     * @throws \Exception
     */
    public function zRank(string $key, array $params, $value, $sort = 1)
    {
        $type = ['zset'];
        $dataKey = $this->generateKey($key, $params, $type);

        if ($sort == 1) {
            $result = $this->getDb($key)->zRank($dataKey, $value);
        } else {
            $result = $this->getDb($key)->zRevRank($dataKey, $value);
        }

        return $result;
    }

    /**
     * 有序集合，单数据移除关系
     *
     * @param string|array $key
     * @param array $params
     * @param mixed $value
     * @return int
     * @throws \Exception
     */
    public function zRem(string $key, array $params, $value)
    {
        $type = ['zset'];
        $dataKey = $this->generateKey($key, $params, $type);

        if (is_array($value)) {
            return $this->getDb($key)->zRem($dataKey, ...$value);
        }

        return $this->getDb($key)->zRem($dataKey, $value);
    }

    /**
     * 有序集合，单数据移除关系 指定rank区间
     *
     * @param string $key
     * @param array $params
     * @param mixed $start
     * @param mixed $end
     * @return int
     * @throws \Exception
     */
    public function zRemRangeByRank(string $key, array $params, $start, $end)
    {

        $type = ['zset'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->zRemRangeByRank($dataKey, $start, $end);
        return $result;
    }

    /**
     * 有序集合，单数据移除关系 根据score的区间删除 删除单个 scorestart ～ scoreend即可
     *
     * @param string $key
     * @param array $params
     * @param mixed $start
     * @param mixed $end
     * @return int
     * @throws \Exception
     */
    public function zRemRangeByScore(string $key, array $params, $start, $end)
    {

        $type = ['zset'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->zRemRangeByScore($dataKey, $start, $end);
        return $result;
    }

    /**
     * 有序集合，单key下，某元素的score值
     *
     * @param string $key
     * @param array $params
     * @param mixed $value
     * @return float
     * @throws \Exception
     */
    public function zScore(string $key, array $params, $value)
    {

        $type = ['zset'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->zScore($dataKey, $value);
        return $result;
    }

    /**
     * 有序集合，单key下，指定区间内的，返回成员值，支持多key
     * sort排序：1从小到大，2从大到小
     *
     * @param string $key
     * @param array $params
     * @param int $start
     * @param int $end
     * @param int $sort
     * @param bool $withscores
     * @param int $idKey 当ids是数组时，是否由id作为返回key
     * @return array
     * @throws \Exception
     */
    public function zRange(
        string $key,
        array $params,
        $start = 0,
        $end = -1,
        $sort = 1,
        $withscores = false,
        $idKey = 0
    ) {

        $type = ['zset'];

        if (count($params) != count($params, 1)) {
            //开启管道
            $this->getDb($key)->multi(\Redis::PIPELINE);

            foreach ($params as $param) {
                $dataKey = $this->generateKey($key, $param, $type);

                if ($sort == 1) {
                    $this->getDb($key)->zRange($dataKey, $start, $end, $withscores);
                } else {
                    $this->getDb($key)->zRevRange($dataKey, $start, $end, $withscores);
                }
            }

            $result = $this->getDb($key)->exec();

            //id作为key
            if ($idKey == 1) {
                $_data = $result;
                $result = [];

                $keyList = array_keys($params);
                foreach ($keyList as $index => $dataKey) {
                    $result[$dataKey] = $_data[$index];
                }
            }
        } else {
            $dataKey = $this->generateKey($key, $params, $type);

            if ($sort == 1) {
                $result = $this->getDb($key)->zRange($dataKey, $start, $end, $withscores);
            } else {
                $result = $this->getDb($key)->zRevRange($dataKey, $start, $end, $withscores);
            }
        }

        return $result;
    }

    /**
     * 有序集合，单key下，指定区间内的，返回成员值，支持多key
     *
     * @param string $key
     * @param array $params
     * @param array $options
     * @param int $idKey
     * @return array
     * @throws \Exception
     */
    public function mZRange(string $key, array $params, $options = array(), $idKey = 1)
    {

        $start = !empty($options['start']) ? $options['start'] : 0;
        $end = !empty($options['end']) ? $options['end'] : -1;
        $sort = !empty($options['sort']) ? $options['sort'] : 1;
        $withscores = !empty($options['withscores']) ? $options['withscores'] : false;

        $result = $this->zRange($key, $params, $start, $end, $sort, $withscores, $idKey);
        return $result;
    }

    /**
     * 有序集合，单key下，score在指定区间内的，返回成员值，支持多key
     * sort排序：1从小到大，2从大到小
     *
     * @param string $key
     * @param array $params
     * @param int $start
     * @param int $end
     * @param int $sort
     * @param bool $withscores
     * @param array $limit
     * @param int $idKey 当ids是数组时,是否由id作为返回key
     * @return array
     * @throws \Exception
     */
    public function zRangeByScore(
        string $key,
        array $params,
        $start = 0,
        $end = 1,
        $sort = 1,
        $withscores = false,
        $limit = array(),
        $idKey = 0
    ) {

        $type = ['zset'];
        $arr['withscores'] = $withscores;
        $arr['limit'] = $limit;

        if (count($params) != count($params, 1)) {
            //开启管道
            $this->getDb($key)->multi(\Redis::PIPELINE);

            foreach ($params as $param) {
                $dataKey = $this->generateKey($key, $param, $type);

                if ($sort == 1) {
                    $this->getDb($key)->zRangeByScore($dataKey, $start, $end, $arr);
                } else {
                    $this->getDb($key)->zRevRangeByScore($dataKey, $start, $end, $arr);
                }
            }

            $result = $this->getDb($key)->exec();

            //id作为key
            if ($idKey == 1) {
                $_data = $result;
                $result = [];

                $keyList = array_keys($params);
                foreach ($keyList as $index => $dataKey) {
                    $result[$dataKey] = $_data[$index];
                }
            }
        } else {
            $dataKey = $this->generateKey($key, $params, $type);

            if ($sort == 1) {
                $result = $this->getDb($key)->zRangeByScore($dataKey, $start, $end, $arr);
            } else {
                $result = $this->getDb($key)->zRevRangeByScore($dataKey, $start, $end, $arr);
            }
        }

        return $result;
    }

    /**
     * 有序集合，单key下，score在指定区间内的，返回成员值，支持多key
     *
     * @param string $key
     * @param array $params
     * @param array $options
     * @param int $idKey
     * @return array
     * @throws \Exception
     */
    public function mZRangeByScore(string $key, array $params, $options = array(), $idKey = 1)
    {

        $start = !empty($options['start']) ? $options['start'] : 0;
        $end = !empty($options['end']) ? $options['end'] : -1;
        $sort = !empty($options['sort']) ? $options['sort'] : 1;
        $withscores = !empty($options['withscores']) ? $options['withscores'] : false;
        $limit = !empty($options['limit']) ? $options['limit'] : [];

        $result = $this->zRangeByScore($key, $params, $start, $end, $sort, $withscores, $limit, $idKey);
        return $result;
    }

    /**
     * 有序集合，交集/并集
     * @param string $key
     * @param array $params
     * @param array $keys
     * @param array $weights
     * @param string $aggregate
     * @param int $sort
     * @return int
     * @throws \Exception
     */
    public function zInters(
        string $key,
        array $params,
        $keys = array(),
        $weights = array(),
        $aggregate = 'SUM',
        $sort = 1
    ) {

        $type = ['zset'];
        $dataKey = $this->generateKey($key, $params, $type);

        if ($sort == 1) {
            $result = $this->getDb($key)->zInterStore($dataKey, $keys, $weights, $aggregate);
        } else {
            $result = $this->getDb($key)->zUnionStore($dataKey, $keys, $weights, $aggregate);
        }

        return $result;
    }

    /**
     * 存储hash类型的数据，多条
     *
     * @param string $key
     * @param array $params
     * @param array $values
     * @param bool $ignoreFieldCheck
     * @return array
     * @throws \Exception
     */
    public function mhSet(string $key, array $params, array $values, bool $ignoreFieldCheck = false)
    {
        $type = ['hash'];

        //开启管道
        $this->getDb($key)->multi(\Redis::PIPELINE);

        $config = $this->config[$key];
        foreach ($params as $index => $param) {
            $dataKey = $this->generateKey($key, $param, $type);
            $val = $values[$index];

            //需要更新的缓存数据
            $dataVal = [];

            if ($ignoreFieldCheck) {
                $dataVal = $val;
            } else {
                foreach ($config['field_list'] as $field) {
                    if (isset($val[$field])) {
                        $dataVal[$field] = $val[$field];
                    }
                }
            }

            //更新
            $this->getDb($key)->hMSet($dataKey, $dataVal);
        }

        $result = $this->getDb($key)->exec();
        return $result;
    }

    /**
     * 存储hash类型的数据
     *
     * @param string $key
     * @param array $params
     * @param array $dataSet
     * @param bool $ignoreFieldCheck
     * @return bool
     * @throws \Exception
     */
    public function hSet(string $key, array $params, $dataSet, bool $ignoreFieldCheck = false)
    {
        $type = ['hash'];
        $dataKey = $this->generateKey($key, $params, $type);

        $config = $this->config[$key];
        //需要更新的缓存数据
        $data = [];
        if ($ignoreFieldCheck) {
            $data = $dataSet;
        } else {
            foreach ($config['field_list'] as $field) {
                if (isset($dataSet[$field])) {
                    $data[$field] = $dataSet[$field];
                }
            }
        }


        //更新缓存
        $result = $this->getDb($key)->hMSet($dataKey, $data);
        return $result;
    }

    /**
     * 获取hash类型的数据，支持多条，可以根据条件获取，条件为空时，获取该key的所有数据
     *
     * @param string $key
     * @param array $params
     * @param array $fields
     * @param int $idKey 当ids是数组时,是否由id作为返回key
     * @param bool $ignoreFieldCheck
     * @return array
     * @throws \Exception
     */
    public function hGet(string $key, array $params, $fields = array(), $idKey = 0, bool $ignoreFieldCheck = false)
    {
        $type = ['hash'];
        $this->getDb($key);

        $config = $this->config[$key];
        if (!empty($fields) && !$ignoreFieldCheck) {
            $fields = array_intersect($fields, $config['field_list']);
        }

        if (count($params) != count($params, 1)) {
            //开启管道
            $this->getDb($key)->multi(\Redis::PIPELINE);

            foreach ($params as $param) {
                $dataKey = $this->generateKey($key, $param, $type);

                if (!empty($fields)) {
                    $this->getDb($key)->hMGet($dataKey, $fields);
                } else {
                    $this->getDb($key)->hGetAll($dataKey);
                }
            }

            $result = $this->getDb($key)->exec();

            //id作为key
            if ($idKey == 1) {
                $_data = $result;
                $result = [];

                $keyList = array_keys($params);
                foreach ($keyList as $index => $dataKey) {
                    $result[$dataKey] = $_data[$index];
                }
            }
        } else {
            $dataKey = $this->generateKey($key, $params, $type);
            if (!empty($fields)) {
                $result = $this->getDb($key)->hMGet($dataKey, $fields);
            } else {
                $result = $this->getDb($key)->hGetAll($dataKey);
            }
        }

        return $result;
    }

    /**
     * 删除hash类型的数据
     *
     * @param string $key
     * @param array $params
     * @param string|array $value
     * @return bool
     * @throws \Exception
     */
    public function hDel(string $key, array $params, $value = '')
    {
        $type = ['hash'];
        $dataKey = $this->generateKey($key, $params, $type);

        //更新缓存
        if (is_array($value)) {
            $result = $this->getDb($key)->hDel($dataKey, ...$value);
        } else {
            $result = $this->getDb($key)->hDel($dataKey, $value);
        }
        return $result;
    }

    /**
     * redis hincrby
     *
     * @param string $key
     * @param array $params
     * @param mixed $field
     * @param mixed $value
     * @param bool $ignoreFieldCheck
     * @return bool|int
     * @throws \Exception
     */
    public function hIncrBy(string $key, array $params, $field, $value, bool $ignoreFieldCheck = false)
    {
        $type = ['hash'];
        $dataKey = $this->generateKey($key, $params, $type);

        $config = $this->config[$key];
        if (!$ignoreFieldCheck && !in_array($field, $config['field_list'])) {
            return false;
        }

        $result = $this->getDb($key)->hIncrBy($dataKey, $field, $value);
        return $result;
    }

    /**
     * LIST push操作
     *
     * @param string $key
     * @param array $params
     * @param mixed $value
     * @param int $sort
     * @return bool|int
     * @throws \Exception
     */
    public function lPush(string $key, array $params, $value, $sort = 1)
    {

        $type = ['list'];
        $dataKey = $this->generateKey($key, $params, $type);

        if ($sort == 1) {
            $result = $this->getDb($key)->lPush($dataKey, $value);
        } else {
            $result = $this->getDb($key)->rPush($dataKey, $value);
        }

        return $result;
    }

    /**
     * LIST pop操作
     *
     * @param string $key
     * @param array $params
     * @param int $sort
     * @return string
     * @throws \Exception
     */
    public function lPop(string $key, array $params, $sort = 1)
    {

        $type = ['list'];
        $dataKey = $this->generateKey($key, $params, $type);

        if ($sort == 1) {
            $result = $this->getDb($key)->lPop($dataKey);
        } else {
            $result = $this->getDb($key)->rPop($dataKey);
        }

        return $result;
    }

    /**
     * LIST size操作
     *
     * @param string $key
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function lSize(string $key, array $params)
    {

        $type = ['list'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->lLen($dataKey);
        return $result;
    }

    /**
     * LIST size操作
     * 单元测试会提示函数已经淘汰，这里过渡下
     * @param string $key
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function lLen(string $key, array $params)
    {
        $type = ['list'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->lLen($dataKey);
        return $result;
    }

    /**
     * LIST lRange操作
     *
     * @param string $key
     * @param array $params
     * @param int $start $end
     * @param int $end
     * @return mixed
     * @throws \Exception
     */
    public function lRange(string $key, array $params, $start = 0, $end = -1)
    {
        $type = ['list'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->lRange($dataKey, $start, $end);
        return $result;
    }

    /**
     * LIST lRemove操作
     *
     * @param string $key
     * @param array $params
     * @param string $value
     * @param int $count
     * @return int
     * @throws \Exception
     */
    public function lRemove(string $key, array $params, $value = '', $count = 0)
    {
        $type = ['list'];

        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->lRem($dataKey, $value, $count);
        return $result;
    }

    /**
     * LIST push多条
     *
     * @param string $key
     * @param array $params
     * @param array $value
     * @param int $sort
     * @return bool|int
     * @throws \Exception
     */
    public function mlPush(string $key, array $params, $value = array(), $sort = 1)
    {

        $type = ['list'];
        $dataKey = $this->generateKey($key, $params, $type);

        if ($sort == 1) {
            //lpush($key, $val1,$val2,$val3...); lpush本身就支持多个val的添加。
            //...的语法，相当于把数组变成了，多个参数。
            $result = $this->getDb($key)->lPush($dataKey, ...$value);
        } else {
            $result = $this->getDb($key)->rPush($dataKey, ...$value);
        }

        return $result;
    }

    /**
     * LIST pop多条
     *
     * @param string $key
     * @param array $params
     * @param int $num
     * @param int $sort
     * @return array
     * @throws \Exception
     */
    public function mlPop(string $key, array $params, $num = 1, $sort = 1)
    {

        $type = ['list'];
        $dataKey = $this->generateKey($key, $params, $type);

        //开启管道
        $this->getDb($key)->multi(\Redis::PIPELINE);

        for ($i = 0; $i < $num; $i++) {
            if ($sort == 1) {
                $this->getDb($key)->lPop($dataKey);
            } else {
                $this->getDb($key)->rPop($dataKey);
            }
        }

        $result = $this->getDb($key)->exec();
        return $result;
    }

    /**
     * LIST size操作多条
     *
     * @param string $key
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function mlSize(string $key, array $params)
    {

        $type = ['list'];

        //开启管道
        $this->getDb($key)->multi(\Redis::PIPELINE);

        foreach ($params as $param) {
            $dataKey = $this->generateKey($key, $param, $type);
            $this->getDb($key)->lLen($dataKey);
        }

        $result = $this->getDb($key)->exec();

        $_data = $result;
        $result = [];

        $keyList = array_keys($params);
        foreach ($keyList as $index => $dataKey) {
            $result[$dataKey] = $_data[$index];
        }

        return $result;
    }

    /**
     * 设置过期时间操作
     *
     * @param string $key
     * @param array $params
     * @param int $time
     * @return bool
     * @throws \Exception
     */
    public function setExpire(string $key, array $params, $time = 0)
    {

        $type = ['string', 'incr', 'hash', 'zset', 'set', 'list'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->expire($dataKey, $time);
        return $result;
    }

    /**
     * 发布消息操作
     *
     * @param string $key
     * @param mixed $channel
     * @param mixed $message
     * @return int
     * @throws \Exception
     */
    public function publish(string $key, $channel, $message)
    {

        $result = $this->getDb($key)->publish($channel, $message);
        return $result;
    }

    /**
     * 订阅消息操作
     *
     * @param string $key
     * @param mixed $channel
     * @param $callback
     * @return void
     * @throws \Exception
     */
    public function subscribe(string $key, $channel, $callback)
    {

        $this->getDb($key)->subscribe($channel, $callback);
    }

    /**
     * 设置超时时间
     *
     * @param string $key
     * @param int $time
     * @return void
     * @throws \Exception
     */
    public function setTimeOut(string $key, $time = -1)
    {
        $this->getDb($key)->setOption(\Redis::OPT_READ_TIMEOUT, $time);
    }

    /**
     * SET sMembers
     *
     * @param string $key
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function sMembers(string $key, array $params)
    {

        $type = ['set'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->sMembers($dataKey);
        return $result;
    }

    /**
     * 判断元素是否集合的成员
     * @param string $key
     * @param array $params
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function sIsMember(string $key, array $params, $data = [])
    {
        $type = ['set'];

        //开启管道
        $this->getDb($key)->multi(\Redis::PIPELINE);

        foreach ($data as $val) {
            $dataKey = $this->generateKey($key, $params, $type);
            $this->getDb($key)->sIsMember($dataKey, $val);
        }

        $result = $this->getDb($key)->exec();
        $result = array_combine(array_keys($data), $result);

        return $result;
    }

    /**
     * SET sAdd
     *
     * @param string $key
     * @param array $params
     * @param string|array $value
     * @return int
     * @throws \Exception
     */
    public function sAdd(string $key, array $params, $value = '')
    {

        $type = ['set'];
        $dataKey = $this->generateKey($key, $params, $type);

        if (is_array($value)) {
            $result = $this->getDb($key)->sAdd($dataKey, ...$value);
        } else {
            $result = $this->getDb($key)->sAdd($dataKey, $value);
        }
        return $result;
    }

    /**
     * SET sRemove
     *
     * @param string $key
     * @param array $params
     * @param string|array $value
     * @return int
     * @throws \Exception
     */
    public function sRemove(string $key, array $params, $value = '')
    {

        $type = ['set'];
        $dataKey = $this->generateKey($key, $params, $type);

        if (is_array($value)) {
            $result = $this->getDb($key)->sRem($dataKey, ...$value);
        } else {
            $result = $this->getDb($key)->sRem($dataKey, $value);
        }
        return $result;
    }

    /**
     * 随机从set中获取指定个数value，不会删除
     * @param string $key
     * @param array $params
     * @param int|null $count 取出value个数，默认为null
     * @return array|string
     * @throws \Exception
     */
    public function sRandMember(string $key, array $params, int $count = null)
    {
        $type = ['set'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->sRandMember($dataKey, $count);
        return $result;
    }

    /**
     * 返回集合 key 的基数(集合中元素的数量)
     *
     * @param string $key
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function sCard(string $key, array $params)
    {
        $type = ['set'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->sCard($dataKey);
        return $result;
    }

    /**
     * 移除并返回集合中的一个随机元素
     *
     * @param string $key
     * @param array $params
     * @return string|int|null
     * @throws \Exception
     */
    public function sPop(string $key, array $params)
    {
        $type = ['set'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->sPop($dataKey);
        return $result;
    }

    /**
     * 缓存模板函数
     * 有效减少重复代码
     * ```
     * $data = $cache->getOrSet('key', function($params) {
     *   // 查询数据库或其他操作...
     *   return ['userId' => 1];
     * }, 3600);
     * var_dump($data); // ['userId' => 1]
     * ```
     * @param string $key 缓存key
     * @param array $params
     * @param callable $callable 缓存失效时调用的函数，并将该函数返回值进行缓存
     * @param int $expire
     * @return mixed|string
     * @throws \Exception
     * @author xialeistudio
     * @date 2019-09-05
     */
    public function getOrSet(string $key, array $params, callable $callable, $expire = 0)
    {
        $type = ['string'];
        $dataKey = $this->generateKey($key, $params, $type);

        $result = $this->getDb($key)->get($dataKey);
        if ($result === false) {
            $result = call_user_func($callable, $params);
            $this->set($key, $params, $result, $expire);
            return $result;
        }

        $config = $this->config[$key];
        if ($config['type'] == 'string') {
            $result = json_decode($result, $this->decode_assoc);
        }

        return $result;
    }

    /**
     * sAddArray("user_like_key", ['topicId' => $topicId, 'userId' => $userId], $commentIds);
     * @param string $key
     * @param array $params
     * @param array $value
     * @return bool
     * @throws \Exception
     */
    public function sAddArray(string $key, array $params, array $value = [])
    {
        $type = ['set'];
        $dataKey = $this->generateKey($key, $params, $type);
        $result = $this->getDb($key)->sAddArray($dataKey, $value);
        return $result;
    }

    /**
     * @param string $key
     * @param array $params
     * @param array $type
     * @return string
     * @throws \Exception
     */
    public function generateKey(string $key, array $params, array $type)
    {
        $config = $this->config[$key] ?? [];
        if (empty($config)) {
            throw new \Exception("{$key} does not exist in {$this->configName}");
        }

        if (!in_array($config['type'], $type)) {
            throw new \Exception('redis配置type错误');
        }

        foreach ($params as $key => $value) {
            if (!in_array($key, $config['params'])) {
                throw new \Exception("params key {$key} not exist");
            }
        }

        //there is no params on the define
        if (!isset($config["params"]) || count($config["params"]) === 0) {
            return $config['key'];
        }

        //resort
        $fillParams = [];
        foreach ($config["params"] as $paramKey) {
            $fillParams[$paramKey] = $params[$paramKey];
        }
        return vsprintf($config['key'], $fillParams);
    }

    /**
     * @param string $key
     * @return \Redis
     * @throws \Exception
     */
    public function getDb(string $key)
    {
        $config = $this->config[$key] ?? [];
        if (empty($config)) {
            throw new \Exception("{$key} does not exist in {$this->configName}");
        }

        $instance = $config['instance'] ?? 'default';

        return Cache::factory($this->dbType, $instance);
    }
}