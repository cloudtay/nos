<?php declare(strict_types=1);

// Docs: This file belongs to the entry file of the http module and will be automatically loaded by the Nos framework.

use Cloudtay\Nos\Http\Route\Route;
use Cloudtay\Nos\Kernel;
use Cloudtay\Nos\Package;
use Co\Net;
use Psc\Core\Http\Server\Request;
use Psc\Core\Http\Server\Server;
use Psc\Utils\Output;
use Psc\Worker\Manager;

//Package::import('example');
//Package::import('readme');
//Package::import('benchmarks');

\Co\forked(static function () {
    // Reference the package in the child process to avoid polluting the main process,
    // thereby enabling the business part to support hot updates

    Package::import('example');
    Package::import('readme');
    Package::import('benchmarks');
});

$worker = new class (\getenv('NOS_HTTP_LISTEN'), \getenv('NOS_HTTP_WORKERS')) extends \Psc\Worker\Worker {
    /*** @var string */
    protected string $listen = 'http://127.0.0.1:8008';

    /*** @var int */
    protected int $count = 1;

    /*** @var string */
    protected string $name = 'http-server';

    /*** @var Server */
    private Psc\Core\Http\Server\Server $server;

    /**
     * @param string $listen
     * @param int    $count
     */
    public function __construct(mixed $listen, mixed $count)
    {
        $this->listen = \strval($listen ?: 'http://127.0.0.1:8008');
        $this->count  = \intval($count ?: 1);
    }

    /**
     * @param Manager $manager
     *
     * @return void
     */
    public function register(Manager $manager): void
    {
        $this->server = Net::Http()->server($this->listen, [
            'socket' => [
                'so_reuseport' => 1,
                'so_reuseaddr' => 1
            ]
        ]);

        Output::writeln("HTTP server listening on {$this->listen} x {$this->count}");
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->server->onRequest(
            static fn (Request $request) => Route::dispatch($request)
        );
        $this->server->listen();
    }
};

Kernel::manager()->addWorker($worker);
return $worker;
