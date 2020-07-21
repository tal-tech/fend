<?php

namespace Fend;

/**
 * Fend_Write
 * 生成单表的写数据表对象
 *
 * @Author  gary
 * @version $Id$
 **/
class Write extends \Fend\Db\SQLBuilder
{

    public static function Factory($table = '', $db = '', $driver = 'Mysql')
    {
        return new self($table, $db, $driver);
    }

    public function __construct($table = '', $db = null, $driver = 'Mysql')
    {
        if (!empty($table)) {
            $this->_tableName = $table;
        }
        $Db_Module = "\\Fend\\Db\\" . $driver;
        $this->_db = $Db_Module::Factory('w', $db);
    }

    /**
     * 返回数据表对象
     */
    public function getModule()
    {
        return $this->_db;
    }

    /**
     * 添加数据接口
     * @param array $data
     * @param bool $prepare 是否开启prepare检测
     * @return false|int 成功返回主键id
     * @throws \Exception 链接失败或SQL错误
     */
    public function add($data, $prepare = false)
    {
        if (empty($data)) {
            return false;
        }

        //prepare
        $bindParam = [];
        if ($prepare) {
            $dataInfo  = $this->makePrepareData($data);
            $data      = $dataInfo[0];
            $bindParam = $dataInfo[1];
        }

        $this->clean();
        $sql = $this->subSQL($data, $this->_tableName, 'insert');

        $this->query($sql, $bindParam);
        return $this->getLastId();
    }

    /**
     * 批量增加数据
     * @param array $data
     * @param bool $prepare
     * @return bool|int
     * @throws \Exception
     */
    public function addMulti($data, $prepare = false)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        $bindParam = [];
        $resultData = [];

        if ($prepare) {
            foreach ($data as $dataKey => $item) {
                //prepare
                $dataInfo = $this->makePrepareData($item);
                $resultData[$dataKey] = $dataInfo[0];
                $bindParam = array_merge($bindParam, $dataInfo[1]);
            }
        } else {
            $resultData = $data;
        }

        $this->clean();
        $sql = $this->subSQL($resultData, $this->_tableName, 'insertall');
        $ret = $this->query($sql, $bindParam);
        return $this->afrows();
    }

    /**
     * 根据条件修改数据
     * @param array|string $condition
     * @param array $data
     * @param bool $prepare 是否开启prepare检测
     * @return bool|int
     * @throws \Exception 链接失败或SQL错误
     */
    public function edit($condition, $data, $prepare = false)
    {
        if (empty($condition) || empty($data)) {
            return false;
        }

        //prepare
        $bindParam = [];
        if ($prepare) {
            //data
            $dataInfo  = $this->makePrepareData($data);
            $data      = $dataInfo[0];
            $bindParam = $dataInfo[1];

            //condition
            $prepareInfo = $this->makePrepareCondition($condition);
            $condition   = $prepareInfo[0];

            //merge param
            $bindParam = array_merge($bindParam, $prepareInfo[1]);
        }

        $this->clean();
        $this->setConditions($condition);
        $where = $this->getWhere();
        $sql   = $this->subSQL($data, $this->_tableName, 'update', $where);
        $this->query($sql, $bindParam);
        return $this->afrows();
    }

    /**
     * 根据条件修改数据
     * 注意：where结尾的函数使用的是新where规则
     * @param array|string $where
     * @param array $data
     * @param bool $prepare 是否开启prepare检测
     * @return int|bool
     * @throws \Exception 链接失败或SQL错误
     */
    public function editByWhere($where, $data, $prepare = false)
    {
        if (empty($where) || empty($data)) {
            return false;
        }

        //prepare
        $bindParam = [];
        if ($prepare) {
            //data
            $dataInfo  = $this->makePrepareData($data);
            $data      = $dataInfo[0];
            $bindParam = $dataInfo[1];

            //condition
            $prepareInfo = $this->makePrepareWhere($where);
            $where   = $prepareInfo[0];

            //merge param
            $bindParam = array_merge($bindParam, $prepareInfo[1]);
        }

        $this->clean();
        $this->where($where);
        $where = $this->getWhere();
        $sql   = $this->subSQL($data, $this->_tableName, 'update', $where);
        $this->query($sql, $bindParam);
        return $this->afrows();
    }

    /**
     * 根据自增id更新记录
     * @param int $id
     * @param array $data
     * @param bool $prepare 是否开启prepare检测
     * @return bool|int
     * @throws \Exception 链接失败或SQL错误
     */
    public function editById($id, $data, $prepare = false)
    {
        if (empty($id) || empty($data)) {
            return false;
        }

        $conditions = ["id" => $id];

        //prepare
        $bindParam = [];
        if ($prepare) {
            //data
            $dataInfo  = $this->makePrepareData($data);
            $data      = $dataInfo[0];
            $bindParam = $dataInfo[1];

            //condition
            $prepareInfo = $this->makePrepareCondition($conditions);
            $conditions  = $prepareInfo[0];

            //merge param
            $bindParam = array_merge($bindParam, $prepareInfo[1]);
        }

        $this->clean();
        $this->setConditions($conditions);

        $sql = $this->subSQL($data, $this->_tableName, 'update', $this->getWhere());
        $this->query($sql, $bindParam);

        return $this->afrows();
    }

    /**
     * 根据条件删除记录
     * @param string|array $condition
     * @param bool $prepare 是否开启prepare检测
     * @return bool|int
     * @throws \Exception 链接失败或SQL错误
     */
    public function del($condition, $prepare = false)
    {
        if (empty($condition)) {
            return false;
        }
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareCondition($condition);
            $condition   = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->setConditions($condition);
        $this->setLimit(0, 0);
        $sql = $this->subSQL(array(), $this->_tableName, 'delete', $this->getWhere());
        $this->query($sql, $bindParam);
        return $this->afrows();
    }

    /**
     * 根据条件删除记录
     * 注意：where结尾的函数使用的是新where规则
     * @param string|array $where
     * @param bool $prepare 是否开启prepare检测
     * @return bool|int
     * @throws \Exception 链接失败或SQL错误
     */
    public function delByWhere($where, $prepare = false)
    {
        if (empty($where)) {
            return false;
        }
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where   = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->where($where);
        $this->setLimit(0, 0);
        $sql = $this->subSQL(array(), $this->_tableName, 'delete', $this->getWhere());
        $this->query($sql, $bindParam);
        return $this->afrows();

    }

    /**
     * 根据条件删除记录
     * @param int $id
     * @param bool $prepare 是否开启prepare检测
     * @return bool|int
     * @throws \Exception 链接失败或SQL错误
     */
    public function delById($id, $prepare = false)
    {
        if (empty($id)) {
            return false;
        }

        $conditions = ["id" => $id];

        //prepare
        $bindParam = [];
        if ($prepare) {
            //condition
            $prepareInfo = $this->makePrepareCondition($conditions);
            $conditions  = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->setConditions($conditions);
        $sql = $this->subSQL(array(), $this->_tableName, 'delete', $this->getWhere());
        $this->query($sql, $bindParam);
        return $this->afrows();
    }

    /**
     * @param $id int id
     * @param string|array  要获取的字段
     * @param bool $prepare 是否开启prepare检测
     * @return  array|boolean
     * @throws \Exception SQL错误，链接失败，安全检测失败
     **/
    public function getById($id, $fields = array(), $prepare = false)
    {
        $bindParam = [];
        $condition = ["id" => $id];

        if ($prepare) {
            $prepareInfo = $this->makePrepareCondition($condition);
            $condition   = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->setConditions($condition);
        $this->setField($fields);
        $this->setLimit(0, 1);
        $this->setOrder("");
        return $this->getOne($bindParam);
    }

    /**
     * 根据ids获取多条记录
     * @param array $idArray id列表数组
     * @param array|string $fields 要获取的字段
     * @param bool $prepare 是否开启prepare检测
     * @return  array()
     * @throws \Exception SQL错误，链接失败，安全检测失败
     **/
    public function getByIdArray($idArray, $fields = array(), $prepare = false)
    {
        $bindParam = [];
        $condition = [["id", "in", $idArray]];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($condition);
            $condition   = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->where($condition);
        $this->setField($fields);
        $this->setLimit(0, 0);
        $this->setOrder("");
        return $this->getList($bindParam);
    }

    /**
     * 根据条件获取记录列表
     * @param array|string $conditions
     * @param array|string $fields
     * @param int $start offset
     * @param int $psize limit
     * @param string $order
     * @param bool $prepare 是否开启prepare检测
     * @return array
     * @throws \Exception SQL错误，链接失败，安全检测失败
     */
    public function getListByCondition($conditions = array(), $fields = array(), $start = 0, $psize = 20, $order = "", $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareCondition($conditions);
            $conditions  = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->setConditions($conditions);
        $this->setField($fields);
        $this->setOrder($order);
        $this->setLimit($start, $psize);
        return $this->getList($bindParam);
    }

    /**
     * 根据条件获取记录列表
     * 注意：where结尾的函数使用的是新where规则
     * @param array|string $where
     * @param array|string $fields
     * @param int $start offset
     * @param int $psize limit
     * @param string $order
     * @param bool $prepare 是否开启prepare检测
     * @return array|string
     * @throws \Exception SQL错误，链接失败，安全检测失败
     */
    public function getListByWhere($where = array(), $fields = array(), $start = 0, $psize = 20, $order = "", $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where  = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->where($where);
        $this->setField($fields);
        $this->setOrder($order);
        $this->setLimit($start, $psize);
        return $this->getList($bindParam);
    }

    /**
     * 根据条件获取多条记录
     * @param array|string $conditions
     * @param array|string $fields
     * @param string $order
     * @param bool $prepare 是否开启prepare检测
     * @return array|string|boolean
     * @throws \Exception SQL错误，链接失败，安全检测失败
     */
    public function getInfoByCondition($conditions = array(), $fields = array(), $order = '', $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareCondition($conditions);
            $conditions  = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->setConditions($conditions);
        $this->setField($fields);
        $this->setLimit(0, 1);
        $this->setOrder($order);
        return $this->getOne($bindParam);
    }

    /**
     * 根据条件获取多条记录
     * 注意：where结尾的函数使用的是新where规则
     * @param array|string $where
     * @param array|string $fields
     * @param string $order
     * @param bool $prepare 是否开启prepare检测
     * @return mixed
     * @throws \Exception SQL错误，链接失败，安全检测失败
     */
    public function getInfoByWhere($where = array(), $fields = array(), $order = '', $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where       = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->where($where);
        $this->setField($fields);
        $this->setLimit(0, 1);
        $this->setOrder($order);
        return $this->getOne($bindParam);
    }

    /**
     * 分页获取数据列表
     * @param array|string $conditions
     * @param int $start
     * @param int $limit
     * @param string|array $fields
     * @param string $order
     * @param bool $prepare 是否开启prepare检测
     * @return array
     * @throws \Exception SQL错误，链接失败，安全检测失败
     */
    public function getDataList($conditions = array(), $start = 0, $limit = 20, $fields = array(), $order = '', $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareCondition($conditions);
            $conditions  = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $item = array('psize' => $limit, 'skip' => $start, 'total' => 0, 'list' => array());
        $this->clean();
        $this->setConditions($conditions);
        $this->setField($fields);
        $this->setLimit($start, $limit);
        $this->setOrder($order);

        $item['total'] = $this->getSum($bindParam);
        $item['list']  = $this->getList($bindParam);
        return $item;
    }


    /**
     * 分页获取数据列表
     * 注意：where结尾的函数使用的是新where规则
     * @param array|string $where
     * @param int $start
     * @param int $limit
     * @param string|array $fields
     * @param string $order
     * @param bool $prepare 是否开启prepare检测
     * @return array
     * @throws \Exception SQL错误，链接失败，安全检测失败
     */
    public function getDataListByWhere($where = array(), $start = 0, $limit = 20, $fields = array(), $order = '', $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where       = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $item = array('psize' => $limit, 'skip' => $start, 'total' => 0, 'list' => array());
        $this->clean();
        $this->where($where);
        $this->setField($fields);
        $this->setLimit($start, $limit);
        $this->setOrder($order);

        $item['total'] = $this->getSum($bindParam);
        $item['list']  = $this->getList($bindParam);
        return $item;
    }


    /**
     * 统计符合条件数据个数
     * @param array|string $conditions
     * @param bool $prepare 是否开启prepare检测
     * @return string
     * @throws \Exception SQL错误，链接失败，安全检测失败
     */
    public function getCount($conditions = array(), $prepare = false)
    {
        //重置条件
        $this->clean();

        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareCondition($conditions);
            $conditions  = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->setConditions($conditions);
        return $this->getSum($bindParam);
    }

    /**
     * 统计符合条件数据个数
     * 注意：where结尾的函数使用的是新where规则
     * @param array|string $where
     * @param bool $prepare 是否开启prepare检测
     * @return string
     * @throws \Exception SQL错误，链接失败，安全检测失败
     */
    public function getCountByWhere($where = array(), $prepare = false)
    {
        //重置条件
        $this->clean();

        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where       = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->where($where);
        return $this->getSum($bindParam);
    }


    /**
     * 根据group分组count，并分页
     * @param string $group 分组字段，逗号分割
     * @param array $where where条件
     * @param int $offset 翻页offset
     * @param int $limit 一页数据个数
     * @param string $fields 统计字段，默认是count(*) as total
     * @param string $order 排序
     * @param bool $prepare 是否使用prepare预处理，默认关闭
     * @return array
     * @throws \Exception
     */
    public function getSumByGroupList($group = "", $where = array(), $offset = 0, $limit = 20, $fields = "count(*) as total", $order = '', $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where       = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $item = array('psize' => $limit, 'skip' => $offset, 'total' => 0, 'list' => array());

        $this->clean();
        $this->where($where);
        $this->setField($fields);
        $this->setGroup($group);
        $this->setLimit($offset, $limit);
        $this->setOrder($order);

        $item['total'] = $this->getSum($bindParam);
        $item['list']  = $this->getList($bindParam);
        return $item;
    }


    /**
     * 根据group分组count
     * @param string $group 分组字段，逗号分割
     * @param array $where where条件
     * @param int $offset 翻页offset
     * @param int $limit 一页数据个数
     * @param string $fields 统计字段，默认是count(*) as total
     * @param string $order 排序
     * @param bool $prepare 是否使用prepare预处理，默认关闭
     * @return array
     * @throws \Exception
     */
    public function getSumByGroup($group = "", $where = array(), $offset = 0, $limit = 20, $fields = "count(*) as total", $order = '', $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where       = $prepareInfo[0];
            $bindParam   = $prepareInfo[1];
        }

        $this->clean();
        $this->where($where);
        $this->setField($fields);
        $this->setGroup($group);
        $this->setLimit($offset, $limit);
        $this->setOrder($order);

        return $this->getList($bindParam);
    }

    /**
     * left join 并返回列表
     * @param string $right_table 右表名称
     * @param array $on left join条件 如:["id" => "id"] key会增加左表前缀，val会加右表前缀
     * @param array $where where条件 [["user",6],["sex",">=",3]]
     * @param string|array $fields 返回结果字段列表 如 "DISTINCT xes_collect_user.content_id,xes_collect_user.id"
     * @param int $offset 开始返回数据offset
     * @param int $limit 最大返回数据条数
     * @param string $order 如 "id desc,user_id asc"
     * @param bool $prepare 默认不开启 prepare
     * @return array
     * @throws \Exception
     */
    public function getLeftJoinListByWhere($right_table, $on, $where = array(), $fields = array(), $offset = 0, $limit = 20,
                                           $order = "", $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where = $prepareInfo[0];
            $bindParam = $prepareInfo[1];
        }

        $this->clean();
        $this->setRelationTable($right_table);
        $this->setField($fields);
        $this->setRelationOn($on);
        $this->where($where);
        $this->setOrder($order);
        $this->setLimit($offset, $limit);
        return $this->getList($bindParam);
    }


    /**
     * left join 并返回符合条件数据总个数
     * @param string $right_table 右表名称
     * @param array $on left join条件 如:["id" => "id"] key会增加左表前缀，val会加右表前缀
     * @param array $where where条件 [["user",6],["sex",">=",3]]
     * @param bool $prepare 默认不开启 prepare
     * @return int|boolean
     * @throws \Exception
     */
    public function getLeftJoinCountByWhere($right_table, $on, $where = array(), $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where = $prepareInfo[0];
            $bindParam = $prepareInfo[1];
        }

        $this->clean();
        $this->setRelationTable($right_table);
        $this->setField("count(*) as total");
        $this->setRelationOn($on);
        $this->where($where);
        $result = $this->getOne($bindParam);
        if ($result && isset($result["total"])) {
            return $result["total"];
        }

        return 0;
    }

    /**
     * left join Group having 返回列表
     * @param string|array $fields 返回结果字段列表 如 "DISTINCT xes_collect_user.content_id,xes_collect_user.id"
     * @param string $right_table 右表名称
     * @param array $on left join条件 如:["id" => "id"] key会增加左表前缀，val会加右表前缀
     * @param array $where where条件 [["user",6],["sex",">=",3]]
     * @param string $group 分组字段,隔开
     * @param array $having group having where
     * @param int $offset 开始返回数据offset
     * @param int $limit 最大返回数据条数
     * @param string $order 如 "id desc,user_id asc"
     * @param bool $prepare 默认不开启 prepare
     * @return array
     * @throws \Exception
     */
    public function getLeftJoinGroupHavingListByWhere($fields, $right_table, $on, $where = array(),
        $group = "", $having = [], $offset = 0, $limit = 20 , $order = "", $prepare = false)
    {
        $bindParam = [];

        if ($prepare) {
            $prepareInfo = $this->makePrepareWhere($where);
            $where = $prepareInfo[0];
            $bindParam = $prepareInfo[1];

            $prepareInfo = $this->makePrepareWhere($having);
            $having = $prepareInfo[0];
            $bindParam = array_merge($prepareInfo[1], $bindParam);
        }

        $this->clean();
        $this->setRelationTable($right_table);
        $this->setField($fields);
        $this->setRelationOn($on);
        $this->where($where);
        $this->setGroup($group);
        $this->having($having);
        $this->setOrder($order);
        $this->setLimit($offset, $limit);
        return $this->getList($bindParam);
    }
}
