<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Model;

use Origin\Model\Model;
use Origin\Model\Association;

/**
 * @todo migrate from model tests here, since this object was created and tests
 * were not moved
 */
class AssociationTest extends \PHPUnit\Framework\TestCase
{
    public function testNamespaceClass()
    {
        $model = new Model(['name' => 'Foo']);
        $assocation = new Association($model);
        $result = $assocation->belongsTo('Member', ['className' => 'Custom\Model\User']);
        $this->assertEquals('user_id', $result['foreignKey']);

        $result = $assocation->hasAndBelongsToMany('Member', ['className' => 'Custom\Model\User']);
        $this->assertEquals('user_id', $result['associationForeignKey']);
        $this->assertEquals('foos_users', $result['joinTable']);
    }
    
    public function testPluginClass()
    {
        $model = new Model(['name' => 'Foo']);

        $assocation = new Association($model);

        $result = $assocation->belongsTo('Member', ['className' => 'Custom.User']);
        $this->assertEquals('user_id', $result['foreignKey']);

        $result = $assocation->hasAndBelongsToMany('Member', ['className' => 'Custom.User']);
        $this->assertEquals('user_id', $result['associationForeignKey']);
        $this->assertEquals('foos_users', $result['joinTable']);
    }

    /**
     * Test using Plugin.Model as the alias name.
     * @interal this does not affect hasMany
     */
    public function testPluginAlias()
    {
        $model = new Model(['name' => 'Foo']);
        $assocation = new Association($model);

        $belongsTo = $assocation->belongsTo('Plugin.User');
        $this->assertEquals(['foos.user_id = users.id'], $belongsTo['conditions']);

        $hasOne = $assocation->hasOne('Plugin.User');
        $this->assertEquals(['foos.id = users.foo_id'], $hasOne['conditions']);

        // hasMany not applicable
        $hasAndBelongsToMany = $assocation->hasAndBelongsToMany('Plugin.User');
        $this->assertEquals(['foos_users.user_id = users.id'], $hasAndBelongsToMany['conditions']);
    }
}
