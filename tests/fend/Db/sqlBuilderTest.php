<?php
declare(strict_types = 1);

namespace App\Test\Fend\Db;

use Fend\Read;
use Fend\Write;
use http\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

class moduleTest extends TestCase
{
    private $_table = 'users';
    private $_db = 'fend_test';

    public function testDateQueryCondition()
    {
        $mod = Read::Factory($this->_table, $this->_db);
        $conditions = [
            '>=' => [
                'created_at' => '2019-09-02'
            ]
        ];

        try{
            $list = $mod->getListByCondition($conditions);
        }catch(\Exception $e) {

        }
        self::assertEquals($mod->getLastSQL()["sql"],"SELECT * FROM users WHERE  created_at >= '2019-09-02'  LIMIT 0,20");

        $conditions = [
            "id" => "03883231207814667379197355882525",
        ];
        try{
            $list = $mod->getListByCondition($conditions);
        }catch(\Exception $e) {

        }
        self::assertEquals($mod->getLastSQL()["sql"],"SELECT * FROM users WHERE  `id` = '03883231207814667379197355882525' LIMIT 0,20");

    }

    public function testNewWhere()
    {
        $mod = Read::Factory($this->_table, $this->_db);

        $where = [
            "(",

            "(",
            ['fend_test.`db`.user_id', 14],
            ['users.user_name', 'oak'],
            ['`users`.user_id', ">=", 0],
            ")",

            "OR",

            "(",
            ['`user_id`', "<=", 10000],
            ['user_id', "like", '57%'],
            ")",

            ")",

            "OR",

            ['user_id', "in", [1, 2, 3, 4, 5, 6]],

            "OR",

            "(",
            ['user_id', "not in", ['a', 'c', 'd', 'f']],
            " `user_name` = 'yes' ",
            ")",
        ];
        $mod->where($where);
        $sql = $mod->getSql();
        self::assertEquals('SELECT  *  FROM users WHERE   (  (  `fend_test`.`db`.`user_id` = \'14\' AND `users`.`user_name` = \'oak\' AND `users`.`user_id` >= \'0\' )  OR   (  `user_id` <= \'10000\' AND `user_id` like \'57%\' )  )  OR   `user_id` in (\'1\',\'2\',\'3\',\'4\',\'5\',\'6\') OR   (  `user_id` not in (\'a\',\'c\',\'d\',\'f\') AND  `user_name` = \'yes\'  ) ', $sql);

    }

    public function testNoPrepareGetList()
    {
        $con   = array(">" => ["id" => 0], "user_sex" => 1);
        $field = array("*");
        $start = 0;
        $limit = 20;

        $mod = Read::Factory($this->_table, $this->_db);
        $mod->setConditions($con);
        $mod->setField($field);
        $mod->setLimit($start, $limit);
        $sql = $mod->getSql();

        self::assertEquals("SELECT * FROM users WHERE  id > '0'  AND `user_sex` = '1' LIMIT 0,20", $sql);

        $q     = $mod->query($sql);

        //return check
        self::assertNotEmpty($q);
        self::assertIsObject($q);


        self::assertInstanceOf(\mysqli_result::class, $q);

        self::assertEquals(2, $mod->getSum());

        while ($rs = $mod->fetch($q)) {
            $result['list'][] = $rs;
        }

        //result check
        self::assertEquals($result["list"][0]["id"], 3);
        self::assertEquals($result["list"][1]["id"], 5);

        $count = $mod->getSum();
        self::assertEquals(2, $count);
    }

    public function testNoPrepareGetListFirstIsNum()
    {
        $con   = array("user_sex" => 1, ">" => ["id" => 0]);
        $field = array("*");
        $start = 0;
        $limit = 20;

        $mod = Read::Factory($this->_table, $this->_db);
        $mod->setConditions($con);
        $mod->setField($field);
        $mod->setLimit($start, $limit);
        $sql = $mod->getSql();

        self::assertEquals("SELECT * FROM users WHERE  `user_sex` = '1' AND  id > '0'  LIMIT 0,20", $sql);
    }

    public function testPrepareGetList()
    {
        $con   = array(">" => ["id" => '?'], "user_sex" => '?');
        $bindparam = array(0, 1);
        $field = array("*");
        $start = 0;
        $limit = 20;

        $mod = Read::Factory($this->_table, $this->_db);
        $mod->setConditions($con);
        $mod->setField($field);
        $mod->setLimit($start, $limit);
        $sql = $mod->getSql();

        self::assertEquals("SELECT * FROM users WHERE  id > ?  AND `user_sex` = ? LIMIT 0,20", $sql);

        $q     = $mod->query($sql, $bindparam);

        //return check
        self::assertNotEmpty($q);
        self::assertIsObject($q);

        self::assertInstanceOf(\mysqli_result::class, $q);

        self::assertEquals(2, $mod->getSum($bindparam));

        while ($rs = $mod->fetch($q)) {
            $result['list'][] = $rs;
        }

        //result check
        self::assertEquals($result["list"][0]["id"], 3);
        self::assertEquals($result["list"][1]["id"], 5);

        $count = $mod->getSum($bindparam);
        self::assertEquals(2, $count);
    }

    public function testPrepareNewWhere()
    {
        $mod = Read::Factory($this->_table, $this->_db);

        $where = [
            "(",

            "(",
            ['fend_test.`db`.user_id', '?'],
            ['users.user_name', '?'],
            ['`users`.user_id', ">=", '?'],
            ")",

            "OR",

            "(",
            ['`user_id`', "<=", '?'],
            ['user_id', "like", '?'],
            ")",

            ")",

            "OR",

            ['user_id', "in", ['?', '?', '?', '?', '?', '?']],

            "OR",

            "(",
            ['user_id', "not in", ['?', '?', '?', '?']],
            " `user_name` = ? ",
            ")",

        ];

        $mod->where($where);
        $sql = $mod->getSql();
        self::assertEquals('SELECT  *  FROM users WHERE   (  (  `fend_test`.`db`.`user_id` = ? AND `users`.`user_name` = ? AND `users`.`user_id` >= ? )  OR   (  `user_id` <= ? AND `user_id` like ? )  )  OR   `user_id` in (?,?,?,?,?,?) OR   (  `user_id` not in (?,?,?,?) AND  `user_name` = ?  ) ', $sql);

    }


    ////////////////////////////////////////
    /// PDO
    ////////////////////////////////////////

    public function testPDONewWhere()
    {
        $mod = Read::Factory($this->_table, $this->_db, "MysqlPDO");

        $where = [
            "(",

            "(",
            ['fend_test.`db`.user_id', 14],
            ['users.user_name', 'oak'],
            ['`users`.user_id', ">=", 0],
            ")",

            "OR",

            "(",
            ['`user_id`', "<=", 10000],
            ['user_id', "like", '57%'],
            ")",

            ")",

            "OR",

            ['user_id', "in", [1, 2, 3, 4, 5, 6]],

            "OR",

            "(",
            ['user_id', "not in", ['a', 'c', 'd', 'f']],
            " `user_name` = 'yes' ",
            ")",

        ];
        $mod->where($where);
        $sql = $mod->getSql();
        self::assertEquals('SELECT  *  FROM users WHERE   (  (  `fend_test`.`db`.`user_id` = \'14\' AND `users`.`user_name` = \'oak\' AND `users`.`user_id` >= \'0\' )  OR   (  `user_id` <= \'10000\' AND `user_id` like \'57%\' )  )  OR   `user_id` in (\'1\',\'2\',\'3\',\'4\',\'5\',\'6\') OR   (  `user_id` not in (\'a\',\'c\',\'d\',\'f\') AND  `user_name` = \'yes\'  ) ', $sql);

    }

    public function testPDONoPrepareGetList()
    {
        $con   = array(">" => ["id" => 0], "user_sex" => 1);
        $field = array("*");
        $start = 0;
        $limit = 20;

        $mod = Read::Factory($this->_table, $this->_db, "MysqlPDO");
        $mod->setConditions($con);
        $mod->setField($field);
        $mod->setLimit($start, $limit);
        $sql = $mod->getSql();

        self::assertEquals("SELECT * FROM users WHERE  id > '0'  AND `user_sex` = '1' LIMIT 0,20", $sql);

        $q     = $mod->query($sql);

        //return check
        self::assertNotEmpty($q);
        self::assertIsObject($q);

        self::assertInstanceOf(\PDOStatement::class, $q);

        self::assertEquals(2, $mod->getSum());

        while ($rs = $mod->fetch($q)) {
            $result['list'][] = $rs;
        }

        //result check
        self::assertEquals($result["list"][0]["id"], 3);
        self::assertEquals($result["list"][1]["id"], 5);

        $count = $mod->getSum();
        self::assertEquals(2, $count);
    }

    public function testPDONoPrepareGetListFirstIsNum()
    {
        $con   = array("user_sex" => 1, ">" => ["id" => 0]);
        $field = array("*");
        $start = 0;
        $limit = 20;

        $mod = Read::Factory($this->_table, $this->_db, "MysqlPDO");
        $mod->setConditions($con);
        $mod->setField($field);
        $mod->setLimit($start, $limit);
        $sql = $mod->getSql();

        self::assertEquals("SELECT * FROM users WHERE  `user_sex` = '1' AND  id > '0'  LIMIT 0,20", $sql);
    }


    public function testPDOPrepareGetList()
    {
        $con   = array(">" => ["id" => '?'], "user_sex" => '?');
        $bindparam = array(0, 1);
        $field = array("*");
        $start = 0;
        $limit = 20;

        $mod = Read::Factory($this->_table, $this->_db, "MysqlPDO");
        $mod->setConditions($con);
        $mod->setField($field);
        $mod->setLimit($start, $limit);
        $sql = $mod->getSql();

        self::assertEquals("SELECT * FROM users WHERE  id > ?  AND `user_sex` = ? LIMIT 0,20", $sql);

        $q     = $mod->query($sql, $bindparam);

        //return check
        self::assertNotEmpty($q);
        self::assertIsObject($q);

        self::assertInstanceOf(\PDOStatement::class, $q);

        self::assertEquals(2, $mod->getSum($bindparam));

        while ($rs = $mod->fetch($q)) {
            $result['list'][] = $rs;
        }

        //result check
        self::assertEquals($result["list"][0]["id"], 3);
        self::assertEquals($result["list"][1]["id"], 5);

        $count = $mod->getSum($bindparam);
        self::assertEquals(2, $count);
    }

    public function testPDOPrepareNewWhere()
    {
        $mod = Read::Factory($this->_table, $this->_db, "MysqlPDO");

        $where = [
            "(",

            "(",
            ['fend_test.`db`.user_id', '?'],
            ['users.user_name', '?'],
            ['`users`.user_id', ">=", '?'],
            ")",

            "OR",

            "(",
            ['`user_id`', "<=", '?'],
            ['user_id', "like", '?'],
            ")",

            ")",

            "OR",

            ['user_id', "in", ['?', '?', '?', '?', '?', '?']],

            "OR",

            "(",
            ['user_id', "not in", ['?', '?', '?', '?']],
            " `user_name` = ? ",
            ")",

        ];

        $mod->where($where);
        $sql = $mod->getSql();
        self::assertEquals('SELECT  *  FROM users WHERE   (  (  `fend_test`.`db`.`user_id` = ? AND `users`.`user_name` = ? AND `users`.`user_id` >= ? )  OR   (  `user_id` <= ? AND `user_id` like ? )  )  OR   `user_id` in (?,?,?,?,?,?) OR   (  `user_id` not in (?,?,?,?) AND  `user_name` = ?  ) ', $sql);

    }

    private function getModuleInstance($table, $db, $style = 1)
    {
        if (1 === (int)$style) {
            return Read::Factory($table, $db);
        } else {
            return Read::Factory($table, $db, "MysqlPDO");
        }
    }

    public function testSetGetTable()
    {
        $resetTableName = 'user_info';
        $this->getModuleInstance($this->_table, $this->_db)->setTable($resetTableName,$this->_db);
        self::assertEquals($resetTableName,$this->getModuleInstance($resetTableName, $this->_db)->getTable());
    }

    public function testSqlSafe()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $mod->setSqlSafe(true);
        $sql = "select load_file('/tmp/1.txt')";

        try{
            $mod->query($sql);
        } catch(\Exception $e) {
            $res = $mod->getErrorInfo();
            $worker = 1;
        }
        self::assertEquals(1, $worker);
    }

    public function testSetWhere()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $mod->setWhere('id = 200');
        self::assertEquals('id = 200',$mod->getWhere());
        $mod->setWhere();
        self::assertEquals('',$mod->getWhere());
    }

    public function testSetGroup()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $mod->setGroup('id');
        self::assertEquals('SELECT  *  FROM users GROUP BY id',$mod->getSql());
    }

    public function testRelation()
    {
        $table = 'user_info';
        $mod = $this->getModuleInstance($this->_table, $this->_db);

        $mod->setRelationTable($table);

        $on = array(
            'id' => 'user_id'
        );
        $mod->setRelationOn($on);

        $field = array(
            'user_id',
            'score',
            'gold'
        );
        $mod->setRelationField($field);
        $conditions = 'users.id > 3';

        $mod->setRelationWhere($conditions);
        self::assertEquals('SELECT user_info.user_id,user_info.score,user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE users.id > 3',$mod->getSql());

        $mod->setField(array('users.id','users.account','users.user_name'));
        $on = 'users.id = user_info.user_id';
        $mod->setRelationOn($on);

        $conditions = ['>'=>['id'=>3], '=' => ['gold'=>'1']];
        $mod->setRelationWhere($conditions);
        self::assertEquals('SELECT users.id,users.account,users.user_name,user_info.user_id,user_info.score,user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE  `user_info`.`id` > \'3\'  AND  `user_info`.`gold` = \'1\' ',$mod->getSql());

        $infoMod = $this->getModuleInstance($table, $this->_db);
        $infoMod->setRelationTable('users');
        $infoMod->setRelationOn(array('user_id' => 'id'));
        $infoMod->setRelationField(array('id','account','user_name'));
        $conditions = ['>' => ['id' => 3], '=' =>['user_name' => 'hehe4']];
        $infoMod->setRelationWhere($conditions);
        $infoMod->setField($field);
        self::assertEquals('SELECT user_id,score,gold,users.id,users.account,users.user_name FROM user_info LEFT JOIN users ON user_info.user_id = users.id WHERE  `users`.`id` > \'3\'  AND  `users`.`user_name` = \'hehe4\' ', $infoMod->getSql());

        $conditions = ['id' => 6, 'user_name' => 'hehe4'];
        $infoMod->setRelationWhere($conditions);
        self::assertEquals('SELECT user_id,score,gold,users.id,users.account,users.user_name FROM user_info LEFT JOIN users ON user_info.user_id = users.id WHERE  `users`.`id` = \'6\' AND `users`.`user_name` = \'hehe4\'',$infoMod->getSql());

        $conditions = ['<' => ['user_sex' => 1] ,'id' => 6, 'user_name' => 'hehe4'];
        $infoMod->setRelationWhere($conditions);
        self::assertEquals('SELECT user_id,score,gold,users.id,users.account,users.user_name FROM user_info LEFT JOIN users ON user_info.user_id = users.id WHERE  `users`.`user_sex` < \'1\'  AND `users`.`id` = \'6\' AND `users`.`user_name` = \'hehe4\'', $infoMod->getSql());
    }

    public function testGet()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $sql = "select * from users where id = 1";
        $res = $mod->get($sql);
        self::assertEquals(null, $res);
    }

    public function testUseDb()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        self::assertArrayHasKey('test', array_flip($mod->fetch($mod->useDb('test')->query("select database()"))));
        $res = $mod->fetch(array());
        $mod->useDb('fend_test');
        self::assertEquals(false, $res);
    }

    public function testSetTimeout()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $mod->setTimeout();
        $worker = 1;
        self::assertEquals(1, $worker);
    }

    public function testGetErrorInfo()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        try{
            $mod->useDb('userssssss;');
        } catch(\Exception $e) {
            $res = $mod->getErrorInfo();
            $worker = 1;
        }
        self::assertEquals(1, $worker);
    }

    public function testSubSQL()
    {
        $mod = Write::Factory($this->_table, $this->_db);
        $data = [
            [
                "account"     => "test1",
                "passwd"      => "fjdklsfjdkslhfjdk",
                "user_sex"    => 1,
                "user_name"   => "test1",
                "create_time" => 1565074615,
                "update_time" => 1565074615
            ],
            [
                "account"     => "test2",
                "passwd"      => "fjdkl'sf",
                "user_sex"    => 1,
                "user_name"   => "test10",
                "create_time" => 1565074915,
                "update_time" => 1565074415
            ],
        ];

        //insertall
        $sql = $mod->subSQL($data, $this->_table,'insertall');
        self::assertEquals('INSERT INTO users (account,passwd,user_sex,user_name,create_time,update_time) VALUES (\'test1\',\'fjdklsfjdkslhfjdk\',\'1\',\'test1\',\'1565074615\',\'1565074615\'),(\'test2\',\'fjdkl\\\'sf\',\'1\',\'test10\',\'1565074915\',\'1565074415\')', $sql);

        //insetall conditions = null
        $sql = $mod->subSQL(array(), $this->_table,'insertall');
        self::assertEquals(null, $sql);

        //replace
        $data = array(
            "id"          => 7,
            "account"     => "test2",
            "passwd"      => "locobve",
            "user_sex"    => 1,
            "user_name"   => "test10",
            "create_time" => 1565074915,
            "update_time" => 1565074415
        );
        $sql = $mod->subSQL($data, $this->_table,'replace');
        self::assertEquals('REPLACE INTO users SET  `id` = \'7\' , `account` = \'test2\' , `passwd` = \'locobve\' , `user_sex` = \'1\' , `user_name` = \'test10\' , `create_time` = \'1565074915\' , `update_time` = \'1565074415\' ', $sql);

        //ifupdate  $duplicate = array()
        $sql = $mod->subSQL($data, $this->_table,'ifupdate', null, array());
        self::assertEquals('INSERT INTO users SET  `id` = \'7\' , `account` = \'test2\' , `passwd` = \'locobve\' , `user_sex` = \'1\' , `user_name` = \'test10\' , `create_time` = \'1565074915\' , `update_time` = \'1565074415\'  ON DUPLICATE KEY UPDATE  `id` = \'7\' , `account` = \'test2\' , `passwd` = \'locobve\' , `user_sex` = \'1\' , `user_name` = \'test10\' , `create_time` = \'1565074915\' , `update_time` = \'1565074415\' ', $sql);

        //ifupdate $duplicate = !empty
        $duplicate = array(
            "id"          => 7,
            "account"     => "test2",
            "passwd"      => "yngwie",
            "user_sex"    => 1,
            "user_name"   => "test10",
            "create_time" => 1565074915,
            "update_time" => 1565074415
        );
        $sql = $mod->subSQL($data, $this->_table,'ifupdate', null, $duplicate);
        self::assertEquals('INSERT INTO users SET  `id` = \'7\' , `account` = \'test2\' , `passwd` = \'locobve\' , `user_sex` = \'1\' , `user_name` = \'test10\' , `create_time` = \'1565074915\' , `update_time` = \'1565074415\'  ON DUPLICATE KEY UPDATE `id`=\'7\',`account`=\'test2\',`passwd`=\'yngwie\',`user_sex`=\'1\',`user_name`=\'test10\',`create_time`=\'1565074915\',`update_time`=\'1565074415\'',$sql);

        $duplicate = $mod->makePrepareData($data);
        $sql = $mod->subSQL($data, $this->_table,'ifupdate', null, $duplicate[0]);
        self::assertEquals('INSERT INTO users SET  `id` = \'7\' , `account` = \'test2\' , `passwd` = \'locobve\' , `user_sex` = \'1\' , `user_name` = \'test10\' , `create_time` = \'1565074915\' , `update_time` = \'1565074415\'  ON DUPLICATE KEY UPDATE `id`=?,`account`=?,`passwd`=?,`user_sex`=?,`user_name`=?,`create_time`=?,`update_time`=?',$sql);

        //default
        $sql = $mod->subSQL($data, $this->_table,'xxx');
        self::assertEquals(null, $sql);

        //make += operation
        $sql = $mod->subSQL(["create_time" => ["+", 1]],$this->_table,'update', [],[]);
        self::assertEquals('UPDATE users SET `create_time` = `create_time` + 1',$sql);

    }

    /**
     * 方法未完成
     */
    public function testGetSqlSum()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $mod->setRelationTable('user_info');
        $mod->setRelationOn(['id' => 'user_id']);
        $sql = $mod->getSqlSum();
        self::assertEquals('SELECT COUNT(*) AS total  FROM users LEFT JOIN user_info ON users.id = user_info.user_id', $sql);


        $mod->setRelationWhere('user_info.user_id > 3');
        $mod->setWhere('users.id > 3'); //setWhere before setRelationWhere
        $sql = $mod->getSqlSum();
        //todo:方法未完成
        //var_dump($sql);
        //self::assertEquals('', $sql);

        $mod->setWhere('');
        $sql = $mod->getSqlSum();
        self::assertEquals('SELECT COUNT(*) AS total  FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE user_info.user_id > 3', $sql);

        $mod->setGroup('id');
        $sql = $mod->getSqlSum();
        self::assertEquals('SELECT COUNT(*) AS total  FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE user_info.user_id > 3 GROUP BY id',$sql);
    }

    public function testDoQuerySafe()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $sql = "select * from users into outfile '/tmp/test.txt'";
        self::assertEquals(false, $mod->checkquery($sql));

        $sql = "select (@i:=@i+1) as rownum from users,(select @i:=1) as init /*sth here*/";
        self::assertEquals(false, $mod->checkquery($sql));

        $sql = "select (@i:=@i+1) as rownum from users,(select @i:=1) as init #sth here \n";
        self::assertEquals(false, $mod->checkquery($sql));

        //$sql = "select * from users -- sth here";
        //self::assertEquals(false, $mod->checkquery($sql));

        $sql = "select * from users where user_name like 0x736563757265";
        self::assertEquals(false, $mod->checkquery($sql));
    }

    public function testOthers()
    {
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $res = $mod->makePrepareCondition("id > 1");
        self::assertEquals(['id > 1',[]],$res);

        $res = $mod->makePrepareWhere(['id > 1']);
        self::assertEquals([['id > 1'],[]],$res);

        $mod->setConditions('id > 3');
        $worker = 1;
        self::assertEquals(1, $worker);

        $mod->setConditions(['>' => ['id' => 3], '=' =>['user_name' => 'hehe4']]);
        $worker = 2;
        self::assertEquals(2, $worker);

        $mod->setConditions(['id' => null]);
        $worker = 3;
        self::assertEquals(3, $worker);

        $table = 'user_info';
        $mod->setRelationTable($table);
        $on = array(
            'id' => 'user_id'
        );
        $mod->setRelationOn($on);
        $field = array(
            'user_id',
            'score',
            'gold'
        );
        $mod->setRelationField($field);
        $conditions = 'user_info.user_id > 3';
        $mod->setRelationWhere($conditions);
        $mod->setWhere('users.id > 3');
        self::assertEquals('SELECT user_info.user_id,user_info.score,user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE users.id > 3  AND user_info.user_id > 3', $mod->getSql());

        unset($mod);
        $mod = $this->getModuleInstance($this->_table, $this->_db);
        $where = [
            "(",

            "(",
            ['fend_test.`db`.user_id', 14],
            ['users.user_name', 'oak'],
            ['`users`.user_id', ">=", 0],
            ")",

            "OR",

            "(",
            ['`user_id`', "<=", 10000],
            ['user_id', "like", '57%'],
            ")",

            ")",

            "OR",

            ['user_id', "in", [1, 2, 3, 4, 5, 6]],

            "OR",

            "(",
            ['user_id', "not in", ['a', 'c', 'd', 'f']],
            " `user_name` = 'yes' ",
            ")",

            "AND",

            "'user_id' != 0"

        ];
        $mod->where($where);
        $sql = $mod->getSql();
        self::assertEquals("SELECT  *  FROM users WHERE   (  (  `fend_test`.`db`.`user_id` = '14' AND `users`.`user_name` = 'oak' AND `users`.`user_id` >= '0' )  OR   (  `user_id` <= '10000' AND `user_id` like '57%' )  )  OR   `user_id` in ('1','2','3','4','5','6') OR   (  `user_id` not in ('a','c','d','f') AND  `user_name` = 'yes'  )  AND   'user_id' != 0",$sql);
    }

    public function testLock()
    {
        $con = array(">" => ["id" => 0], "user_sex" => 1);
        $field = array("*");
        $start = 0;
        $limit = 20;

        $mod = Read::Factory($this->_table, $this->_db);
        $sql = $mod->clean()->setConditions($con)->setField($field)->setLimit($start, $limit)->lock()->getSql();
        self::assertEquals($sql, "SELECT * FROM users WHERE  id > '0'  AND `user_sex` = '1' LIMIT 0,20 FOR UPDATE");

        $sql = $mod->clean()->setConditions($con)->setField($field)->setLimit($start, $limit)->shareLock()->getSql();
        self::assertEquals($sql, "SELECT * FROM users WHERE  id > '0'  AND `user_sex` = '1' LIMIT 0,20 LOCK IN SHARE MODE");
    }
}