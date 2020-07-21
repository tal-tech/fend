<?php


namespace Fend\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{

    protected static $defaultName = 't';

    /**
     * @var string
     */
    public $signature;

    /**
     * @var string
     */
    public $desc;


    /**
     * @var array 固定参数配置 [ $item ]  $item格式:'[ "参数名", "是否必填", "描述"]'
     */
    protected $params = [];
    /**
     * @var array 可选参数传递 [ $item ] $item格式 [ "选项全拼", "选项简写", "参数属性", "描述" ]
     */
    protected $optional = [];

    private $paramsVerify = [
        "required" => InputArgument::REQUIRED,//必填
        "optional" => InputArgument::OPTIONAL,//可选
        "is_array" => InputArgument::IS_ARRAY,//列表 接收多个值
    ];

    private $optionalTo = [
        "none"     => InputOption::VALUE_NONE, //该选项布尔值 不接受传参
        "required" => InputOption::VALUE_REQUIRED, //必传参数
        "optional" => InputOption::VALUE_OPTIONAL,//可选
        "is_array" => InputOption::VALUE_IS_ARRAY,//可以传递多个值
    ];

    /**
     * 添加配置信息
     */
    protected function configure()
    {
        $this->setName($this->signature)->setDescription($this->desc);
        //设置可选选项 是用多维数组
        $this->setOptional();
        //设置参数访问
        $this->setParams();
    }

    /**
     * 添加参数
     */
    private function setParams()
    {

        if (empty($this->params) || !is_array($this->params)) {
            return;
        }

        foreach ($this->params as $param) {
            if (!empty($param)) {
                $this->addArgument($param[0], $this->paramsVerify[$param[1]], $param[2] ?? '');
            }
        }
    }

    private function setOptional()
    {
        if (!empty($this->optional) && is_array($this->optional)) {
            foreach ($this->optional as $value) {
                $value[2] = $this->optionalTo[$value[2]];
                $this->addOption(...$value);
            }
        }
    }

    /**
     * 重新execute 使得业务层代码更简单
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception 命令未找到
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = array_values($input->getArguments());
        array_shift($args);

        $args = $this->dealParams($input);

        $this->handle($args);
        return $output->writeln("");
    }

    /**
     * 获取命令行传参
     *
     * @param InputInterface $input
     * @return array|false
     * @throws \Exception 必填项提示
     */
    private function dealParams(InputInterface $input)
    {
        //params接收
        $args = array_values($input->getArguments());
        array_shift($args);
        $keys = array_column($this->params, "0");
        $args = array_combine($keys, $args);

        //选项传递 获取对应值
        $options = [];
        $optionsConfig = array_column($this->optional, '2', '0');
        $optionsTotal = array_slice($input->getOptions(), 0 , count($optionsConfig));
        foreach ($optionsTotal as $key => $option){
            if ($this->optionalTo[$optionsConfig[$key]] == InputOption::VALUE_REQUIRED
                && empty($option)
            ) {
                throw new \Exception("{$key} is required");
            }

            //过滤掉空值 防止选项没有传值的数据把参数传值冲没
            if (!empty($option)) {
                $options[$key] = $option;
            }
        }

        //数据合并 优先选项传值
        return array_merge($args, $options);
    }

    abstract public function handle(array $params);
}