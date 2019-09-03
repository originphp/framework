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

namespace Origin\Test\Concern;

use Origin\Model\Model;
use Origin\Concern\Concern;
use Origin\Concern\ConcernRegistry;

class PublishableConcern extends Concern
{
    public function foo()
    {
        return 'bar';
    }
}
class ConcernRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testInit()
    {
        $model = new Model(['name' => 'Article','datasource' => 'test']);
        $registry = new ConcernRegistry($model);
        $registry->set('Publishable', new PublishableConcern($model));
        $registry->enable('Publishable');

        $this->assertInstanceOf(Model::class, $registry->object());
        $this->assertInstanceOf(PublishableConcern::class, $registry->hasMethod('foo'));
        $this->assertNull($registry->hasMethod('bar'));
    }
}
