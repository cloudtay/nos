<?php declare(strict_types=1);

// Docs: This file does not belong to the entry file of the http module and will not be automatically loaded by the Nos framework.

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;

$config     = MysqlConfig::fromString("host=tfb-database port=3306 user=benchmarkdbuser password=benchmarkdbpass db=hello_world");
$connection = new MysqlConnectionPool($config);
return $connection;
