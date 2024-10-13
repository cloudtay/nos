<?php declare(strict_types=1);

use Amp\Mysql\MysqlConnectionPool;
use Cloudtay\Nos\Http\Method;
use Cloudtay\Nos\Http\Route\Route;
use Cloudtay\Nos\Package;
use Psc\Core\Http\Server\Chunk;
use Psc\Core\Http\Server\Request;

//Data::$mysqlConnectionPool = Package::import('database/mysql-readme');
Data::$faviconBinary = Package::import('static/favicon.ico');

Route::define(Method::GET, '/door1', function (Request $request) {
    $request->respond('is door1.');
});

Route::define(Method::GET, '/door2', function (Request $request) {
    $request->respond('is door2.');
});

Route::define(Method::GET, '/snail', function (Request $request) {
    $request->respond(function () {
        $hello = 'i am a happy little snail! ';
        while (1) {
            for ($i = 0; $i < \strlen($hello); $i++) {
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

function aRunningSnail(Request $request): void
{
    $request->respond(function () use ($request) {
        yield Chunk::chunk("[{$request->stream->id}]");
        $hello = 'i am a happy little snail! ';
        while (1) {
            for ($i = 0; $i < \strlen($hello); $i++) {
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
    \Co\async(fn () => \aRunningSnail($request));

    $stream          = $request->stream;
    $id              = $request->stream->id;
    Data::$list[$id] = $stream;

    $stream->onClose(function () use ($id) {
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

//Route::define(\Psc\Core\Http\Enum\Method::GET, '/db', function (Request $request) use (&$dateFormatted) {
//    $once = Data::$mysqlConnectionPool->prepare('SELECT * FROM `World` WHERE id = :id')->execute(['id' => randomInt()]);
//    $request->respondJson($once->fetchRow());
//});

//Route::define(Method::GET, '/favicon.ico', function (Request $request) {
//    $request->respond(Data::$faviconBinary, ['Content-Type' => 'image/x-icon']);
//});

class Data
{
    public static array               $list = [];
    public static MysqlConnectionPool $mysqlConnectionPool;
    public static string              $faviconBinary;
}
