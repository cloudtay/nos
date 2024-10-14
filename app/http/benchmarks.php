<?php declare(strict_types=1);

// Docs: This file does not belong to the entry file of the http module and will not be automatically loaded by the Nos framework.

namespace app\http;

use Amp\Mysql\MysqlConnectionPool;
use Amp\Mysql\MysqlStatement;
use Cloudtay\Nos\Http\Method;
use Cloudtay\Nos\Http\Route\Route;
use Cloudtay\Nos\Package;
use DateTime;
use DateTimeZone;
use Psc\Core\Http\Server\Request;
use Throwable;

use function intval;
use function is_numeric;
use function ob_get_clean;
use function ob_start;
use function random_int;
use function usort;

final class Setup
{
    public static string              $dateFormatted;
    public static MysqlConnectionPool $mysqlConnectionPool;
    public static MysqlStatement      $statementWorld;
    public static MysqlStatement      $statementFortune;
    public static MysqlStatement      $statementUpdate;
}

/**
 * We recommend annotating the import package list with @documents before all imports to get better code hints in the IDE
 * Looking forward to Nos extensions or PHP pan-ness support
 */

Setup::$mysqlConnectionPool = Package::import('database/mysql-tfb');
Setup::$statementWorld      = Setup::$mysqlConnectionPool->prepare('SELECT * FROM `World` WHERE id = :id');
Setup::$statementFortune    = Setup::$mysqlConnectionPool->prepare('SELECT * FROM `Fortune`');
Setup::$statementUpdate     = Setup::$mysqlConnectionPool->prepare('UPDATE `World` SET randomNumber = :randomNumber WHERE id = :id');

try {
    $date                 = new DateTime('now', new DateTimeZone('GMT'));
    Setup::$dateFormatted = $date->format('D, d M Y H:i:s T');
} catch (Throwable) {
    return;
}

\Co\repeat(static function () {
    $date                 = new DateTime('now', new DateTimeZone('GMT'));
    Setup::$dateFormatted = $date->format('D, d M Y H:i:s T');
}, 1);

/**
 * @return int
 * @throws \Random\RandomException
 */
function randomInt(): int
{
    return random_int(1, 10000);
}

/**
 * @param mixed $value
 *
 * @return int
 */
function clamp(mixed $value): int
{
    if (!is_numeric($value) || $value < 1) {
        return 1;
    }
    if ($value > 500) {
        return 500;
    }
    return intval($value);
}

/**
 * @param string $template
 * @param array  $data
 *
 * @return string
 */
function render(string $template, array $data = []): string
{
    foreach ($data as $key => $value) {
        $$key = $value;
    }

    ob_start();
    include $template;
    return ob_get_clean();
}

/**
 * @method GET
 * @path /json
 */
Route::define(Method::GET, '/json', static function (Request $request) {
    $request->respondJson(
        ['message' => 'Hello, World!'],
        ['Date' => Setup::$dateFormatted]
    );
});

/**
 * @method GET
 * @path /db
 */
Route::define(Method::GET, '/db', static function (Request $request) {
    \Co\async(static function () use ($request) {
        $once = Setup::$statementWorld->execute(['id' => randomInt()]);
        $request->respondJson(
            $once->fetchRow(),
            ['Date' => Setup::$dateFormatted]
        );
    });
});

/**
 * @method GET
 * @path /queries
 */
Route::define(Method::GET, '/queries', static function (Request $request) {
    $queries = clamp($request->GET['queries'] ?? 1);
    $rows    = [];

    while ($queries--) {
        $rows[] = Setup::$statementWorld->execute(['id' => randomInt()])->fetchRow();
    }

    $request->respondJson($rows, ['Date' => Setup::$dateFormatted]);
});

/**
 * @method GET
 * @path /fortunes
 */
Route::define(Method::GET, '/fortunes', static function (Request $request) {
    $result = Setup::$statementFortune->execute();
    $rows   = [];
    foreach ($result as $row) {
        $rows[] = $row;
    }

    $rows[] = ['id' => 0, 'message' => 'Additional fortune added at request time.'];
    usort($rows, static function ($a, $b) {
        return $a['message'] <=> $b['message'];
    });

    $request->respondHtml(
        render('view/fortunes.blade.php', ['rows' => $rows]),
        [
            'Date'         => Setup::$dateFormatted,
            'Content-Type' => 'text/html; charset=UTF-8'
        ]
    );
});

/**
 * @method GET
 * @path /updates
 */
Route::define(Method::GET, '/updates', static function (Request $request) {
    $queries = clamp($request->GET['queries'] ?? 1);
    $rows    = [];

    while ($queries--) {
        $query = Setup::$statementWorld->execute(['id' => randomInt()]);
        $row   = $query->fetchRow();

        Setup::$statementUpdate->execute([
            'randomNumber' => randomInt(),
            'id'           => $row['id'],
        ]);

        $rows[] = $row;
    }

    $request->respondJson($rows, ['Date' => Setup::$dateFormatted]);
});

/**
 * @method GET
 * @path /plaintext
 */
Route::define(Method::GET, '/plaintext', static function (Request $request) {
    $request->respond(
        'Hello, World!',
        [
            'Date'         => Setup::$dateFormatted,
            'Content-Type' => 'text/plain; charset=UTF-8'
        ]
    );
});
