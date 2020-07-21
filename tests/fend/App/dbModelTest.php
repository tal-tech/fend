<?php
declare(strict_types=1);

namespace Test\App;
include_once("DemoDbModel.php");

use Fend\Cache;
use Fend\Queue\Exception;
use PHPUnit\Framework\TestCase;

class dbModelTest extends TestCase
{

    public function testNullInsert()
    {
        $model = DemoDbModel::factory();
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
        $model = DemoDbModel::factory();
        $model->setCacheTime(120);

        $cacheModel = Cache::factory(Cache::CACHE_TYPE_REDIS, "fend_test");

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

        //test getInfoById inject flag on cache to test read from cache
        $key = $model->getCacheKeyPrefix() . "_info_" . $id;
        $cacheResult = json_decode($cacheModel->get($key), true);
        self::assertNotEmpty($cacheResult);
        $cacheResult["cache"] = 1;
        $ret = $cacheModel->set($key, json_encode($cacheResult));
        self::assertTrue($ret);
        $result = $model->getInfoById($id);
        self::assertEquals($result["cache"], 1);

        //test getinfo by Id array
        $model->getInfoByIdArray([3, 4, 5, 6]);
        $key = $model->getCacheKeyPrefix() . "_idarr_9cf3b90cd135569e6d93136706a155f5";
        $cacheResult = json_decode($cacheModel->get($key), true);
        self::assertNotEmpty($cacheResult);
        $cacheResult["data"]["cache"] = 1;
        $ret = $cacheModel->set($key, json_encode($cacheResult));
        self::assertTrue($ret);
        $result = $model->getInfoByIdArray([3, 4, 5, 6]);
        self::assertEquals($result["cache"], 1);
        self::assertArrayHasKey("cache", $result);

        //add data 2
        $id2 = $model->add($data);
        self::assertNotEmpty($id2);

        //data changed. cache must expire
        $result = $model->getInfoByIdArray([3, 4, 5, 6]);
        self::assertArrayNotHasKey("cache", $result);

        //check getlist
        $result = $model->getListByCondition(["account" => "test1"], "");
        $result = $model->getListByCondition(["account" => "test1"], "");
        $result2 = $model->getListByCondition(["account" => "test1"], "", 0, 20, "", false);
        self::assertNotEmpty($result);
        self::assertNotEmpty($result2);
        self::assertEquals(count($result), 2);
        self::assertEquals($result, $result2);

        //get list by where
        $result = $model->getListByWhere([["account", "test1"]], [], 0, 20, "");
        $result = $model->getListByWhere([["account", "test1"]], [], 0, 20, "");
        $result2 = $model->getListByCondition(["account" => "test1"], "", 0, 20, "", false);
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
        $model = new DemoDbModel();
        $model->setCacheTime(120);

        $cacheModel = Cache::factory(Cache::CACHE_TYPE_REDIS, "fend_test");

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

        $info = $model->getInfoById($id, "", false);
        $cache = $cacheModel->get("tb_default_users_info_$id");
        $cache = json_decode($cache, true);
        self::assertEquals($cache["id"], $id);
        self::assertEquals($info, $cache);

        //add data 2
        $id2 = $model->add($data);
        self::assertNotEmpty($id2);

        $model->forceWrite(true);
        $info = $model->getInfoById($id2, "", false);
        $cache = $cacheModel->get("tb_default_users_info_$id2");
        $cache = json_decode($cache, true);
        self::assertEquals($cache["id"], $id2);
        self::assertEquals($info, $cache);
        $model->forceWrite(false);


        //test param validate

        $ret = $model->updateByCondition([], ["user_name" => "test2"]);
        self::assertFalse($ret);
        $ret = $model->updateByCondition(["user_name" => "test1"], []);
        self::assertFalse($ret);

        //update by condition
        $ret = $model->updateByCondition(["user_name" => "test1"], ["user_name" => "test2"]);
        self::assertEquals($ret, 2);

        $info = $model->getInfoById($id, "", false);
        $cache = $cacheModel->get("tb_default_users_info_$id");
        $cache = json_decode($cache, true);
        self::assertEquals($cache["user_name"], "test2");
        self::assertEquals($info, $cache);

        $info = $model->getInfoById($id2, "", true);
        $cache = $cacheModel->get("tb_default_users_info_$id2");
        $cache = json_decode($cache, true);
        self::assertEquals($cache["user_name"], "test2");
        self::assertEquals($info, $cache);

        //validate param
        $ret = $model->updateByWhere([["user_name", "test2"]], []);
        self::assertFalse($ret);
        $ret = $model->updateByWhere([], ["user_name" => "test3"]);
        self::assertFalse($ret);

        //update by where
        $ret = $model->updateByWhere([["user_name", "test2"]], ["user_name" => "test3"]);
        self::assertEquals($ret, 2);

        $info = $model->getInfoById($id, "", false);
        $cache = $cacheModel->get("tb_default_users_info_$id");
        $cache = json_decode($cache, true);
        self::assertEquals($cache["user_name"], "test3");
        self::assertEquals($info, $cache);

        $info = $model->getInfoById($id2, "", true);
        $cache = $cacheModel->get("tb_default_users_info_$id2");
        $cache = json_decode($cache, true);
        self::assertEquals($cache["user_name"], "test3");
        self::assertEquals($info, $cache);

        //update by id
        $ret = $model->updateById($id, ["user_name" => "test4"]);
        $info = $model->getInfoById($id, "", false);
        $cache = $cacheModel->get("tb_default_users_info_$id");
        $cache = json_decode($cache, true);
        self::assertEquals($ret, 1);
        self::assertEquals($info, $cache);

        $ret = $model->updateById($id2, ["user_name" => "test5"]);
        $info = $model->getInfoById($id2, "", true);
        $cache = $cacheModel->get("tb_default_users_info_$id2");
        $cache = json_decode($cache, true);
        self::assertEquals($ret, 1);
        self::assertEquals($info, $cache);

        //del by Condition
        $ret = $model->delByCondition(["user_name" => "test4"]);
        $cache = $cacheModel->get("tb_default_users_info_$id");
        self::assertEquals($ret, 1);
        self::assertEquals($cache, '');

        //del by Where
        $ret = $model->delByWhere([["user_name", "test5"]]);
        $cache = $cacheModel->get("tb_default_users_info_$id2");
        self::assertEquals($ret, 1);
        self::assertEquals($cache, '');

        //del by id
        $ret = $model->delById($id);
        self::assertEquals($ret, 0);

        $ret = $model->delById($id2);
        self::assertEquals($ret, 0);

        $ret = $model->getSumByGroup("user_name", [], 0, 2);
        self::assertEquals(count($ret), 2);
        self::assertEquals(json_encode($ret), "[{\"total\":\"1\"},{\"total\":\"1\"}]");

        $ret = $model->getSumByGroup("user_name", [], 0, 2);
        self::assertEquals(count($ret), 2);
        self::assertEquals(json_encode($ret), "[{\"total\":\"1\"},{\"total\":\"1\"}]");

        $ret = $model->getSumByGroupList("user_name", [], 0, 2);
        self::assertEquals(count($ret), 4);
        self::assertEquals(json_encode($ret), "{\"psize\":2,\"skip\":0,\"total\":\"1\",\"list\":[{\"total\":\"1\"},{\"total\":\"1\"}]}");

        $ret = $model->getSumByGroupList("user_name", [], 0, 2);
        self::assertEquals(count($ret), 4);
        self::assertEquals(json_encode($ret), "{\"psize\":2,\"skip\":0,\"total\":\"1\",\"list\":[{\"total\":\"1\"},{\"total\":\"1\"}]}");
    }

    public function testInfoChange()
    {
        $model = new DemoDbModel();
        $model->setCacheTime(120);

        //get info by where
        $count = $model->getCountByWhere([["id", ">=", 3]], true);
        self::assertEquals($count, "4");

        //get info by where cache
        $count = $model->getCountByWhere([["id", ">=", 3]], true);
        self::assertEquals($count, "4");

        //get info by condition
        $count = $model->getCount([">=" => ["id" => 3]], true);
        self::assertEquals($count, "4");
        //get info by condition cache
        $count = $model->getCount([">=" => ["id" => 3]], true);
        self::assertEquals($count, "4");

        //get info by where
        $info = $model->getInfoByWhere([["id", ">=", 5]], "id,user_name", "user_name asc", true);
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');

        //get info by where cache
        $info = $model->getInfoByWhere([["id", ">=", 5]], "id,user_name", "user_name asc", true);
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');

        //get info by condition
        $info = $model->getInfoByCondition([">=" => ["id" => 5]], "id,user_name", true);
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');

        //get info by condition cache
        $info = $model->getInfoByCondition([">=" => ["id" => 5]], "id,user_name", true);
        self::assertEquals(json_encode($info), '{"id":5,"user_name":"hehe3"}');

    }

    public function testOtherSetting()
    {
        $model = new DemoDbModel();
        $model->setCacheTime(120);

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
        $model = new DemoDbModel();

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
        $model = new DemoDbModel();

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
        $model = new DemoDbModel();

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

    public function testUpdateDelData()
    {
        $model = new DemoDbModel();
        //$model->setCacheTime(120);

        $cacheModel = Cache::factory(Cache::CACHE_TYPE_REDIS, "fend_test");

        $data = [
            "account" => "test1",
            "passwd" => "fjdklsfjdkslhfjdk",
            "user_sex" => 1,
            "user_name" => "test1",
            "create_time" => time(),
            "update_time" => time(),
        ];

        //add data
        $id = $model->add($data);
        self::assertNotEmpty($id);

        //fetch and put cache
        $ret = $model->getInfoById($id, "", true);
        self::assertNotEmpty($ret);

        //fetch from cache
        $ret = $model->getInfoById($id, "", true);
        self::assertNotEmpty($ret);

        //del and remove cache
        $ret = $model->delById($id);
        self::assertEquals($ret, 1);

        //check cache removed
        $ret = $model->getInfoById($id, "", true);
        self::assertEmpty($ret);

        //check false for wrong input
        $ret = $model->getInfoById(0, "", true);
        self::assertFalse($ret);

        $data = [
            [
                "account" => "test1",
                "passwd" => "123456",
                "user_sex" => 1,
                "user_name" => "test1",
                "create_time" => time(),
                "update_time" => time(),
            ],
            [
                "account" => "test2",
                "passwd" => "123456",
                "user_sex" => 1,
                "user_name" => "test1",
                "create_time" => time(),
                "update_time" => time(),
            ],
        ];

        $affect = $model->addMulti($data);
        self::assertEquals($affect, 2);

        //clean the more
        $affect = $model->delByWhere([["id", ">", 6]]);
        self::assertEquals($affect, 2);

        //fetch twice for save cache by getInfo
        $ret = $model->getInfoById(3, "", true);
        self::assertNotEmpty($ret);
        
        $ret = $model->getInfoById(3, "", true);
        self::assertNotEmpty($ret);
    }

    public function testMultipleModelTransaction()
    {
        //测试多个Model事务回滚
        $data = [
            "account" => "xcl@test.com",
            "passwd" => "user_test_pwd",
            "user_sex" => 1,
            "user_name" => "test1",
            "create_time" => time(),
            "update_time" => time(),
        ];

        $model1 = new DemoDbModel();
        $model2 = new DemoDbModel();
        $model1->forceWrite(true);
        $model2->forceWrite(true);

        $model1->transaction();

        $id1 = $model1->add($data);
        $id2 = $model2->add($data);

        self::assertNotEmpty($id1);
        self::assertNotEmpty($id2);

        $info1 = $model1->getInfoById($id1, [], false);
        $info2 = $model1->getInfoById($id2, [], false);

        self::assertNotEmpty($info1);
        self::assertNotEmpty($info2);

        $model2->rollBack();

        $info1 = $model1->getInfoById($id1, [], false);
        $info2 = $model1->getInfoById($id2, [], false);

        self::assertEmpty($info1);
        self::assertEmpty($info2);

    }


    public function testLeftJoingGroupHaving()
    {
        $mod = new DemoDbModel();
        $result = $mod->getLeftJoinGroupHavingListByWhere(
            "users.id, users.account, users.user_name, user_info.score, user_info.gold",
            "user_info", ["id" => "user_id"], [["users.id", ">", 0]],
            "account", [["users.id", ">", "3"]]);
        $sql = $mod->getLastSQL();
        self::assertEquals($sql["sql"], "SELECT users.id, users.account, users.user_name, user_info.score, user_info.gold FROM users LEFT JOIN user_info ON users.id = user_info.user_id WHERE   `users`.`id` > ? GROUP BY account Having   `users`.`id` > ? LIMIT 0,20");
        self::assertEquals(json_encode($result),"[{\"id\":4,\"account\":\"user2\",\"user_name\":\"\u6d4b\u8bd5\",\"score\":1222,\"gold\":2},{\"id\":6,\"account\":\"user4\",\"user_name\":\"hehe4\",\"score\":200,\"gold\":1},{\"id\":5,\"account\":\"user3\",\"user_name\":\"hehe3\",\"score\":123,\"gold\":0}]");
    }
}