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
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Console\Command;

use Locale;
use DateTime;
use ResourceBundle;
use NumberFormatter;
use IntlDateFormatter;

class LocaleGenerateCommand extends Command
{
    protected $name = 'locale:generate';
    protected $description = 'Generates locale definition file or files';

    protected $path = CONFIG . DS  . 'locales';

    /**
     * I18n -> PHP
     *
     * @var array
     */
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
        'G' => '',
        'GG' => '',
        'GGG' => '',
    ];
    /**
     * I18n -> PHP
     *
     * @var array
     */
    protected $timeMap = [
        'HH' => 'H',
        'H' => 'G',
        'hh' => 'h',
        'h' => 'g',
        'a' => 'a', // am pm
        'mm' => 'i',
    ];

    public function initialize() : void
    {
        $this->addOption('expected', ['description' => 'Adds the expected information', 'type' => 'boolean']);
        $this->addOption('single-file', ['description' => 'Put all definitions in a single file', 'type' => 'boolean']);
        $this->addOption('force', ['description' => 'Force overwrite', 'type' => 'boolean']);
        $this->addArgument('locales', ['description' => 'The names of the locales you want to genreate seperated by space', 'type' => 'array']);
    }

    public function execute() : void
    {
        $types = [];
        $locales = ResourceBundle::getLocales('');
        if ($this->arguments('locales')) {
            $locales = $this->arguments('locales');
        }

        if (! file_Exists($this->path)) {
            mkdir($this->path);
        }

        $results = [];

        $max = count($locales);
        $this->info('Generating locales definitions');

        $this->io->progressBar(0, $max);

        $i = 1;

        //@todo where to put this. Should be part of source code, but cant add to vendor
        foreach ($locales as $locale) {
            $result = $this->parseLocale($locale);
            $results[$locale] = $result;
            if ($this->options('single-file') === false) {
                $this->io->createFile($this->path . DS . $locale . '.php', $this->toString($result), $this->options('force'));
            }

            $this->io->progressBar($i, $max);

            $i++;
        }

        if ($this->options('single-file')) {
            $this->io->createFile($this->path . DS . 'locales.php', $this->toString($result), $this->options('force'));
        }
        $this->io->success(sprintf('Generated %d locale definitions', count($results)));
    }

    private function toString(array $result)
    {
        return "<?php\nreturn ".$this->varExport($result, true).";";
    }

    /**
     * Parses the locale from I18n
     *
     * @param string $locale
     * @return array
     */
    private function parseLocale(string $locale = null): array
    {
        $config = ['name' => null, 'decimals' => null, 'thousands' => null, 'currency' => null, 'before' => null, 'after' => null, 'date' => null, 'time' => null, 'datetime' => null];

        $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        $config['name'] = Locale::getDisplayName($locale);
        $config['decimals'] = $fmt->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        $config['thousands'] = $fmt->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        $config['currency'] = $fmt->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        $symbol = $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
        $config['before'] = $config['after'] = null;

        if ($fmt->getPattern() === '#,##0.00 ¤') {
            $config['after'] = ' ' . $symbol;
        } else {
            $config['before'] = $symbol;
        }

        $expected = [];
        $expected['currency'] = $fmt->format(1234567.890);

        //http://userguide.icu-project.org/formatparse/datetime
        $fmt = new IntlDateFormatter($locale, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);

        $config['date'] = $this->convertDate($fmt->getPattern());
        $expected['date'] = $fmt->format(new DateTime());

        $fmt = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
        $config['time'] = $this->convertTime($fmt->getPattern());
        $expected['time'] = $fmt->format(new DateTime());

        $fmt = new IntlDateFormatter($locale, IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
        $config['datetime'] = $this->convertDatetime($fmt->getPattern());

        $expected['datetime'] = $fmt->format(new DateTime());
        if ($this->options('expected')) {
            $config['expected'] = $expected;
        }

        return $config;
    }
    /**
     * Convert the date using the date map
     *
     * @param string $string
     * @return string
     */
    private function convertDate(string $string): string
    {
        return strtr($string, $this->dateMap);
    }
    /**
     * Convert the time using the time map
     *
     * @param string $string
     * @return string
     */
    private function convertTime(string $string): string
    {
        return strtr($string, $this->timeMap);
    }

    /**
     * Convert the time using the time map
     *
     * @param string $string
     * @return string
     */
    private function convertDatetime(string $string): string
    {
        $string = $this->convertDate($string);

        return strtr($string, $this->timeMap);
    }

    /**
     * Modern version of varExport
     *
     * @param array $data
     * @return string
     */
    private function varExport(array $data) : string
    {
        $data = var_export($data, true);
        $data = str_replace(
            ['array (', "),\n", " => \n"],
            ['[', "],\n", ' => '],
            $data
        );
        $data = preg_replace('/=>\s\s+\[/i', '=> [', $data);
        $data = preg_replace("/=> \[\s\s+\]/m", '=> []', $data);

        return substr($data, 0, -1).']';
    }
}
