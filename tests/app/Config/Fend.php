<?php

return [
    "log" => [
        "trace" => true, //是否开启trace日志
        "traceResponseMaxLen" => -1, //-1 记录全部(默认) 0时不记录 >0超过这个长度截断
        "level" => \Fend\Logger::LOG_TYPE_INFO, //分级日志记录级别
        "path" => SYS_ROOTDIR . 'logs' . FD_DS, //日志文件保存路径
        "filenameWithPid" => true, //日志文件增加getmypid，线上建议开启，主要原因是多进程高并发写大于8k的日志会导致文件内容相互覆盖
        "logFormat" => "json", //日志输出格式 json: json_encode方式, export:var_dump常见标准日志
        "logRoll" => "hour", //日志滚动规则 none:不滚动，day:按天滚动，hour:按小时滚动
        "logPrefix" => "fend", //日志文件前缀，用于区分多个项目日志
    ],

    "debug" => true, //是否开启debug模式，如果开启在网址增加query参数?wxdebug=1可以看到错误原因

];