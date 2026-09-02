<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use Tests\TestCase;

class RouterTest extends TestCase
{
    public function testRouteDispatchAndParameters(): void
    {
        $router = new Router();
        $capturedId = null;

        $router->get('/pilot/{id}', function (Request $req, string $id) use (&$capturedId) {
            $capturedId = $id;
            return Response::html("Pilot: {$id}");
        });

        $request = Request::create('GET', '/pilot/vortex_01');
        $response = $router->dispatch($request);

        $this->assertEquals('vortex_01', $capturedId);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Pilot: vortex_01', $response->getContent());
    }

    public function testRoutePrefixAndGrouping(): void
    {
        $router = new Router();
        $called = false;

        $router->group(['prefix' => '/sector'], function (Router $r) use (&$called) {
            $r->get('/scan', function () use (&$called) {
                $called = true;
                return Response::json(['status' => 'scanned']);
            });
        });

        $request = Request::create('GET', '/sector/scan');
        $response = $router->dispatch($request);

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRouteMiddlewarePipeline(): void
    {
        $router = new Router();
        $executionOrder = [];

        $mw1 = function (Request $req, callable $next) use (&$executionOrder): Response {
            $executionOrder[] = 'mw1_before';
            $res = $next($req);
            $executionOrder[] = 'mw1_after';
            return $res;
        };

        $router->get('/shield', function () use (&$executionOrder) {
            $executionOrder[] = 'handler';
            return Response::html('shield active');
        }, [$mw1]);

        $request = Request::create('GET', '/shield');
        $router->dispatch($request);

        $this->assertEquals(['mw1_before', 'handler', 'mw1_after'], $executionOrder);
    }

    public function testNotFoundReturns404(): void
    {
        $router = new Router();
        $request = Request::create('GET', '/non-existent-sector');
        $response = $router->dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
