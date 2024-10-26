<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Class ApiException
 *
 * Custom exception class for API-related errors.
 *
 * @package App\Exceptions
 */
class ApiException extends Exception
{
    /** @var int */
    protected int $statusCode;

    /** @var string|null */
    protected ?string $errorCode;

    /**
     * ApiException constructor.
     *
     * @param string $message The error message
     * @param int $statusCode The HTTP status code
     * @param string|null $errorCode A custom error code
     * @param Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(string $message = "", int $statusCode = 400, $errorCode = null, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the custom error code.
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
