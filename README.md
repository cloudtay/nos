## theme

> "PHP is a programming language, but PHP itself can be considered a framework"

This is a static `PHP` project, an extension of one of my own whims.
It means that if you master PHP, then you will completely master the `Nos` framework

### Install

```bash
composer create-project --stability=dev cloudtay/nos
```

### Run the application

```bash
NOS_APP_PATH=app vendor/bin/nos
```

#### Optional parameters

```bash
NOS_APP_PATH=app \
NOS_HTTP_LISTEN=http://127.0.0.1:8080 \
NOS_HTTP_WORKERS=1 \
vendor/bin/nos
```

### Routing

It has no controller and middleware, only one file `app/http/readme.php`

```php
<?php declare(strict_types=1);

// Path: app/http/readme.php

Route::define(Method::GET, '/door1', function (Request $request) {
    $request->respond('is door1.');
});

Route::define(Method::GET, '/door2', function (Request $request) {
    $request->respond('is door2.');
});
```

and introduced in `app/http/http.php`

```php
<?php declare(strict_types=1);

// Path: app/http/http.php

Package::import('room');

// more...
```

### Quote

```php
//Other routes...

$list = [];
Route::define(
    Method::GET,
    '/snails',
    function (Request $request) use (&$list) {
    
    }
);
```

### Data heap area

```php
<?php

// Path: app/http/readme.php

// Other routes...

Route::define(Method::GET, '/snail-tail', function (Request $request) {
    \Co\async(fn () => Perform a running snail ($request));

    $stream = $request->stream;
    $id = $request->stream->id;
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

### Module reference

For example, I have a file like this `app/database/mysql.php`:

```php
<?php declare(strict_types=1);

// Path: /app/database/mysql.php

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;

$config = MysqlConfig::fromString("host=tfb-database port=3306 user=benchmarkdbuser password=benchmarkdbpass db=hello_world");

return new MysqlConnectionPool($config);
```

Data reference

```php
$pool = Package::import('database/mysql');

// Any import will get the same reference
$pool = Package::import('database/mysql');

// Other routes...

Route::define(\Psc\Core\Http\Enum\Method::GET, '/db', function (Request $request) use ($pool, &$dateFormatted) {
    $once = $pool->prepare('SELECT * FROM `World` WHERE id = :id')->execute(['id' => randomInt()]);
    $request->respondJson($once->fetchRow());
});
```

```php
<?php declare(strict_types=1);

// Path: /app/http/readme.php

// Other routes

Data::$mysqlConnectionPool = Package::import('database/mysql');

Route::define(\Psc\Core\Http\Enum\Method::GET, '/db', function (Request $request) use ($pool, &$dateFormatted) {
    $once = $pool->prepare('SELECT * FROM `World` WHERE id = :id')->execute(['id' => randomInt()]);
    $request->respondJson($once->fetchRow());
});

class Data
{
    public static array $list = [];
    public static MysqlConnectionPool $mysqlConnectionPool;
}
```

### Static files

```php
Data::$faviconBinary = Package::import('static/favicon.ico');
Route::define(Method::GET, '/favicon.ico', function (Request $request) {
    $request->respond(Data::$faviconBinary, ['Content-Type' => 'image/x-icon']);
});

class Data
{
    public static array $list = [];
    public static MysqlConnectionPool $mysqlConnectionPool;
    public static string $faviconBinary;
}
```
