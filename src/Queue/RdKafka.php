<?php

namespace Fend\Queue;

use Fend\Config;
use Fend\Log\EagleEye;

class RdKafka
{

    static $in = array();

    /**
     * @var array 具体配置
     */
    protected $configName = null;

    /**
     * 配置信息
     * @var array
     */
    protected $config = null;

    /**
     * @var
     */
    protected $producer = null;

    protected $topic = null;

    /**
     * @var
     */
    protected $consumer = null;


    /**
     * @param string $config
     * @param string $db database config name
     * @return mixed
     * @throws Exception
     */
    public static function Factory($config, $db = 'default')
    {
        //全链路压测时，自动读写影子库
        $dbList = Config::get('Kafka');
        if (EagleEye::getGrayStatus() && isset($dbList[$config . "-gray"])) {
            $config = $config . "-gray";
        }
        if (!isset(self::$in[$config][$db])) {
            self::$in[$config][$db] = new self($config);
        }
        return self::$in[$config][$db];
    }

    /**
     *  constructor.
     * @param string $configName 配置名称
     * @param string $db 预留
     * @throws Exception
     */
    public function __construct($configName)
    {
        $config = Config::get("Kafka");
        if (!isset($config[$configName])) {
            throw new Exception("Kafka Config Not Found! Config:" . $configName, 51122);
        }
        $this->config = $config[$configName];
        $this->configName = $configName;

        if (empty($this->config["Queue"])
            || empty($this->config["Broker"])
            || empty($this->config["Group"])
        ) {
            throw new \Exception("Kafka 配置填写错误，请检查缺失项", -7001);
        }
    }


    /**
     * 下发队列一个任务
     * waitloop属性用于检测数据是否已经从内存中推送到kafka、一次检测等待20ms、超过指定次数暂时先不阻塞返回成功
     * 如果有大量数据推送可以设置waitloop为0，先全提交后执行flush，切记、否则会丢数据
     * @param string $data 数据内容，必须是字符串
     * @param int $waitloop 默认尝试等待推送完成5次（一次20ms）、100ms后还没推送成功就放内存，如需要批量推送建议设置成0，推送完毕执行flush
     * @param int $partition 指定 分区id 默认为\RD_KAFKA_PARTITION_UA sdk自行分配
     * @param string|null $key key
     * @param array|null $header header只支持数组内kv
     * @return bool
     * @throws Exception
     */
    public function pushQueue($data, $waitloop = 5, $partition = \RD_KAFKA_PARTITION_UA, $key = null, $header = null)
    {
        //init produce
        $this->getProducer();

        //produce msg
        $this->topic->producev($partition, 0, $data, $key, $header);
        $this->producer->poll(20);

        $waitCount = 0;
        //如果发送队列中有累计，那么循环推送一部分
        //block until msg send
        while ($waitCount < $waitloop && $this->producer->getOutQLen() > 0) {
            $waitCount++;
            $this->producer->poll(20);
        }

        return true;
    }

    /**
     * flush 数据到kafka
     * 请在提交数据后执行
     * 不执行可能会丢数据
     */
    public function flush()
    {
        //10次尝试
        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $this->producer->flush(10000);
            if (\RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }
    }

    /**
     * @return \RdKafka\Producer|null
     */
    protected function getProducer()
    {
        if (!$this->producer || !$this->topic) {
            $conf = new \RdKafka\Conf();
            $conf->set('api.version.request', 'true');
            $conf->set('message.send.max.retries', 5);
            $producer = new \RdKafka\Producer($conf);

            //$rk->setLogLevel(LOG_INFO);
            $producer->addBrokers($this->config["Broker"]);

            $this->topic = $producer->newTopic($this->config["Queue"]);
            $this->producer = $producer;
        }
        return $this->producer;
    }

    /**
     * @return \RdKafka\KafkaConsumer
     */
    protected function getConsumer()
    {
        if (!$this->consumer) {
            ///////
            //init consumer
            //////
            $conf = new \RdKafka\Conf();
            $conf->set('api.version.request', 'true');
            $conf->set('group.id', $this->config["Group"]);
            $conf->set('metadata.broker.list', $this->config["Broker"]);
            $conf->set('auto.offset.reset', 'smallest');

            //是否自动提交，-1为不自动提交
            $commitInterval = $this->config["AutoCommit"] ?? 200;
            if($commitInterval == -1) {
                $conf->set('enable.auto.commit', 'false');
            } else {
                $conf->set('auto.commit.interval.ms', $commitInterval);
            }

            $consumer = new \RdKafka\KafkaConsumer($conf);
            $consumer->subscribe([$this->config["Queue"]]);

            $this->consumer = $consumer;
        }
        return $this->consumer;
    }

    /**
     * 消费队列获取任务
     * @return mixed
     * @throws Exception 请求失败
     */
    public function consumer()
    {
        $message = $this->getConsumer()->consume($this->config["ConsumerTimeout"]);
        switch ($message->err) {
            case \RD_KAFKA_RESP_ERR_NO_ERROR:
                return (array)$message;
                break;
            case \RD_KAFKA_RESP_ERR__PARTITION_EOF:
                throw new Exception("No more messages; will wait for more", \RD_KAFKA_RESP_ERR__PARTITION_EOF);
                break;
            case \RD_KAFKA_RESP_ERR__TIMED_OUT:
                //throw new Exception("Kafka get msg timeout", \RD_KAFKA_RESP_ERR__PARTITION_EOF);
                return false;
                break;
            default:
                throw new \Exception($message->errstr(), $message->err);
                break;
        }
    }

    /**
     * 提交commit
     * @param array $task consumer返回的数组
     * @return bool
     */
    public function commitAck($task)
    {
        if(empty($task)) {
            return false;
        }

        if(is_array($task)) {
            $obj = new \RdKafka\Message( );
            foreach ($task as $k=>$v) {
                $obj->$k =$v;
            }
        }

        $this->getConsumer()->commit($obj);
        return true;
    }


}