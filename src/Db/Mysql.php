<?php

namespace Fend\Db;

use Fend\Config;
use Fend\Exception\SystemException;
use Fend\Log\EagleEye;

/**
 * Class Mysql
 * @package Fend\Db
 */
class Mysql extends \Fend\Fend
{
    /**
     * @var \mysqli
     */
    protected $_db = null;//连接db对象
    protected $_instant_name = "";
    protected $_cfg = null;//连接配置信息

    private $_lastSQL = [];

    private $_affect_row = -1;

    private $_transaction_enabled = false;

    protected $_timeout = 3;

    protected static $in = array();

    /**
     * @param string $r r|w 读写标志
     * @param string $db database config name
     * @return mixed
     * @throws SystemException
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
     * 连接服务器
     * @return bool
     * @throws SystemException 多次重试链接失败
     */
    public function connect()
    {
        $retry = 4;
        while ($retry) {
            //record connect time
            $startTime = microtime(true);

            //prepare info of error
            $eagleeye_param = array(
                "x_name" => "mysql.connect",
                "x_host" => $this->_cfg['host'],
                "x_module" => "php_mysql_connect",
                "x_instance_name" => $this->_instant_name,
                "x_file" => __FILE__,
                "x_line" => __LINE__,
            );

            try {
                $this->_db = mysqli_init();
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                $this->_db->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->_timeout);
                $this->_db->real_connect($this->_cfg['host'], $this->_cfg['user'], $this->_cfg['pwd'], $this->_cfg['name'], $this->_cfg['port']);
            } catch (\Exception $e) {
                //error
                $eagleeye_param["x_duration"] = round(microtime(true) - $startTime, 4);
                $eagleeye_param["x_name"] = "mysql.connect.error";
                $eagleeye_param["x_code"] = mysqli_connect_errno();
                $eagleeye_param["x_msg"] = mysqli_connect_error();
                EagleEye::baseLog($eagleeye_param);

                //decrease the count
                $retry--;

                continue;
            }

            $eagleeye_param["x_duration"] = round(microtime(true) - $startTime, 4);
            EagleEye::baseLog($eagleeye_param);
            $this->_db->query("SET character_set_connection={$this->_cfg['lang']},character_set_results={$this->_cfg['lang']},character_set_client=binary,sql_mode='';");
            return true;
        }

        throw new SystemException(" Connect mysql error :" . mysqli_connect_error(), -mysqli_connect_errno());
    }

    /**
     * 检查数据库连接,是否有效，无效则重新建立
     * @throws SystemException
     */
    protected function checkConnection()
    {
        try {
            $this->_db->ping();
        } catch (\Exception $e) {
            $this->close();
            return $this->connect();
        }
        return true;
    }

    /**
     * 执行sql查询，并自带重连机制 - 韩天峰老师改进
     * @param string $sql sql
     * @param array $bindparam prepare bind
     * @param int $retryCount 重试次数
     * @return bool|\mysqli_result
     * @throws SystemException 传输失败|SQL 语法错误
     */
    protected function queryWithRetry($sql, $bindparam = [], $retryCount = 4)
    {
        //保留最后一次异常情况
        $lastException = null;

        for ($i = 0; $i < $retryCount; $i++) {

            try {
                $startTime = microtime(true);
                $eagleeyeParam = array(
                    "x_name" => "mysql.request",
                    "x_host" => $this->_cfg['host'],
                    "x_module" => "php_mysql_query",
                    "x_instance_name" => $this->_instant_name,
                    "x_file" => __FILE__,
                    "x_line" => __LINE__,
                    "x_action" => $sql,
                    "x_param" => @json_encode($bindparam)
                );

                //not prepare query?
                if (empty($bindparam)) {
                    //sql directly
                    $result = $this->_db->query($sql);
                    $this->_affect_row = $this->_db->affected_rows;
                } else {

                    //prepare
                    $stmt = $this->_db->prepare($sql);

                    //type list of bind
                    $type = "";

                    //one by one for check bind fail
                    foreach ($bindparam as $item) {

                        switch (gettype($item)) {
                            case "NULL":
                                $type .= "s";
                                break;
                            case "integer":
                                $type .= "i";
                                break;
                            case "double":
                                $type .= "d";
                                break;
                            case "string":
                                $type .= "s";
                                //长度超过1m，默认mysql max_allowed_packet 那么使用这个发送
                                if (strlen($item) > 1 * 1024 * 1024) {
                                    $type .= "b";
                                }
                                break;
                        }
                    }

                    //bind param with mass value
                    $stmt->bind_param($type, ...$bindparam);

                    if ($stmt->execute()) {
                        //人工修复不准问题
                        $this->_affect_row = $stmt->affected_rows;
                        $result = $stmt->get_result();
                        //insert update del was no result
                        if (!$result) {
                            return true;
                        }
                    }
                }

                $costTime = round(microtime(true) - $startTime, 4);
                $eagleeyeParam["x_duration"] = $costTime;

                //成功
                if ($result != false) {
                    EagleEye::baseLog($eagleeyeParam);
                    return $result;
                }

                //不是连接错误，是其他错误直接抛了,一般都是SQL错误
                throw new SystemException(__CLASS__ . " SQL Execute Error : {$sql} Error:". $this->_db->error . " error no:" . $this->_db->errno , -23222);
            } catch (\mysqli_sql_exception $e) {
                $lastException = $e;
                $eagleeyeParam["x_name"] = "mysql.request.error";
                $eagleeyeParam["x_code"] = $e->getCode();
                $eagleeyeParam["x_msg"] = $e->getMessage();
                $eagleeyeParam["x_backtrace"] = $e->getTraceAsString();
                $eagleeyeParam["x_duration"] = round(microtime(true) - $startTime, 4);

                //语法错误 1064
                //主键冲突 1062
                if ($e->getCode() > 1000 && $e->getCode() < 2000) {
                    EagleEye::baseLog($eagleeyeParam);
                    throw $e;
                }

                //mysql has gone away
                if ($e->getCode() === 2006) {
                    $this->checkConnection();
                    //如果开启了事务就不再retry、需要打断重来，防止因为链接中断导致事务执行一半，后续在非事务下继续执行
                    if ($this->_transaction_enabled) {
                        //关闭事务标志
                        $this->_transaction_enabled = false;
                        EagleEye::baseLog($eagleeyeParam);
                        throw $e;
                    }
                    continue;
                }

                //ping
                if (!$this->_db->ping()) {
                    $this->checkConnection();

                    //如果开启了事务就不再retry、需要打断重来，防止因为链接中断导致事务执行一半，后续在非事务下继续执行
                    if ($this->_transaction_enabled) {
                        //关闭事务标志
                        $this->_transaction_enabled = false;
                        EagleEye::baseLog($eagleeyeParam);
                        throw $e;

                    } else {
                        usleep(100);
                        //没有开启事务，那么重新执行SQL，错误原因待定
                        continue;
                    }
                }

                //其他问题直接抛出来
                EagleEye::baseLog($eagleeyeParam);
                throw $e;
            }

        }

        //多次重连都失败了
        throw new SystemException(__CLASS__ . " mysql Retry Connect server 3 times fail {$sql} Error:" . !empty($lastException) ? $lastException->getMessage() . "(". $lastException->getCode() . ")": "", -22122);
    }

    /**
     * 初始化Mysql对象并进行连接服务器尝试
     * @param $r
     * @param $db
     * @throws SystemException 链接失败
     */
    public function __construct($r, $db)
    {
        $dbList = Config::get('Db');
        if (empty($dbList)) {
            throw new SystemException("dbconfig  is not set", -23212);
        }
        //如果没有指定使用默认第一个db配置
        $db = empty($db) ? array_keys($dbList)[0] : $db;
        $this->_instant_name = $db . "_" . $r;
        $config = $dbList[$db][$r];
        if (isset($config["type"])) {
            if ($config["type"] === "random") {
                $config = $config["config"][array_rand($config["config"])];
            } else {
                throw new SystemException("SQL Config Type is Wrong!" . $this->_instant_name, -23129);
            }
        }
        $this->_cfg = $config;
        $this->_cfg['port'] = empty($this->_cfg['port']) ? 3306 : $this->_cfg['port'];

        //链接数据库
        $this->connect();
    }

    /**
     * 设置链接超时
     * @param int $timeout
     */
    public function setTimeout($timeout = 3)
    {
        $this->_timeout = $timeout;
    }

    /**
     * 返回数据表对象
     */
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * 选中并打开数据库
     *
     * @param string $name 重新选择数据库,为空时选择默认数据库
     * @throws SystemException 选择失败会抛异常
     * */
    public function useDb($name = null)
    {
        if (null === $name) {
            $name = $this->_cfg['name'];
        }
        if (!$this->_db->select_db($name)) {
            throw new SystemException("mysql cant use db " . $name, -23221);
        }
    }

    /**
     * 获取记录集合,当记录行为一个字段时输出变量结果 当记录行为多个字段时输出一维数组结果变量
     *
     * @param string $sql 标准查询SQL语句
     * @param integer $r 是否合并数组
     * @param array $bindParam bind parameter
     * @return string|array
     * @throws SystemException SQL如果格式错误会抛出
     * */
    public function get($sql, $r = null, $bindParam = array())
    {
        $ret = $this->query($sql, $bindParam);
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
     * @param array $bindparam bind prepare param
     * @return array
     * @throws SystemException SQL如果格式错误会抛出
     * */
    public function getall($sql, $bindparam = array())
    {
        $item = array();
        $q = $this->query($sql, $bindparam);
        while ($rs = $this->fetch($q)) {
            $item[] = $rs;
        }
        return $item;
    }

    /**
     * 获取最后插入的自增ID
     * @return int
     * */
    public function getId()
    {
        return $this->_db->insert_id;
    }

    /**
     * 查询SQL
     *
     * @param string $sql 标准SQL语句
     * @param array $bindparam prepare bind param
     * @return bool|\mysqli_result
     * @throws SystemException SQL 语法错误，链接失败
     **/
    public function query($sql, $bindparam = [])
    {

        //last sql
        $this->_lastSQL = ["sql" => $sql, "param" => $bindparam];

        //emmm ? why ?
        if (empty($this->_db)) {
            throw new SystemException("db is not Init", -22231);
        }

        //record query strat time
        $startTime = microtime(true);
        $exception = null;
        try{
            //free the before result
            while (mysqli_more_results($this->_db) && mysqli_next_result($this->_db)) {
                $dummyResult = mysqli_use_result($this->_db);
                if ($dummyResult instanceof \mysqli_result) {
                    mysqli_free_result($dummyResult);
                }
            }

            //query sql with retry
            $query = $this->queryWithRetry($sql, $bindparam);
        }catch (\Exception $e) {
            $exception = $e;
        }

        $costTime = round(microtime(true) - $startTime, 4);

        //debug open will record more
        if (\Fend\Debug::isDebug()) {

            //debug Info collect
            $debugInfo = [];
            $debugInfo['mode']  = $this->_instant_name . "_" . "mysqli";
            $debugInfo['sql'] = $sql;
            $debugInfo['info'] = [$bindparam];
            $debugInfo['time'] = $costTime;

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

        if($exception !== null){
            throw $exception;
        }

        return $query;
    }

    /**
     * 返回字段名为索引的数组集合
     *
     * @param \mysqli_result $q 查询指针
     * @return array
     * */
    public function fetch($q)
    {
        return $q->fetch_assoc();
    }

    /**
     * 格式化MYSQL查询字符串
     *
     * @param string $str 待处理的字符串
     * @return string
     * */
    public function escape($str)
    {
        return $this->_db->real_escape_string($str);
    }

    /**
     * 关闭当前数据库连接
     *
     * @return bool
     * */
    public function close()
    {
        return $this->_db->close();
    }

    /**
     * 取得数据库中所有表名称
     *
     * @param string $db 数据库名,默认为当前数据库
     * @return array
     * @throws SystemException 链接失败
     * */
    public function getTableList($db = null)
    {
        $item = array();
        $q = $this->query('SHOW TABLES ' . (null == $db ? null : 'FROM ' . $db));
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
     * @throws SystemException 数据库链接失败
     * */
    public function getDbFields($tb)
    {
        $item = array();
        $q = $this->query("SHOW FULL FIELDS FROM {$tb}"); //DESCRIBE users
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
     * @throws SystemException 数据库链接失败
     * */
    public function sqlTB($tb)
    {
        $q = $this->query("SHOW CREATE TABLE {$tb}");
        $rs = $this->fetchs($q);
        return $rs[1];
    }

    /**
     * 整理优化表
     * 注意: 多个表采用多个参数进行传入
     *
     * Example: setTB('table0','table1','tables2',...)
     * @param string 表名称可以是多个
     * @throws SystemException 数据库链接失败
     * */
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
     * @param \mysqli_result $q 资源标识指针
     * @return array
     * */
    public function fetchs($q)
    {
        return $q->fetch_row();
    }

    /**
     * 取得结果集中行的数目
     *
     * @param \mysqli_result $q 资源标识指针
     * @return int
     * */
    public function reRows($q)
    {
        return $q->num_rows;
    }

    /**
     * 取得被INSERT、UPDATE、DELETE查询所影响的记录行数
     *
     * @return int
     * */
    public function afrows()
    {
        return $this->_affect_row;
    }

    /**
     * 释放结果集缓存
     *
     * @param \mysqli_result $q 资源标识指针
     * */
    public function refree($q)
    {
        $q->free_result();
    }

    /**
     * 启动事务处理
     * @return bool
     */
    public function start()
    {
        $ret = $this->_db->begin_transaction();
        if ($ret) {
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
        return $this->_db->rollback();
    }

    /**
     * 获取当前是否开启事务
     * @return bool true打开状态、false关闭状态
     */
    public function getTransactionStatus()
    {
        return $this->_transaction_enabled;
    }

    public function getErrorInfo()
    {
        return array(
            "msg" => $this->_db->error,
            "code" => $this->_db->errno,
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
