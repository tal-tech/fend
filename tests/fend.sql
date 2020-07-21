/**
 * fend_test
 * fend框架单元测试使用
 * 仅供单元测试使用
 */

DROP DATABASE IF EXISTS fend_test;
create database fend_test DEFAULT CHARSET utf8 COLLATE utf8_general_ci;

use fend_test;

DROP TABLE IF EXISTS users;

create table if not exists users(
    id int not null auto_increment,
    account varchar(30) not null,
    passwd varchar(30) not null,
    user_sex tinyint(4) null,
    user_name varchar(30) not null,
    create_time int,
    update_time int,
    primary key (id)
);

insert into users
(id, account, passwd, user_sex, user_name, create_time,update_time)
values
    (3,'user1','pwd',1,'hehe1',1563518812,1563518812),
    (4,'user2','pwd',2,'测试',1563518812,1563518812),
    (5,'user3','pwd',1,'hehe3',1563518812,1563518812),
    (6,'user4','pwd',0,'hehe4',1563518812,1563518812);


DROP TABLE IF EXISTS user_info;

create table if not exists user_info(
    id int not null auto_increment,
    user_id int,
    score int,
    gold int,
    primary key (id)
);

insert into user_info
(id, user_id, score, gold)
values
    (3,3,10,1),
    (4,4,1222,2),
    (5,6,200,1),
    (6,5,123,0);
