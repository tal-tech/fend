
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
 
 
### Document    
[Document](https://tal-tech.github.io/fend-doc/) 

[国内访问](https://www.yuque.com/tal-tech/fend/readme) 
 
 
### Release Note
 * Tag 1.2.x FPM/Swoole 1.10.x support FPM \<-\> Swoole 1.10.x
 * Tag 1.3.x FPM/Swoole 4.5.x support FPM \<-\> Swoole Coroutine 4.5.x
 
 
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
 
 
## 软件作者贡献列表

|姓名|事业部|部门|
|:---| :--- |:---|
|刘帅 (@lsfree) |网校|平台研发部|
|韩天峰 (@matyhtf) |网校|架构研发部|
|徐长龙 (@蓝天)|网校|架构研发部|
|李丹阳 (@会敲打码的猫)|网校|架构研发部|
|陈曹奇昊 (@twose)|网校|架构研发部|
|谢华亮 (@黑夜路人)|开放平台|智慧教育|
|陈雷 |网校|互联网研发部|

(其他贡献者不一一列举)
 
 
## Contact us
issue: [https://github.com/tal-tech/fend/issues](https://github.com/tal-tech/fend/issues) 

加群请加微信： 

![](http://static0.xesimg.com/tal-tech-pic/fend/assets/contactus.png)
