<?php
namespace %namespace%\Middleware;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware;

class %class%Middleware extends Middleware
{
    /**
     * This HANDLES the request. Use this to make changes to the request.
     *
     * @param \Origin\Http\Request $request
     */
    public function startup(Request $request)
    {

    }

    /**
     * This PROCESSES the response. Use this to make changes to the response.
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Response
     */
    public function shutdown(Request $request, Response $response) 
    {

    }
}