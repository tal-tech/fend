<?php


namespace App\Test\Fend\Db;

use Fend\Write;
use PHPUnit\Framework\TestCase;

class writeTest extends TestCase
{
    private $_table = 'users';
    private $_db = 'fend_test';

    public function testQuery()
    {
        $mod = Write::Factory($this->_table, $this->_db);

        //get by id
        $info = $mod->getById(3, ["id", "account", "user_name"]);
        self::assertEquals(json_encode($info), '{"id":"3","account":"user1","user_name":"hehe1"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE  `id` = \'3\' LIMIT 0,1","param":[]}');

        //get by ids
        $infos = $mod->getByIdArray([3, 4, 5], "id, account, user_name");
        self::assertEquals(json_encode($infos), '[{"id":"3","account":"user1","user_name":"hehe1"},{"id":"4","account":"user2","user_name":"\u6d4b\u8bd5"},{"id":"5","account":"user3","user_name":"hehe3"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id, account, user_name FROM users WHERE   `id` in (\'3\',\'4\',\'5\')","param":[]}');

        //get List by condition and page
        $list = $mod->getListByCondition([">" => ["id" => 0]], ["id,account,user_name"], 1, 2, "id desc");
        self::assertEquals(json_encode($list), '[{"id":"5","account":"user3","user_name":"hehe3"},{"id":"4","account":"user2","user_name":"\u6d4b\u8bd5"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE  id > \'0\'  ORDER BY id desc LIMIT 1,2","param":[]}');

        //get info by condition
        $info = $mod->getInfoByCondition([">=" => ["id" => 5]], "id,user_name", "user_name asc");
        self::assertEquals(json_encode($info), '{"id":"5","user_name":"hehe3"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE  id >= \'5\'  ORDER BY user_name asc LIMIT 0,1","param":[]}');

        //get info by condition
        $list = $mod->getDataList([">=" => ["id" => 5]], 0, 2, "id,user_name", "user_name desc");
        self::assertEquals(json_encode($list), '{"psize":2,"skip":0,"total":"2","list":[{"id":"6","user_name":"hehe4"},{"id":"5","user_name":"hehe3"}]}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE  id >= \'5\'  ORDER BY user_name desc LIMIT 0,2","param":[]}');

        //get info by condition
        $count = $mod->getCount([">=" => ["id" => 3]]);
        self::assertEquals($count, "4");
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT COUNT(*) AS total  FROM users WHERE  id >= \'3\' ","param":[]}');
    }

    public function testQueryNewWhere()
    {
        $mod = Write::Factory($this->_table, $this->_db);

        //get list by where and page
        $list = $mod->getListByWhere([["id", ">=", 0]], ["id,account,user_name"], 1, 2, "id desc");
        self::assertEquals(json_encode($list), '[{"id":"5","account":"user3","user_name":"hehe3"},{"id":"4","account":"user2","user_name":"\u6d4b\u8bd5"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE   `id` >= \'0\' ORDER BY id desc LIMIT 1,2","param":[]}');

        //get info by where
        $info = $mod->getInfoByWhere([["id", ">=", 5]], "id,user_name", "user_name asc");
        self::assertEquals(json_encode($info), '{"id":"5","user_name":"hehe3"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE   `id` >= \'5\' ORDER BY user_name asc LIMIT 0,1","param":[]}');

        //get info by where
        $list = $mod->getDataListByWhere([["id", ">=", 5]], 0, 2, "id,user_name", "user_name desc");
        self::assertEquals(json_encode($list), '{"psize":2,"skip":0,"total":"2","list":[{"id":"6","user_name":"hehe4"},{"id":"5","user_name":"hehe3"}]}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE   `id` >= \'5\' ORDER BY user_name desc LIMIT 0,2","param":[]}');

        //get info by where
        $count = $mod->getCountByWhere([["id", ">=", 3]]);
        self::assertEquals($count, "4");
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT COUNT(*) AS total  FROM users WHERE   `id` >= \'3\'","param":[]}');
    }

    public function testAddDelUpdate()
    {
        $mod = Write::Factory($this->_table, $this->_db);

        //account, passwd, user_sex, user_name, create_time,update_time
        $data = [
            "account" => "test1",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test1",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test1
        $id = $mod->add($data);
        self::assertNotEmpty($id);

        //update by id test
        $ret = $mod->editById($id, ["user_sex" => 2]);
        self::assertEquals($ret, 1);

        //get by id
        $info = $mod->getById($id);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($info), '{"account":"test1","passwd":"fjdklsfjdkslhfjdk","user_sex":"2","user_name":"test1"}');

        //del by id
        $ret = $mod->delById($id);
        self::assertEquals($ret, 1);

        //test by condition
        $data = [
            "account" => "test2",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test2
        $id2 = $mod->add($data);
        self::assertNotEmpty($id2);

        //update by where
        $where = [
            "account" => "test2",
        ];
        $ret = $mod->edit($where, ["passwd" => "ok"]);
        self::assertEquals($ret, 1);


        //get by id
        $info = $mod->getById($id2);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($info), '{"account":"test2","passwd":"ok","user_sex":"1","user_name":"test2"}');

        //del by where
        $where = [
            "account" => "test2"
        ];
        $ret = $mod->del($where);
        self::assertEquals($ret, 1);

    }

    public function testAddDelUpdateNewWhere()
    {
        //test new where
        $mod = Write::Factory($this->_table, $this->_db);

        //test by condition
        $data = [
            "account" => "test2",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test2
        $id2 = $mod->add($data);
        self::assertNotEmpty($id2);

        //update by where
        $where = [
            ["account", "test2"],
        ];
        $ret = $mod->editByWhere($where, ["passwd" => "ok"]);
        self::assertEquals($ret, 1);

        //get by id
        $info = $mod->getById($id2);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($info), '{"account":"test2","passwd":"ok","user_sex":"1","user_name":"test2"}');

        //del by where
        $where = [
            ["account", "test2"]
        ];
        $ret = $mod->delByWhere($where);
        self::assertEquals($ret, 1);

    }

    public function testCheckTrastion()
    {
        $mod = Write::Factory($this->_table, $this->_db);
        $mod->trans_begin();

        //test by condition
        $data = [
            "account" => "test2",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test2
        $id = $mod->add($data);
        self::assertNotEmpty($id);

        //update by where
        $where = [
            ["account", "test2"],
        ];
        $ret = $mod->editByWhere($where, ["passwd" => "ok"]);
        self::assertEquals($ret, 1);

        $mod->trans_rollback();

        //get by id
        $info = $mod->getById($id);
        self::assertEquals($info, FALSE);


        //test by condition
        $data = [
            "account" => "test2",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];
        //begin
        $mod->trans_begin();

        //add test2
        $id = $mod->add($data);
        self::assertNotEmpty($id);

        //commit
        $mod->trans_commit();
        //del by where
        $where = [
            ["account", "test2"]
        ];
        $ret = $mod->delByWhere($where);
        self::assertEquals($ret, 1);

    }

    //////////////////////////
    /// PDO
    //////////////////////////

    public function testPDOQuery()
    {
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");

        //get by id
        $info = $mod->getById(3, ["id", "account", "user_name"]);
        self::assertEquals(json_encode($info), '{"id":3,"account":"user1","user_name":"hehe1"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE  `id` = \'3\' LIMIT 0,1","param":[]}');

        //get by ids
        $infos = $mod->getByIdArray([3, 4, 5], "id, account, user_name");
        self::assertEquals(json_encode($infos), '[{"id":3,"account":"user1","user_name":"hehe1"},{"id":4,"account":"user2","user_name":"\u6d4b\u8bd5"},{"id":5,"account":"user3","user_name":"hehe3"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id, account, user_name FROM users WHERE   `id` in (\'3\',\'4\',\'5\')","param":[]}');

        //get List by condition and page
        $list = $mod->getListByCondition([">" => ["id" => 0]], ["id,account,user_name"], 1, 2, "id desc");
        self::assertEquals(json_encode($list), '[{"id":5,"account":"user3","user_name":"hehe3"},{"id":4,"account":"user2","user_name":"\u6d4b\u8bd5"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE  id > \'0\'  ORDER BY id desc LIMIT 1,2","param":[]}');

        //get info by condition
        $info = $mod->getInfoByCondition([">=" => ["id" => 5]], "id,user_name", "user_name asc");
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE  id >= \'5\'  ORDER BY user_name asc LIMIT 0,1","param":[]}');

        //get info by condition
        $list = $mod->getDataList([">=" => ["id" => 5]], 0, 2, "id,user_name", "user_name desc");
        self::assertEquals(json_encode($list), '{"psize":2,"skip":0,"total":"2","list":[{"id":6,"user_name":"hehe4"},{"id":5,"user_name":"hehe3"}]}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE  id >= \'5\'  ORDER BY user_name desc LIMIT 0,2","param":[]}');

        //get info by condition
        $count = $mod->getCount([">=" => ["id" => 3]]);
        self::assertEquals($count, "4");
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT COUNT(*) AS total  FROM users WHERE  id >= \'3\' ","param":[]}');
    }

    public function testPDOQueryNewWhere()
    {
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");

        //get list by where and page
        $list = $mod->getListByWhere([["id", ">=", 0]], ["id,account,user_name"], 1, 2, "id desc");
        self::assertEquals(json_encode($list), '[{"id":5,"account":"user3","user_name":"hehe3"},{"id":4,"account":"user2","user_name":"\u6d4b\u8bd5"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE   `id` >= \'0\' ORDER BY id desc LIMIT 1,2","param":[]}');

        //get info by where
        $info = $mod->getInfoByWhere([["id", ">=", 5]], "id,user_name", "user_name asc");
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE   `id` >= \'5\' ORDER BY user_name asc LIMIT 0,1","param":[]}');

        //get info by where
        $list = $mod->getDataListByWhere([["id", ">=", 5]], 0, 2, "id,user_name", "user_name desc");
        self::assertEquals(json_encode($list), '{"psize":2,"skip":0,"total":"2","list":[{"id":6,"user_name":"hehe4"},{"id":5,"user_name":"hehe3"}]}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE   `id` >= \'5\' ORDER BY user_name desc LIMIT 0,2","param":[]}');

        //get info by where
        $count = $mod->getCountByWhere([["id", ">=", 3]]);
        self::assertEquals($count, "4");
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT COUNT(*) AS total  FROM users WHERE   `id` >= \'3\'","param":[]}');
    }

    public function testPDOAddDelUpdate()
    {
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");

        //account, passwd, user_sex, user_name, create_time,update_time
        $data = [
            "account" => "test1",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test1",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test1
        $id = $mod->add($data);
        self::assertNotEmpty($id);

        //update by id test
        $ret = $mod->editById($id, ["user_sex" => 2]);
        self::assertEquals($ret, 1);

        //get by id
        $info = $mod->getById($id);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($info), '{"account":"test1","passwd":"fjdklsfjdkslhfjdk","user_sex":2,"user_name":"test1"}');

        //del by id
        $ret = $mod->delById($id);
        self::assertEquals($ret, 1);

        //test by condition
        $data = [
            "account" => "test2",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test2
        $id2 = $mod->add($data);
        self::assertNotEmpty($id2);

        //update by where
        $where = [
            "account" => "test2",
        ];
        $ret = $mod->edit($where, ["passwd" => "ok"]);
        self::assertEquals($ret, 1);


        //get by id
        $info = $mod->getById($id2);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($info), '{"account":"test2","passwd":"ok","user_sex":1,"user_name":"test2"}');

        //del by where
        $where = [
            "account" => "test2"
        ];
        $ret = $mod->del($where);
        self::assertEquals($ret, 1);

    }

    public function testPDOAddDelUpdateNewWhere()
    {
        //test new where
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");

        //test by condition
        $data = [
            "account" => "test2",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test2
        $id2 = $mod->add($data);
        self::assertNotEmpty($id2);

        //update by where
        $where = [
            ["account", "test2"],
        ];
        $ret = $mod->editByWhere($where, ["passwd" => "ok"]);
        self::assertEquals($ret, 1);

        //get by id
        $info = $mod->getById($id2);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($info), '{"account":"test2","passwd":"ok","user_sex":1,"user_name":"test2"}');

        //del by where
        $where = [
            ["account", "test2"]
        ];
        $ret = $mod->delByWhere($where);
        self::assertEquals($ret, 1);
    }

    public function testPDOCheckTrastion()
    {
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");
        $mod->trans_begin();

        //test by condition
        $data = [
            "account" => "test2",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test2
        $id = $mod->add($data);
        self::assertNotEmpty($id);

        //update by where
        $where = [
            ["account", "test2"],
        ];
        $ret = $mod->editByWhere($where, ["passwd" => "ok"]);
        self::assertEquals($ret, 1);

        $mod->trans_rollback();

        //get by id
        $info = $mod->getById($id);
        self::assertEquals($info, FALSE);


        //test by condition
        $data = [
            "account" => "test2",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];
        //begin
        $mod->trans_begin();

        //add test2
        $id = $mod->add($data);
        self::assertNotEmpty($id);

        //commit
        $mod->trans_commit();
        //del by where
        $where = [
            ["account", "test2"]
        ];
        $ret = $mod->delByWhere($where);
        self::assertEquals($ret, 1);

    }

    public function testMassAdd()
    {
        $mod = Write::Factory($this->_table, $this->_db, "Mysql");
        $data = [
            [
                "account" => "test2",
                "passwd" => "fjdklsfjdkslhfjdk",
                "user_sex" => 1,
                "user_name" => "test2",
                "create_time" => time(),
                "update_time" => time(),
            ],
            [
                "account" => "test3",
                "passwd" => "fjdklsfjdkslhfjdk",
                "user_sex" => 2,
                "user_name" => "test3",
                "create_time" => time(),
                "update_time" => time(),
            ],
        ];
        $ret = $mod->addMulti($data);
        self::assertEquals($ret, 2);
        //del by where
        $where = [
            ["account", "test2"],
            "OR",
            ["account", "test3"],
        ];
        $ret = $mod->delByWhere($where);
        self::assertEquals($ret, 2);
    }

    public function testPDOMassAdd()
    {
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");
        $data = [
            [
                "account" => "test2",
                "passwd" => "fjdklsfjdkslhfjdk",
                "user_sex" => 1,
                "user_name" => "test2",
                "create_time" => time(),
                "update_time" => time(),
            ],
            [
                "account" => "test3",
                "passwd" => "fjdklsfjdkslhfjdk",
                "user_sex" => 2,
                "user_name" => "test3",
                "create_time" => time(),
                "update_time" => time(),
            ],
        ];
        $ret = $mod->addMulti($data);
        self::assertEquals($ret, 2);

        //del by where
        $where = [
            ["account", "test2"],
            "OR",
            ["account", "test3"],
        ];
        $ret = $mod->delByWhere($where);
        self::assertEquals($ret, 2);
    }

    public function testWriteParam()
    {
        $mod = Write::Factory($this->_table, $this->_db);

        //get module obj
        $obj = $mod->getModule();
        self::assertNotEmpty($obj);

        //check add param
        $ret = $mod->add(array());
        self::assertFalse($ret);

        $testSuccess = 0;
        //check add error
        try {
            $mod->add(array("abc" => "1"));
        } catch (\Exception $e) {
            $testSuccess = 1;
        }
        self::assertEquals($testSuccess, 1);

        //check addmulti param
        $ret = $mod->addMulti(array());
        self::assertFalse($ret);

        //type check
        $ret = $mod->addMulti("abc");
        self::assertFalse($ret);

        //check edit param
        $ret = $mod->edit(array(), array("key" => 1));
        self::assertFalse($ret);

        //check edit param
        $ret = $mod->edit(array("key" => 1), array());
        self::assertFalse($ret);

        //check edit by where param
        $ret = $mod->editByWhere(array(), array("key" => 1));
        self::assertFalse($ret);

        $ret = $mod->editByWhere(array("key" => 1), array());
        self::assertFalse($ret);

        //check edit by id param
        $ret = $mod->editById(1, array());
        self::assertFalse($ret);

        $ret = $mod->editByWhere(0, array("a" => 2));
        self::assertFalse($ret);

        //check del id param
        $ret = $mod->del(array());
        self::assertFalse($ret);

        //check del by where param
        $ret = $mod->delByWhere(array());
        self::assertFalse($ret);

        //check del by id param
        $ret = $mod->delById(array());
        self::assertFalse($ret);

    }

    public function testLeftJoin()
    {
        $mod = Write::Factory($this->_table, $this->_db);
        $result = $mod->getLeftJoinListByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], "users.id, users.account, users.user_name, user_info.score, user_info.gold");
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > '0' LIMIT 0,20");
        self::assertEquals(json_encode($result), "[{\"id\":\"3\",\"account\":\"user1\",\"user_name\":\"hehe1\",\"score\":\"10\",\"gold\":\"1\"},{\"id\":\"4\",\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":\"1222\",\"gold\":\"2\"},{\"id\":\"6\",\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":\"200\",\"gold\":\"1\"},{\"id\":\"5\",\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":\"123\",\"gold\":\"0\"}]");

        $mod = Write::Factory($this->_table, $this->_db);
        $result = $mod->getLeftJoinCountByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]]);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT count(*) as total FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > '0'");
        self::assertEquals($result, "4");

        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");
        $result = $mod->getLeftJoinListByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], "users.id, users.account, users.user_name, user_info.score, user_info.gold");
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > '0' LIMIT 0,20");
        self::assertEquals(json_encode($result), "[{\"id\":3,\"account\":\"user1\",\"user_name\":\"hehe1\",\"score\":10,\"gold\":1},{\"id\":4,\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":1222,\"gold\":2},{\"id\":6,\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":200,\"gold\":1},{\"id\":5,\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":123,\"gold\":0}]");

        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");
        $result = $mod->getLeftJoinCountByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]]);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT count(*) as total FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > '0'");
        self::assertEquals($result, "4");

    }

    public function testLeftJoingGroupHaving()
    {
        $mod = Write::Factory($this->_table, $this->_db);
        $result = $mod->getLeftJoinGroupHavingListByWhere(
            "users.id, users.account, users.user_name, user_info.score, user_info.gold",
            "user_info",
            ["id" => "user_id"],
            [["users.id", ">", 0]],
            "account",
            [["users.id", ">", "3"]]
        );
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > '0' GROUP BY account Having   `users`.`id` > '3' LIMIT 0,20");
        self::assertEquals(json_encode($result),"[{\"id\":\"4\",\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":\"1222\",\"gold\":\"2\"},{\"id\":\"6\",\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":\"200\",\"gold\":\"1\"},{\"id\":\"5\",\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":\"123\",\"gold\":\"0\"}]");
    }
}