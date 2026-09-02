<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    private int $statusCode;
    private array $headers = [];
    private string $content;
    private array $cookies = [];

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'text/html; charset=UTF-8';
        return new self($html, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return new self($json ?: '{}', $status, $headers);
    }

    public static function redirect(string $url, int $status = 302, array $headers = []): self
    {
        $headers['Location'] = $url;
        return new self('', $status, $headers);
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $k => $v) {
            $this->headers[$k] = $v;
        }
        return $this;
    }

    public function cookie(
        string $name,
        string $value,
        int $expire = 0,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Strict'
    ): self {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ];
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }

            foreach ($this->cookies as $cookie) {
                setcookie(
                    $cookie['name'],
                    $cookie['value'],
                    [
                        'expires' => $cookie['expire'],
                        'path' => $cookie['path'],
                        'domain' => $cookie['domain'] ?? '',
                        'secure' => $cookie['secure'],
                        'httponly' => $cookie['httponly'],
                        'samesite' => $cookie['samesite'],
                    ]
                );
            }
        }

        echo $this->content;
    }
}
