<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Console;

use Origin\TestSuite\TestTrait;
use Origin\Console\ConsoleHelpFormatter;

class MockConsoleHelperFormatter extends ConsoleHelpFormatter
{
    use TestTrait;
}

class ConsoleHelpFormatterTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerate()
    {
        $formatter = new ConsoleHelpFormatter();
        $formatter->setDescription([
            'Lorem ipsum dolor sit amet, adipisci nibh risus mollis sagittis, sed egestas erat, dui eu eros,',
            'facilisi nulla, wisi aenean id egestas. Ante orci vivamus fusce ac orci eget, id eget tincidunt',
            'nonummy diam.',
        ]);

        $formatter->setUsage(['command [options] [arguments]']);
        $formatter->setCommands([
            'app:do-something' => 'Lorem ipsum dolor sit amet, adipisci nibh risus mollis sagittis',
            'app:clear' => 'Ante orci vivamus fusce ac orci eget, id eget tincidunt',
        ]);

        $formatter->setArguments([
            'url' => 'url to access',
            'password' => ['The password to use.', '(default: something)'],
        ]);

        $formatter->setOptions([
            '-h,--help' => 'Displays this help',
            '-v,--verbose' => 'Displays verbose messaging',
        ]);

        $formatter->setEpilog([
            'Lorem ipsum dolor sit amet, adipisci nibh risus mollis sagittis, sed egestas erat, dui eu eros,',
            'facilisi nulla, wisi aenean id egestas. Ante orci vivamus fusce ac orci eget, id eget tincidunt',
            'nonummy diam.',
        ]);

        $result = $formatter->generate();

        $expected = <<<EOF
<white>Lorem ipsum dolor sit amet, adipisci nibh risus mollis sagittis, sed egestas erat, dui eu eros,
facilisi nulla, wisi aenean id egestas. Ante orci vivamus fusce ac orci eget, id eget tincidunt
nonummy diam.</white>

<yellow>Usage:</yellow>
<white>  command [options] [arguments]</white>

<yellow>Commands:</yellow>
  <green>app:do-something     </green><white>Lorem ipsum dolor sit amet, adipisci nibh risus mollis sagittis</white>
  <green>app:clear            </green><white>Ante orci vivamus fusce ac orci eget, id eget tincidunt</white>

<yellow>Arguments:</yellow>
  <green>url                  </green><white>url to access</white>
  <green>password             </green><white>The password to use.</white>
  <green>                     </green><white>(default: something)</white>

<yellow>Options:</yellow>
  <green>-h,--help            </green><white>Displays this help</white>
  <green>-v,--verbose         </green><white>Displays verbose messaging</white>

<white>Lorem ipsum dolor sit amet, adipisci nibh risus mollis sagittis, sed egestas erat, dui eu eros,
facilisi nulla, wisi aenean id egestas. Ante orci vivamus fusce ac orci eget, id eget tincidunt
nonummy diam.</white>

EOF;
        $this->assertEquals($expected, $result);
    }
}
