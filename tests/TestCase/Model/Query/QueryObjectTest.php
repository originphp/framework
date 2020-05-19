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

namespace Origin\Test\Query;

use Origin\Model\Query\QueryObject;

class SuperQuery extends QueryObject
{
    private $initialized = false;

    protected function initialize(): void
    {
        $this->initialized = true;
    }

    public function execute(bool $result): bool
    {
        return $this->initialized and $result;
    }
}

class QueryObjectTest extends \PHPUnit\Framework\TestCase
{
    public function testMain()
    {
        $query = new SuperQuery();
        $this->assertTrue($query->execute(true));
    }
}
