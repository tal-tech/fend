<?php

namespace Fend;

class Template extends Fend
{
    /**
     * @var \Smarty
     */
    protected $_tpl = null;
    protected $user = array();

    public function initTemplate()
    {
        $this->_tpl = new \Smarty();
        $this->_tpl->setTemplateDir(SYS_VIEW);
        $this->_tpl->setCompileDir(SYS_CACHE . 'smartycompire/');
        $this->_tpl->setCacheDir(SYS_CACHE . 'smarty/');
        $this->_tpl->setConfigDir(SYS_CACHE . 'smartyconf/');
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
        $this->_tpl->assign($tplVar, $strVar);
    }

    /**
     * 引用变量到模板
     * 注意: 呼叫时参量1必须为变量,不能为常量
     * 是一种节省资源的传递方式
     *
     * @param string $strVar 变量指针
     * @param string $tplVar 模板中的变量名称
     **/
    protected function assignbyref($strVar, $tplVar)
    {
        $this->_tpl->assignbyref($tplVar, $strVar);
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
        //传递一个变量到Smarty模版内部
        $tplVar = empty($this->uri[1]) ? $tplVar : $this->uri[1] . '/' . $tplVar;
        return $this->_tpl->fetch($tplVar . $tplPre);
    }
}
