<?php
namespace zedisdog\MysqlHandler;


use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;

class MysqlHandlerServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot(Connection $db)
    {
        $this->publishes([
            __DIR__ . '/config/log.php' => config_path('log.php'),
        ], 'zedisdog/mysqlHandler');
        $mysqlHandler = new MySQLHandler($db->getPdo(), config('log.table', 'log'), [], Logger::DEBUG);
        \Log::getMonolog()->pushHandler($mysqlHandler);
    }
}