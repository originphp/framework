<?php
namespace %namespace%\Http\Middleware;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware\Middleware;

class %class%Middleware extends Middleware
{
    /**
     * This HANDLES the request. Use this to make changes to the request.
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    public function handle(Request $request) : void
    {

    }

    /**
     * This PROCESSES the response. Use this to make changes to the response.
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return void
     */
    public function process(Request $request, Response $response) : void
    {

    }
}