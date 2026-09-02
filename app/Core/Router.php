<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

class Router
{
    private array $routes = [];
    private array $groupStack = [];
    private array $globalMiddleware = [];

    public function use(string|callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    public function get(string $uri, mixed $action, array $middleware = []): self
    {
        return $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, mixed $action, array $middleware = []): self
    {
        return $this->addRoute('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, mixed $action, array $middleware = []): self
    {
        return $this->addRoute('PUT', $uri, $action, $middleware);
    }

    public function delete(string $uri, mixed $action, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $uri, mixed $action, array $middleware = []): self
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $gm = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $groupMiddleware = array_merge($groupMiddleware, $gm);
            }
        }

        $fullUri = '/' . trim($prefix . '/' . trim($uri, '/'), '/');
        $allMiddleware = array_merge($groupMiddleware, $middleware);

        // Convert {param} to named regex (?P<param>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $fullUri);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $fullUri,
            'regex' => $regex,
            'action' => $action,
            'middleware' => $allMiddleware,
        ];

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->uri();

        $matchedRoute = null;
        $matchedParams = [];
        $methodNotAllowed = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                if ($route['method'] !== $method) {
                    $methodNotAllowed = true;
                    continue;
                }

                // Extract named matches
                $params = [];
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = $v;
                    }
                }

                $matchedRoute = $route;
                $matchedParams = $params;
                break;
            }
        }

        if ($matchedRoute === null) {
            if ($methodNotAllowed) {
                return $this->errorResponse($request, 405, 'Method Not Allowed');
            }
            return $this->errorResponse($request, 404, 'Page Not Found');
        }

        $request->setRouteParams($matchedParams);

        // Build middleware pipeline
        $pipeline = array_merge($this->globalMiddleware, $matchedRoute['middleware']);

        $runner = function (Request $req) use ($matchedRoute, $matchedParams): Response {
            $action = $matchedRoute['action'];

            if (is_callable($action)) {
                $result = call_user_func_array($action, array_merge([$req], array_values($matchedParams)));
            } elseif (is_array($action) && count($action) === 2) {
                [$controller, $methodName] = $action;
                $instance = is_string($controller) ? new $controller() : $controller;
                $result = call_user_func_array([$instance, $methodName], array_merge([$req], $matchedParams));
            } else {
                throw new HttpException(500, 'Invalid route handler');
            }

            if ($result instanceof Response) {
                return $result;
            }

            if (is_array($result) || is_object($result)) {
                return Response::json($result);
            }

            return Response::html((string) $result);
        };

        // Wrap pipeline in reverse order
        $pipeline = array_reverse($pipeline);
        foreach ($pipeline as $middleware) {
            $next = $runner;
            $runner = function (Request $req) use ($middleware, $next): Response {
                if (is_string($middleware)) {
                    $mw = new $middleware();
                    return $mw->handle($req, $next);
                }
                if (is_callable($middleware)) {
                    return $middleware($req, $next);
                }
                return $next($req);
            };
        }

        try {
            return $runner($request);
        } catch (HttpException $e) {
            return $this->errorResponse($request, $e->getStatusCode(), $e->getMessage());
        } catch (Throwable $e) {
            if (config('app.debug', false)) {
                $msg = $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString();
                return $this->errorResponse($request, 500, $msg);
            }
            return $this->errorResponse($request, 500, 'Internal Server Error');
        }
    }

    private function errorResponse(Request $request, int $status, string $message): Response
    {
        if ($request->isJson()) {
            return Response::json(['error' => $message, 'status' => $status], $status);
        }

        try {
            return Response::html(View::render('errors.error', ['status' => $status, 'message' => $message]), $status);
        } catch (Throwable) {
            return Response::html(
                "<!DOCTYPE html><html><head><title>Error {$status}</title><style>body{background:#060406;color:#f4ecec;font-family:sans-serif;padding:4rem;text-align:center;}</style></head><body><h1>{$status}</h1><p>" . e($message) . "</p><p><a href='/' style='color:#00f0ff;'>Return to Arena</a></p></body></html>",
                $status
            );
        }
    }
}
