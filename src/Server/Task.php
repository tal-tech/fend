<?php
namespace Fend;

/**
 * swoole task 异步任务投递
 */
class Task extends Fend
{
    public static function Factory()
    {
        return new self();
    }

    /**
     * 投递task任务
     * @param string $class 带namespace类名
     * @param string $func 函数名
     * @param array $argv 参数数组
     * @return bool
     */
    public function add($class, $func ="", $argv=array())
    {
        $data = array('class'=>$class,'func'=>$func,'argv'=>$argv);
        $swoole = \Fend\Di::factory()->get('swoole_server');
        if (!empty($swoole)) {
            return $swoole->task($data);
        }
        return false;
    }

    /**
     * 返回当前server的任务状态
     */
    public function getTaskStatus()
    {
        $list = array();
        $swoole = \Fend\Di::factory()->get('swoole_server');

        if (!empty($swoole)) {
            $list =  $swoole->stats();
        }
        return $list;
    }
}
