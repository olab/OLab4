<?php

namespace Entrada\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof TokenExpiredException) {
            return response()->json(['token_expired'], 401);
        } else if ($e instanceof TokenInvalidException) {
            return response()->json(['token_invalid', $e->getMessage()], 401);
        } else if ($e instanceof JWTException) {
            return response()->json(['token_not_found', $e->getMessage()], 401);
        } else if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json(['method_not_allowed'], $e->getStatusCode());
        } else if ($e instanceof AuthorizationException || $e instanceof UnauthorizedHttpException) {
            return response()->json(['not_authorized'], 403);
        } else if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
            return response()->json(['item_not_found'], 404);
        } else if ($e instanceof ValidationException) {
            return response()->json(['validation_error', $e->getMessage(), $e->validator->getMessageBag()->messages()], 400);
        }

        return parent::render($request, $e);
    }
}
