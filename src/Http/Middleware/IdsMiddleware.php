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
declare(strict_types=1);
namespace Origin\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Middleware\Middleware;

/**
 * IDS - A lightweight intrusion detection system (IDS) at the application level.
 * Its purpose is to provide an engine to help identify possible SQL Injection and XSS Attacks on the application.
 *
 * To add rules create `config/rules.php` which returns an array e.g.
 *
 *  return [
 *        [
 *            'name' => 'XSS Attack',
 *            'signature' => '/((\%3C)|(\%3E)|(\%3A)|(%00)|(&\#[a-z0-9]+)|(&lt;)|(&gt;))/ix',
 *            'description' => 'Detects potential XSS attacks using hex equivelent of <:>, null byte and checks
 *                              HTML hexidecimal character references with/without padding.', // e.g. &#0000106,&#106,&x6A
 *            'level' => 1
 *       ]
 *    ]
 *
 * @see https://ascii.cl/
 */
class IdsMiddleware extends Middleware
{
    /**
     * Default configuration
     *
     * @var array
     */
    protected $defaultConfig = [
        'log' => LOGS . '/ids.log',
        'rules' => null, // file or files to load rules from (default will be overwritten)
        'level' => 1,
    ];

    /**
     * Events will be logged here
     *
     * @var array
     */
    protected $events = [];

    /**
     * Default Rules which look anemic but they are anything but.
     *
     * @var array
     */
    private $rules = [
        /**
         * MySQL comment is # and Postgresql comment is --
         */
        [
            'name' => 'SQL Injection (paranoid)',
            'signature' => '/(\%27)|(\')|(?<!=)=(?!=)|(\%23)|(\#)|(\-\-)|(\%2D)/ix',
            'description' => 'Check for SQL specific meta-characters such as quote, equals comments #/-- and their hex equivalent',
            'level' => 3
        ],
        [
            'name' => 'SQL Injection Attack',
            'signature' => '/(\%20|\s)(((\%6F)|o|(\%4F))((\%72)|r|(\%52)))(\%20|\s)*((\%3D)|=|(\%3E)|>)*/ix',
            'description' => 'Check for OR or its hex equivalent and a condition',
            'level' => 1
        ],
        [
            'name' => 'SQL Injection Attack',
            'signature' => '/(union(\%20|\s)select)/i',
            'description' => 'Checks for union select statement',
            'level' => 1
        ],
        [
            'name' => 'SQL Injection Attack',
            'signature' => '/(?=.*(select|insert|update|delete|drop|union|truncate))(?=.*((\%3D)|(=)(\%27)|(\')|(\%23)|(\%3B)|(;)|(\#)|(\-\-)))/ix',
            'description' => 'Checks for SQL reserved words with SQL meta-characters',
            'level' => 1
        ],
        [
            'name' => 'XSS Attack',
            'signature' => '/((\%3C)|<|(\x3c)|(\\\u003c)).*((\%[a-z0-9]+)|(0x[0-9]+)|(&\#[a-z0-9]+)|script|iframe|(on[a-z]+\s*((\%3D)|=)))+/ix',
            'description' => 'Detect XSS attack if there is a opening < or hex equivilent and then the use of hex/dec encoding or script/iframe/or onevent is found',
            'level' => 1
        ]
    ];

 

    /**
     * This HANDLES the request. Use this to make changes to the request.
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    public function handle(Request $request) : void
    {
        if ($this->config['rules']) {
            $this->loadRules();
        }
        /**
         * Check the REQUEST_URI before they are decoded into _GET e.g
         * /bookmarks/view/1000?username=1%27%20or%20%271%27%20=%20%271&password=1%27%20or%20%271%27%20=%20%
         * $_GET will not have the params, $request->query() has the decoded versions.
         */
        //
   
        $this->run(['GET'=>$request->env('REQUEST_URI'),'POST'=>$_POST,'COOKIE'=>$_COOKIE]);

        $this->report($request);
 
        $this->cleanUp();
    }


    /**
     * Clears default rules and loads the rules from the value or values set
     * in config['rules]
     *
     * @return void
     */
    private function loadRules()
    {
        $this->rules = [];
        foreach ((array) $this->config['rules'] as $file) {
            $result = include($file);
            if (is_array($result)) {
                $this->rules = array_merge($this->rules, $result);
            }
        }
    }
  
    /**
     * Runs the rules on an array of data
     *
     * @param array $items key/value pair ['get'=>$_GET]
     * @return void
     */
    public function run(array $items) : void
    {
        foreach ($items as $name => $data) {
            $this->detect($name, $data);
        }
    }

    /**
     * A function that can be used recursively
     *
     * @param string $name
     * @param array|string $data
     * @return void
     */
    private function detect(string $name, $data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->detect($name . '.' . $key, $value);
            }
            return;
        }
        $matches = $this->match($name, (string) $data);
        if ($matches) {
            $this->events[] = [
                'name' => $name,
                'data' => $data,
                'matches' => $matches
            ];
        }
    }
   
    /**
     * Run the rules and find matches
     *
     * @param string $name
     * @param string $value
     * @return array
     */
    private function match(string $name, string $value = null) : array
    {
        /**
         * For better performance don't run rules on alphanumeric strings, and certain
         * chars. Dont use &%#
         */
      
        if (empty($value) or preg_match('/\w\s@\.!?-/i', $value)) {
            return [];
        }
        $matches = [];
        foreach ($this->rules as $rule) {
            if ($rule['level'] > $this->config['level']) {
                continue;
            }

            if (preg_match($rule['signature'], $value)) {
                $matches[] = $rule['name'];
            }
        }
        return array_unique($matches);
    }

    /**
     * Create the report
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    private function report(Request $request)
    {
        $out = '';
        foreach ($this->events as $event) {
            $out .= sprintf(
                '[%s] %s %s %s "%s" "%s"',
                date('Y-m-d H:i:s'),
                $request->ip(),
                $request->env('REQUEST_URI'),
                $event['name'],
                $event['data'],
                implode(',', $event['matches'])
            ) . "\n";
        }
        file_put_contents($this->config['log'] ."\n", $out, FILE_APPEND);
    }

    /**
     * Cleans up, frees the memory
     *
     * @return void
     */
    protected function cleanUp() : void
    {
        $this->rules = $this->events = null;
    }
}
