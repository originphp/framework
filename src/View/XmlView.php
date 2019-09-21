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
namespace Origin\View;

use Origin\Utility\Xml;
use Origin\Http\Serializer;
use Origin\Controller\Controller;

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
     * @param [type] $data
     * @param integer $status
     * @return void
     */
    public function render($data = null, $status = 200)
    {
        /**
         * If user requests JSON and serialize is set then use that
         */
        if ($data === null and $this->request->type() === 'xml' and ! empty($this->serialize)) {
            $serializer = new Serializer();
            $data = $serializer->serialize($this->serialize, $this->viewVars);
            $data = ['response' => $data];
        }

        if (is_object($data) and method_exists($data, 'toXml')) {
            return $data->toXml();
        } elseif (is_array($data)) {
            return Xml::fromArray($data);
        }

        return $data;
    }
}
