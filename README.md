
<p align="center"> 
 <a href="https://tal-tech.github.io/fend-doc/" target="_blank">
    <img src="https://github.com/tal-tech/fend-skeleton/blob/master/www/img/fend.png?raw=true"  alt="Fend Logo" align=center />
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

## Contact us
issue: [https://github.com/tal-tech/fend/issues](https://github.com/tal-tech/fend/issues) 

加群请加微信： 

![](https://github.com/tal-tech/fend/blob/master/contactus.png?raw=true)

-----

<img src="http://static0.xesimg.com/tal-tech-pic/68213862.png"   />