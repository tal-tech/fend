
<p align="center"> 
 <a href="https://tal-tech.github.io/fend" target="_blank">
    <img src="./www/img/fend.png"  alt="Fend Logo" align=center />
 </a> 
</p>

 * tiny and easy customize
 * FPM/Swoole Smooth switching
 * Debug Mode when request querysting set wxdebug=1|2|3
 * Good ELK Trace Log standard within

### Document    
[Document](https://tal-tech.github.io/fend)

### Release Note
 * Tag 1.2.x FPM/Swoole 1.10.x support FPM \<-\> Swoole 1.10.x
 * Tag 1.3.x FPM/Swoole 4.5.x support FPM \<-\> Swoole Coroutine 4.5.x

## Install for 1.2.x branch

#### FPM Engine Start
master is 1.2.x version 

```bash
git clone https://github.com/tal-tech/fend.git
composer install --no-dev
```

Ref [nginx.conf](nginx.conf) to configure Nginx and http://127.0.0.1/ on browser 

#### Swoole Engine Start

```bash
git clone https://github.com/tal-tech/fend.git
composer install --no-dev

# swoole start ( /bin/fend depend on composer require symfony/console )
php /bin/fend Swoole -c app/Config/Swoole.php start
php /bin/start.php -c app/Config/Swoole.php start
```

browser http://127.0.0.1:9572/ 
