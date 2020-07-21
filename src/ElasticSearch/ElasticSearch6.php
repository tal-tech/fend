<?php

namespace Fend\ElasticSearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Fend\Config;
use Fend\Exception\FendException;
use Fend\Exception\SystemException;

/**
 * ES操作封装，仅用于方便使用
 * Class ElasticSearch
 * @package Fend\ElasticSearch
 */
class ElasticSearch6
{

    /**
     * @var Client
     */
    protected $client = null;

    //对应配置原文
    protected $config = [];


    /**
     * 工厂模式+单例模式 获取指定配置的es对象
     * @param string $configName
     * @return ElasticSearch6
     * @throws SystemException
     */
    public static function Factory($configName = "default")
    {
        static $instance = null;

        if (isset($instance[$configName])) {
            return $instance[$configName];
        }

        //new one
        $instance[$configName] = new self($configName);
        return $instance[$configName];

    }

    /**
     * ElasticSearch constructor.
     * @param string $configName 配置名称
     * @throws SystemException
     */
    public function __construct($configName = "default")
    {
        $config = Config::get("ElasticSearch");
        if (!isset($config[$configName])) {
            throw new SystemException("ElasticSearch 没有找到指定配置文件内指定db:" . $configName, -8001);
        }

        $this->config = $config[$configName];

        $this->client = ClientBuilder::create()->setHosts($this->config["hosts"])->build();
    }

    /**
     * 创建索引
     * @param string $index
     * @param array $mapping
     * @param array $setting
     * @return bool
     * @throws \Exception
     */
    public function createIndex($index, $mapping = array(), $setting = array())
    {
        if (empty($index) || empty($mapping)) {
            throw new FendException("参数必填", -8002);
        }
        $params = [
            'index' => $index,
            'body' => [
                'settings' => $setting,
                'mappings' => $mapping,
            ]
        ];

        $response = $this->client->indices()->create($params);
        if ($response["acknowledged"] && $response["shards_acknowledged"]) {
            return true;
        }

        //失败就直接抛出异常了
        return false;
    }

    /**
     * 删除索引
     * @param string $index
     * @return bool
     * @throws \Exception
     */
    public function removeIndex($index)
    {
        if (empty($index)) {
            throw new FendException("参数必填", -8002);
        }
        $params = ['index' => $index];
        $response = $this->client->indices()->delete($params);
        if ($response["acknowledged"]) {
            return true;
        }
        //失败就直接抛出异常了
        return false;
    }

    /**
     * 索引一个数据，id让ES自动生成
     * @param string $index 索引名称
     * @param string $type es doc type
     * @param array $data kv数组，要索引的数据
     * @return bool
     * @throws \Exception
     */
    public function indexDocument($index, $type, $data)
    {
        if (empty($index) || empty($type) || empty($data)) {
            throw new FendException("参数必填", -8002);
        }
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => $data
        ];

        $response = $this->client->index($params);
        if (isset($response["result"]) && $response["result"] === "created" && isset($response["_id"])) {
            return $response["_id"];
        }
        //失败就直接抛出异常了
        return false;
    }

    /**
     * 索引指定id的数据
     * @param string $index 索引名称
     * @param string $type es doc type
     * @param string $id 指定索引的id
     * @param array $data kv数组，为要索引数据
     * @return bool
     * @throws \Exception
     */
    public function indexDocumentWithId($index, $type, $id, $data)
    {
        if (empty($index) || empty($type) || empty($data) || empty($id)) {
            throw new FendException("参数必填", -8002);
        }
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $data
        ];

        $response = $this->client->index($params);
        if (isset($response["result"]) && $response["result"] === "created") {
            return true;
        }
        //失败就直接抛出异常了
        return false;
    }

    /**
     * 批量索引数据
     * @param array $dataList 二维数组，批量数据，必须包含type index data及可选id
     * @return bool
     * @throws \Exception
     */
    public function bulkIndexDocument($dataList)
    {
        if (empty($dataList)) {
            throw new FendException("参数必填", -8002);
        }
        $params = ['body' => []];

        $total = 0;

        foreach ($dataList as $dataIndex => $dataItem) {
            //添加索引及id信息

            //有id，设置索引及id
            if (isset($dataItem["id"])) {
                $params['body'][] = [
                    'index' => [
                        '_type' => $dataItem["type"],
                        '_index' => $dataItem["index"],
                        '_id' => $dataItem["id"],
                    ]
                ];
            } else {
                $params['body'][] = [
                    'index' => [
                        '_type' => $dataItem["type"],
                        '_index' => $dataItem["index"],
                    ]
                ];
            }

            //设置下一条，用于数据存储
            $params['body'][] = $dataItem["data"];

            $total++;

            // Every 1000 documents stop and send the bulk request
            if ($total % 1000 == 0) {
                $responses = $this->client->bulk($params);
                if (!isset($responses["errors"]) || $responses["errors"] !== false) {
                    return false;
                }

                // erase the old bulk request
                $params = ['body' => []];

                // unset the bulk response when you are done to save memory
                unset($responses);
            }
        }

        // Send the last batch if it exists
        if (!empty($params['body'])) {
            $responses = $this->client->bulk($params);

            if (!isset($responses["errors"]) || $responses["errors"] !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * 根据id获取索引的文档
     * @param string $index 索引
     * @param string $type 索引type，类似于数据库中的db
     * @param string $id 数据id
     * @return bool
     * @throws \Exception
     */
    public function getDocumentByid($index, $type, $id)
    {
        if (empty($index) || empty($type) || empty($id)) {
            throw new FendException("参数必填", -8002);
        }
        $params = [
            'id' => $id,
            'index' => $index,
            'type' => $type,
        ];

        // Get doc at /my_index/_doc/my_id
        $response = $this->client->get($params);
        if ($response["found"]) {
            return $response["_source"];
        }
        return false;
    }

    /**
     * 更新指定id的索引内容
     * @param string $index 索引
     * @param string $type 索引type，类似于数据库中的db
     * @param string $id 数据id
     * @param array $data kv数据，要更新的数据放这里
     * @return bool
     * @throws \Exception
     */
    public function updateDocumentById($index, $type, $id, $data)
    {
        if (empty($index) || empty($type) || empty($id) || empty($data)) {
            throw new FendException("参数必填", -8002);
        }

        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => [
                'doc' => $data
            ]
        ];

        // Update doc at /my_index/_doc/my_id
        $response = $this->client->update($params);
        if ($response["result"] === "updated") {
            return true;
        }
        return false;
    }


    /**
     * 删除指定id索引文档
     * @param string $index 索引
     * @param string $type 索引type，类似于数据库中的db
     * @param string $id 数据id
     * @return bool
     * @throws \Exception
     */
    public function delDocumentById($index, $type, $id)
    {
        if (empty($index) || empty($type) || empty($id)) {
            throw new FendException("参数必填", -8002);
        }
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id
        ];

        // Delete doc at /my_index/_doc_/my_id
        $response = $this->client->delete($params);
        if (isset($response["result"]) && $response["result"] === "deleted") {
            return true;
        }
        return false;
    }

    /**
     * 对指定索引、type进行检索，没有分页选项是因为可以在body指定
     * @param string $index
     * @param string $type
     * @param array $body body内内容随意定制，具体参考官方
     * @return array
     * @throws \Exception
     */
    public function search($index, $type, $body)
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => $body
        ];

        $results = $this->client->search($params);
        return $results;
    }

    /**
     * 自带翻页的search，适合大数量级结果返回
     * @param string $index
     * @param string $type
     * @param array $body
     * @param int $pageSize 每个分区每次返回数据个数
     * @param int $limit 总共获取多少条
     * @return array
     * @throws \Exception
     */
    public function searchByScroll($index, $type, $body, $pageSize, $limit = 1000)
    {
        $params = [
            "size" => $pageSize,
            'index' => $index,
            'type' => $type,
            'body' => $body
        ];
        $results = [];
        $fetchCount = 0;

        $response = $this->client->search($params);
        while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
            $fetchCount += count($response['hits']['hits']);
            $results = array_merge($results, $response['hits']['hits']);

            if ($fetchCount >= $limit) {
                return $results;
            }

            $scroll_id = $response['_scroll_id'] ?? null;

            //完成
            if (!$scroll_id) {
                return $results;
            }
            // Execute a Scroll request and repeat
            $response = $this->client->scroll([
                    "scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
                    "scroll" => "30s"           // and the same timeout window
                ]
            );
        }
        return $results;
    }

    /**
     * 获取es客户端对象，用于特殊定制化场景使用
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * 获取索引setting
     * @param string $index
     * @return array
     */
    public function getIndexSetting($index)
    {
        $params = [
            'index' => $index,
        ];
        $response = $this->client->indices()->getSettings($params);
        return $response;
    }

    /**
     * 更新索引设置
     * @param string $index
     * @param array $setting
     * @return bool
     */
    public function setIndexSetting($index, $setting)
    {
        $params = [
            'index' => $index,
            'body' => [
                'settings' => $setting
            ]
        ];
        $response = $this->client->indices()->putSettings($params);
        if ($response["acknowledged"]) {
            return true;
        }
        return false;
    }

    /**
     * 获取索引字段映射
     * @param string $index
     * @return array
     */
    public function getMapping($index)
    {
        $params = ['index' => $index];
        $response = $this->client->indices()->getMapping($params);
        return $response;
    }

    /**
     * 设置字段映射
     * @param string $index
     * @param string $type
     * @param array $mapping
     * @return bool
     */
    public function putMapping($index, $type, $mapping)
    {
        // Set the index and type
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => [
                'properties' => $mapping
            ]
        ];
        $response = $this->client->indices()->putMapping($params);
        if ($response["acknowledged"]) {
            return true;
        }
        return false;
    }


}