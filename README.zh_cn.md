## 主题

> "PHP是一种编程语言，但PHP本身可以被认为是一个框架"

这是一个的静态`PHP`项目, 源自本人某次突发奇想的延伸,
意味着你如果掌握了PHP,那么你就彻底掌握 `Nos`框架

### 安装

```bash
composer create-project --stability=dev cloudtay/nos
```

### 运行应用

```bash
NOS_APP_PATH=app vendor/bin/nos
```

#### 可选参数

```bash
NOS_APP_PATH=app \
NOS_HTTP_LISTEN=http://127.0.0.1:8080 \
NOS_HTTP_WORKERS=1 \
vendor/bin/nos
```

### 路由

它没有控制器与中间件
仅需一个文件`app/http/readme.php`

```php
<?php declare(strict_types=1);

// Path: app/http/readme.php

Route::define(Method::GET, '/door1',  function (Request $request) {
    $request->respond('is door1.');
});

Route::define(Method::GET, '/door2',  function (Request $request) {
    $request->respond('is door2.');
});
```

并在`app/http/http.php`中引入

```php
<?php declare(strict_types=1);

// Path: app/http/http.php

Package::import('readme');

// more...
```

### 引用

```php
//其他路由...

$list = [];
Route::define(
    Method::GET, 
    '/snails', 
    function (Request $request) use (&$list) {
    
    }
);
```

### 数据堆区

```php
<?php

// Path: app/http/readme.php

// 其他路由...

Route::define(Method::GET, '/snail-tail', function (Request $request) {
    \Co\async(fn () => performARunningSnail($request));

    $stream          = $request->stream;
    $id              = $request->stream->id;
    Data::$list[$id] = $stream;

    $stream->onClose(function () use (, $id) {
        unset(Data::$list[$id]);
    });
});

Route::define(Method::GET, '/snail-stop', function (Request $request) {
    if (!$id = $request->GET['id'] ?? null) {
        $request->respond('please provide id.');
        return;
    }

    if (isset(Data::$list[$id])) {
        Data::$list[$id]->write(Chunk::chunk('stoooooooooop!'));
        Data::$list[$id]->close();
        $request->respond('snail ' . $id . ' is gone.');
    } else {
        $request->respond('snail ' . $id . ' is not found.');
    }
});


class Data
{
    public static array $list = [];
}
```

### 模块引用

比如我有这样一个文件`app/database/mysql.php`:

```php
<?php declare(strict_types=1);

// Path: /app/database/mysql.php

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;

$config = MysqlConfig::fromString("host=tfb-database port=3306 user=benchmarkdbuser password=benchmarkdbpass db=hello_world");

return new MysqlConnectionPool($config);
```

数据引用

```php
$pool = Package::import('database/mysql');

// 任何地方导入都会得到相同的引用
$pool = Package::import('database/mysql');

// 其他路由...

Route::define(\Psc\Core\Http\Enum\Method::GET, '/db', function (Request $request) use ($pool, &$dateFormatted) {
    $once = $pool->prepare('SELECT * FROM `World` WHERE id = :id')->execute(['id' => randomInt()]);
    $request->respondJson($once->fetchRow());
});
```

```php
<?php declare(strict_types=1);

// Path: /app/http/readme.php

// 其他路由

Data::$mysqlConnectionPool = Package::import('database/mysql');

Route::define(\Psc\Core\Http\Enum\Method::GET, '/db', function (Request $request) use ($pool, &$dateFormatted) {
    $once = $pool->prepare('SELECT * FROM `World` WHERE id = :id')->execute(['id' => randomInt()]);
    $request->respondJson($once->fetchRow());
});

class Data
{
    public static array               $list = [];
    public static MysqlConnectionPool $mysqlConnectionPool;
}
```

### 静态文件

```php
Data::$faviconBinary = Package::import('static/favicon.ico');
Route::define(Method::GET, '/favicon.ico', function (Request $request) {
    $request->respond(Data::$faviconBinary, ['Content-Type' => 'image/x-icon']);
});

class Data
{
    public static array               $list = [];
    public static MysqlConnectionPool $mysqlConnectionPool;
    public static string              $faviconBinary;
}
```
