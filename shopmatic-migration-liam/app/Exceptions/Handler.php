<?php

namespace App\Exceptions;

use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\ApiController;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];
    
    /**
     * Report or log an exception.
     *
     * @param \Exception $exception
     *
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)){
            app('sentry')->captureException($exception);
        }
    
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($request->is('api*') || $request->is('web*')) {
            $controller = new Controller();
        
            if ($exception instanceof NotFoundHttpException) {
                return $controller->respondNotFound('This endpoint does not exist');
            } else if ($exception instanceof ModelNotFoundException) {
                return $controller->respondNotFound('The specified resource cannot be found or is no longer available');
            } else if ($exception instanceof InternalErrorException) {
                return $controller->respondInternalError();
            } else if ($exception instanceof MethodNotAllowedHttpException) {
                return $controller->respondMethodNotAllowed();
            } else if ($exception instanceof AuthorizationException || $exception instanceof AuthenticationException) {
                return $controller->respondUnauthorizedError();
            } else if ($exception instanceof ValidationException) {
                return $controller->respondBadRequestError(implode(" ", $exception->validator->errors()->all()));
            } else {
                $controller->addDebugInfo($this->convertExceptionToArray($exception));
                return $controller->respondInternalError();
            }
        }
        return parent::render($request, $exception);
    }
}
