<?php

namespace Fend\Db;

use Fend\Config;

class SQLBuilder extends \Fend\Fend
{
    protected $_tableName = '';
    protected $_where = '';
    protected $_field = '';
    protected $_order = '';
    protected $_group = '';
    protected $_lock = 0;   //0无锁，1排他锁，2共享锁
    protected $_having = '';

    //left join使用
    protected $_collect_table = '';
    protected $_collect_field = '';
    protected $_collection_on = '';
    protected $_collection_where = '';

    protected $_start = 0;
    protected $_limit = 0;
    protected $_checksafe = 1;

    /**
     * @var \Fend\Db\Mysql
     */
    protected $_db = null;
    protected $checkcmd = array('SELECT', 'UPDATE', 'INSERT', 'REPLACE', 'DELETE');
    protected $config = array(
        'dfunction' => array('load_file', 'hex', 'substring', 'if', 'ord', 'char', 'ascii'),
        'daction' => array('intooutfile', 'intodumpfile', 'unionselect', '(select', 'unionall', 'uniondistinct', '@'),
        //'dnote' => array('/*', '*/', '#', '--', '"'),
        'dlikehex' => 1,
        'afullnote' => 1
    );

    /**
     * 将要提交的data拆分成条件+prepare bind param
     * 用于提高安全性做prepare查询
     * 自动将提交数据内value参数替换成占位符 "?"
     * 生成两个数组 一个是带占位符的条件，一个是摘除来的顺序参数数组
     *
     * @param $data
     * @return array 下标0为过滤后data，下标1为摘出的参数
     */
    public function makePrepareData($data)
    {
        $bindParam = [];
        $filteredData = [];

        foreach ($data as $k => $item) {
            //if is update xxx = xxx +|-|* 1;
            if (is_array($item) && count($item) == 2) {
                $filteredData[$k] = [$item[0], "?"];
                $bindParam[] = $item[1];
            } else {
                $filteredData[$k] = "?";
                $bindParam[] = $item;
            }
        }

        return [$filteredData, $bindParam];
    }

    /**
     * 将condition函数传入where条件做prepare整理
     * 自动将提交condition内value参数替换成占位符 "?"
     * 生成两个数组 一个是带占位符的条件，一个是摘除来的顺序参数数组
     *
     * @param array $conditions
     * @return array 下标0为过滤后data，下标1为摘出的参数
     */
    public function makePrepareCondition($conditions)
    {
        $bindParam = [];
        $filteredConditions = [];

        if (is_array($conditions) && !empty($conditions)) {
            foreach ($conditions as $key => $value) {
                //下标是操作符的数组
                if (is_array($value) && !empty($value)) {
                    //操作符下面的所有kv条件遍历
                    foreach ($value as $ky => $val) {
                        $filteredConditions[$key][$ky] = "?";
                        $bindParam[] = $val;
                    }
                } else {
                    $filteredConditions[$key] = "?";
                    $bindParam[] = $value;
                }
            }
        } elseif (!empty($conditions) && is_string($conditions)) {
            //这种没办法
            $filteredConditions = $conditions;
        }

        //还有其他类型的如object，这里直接忽略掉

        return [$filteredConditions, $bindParam];
    }

    /**
     * 将where 函数 传入条件，进行 prepare 查询整理
     * 自动将提交where条件内value参数替换成占位符 "?"
     * 生成两个数组 一个是带占位符的条件，一个是摘除来的顺序参数数组
     *
     * @param $where
     * @return array 下标0为过滤后data，下标1为摘出的参数
     */
    public function makePrepareWhere($where)
    {
        $bindParam = [];
        $filteredWhere = [];

        foreach ($where as $key => $val) {

            if (is_array($val) && count($val) == 2) {
                //支持如下
                //[ `$key` = $val]
                //[ `user_id` = 14]
                //[ `user_name` = 'oak']

                $filteredWhere[$key] = [$val[0], "?"];
                $bindParam[] = $val[1];

            } elseif (is_array($val) && count($val) == 3) {
                //支持如下
                //[$key $op $val]
                //[ `user_id` >= 16]
                //[ `user_name` like '%15%']
                //[ `user_id` not in (15,34,67,86)]
                //[ `user_name` in ('oak','cd','yes','haha')]

                $filteredWhere[$key][0] = $val[0];
                $filteredWhere[$key][1] = $val[1];
                //in 操作
                //[ `user_name` in ('oak','cd','yes','haha')]
                if (is_array($val[2])) {

                    foreach ($val[2] as $sk => $v) {
                        $filteredWhere[$key][2][$sk] = "?";
                        $bindParam[] = $v;
                    }
                } else {
                    $filteredWhere[$key][2] = "?";
                    $bindParam[] = $val[2];
                }

            } elseif (is_string($val)) {
                //这种没法过滤
                $filteredWhere[$key] = $val;
            }
        }
        //还有其他类型的如object 这里直接忽略掉

        return [$filteredWhere, $bindParam];

    }

    /**
     * @param $conditions
     * @param string $type
     * @return SQLBuilder
     */
    public function setConditions($conditions, $type = 'AND')
    {
        $where = '';
        if (is_array($conditions) && !empty($conditions)) {
            foreach ($conditions as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    foreach ($value as $ky => $val) {
                        $vs = $this->filterValue($val);
                        //如果value是数组，$key非数值，那么key是op 操作符
                        $wheres = (is_string($key)) ? " {$ky} {$key} {$vs} " : " {$ky} = {$vs} ";
                        $where .= !empty($where) ? " {$type} " . $wheres : $wheres;
                    }
                } else {
                    $vs = $this->filterValue($value);
                    $where .= !empty($where) ? " {$type} `{$key}` = {$vs}" : " `{$key}` = {$vs}";
                }
            }
            $this->_where = $where;
        } elseif (!empty($conditions) && is_string($conditions)) {
            $this->_where = $conditions;
        }
        return $this;
    }

    /**
     * 过滤val参数,并添加单引号
     * @param $val
     * @return int|string
     */
    private function filterValue($val)
    {
        if (is_numeric($val) && strlen($val) < 32) {
            return "'" . $val . "'";
        } else {
            if ($val === null) {
                return "null";
            }
            if ($val === "?") {
                return $val;
            }

            return "'" . $this->escape($val) . "'";
        }
    }

    /**
     * 过滤where中的field
     * 支持 database.table.field|table.field|field
     * 去掉空格,原有`
     * @param $key
     * @return string
     */
    private function filterField($key)
    {
        //replace all space char(`)
        $key = str_replace(array("`", " "), "", $key);

        //dont have path
        if (strpos($key, ".") === false) {
            return "`" . $key . "`";
        }

        return "`" . str_replace(".", "`.`", $key) . "`";
    }

    /**
     * where 条件追加检测
     * 如果上一个条件结尾是 ( 那么不追加And Or操作
     * @param $where
     * @param $op
     * @return string
     */
    private function filterOp($where, $op)
    {
        //where 为空，不追加op操作符
        if (empty($where)) {
            return "";
        }

        //(结尾不追加op
        $where = trim($where);
        if (substr($where, strlen($where) - 1) === "(") {
            return "";
        }

        //OR结尾不追加op
        if (substr($where, strlen($where) - 2) === "OR") {
            return "";
        }

        //AND结尾不追加op
        if (substr($where, strlen($where) - 3) === "AND") {
            return "";
        }
        return $op;
    }

    /**
     * 根据数组生成where结果
     * 和condition方法对比这个功能更多一些
     * @param array $conditions
     * @param string $Op 默认连接符号
     * @return string
     */
    public function generalWhere($conditions, $Op = 'AND')
    {
        $where = "";
        $Op = trim(strtoupper($Op));
        foreach ($conditions as $val) {

            if (is_array($val) && count($val) == 2) {
                //支持如下
                //[ `$key` = $val]
                //[ `user_id` = 14]
                //[ `user_name` = 'oak']

                $fieldString = $this->filterField($val[0]);
                $valueString = $this->filterValue($val[1]);

                $Ops = $this->filterOp($where, $Op);
                $where .= " $Ops $fieldString = $valueString";

            } elseif (is_array($val) && count($val) == 3) {
                //支持如下
                //[$key $op $val]
                //[ `user_id` >= 16]
                //[ `user_name` like '%15%']
                //[ `user_id` not in (15,34,67,86)]
                //[ `user_name` in ('oak','cd','yes','haha')]

                $fieldString = $this->filterField($val[0]);
                $operaString = $val[1];

                //in 操作
                //[ `user_name` in ('oak','cd','yes','haha')]
                if (is_array($val[2])) {
                    $valResult = [];
                    foreach ($val[2] as $v) {
                        $valResult[] = $this->filterValue($v);
                    }
                    $valueString = "(" . implode(",", $valResult) . ")";
                } else {
                    $valueString = $this->filterValue($val[2]);
                }

                $Ops = $this->filterOp($where, $Op);
                $where .= " $Ops $fieldString $operaString $valueString";

            } elseif (is_string($val)) {
                if ($val == "(") {
                    $Ops = $this->filterOp($where, $Op);
                    $where .= " $Ops $val";
                } elseif ($val == ")") {
                    $where .= " $val ";
                } elseif (stripos($val, "OR") === 0) {
                    $val = strtoupper($val);
                    $where .= " $val ";
                } elseif (stripos($val, "AND") === 0) {
                    $val = strtoupper($val);
                    $where .= " $val ";
                } else {
                    $Ops = $this->filterOp($where, $Op);
                    $where .= empty($where) ? " $val" : " $Ops $val";
                }
            }
        }
        return $where;
    }

    /**
     * where 新尝试
     * 和condition方法对比这个功能更多一些
     * @param array $where
     * @param string $Op 默认连接符号
     * @return SQLBuilder
     */
    public function where($where, $Op = 'AND')
    {
        $this->_where = $this->generalWhere($where, $Op);
        return $this;
    }

    /**
     * 设置数据表
     * 同时清理临时变量
     * @param string $table 表名
     * @return SQLBuilder
     */
    public function setTable($table)
    {
        $this->_tableName = $table;
        $this->clean();
        return $this;
    }

    /**
     * clean之前操作残余条件、分组、limit、join信息
     * 推荐每次查询之前都使用factory生成一个新对象
     * 而不是使用此方式来清理残余信息
     * @return $this
     */
    public function clean()
    {
        $this->_field = '';
        $this->_where = '';
        $this->_order = '';
        $this->_group = '';
        $this->_having = '';
        $this->_start = 0;
        $this->_limit = 0;
        $this->_collect_table = '';
        $this->_collect_field = '';
        $this->_collection_on = '';
        $this->_collection_where = '';
        $this->_lock = 0;
        return $this;
    }

    /**
     * 获取当前实例的表名字
     */
    public function getTable()
    {
        return $this->_tableName;
    }

    /**
     * 设置查询字段
     * @param string|array $field 字段列表
     * @return SQLBuilder
     * */
    public function setField($field = array())
    {
        if (empty($field)) {
            $this->_field = '*';
        } elseif (is_array($field)) {
            $this->_field = join(',', $field);
        } elseif (!empty($field) && !is_array($field)) {
            $this->_field = $field;
        }
        return $this;
    }

    /**
     * 设置limit数据,都设置成0会无限制
     * @param int $start offset
     * @param int $end limit
     * @return SQLBuilder
     * */
    public function setLimit($start = 0, $end = 20)
    {
        if (is_numeric($start)) {
            $this->_start = $start;
        }

        if (is_numeric($end)) {
            $this->_limit = $end;
        }
        return $this;
    }

    /**
     * 分页展示，自动计算offset及limit并填入
     * @param $page
     * @param int $pageSize
     * @return $this
     */
    public function page($page, $pageSize = 20)
    {
        if (empty($page) || $page <= 0) {
            $page = 1;
        }

        $this->setLimit(($page - 1) * $pageSize, $pageSize);
        return $this;
    }

    /**
     * 是否打开SQL检测
     * @param bool $open sql检测
     * @return SQLBuilder
     * */
    public function setSqlSafe($open = true)
    {
        $this->_checksafe = $open;
        return $this;
    }

    /**
     * 设置查询条件
     * @param string $where 字符串拼出来的where条件
     * @return SQLBuilder
     * */
    public function setWhere($where = '')
    {
        $this->_where = empty($where) ? "" : $where;
        return $this;
    }

    /**
     * 获取查询条件
     * @return string 最终结果的的where条件
     */
    public function getWhere()
    {
        return $this->_where;
    }

    /**
     * 设置排序
     * @param string $order order排序字符串
     * @return SQLBuilder
     * */
    public function setOrder($order = '')
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * 设置group by 内容 如果设置sql会自动跟随在后面增加group by
     * @param string $group 分组相关输出内容
     * @return SQLBuilder
     * */
    public function setGroup($group = '')
    {
        $this->_group = $group;
        return $this;
    }

    /**
     * group having
     * where 语法
     * @param array $where
     * @param string $Op 默认连接符号
     * @return SQLBuilder
     */
    public function having($where, $Op = 'AND')
    {
        $this->_having = $this->generalWhere($where, $Op);
        return $this;
    }

    /**
     * 设置Left Join关系表名
     * @param string $table left join表名
     * @return SQLBuilder
     * */
    public function setRelationTable($table)
    {
        if (!empty($table)) {
            $this->_collect_table = $table;
        }
        return $this;
    }

    /**
     * 设置Left Join 关联字段 on 内条件
     * @param string|array $on 条件数组或直接字符串语句
     * @return SQLBuilder
     * */
    public function setRelationOn($on)
    {
        $arron = array();
        if (is_array($on) && !empty($on)) {
            foreach ($on as $key => $val) {
                $arron[] = "{$this->_tableName}.{$key} = {$this->_collect_table}" . '.' . $val;
            }
        }
        if (is_string($on) && !empty($on)) {
            $arron[] = $on;
        }

        if (!empty($arron)) {
            $this->_collection_on = join(' AND ', $arron);
        }
        return $this;
    }

    /**
     * 这个用于Left Join查询右边SQL的Field
     * @param $field
     * @return SQLBuilder
     */
    public function setRelationField($field)
    {
        if (!empty($field) && is_array($field)) {
            foreach ($field as &$val) {
                if ($this->_collect_table) {
                    $val = "{$this->_collect_table}.{$val}";
                }
            }
            $this->_collect_field = join(',', $field);
        }
        return $this;
    }

    /**
     * Left Join 查询的Where条件设置
     * @param array|string $conditions
     * @param string $type
     * @return SQLBuilder
     */
    public function setRelationWhere($conditions, $type = 'AND')
    {
        $where = '';
        if (is_array($conditions) && !empty($conditions) && !empty($this->_collect_table)) {
            foreach ($conditions as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    foreach ($value as $ky => $val) {

                        $val = $this->filterValue($val);

                        $wheres = (is_string($key)) ? " `{$this->_collect_table}`.`{$ky}` {$key} {$val} " : " `{$this->_collect_table}`.`{$ky}` = {$val} ";
                        $where .= !empty($where) || !empty($this->_where) ? " {$type} " . $wheres : $wheres;
                    }
                } else {
                    $value = $this->filterValue($value);

                    $where .= !empty($where) || !empty($this->_where) ? " {$type} `{$this->_collect_table}`.`{$key}` = {$value}" : " `{$this->_collect_table}`.`{$key}` = {$value}";
                }
            }
            $this->_collection_where = $where;
        } elseif (!is_array($conditions) && !empty($conditions) && !empty($this->_collect_table)) {
            $this->_collection_where = !empty($where) || !empty($this->_where) ? " $type " . $this->escape($conditions) : $this->escape($conditions);
        }
        return $this;
    }

    /**
     * SQL 排他锁 FOR UPDATE
     * 注意：影响性能
     * @return $this
     */
    public function lock()
    {
        $this->_lock = 1;
        return $this;
    }

    /**
     * SQL 共享锁 LOCK IN SHARE MODE
     * 注意：影响性能
     * @return $this
     */
    public function shareLock()
    {
        $this->_lock = 2;
        return $this;
    }

    /**
     * 格式化MYSQL查询字符串
     *
     * @param string $str 待处理的字符串
     * @return string
     * */
    public function escape($str)
    {
        return $this->_db->escape($str);
    }

    /**
     * 生成REPLACE|UPDATE|INSERT等标准SQL语句
     * 此函数用于对数据添加更新
     *
     * @param array $arr 操纵数据库的数组源
     * @param string $dbname 数据表名
     * @param string $type SQL类型 UPDATE|INSERT|REPLACE|IFUPDATE
     * @param string $where where条件
     * @param array $duplicate 如果传递会变成如果存在那么更新数组内指定kv
     * @return string         一个标准的SQL语句
     * */
    public function subSQL($arr, $dbname, $type = 'update', $where = null, $duplicate = array())
    {
        $tem = $vals = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $k => $v) {
                if (is_array($v) && $type == 'insertall') {
                    if (empty($keys)) {
                        $keys = join(',', array_keys($v));
                    }
                    if (!empty($v)) {
                        //all value is ? must prepare
                        $isPrepare = true;
                        foreach ($v as $ks => $testItem) {
                            if ($testItem !== "?") {
                                $v[$ks] = $this->escape($testItem);
                                $isPrepare = false;
                            }

                        }
                        //if is prepare
                        if ($isPrepare) {
                            $vals[] = "( " . join(" , ", $v) . " )";
                        } else {
                            foreach ($v as $ks => $item) {
                                if($item === null) {
                                    $v[$ks] = "null";
                                    continue;
                                }
                                $v[$ks] = "'$item'";
                            }
                            $vals[] = "(" . join(",", $v) . ")";
                        }
                    }
                } else {
                    if (is_array($v) && count($v) == 2) {
                        $k = $this->escape($k);
                        $tem[$k] = "`{$k}` = `{$k}` {$v[0]} {$v[1]}";
                        continue;
                    }
                    $k = $this->escape($k);

                    if ($v === "?") {
                        $tem[$k] = " `{$k}` = {$v} ";
                    } else if($v === null) {
                        $tem[$k] = " `{$k}` = null ";
                    } else {
                        $v = $this->escape($v);
                        $tem[$k] = " `{$k}` = '{$v}' ";
                    }
                }
            }
        }
        switch (strtolower($type)) {
            case 'insertall'://批量插入
                if (!empty($keys) && !empty($vals)) {
                    $sql = "INSERT INTO {$dbname} ({$keys}) VALUES " . join(',', $vals);
                } else {
                    $sql = null;
                }
                break;
            case 'insert'://插入
                $sql = "INSERT INTO {$dbname} SET " . join(',', $tem);
                break;
            case 'replace'://替换
                $sql = "REPLACE INTO {$dbname} SET " . join(',', $tem);
                break;
            case 'update'://更新
                $sql = "UPDATE {$dbname} SET " . join(',', $tem);
                if (!empty($where)) {
                    $sql .= " WHERE {$where}";
                }
                break;
            case 'ifupdate'://存在则更新记录
                $tem = join(',', $tem);
                if (!empty($duplicate)) {
                    $ifitem = [];
                    foreach ($duplicate as $ks => $vs) {
                        $vs = $this->filterValue($vs);
                        $ifitem[$ks] = "`{$ks}`={$vs}";
                    }
                    $ifitem = join(',', $ifitem);
                    $sql = "INSERT INTO {$dbname} SET {$tem} ON DUPLICATE KEY UPDATE {$ifitem}";
                } else {
                    $sql = "INSERT INTO {$dbname} SET {$tem} ON DUPLICATE KEY UPDATE {$tem}";
                }
                break;
            case 'delete'://存在则更新记录
                $sql = "delete FROM {$dbname}";
                if (!empty($where)) {
                    $sql .= " WHERE {$where}";
                }
                break;
            default:
                $sql = null;
                break;
        }
        return $sql;
    }

    /**
     * 用于生成查询SQL
     * @return string
     */
    public function getSql()
    {
        if (empty($this->_field)) {
            $sql = 'SELECT ' . ' * ';
        } else {
            $sql = 'SELECT ' . $this->_field;
        }
        if (empty($this->_field) && !empty($this->_collect_field) && !empty($this->_collect_table)) {
            $sql = 'SELECT ' . $this->_collect_field;
        } elseif (!empty($this->_collect_field) && !empty($this->_collect_table)) {
            $sql .= "," . $this->_collect_field;
        }

        $sql .= ' FROM ' . $this->_tableName;
        if (!empty($this->_collect_table) && !empty($this->_collection_on)) {
            $sql .= ' LEFT JOIN ' . $this->_collect_table;
            $sql .= ' ON ' . $this->_collection_on;
        }
        if (!empty($this->_where) && !empty($this->_collection_where)) {
            $sql .= ' WHERE ' . $this->_where . ' ' . $this->_collection_where;
        } elseif (!empty($this->_where)) {
            $sql .= ' WHERE ' . $this->_where;
        } elseif (!empty($this->_collection_where)) {
            $sql .= ' WHERE ' . $this->_collection_where;
        }

        if (!empty($this->_group)) {
            $sql .= ' GROUP BY ' . $this->_group;
        }

        if (!empty($this->_having)) {
            $sql .= ' Having ' . $this->_having;
        }

        if (!empty($this->_order)) {
            $sql .= ' ORDER BY ' . $this->_order;
        }
        if (!empty($this->_limit)) {
            $start = !empty($this->_start) ? $this->_start : 0;
            $sql .= ' LIMIT ' . $start . ',' . $this->_limit;
        }

        //add Lock
        if ($this->_lock === 1) {
            $sql .= " FOR UPDATE";
        } elseif ($this->_lock === 2) {
            $sql .= " LOCK IN SHARE MODE";
        }

        return $sql;
    }

    /**
     * 获取count查询SQL
     * @return string
     */
    public function getSqlSum()
    {
        $sql = 'SELECT COUNT(*) AS total ';

        $sql .= ' FROM ' . $this->_tableName;
        if (!empty($this->_collect_table) && !empty($this->_collection_on)) {
            $sql .= ' LEFT JOIN ' . $this->_collect_table;
            $sql .= ' ON ' . $this->_collection_on;
        }
        if (!empty($this->_where) && !empty($this->_collection_where)) {
            $sql .= ' WHERE ' . $this->_where . ' ' . $this->_collection_where;
        } elseif (!empty($this->_where)) {
            $sql .= ' WHERE ' . $this->_where;
        } elseif (!empty($this->_collection_where)) {
            $sql .= ' WHERE ' . $this->_collection_where;
        }
        if (!empty($this->_group)) {
            $sql .= ' GROUP BY ' . $this->_group;
        }

        if (!empty($this->_having)) {
            $sql .= ' Having ' . $this->_having;
        }

        //add Lock
        if ($this->_lock === 1) {
            $sql .= " FOR UPDATE";
        } elseif ($this->_lock === 2) {
            $sql .= " LOCK IN SHARE MODE";
        }

        return $sql;
    }

    /**
     * 获取最后一次insert id
     * @return int|bool
     */
    public function getLastId()
    {
        return $this->_db->getId();
    }

    /**
     * 获取一条数据
     * @param array $bindParam prepare bind param array
     * @return array|string|boolean
     * @throws \Exception 查询SQL错误，或链接失败
     */
    public function getOne($bindParam = array())
    {
        $sql = $this->getSql();
        return $this->_db->get($sql, 0, $bindParam);
    }

    /**
     * 获取记录集合,当记录行为一个字段时输出变量结果 当记录行为多个字段时输出一维数组结果变量
     * @param string $sql ;
     * @param int $r ;
     * @return mixed
     * @throws \Exception  SQL错误或链接失败
     */
    public function get($sql, $r = 0)
    {
        return $r > 0 ? $this->_db->get($sql, 1) : $this->_db->get($sql);
    }

    /**
     * 获取查询条件的数据的总数
     * @param array $bindParam prepare bind parameter
     * @return string;
     * @throws \Exception SQL错误或链接失败
     * */
    public function getSum($bindParam = array())
    {
        $sql = $this->getSqlSum();
        return $this->_db->get($sql, 1, $bindParam);
    }

    /**
     * 获取之前SQL拼装的，返回数据列表
     * @param array $bindParam prepare bind parameter
     * @return array
     * @throws \Exception SQL错误或链接失败
     */
    public function getList($bindParam = array())
    {
        $list = array();
        $query = $this->query('', $bindParam);
        if (!empty($query)) {
            while ($rs = $this->fetch($query)) {
                $list[] = $rs;
            }
        }
        return $list;
    }

    /**
     * 查询SQL
     * @param string $sql
     * @param array $bindparam prepare bind param
     * @return \mysqli_result|bool
     * @throws \Exception SQL错误或链接失败
     */
    public function query($sql = '', $bindparam = array())
    {
        $sql = empty($sql) ? $this->getSql() : $sql;

        //sql 安全检查
        $sqlsafe = $this->checkquery($sql);

        //sql安全不符合，拒绝记录日志
        if (!$sqlsafe && $this->_checksafe) {
            $_tmp = '';
            $str = "SqlSafe failed: ";
            isset($_SERVER['SERVER_ADDR']) && $_tmp .= '[' . $_SERVER['SERVER_ADDR'] . ']';
            isset($_SERVER['REQUEST_URI']) && $_tmp .= '[' . $_SERVER['REQUEST_URI'] . ']';
            $_tmp .= "\n";
            $_tmp .= $sql . "\n";
            $_tmp .= json_encode($bindparam) . "\n";

            $logPath = Config::get("Fend");
            $logPath = $logPath["log"]["path"];

            file_put_contents($logPath . FD_DS . "db_safe.log",
                date("Y-m-d H:i:s > ") . $_tmp . $str . $this->_db->error . "\n\n", FILE_APPEND);
            throw new \Exception("Sql Safe Not Passed:" . $sql, -3123);
        }

        return $this->_db->query($sql, $bindparam);

    }

    /**
     * 取得被INSERT、UPDATE、DELETE查询所影响的记录行数
     *
     * @return int
     * */
    public function afrows()
    {
        return $this->_db->afrows();
    }

    /**
     * 从result_result获取数据
     * @param \mysqli_result $query
     * @return bool|array
     */
    public function fetch($query)
    {
        if (!empty($query)) {
            return $this->_db->fetch($query);
        }
        return false;
    }

    /**
     * 开启事务
     * @return bool
     */
    public function trans_begin()
    {
        return $this->_db->start();
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function trans_rollback()
    {
        return $this->_db->back();
    }

    /**
     * 事务提交
     * @return bool
     */
    public function trans_commit()
    {
        return $this->_db->commit();
    }

    /**
     * 获取事务开启状态
     * @return bool
     */
    public function getTransactionStatus()
    {
        return $this->_db->getTransactionStatus();
    }

    /**
     * sql安全监测
     * @param $sql
     * @return bool
     */
    public function checkquery($sql)
    {
        $cmd = trim(strtoupper(substr($sql, 0, strpos($sql, ' '))));
        if (in_array($cmd, $this->checkcmd)) {
            $test = self::_do_query_safe($sql);
            if ($test < 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * 私有sql检测
     * 只要一点不符合就拒绝
     * @param $sql
     * @return int|string
     */
    private function _do_query_safe($sql)
    {
        $sql = str_replace(array('\\\\', '\\\'', '\\"', '\'\''), '', $sql);
        $mark = $clean = '';
        if (strpos($sql, '/') === false && strpos($sql, '#') === false && strpos($sql, '-- ') === false) {
            $clean = preg_replace("/'(.+?)'/s", '', $sql);
        } else {
            $len = mb_strlen($sql);
            $mark = $clean = '';
            for ($i = 0; $i < $len; $i++) {
                $str = $sql[$i];
                switch ($str) {
                    case '\'':
                        if (!$mark) {
                            $mark = '\'';
                            $clean .= $str;
                        } elseif ($mark == '\'') {
                            $mark = '';
                        }
                        break;
                    case '/':
                        if (empty($mark) && $sql[$i + 1] == '*') {
                            $mark = '/*';
                            $clean .= $mark;
                            $i++;
                        } elseif ($mark == '/*' && $sql[$i - 1] == '*') {
                            $mark = '';
                            $clean .= '*';
                        }
                        break;
                    case '#':
                        if (empty($mark)) {
                            $mark = $str;
                            $clean .= $str;
                        }
                        break;
                    case "\n":
                        if ($mark == '#' || $mark == '--') {
                            $mark = '';
                        }
                        break;
                    case '-':
                        if (empty($mark) && substr($sql, $i, 3) == '-- ') {
                            $mark = '-- ';
                            $clean .= $mark;
                        }
                        break;

                    default:

                        break;
                }
                $clean .= $mark ? '' : $str;
            }
        }

        if (strpos($clean, '@') !== false) {
            return '-3';
        }

        if (is_array($this->config['dfunction'])) {
            foreach ($this->config['dfunction'] as $fun) {
                if ( strpos($clean, $fun . '(') === 0   //front
                    || strpos($clean, ' '.$fun . '(') !== false //empty head
                    || strpos($clean, '('.$fun . '(') !== false //with ()
                    || strpos($clean, ','.$fun . '(') !== false //,ord()
                ) {
                    return '-1';
                }
            }
        }

        $clean = preg_replace("/[^a-z0-9_\-\(\)#\*\/\"]+/is", "", strtolower($clean));

        if ($this->config['afullnote']) {
            $clean = str_replace('/**/', '', $clean);
        }

        if (is_array($this->config['daction'])) {
            foreach ($this->config['daction'] as $action) {
                if (strpos($clean, $action) !== false) {
                    return '-3';
                }
            }
        }

        if ($this->config['dlikehex'] && strpos($clean, 'like0x')) {
            return '-2';
        }

        /*
        if (is_array($this->config['dnote'])) {
            foreach ($this->config['dnote'] as $note) {
                if (strpos($clean, $note) !== false) {
                    return '-4';
                }
            }
        }*/
        return 1;
    }

    /**
     * 切换数据库
     * @param mixed $dbName
     * @return SQLBuilder
     * @throws \Exception 切换异常
     */
    public function useDb($dbName = null)
    {
        $this->_db->useDb($dbName);
        return $this;
    }

    /**
     * 更改数据库链接超时时间，不建议改变
     * @param int $timeout
     * @return SQLBuilder
     */
    public function setTimeout($timeout = 30)
    {
        $this->_db->setTimeout($timeout);
        return $this;
    }

    /**
     * 如果SQL执行报错，可以通过此获取具体信息
     * 不过改版后，具体原因直接在异常内展示了
     * @return array
     */
    public function getErrorInfo()
    {
        return $this->_db->getErrorInfo();
    }


    /**
     * 获取最后一次执行的SQL
     * @return array [sql,param]
     */
    public function getLastSQL()
    {
        return $this->_db->getLastSQL();
    }
}
