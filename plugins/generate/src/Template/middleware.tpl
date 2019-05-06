<?php
namespace App\Middleware;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware;

class %middleware%Middleware extends Middleware
{
    /**
     * Processes the request, this must be implemented
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Response
     */
    public function process(Request $request, Response $response) : Response
    {

        // do something
        return $response;
    }
}