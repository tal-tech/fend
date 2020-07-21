<?php


namespace App\Test\Fend\Db;

use Fend\Read;
use PHPUnit\Framework\TestCase;

class readPrepareTest extends TestCase
{
    private $_table = 'users';
    private $_db = 'fend_test';

    public function testQuery()
    {
        $mod = Read::Factory($this->_table, $this->_db);

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
        $mod = Read::Factory($this->_table, $this->_db);

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

    ////////////////////////////
    /// PDO
    ////////////////////////////

    public function testPDOQuery()
    {
        $mod = Read::Factory($this->_table, $this->_db, "MysqlPDO");

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
        $mod = Read::Factory($this->_table, $this->_db, "MysqlPDO");

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


    public function testLeftJoin()
    {
        $mod = Read::Factory($this->_table, $this->_db);
        $result = $mod->getLeftJoinListByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], "users.id, users.account, users.user_name, user_info.score, user_info.gold", 0, 20, "", true);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ? LIMIT 0,20");
        self::assertEquals(json_encode($result), "[{\"id\":3,\"account\":\"user1\",\"user_name\":\"hehe1\",\"score\":10,\"gold\":1},{\"id\":4,\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":1222,\"gold\":2},{\"id\":6,\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":200,\"gold\":1},{\"id\":5,\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":123,\"gold\":0}]");

        $mod = Read::Factory($this->_table, $this->_db);
        $result = $mod->getLeftJoinCountByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], true);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT count(*) as total FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ?");
        self::assertEquals($result, "4");

        $mod = Read::Factory($this->_table, $this->_db, "MysqlPDO");
        $result = $mod->getLeftJoinListByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], "users.id, users.account, users.user_name, user_info.score, user_info.gold", 0, 20, "", true);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ? LIMIT 0,20");
        self::assertEquals(json_encode($result), "[{\"id\":3,\"account\":\"user1\",\"user_name\":\"hehe1\",\"score\":10,\"gold\":1},{\"id\":4,\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":1222,\"gold\":2},{\"id\":6,\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":200,\"gold\":1},{\"id\":5,\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":123,\"gold\":0}]");

        $mod = Read::Factory($this->_table, $this->_db, "MysqlPDO");
        $result = $mod->getLeftJoinCountByWhere("user_info", ["id" => "user_id"], [["users.id", ">", 0]], true);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT count(*) as total FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ?");
        self::assertEquals($result, "4");

    }

    public function testLeftJoingGroupHaving()
    {
        $mod = Read::Factory($this->_table, $this->_db);
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