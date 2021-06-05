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
namespace Origin\Ssh;

class RemoteFile
{
    /**
     * Filename
     *
     * @var string
     */
    public $name;
    
    /**
     * Directory where file is
     *
     * @var string
     */
    public $directory;

    /**
     * Full path to file (directory and filename)
     *
     * @var string
     */
    public $path;

    /**
     * Extension if it has one
     *
     * @var string|null
     */
    public $extension = null;

    /**
     * Last modified timestamp
     *
     * @var int
     */
    public $timestamp;

    /**
    * Size of file
    *
    * @var int
    */
    public $size;
}
