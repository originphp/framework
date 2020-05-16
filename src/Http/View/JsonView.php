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
declare(strict_types = 1);
namespace Origin\Http\View;

use Origin\Http\Controller\Controller;

class JsonView
{
    /**
     * Request Object
     *
     * @var \Origin\Http\Request
     */
    protected $request = null;
    /**
       * Response Object
       *
       * @var \Origin\Http\Response
       */
    protected $response = null;

    /**
     * These are the view vars (needed by testing).
     *
     * @var array
     */
    protected $viewVars = [];

    /**
     * Array keys to be serialized
     *
     * @var array
     */
    protected $serialize = [];

    public function __construct(Controller $controller)
    {
        $this->request = $controller->request();
        $this->response = $controller->response();
        $this->viewVars = $controller->viewVars();
        $this->serialize = $controller->serialize();
    }

    /**
     * Does the rendering
     *
     * @param mixed $data
     * @return string
     */
    public function render($data = null) : string
    {
        /**
         * If user requests JSON and serialize is set then use that
         */
        if ($data === null && $this->request->type() === 'json' && ! empty($this->serialize)) {
            $data = $this->serialize($this->serialize);
        }

        if (is_object($data) && method_exists($data, 'toJson')) {
            return $data->toJson();
        }

        return json_encode($data);
    }

    /**
     * Serializes the data
     *
     * @param string|array $serialize
     * @return array
     */
    private function serialize($serialize) : array
    {
        $result = [];
   
        if (is_string($serialize)) {
            if (isset($this->viewVars[$serialize])) {
                $result = $this->toArray($this->viewVars[$serialize]);
            }

            return $result;
        }

        foreach ($serialize as $key) {
            if (isset($this->viewVars[$key])) {
                $result[$key] = $this->toArray($this->viewVars[$key]);
            }
        }

        return $result;
    }

    /**
     * Converts an object to an array
     *
     * @param mixed $mixed
     * @return mixed
     */
    private function toArray($mixed)
    {
        if (is_object($mixed) && method_exists($mixed, 'toArray')) {
            $mixed = $mixed->toArray();
        }

        return $mixed;
    }
}
