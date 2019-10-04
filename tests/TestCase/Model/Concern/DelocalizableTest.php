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

namespace Origin\Test\Model\Concern;

use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Utility\Date;
use Origin\Utility\Number;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Concern\Delocalizable;

class Deal extends Model
{
    use Delocalizable;
    public $connection = 'test';
}

class DelocalizeTest extends OriginTestCase
{
    public $fixtures = ['Origin.Deal'];

    /**
     * Deal model
     *
     * @var \Origin\Test\Model\Concern\Deal
     */
    protected $Deal;

    protected function setUp() : void
    {
        $this->Deal = new Deal();
        
        Date::locale(['date' => 'd/m/Y','time' => 'H:i','datetime' => 'd/m/Y H:i','timezone' => 'Europe/London']);
        Number::locale(['currency' => 'USD','thousands' => ',','decimals' => '.']);
    }
 

    public function testCallbackIsRegistered() : void
    {
        $result = $this->Deal->registeredCallbacks('beforeValidate');
        $this->assertArrayHasKey('delocalize', $result);
    }

    public function testBehavior()
    {
        $deal = new Entity([
            'amount' => '1,234,567.89',
            'close_date' => '11/06/2019',
            'created' => '11/06/2019 10:27',
            'confirmed' => '10:27',
        ]);
        $this->Deal->delocalize($deal);

        $this->assertEquals(1234567.89, $deal->amount);
        $this->assertEquals('2019-06-11', $deal->close_date);
        $this->assertEquals('2019-06-11 09:27:00', $deal->created);
       
        /**
         * This is correct. Without date, can't convert time due to DST
         */
        $this->assertEquals('10:27:00', $deal->confirmed);
    }

    /**
     * Process invalid values, original values should be restored since date
     * will return null
     *
     * @return void
     */
    public function testBehaviorError()
    {
        $deal = new Entity([
            'amount' => '1,234,567.89',
            'close_date' => '50-06/2019',
            'created' => '11/21/2019 10:27:00000',
            'confirmed' => '10:27pm',
        ]);
        $this->Deal->delocalize($deal);

        $this->assertEquals('50-06/2019', $deal->close_date);
        $this->assertEquals('11/21/2019 10:27:00000', $deal->created);
        $this->assertEquals('10:27pm', $deal->confirmed);
    }

    public function shutdown() : void
    {
        Date::locale([
            'timezone' => 'UTC',
            'date' => 'Y-m-d',
            'datetime' => 'Y-m-d H:i',
            'time' => 'H:i',
        ]);
    }
}
