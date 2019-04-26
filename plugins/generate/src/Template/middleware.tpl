<?php
namespace App\Middleware;
use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Middleware\Middleware;

class %middleware%Middleware extends Middleware
{
    /**
     * Processes the request, this must be implemented
     *
     * @param \Origin\Controller\Request $request
     * @param \Origin\Controller\Response $response
     * @return \Origin\Controller\Response
     */
    public function process(Request $request, Response $response) : Response
    {

        // do something
        return $response;
    }
}