<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);
namespace Origin\Http\Middleware;

use Origin\Html\Html;
use Origin\Http\Request;
use Origin\Http\Response;

class MinifyMiddleware extends Middleware
{
    /**
     * Default configuration
     *
     * @var array
     */
    protected $defaultConfig = [
        /**
         * Ensures there is at least one space between each tag. (Recommended)
         */
        'conservativeCollapse' => true,
        /**
         * Minifies inline Javascript
         */
        'minifyJs' => true,
        /**
         * Minifies inline Styles
         */
        'minifyCss' => true
    ];

    /**
    * @param \Origin\Http\Request $request
    * @param \Origin\Http\Response $response
    * @return void
    */
    public function process(Request $request, Response $response): void
    {
        if ($response->type() === 'text/html' && $response->body()) {
            $this->minifyBody($response);
        }
    }

    /**
     * Handles the minfication
     *
     * @param \Origin\Http\Response $response
     * @return void
     */
    private function minifyBody(Response $response): void
    {
        $response->body(
            Html::minify($response->body(), $this->config())
        );
    }
}
