<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
      $this->reportable(function (Throwable $e) {
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            Log::error('CORS Error: ' . $e->getMessage());
        }
    });
    }

    public function report(Throwable $exception)
    {
        if ($this->shouldReport($exception)) {
            Log::error('Detailed exception report: ', [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        parent::report($exception);
    }

}
