<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Http;

use Origin\Exception\HttpException;

class ExceptionRenderer
{
    /**
    * Undocumented variable
    *
    * @var \Origin\Http\Request
    */
    protected $request;
    /**
     * Undocumented variable
     *
     * @var \Origin\Http\Response
     */
    protected $response;

    /**
     * Construtor
     *
     * @param Request $request (Required for ajax detection)
     * @param Response $response (not required, but might need to be replaced)
     */
    public function __construct(Request $request, Response $response = null)
    {
        $this->request = $request;
        $this->response = $response ? $response : new Response();
    }

    public function render($exception, $debug = false) : Response
    {
        if ($debug) {
            $errorCode = $exception->getCode();
            $errorMessage = $exception->getMessage();
        } else {
            list($errorCode, $errorMessage) = $this->getErrorCodeAndMessage($exception);
        }
       
        if ($this->request->ajax() or $this->request->type() === 'json') {
            $body = json_encode(['error' => ['message' => $errorMessage, 'code' => $errorCode]]);
        } else {
            ob_start();
            include $this->getFileToRender($exception);
            $body = ob_get_clean();
        }
      
        $this->response->body($body);
        $this->response->statusCode($exception->getCode());

        return $this->response;
    }

    protected function getErrorCodeAndMessage($exception) : array
    {
        $errorCode = ($exception->getCode() === 404) ? 404 : 500;
        $errorMessage = ($exception->getCode() === 404) ? 'Not Found' : 'An Internal Error has Occured';
        if ($exception instanceof HttpException and $exception->getCode() < 500) {
            $errorCode = $exception->getCode();
            $errorMessage = $exception->getMessage(); # used in rendering
        }

        return [$errorCode,$errorMessage];
    }
    protected function getFileToRender($exception)
    {
        $errorCode = ($exception->getCode() === 404) ? 404 : 500;
        $error400 = SRC . DS . 'View' . DS . 'Error' . DS .  '400.ctp';
      
        $file = SRC . DS . 'View' . DS . 'Error' . DS . $errorCode . '.ctp';
        if ($exception instanceof HttpException and file_exists($error400) and $exception->getCode() < 500) {
            $file = $error400;
        }

        return $file;
    }
}
