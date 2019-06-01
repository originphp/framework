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

namespace Origin\Command;

use Origin\Command\Command;
use NumberFormatter;
use IntlDateFormatter;
use ResourceBundle;
use DateTime;
use Locale;
use Origin\Utility\Yaml;

class LocalesGeneratorCommand extends Command
{
    protected $name = 'locales:generator';
    protected $description = 'Generates the Locales definition files';

    protected $dateMap = [
        'MMMM' => 'F',
        'MMM' => 'M',
        'MM' => 'm', // with leading,
        'M' => 'n', // with leading,
        'Y' => 'o', // week numbering
        'yy' => 'y',
        'y' => 'Y',
        'yyyy' => 'Y',
        'dd' => 'd',
        'd' => 'j',
        'GGGG ' => '',
        ' GGGG' => '', // remove ad,
        'G'=> '',
        'GG' =>'',
        'GGG' =>'',
    ];

    protected $timeMap = [
        'HH' => 'H',
        'H' => 'G',
        'hh' => 'h',
        'h' => 'g',
        'a' => 'a', // am pm
        'mm' => 'i'
    ];

    public function initialize()
    {
        $this->addOption('expected',['description'=>'Adds the expected information','type'=>'boolean']);
        $this->addOption('single-file',['description'=>'Put all definitions in a single file','type'=>'boolean']);
    }
    public function execute()
    {
        $types = [];
        $locales = ResourceBundle::getLocales('');

        $path = CONFIG . DS  .'locales';

        if(file_exists($path)){
            $this->io->warning('Locales definitions found in ' . $path);
            if($this->io->askChoice('Do you want to continue?',['y','n'],'n') === 'no'){
                $this->exit();
            }
        }

        if(!file_Exists($path )){
            mkdir($path);
        }

        $results = [];
        $max = count($locales)-1;
        $this->info('Generating locales definitions');
        $i=0;
        //@todo where to put this. Should be part of source code, but cant add to vendor
        foreach ($locales as $locale) {
            $result = $this->parseLocale($locale);
            $results[$locale] = $result;
            if($this->options('single-file') === false){
                $this->io->createFile($path . DS .$locale.'.yml',Yaml::fromArray($result),true);
            }
           
            if($i === 0 OR $i === $max OR $i % 2 === 0){
                $this->io->progressBar($i,$max);
            }
            
            $i++;
            
        }
    
        if($this->options('single-file')){
            $this->io->createFile( $path . DS . 'locales.yml',Yaml::fromArray($results),true);
        }

        $this->io->success(sprintf('Generated %d locale definitions',count($results)));
    }

    /**
     * Decodes unicode characters
     *
     * @param string $value
     * @return void
     */
    protected function decode(string $value){
        if(substr($value,0,2) == '\u'){
            $value = str_replace('\u','\u{',$value) . '}';
        }
        return $value;
    }
    /**
     * Undocumented function
     *
     * @param string $locale
     * @return void
     */
    protected function parseLocale(string $locale = null)
    {
        $config = ['name'=>null,'decimals'=>null,'thousands'=>null,'currency'=>null,'before'=>null,'after'=>null,'date'=>null,'time'=>null,'datetime'=>null];

        $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);
       
        $config['name'] = Locale::getDisplayName($locale);
        $config['decimals'] = $fmt->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        $config['thousands'] = $fmt->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        $config['currency'] = $fmt->getTextAttribute(NumberFormatter::CURRENCY_CODE);
         $symbol = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
         $config['before'] = $config['after'] = null;

         $symbol = $this->decode($symbol);
         if( $fmt->getPattern() === '#,##0.00 ¤'){
            $config['after'] = ' ' . $symbol;
         }
         else{
             $config['before'] = $symbol;
         }

         $expected = [];
         $expected['currency'] = $fmt->format(1234567.890);

         //http://userguide.icu-project.org/formatparse/datetime
        $fmt = new IntlDateFormatter($locale, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
    
        $config['date'] = $this->convertDate($fmt->getPattern());
        $expected['date'] = $fmt->format(new DateTime());
        
        $fmt = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
        $config['time'] =  $this->convertTime($fmt->getPattern());
        $expected['time'] = $fmt->format(new DateTime());
        
        $fmt = new IntlDateFormatter($locale, IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
        $config['datetime'] =  $this->convertDatetime($fmt->getPattern());

        $expected['datetime'] = $fmt->format(new DateTime());
        if($this->options('expected')){
            $config['expected'] = $config['expected'];
        }
        
        return $config;
    }

    public function convertDate(String $string){
        return strtr($string,$this->dateMap);
    }
    public function convertTime(String $string){
        return strtr($string,$this->timeMap);
    }
    public function convertDatetime(String $string){
        $string = $this->convertDate($string);
        return strtr($string,$this->timeMap);
    }
}
