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

namespace Origin\Utility;

use Iterator;
use Countable;
use Origin\Exception\InvalidArgumentException;
use Origin\Utility\Exception\NotFoundException;

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
        while (! feof($fh)) {
            if (fgets($fh) !== false) {
                $lines ++;
            }
        }
        fclose($fh);
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

class Csv
{

    /**
     * Used for Processing large files in a memory efficient way
     *
     * $records = Csv::process('/path/to/file.csv',$options);
     * foreach($records as $record){
     *      .. do something with data
     * }
     *
     * @param string $filename
     * @param array $options
     * @return CsvIterator
     */
    public static function process(string $filename, array $options = []) : CsvIterator
    {
        if (file_exists($filename)) {
            return new CsvIterator($filename, $options);
        }
        throw new NotFoundException(sprintf('%s could not be found.', $filename));
    }

    /**
     * Converts an CSV string to an array
     *
     * @param string $csv
     * @param array $options The option keys are :
     *  - header: default false. If the csv file contains a header row
     *  - keys: array of keys to use or set to true to use headers from csv file
     *  - separator: default:,
     *  - enclosure: default:"
     *  - escape: default:\
     * @return array
     */
    public static function toArray(string $csv, array $options = []) : array
    {
        $options += ['header' => false, 'keys' => null, 'separator' => ',','enclosure' => '"','escape' => '\\'];
        $stream = fopen('php://temp', 'r+');
        fputs($stream, $csv);
        rewind($stream);

        $result = [];
        $i = 0;
       
        while (($data = fgetcsv($stream, 0, $options['separator'], $options['enclosure'], $options['escape'])) !== false) {
            if ($i === 0 and $options['header']) {
                if ($options['keys'] === true) {
                    $options['keys'] = $data;
                }
            } else {
                if ($options['keys'] and is_array($options['keys'])) {
                    if (count($options['keys']) !== count($data)) {
                        throw new InvalidArgumentException('Number of keys does not match columns');
                    }
                    $data = array_combine($options['keys'], $data);
                }
                $result[] = $data;
            }
            $i++;
        }
        fclose($stream);

        return $result;
    }

    /**
     * Converts an array to CSV string

     * @param array $data
     * @param array $options
     * @param array $options The option keys are :
     *  - header: true to use keys from array as headers, or pass array of keys to use
     * @return string
     */
    public static function fromArray(array $data, array $options = []) : string
    {
        $options += ['header' => false];

        $stream = fopen('php://temp', 'r+');

        if ($options['header'] === true) {
            $options['header'] = array_keys(current($data));
        }
        if (is_array($options['header'])) {
            fputcsv($stream, $options['header']);
        }

        foreach ($data as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $result = stream_get_contents($stream);
        fclose($stream);

        return $result;
    }
}
