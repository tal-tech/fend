<?php

namespace Fend;

use Fend\Exception\SystemException;

class PHPTemplate
{
    /**
     * 模板路径
     *
     * @var string
     */
    protected $tplDir = '';

    /**
     * @var array
     */
    protected $tplVars = [];

    /**
     * 初始化模板
     *
     * @author wll <wanglelecc@gmail.com>
     * @date 2020-02-12 17:20
     */
    public function initTemplate()
    {
        $this->tplDir = SYS_VIEW;
    }

    /**
     * 注册变量到模板
     * 注意: 在函数体内的变量实现完全拷贝,会重复占用内存以及CPU资源
     * 建议使用refVar引用传递
     *
     * @param string $strVar 变量指针
     * @param string $tplVar 模板中的变量名称
     * @param integer $tp 是否引用注册
     **/
    protected function assign($tplVar, $strVar)
    {
        $this->tplVars[$tplVar] = $strVar;
    }

    /**
     * 处理模板并回显
     *
     * @param string $tplVar 模板文件名称
     * @param string $tplPre 模板后缀
     * @return string 渲染后结果
     * @throws
     **/
    protected function showView($tplVar, $tplPre = '.tpl')
    {
        return $this->fetch($tplVar . $tplPre);
    }

    /**
     * 渲染模板内容
     *
     * @param string $filename 模板文件名称
     *
     * @return false|string
     * @throws SystemException
     */
    protected function fetch( $filename = '' )
    {
        $tplPath = $this->tplDir . $filename;

        if( !file_exists($tplPath) ){
            throw new SystemException("View file: `{$tplPath}` does not exist");
        }

        ob_start();

        // 载入模板
        include $tplPath;

        return ob_get_clean();
    }

    /**
     * 获取参数值
     *
     * @param string $k 键名
     *
     * @return mixed|null
     */
    public function __get($k)
    {
        return isset($this->tplVars[$k]) ? $this->tplVars[$k] : null;
    }

    /**
     * 检查参数
     *
     * @param $k
     *
     * @return bool
     */
    public function __isset($k)
    {
        return isset($this->tplVars[$k]);
    }
}