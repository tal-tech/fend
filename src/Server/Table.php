<?php

namespace Fend\Server;
/**
 * Fend Framework
 * 缓存
 **/
class Table
{
    private $_table = null;
    private $_dumpFile = "";     // dumppath 日志落地位置需填写绝对路径，未设置不dump
    private $_tableSize = 2048;

    /**
     * Fend_MemoryTable constructor.
     * table创建 columns为列定义、dumppath为tabledump数据文件路径、table最大记录数
     * @param array $columns 数组key、type、len，定义table列
     * @param string $dumpPath 备份地址
     * @param int $tableSize 最大数据量
     * @param float $proportion hash 空间预留比例
     * @throws \Exception
     */
    function __construct($columns, $dumpPath, $tableSize, $proportion = 0.2)
    {
        $this->_dumpFile  = $dumpPath;
        $this->_tableSize = $tableSize;

        $table = new \swoole_table($this->_tableSize, $proportion);
        foreach ($columns as $col) {
            if ($col["type"] == "string") {
                $type = \Swoole\Table::TYPE_STRING;
            } elseif ($col["type"] == "int") {
                $type = \Swoole\Table::TYPE_INT;
            } elseif ($col["type"] == "float") {
                $type = \Swoole\Table::TYPE_FLOAT;
            } else {
                throw new \Exception("table column of wrong type ..");
            }
            $table->column($col["key"], $type, $col["len"]);
        }
        $table->create();
        $this->_table = $table;
    }

    /**
     * 从文件中加载table数据到内存中
     * 用于从文件恢复table数据
     */
    public function loadTableRecord()
    {
        if ($this->_dumpFile == "") {
            return;
        }

        $record = '';
        if (file_exists($this->_dumpFile)) {
            //load table data from file
            $record = file_get_contents($this->_dumpFile);
        }

        //Util::log("Load Table Record:" . $this->_dumppath . " " . (($record !== FALSE) ? "Success" : "Fail"));
        if ($record) {
            $record = json_decode($record, true);
            foreach ($record as $k => $item) {
                $this->_table->set($k, $item);
            }
        }
    }

    /**
     * 备份当前table数据到文件，根据参数过滤掉指定前缀数据或key
     */
    public function dumpTableRecord()
    {
        if ($this->_dumpFile == "") {
            return;
        }

        //table store to the file
        $statics = array();
        foreach ($this->_table as $k => $v) {
            //过滤掉指定前缀数据或指定key数据
            $statics[$k] = $v;
        }
        file_put_contents($this->_dumpFile, json_encode($statics));
    }


    /**
     * 根据前缀搜索数据，并返回列表
     * @param $prefix
     * @return array
     */
    public function getListByPrefix($prefix)
    {
        $pidCountList = array();
        foreach ($this->_table as $k => $v) {
            if (stripos($k, $prefix) === 0) {
                $pidCountList[$k] = $v;
            }
        }
        return $pidCountList;
    }

    /**
     * 获取一个key
     * @param $key
     * @return array
     */
    public function get($key)
    {
        return $this->_table->get($key);
    }

    /**
     * 获得某个key下的某个field
     * @param $key
     * @param $field
     * @return string/int/float or false
     * */
    public function getField($key, $field)
    {
        $col = $this->_table->get($key);
        if (!$col) {
            return false;
        }
        return $col[$field];
    }

    /**
     * 修改key值
     * @param string $key
     * @param array $val
     * @return bool
     */
    public function set($key, $val)
    {
        return $this->_table->set($key, $val);
    }

    /**
     * 删除key
     * @param $key
     * @return bool
     */
    public function del($key)
    {
        return $this->_table->del($key);
    }

    /**
     * 获取所有kv值
     * @return array
     */
    public function getList()
    {
        $list = array();
        foreach ($this->_table as $k => $v) {
            $list[$k] = $v;
        }
        return $list;
    }

}
