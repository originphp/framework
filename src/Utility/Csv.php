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

namespace Origin\Utility;

use Origin\Exception\InvalidArgumentException;
use Origin\Utility\Exception\NotFoundException;
use Origin\Utility\Lib\CsvIterator;

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
     * @return \Origin\Utility\Lib\CsvIterator
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
        defer($context, 'fclose', $stream);

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
        defer($context, 'fclose', $stream);

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
        return stream_get_contents($stream);
    }
}
