<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        ErrorMessage::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Throwable $e)
    {
        if (envOverload('APP_DEBUG') === 'true') {
            parent::report($e);
        } else {
            if ($this->shouldntReport($e)) {
                return;
            }
            $errorMessage = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            Log::error($errorMessage);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $e)
    {
        $errorCode = $e->getCode();

        if ($errorCode == 2002) {
            echo json_encode(error_response('0x000022', 'common'));
        } else if ($errorCode == 10061) {
            echo json_encode(error_response('0x000023', 'common'));
        } else {
            if ($e instanceof NotFoundHttpException) {
                $url = $request->url();
                echo json_encode(error_response('404', '', '404:' . $url));
            } elseif ($e instanceof ErrorMessage) {
                echo json_encode(error_response(...$e->getCodeArray()));
            } else if ($e instanceof ResponseException) {
//                return response('data', 200);
                echo $e->returnErrorResponse();
            } else {
                $appDebug = envOverload('APP_DEBUG', false);
                if ($appDebug === 'true') {
                    echo json_encode(error_response('0x000013', '', json_encode(["file" => $e->getFile(), "line" => $e->getLine()])));
                } else {
                    echo json_encode(error_response('0x000013', 'common'));
                }
            }
        }
        exit;

        return parent::render($request, $e);
    }
}
