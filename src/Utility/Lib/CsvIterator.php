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

namespace Origin\Utility\Lib;

use Iterator;
use Countable;
use Origin\Exception\InvalidArgumentException;

class CsvIterator implements Iterator, Countable
{
    /**
     * Holds the file handle
     *
     * @var Resource
     */
    protected $fh = null;

    /**
     * Current position
     *
     * @var int
     */
    protected $position;

    /**
     * Holds the current row
     *
     * @var array
     */
    protected $row = null;

    /**
     * Options array
     *
     * @var array
     */
    protected $options = [];

    /**
     * Holds the headers
     *
     * @var array
     */
    protected $headers = [];
    /**
     * Constructor
     *
     * @param string $filename
     * @param array $options
     */
    public function __construct(string $filename, array $options = [])
    {
        $options += ['separator' => ',', 'enclosure' => '"', 'escape' => '\\', 'header' => null, 'keys' => null];
       
        $this->options = $options;
        $this->filename = $filename; // keep for count

        $this->fh = fopen($filename, 'rt'); // read text mode
        if ($options['header'] === true) {
            $this->headers = $this->current();
        }

        if (is_array($options['keys'])) {
            $this->headers = $options['keys'];
        }
    }

    /**
     * Counts the number of rows excluding headers
     */
    public function count()
    {
        $lines = 0;
        $fh = fopen($this->filename, 'rt');
        defer($context, 'fclose', $fh);
        while (! feof($fh)) {
            if (fgets($fh) !== false) {
                $lines ++;
            }
        }
        if ($this->options['header']) {
            -- $lines;
        }

        return $lines;
    }

    /**
     * Return the current element
     *
     * @return void
     */
    public function current()
    {
        $this->row = fgetcsv(
            $this->fh,
            0,
            $this->options['separator'],
            $this->options['enclosure'],
            $this->options['escape']
        );
     
        if ($this->row and $this->headers) {
            if (count($this->row) !== count($this->headers)) {
                throw new InvalidArgumentException(sprintf('Column header mistmatch on row %d.', $this->position));
            }
            $this->row = array_combine($this->headers, $this->row);
        }
        $this->position++;

        return $this->row;
    }

    /**
     * Return the key of the current element
     *
     * @return scalar
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Move forward to next element
     * This method is called after each foreach loop.
     * @return bool
     */
    public function next()
    {
        return ! feof($this->fh);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
        rewind($this->fh);
        /**
         * Skip first row if its header
         */
        if ($this->options['header'] === true) {
            $this->current();
        }
    }

    /**
     * checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        if (! $this->next()) {
            fclose($this->fh);

            return false;
        }

        return true;
    }
}
