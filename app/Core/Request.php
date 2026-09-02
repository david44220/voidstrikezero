<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private array $queryParams;
    private array $bodyParams;
    private ?array $jsonParams = null;
    private array $headers = [];
    private array $routeParams = [];
    private array $files = [];
    private array $cookies = [];
    private string $ip;

    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $headers = [],
        array $files = [],
        array $cookies = [],
        string $ip = '127.0.0.1'
    ) {
        $this->method = strtoupper($method);
        $this->uri = '/' . trim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');
        $this->queryParams = $query;
        $this->bodyParams = $body;
        $this->headers = $headers;
        $this->files = $files;
        $this->cookies = $cookies;
        $this->ip = $ip;
    }

    public static function create(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $headers = [],
        array $files = [],
        array $cookies = [],
        string $ip = '127.0.0.1'
    ): self {
        if (empty($query) && ($queryString = parse_url($uri, PHP_URL_QUERY))) {
            parse_str($queryString, $query);
        }
        return new self($method, $uri, $query, $body, $headers, $files, $cookies, $ip);
    }

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $query = $_GET;
        $body = $_POST;
        $files = $_FILES;
        $cookies = $_COOKIE;

        // Parse all request headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$headerName] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'REMOTE_ADDR'])) {
                $headerName = strtolower(str_replace('_', '-', $key));
                $headers[$headerName] = (string) $value;
            }
        }

        // IP detection
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $req = new self($method, $uri, $query, $body, $headers, $files, $cookies, $ip);

        // Parse raw JSON if payload is application/json
        $contentType = $req->header('content-type', '');
        if (str_contains(strtolower($contentType), 'application/json')) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $req->jsonParams = $decoded;
                }
            }
        }

        return $req;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function ip(): string
    {
        return $this->ip;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->bodyParams;
        }
        return $this->bodyParams[$key] ?? $default;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonParams === null) {
            return $key === null ? [] : $default;
        }
        if ($key === null) {
            return $this->jsonParams;
        }
        return $this->jsonParams[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->jsonParams !== null && array_key_exists($key, $this->jsonParams)) {
            return $this->jsonParams[$key];
        }
        if (array_key_exists($key, $this->bodyParams)) {
            return $this->bodyParams[$key];
        }
        if (array_key_exists($key, $this->queryParams)) {
            return $this->queryParams[$key];
        }
        if (array_key_exists($key, $this->routeParams)) {
            return $this->routeParams[$key];
        }
        return $default;
    }

    public function all(): array
    {
        $all = array_merge($this->queryParams, $this->bodyParams, $this->routeParams);
        if ($this->jsonParams !== null) {
            $all = array_merge($all, $this->jsonParams);
        }
        return $all;
    }

    public function has(string $key): bool
    {
        return $this->input($key) !== null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower($name);
        return $this->headers[$normalized] ?? $default;
    }

    public function file(string $key): ?array
    {
        if (!isset($this->files[$key]) || !is_array($this->files[$key])) {
            return null;
        }
        return $this->files[$key];
    }

    public function cookie(string $key, ?string $default = null): ?string
    {
        return $this->cookies[$key] ?? $default;
    }

    public function isJson(): bool
    {
        $accept = $this->header('accept', '');
        $contentType = $this->header('content-type', '');
        return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }
}
