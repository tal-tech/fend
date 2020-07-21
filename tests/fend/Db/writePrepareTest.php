<?php


namespace App\Test\Fend\Db;

use Fend\Write;
use PHPUnit\Framework\TestCase;

class writePrepareTest extends TestCase
{
    private $_table = 'users';
    private $_db = 'fend_test';

    public function testQuery()
    {
        $mod = Write::Factory($this->_table, $this->_db);

        //get by id
        $info = $mod->getById(3, ["id", "account", "user_name"], true);
        self::assertEquals(json_encode($info), '{"id":3,"account":"user1","user_name":"hehe1"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE  `id` = ? LIMIT 0,1","param":[3]}');

        //get by ids
        $infos = $mod->getByIdArray([3, 4, 5], "id, account, user_name", true);
        self::assertEquals(json_encode($infos), '[{"id":3,"account":"user1","user_name":"hehe1"},{"id":4,"account":"user2","user_name":"\u6d4b\u8bd5"},{"id":5,"account":"user3","user_name":"hehe3"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id, account, user_name FROM users WHERE   `id` in (?,?,?)","param":[3,4,5]}');

        //get List by condition and page
        $list = $mod->getListByCondition([">" => ["id" => 0]], ["id,account,user_name"], 1, 2, "id desc", true);
        self::assertEquals(json_encode($list), '[{"id":5,"account":"user3","user_name":"hehe3"},{"id":4,"account":"user2","user_name":"\u6d4b\u8bd5"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE  id > ?  ORDER BY id desc LIMIT 1,2","param":[0]}');

        //get info by condition
        $info = $mod->getInfoByCondition([">=" => ["id" => 5]], "id,user_name", "user_name asc", true);
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE  id >= ?  ORDER BY user_name asc LIMIT 0,1","param":[5]}');

        //get info by condition
        $list = $mod->getDataList([">=" => ["id" => 5]], 0, 2, "id,user_name", "user_name desc", true);
        self::assertEquals(json_encode($list), '{"psize":2,"skip":0,"total":"2","list":[{"id":6,"user_name":"hehe4"},{"id":5,"user_name":"hehe3"}]}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE  id >= ?  ORDER BY user_name desc LIMIT 0,2","param":[5]}');

        //get info by condition
        $count = $mod->getCount([">=" => ["id" => 3]], true);
        self::assertEquals($count, "4");
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT COUNT(*) AS total  FROM users WHERE  id >= ? ","param":[3]}');

        $group = $mod->getSumByGroup("user_name", [["id", ">=", 1]], 0, 10, "count(1) as total,id", "id asc", true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT count(1) as total,id FROM users WHERE   `id` >= ? GROUP BY user_name ORDER BY id asc LIMIT 0,10","param":[1]}');
        self::assertEquals(count($group), "4");

        $group = $mod->getSumByGroupList("user_name", [["id", ">=", 1]], 0, 10, "count(1) as total,id", "id asc", true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT count(1) as total,id FROM users WHERE   `id` >= ? GROUP BY user_name ORDER BY id asc LIMIT 0,10","param":[1]}');
        self::assertEquals($group["total"], "1");
    }

    public function testQueryNewWhere()
    {
        $mod = Write::Factory($this->_table, $this->_db);

        //get list by where and page
        $list = $mod->getListByWhere([["id", ">=", 0]], ["id,account,user_name"], 1, 2, "id desc", true);
        self::assertEquals(json_encode($list), '[{"id":5,"account":"user3","user_name":"hehe3"},{"id":4,"account":"user2","user_name":"\u6d4b\u8bd5"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE   `id` >= ? ORDER BY id desc LIMIT 1,2","param":[0]}');

        //get info by where
        $info = $mod->getInfoByWhere([["id", ">=", 5]], "id,user_name", "user_name asc", true);
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE   `id` >= ? ORDER BY user_name asc LIMIT 0,1","param":[5]}');

        //get info by where
        $list = $mod->getDataListByWhere([["id", ">=", 5]], 0, 2, "id,user_name", "user_name desc", true);
        self::assertEquals(json_encode($list), '{"psize":2,"skip":0,"total":"2","list":[{"id":6,"user_name":"hehe4"},{"id":5,"user_name":"hehe3"}]}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE   `id` >= ? ORDER BY user_name desc LIMIT 0,2","param":[5]}');

        //get info by where
        $count = $mod->getCountByWhere([["id", ">=", 3]], true);
        self::assertEquals($count, "4");
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT COUNT(*) AS total  FROM users WHERE   `id` >= ?","param":[3]}');

    }

    public function testAddDelUpdate()
    {
        $mod = Write::Factory($this->_table, $this->_db);

        //account, passwd, user_sex, user_name, create_time,update_time
        $data = [
            "account"     => "test1",
            "passwd"      => "fjdklsfjdkslhfjdk",
            "user_sex"    => 1,
            "user_name"   => "test1",
            "create_time" => 1565074615,
            "update_time" => 1565074615,
        ];

        //add test1
        $id = $mod->add($data, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"INSERT INTO users SET  `account` = ? , `passwd` = ? , `user_sex` = ? , `user_name` = ? , `create_time` = ? , `update_time` = ? ","param":["test1","fjdklsfjdkslhfjdk",1,"test1",1565074615,1565074615]}');
        self::assertNotEmpty($id);

        //update by id test
        $ret = $mod->editById($id, ["user_sex" => 2], true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"UPDATE users SET  `user_sex` = ?  WHERE  `id` = ?","param":[2,' . $id . ']}');
        self::assertEquals($ret, 1);

        //get by id
        $info = $mod->getById($id, '', true);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT * FROM users WHERE  `id` = ? LIMIT 0,1","param":[' . $id . ']}');
        self::assertEquals(json_encode($info), '{"account":"test1","passwd":"fjdklsfjdkslhfjdk","user_sex":2,"user_name":"test1"}');

        //del by id
        $ret = $mod->delById($id, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"delete FROM users WHERE  `id` = ?","param":[' . $id . ']}');
        self::assertEquals($ret, 1);

        //test by condition
        $data = [
            "account"     => "test2",
            "passwd"      => "fjdklsfjdkslhfjdk",
            "user_sex"    => 1,
            "user_name"   => "test2",
            "create_time" => 1565075472,
            "update_time" => 1565075472,
        ];

        //add test2
        $id2 = $mod->add($data, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"INSERT INTO users SET  `account` = ? , `passwd` = ? , `user_sex` = ? , `user_name` = ? , `create_time` = ? , `update_time` = ? ","param":["test2","fjdklsfjdkslhfjdk",1,"test2",1565075472,1565075472]}');
        self::assertNotEmpty($id2);

        //update by where
        $where = [
            "account" => "test2",
        ];
        $ret   = $mod->edit($where, ["passwd" => "ok"], true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"UPDATE users SET  `passwd` = ?  WHERE  `account` = ?","param":["ok","test2"]}');
        self::assertEquals($ret, 1);


        //get by id
        $info = $mod->getById($id2, '', true);

        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT * FROM users WHERE  `id` = ? LIMIT 0,1","param":[' . $id2 . ']}');
        self::assertEquals(json_encode($info), '{"account":"test2","passwd":"ok","user_sex":1,"user_name":"test2"}');

        //del by where
        $where = [
            "account" => "test2"
        ];
        $ret   = $mod->del($where, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"delete FROM users WHERE  `account` = ?","param":["test2"]}');
        self::assertEquals($ret, 1);

    }

    public function testAddDelUpdateNewWhere()
    {
        //test new where
        $mod = Write::Factory($this->_table, $this->_db);

        //test by condition
        $data = [
            "account"     => "test2",
            "passwd"      => "fjdklsfjdkslhfjdk",
            "user_sex"    => 1,
            "user_name"   => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test2
        $id2 = $mod->add($data, true);
        self::assertNotEmpty($id2);

        //update by where
        $where = [
            ["account", "test2"],
        ];
        $ret   = $mod->editByWhere($where, ["passwd" => "ok"], true);
        self::assertEquals($ret, 1);

        //get by id
        $info = $mod->getById($id2, '', true);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT * FROM users WHERE  `id` = ? LIMIT 0,1","param":[' . $id2 . ']}');
        self::assertEquals(json_encode($info), '{"account":"test2","passwd":"ok","user_sex":1,"user_name":"test2"}');

        //del by where
        $where = [
            ["account", "test2"]
        ];
        $ret   = $mod->delByWhere($where, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"delete FROM users WHERE   `account` = ?","param":["test2"]}');
        self::assertEquals($ret, 1);
    }


    ///////////////////////////////////////////
    /// PDO
    ///////////////////////////////////////////

    public function testPDOQuery()
    {
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");

        //get by id
        $info = $mod->getById(3, ["id", "account", "user_name"], true);
        self::assertEquals(json_encode($info), '{"id":3,"account":"user1","user_name":"hehe1"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE  `id` = ? LIMIT 0,1","param":[3]}');

        //get by ids
        $infos = $mod->getByIdArray([3, 4, 5], "id, account, user_name", true);
        self::assertEquals(json_encode($infos), '[{"id":3,"account":"user1","user_name":"hehe1"},{"id":4,"account":"user2","user_name":"\u6d4b\u8bd5"},{"id":5,"account":"user3","user_name":"hehe3"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id, account, user_name FROM users WHERE   `id` in (?,?,?)","param":[3,4,5]}');

        //get List by condition and page
        $list = $mod->getListByCondition([">" => ["id" => 0]], ["id,account,user_name"], 1, 2, "id desc", true);
        self::assertEquals(json_encode($list), '[{"id":5,"account":"user3","user_name":"hehe3"},{"id":4,"account":"user2","user_name":"\u6d4b\u8bd5"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE  id > ?  ORDER BY id desc LIMIT 1,2","param":[0]}');

        //get info by condition
        $info = $mod->getInfoByCondition([">=" => ["id" => 5]], "id,user_name", "user_name asc", true);
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE  id >= ?  ORDER BY user_name asc LIMIT 0,1","param":[5]}');

        //get info by condition
        $list = $mod->getDataList([">=" => ["id" => 5]], 0, 2, "id,user_name", "user_name desc", true);
        self::assertEquals(json_encode($list), '{"psize":2,"skip":0,"total":"2","list":[{"id":6,"user_name":"hehe4"},{"id":5,"user_name":"hehe3"}]}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE  id >= ?  ORDER BY user_name desc LIMIT 0,2","param":[5]}');

        //get info by condition
        $count = $mod->getCount([">=" => ["id" => 3]], true);
        self::assertEquals($count, "4");
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT COUNT(*) AS total  FROM users WHERE  id >= ? ","param":[3]}');

    }

    public function testPDOQueryNewWhere()
    {
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");

        //get list by where and page
        $list = $mod->getListByWhere([["id", ">=", 0]], ["id,account,user_name"], 1, 2, "id desc", true);
        self::assertEquals(json_encode($list), '[{"id":5,"account":"user3","user_name":"hehe3"},{"id":4,"account":"user2","user_name":"\u6d4b\u8bd5"}]');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,account,user_name FROM users WHERE   `id` >= ? ORDER BY id desc LIMIT 1,2","param":[0]}');

        //get info by where
        $info = $mod->getInfoByWhere([["id", ">=", 5]], "id,user_name", "user_name asc", true);
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE   `id` >= ? ORDER BY user_name asc LIMIT 0,1","param":[5]}');

        //get info by where
        $list = $mod->getDataListByWhere([["id", ">=", 5]], 0, 2, "id,user_name", "user_name desc", true);
        self::assertEquals(json_encode($list), '{"psize":2,"skip":0,"total":"2","list":[{"id":6,"user_name":"hehe4"},{"id":5,"user_name":"hehe3"}]}');
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT id,user_name FROM users WHERE   `id` >= ? ORDER BY user_name desc LIMIT 0,2","param":[5]}');

        //get info by where
        $count = $mod->getCountByWhere([["id", ">=", 3]], true);
        self::assertEquals($count, "4");
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT COUNT(*) AS total  FROM users WHERE   `id` >= ?","param":[3]}');

    }

    public function testPDOAddDelUpdate()
    {
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");

        //account, passwd, user_sex, user_name, create_time,update_time
        $data = [
            "account"     => "test1",
            "passwd"      => "fjdklsfjdkslhfjdk",
            "user_sex"    => 1,
            "user_name"   => "test1",
            "create_time" => 1565074615,
            "update_time" => 1565074615,
        ];

        //add test1
        $id = $mod->add($data, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"INSERT INTO users SET  `account` = ? , `passwd` = ? , `user_sex` = ? , `user_name` = ? , `create_time` = ? , `update_time` = ? ","param":["test1","fjdklsfjdkslhfjdk",1,"test1",1565074615,1565074615]}');
        self::assertNotEmpty($id);

        //update by id test
        $ret = $mod->editById($id, ["user_sex" => 2], true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"UPDATE users SET  `user_sex` = ?  WHERE  `id` = ?","param":[2,"' . $id . '"]}');
        self::assertEquals($ret, 1);

        //get by id
        $info = $mod->getById($id, '', true);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT * FROM users WHERE  `id` = ? LIMIT 0,1","param":["' . $id . '"]}');
        self::assertEquals(json_encode($info), '{"account":"test1","passwd":"fjdklsfjdkslhfjdk","user_sex":2,"user_name":"test1"}');

        //del by id
        $ret = $mod->delById($id, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"delete FROM users WHERE  `id` = ?","param":["' . $id . '"]}');
        self::assertEquals($ret, 1);

        //test by condition
        $data = [
            "account"     => "test2",
            "passwd"      => "fjdklsfjdkslhfjdk",
            "user_sex"    => 1,
            "user_name"   => "test2",
            "create_time" => 1565075472,
            "update_time" => 1565075472,
        ];

        //add test2
        $id2 = $mod->add($data, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"INSERT INTO users SET  `account` = ? , `passwd` = ? , `user_sex` = ? , `user_name` = ? , `create_time` = ? , `update_time` = ? ","param":["test2","fjdklsfjdkslhfjdk",1,"test2",1565075472,1565075472]}');
        self::assertNotEmpty($id2);

        //update by where
        $where = [
            "account" => "test2",
        ];
        $ret   = $mod->edit($where, ["passwd" => "ok"], true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"UPDATE users SET  `passwd` = ?  WHERE  `account` = ?","param":["ok","test2"]}');
        self::assertEquals($ret, 1);


        //get by id
        $info = $mod->getById($id2, '', true);

        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT * FROM users WHERE  `id` = ? LIMIT 0,1","param":["' . $id2 . '"]}');
        self::assertEquals(json_encode($info), '{"account":"test2","passwd":"ok","user_sex":1,"user_name":"test2"}');

        //del by where
        $where = [
            "account" => "test2"
        ];
        $ret   = $mod->del($where, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"delete FROM users WHERE  `account` = ?","param":["test2"]}');
        self::assertEquals($ret, 1);

    }

    public function testPDOAddDelUpdateNewWhere()
    {
        //test new where
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");

        //test by condition
        $data = [
            "account"     => "test2",
            "passwd"      => "fjdklsfjdkslhfjdk",
            "user_sex"    => 1,
            "user_name"   => "test2",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add test2
        $id2 = $mod->add($data, true);
        self::assertNotEmpty($id2);

        //update by where
        $where = [
            ["account", "test2"],
        ];
        $ret   = $mod->editByWhere($where, ["passwd" => "ok"], true);
        self::assertEquals($ret, 1);

        //get by id
        $info = $mod->getById($id2, '', true);
        unset($info["id"]);
        unset($info["create_time"]);
        unset($info["update_time"]);

        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"SELECT * FROM users WHERE  `id` = ? LIMIT 0,1","param":["' . $id2 . '"]}');
        self::assertEquals(json_encode($info), '{"account":"test2","passwd":"ok","user_sex":1,"user_name":"test2"}');

        //del by where
        $where = [
            ["account", "test2"]
        ];
        $ret   = $mod->delByWhere($where, true);
        self::assertEquals(json_encode($mod->getLastSQL()), '{"sql":"delete FROM users WHERE   `account` = ?","param":["test2"]}');
        self::assertEquals($ret, 1);

    }

    public function testPrepareMassAdd(){
        $mod = Write::Factory($this->_table, $this->_db, "Mysql");
        $data = [
            [
                "account"     => "test2",
                "passwd"      => "fjdklsfjdkslhfjdk",
                "user_sex"    => 1,
                "user_name"   => "test2",
                "create_time" => time(),
                "update_time" => time(),
            ],
            [
                "account"     => "test3",
                "passwd"      => "fjdklsfjdkslhfjdk",
                "user_sex"    => 2,
                "user_name"   => "test3",
                "create_time" => time(),
                "update_time" => time(),
            ],
        ];
        $ret = $mod->addMulti($data, true);
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

    public function testPDOMassAdd(){
        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");
        $data = [
            [
                "account"     => "test2",
                "passwd"      => "fjdklsfjdkslhfjdk",
                "user_sex"    => 1,
                "user_name"   => "test2",
                "create_time" => time(),
                "update_time" => time(),
            ],
            [
                "account"     => "test3",
                "passwd"      => "fjdklsfjdkslhfjdk",
                "user_sex"    => 2,
                "user_name"   => "test3",
                "create_time" => time(),
                "update_time" => time(),
            ],
        ];
        $ret = $mod->addMulti($data, true);
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


    public function testLeftJoin()
    {
        $mod = Write::Factory($this->_table, $this->_db);
        $result = $mod->getLeftJoinListByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], "users.id, users.account, users.user_name, user_info.score, user_info.gold", 0, 20, "", true);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ? LIMIT 0,20");
        self::assertEquals(json_encode($result), "[{\"id\":3,\"account\":\"user1\",\"user_name\":\"hehe1\",\"score\":10,\"gold\":1},{\"id\":4,\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":1222,\"gold\":2},{\"id\":6,\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":200,\"gold\":1},{\"id\":5,\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":123,\"gold\":0}]");

        $mod = Write::Factory($this->_table, $this->_db);
        $result = $mod->getLeftJoinCountByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], true);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT count(*) as total FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ?");
        self::assertEquals($result, "4");

        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");
        $result = $mod->getLeftJoinListByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], "users.id, users.account, users.user_name, user_info.score, user_info.gold", 0, 20, "", true);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ? LIMIT 0,20");
        self::assertEquals(json_encode($result), "[{\"id\":3,\"account\":\"user1\",\"user_name\":\"hehe1\",\"score\":10,\"gold\":1},{\"id\":4,\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":1222,\"gold\":2},{\"id\":6,\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":200,\"gold\":1},{\"id\":5,\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":123,\"gold\":0}]");

        $mod = Write::Factory($this->_table, $this->_db, "MysqlPDO");
        $result = $mod->getLeftJoinCountByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], true);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT count(*) as total FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ?");
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
            [["users.id", ">", "3"]],
            0,
            20,
            "",
            true
        );
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ? GROUP BY account Having   `users`.`id` > ? LIMIT 0,20");
        self::assertEquals(json_encode($result),"[{\"id\":4,\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":1222,\"gold\":2},{\"id\":6,\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":200,\"gold\":1},{\"id\":5,\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":123,\"gold\":0}]");
    }
}