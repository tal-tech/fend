<?php
declare(strict_types=1);


namespace Fend\Db\PDO;


use Fend\Config;
use Fend\Db\Mysql;
use Fend\Db\MysqlPDO;
use Fend\Exception\FendException;
use Fend\Pool\Pool;

class PDOPool extends Pool
{
    private static $instance = [];

    /**
     * @param string $r
     * @param string $db
     * @return mixed
     * @throws FendException
     */
    public static function factory(string $r, string $db)
    {
        $key = sprintf('%s.%s', $db, $r);
        if(!isset(static::$instance[$key])) {
            static::$instance[$key] = new static($r, $db);
        }
        return static::$instance[$key];
    }

    /**
     * @var string
     */
    protected $r;

    /**
     * @var string
     */
    protected $db;

    /**
     * @var array
     */
    protected $config;

    /**
     * RedisPool constructor.
     * @param string $r
     * @param string $db
     * @throws FendException
     */
    public function __construct(string $r, string $db)
    {
        $this->r = $r;
        $this->db = $db;

        $key = sprintf('Db.%s', $this->db);

        try {
            $config = Config::get($key);
        } catch (\Exception $e) {
            throw new FendException(sprintf('config[%s] is not exist!', $key));
        }

        $this->config = $config['pool'] ?? [];

        parent::__construct($config);
    }

    /**
     * @return MysqlPDO
     * @throws FendException
     */
    protected function createConnection()
    {
        return new MysqlPDO($this->r, $this->db);
    }

    /**
     * @return Mysql
     * @throws FendException
     */
    public function get()
    {
        return $this->getConnection();
    }
}