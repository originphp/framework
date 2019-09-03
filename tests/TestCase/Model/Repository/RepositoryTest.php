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

namespace Origin\Test\Repository;

use Origin\Model\Model;
use Origin\Model\Repository\Repository;

class MockRepository extends Repository
{
    public $User = null;

    public function initialize(Model $User)
    {
        $this->User = $User;
    }
}

class RepositoryTest extends \PHPUnit\Framework\TestCase
{
    public function testRepo()
    {
        $model = new Model(['name' => 'Article','datasource' => 'test']);
        $repository = new MockRepository($model);
        $this->assertInstanceOf(Model::class, $repository->User);
    }
}
