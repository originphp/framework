<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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

namespace Origin\Core;

/**
 * Dot Class
 * This is for handling array paths which are defined by dot notations.
 *
 *  array('Asset' => array(
 *            'Framework' => array(
 *                'js' => 'origin.js'
 *                )
 *             )
 *       );
 *
 * Would be expressed as Asset.Framework.js
 *
 * $Dot = new Dot($someArray)
 * $Dot->set('App.someSetting',123)
 * $app = $Dot->get('App') // array('App'=>array('someSetting'=>123))
 * or: $someSetting = $Dot->get('App.someSetting');
 */
class Dot
{
    /**
     * The items in the array to mainuplate.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Set the items to use or leave blank.
     *
     * @param array $items array of items to play with
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Sets an item in the path.
     *
     * @param string $key  The key to use, accepts also dot notation e.g. App.currency
     * @param mixed  $value [string|array|integer]
     * @return void
     */
    public function set(string $key, $value): void
    {
        $items = &$this->items;
        foreach (explode('.', $key) as $key) {
            if (! isset($items[$key]) || ! is_array($items[$key])) {
                $items[$key] = [];
            }
            $items = &$items[$key];
        }
        $items = $value;
    }

    /**
     * Gets an item in the path.
     *
     * @param string $key The key to get, accepts also dot notation e.g. App.currency
     * @param mixed $defaultValue this is what to return if not found e.g null, '' or []
     * @return mixed $value | $defaultValue
     */
    public function get(string $key, $defaultValue = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }
        if (strpos($key, '.') === false) {
            return $defaultValue;
        }

        $items = $this->items;
        foreach (explode('.', $key) as $path) {
            if (! is_array($items) || ! array_key_exists($path, $items)) {
                return $defaultValue;
            }
            $items = &$items[$path];
        }

        return $items;
    }

    /**
     * Returns the items array.
     *
     * @return array $items
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Deletes an item in the path.
     *
     * @param string $key The key to delete, accepts also dot notation e.g. App.currency
     * @return bool
     */
    public function delete(string $key): bool
    {
        if (array_key_exists($key, $this->items)) {
            unset($this->items[$key]);

            return true;
        }
        if (strpos($key, '.') === false) {
            return false;
        }

        $items = &$this->items;
        $paths = explode('.', $key);
        $lastPath = array_pop($paths);

        foreach ($paths as $path) {
            if (! is_array($items) || ! array_key_exists($path, $items)) {
                continue;
            }
            $items = &$items[$path];
        }
        if (isset($items[$lastPath])) {
            unset($items[$lastPath]);

            return true;
        }

        return false;
    }

    /**
     * Checks item in the path.
     *
     * @param string $key The key to check, accepts also dot notation e.g. App.currency
     * @return bool
     */
    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->items)) {
            return true;
        }
        if (strpos($key, '.') === false) {
            return false;
        }
        $items = &$this->items;
        $paths = explode('.', $key);
        $lastPath = array_pop($paths);

        foreach ($paths as $path) {
            if (! is_array($items) || ! array_key_exists($path, $items)) {
                continue;
            }
            $items = &$items[$path];
        }
        if (isset($items[$lastPath])) {
            return true;
        }

        return false;
    }
}
