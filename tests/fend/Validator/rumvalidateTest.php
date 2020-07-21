<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Fend\RumValidate as R;

/**
 * RumValidate Unit Test
 */
class rumvalidateTest extends TestCase
{
    /**
     * 基础测试
     */
    public function testRules()
    {
        // 常规
        $param = [
            "name" => "张三",                       // 必填|只能包含汉字，字母，数字|20字符以内
        ];
        $rules = [
            'name' => [[R::required(), R::cnwordnum(), R::length(0, 20)], '参数姓名不合法', 53000],
            'sex' => [[R::optional()], '参数性别不合法'],
        ];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');
        self::assertEquals($ret_check['data']['name'], '张三');

        // 参数不合法
        $param1 = [];
        $ret_check = R::doe($param1, $rules);
        self::assertEquals($ret_check['error'], '参数姓名不合法');

        // 抛出异常
        try {
            $ret_check = R::do($param1, $rules);
        } catch (\Throwable $th) {
            self::assertEquals($th->getMessage(), '参数姓名不合法');
            self::assertEquals($th->getCode(), 53000);
        }


        // 参数不合法
        $param2 = [
            'stat' => 1,
            'msg' => 'OK'
        ];
        $rules2 = [
            'stat' => [[R::optional(), function ($v, $k, $o) {
                if ($o[$k] === 1 && $o['msg'] === 'OK') {
                    return R::succ();
                }
                return R::fail('组合规则：函数内自定义错误');
            }], '组合规则']
        ];
        $param3 = [
            'stat' => 1,
            'msg' => 'ok'
        ];
        $ret_check = R::doe($param3, $rules2);
        self::assertEquals($ret_check['error'], '组合规则：函数内自定义错误');

        $param4 = [
            'workcode' => '',
            'age' => ''
        ];
        $rules4 = [
            'workcode' => [[R::required(), R::emptystr(), R::num()], '空字符串'],
            'age' => [[R::required(), R::num()], 'age空字符串']
        ];
        $ret_check = R::doe($param4, $rules4);
        self::assertEquals($ret_check['error'], 'age空字符串');
    }
    /**
     * 格式 
     */
    public function testFormat()
    {
        // 布尔值
        $param = [
            "b1" => 4,                       // 必填|布尔值
        ];
        $rules = [
            'b1' => [[R::required(), R::boolean()], '参数b1必须为boolean类型']
        ];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数b1必须为boolean类型');
        $param['b1'] = false;
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');
        $param['b1'] = 'true';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        // 浮点
        $param['f1'] = 't';
        $rules['f1'] = [[R::required(), R::float()], '参数f1必须为float类型'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数f1必须为float类型');
        $param['f1'] = '12.3';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        // 整数
        $param['i1'] = '32i';
        $rules['i1'] = [[R::required(), R::float()], '参数i1必须为int类型'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数i1必须为int类型');
        $param['i1'] = '123';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        // email
        $param['e1'] = 'liumurong1@';
        $rules['e1'] = [[R::required(), R::email()], '参数e1必须为有效的邮箱地址'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数e1必须为有效的邮箱地址');
        $param['e1'] = 'liumurong1@100tal.com';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        // 11位手机号码
        $param['p1'] = '12345678901';
        $rules['p1'] = [[R::required(), R::phone()], '参数p1必须为有效的手机号码'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数p1必须为有效的手机号码');
        $param['p1'] = '13489684586';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        // IP地址
        $param['ip1'] = '0.0.0.x';
        $rules['ip1'] = [[R::required(), R::ip()], '参数ip1必须为IP地址'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数ip1必须为IP地址');
        $param['ip1'] = '127.0.0.1';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        // IP地址
        $param['u1'] = 'localhost:8080/test';
        $rules['u1'] = [[R::required(), R::url()], '参数u1必须为URL'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数u1必须为URL');
        $param['u1'] = 'https://localhost:8080/test';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        // 日期
        $param['d1'] = '2019-12-27 13';
        $rules['d1'] = [[R::required(), R::datetime()], '参数d1必须为日期类型'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数d1必须为日期类型');
        $param['d1'] = '2019-12-27 13:23:54';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');
        $param['d1'] = '2019-12-27';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');
    }
    /**
     * 正则
     */
    public function testRegex()
    {
        // 数字
        $param = [
            'n1' => '2#'
        ];
        $rules = [
            'n1' => [[R::required(), R::num()], '参数n1只能为数字']
        ];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数n1只能为数字');
        $param['n1'] = '9123';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        $param['wn1'] = 's_a';
        $rules['wn1'] = [[R::required(), R::wordnum()], '参数wn1只能包含字母,数字'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数wn1只能包含字母,数字');
        $param['wn1'] = 's3';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        $param['cwn1'] = 's,a';
        $rules['cwn1'] = [[R::required(), R::cnwordnum()], '参数cwn1只能包含中文,字母,数字'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数cwn1只能包含中文,字母,数字');
        $param['cwn1'] = '中国s3';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        $param['c_code'] = '457845';
        $rules['c_code'] = [[R::required(), R::regex('/^(?!0).*/', "资格证编号不能以0开头"), R::regex('/^\d{17}$/', '资格证编号为17位数字')], '参数c_code不合法'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '资格证编号为17位数字');
        $param['c_code'] = '58749685478547859';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');
    }
    /**
     * 枚举(数组)
     */
    public function testEnum()
    {
        $param = [
            "sex" => "1",                           // 必填|枚举 1.男，2.女
            "teacher_type" => "1"                   // 必填|取值范围：1.主讲教师，2.辅导教师，3.专属教师 
        ];
        $SEX = [
            '1' => ['id' => 1, 'name' => '男'],
            '2' => ['id' => 2, 'name' => '女']
        ];
        $TEACHERTYPE = [['id' => 1, 'name' => '主讲教师'], ['id' => 2, 'name' => '辅导教师'], ['id' => 3, 'name' => '专属教师']];
        $rules = [
            'sex' => [[R::required(), R::enum($SEX)], '参数性别不合法'],
            'teacher_type' => [[R::required(), R::enum($TEACHERTYPE, "教师类型不是有效值", true, 'id')], '参数教师类型不合法'],
        ];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');
        self::assertEquals($ret_check['data']['sex'], '1');
        self::assertEquals($ret_check['data']['teacher_type'], '1');
    }
    /**
     * 异常信息提示顺序
     */
    public function testErrorQueue()
    {
        // 数字
        $param = [
            'q1' => '2#',
        ];
        $rules = [
            'q1' => [[R::optional(), R::num()]]
        ];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数q1非法');
        $rules['q1'] = [[R::optional(), R::num()], '所有规则默认异常提示'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '所有规则默认异常提示');
        $rules['q1'] = [[R::optional(), R::num('参数q1只能包含数字')], '所有规则默认异常提示'];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数q1只能包含数字');
    }

    /**
     * 范围
     */
    public function testRange()
    {
        $param = [
            'bw' => '101',
        ];
        $rules = [
            'bw' => [[R::optional(), R::between(0, 100)]]
        ];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数bw非法');
        $param['bw'] = 55;
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        $param['l'] = 'str';
        $rules['l'] = [[R::optional(), R::length(4, 8)]];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数l非法');
        $param['l'] = 'string';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');

        $param['dec'] = '12.365';
        $rules['dec'] = [[R::optional(), R::maxdeci(2)]];
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '参数dec非法');
        $param['dec'] = '12.3';
        $ret_check = R::doe($param, $rules);
        self::assertEquals($ret_check['error'], '');
    }
}
