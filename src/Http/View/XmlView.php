<?php
declare(strict_types = 1);
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
namespace Origin\Http\View;

use Origin\Utility\Xml;
use Origin\Http\Controller\Controller;
use Origin\Utility\Inflector;

class XmlView
{
    /**
     * Request Object
     *
     * @var \Origin\Http\Request
     */
    public $request = null;
    /**
       * Response Object
       *
       * @var \Origin\Http\Response
       */
    public $response = null;

    /**
     * These are the view vars (needed by testing).
     *
     * @var array
     */
    public $viewVars = [];

    /**
     * Array keys to be serialized
     *
     * @var array
     */
    protected $serialize = [];

    public function __construct(Controller $controller)
    {
        $this->request = & $controller->request;
        $this->response = & $controller->response;
        $this->viewVars = & $controller->viewVars;
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
        if ($data === null and $this->request->type() === 'xml' and ! empty($this->serialize)) {
            $data = $this->serialize($this->serialize);
        }

        if (is_object($data) and method_exists($data, 'toXml')) {
            return $data->toXml();
        }
        
        if (is_array($data)) {
            return Xml::fromArray($data);
        }

        return $data;
    }

    /**
     * Serializes
     *
     * @param string|array $serialize
     * @return array
     */
    private function serialize($serialize) : array
    {
        if (is_string($serialize)) {
            $data = [];
            if (isset($this->viewVars[$serialize])) {
                $data = $this->toArray($this->viewVars[$serialize]);
            }
            
            if ($this->isNumericArray($data)) {
                $data = ['response' => [Inflector::singular($serialize) => $data]];
            } else {
                $data = ['response' => [$serialize => $data]];
            }
            
            return $data;
        }
        
        $data = ['response' => []];
        foreach ($serialize as $key) {
            if (isset($this->viewVars[$key])) {
                $data['response'][$key] = $this->toArray($this->viewVars[$key]);
            }
        }
        return $data;
    }


    /**
     * Converts an object to an array
     *
     * @param mixed $mixed
     * @return array
     */
    private function toArray($mixed) : array
    {
        if (is_object($mixed) and method_exists($mixed, 'toArray')) {
            $mixed = $mixed->toArray();
        }
        return (array) $mixed;
    }

    /**
     * Check if the array is a numerical one
     *
     * @param array $data
     * @return boolean
     */
    private function isNumericArray(array $data) : bool
    {
        $keys  = array_keys($data);
        $string = implode('', $keys);
        return (bool) ctype_digit($string);
    }
}
