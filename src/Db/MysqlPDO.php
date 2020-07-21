<?php

namespace Fend\Db;

use Fend\Config;
use Fend\Exception\SystemException;
use Fend\Log\EagleEye;

/**
 * Class MysqlPDO
 * @package Fend\Db
 */
class MysqlPDO extends \Fend\Fend
{
    /**
     * @var \PDO
     */
    protected $_db = null;//连接池标识
    protected $_cfg = null;//连接配置信息

    private $_lastSQL = [];

    private $_instant_name;

    private $_affrow = 0;

    private $_transaction_enabled = false;

    protected $_timeout = 3;

    protected $options = array(
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_ORACLE_NULLS       => \PDO::NULL_NATURAL,
        \PDO::ATTR_CASE               => \PDO::CASE_LOWER,
        \PDO::ATTR_TIMEOUT            => 3,
        \PDO::ATTR_EMULATE_PREPARES   => false,
        \PDO::ATTR_STRINGIFY_FETCHES  => false,
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"
    );

    public static $in = array();

    /**
     * factory get obj
     * @param $r
     * @param string $db
     * @return mixed
     * @throws \Exception
     */
    public static function Factory($r, $db = '')
    {
        //全链路压测时，自动读写影子库
        $dbList = Config::get('Db');
        if (EagleEye::getGrayStatus() && isset($dbList[$db . "-gray"])) {
            $db = $db . "-gray";
        }
        if (isset(self::$in[$db][$r])) {
            return self::$in[$db][$r];
        }
        self::$in[$db][$r] = new self($r, $db);
        return self::$in[$db][$r];
    }

    /**
     * 初始化Mysql对象并进行连接服务器尝试
     * \Fend\Db\MysqlPdo constructor.
     * @param $r
     * @param $db
     * @throws \Exception pdo connect or param wrong exception
     */
    public function __construct($r, $db)
    {
        //record instant name
        $this->_instant_name = $db . "_" . $r;

        $dbConfig = Config::get('Db');

        $config = $dbConfig[$db][$r];
        if (isset($config["type"])) {
            if ($config["type"] === "random") {
                $config = $config["config"][array_rand($config["config"])];
            } else {
                throw new SystemException("SQL Config Type is Wrong!" . $this->_instant_name, -23129);
            }
        }

        //add option
        if (isset($config['options'])) {
            foreach ($config['options'] as $key => $val) {
                $this->options[$key] = $val;
            }
        }

        $this->_cfg = $config;
        $this->_cfg['port'] = empty($this->_cfg['port']) ? 3306 : $this->_cfg['port'];
        $this->_cfg['dsn']  = $this->parseDsn($this->_cfg);

        //retry connect fail
        $this->connect();
    }

    /**
     * 链接数据库,失败重试链接两次
     * 仍旧失败抛异常
     * @return bool
     * @throws \Exception 链接失败
     */
    public function connect()
    {
        //retry on connection
        $retry = 4;

        while ($retry) {
            $startTime = microtime(true);

            try {

                //prepare info of error
                $eagleeye_param = array(
                    "x_name" => "mysql.connect",
                    "x_host" => $this->_cfg['host'],
                    "x_module" => "php_mysql_connect",
                    "x_instance_name" => $this->_instant_name,
                    "x_file" => __FILE__,
                    "x_line" => __LINE__,
                );

                //if connect fail will throw exception
                $this->_db = new \PDO($this->_cfg['dsn'], $this->_cfg['user'], $this->_cfg['pwd'], $this->options);
                $eagleeye_param["x_duration"] = round(microtime(true) - $startTime, 4);

                EagleEye::baseLog($eagleeye_param);

                return true;
            } catch (\Exception $e) {
                //record error
                $eagleeye_param["x_duration"] = round(microtime(true) - $startTime, 4);
                $eagleeye_param["x_name"] = "mysql.connect.error";
                $eagleeye_param["x_code"] = $e->getCode();
                $eagleeye_param["x_msg"] = $e->getMessage();
                EagleEye::baseLog($eagleeye_param);

                $retry--;

                if (!$retry) {
                    throw $e;
                }
                continue;
            }
        }
        return false;
    }

    /**
     * Ping 一下服务器，测试是否通
     * 由于PDO没有这个函数，自行实现
     * @return bool
     */
    public function ping()
    {
        try {
            $this->_db->getAttribute(\PDO::ATTR_SERVER_INFO);
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'MySQL server has gone away') !== false) {
                return false;
            }
        }
        return true;

    }

    /**
     * 检查数据库连接,是否有效，无效则重新建立
     * @throws \Exception 链接异常
     */
    protected function checkConnection()
    {
        if (!$this->ping()) {
            $this->close();
            return $this->connect();
        }
        return true;
    }

    /**
     * SQL错误信息
     * @param $sql
     * @return string
     */
    protected function errorMessage($sql)
    {
        $msg = $this->_db->errorCode() . "<hr />SQL: $sql<hr />\n";
        $msg .= "Server: {$this->_cfg['host']}:{$this->_cfg['port']}. <br/>\n";

        $msg .= "Message: " . json_encode($this->_db->errorInfo()) . " <br/>\n";
        $msg .= "Errno: {$this->_db->errorCode()}\n";
        return $msg;
    }

    /**
     * 执行sql查询，并自带重连机制 - 韩天峰老师改进
     * @param string $sql sql
     * @param array $bindparam prepare bind
     * @param int $retryCount 重试次数
     * @return bool|\PDOStatement
     * @throws \Exception 传输失败|SQL 语法错误
     */
    protected function queryWithRetry($sql, $bindparam = [], $retryCount = 4)
    {
        $lastException = null;

        for ($i = 0; $i < $retryCount; $i++) {
            $startTime     = microtime(true);
            $eagleeyeParam = array(
                "x_name"          => "mysql.request",
                "x_host"          => $this->_cfg['host'],
                "x_module"        => "php_mysql_query",
                "x_instance_name" => $this->_instant_name,
                "x_file"          => __FILE__,
                "x_line"          => __LINE__,
                "x_action"        => $sql,
                "x_param"         => @json_encode($bindparam)
            );

            try {
                if (empty($bindparam)) {
                    //sql directly
                    $result = $this->_db->query($sql);

                    $costTime                    = round(microtime(true) - $startTime, 4);
                    $eagleeyeParam["x_duration"] = $costTime;

                    if ($result) {
                        EagleEye::baseLog($eagleeyeParam);

                        $this->_affrow = $result->rowCount();
                        return $result;
                    }
                } else {

                    //prepare
                    $sth = $this->_db->prepare($sql);

                    //execute
                    $ret = $sth->execute($bindparam);

                    $costTime                    = round(microtime(true) - $startTime, 4);
                    $eagleeyeParam["x_duration"] = $costTime;

                    //success
                    if ($ret) {
                        EagleEye::baseLog($eagleeyeParam);
                        $result        = $sth;
                        $this->_affrow = $result->rowCount();
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                $lastException = $e;
                $eagleeyeParam["x_name"]      = "mysql.request.error";
                $eagleeyeParam["x_code"]      = $e->getCode();
                $eagleeyeParam["x_msg"]       = $e->getMessage();
                $eagleeyeParam["x_backtrace"] = $e->getTraceAsString();
                $eagleeyeParam["x_duration"]  = round(microtime(true) - $startTime, 4);

                //网络问题，失败重来
                if (!$this->ping()) {
                    $this->checkConnection();

                    //如果开启了事务就不再retry、需要打断重来，防止因为链接中断导致事务执行一半，后续在非事务下继续执行
                    if ($this->_transaction_enabled)
                    {
                        //关闭事务标志
                        $this->_transaction_enabled = false;
                        EagleEye::baseLog($eagleeyeParam);
                        throw $e;
                    } else {
                        //网络不好时延迟重连 200ms后重试
                        usleep(100);
                        //没有开启事务，那么重新执行SQL，提高系统稳定性
                        continue;
                    }

                }

                //不是网络抛出
                EagleEye::baseLog($eagleeyeParam);
                throw $e;
            }


        }
        throw new SystemException(__CLASS__ . " mysql Retry Connect server 4 times fail {$sql} Error:" . !empty($lastException) ? $lastException->getMessage() . "(". $lastException->getCode() . ")": "", -22122);
    }

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'mysql:dbname=' . $config['name'] . ';host=' . $config['host'];
        if (!empty($config['port'])) {
            $dsn .= ';port=' . $config['port'];
        } elseif (!empty($config['socket'])) {
            $dsn .= ';unix_socket=' . $config['socket'];
        }
        if (!empty($config['lang'])) {
            //为兼容各版本PHP,用两种方式设置编码
            $this->options[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config['lang'];
            $dsn                                          .= ';charset=' . $config['lang'];
        }
        return $dsn;
    }


    public function setTimeout($timeout = 3)
    {
        $this->options[\PDO::ATTR_TIMEOUT] = $timeout;
        $this->_timeout = $timeout;
    }

    /**
     * 返回数据表对象
     * @return \PDO
     */
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * 选中并打开数据库
     *
     * @param string $name 重新选择数据库,为空时选择默认数据库
     * */
    public function useDb($name = null)
    {
        if (null === $name) {
            $name = $this->_cfg['name'];
        }
        $this->_db->exec("use $name;");
    }

    /**
     * 获取记录集合,当记录行为一个字段时输出变量结果 当记录行为多个字段时输出一维数组结果变量
     *
     * @param string $sql 标准查询SQL语句
     * @param array $bindparam prepare bind parameter
     * @param integer $r 是否合并数组
     * @return string|array
     * @throws SystemException SQL如果格式错误会抛出
     * */
    public function get($sql, $r = null, $bindparam = array())
    {
        $ret = $this->query($sql, $bindparam);
        if (!$ret) {
            return null;
        }

        $rs = $this->fetch($ret);
        //是否合并数组
        if (!empty($r) && !empty($rs)) {
            $rs = join(',', $rs);
        }
        return $rs;
    }

    /**
     * 返回查询记录的数组结果集
     *
     * @param string $sql 标准SQL语句
     * @param array $bindparam prepare bind parameter
     * @return array
     * @throws SystemException SQL如果格式错误会抛出
     * */
    public function getall($sql, $bindparam = array())
    {
        $item = array();
        $q    = $this->query($sql, $bindparam);
        while ($rs = $this->fetch($q)) {
            $item[] = $rs;
        }
        return $item;
    }

    /**
     * 获取插入的自增ID
     *
     * @return integer
     **/
    public function getId()
    {
        return $this->_db->lastInsertId();
    }

    /**
     * 发送查询
     *
     * @param string $sql 标准SQL语句
     * @param array $bindparam prepare parameter
     * @return \PDOStatement | bool
     * @throws SystemException 链接错误，SQL错误
     */
    public function query($sql, $bindparam = array())
    {

        //last sql
        $this->_lastSQL = ["sql" => $sql, "param" => $bindparam];

        //emmm ? why ?
        if (empty($this->_db)) {
            throw new SystemException("db is not Init", -22231);
        }

        //temp fixed the exception no sql on debug
        $query = null;
        $startTime = microtime(true);
        $exception = null;

        try {
            //query sql with retry
            $query = $this->queryWithRetry($sql, $bindparam);
        } catch (\Exception $e) {
            $exception = $e;
        }

        //debug open will record more
        if (\Fend\Debug::isDebug()) {

            //debug Info collect
            $debugInfo = [];
            $debugInfo['mode'] = $this->_instant_name;
            $debugInfo['sql'] = $sql;
            $debugInfo['info'] = [$bindparam];
            $debugInfo['time'] = round(microtime(true) - $startTime, 4);

            //if have explain
            $explain = array();
            if ($query && preg_match("/^(select )/i", $sql)) {
                $qs = $this->queryWithRetry('EXPLAIN ' . $sql, $bindparam);
                while ($rs = $this->fetch($qs)) {
                    $explain[] = $rs;
                }

                if (!empty($explain)) {
                    $debugInfo['explain'] = $explain;
                }
            }

            //record sql info
            \Fend\Debug::appendSqlInfo($debugInfo);
        }

        if($exception != null) {
            throw $exception;
        }

        return $query;
    }

    /**
     * 返回字段名为索引的数组集合
     *
     * @param \PDOStatement $q 查询指针
     * @return array
     **/
    public function fetch($q)
    {
        return $q->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * 格式化MYSQL查询字符串
     *
     * @param string $str 待处理的字符串
     * @return string
     **/
    public function escape($str)
    {
        $str = $this->_db->quote($str);
        if (strlen($str) > 2) {
            return substr($str, 1, strlen($str) - 2);
        }

        return "";
    }

    /**
     * 关闭当前数据库连接
     *
     * @return bool
     **/
    public function close()
    {
        return $this->_db = null;
    }

    /**
     * 取得数据库中所有表名称
     *
     * @param string $db 数据库名,默认为当前数据库
     * @return array
     * @throws SystemException 链接失败，语法错误
     **/
    public function getTableList($db = null)
    {
        $item = array();
        $q    = $this->query('SHOW TABLES ' . (null == $db ? null : 'FROM ' . $db));
        while ($rs = $this->fetchs($q)) {
            $item[] = $rs[0];
        }
        return $item;
    }


    /**
     * 获取表中所有字段及属性
     *
     * @param string $tb 表名
     * @return array
     * @throws SystemException 链接失败，语法错误
     **/
    public function getDbFields($tb)
    {
        $item = array();
        $q    = $this->query("SHOW FULL FIELDS FROM {$tb}");//DESCRIBE users
        while ($rs = $this->fetch($q)) {
            $item[] = $rs;
        }
        return $item;
    }

    /**
     * 生成表的标准Create创建SQL语句
     *
     * @param string $tb 表名
     * @return string
     * @throws SystemException 链接失败，语法错误
     **/
    public function sqlTB($tb)
    {
        $q  = $this->query("SHOW CREATE TABLE {$tb}");
        $rs = $this->fetchs($q);
        return $rs[1];
    }

    /**
     * 整理优化表
     * 注意: 多个表采用多个参数进行传入
     *
     * Example: setTB('table0','table1','tables2',...)
     * @param string 表名称可以是多个
     * @throws SystemException 链接失败，语法错误
     **/
    public function optimizeTable()
    {
        $args = func_get_args();
        foreach ($args as &$v) {
            $this->query("OPTIMIZE TABLE {$v};");
        }

    }

    /**
     * 返回键名为序列的数组集合
     *
     * @param \PDOStatement $q 资源标识指针
     * @return array
     **/
    public function fetchs($q)
    {
        return $q->fetch();
    }

    /**
     * 取得结果集中行的数目
     *
     * @param \PDOStatement $q 资源标识指针
     * @return int
     **/
    public function reRows($q)
    {
        return $q->rowCount();
    }

    /**
     * 取得被INSERT、UPDATE、DELETE查询所影响的记录行数
     *
     * @return int
     **/
    public function afrows()
    {
        //每次执行后会人工记录给用户获取
        return $this->_affrow;
    }

    /**
     * 释放结果集缓存
     *
     * @param \PDOStatement $q 资源标识指针
     **/
    public function refree(&$q)
    {
        $q = null;
    }

    /**
     * 启动事务处理
     * @return bool
     */
    public function start()
    {
        $ret = $this->_db->beginTransaction();
        if($ret) {
            $this->_transaction_enabled = true;
        }
        return $ret;
    }

    /**
     * 提交事务处理
     * @return bool
     */
    public function commit()
    {
        $this->_transaction_enabled = false;
        return $this->_db->commit();
    }

    /**
     * 事务回滚
     * @return bool
     */
    public function back()
    {
        $this->_transaction_enabled = false;
        if (!empty($this->_db) && $this->_db->inTransaction()) {
            return $this->_db->rollBack();
        } else {
            return false;
        }
    }

    /**
     * 获取当前是否开启事务
     * @return bool true打开状态、false关闭状态
     */
    public function getTransactionStatus()
    {
        return $this->_transaction_enabled;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function getErrorInfo()
    {
        return array(
            "msg"  => implode("\n", $this->_db->errorInfo()),
            "code" => $this->_db->errorCode()
        );
    }

    /**
     * 获取最后一次执行的SQL
     * @return array [sql,param]
     */
    public function getLastSQL()
    {
        return $this->_lastSQL;
    }

    /**
     * 闭包执行事务
     * 闭包函数内抛出异常会自动回滚事务，否则自动提交事务并将闭包函数的返回值返回
     * ```
     * $result = $db->transaction(function($db) {
     *       // 业务逻辑处理
     *  return true;
     * });
     * var_dump($result); // true
     * ```
     * @param callable $callable
     * @return mixed
     * @throws \Throwable
     * @author xialeistudio
     * @date 2019-09-05
     */
    public function transaction(callable $callable)
    {
        $this->start();
        try {
            $result = call_user_func($callable, $this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->back();
            throw $e;
        }
    }
}
