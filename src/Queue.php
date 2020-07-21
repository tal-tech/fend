<?php

namespace Fend;

use Fend\Queue\Exception;
use Fend\Queue\KafkaAgent;
use Fend\Queue\KafkaAgentV2;
use Fend\Queue\RabbitMQAgent;
use Fend\Queue\RabbitMQAgentV2;
use Fend\Queue\RdKafka;

class Queue
{

    /**
     * librdkafka - PHP扩展rdkafka方式
     */
    const QUEUE_TYPE_RDKAFKA = 5;


    /**
     * @param int $t 驱动类型
     * @param string $db 配置名称
     * @return Queue\RdKafka
     * @throws \Exception|Queue\Exception
     */
    public static function factory($t = self::QUEUE_TYPE_KAFKA_SIDECAR, $db = "")
    {
        if ($t == self::QUEUE_TYPE_RDKAFKA) {
            return RdKafka::Factory($db);
        } else {
            throw new Queue\Exception("fend queue 传递未知queue类型");
        }
    }
}