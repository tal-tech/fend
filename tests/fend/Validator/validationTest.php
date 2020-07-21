<?php
declare(strict_types=1);

namespace App\Test\Fend;

use Fend\Validation;
use PHPUnit\Framework\TestCase;

class validationTest extends TestCase
{

    public function testDotArray()
    {
        $data = [
            "user" => [
                [
                    "ll" => "dd",
                    "info" => "yaha"
                ],
                [
                    "ll" => "dd",
                    "info" => "yaha1"
                ],
                [
                    "ll" => "dd",
                    "info" => "yaha2"
                ],
                [
                    "ll" => "dd",
                    "info" => "yaha3"
                ],
            ],
            "dd" => [
                [
                    "ll" => "dd1",
                    "info" => "yaha4"
                ],
                [
                    "ll" => "dd2",
                    "info" => "yaha5"
                ],
                [
                    "ll" => "dd3",
                    "info" => "yaha6"
                ],
                [
                    "info" => "12"
                ]
            ]
        ];

        $rules = [
            "user.*.info" => "require|alpha_num",
            "user.*.ll" => "require|alpha_num",
        ];
        $result = Validation::make($data, $rules, []);
        self::assertTrue($result["passed"]);
    }

    public function testCallback()
    {
        $data = [
            "t" => [
                "user" => [
                    [
                        "ll" => "dd",
                        "info" => "yaha"
                    ],
                    [
                        "ll" => "dd",
                        "info" => "yaha1"
                    ],
                    [
                        "ll" => "dd",
                        "info" => "yaha2"
                    ],
                    [
                        "ll" => "dd",
                        "info" => "yaha3"
                    ],
                ],
                "dd" => [
                    [
                        "ll" => "dd1",
                        "info" => "yaha4"
                    ],
                    [
                        "ll" => "dd2",
                        "info" => "yaha5"
                    ],
                    [
                        "ll" => "dd3",
                        "info" => "yaha6"
                    ],
                    [
                        "info" => "12"
                    ]
                ]
            ]
        ];

        $result = Validation::eachArray("t.user.0.info", $data);
        self::assertEquals(json_encode($result), '{"t.user.0.info":"yaha"}');

        $result = Validation::eachArray("t.user.*.info", $data);
        self::assertEquals(json_encode($result), '{"t.user.0.info":"yaha","t.user.1.info":"yaha1","t.user.2.info":"yaha2","t.user.3.info":"yaha3"}');

        $result = Validation::eachArray("t.dd.*", $data);
        self::assertEquals(json_encode($result), '{"t.dd.*":[{"ll":"dd1","info":"yaha4"},{"ll":"dd2","info":"yaha5"},{"ll":"dd3","info":"yaha6"},{"info":"12"}]}');

        $data = [
            [
                [
                    "info1" => 1
                ],
                [
                    "info1" => 2
                ],
            ],
            [
                [
                    "info1" => 3
                ],
                [
                    "info1" => 4
                ],
            ],
            [
                [
                    "info1" => 5
                ],
            ],

        ];
        $result = Validation::eachArray("*.*.info1", $data);
        self::assertEquals(json_encode($result), '{"0.0.info1":1,"0.1.info1":2,"1.0.info1":3,"1.1.info1":4,"2.0.info1":5}');

        $result = Validation::eachArray("dd", ["dd" => 123]);
        self::assertEquals(json_encode($result), '{"dd":123}');

    }


    public function testPreProcess()
    {
        //test unknown cmd will fail
        $rules = [
            "t_norule" => 'fend:1',
        ];
        $result = Validation::make(["t_norule" => 1], $rules, []);
        self::assertFalse($result["passed"]);

        //test no rule will passed
        $rules = [
            "t_norule" => '',
        ];
        $result = Validation::make(["t_norule" => 1], $rules, []);

        self::assertTrue($result["passed"]);

        //test require
        $rules = [
            "t_require" => 'require',
        ];
        $result = Validation::make(["t_require" => 0], $rules, []);
        self::assertTrue($result["passed"]);

        //test require error
        $result = Validation::make([], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            "t_require" => 'default:1|require',
        ];
        $result = Validation::make(["t_require" => null], $rules, []);
        self::assertTrue($result["passed"]);

        $rules = [
            "t_require.*.id" => 'require',
        ];
        $result = Validation::make(["t_require" => [["id" => 1], ["id" => 2]]], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_require" => [["id" => ""], ["id" => 2]]], $rules, []);
        self::assertFalse($result["passed"]);
    }

    public function testType()
    {
        //bool test
        $rules = [
            't_bool' => 'require|bool',
        ];
        $result = Validation::make(["t_bool" => 0], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_bool" => 1], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_bool" => true], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_bool" => false], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_bool" => "0"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_bool" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_bool" => "true"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_bool" => "false"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_bool" => 2], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_bool.*.ok' => 'require|bool',
        ];
        $result = Validation::make(["t_bool" => [["ok" => "true"]]], $rules, []);
        self::assertTrue($result["passed"]);

        //numeric test
        $rules = [
            't_number' => 'require|number',
        ];

        $result = Validation::make(["t_number" => 0], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_number" => -1], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_number" => "0"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_number" => "-1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_number" => "1e1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_number" => false], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_number" => "false"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_number" => new \stdClass()], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_number" => "232d"], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_number.*.ok' => 'require|number',
        ];
        $result = Validation::make(["t_number" => [["ok" => "2"]]], $rules, []);
        self::assertTrue($result["passed"]);

        //string test
        $rules = [
            't_string' => 'require|string',
        ];

        $result = Validation::make(["t_string" => "232d"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_string" => 123], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_string" => true], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_string" => false], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_string" => new \stdClass()], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_string.*.ok' => 'require|number',
        ];
        $result = Validation::make(["t_string" => [["ok" => "2"]]], $rules, []);
        self::assertTrue($result["passed"]);

        //array test
        $rules = [
            't_array' => 'require|array',
        ];

        $result = Validation::make(["t_array" => ["dd" => 11]], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_array" => ["abnc" => 123]], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_array" => "d"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_array" => true], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_array" => false], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_array" => new \stdClass()], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_array.*.ok' => 'require|array',
        ];
        $result = Validation::make(["t_array" => [["ok" => ["d" => "d"]]]], $rules, []);
        self::assertTrue($result["passed"]);

        $rules = [
            't_array.*.ok.*.ab' => 'require|number',
        ];
        $result = Validation::make([
            "t_array" => [
                                [
                                    "ok" => [
                                                ["ab" => 1],
                                                ["ab" => 2],
                                            ]
                                ]
                        ]
        ], $rules, []);
        self::assertTrue($result["passed"]);
    }

    public function testFormat()
    {
        //json test
        $rules = [
            't_json' => 'require|json',
        ];

        $result = Validation::make(["t_json" => '{"key":123}'], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_json" => 'key]'], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_json" => 'dkd'], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_json" => new \stdClass()], $rules, []);
        self::assertFalse($result["passed"]);

        //email test
        $rules = [
            't_email' => 'require|email',
        ];

        $result = Validation::make(["t_email" => 'xcl@fend.org'], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_email" => 'x'], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_email" => 123], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_email" => new \stdClass()], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_email" => false], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_email" => 0], $rules, []);
        self::assertFalse($result["passed"]);

        //alpha test
        $rules = [
            't_alpha' => 'require|alpha',
        ];

        $result = Validation::make(["t_alpha" => 'abdefg'], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_alpha" => '_'], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_alpha" => '1'], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_alpha" => new \stdClass()], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_alpha" => false], $rules, []);
        self::assertFalse($result["passed"]);

        //alpha number test
        $rules = [
            't_alpha_num' => 'require|alpha_num',
        ];

        $result = Validation::make(["t_alpha_num" => 'abdefg'], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_alpha_num" => '1123'], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_alpha_num" => '_'], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_alpha_num" => new \stdClass()], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_alpha_num" => false], $rules, []);
        self::assertFalse($result["passed"]);

        //alpha number test
        $rules = [
            't_alpha_dash' => 'require|alpha_dash',
        ];

        $result = Validation::make(["t_alpha_dash" => 'abdefg'], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_alpha_dash" => '1123'], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_alpha_dash" => '_-'], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_alpha_dash" => new \stdClass()], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_alpha_dash" => false], $rules, []);
        self::assertFalse($result["passed"]);

        //url test
        $rules = [
            't_url' => 'require|url',
        ];

        $result = Validation::make(["t_url" => 'http://www.baidu.com/s/q?foo=1'], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_url" => 'www.sina.com'], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_url" => '//ci.com'], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_url" => new \stdClass()], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_url" => false], $rules, []);
        self::assertFalse($result["passed"]);

        //timestamp test
        $rules = [
            't_timestamp' => "require|timestamp",
        ];

        $result = Validation::make(["t_timestamp" => time()], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_timestamp" => time() . ""], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_timestamp" => time() * 1000], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_timestamp" => "123dd"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_timestamp" => "abcd"], $rules, []);
        self::assertFalse($result["passed"]);

        //date test
        $rules = [
            't_date' => "require|date",
        ];

        $result = Validation::make(["t_date" => "2019-06-10"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_date" => "20:20:20"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_date" => "-2 day"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_date" => "ad"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_date" => 0], $rules, []);
        self::assertFalse($result["passed"]);

        //ip test
        $rules = [
            't_ip' => ["require", "ip"],
        ];

        $result = Validation::make(["t_ip" => "12.0.12.33"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_ip" => "2001:cdba:0:0:0:0:3257:9652"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_ip" => "12.12.33"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_ip" => 16], $rules, []);
        self::assertFalse($result["passed"]);

        //ipv4 test
        $rules = [
            't_ipv4' => "require|ipv4",
        ];

        $result = Validation::make(["t_ipv4" => "12.0.12.33"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_ipv4" => "2001:cdba:0:0:0:0:3257:9652"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_ipv4" => "12.12.33"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_ipv4" => 16], $rules, []);
        self::assertFalse($result["passed"]);

        //ipv4 test
        $rules = [
            't_ipv6' => "require|ipv6",
        ];

        $result = Validation::make(["t_ipv6" => "12.0.12.33"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_ipv6" => "2001:cdba:0:0:0:0:3257:9652"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_ipv6" => "12.12.33"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_ipv6" => 16], $rules, []);
        self::assertFalse($result["passed"]);

        //start with
        $rules = [
            't_start_with' => "require|start_with:11",
        ];

        $result = Validation::make(["t_start_with" => "11abcd"], $rules, []);

        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_start_with" => "200"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_start_with" => "A3"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_start_with" => 116], $rules, []);
        self::assertTrue($result["passed"]);
        $rules = [
            't_start_with' => "start_with:",
        ];
        $result = Validation::make(["t_start_with" => "A3"], $rules, []);
        self::assertFalse($result["passed"]);

        //end with
        $rules = [
            't_end_with' => ["require" => [], "end_with" => ["66"]],
        ];

        $result = Validation::make(["t_end_with" => "1abcd66"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_end_with" => "200"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_end_with" => "A3"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_end_with" => 166], $rules, []);
        self::assertTrue($result["passed"]);

        $rules = [
            't_end_with' => "end_with:",
        ];
        $result = Validation::make(["t_end_with" => "A3"], $rules, []);
        self::assertFalse($result["passed"]);

        //in str
        $rules = [
            't_in_str' => "require|in_str:fend",
        ];
        $result = Validation::make(["t_in_str" => "fend1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_in_str" => "1fend"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_in_str" => "test"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_in_str" => 0], $rules, []);
        self::assertFalse($result["passed"]);

        //in str
        $rules = [
            't_in_str' => "require|in_str:1",
        ];
        $result = Validation::make(["t_in_str" => 115], $rules, []);
        self::assertTrue($result["passed"]);

        $rules = [
            't_in_str' => "in_str:",
        ];
        $result = Validation::make(["t_in_str" => "fend1"], $rules, []);
        self::assertFalse($result["passed"]);
    }

    public function testRange()
    {
        //min test
        $rules = [
            't_min' => 'require|min:1',
        ];
        $result = Validation::make(["t_min" => 1], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_min" => 0], $rules, []);
        self::assertFalse($result["passed"]);
        $rules = [
            't_min' => 'min:',
        ];
        $result = Validation::make(["t_min" => 1], $rules, []);
        self::assertFalse($result["passed"]);


        //max test
        $rules = [
            't_max' => 'require|max:1',
        ];
        $result = Validation::make(["t_max" => 0], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_max" => 2], $rules, []);
        self::assertFalse($result["passed"]);
        $rules = [
            't_max' => 'max:',
        ];
        $result = Validation::make(["t_max" => 0], $rules, []);
        self::assertFalse($result["passed"]);

        //range test
        $rules = [
            't_range' => 'require|range:2,3',
        ];
        $result = Validation::make(["t_range" => 2], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_range" => 3], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_range" => 1], $rules, []);
        self::assertFalse($result["passed"]);
        $rules = [
            't_range' => 'range',
        ];
        $result = Validation::make(["t_range" => 2], $rules, []);
        self::assertFalse($result["passed"]);

        //length test
        $rules = [
            't_length' => 'require|length:2,3',
        ];
        $result = Validation::make(["t_length" => 22], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_length" => "22"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_length" => 0], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_length" => "11111"], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_length' => 'length',
        ];
        $result = Validation::make(["t_length" => 22], $rules, []);
        self::assertFalse($result["passed"]);

        //count test
        $rules = [
            't_count' => 'require|count:2,3',
        ];
        $result = Validation::make(["t_count" => [[], []]], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_count" => "ss"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_count" => 0], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_count" => [[], [], [], []]], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_count' => 'count',
        ];
        $result = Validation::make(["t_count" => 2], $rules, []);
        self::assertFalse($result["passed"]);

        //in test
        $rules = [
            't_in' => 'require|in:2,3,4,5,6',
        ];
        $result = Validation::make(["t_in" => "2"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_in" => 2], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_in" => 0], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_in" => [[], [], [], []]], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_in' => ["in" => [2, 3, 4, 5]],
        ];
        $result = Validation::make(["t_in" => 2], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_in" => "2"], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_in' => 'in',
        ];
        $result = Validation::make(["t_in" => 2], $rules, []);
        self::assertFalse($result["passed"]);

        //not_in test
        $rules = [
            't_not_in' => 'require|not_in:2,3,4,5,6',
        ];
        $result = Validation::make(["t_not_in" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_not_in" => 2], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_not_in" => 0], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_not_in" => [[], [], [], []]], $rules, []);
        self::assertTrue($result["passed"]);

        $rules = [
            't_not_in' => ["require" => [], "not_in" => [2, 3, 4, 5]],
        ];
        $result = Validation::make(["t_not_in" => 2], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_not_in" => "2"], $rules, []);
        self::assertTrue($result["passed"]);

        $rules = [
            't_not_in' => 'not_in',
        ];
        $result = Validation::make(["t_not_in" => 2], $rules, []);
        self::assertFalse($result["passed"]);

        //regex test
        $rules = [
            't_regx' => ["regex" => ["/^[0-9]+$/"]],
        ];
        $result = Validation::make(["t_regx" => 2], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_regx" => "2"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_regx" => "a"], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_regx' => 'regex',
        ];
        $result = Validation::make(["t_regx" => 2], $rules, []);
        self::assertFalse($result["passed"]);
    }

    public function testValidate()
    {
        //test callback
        $rules = [
            't_callback' => ["require" => [], 'callback' => function ($input) {

                if ($input === 3) {
                    return false;
                }

                return true;
            }],
        ];
        $result = Validation::make(["t_callback" => 2], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_callback" => 3], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_callback' => ['callback' => []],
        ];

        $result = Validation::make(["t_callback" => 3], $rules, []);
        self::assertFalse($result["passed"]);

    }

    public function testCondition()
    {
        //test require_if
        $rules = [
            't_require_if' => "require_if:a,1,2,3",
        ];
        $result = Validation::make(["t_require_if" => 2, "a" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["a" => "1"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_require_if" => 2, "a" => 1], $rules, []);
        self::assertTrue($result["passed"]);

        $rules = [
            't_require_if' => ["require_if" => ["a", 1, 2, 3]],
        ];
        $result = Validation::make(["t_require_if" => 2, "a" => 1], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_require_if" => "", "a" => 1], $rules, []);
        self::assertFalse($result["passed"]);

        //test the type not same condition
        $result = Validation::make(["t_require_if" => 2, "a" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        //test require_if
        $rules = [
            't_require_with' => "require_with:a,b",
        ];
        $result = Validation::make(["t_require_with" => 2, "a" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_require_with" => 2, "b" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_require_with" => [], "b" => "1"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make([], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["a" => "1"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["b" => "1"], $rules, []);
        self::assertFalse($result["passed"]);

        $rules = [
            't_require_with' => ["require_with" => ["a", "b"]],
        ];
        $result = Validation::make(["t_require_with" => 2, "a" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_require_with" => 2, "a" => ""], $rules, []);
        self::assertTrue($result["passed"]);

        //test require_all
        $rules = [
            't_required_with_all' => ["required_with_all" => ["a", "b"]],
        ];
        $result = Validation::make(["t_required_with_all" => 2, "a" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_required_with_all" => 2, "a" => ""], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["a" => 2, "b" => "1"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_required_with_all" => 2, "a" => 2, "b" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_required_with_all" => 2, "a" => 2, "b" => ""], $rules, []);
        self::assertTrue($result["passed"]);

        //test require without
        $rules = [
            't_required_without' => ["required_without" => ["a", "b"]],
        ];
        $result = Validation::make(["t_required_without" => 2, "a" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_required_without" => 2, "b" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_required_without" => "", "a" => "1"], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["b" => "1", "a" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        //test require without
        $rules = [
            't_require_without_all' => ["required_without_all" => ["a", "b"]],
        ];
        $result = Validation::make(["t_require_without_all" => 2], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["a" => 2], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["b" => 2], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make([], $rules, []);
        self::assertFalse($result["passed"]);

        //test require without
        $rules = [
            't_same' => ["same" => ["a", "b"]],
        ];
        $result = Validation::make(["t_same" => 2, "a" => 2, "b" => 2], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_same" => 2, "a" => 1, "b" => 2], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_same" => 2, "a" => 2, "b" => 1], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_same" => 2], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make([], $rules, []);
        self::assertFalse($result["passed"]);

        $result = Validation::make(["t_same" => "1", "a" => "1", "b" => "1"], $rules, []);
        self::assertTrue($result["passed"]);

        $result = Validation::make(["t_same" => "", "a" => "", "b" => ""], $rules, []);
        self::assertFalse($result["passed"]);
    }

}