<?php


namespace App\Test\Fend\Db;

use Fend\Read;
use Fend\Write;
use http\Exception\RuntimeException;
use phpDocumentor\Reflection\DocBlock;
use PHPUnit\Framework\TestCase;

class mysqlTest extends TestCase
{
    private $_table = 'users';
    private $_db = 'fend_test';

    private function getModuleInstance($table, $db, $style = 1)
    {
        if (1 === (int)$style) {
            return Read::Factory($table, $db);
        } else {
            return Read::Factory($table, $db, "MysqlPDO");
        }
    }

    public function testClose()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        self::assertEquals(true, $mod->getModule()->close());
        $mod->getModule()->connect();
    }

    public function testGetDb()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $worker = 0;
        if(null != $mod->getModule()->getDb()) {
            $worker = 1;
        }
        self::assertEquals(1, $worker);
    }

    /**
     * situations: $name = null
     */
    public function testUseDb()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $mod->getModule()->useDb();
        $worker = 1;
        self::assertEquals(1, $worker);
    }

    public function testGetall()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $sql = "select * from users where id > 1";
        $res = $mod->getModule()->getall($sql);
        $worker = 0;
        if (is_array($res) && !empty($res)) {
            $worker = 1;
        }
        self::assertEquals(1, $worker);
    }

    public function testGetTableList()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $res = $mod->getModule()->getTableList($this->_db);
        self::assertArrayHasKey('users', array_flip($res));
        self::assertArrayHasKey('user_info', array_flip($res));
    }

    public function testGetDbFields()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $res = $mod->getModule()->getDbFields($this->_table);
        $worker = 0;
        if (is_array($res) && !empty($res)) {
            $worker = 1;
        }
        self::assertEquals(1, $worker);
    }

    public function testSqlTB()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $str = <<<EOF
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(30) NOT NULL,
  `passwd` varchar(30) NOT NULL,
  `user_sex` tinyint(4) DEFAULT NULL,
  `user_name` varchar(30) NOT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT
EOF;
        $worker = 0;
        if (false !== strpos($mod->getModule()->sqlTB($this->_table),$str)) {
            $worker = 1;
        }
        self::assertEquals(1, $worker);
    }

    public function testOptimizeTable()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $mod->getModule()->optimizeTable($this->_table,'user_info');
        $worker = 1;
        self::assertEquals(1, $worker);
    }

    public function testReRows()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $res = $mod->getModule()->query("SELECT * FROM users");
        self::assertEquals(4, $mod->getModule()->reRows($res));
        $mod->getModule()->refree($res);
    }

    public function testBadSQL()
    {
        //测试错误sql
        $yes = 0;
        try {
            $mod = $this->getModuleInstance($this->_table, $this->_db);
            $mod->query("select1 from user", [1]);
        } catch (\Exception $e) {
            $yes = 1;
        }
        self::assertEquals(1, $yes);

        //测试错误链接
        $yes = 0;
        try {
            $mod = $this->getModuleInstance($this->_table, "fend_test_broken");
            $mod->query("select1 from user");
        } catch (\Exception $e) {
            $yes = 1;
        }
        self::assertEquals(1, $yes);

        //sleep(20);
        //测试关闭期间报错
        $yes = 0;
        try {
            $mod = $this->getModuleInstance($this->_table, $this->_db);

            $mod->query("select1 from user", [1]);
        } catch (\Exception $e) {
            $yes = 1;
        }

        self::assertEquals(1, $yes);

    }
}