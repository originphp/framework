<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Core\Test\ModelCrude;

use App\Model\AppModel;
use Origin\Model\Model;
use Origin\Model\ConnectionManager;
use Origin\Model\Entity;

class Article extends AppModel
{
    public $hasMany = array('Comment');
    public $belongsTo = array('User');
    public $hasAndBelongsToMany = array('Tag');
}

class Comment extends AppModel
{
    public $belongsTo = array('Article');
}

class Tag extends AppModel
{
    public $hasAndBelongsToMany = array('Article');
}

class Profile extends AppModel
{
    public $belongsTo = array('User');
}

class User extends AppModel
{
    public $hasOne = array('Profile');
}

class ModelCrudTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @todo migrate to Fixtures
     */
    public static function setUpBeforeClass()
    {
        $sql = file_get_contents(ORIGIN.DS.'tests'.DS.'TestCase/Model/schema.sql');
        $statements = explode(";\n", $sql);

        $connection = ConnectionManager::get('test');

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $connection->execute($statement);
            }
        }
    }

    public function testCreateEntity()
    {
        $User = new Model(array('name' => 'User', 'datasource' => 'test'));
        $user = new Entity(array(
      'name' => 'Dave',
      'email' => 'dave@example.com',
      'password' => 'secret',
    ));

        $result = $User->save($user);

        $this->assertTrue($result);
        $this->assertNotNull($User->id);
    }

    /**
     * @depends testCreateEntity
     */
    public function testReadEntityNoResult()
    {
        $User = new Model(array('name' => 'User', 'datasource' => 'test'));

        $params = array(
      'conditions' => array('id' => 'doesNotExist'),
    );
        $User->returnObject = true;
        $this->assertNull($User->find('first', $params));
        $this->assertEquals(array(), $User->find('all', $params));
        $this->assertEquals(0, $User->find('count', $params));
        $this->assertEquals(array(), $User->find('list', $params));
    }

    /**
     * @depends testCreateEntity
     */
    public function testReadEntity()
    {
        $User = new Model(array('name' => 'User', 'datasource' => 'test'));

        $User->returnObject = true;
        $params = array(
      'conditions' => array('email' => 'dave@example.com'),
      'fields' => array('id', 'name', 'email', 'password'),
    );

        $expected = new Entity(array(
      'id' => 3,
      'name' => 'Dave',
      'email' => 'dave@example.com',
      'password' => 'secret',
    ), ['name' => 'User']);

        $this->assertEquals($expected, $User->find('first', $params));
        $this->assertEquals(array($expected), $User->find('all', $params));
        $this->assertEquals(1, $User->find('count', $params));
        $this->assertEquals(array(3 => 'Dave'), $User->find('list', $params)); // Generic test to see works with objects
    }

    /**
     * @depends testReadEntity
     */
    public function testUpdateEntity()
    {
        $User = new Model(array('name' => 'User', 'datasource' => 'test'));

        $params = array(
      'conditions' => array('email' => 'dave@example.com'),
    );
        $newEmailAddress = 'dave.smith@example.com';

        $User->returnObject = true;
        $user = $User->find('first', $params);
        $user->email = $newEmailAddress;
        $this->assertTrue($User->save($user));

        $params = array(
      'conditions' => array('email' => $newEmailAddress),
    );
        $this->assertEquals(1, $User->find('count', $params));
    }

    /**
     * @depends testUpdateEntity
     */
    public function testDeleteEntity()
    {
        $User = new Model(array('name' => 'User', 'datasource' => 'test'));

        $params = array(
      'conditions' => array('email' => 'dave.smith@example.com'),
    );
        $User->returnObject = true;
        $user = $User->find('first', $params);
        $this->assertTrue($User->delete($user->id));
        $this->assertEquals(0, $User->find('count', $params));
    }

    public function testTransactionRollback()
    {
        $User = new Model(array('name' => 'User', 'datasource' => 'test'));
        $user = new Entity(array(
      'name' => 'Tommy',
      'email' => 'tommy@example.com',
      'password' => 'secret',
    ));

        $User->begin();
        $this->assertTrue($User->save($user));
        $User->rollback();

        $params = array('conditions' => array('email' => 'tommy@example.com'));

        $this->assertEquals(0, $User->find('count', $params));
    }

    public function testTransactionCommit()
    {
        $User = new Model(array('name' => 'User', 'datasource' => 'test'));
        $user = new Entity(array(
      'name' => 'Claire',
      'email' => 'claire@example.com',
      'password' => 'secret',
    ));

        $params = array('conditions' => array('email' => 'claire@example.com'));

        $User->begin();
        $this->assertTrue($User->save($user));
        $User->commit();
        $this->assertEquals(1, $User->find('count', $params));
    }
}
