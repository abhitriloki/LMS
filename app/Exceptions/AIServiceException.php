<?php

namespace App\Exceptions;

use Exception;

class AIServiceException extends Exception
{
    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = 'AI service error occurred', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        \Log::error('AI Service Exception', [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'trace' => $this->getTraceAsString()
        ]);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AI_SERVICE_ERROR',
                    'message' => $this->getMessage(),
                ]
            ], 500);
        }

        return back()->with('error', 'AI service is temporarily unavailable. Please try again later.');
    }
}
