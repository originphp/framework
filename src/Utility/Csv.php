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

use Origin\Exception\InvalidArgumentException;

class Csv
{
    /**
     * Converts an CSV string to an array
     *
      * @param string $csv
      * @param array $options The option keys are :
      *  - headers: default false. If the csv file contains headers
      *  - keys: array of keys to use or set to true to use headers from csv file
      * @return array
      */
    public static function toArray(string $csv, array $options=[]) : array
    {
        $options += ['headers'=>false,'keys'=>null];

        $stream = fopen("php://temp", 'r+');
        fputs($stream, $csv);
        rewind($stream);

        $result = [];
        $i = 0;
        while (($data = fgetcsv($stream)) !== false) {
            if ($i === 0 and $options['headers']) {
                if ($options['keys'] === true) {
                    $options['keys'] = $data;
                }
            } else {
                if ($options['keys'] AND is_array($options['keys'])) {
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
     *  - headers: true to use keys from array as headers, or pass array of keys to use
     * @return string
     */
    public static function fromArray(array $data, array $options=[]) : string
    {
        $options += ['headers'=>false];

        $stream = fopen("php://temp", 'r+');
  
        if ($options['headers'] === true) {
            $options['headers'] = array_keys(current($data));
        }
        if (is_array($options['headers'])) {
            fputcsv($stream, $options['headers']);
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
