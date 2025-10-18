<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

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

        // Responder JSON para autenticación / autorización de forma consistente
        $this->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json([
                'message' => 'No autenticado.',
                'hint' => 'Proporcione un token válido en Authorization: Bearer <token>'
            ], \Symfony\Component\HttpFoundation\Response::HTTP_UNAUTHORIZED);
        });

        $this->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            return response()->json([
                'message' => 'No autorizado.',
                'detail' => $e->getMessage() ?: 'No tiene permisos para realizar esta acción.'
            ], \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
        });
    }

}
