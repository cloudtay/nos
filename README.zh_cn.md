## 主题

> "PHP是一种编程语言，但PHP本身可以被认为是一个框架"

意味着你如果掌握了PHP,那么你就彻底掌握 `Nos`框架

### 引言

这是一个真正意义上的静态`PHP`(常驻内存)项目, 源自本人某次突发奇想的延伸, 文中观点仅代表个人愚见)
虽然它基于`ripple`协程引擎开发且仅仅不到10个文件, 但在我心目中, `ripple`与之相比都要逊色许多
为此我准备了个易拆解的脚手架 **(即当前项目)** 并通过:

- 《路由》
- 《奔跑的蜗牛》
- 《腚件》
- 《静态类》
- 《数据库连接池》
- 《Package复用》
- 《静态缓存》

的典型例子来展示`Nos`的一些特性, 文中提到的所有例子都可在文章末尾一键部署复现, 旨在探讨娱乐, 切勿对号入座

### 尽在代码中

从创建一个路由组开始, 它没有控制器与中间件, 仅需一个文件`app/http/readme.php`

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

Package::import('room');

// more...
```

可能有同学会问 > 那和其他框架有什么区别? 其他框架不也是一个`route.php`就能创建路由吗? 中间件呢验证器呢?

对此我嗤之以鼻 ) 某天你创建了一个路由并返回了一个 `hello,world` 但它返回了一个 `world,hello` 你会怎么办?

`Nos`与之不同的是, 它将在请求到达时**掌握一切**, 例如这段代码

```php
Route::define(Method::GET, '/snail', static function (Request $request) use ($favicon) {
    $request->respond(static function () {
        $hello = 'I am a happy little snail!';
        while (1) {
            for ($i = 0; $i < strlen($hello); $i++) {
                yield Chunk::chunk($hello[$i]);
                \Co\sleep(0.1);
            }
            yield Chunk::chunk('...');
            \Co\sleep(1);
        }
    }, [
        'Transfer-Encoding' => 'chunked',
        'Content-Type'      => 'text/html; charset=utf-8',
        'Cache-Control'     => 'no-cache',
    ]);
});
```

你将在访问时看到如图效果:

> <img src="assets/snail.gif" width="100%"></img>

### 腚件

那么有人会问, 你的框架没有中间件, 难不成你要我在每个路由中写一遍?

非也, `Nos` 中虽然没有中间件, 但是有更强大的件 ➡《腚件》

以下例子我将随手搓出一个腚件

```php
<?php declare(strict_types=1);

// Path: app/http/readme.php

// 其他路由

/**
 * 随手搓出的腚件
 * @param \Psc\Core\Http\Server\Request $request
 * @return void
 */
function 表演一只奔跑的蜗牛(Request $request): void
{
    $request->respond(static function () {
        $hello = 'I am a happy little snail!';
        while (1) {
            for ($i = 0; $i < strlen($hello); $i++) {
                yield Chunk::chunk($hello[$i]);
                \Co\sleep(0.1);
            }
            yield Chunk::chunk('...');
            \Co\sleep(1);
        }
    }, [
        'Transfer-Encoding' => 'chunked',
        'Content-Type'      => 'text/html; charset=utf-8',
        'Cache-Control'     => 'no-cache',
    ]);
}

Route::define(Method::GET, '/snail-tail', function (Request $request) {
    表演一只奔跑的蜗牛($request);
});
```

上述例子中, 我创建了一个腚件, 该腚件将为每一个请求提供一只奔跑的蜗牛,
谁能告诉我什么场景是腚件实现不了的吗?

### 一切皆可引用

> 注意我此前说的`掌握一切`并非空话, 例如我将在任何场景让这只蜗牛停下

我只需要手搓一个`$list`

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

是的, 我搓出一个多路由共享谁想用谁引用的`$list`以达到目的

> 创建蜗牛API http://127.0.0.1:8008/snail-tail  
> 停止蜗牛API http://127.0.0.1:8008/snail-stop?id={蜗牛ID}

```php
$list = [];

Route::define(Method::GET, '/snail-tail', function (Request $request) {
    \Co\async(fn () => 表演一只奔跑的蜗牛($request));

    $stream    = $request->stream;
    $id        = $request->stream->id;
    $list[$id] = $stream;

    $stream->onClose(function () use (&$list, $id) {
        unset($list[$id]);
    });
});

Route::define(Method::GET, '/snail-stop', function (Request $request) use (&$list) {
    if (!$id = $request->GET['id'] ?? null) {
        $request->respond('please provide id.');
        return;
    }

    if (isset($list[$id])) {
        $list[$id]->write(Chunk::chunk('stoooooooooop!'));
        $list[$id]->close();
        $request->respond('snail ' . $id . ' is gone.');
    } else {
        $request->respond('snail ' . $id . ' is not found.');
    }
});
```

上述例子中我使用了一个`$list`在两个路由中共同管理一个请求, 实现了一只蜗牛的产生与终止

### 你掌握了PHP?

细心看代码的同学就会发现: 地狱般的use简直糟糕透顶!!!!!

我想告诉你, 我也这么认为, 但很快我会告诉你答案

```php
<?php

// Path: app/http/readme.php

// 其他路由...

Route::define(Method::GET, '/snail-tail', function (Request $request) {
    \Co\async(fn () => 表演一只奔跑的蜗牛($request));

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

没错,就是这么简单, 如果你很烦躁这些`use`的话, 你可以将它们通过静态属性共享

> 每个文件都定义一个`Class Data`, 不会报错吗?

^ 能问出上述问题的同学建议重修`PHP`的命名空间, 虽然是纯静态PHP, 但也可以用`namespace`

---

这么便捷的开发效率, 谁能告诉我, 我到底需不需要对象? 对象有什么用?

hhh, 其实还是需要的, 对象也要 `object` 也得要

毕竟`Session`,`View`, `Container`,`Database` 有时候还是离不开

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

注意该文件的返回可以**在多处被导入并返回相同的引用**

#### 回到路由`readme.php`

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

只需如此你就可以愉快地使用数据库了

#### 我相信不会有人喜欢`use`, 因此可以这样:

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

事实上本文的主题是:

#### 静态!静态!

为什么说他是一个静态框架? 因为他的一切都可以是静态的

比如将静态文件引用至内存中:

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

### 一切皆可导入

### 文末

上述例子中通过

- 路由
- 奔跑的蜗牛
- 腚件
- 静态类
- 数据库连接池
- Package引用

展示了`Nos`框架的一些特性,事实上你还可以手动构建一个SessionManager, 一个View, 一个Container,甚至整个
`Laravel/Application` 当然这是一个很大的工程, 我相信你能做到

以下是一键部署体验

#### 安装

```bash
composer create-project --stability=dev cloudtay/nos
```

#### 附加项

> 本项目的数据库用到了`techempower/mysql:latest`数据例子, 你可以通过以下命令启动以确保复现每个步骤

```bash
docker run -d --name tfb-database -p 3306:3306 techempower/mysql:latest
````

#### 运行应用

```bash
NOS_APP_PATH=app vendor/bin/nos
```

#### 可选参数

```bash
NOS_APP_PATH=app \
NOS_HTTP_LISTEN=http://127.0.0.1:8080 \
NOS_HTTP_WORKER=1 \
vendor/bin/nos
```
