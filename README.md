monolog-database-handler-for-laravel5
=====================

- 让monolog写日志到数据库里面

## 使用前的准备

```bash
composer require "zedisdog/monolog-database-handler-for-laravel5"
```


在配置文件 `config/app.php` 中替换“别名”

app配置文件中加入zedisdog\MysqlHandler\MysqlHandlerServiceProvider::class,
```bash
php artisan vendor:publish --tag="zedisdog/MysqlHandler"
```

## 使用方法

log.php配置文件里面只有一个配置项，用于指定保存日志的表名。

## 致谢

- [waza-ari/monolog-mysql](https://github.com/waza-ari/monolog-mysql)
