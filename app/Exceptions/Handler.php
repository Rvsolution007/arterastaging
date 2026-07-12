<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'Too Many Attempts. Please try again later.'], 429);
            }
            return back()->withInput()->withErrors(['email' => 'Too many attempts. Please try again later.'])->with('error', 'Too many attempts. Please try again later.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            // If it's a validation exception or authentication exception, let Laravel handle it
            if ($e instanceof \Illuminate\Validation\ValidationException || $e instanceof \Illuminate\Auth\AuthenticationException) {
                return parent::render($request, $e);
            }

            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("[$correlationId] " . $e->getMessage(), ['exception' => $e]);
            
            if (!config('app.debug')) {
                return response()->json([
                    'error' => 'An unexpected error occurred. Please contact support.',
                    'reference_id' => $correlationId
                ], 500);
            }
        }

        return parent::render($request, $e);
    }
}
