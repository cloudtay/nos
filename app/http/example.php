<?php declare(strict_types=1);

// Docs: This file does not belong to the entry file of the http module and will not be automatically loaded by the Nos framework.

use Cloudtay\Nos\Http\Method;
use Cloudtay\Nos\Http\Route\Route;
use Cloudtay\Nos\Package;
use Co\IO;
use Psc\Core\Http\Server\Chunk;
use Psc\Core\Http\Server\Request;
use Psc\Worker\Command;

/**
 * We recommend annotating the import package list with @documents before all imports to get better code hints in the IDE
 * Looking forward to Nos extensions or PHP pan-ness support
 *
 * @var \Psc\Worker\Worker $http
 * @var string             $http
 * @var string             $indexTemplate
 */
$http          = Package::import('http');
$indexTemplate = Package::import('view/index.blade.php');
$favicon       = Package::import('static/favicon.ico');

/**
 * @method GET
 * @path /favicon.ico
 */
Route::define(Method::GET, '/favicon.ico', static function (Request $request) use ($favicon) {
    $request->respond($favicon, ['Content-Type' => 'image/x-icon']);
});

/**
 * @method GET
 * @path /
 */
Route::define(Method::GET, '/', static function (Request $request) use ($indexTemplate) {
    $request->respondHtml(
        $indexTemplate,
        ['Content-Type' => 'text/html']
    );
});

/**
 * @method GET
 * @path /hello
 */
Route::define(Method::GET, '/hello', static function (Request $request) {
    $request->respondText('Hello, World!');
});

/**
 * @method POST
 * @path /broadcast
 */
Route::define(Method::POST, '/broadcast', static function (Request $request) use ($http) {
    if ($message = $request->POST['message'] ?? null) {
        $command = Command::make('message', [$message]);
        $http->commandToWorker($command, 'ws-server');
        $request->respondJson(['message' => 'Message sent!']);
        return;
    }

    $request->respondJson(
        \json_encode(
            ['error' => 'Message is required!']
        )
    );
});

/**
 * @method GET
 * @path /sse
 */
Route::define(Method::GET, '/sse', static function (Request $request) {
    $content = static function () {
        for ($i = 0; $i < 10; $i++) {
            yield Chunk::event('message', 'Hello, World!', \strval($i));
            \Co\sleep(1);
        }

        return false;
    };

    $request->respond($content(), [
        'Content-Type'  => 'text/event-stream',
        'Cache-Control' => 'no-cache',
    ]);
});

/**
 * @method GET
 * @path /download
 */
Route::define(Method::GET, '/download', static function (Request $request) {
    $request->respond(IO::File()->open(__FILE__, 'r'));
});
