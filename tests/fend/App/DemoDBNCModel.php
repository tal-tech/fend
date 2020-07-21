<?php

namespace Test\App;

use Fend\App\DBNCModel;
use Fend\Cache;

class DemoDBNCModel extends DBNCModel
{

    //string 数据库表名, 继承根据需要覆盖
    protected $_table = 'users';

    // string 数据库配置名称, 继承根据需要覆盖
    protected $_db = 'default';


    //bool $_preapare 是否开启prepare查询
    protected $_prepare = true;


    /**
     * DemoDbModel constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->setFieldList([
            "account"     => "string",
            "passwd"      => "string",
            "user_sex"    => "int",
            "user_name"   => "string",
            "create_time" => "int",
            "update_time" => "int"
        ]);

        //注入cache对象，用于保存cache
        $this->_cache = Cache::factory(Cache::CACHE_TYPE_REDIS, "fend_test");
    }

    public function addUser()
    {
        $data = [
            "account"     => "test",
            "passwd"      => "123",
            "user_sex"    => 1,
            "user_name"   => "xcl",
            "create_time" => time(),
            "update_time" => time()
        ];

        $this->forceWrite(true);

        $this->transactionCallback(function () use ($data){
            $id = $this->add($data);
            $info = $this->getInfoById($id);

            if(!$info) {
                throw new \Exception("add fail",123);
            }

            if(!$this->delById($id)) {
                throw new \Exception("del fail",124);
            }
        });
        $this->forceWrite(false);
    }

}