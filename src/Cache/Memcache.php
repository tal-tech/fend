<?php
namespace Fend\Cache;

use Fend\Config;
use Fend\Exception\SystemException;

/**
 * Fend Framework
 * 缓存
 * */
class Memcache extends \Fend\Fend
{
    const PACK_TYPE_JSON = 1;

    const PACK_TYPE_SERIALIZE = 2;


    public $mc = '';                    //连接成功时的标识
    public $_pre = '';                  //标识

    private $_config_tag = array();

    private $pack_type;

    /**
     * 预留方法 扩展使用
     *
     * @param int $t
     * @param string $hash
     * @return mixed
     * @throws \Exception
     */
    public static function Factory($t=0, $hash='')
    {
        static $mcs = array();

        if (empty($mcs[$t])) {
            $mcs[$t] = new self($t, $hash);
        }


        return $mcs[$t];
    }

    /**
     * 初始化对象
     *
     * @param $t
     * @param string $hash
     * @throws \Exception
     */
    private function __construct($t, $hash = '')
    {
        $config = Config::get('Memcache');

        if(!isset($config[$t])) {
           throw new SystemException('memcahce config Not Found :'. $t);
        }
        $config = $config[$t];

        //默认使用json序列化方式
        $this->pack_type = $config['pack_type'] ?? self::PACK_TYPE_JSON;

        $this->mc = new \memcached($config["persistent_id"] ?? '');
        $this->_pre = $config['prefix'] ?? '';

        //创建对比数组
        $cmp = array();

        foreach ($config["hosts"] as $v) {
            $key       = $v["host"] . "_" . $v["port"];
            $cmp[$key] = 1;
        }

        //如果对比数组有不同或config没有设置，那么重置连接
        //为了兼容fpm下不重启更新config问题
        //由于memcached维持长连接导致addserver重复执行问题
        //这里也要做下config变更更新问题
        if (count(array_diff_assoc($cmp, $this->_config_tag)) || (!empty($config) && count($this->mc->getServerList()) == 0)) {
            $this->_config_tag = array();
            $this->mc->resetServerList();
            foreach ($config["hosts"] as $v) {
                $key                     = $v["host"] . "_" . $v["port"];
                $this->_config_tag[$key] = 1;
                $this->mc->addServer($v['host'], $v['port'], $v["weight"] ?? 0);
            }
        }
    }

    public function getObj()
    {
        return $this->mc;
    }

    /**
     * 缓存模板函数
     * 有效减少重复代码
     * ```
     * $data = $cache->getOrSet('key', function() {
     *   // 查询数据库或其他操作...
     *   return ['userId' => 1];
     * }, 3600);
     * var_dump($data); // ['userId' => 1]
     * ```
     * @param string $key 缓存key
     * @param callable $callable 缓存失效时调用的函数，并将该函数返回值进行缓存
     * @param int $expire
     * @return mixed|string
     * @throws SystemException
     * @author xialeistudio
     * @date 2019-09-05
     */
    public function getOrSet($key, callable $callable, $expire = 0)
    {
        $data = $this->get($key);
        if ($data) {
            return $data;
        }

        $value = call_user_func($callable);
        $this->set($key, $value, $expire);
        return $value;
    }

    /**
     * 设置数据缓存
     * 与add|replace比较类似
     * 唯一的区别是: 无论key是否存在,是否过期都重新写入数据
     *
     * @param string $key 数据的标识
     * @param string $value 实体内容
     * @param int $expire 过期时间[天d|周w|小时h|分钟i] 如:8d=8天 默认为0永不过期
     * @return bool
     * @throws SystemException
     */
    public function set($key, $value, $expire = 0)
    {
        $expire = $this->setLifeTime($expire);
        return  $this->mc->set($this->_pre . $key, $this->pack($value), $expire);
    }

    /**
     * 获取数据缓存
     *
     * @param string $key 数据的标识
     * @return string
     * @throws SystemException
     */
    public function get($key)
    {
        $value = $this->mc->get($this->_pre . $key);
        return $this->unpack($value);
    }


    /**
     * 删除一个数据缓存
     *
     * @param string $key 数据的标识
     * @return bool
     */
    public function del($key)
    {
        return $this->mc->delete($this->_pre . $key);
    }

    /**
     * 格式化过期时间
     * 注意: 限制时间小于2592000=30天内
     *
     * @param  string $t 要处理的串
     * @return int
     *
     */
    protected function setLifeTime($t)
    {
        $t = empty($t)?86400:$t;
        if (!is_numeric($t)) {
            switch (substr($t, -1)) {
                case 'w'://周
                    $t = (int) $t * 7 * 24 * 3600;
                    break;
                case 'd'://天
                    $t = (int) $t * 24 * 3600;
                    break;
                case 'h'://小时
                    $t = (int) $t * 3600;
                    break;
                case 'i'://分钟
                    $t = (int) $t * 60;
                    break;
                default:
                    $t = (int) $t;
                    break;
            }
        }
        $t > 2592000 && $t = 2592000;
        //if($t>2592000) self::showMsg('Memcached Backend has a Limit of 30 days (2592000 seconds) for the LifeTime');
        return $t;
    }

    /**
     * @param $value
     * @return false|string
     * @throws SystemException
     */
    protected function pack($value)
    {
        if (is_object($value) || is_array($value)) {
            switch ($this->pack_type) {
                case self::PACK_TYPE_JSON: {
                    return json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                }
                case self::PACK_TYPE_SERIALIZE: {
                    return serialize($value);
                }
                default: {
                    throw new SystemException('invalid pack type: '. $this->pack_type);
                }
            }
        }
        return $value;
    }

    /**
     * @param $value
     * @return mixed
     * @throws SystemException
     */
    protected function unpack($value)
    {
        switch ($this->pack_type) {
            case self::PACK_TYPE_JSON: {
                $output = json_decode($value, true);
                break;
            }
            case self::PACK_TYPE_SERIALIZE: {
                $output = unserialize($value);
                break;
            }
            default: {
                throw new SystemException('invalid pack type: '. $this->pack_type);
            }
        }
        return ($output === false || $output === NULL) ? $value : $output;
    }

    /**
     * @param int $pack_type
     */
    public function setPackType(int $pack_type): void
    {
        $this->pack_type = $pack_type;
    }
}
