<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use WorkOS\Exception\BaseRequestException;

class WorkOSException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'workos_error',
        public readonly ?string $debugInfo = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create from a WorkOS API exception.
     */
    public static function fromWorkOS(Throwable $e): self
    {
        $message = $e->getMessage();
        $errorCode = 'workos_error';

        // WorkOS SDK exceptions extend BaseRequestException with public properties
        if ($e instanceof BaseRequestException) {
            $message = $e->responseMessage ?? $e->responseErrorDescription ?? $message;
            $errorCode = $e->responseCode ?? $e->responseError ?? $errorCode;

            // If message is still JSON, try to parse it
            if (str_starts_with($message, '{')) {
                $decoded = json_decode($message, true);
                if ($decoded) {
                    $message = $decoded['message'] ?? $message;
                    $errorCode = $decoded['code'] ?? $errorCode;
                }
            }
        }

        // Handle case where message contains JSON error response
        if (str_contains($message, '"code"') && str_contains($message, '"message"')) {
            $decoded = json_decode($message, true);
            if ($decoded) {
                $message = $decoded['message'] ?? $message;
                $errorCode = $decoded['code'] ?? $errorCode;
            }
        }

        $debugInfo = null;
        if (config('app.debug')) {
            $debugInfo = sprintf(
                "%s in %s:%d\n%s",
                get_class($e),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
        }

        return new self($message, $errorCode, $debugInfo, $e->getCode(), $e);
    }

    /**
     * Convert to array for JSON response.
     */
    public function toArray(): array
    {
        $data = [
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
        ];

        if ($this->debugInfo && config('app.debug')) {
            $data['debug'] = $this->debugInfo;
        }

        return $data;
    }
}
