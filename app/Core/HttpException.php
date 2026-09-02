<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

class HttpException extends Exception
{
    private int $statusCode;

    public function __construct(int $statusCode, string $message = '', ?\Throwable $previous = null)
    {
        if ($message === '') {
            $message = match ($statusCode) {
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                419 => 'Page Expired (CSRF token mismatch)',
                422 => 'Unprocessable Entity',
                429 => 'Too Many Requests',
                500 => 'Internal Server Error',
                default => 'HTTP Error ' . $statusCode,
            };
        }

        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
