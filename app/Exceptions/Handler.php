<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;

class Handler extends ExceptionHandler {

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        'Symfony\Component\HttpKernel\Exception\HttpException',
//        'UnexpectedValueException',
//        'Illuminate\Session\TokenMismatchException'
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e) {
        return parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e) {
        if ($e instanceof TokenMismatchException) {
            return redirect('auth/login')->with('msg', "Сессия истекла. Перезайдите в приложение.")->with('class', 'alert-warning');
        }
        if($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException){
            return parent::render($request, $e);
        }
        if ($e instanceof \ErrorException) {
            \App\Spylog\Spylog::logError(json_encode([
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => (string) $e->getCode(),
                'message' => $e->getMessage(),
                'trace' => html_entity_decode($e->getTraceAsString())
                    ]), true);
        }
        return parent::render($request, $e);
        return response()->view('errors.500', [], 500);
    }

}
