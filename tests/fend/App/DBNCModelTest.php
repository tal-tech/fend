<?php
declare(strict_types = 1 );

namespace Test\App;
include_once("DemoDBNCModel.php");
use Fend\Cache;
use PHPUnit\Framework\TestCase;

class DBNCModelTest extends TestCase
{


    public function testNullInsert()
    {
        $model = DemoDBNCModel::factory();
        $data = [
            "account" => "test1",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => NULL,
            "user_name" => "test1",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add data 1
        $id = $model->add($data);
        self::assertNotEmpty($id);
        $sql = $model->getLastSQL(true);

        $data = $model->getInfoById($id);
        self::assertEquals($data["user_sex"], NULL);

        $ret = $model->delById($id);
        self::assertNotEmpty($ret);

    }

    public function testDbModelQueryCacheTest()
    {
        $model = DemoDBNCModel::factory();

        $data = [
            "account" => "test1",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test1",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add data 1
        $id = $model->add($data);
        self::assertNotEmpty($id);

        //get info
        $result = $model->getInfoById($id);
        self::assertNotEmpty($result);

        //test getinfo by Id array
        $result = $model->getInfoByIdArray([3, 4, 5, 6]);
        self::assertNotEmpty($result);

        //add data 2
        $id2 = $model->add($data);
        self::assertNotEmpty($id2);

        //data changed. cache must expire
        $result = $model->getInfoByIdArray([3, 4, 5, 6]);
        self::assertArrayNotHasKey("cache", $result);

        //check getlist
        $result = $model->getListByCondition(["account" => "test1"], "");
        $result2 = $model->getListByCondition(["account" => "test1"], "", 0, 20, "");
        self::assertNotEmpty($result);
        self::assertNotEmpty($result2);
        self::assertEquals(count($result), 2);
        self::assertEquals($result, $result2);

        //get list by where
        $result = $model->getListByWhere([["account", "test1"]], [], 0, 20, "");
        $result2 = $model->getListByCondition(["account" => "test1"], "", 0, 20, "");
        self::assertNotEmpty($result);
        self::assertNotEmpty($result2);
        self::assertEquals(count($result), 2);
        self::assertEquals($result, $result2);

        //del by id
        $ret = $model->delById($id);
        self::assertEquals($ret, 1);

        //del by id 2
        $ret = $model->delById($id2);
        self::assertEquals($ret, 1);

    }

    public function testDbModelModifyCacheTest()
    {
        $model = new DemoDBNCModel();

        $data = [
            "account" => "test1",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test1",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add data 1
        $id = $model->add($data);
        self::assertNotEmpty($id);

        $info = $model->getInfoById($id, "");
        self::assertNotEmpty($info);

        //add data 2
        $id2 = $model->add($data);
        self::assertNotEmpty($id2);

        $model->forceWrite(true);
        $info = $model->getInfoById($id2, "");
        self::assertNotEmpty($info);

        $model->forceWrite(false);


        //test param validate

        $ret = $model->updateByCondition([], ["user_name" => "test2"]);
        self::assertFalse($ret);
        $ret = $model->updateByCondition(["user_name" => "test1"], []);
        self::assertFalse($ret);

        //update by condition
        $ret = $model->updateByCondition(["user_name" => "test1"], ["user_name" => "test2"]);
        self::assertEquals($ret, 2);

        $info = $model->getInfoById($id, "");
        self::assertEquals($info["user_name"], "test2");

        $info = $model->getInfoById($id2, "");
        self::assertEquals($info["user_name"], "test2");

        //validate param
        $ret = $model->updateByWhere([["user_name", "test2"]], []);
        self::assertFalse($ret);
        $ret = $model->updateByWhere([], ["user_name" => "test3"]);
        self::assertFalse($ret);

        //update by where
        $ret = $model->updateByWhere([["user_name", "test2"]], ["user_name" => "test3"]);
        self::assertEquals($ret, 2);

        $info = $model->getInfoById($id, "");
        self::assertEquals($info["user_name"], "test3");

        $info = $model->getInfoById($id2, "");
        self::assertEquals($info["user_name"], "test3");

        //update by id
        $ret = $model->updateById($id, ["user_name" => "test4"]);
        self::assertEquals($ret, 1);
        $info = $model->getInfoById($id, "");
        self::assertEquals($info["user_name"], "test4");

        $ret = $model->updateById($id2, ["user_name" => "test5"]);
        self::assertEquals($ret, 1);
        $info = $model->getInfoById($id2, "");
        self::assertEquals($info["user_name"], "test5");


        //del by Condition
        $ret = $model->delByCondition(["user_name" => "test4"]);
        self::assertEquals($ret, 1);

        //del by Where
        $ret = $model->delByWhere([["user_name", "test5"]]);
        self::assertEquals($ret, 1);

        //del by id
        $ret = $model->delById($id);
        self::assertEquals($ret, 0);

        $ret = $model->delById($id2);
        self::assertEquals($ret, 0);

    }

    public function testInfoChange()
    {
        $model = new DemoDBNCModel();

        //get info by where
        $count = $model->getCountByWhere([["id", ">=", 3]]);
        self::assertEquals($count, "4");

        //get info by condition
        $count = $model->getCount([">=" => ["id" => 3]]);
        self::assertEquals($count, "4");

        //get info by where
        $info = $model->getInfoByWhere([["id", ">=", 5]], "id,user_name", "user_name asc");
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');

        //get info by condition cache
        $info = $model->getInfoByCondition([">=" => ["id" => 5]], "id,user_name");
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');

    }

    public function testOtherSetting()
    {
        $model = new DemoDBNCModel();

        //test getTableName
        $ret = $model->getTableName();
        self::assertEquals($ret, "users");

        //open prepare test
        $model->openPrepare(true);

        //test transaction
        $testSuccess = 1;
        try {
            $model->addUser();
        } catch (\Exception $e) {
            $testSuccess = 0;
        }
        self::assertEquals($testSuccess, 1);

        //transaction
        $ret = $model->transaction();
        self::assertNotFalse($ret);

        //add false
        $id = $model->add([]);
        self::isFalse($id);

        $data = [
            "account" => "test",
            "passwd" => "123",
            "user_sex" => 1,
            "user_name" => "xcl",
            "create_time" => time(),
            "update_time" => time()
        ];
        $id = $model->add($data);
        self::assertNotEmpty($id);

        $ret = $model->rollBack();
        self::assertNotFalse($ret);

        $ret = $model->delById($id);
        self::isFalse($ret);

        $ret = $model->getAffectRow();
        self::assertEquals($ret, 0);

        //transaction by manual
        $ret = $model->transaction();
        self::assertNotFalse($ret);

        $id = $model->add($data);
        self::assertNotEmpty($id);

        $ret = $model->commit();
        self::assertNotFalse($ret);

        //get last id
        $ret = $model->getLastInsertId();
        self::assertEquals($ret, 0);

        //del dirty data
        $ret = $model->delById($id);
        self::isTrue($ret);

        //get affect row
        $ret = $model->getAffectRow();
        self::assertEquals($ret, 1);

        //get last check
        $ret = $model->getLastSQL(true);
        self::assertEquals($ret["sql"], "delete FROM users WHERE  `id` = ?");

        //read sql get
        $ret = $model->getLastSQL();
        self::assertNotEmpty($ret);

    }

    public function testFilterData()
    {
        $model = new DemoDbNCModel();

        $model->setFieldList([
            "test_str" => "string",
            "test_int" => "int",
            "test_float" => "float",
            "test_double" => "double",
        ]);

        $result = $model->filterFieldData([
            "test_str" => "abcd",
            "test_int" => "123",
            "test_float" => "34.123",
            "test_double" => "7.8",
        ]);

        self::assertNotEmpty($result);
        self::assertTrue(is_string($result["test_str"]));
        self::assertTrue(is_int($result["test_int"]));
        self::assertTrue(is_float($result["test_float"]));
        self::assertTrue(is_double($result["test_double"]));

    }

    public function testFilterDataEmpty()
    {
        $model = new DemoDbNCModel();

        $model->setFieldList([]);

        $define = [
            "test_str" => "abcd",
            "test_int" => "123",
            "test_float" => "34.123",
            "test_double" => "7.8",
        ];

        $result = $model->filterFieldData($define);

        self::assertEquals($result, $define);

    }

    public function testFilterDataIsArray()
    {
        $model = new DemoDbNCModel();

        //错误用法
        $model->setFieldList([
            "test_str" => ["string"],
        ]);

        $define = [
            "test_str" => ["d","e"],
        ];

        $fire = 0;

        try {
            $model->filterFieldData($define);
        }catch (\Exception $e) {
            $fire = 1;
        }

        self::assertEquals($fire, 1);
    }


    public function testLeftJoingGroupHaving()
    {
        $mod = new DemoDbNCModel();

        $result = $mod->getLeftJoinGroupHavingListByWhere(
            "users.id, users.account, users.user_name, user_info.score, user_info.gold",
            "user_info",
            ["id" => "user_id"], [["users.id", ">", 0]],
            "account", [["users.id", ">", "3"]]);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ? GROUP BY account Having   `users`.`id` > ? LIMIT 0,20");
        self::assertEquals(json_encode($result),"[{\"id\":4,\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":1222,\"gold\":2},{\"id\":6,\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":200,\"gold\":1},{\"id\":5,\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":123,\"gold\":0}]");
    }

}