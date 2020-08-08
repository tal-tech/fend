
<p align="center"> 
 <a href="https://tal-tech.github.io/fend-doc/" target="_blank">
    <img src="http://static0.xesimg.com/tal-tech-pic/fend/assets/fend.png?raw=true"  alt="Fend Logo" align=center />
 </a> 
</p>

PHP是一款简单方便的语言，而行业开源框架为了`后续灵活` 而变得过于繁重

Fend框架是一款很有历史的框架、初代发布后一直在好未来坊间传播使用、衍生出大量分支版本 

这是一款很有意思的框架、普通的框架内隐藏着大型互联网经验的精华、也同时存在大量历史痕迹

2019年7月 我们对Fend进行整理、封装、推广、目前在好未来内部有大量的用户在使用、维护 

2020年7月 开源、以此共建交流 

我们崇尚 `脚踏实地、仰望星空` 精神 欢迎小伙伴一起参与开源共建

<hr /> 
 
## Document    
[Document](https://tal-tech.github.io/fend-doc/) 

[国内访问](https://www.yuque.com/tal-tech/fend/readme) 
 
## 设计方向

Fend 框架是一款以企业快速实现业务为主要目标的框架，但与复杂的行业流行框架追求不同： 
 * `简单实用`：追求快速上手，扩展功能一步到位、大量降低功能的复杂度、框架更注重简单实用实现
 * `单层内核`：追求一个函数能实现的功能绝不继承封装，不追求框架自身功能的继承可复用 
 * `内聚归类`：高度集中归类功能，降低底层复杂度，减少底层组件关注度、让过程更多时间在业务
 * `持续积累`：持续积累大型互联网线上运营经验，持续探索企业实用技巧，深度来自于积累而非AOP带来的灵活性
 * `内核设计`：高内聚简单内核，放开业务自封装空间，留下更多空间给业务
 * `开源心态`：开放公开，接受任何符合价值观源码奉献、但有严格代码审核
 
## 功能简介
 * Swoole/FPM 双引擎平滑切换(协程版本还在整理稍晚放出)
 * 统一使用 Composer Autoload PSR4
 * 请求Debug 模式，请求网址wxdebug=1可查看debug模式查看异常分析性能
 * 协程模式下对变量域做了更好的封装，降低协程使用难度
 * 支持压测使用灰度影子库
 * 高速map映射路由 + FastRouter正则路由
 * 符合大数据挖掘设计的Trace日志，方便ELK分析、ClickHouse、HBase、实时预警
 * throw new Exception方式处理业务异常、能够快速发现异常
 
<hr /> 

## Release Note
 * Tag 1.2.x FPM/Swoole 1.10.x support FPM \<-\> Swoole 1.10.x
 * Tag 1.3.x FPM/Swoole 4.5.x support FPM \<-\> Swoole Coroutine 4.5.x
 
<hr /> 

## Install for 1.2.x branch
 
#### FPM Engine Start
master is 1.2.x version 

```bash
composer create-project fend/fend-skeleton:~1.2.0 project_name
```

Ref [nginx.conf](nginx.conf) to configure Nginx and http://127.0.0.1/ on browser 
 
 
#### Swoole Engine Start

```bash
composer create-project fend/fend-skeleton:~1.2.0 project_name

# swoole start ( /bin/fend depend on composer require symfony/console )
php /bin/fend Swoole -c app/Config/Swoole.php start
php /bin/start.php -c app/Config/Swoole.php start
```

browser http://127.0.0.1:9572/ 
 
## 1.3.0 version install
```bash
composer create-project fend/fend-skeleton:~1.3.0 project_name
```
 
<hr /> 

## Contributors

|姓名|事业部|部门|
|:---| :--- |:---|
|刘帅 (@lsfree) |网校|平台研发部|
|韩天峰 (@matyhtf) |网校|架构研发部|
|徐长龙 (@蓝天)|网校|架构研发部|
|李丹阳 (@会敲打码的喵)|网校|架构研发部|
|陈曹奇昊 (@twose)|网校|架构研发部|
|谢华亮 (@黑夜路人)|开放平台|智慧教育|
|陈雷 (@godblessmychildren)|网校|互联网研发部|

(其他贡献者、请详见文档鸣谢)
 
<hr /> 

## Contact us
issue: [https://github.com/tal-tech/fend/issues](https://github.com/tal-tech/fend/issues) 

加群请加微信： 

![](http://static0.xesimg.com/tal-tech-pic/fend/assets/contactus.png)
