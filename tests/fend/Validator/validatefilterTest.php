<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class validatefilterTest extends TestCase
{
    public static function t1($key, $val)
    {
        return $val;
    }

    public function testValidate()
    {
        $param = [
            "must" => true,
            "string" => "wahahah",
            "int" => "3244",
            "float" => "43.6",
            "double" => "123.1",
            "email" => "test@qq.com",
            "enum" => "yes",
            "callback" => "ahaha",
            "testreg" => "xcl@fend.com",
            "t" => "xcl@fend.com",

        ];

        //期望输出信息
        $expectResult = array(
            'must' => true,
            'string' => 'wahahah',
            'int' => 3244,
            'float' => 43.6,
            'double' => 123.1,
            'email' => 'test@qq.com',
            'enum' => 'yes',
            'callback' => 'ahaha',
            'testreg' => "xcl@fend.com",
            "t" => "xcl@fend.com",

        );

        $message = [
            "must.require" => ["must必填,自定义哈", 223],
            "string.int" => "user_id 必须是数值",
            "float.float" => ["只要float", 3636], //数组类型，第二个参数为自定义exception错误码
            "user_name.regx" => "user_name 必须由数字英文字符串组成",
            "user_name.string" => "user_name 必须是字符串类型数据",
        ];

        //参数1、2用于生成文档
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        //用于演示使用的demo数据
        $validate->addDemoParameter([["uid" => 12312], ["uid" => 123]]);
        //添加 过滤规则
        $validate->addRule("must", "bool", "bool类型，必填字段", true);
        $validate->addRule("default", "int", "int类型，非必填，默认1", false, 1);
        $validate->addRule("string", "string", "用户uid", false, "", [1, 10]);
        $validate->addRule("int", "int", "用户uid的int写法", false, "", [1, 20000]);
        $validate->addRule("float", "float", "float类型", false, "", [1, 20000]);
        $validate->addRule("double", "double", "double", false, "", [1, 20000]);
        $validate->addRule("email", "email", "email检测", false);
        $validate->addRule("enum", "enum", "enum检测:yes代表xx，no代表xx", false, "", ["yes", "no"]);
        $validate->addRule("heiheihei", "string", "非必填，没填写", false);
        $validate->addRule("testreg", "regx:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/", "邮件正则检测", true);
        $validate->addRule("t", "callback", "邮件正则检测", true, "", [validatefilterTest::class, "t1"]);

        $callback = function ($key, $val) {
            if ($val != "ahaha") {
                throw new \Exception("嗯错误了");
            }
            return $val;
        };
        $validate->addRule("callback", "callback", "用户回调规则", false, "", $callback);

        //自定义，错误提示，如果不定义会使用默认错误码和错误提示
        $validate->addMessage($message);

        $result = $validate->checkParam($param);

        $this->assertEmpty(array_diff_assoc($expectResult, $result));


    }

    public function testValidateMultiRule()
    {
        $param = [
            "must" => true,
            "string" => "wahahah",
            "int" => "3244",
            "float" => "43.6",
            "double" => "123.1",
            "email" => "test@qq.com",
            "enum" => "yes",
            "callback" => "ahaha",
            "testreg" => "xcl@fend.com",
            "t" => "xcl@fend.com",

        ];

        //期望输出信息
        $expectResult = array(
            'must' => true,
            'string' => 'wahahah',
            'int' => 3244,
            'float' => 43.6,
            'double' => 123.1,
            'email' => 'test@qq.com',
            'enum' => 'yes',
            'callback' => 'ahaha',
            'testreg' => "xcl@fend.com",
            "t" => "xcl@fend.com",

        );

        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addDemoParameter([["uid" => 12312], ["uid" => 123]]);

        $callback = function ($key, $val) {
            if ($val != "ahaha") {
                throw new \Exception("嗯错误了");
            }
            return $val;
        };

        $rules = [
            "must" => ["bool", "bool类型，必填字段", true],
            "default" => ["int", "int类型，非必填，默认1", false, 1],
            "string" => ["string", "用户uid", false, "", [1, 10]],
            "int" => ["int", "用户uid的int写法", false, "", [1, 20000]],
            "float" => ["float", "float类型", false, "", [1, 20000]],
            "double" => ["double", "double", false, "", [1, 20000]],
            "email" => ["email", "email检测", false],
            "enum" => ["enum", "enum检测:yes代表xx，no代表xx", false, "", ["yes", "no"]],
            "heiheihei" => ["string", "非必填，没填写", false],
            "testreg" => ["regx:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/", "邮件正则检测", true],
            "t" => ["callback", "邮件正则检测", true, "", [validatefilterTest::class, "t1"]],
            "callback" => ["callback", "用户回调规则", false, "", $callback],
        ];

        $validate->addMultiRule($rules);

        $result = $validate->checkParam($param);

        $this->assertEmpty(array_diff_assoc($expectResult, $result));

    }

    public function testAddRuleException()
    {
        //test default
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");

        //key empty
        $occur = 0;
        try {
            $validate->addRule("", "string", "默认测试", false, "default");
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //type empty
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");

        $occur = 0;
        try {
            $validate->addRule("id", "", "默认测试", false, "default");
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //desc empty
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");

        $occur = 0;
        try {
            $validate->addRule("id", "bool", "", false, "default");
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //callable check callable
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");

        $occur = 0;
        try {
            $validate->addRule("id", "callback", "123", false, "default", []);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //callable check string empty
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $occur = 0;
        try {
            $validate->addRule("id", "callback", "123", false, "default", null);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test require

        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRule("must", "bool", "bool类型，必填字段", true);
        $param = [
            "other" => 1,
        ];

        $occur = 0;
        try {
            $result = $validate->checkParam($param);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

    }

    public function testAddRuleExException()
    {
        //test default
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");

        //key empty
        $occur = 0;
        try {
            $validate->addRuleEx("", "string", "默认测试", false, "default");
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //type empty
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");

        $occur = 0;
        try {
            $validate->addRuleEx("id", "", "默认测试", false, "default");
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //desc empty
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");

        $occur = 0;
        try {
            $validate->addRuleEx("id", "bool", "", false, "default");
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //callable check callable
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");

        $occur = 0;
        try {
            $validate->addRuleEx("id", "callback", "123", false, "default", []);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //callable check string empty
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $occur = 0;
        try {
            $validate->addRuleEx("id", "callback", "123", false, "default", null);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test require

        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("must", "bool", "bool类型，必填字段", true);
        $param = [
            "other" => 1,
        ];

        $occur = 0;
        try {
            $result = $validate->checkParam($param);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

    }

    public function testCheckParamException()
    {
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("int", "int", "int测试", 1, 1, [],3002);
        //test int with array
        $occur = 0;
        try {
            $result = $validate->checkParam(["int" => [1]]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test default
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("def", "string", "默认测试", false, "default");
        $result = $validate->checkParam([]);

        self::assertEquals("default", $result["def"]);

        ////////////////////////////////////////
        //test int
        ////////////////////////////////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("int", "int", "int测试", false, null, [1, 10]);

        //test int limit bigger
        $occur = 0;
        try {
            $result = $validate->checkParam(["int" => 11]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test int limit small
        $occur = 0;
        try {
            $result = $validate->checkParam(["int" => 0]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test int limit good
        $result = $validate->checkParam(["int" => 2]);
        self::assertEquals(2, $result["int"]);

        //test int with string
        $occur = 0;
        try {
            $result = $validate->checkParam(["int" => "ddd"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);



        ////////////////////////////////////////
        //test float
        ////////////////////////////////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("float", "float", "float test", false, null, [1.0, 10.0]);

        //test float limit bigger
        $occur = 0;
        try {
            $result = $validate->checkParam(["float" => 11.0]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test float limit small
        $occur = 0;
        try {
            $result = $validate->checkParam(["float" => 0]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test float limit good
        $result = $validate->checkParam(["float" => 2]);
        self::assertEquals(2, $result["float"]);

        //test float with string
        $occur = 0;
        try {
            $result = $validate->checkParam(["float" => "ddd"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);


        ////////////////////////////////////////
        //test double
        ////////////////////////////////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("double", "double", "double test", false, null, [1.0, 10.0]);

        //test float limit bigger
        $occur = 0;
        try {
            $result = $validate->checkParam(["double" => 11.0]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test float limit small
        $occur = 0;
        try {
            $result = $validate->checkParam(["double" => 0]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test float limit good
        $result = $validate->checkParam(["double" => 2]);
        self::assertEquals(2, $result["double"]);

        //test float with string
        $occur = 0;
        try {
            $result = $validate->checkParam(["double" => "ddd"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        ////////////////////////////////////////
        //test string
        ////////////////////////////////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("string", "string", "string test", false, null, [2, 3]);

        //test string limit bigger
        $occur = 0;
        try {
            $result = $validate->checkParam(["string" => "dddd"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test string limit small
        $occur = 0;
        try {
            $result = $validate->checkParam(["string" => "d"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test string limit good
        $result = $validate->checkParam(["string" => "dd"]);
        self::assertEquals("dd", $result["string"]);

        //test string with array
        $occur = 0;
        try {
            $result = $validate->checkParam(["string" => ["array"]]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        ////////////////////////////////////////
        //test email
        ////////////////////////////////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("email", "email", "email test", false, null, [2, 10]);

        //test email limit bigger
        $occur = 0;
        try {
            $result = $validate->checkParam(["email" => "tttttttttttt@qq.com"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test email limit small
        $occur = 0;
        try {
            $result = $validate->checkParam(["email" => "d"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test email limit good
        $result = $validate->checkParam(["email" => "xx@qq.com"]);
        self::assertEquals("xx@qq.com", $result["email"]);

        //test email with array
        $occur = 0;
        try {
            $result = $validate->checkParam(["email" => ["array"]]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //test email with array
        $occur = 0;
        try {
            $result = $validate->checkParam(["email" => "dddd"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        ////////////////////////////////////////
        //test enum
        ////////////////////////////////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("enum", "enum", "enum test", false, null, ["o1", "o2"]);

        $result = $validate->checkParam(["enum" => "o1"]);

        $occur = 0;
        try {
            $result = $validate->checkParam(["enum" => "o3"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        ////////////////////////////////////////
        //test regx
        ////////////////////////////////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("regx", "regx:/^[0-9]+$/", "enum test", false, null, [1, 3]);

        $validate->checkParam(["regx" => "1"]);

        $occur = 0;
        try {
            $result = $validate->checkParam(["regx" => "d"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        $occur = 0;
        try {
            $result = $validate->checkParam(["regx" => "dddd"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        ////////////////////////////////////////
        //test array
        ////////////////////////////////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("arr", "array", "array test", true);

        $result = $validate->checkParam(["arr" => ["a"=>1]]);

        //wrong type
        $occur = 0;
        try {
            $result = $validate->checkParam(["arr" => "o3"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

        //empty
        $occur = 0;
        try {
            $result = $validate->checkParam(["arr" => []]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);

    }

    public function testExceptionMessage()
    {
        //////////////////////////
        //test  message
        //////////////////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("double", "double", "double test", false, null, [1.0, 10.0]);

        $occur = 0;
        try {
            $validate->addMessage(["double.double" => ["type error", 4444]]);
            $result = $validate->checkParam(["double" => "ddd"]);
        } catch (\Exception $e) {
            self::assertEquals("type error", $e->getMessage());
            self::assertEquals(4444, $e->getCode());

            $occur = 1;
        }
        self::assertEquals(1, $occur);

        $occur = 0;
        try {
            $validate->addMessage(["double.limit" => "limit error"]);
            $result = $validate->checkParam(["double" => 15]);
        } catch (\Exception $e) {
            self::assertEquals("limit error", $e->getMessage());
            self::assertEquals(3002, $e->getCode());

            $occur = 1;
        }
        self::assertEquals(1, $occur);
    }

    public function testUnknowTypeException()
    {
        ////////////////
        /// unknow type exception
        ///////////////
        $validate = new \Fend\ValidateFilter("http://www.test.php/user/info", "根据学生id查找学生信息", "get");
        $validate->addRuleEx("unknow", "unknow", "unknow test", false, null, [1, 3]);

        $occur = 0;
        try {
            $validate->checkParam(["unknow" => "1"]);
        } catch (\Exception $e) {
            $occur = 1;
        }
        self::assertEquals(1, $occur);
    }
}
