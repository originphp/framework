<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Model\Behavior;

use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Model\Behavior\Behavior;
use Origin\Model\ConnectionManager;

class Tester extends Model
{
    public function initialize(array $config)
    {
        $this->loadBehavior('Tester', ['className' => 'Origin\Test\Model\Behavior\BehaviorTesterBehavior']);
    }
}

class BehaviorTesterBehavior extends Behavior
{
    public function initialize(array $config)
    {
    }

    public function beforeFind($query = array())
    {
        $query += ['return' => true];
        if (is_bool($query['return'])) {
            return $query['return'];
        }
        $query['beforeFind'] = true;

        return $query;
    }

    public function afterFind($results)
    {
        return $results;
    }

    /**
     * This must return true;.
     *
     * @return bool true
     */
    public function beforeValidate(Entity $entity)
    {
        return true;
    }

    /**
     * Called after validating data.
     */
    public function afterValidate(Entity $entity)
    {
    }

    public function beforeSave(Entity $entity, array $options = array())
    {
        return true;
    }

    public function afterSave(Entity $entity, $created, $options = array())
    {
    }

    public function beforeDelete(bool $cascade = true)
    {
        return true;
    }

    public function afterDelete()
    {
    }
}

class BehaviorTest extends \PHPUnit\Framework\TestCase
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

    public function testFindCallbacks()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeFind', 'afterFind'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeFind')
         ->willReturn($this->returnArgument(0));

        $behavior->expects($this->once())
        ->method('afterFind')
          ->willReturn($this->returnArgument(0));

        // As we are injecting mock, we need to enable it as well
        $Article->behaviors->set('BehaviorTester', $behavior);
        $Article->behaviors->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $Article->find('first');
    }

    public function testValidateCallbacks()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeValidate', 'afterValidate'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeValidate')->willReturn(true);

        $behavior->expects($this->once())
        ->method('afterValidate');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviors->set('BehaviorTester', $behavior);
        $Article->behaviors->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->newEntity(array(
        'user_id' => 3, 'title' => 'testValidateCallbacks',
        'body' => 'testValidateCallbacks',
        'slug' => 'test-validate-callbacks',
        'created' => date('Y-m-d'),
        'modified' => date('Y-m-d'),
      ));

        $this->assertTrue($Article->save($article));
    }

    public function testValidateCallbacksAbort()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeValidate', 'afterValidate'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeValidate')
       ->willReturn(false);

        $behavior->expects($this->never())
        ->method('afterValidate');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviors->set('BehaviorTester', $behavior);
        $Article->behaviors->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->newEntity(array(
        'user_id' => 3, 'title' => 'testValidateCallbacksAbort',
        'body' => 'testValidateCallbacksAbort',
        'slug' => 'test-validate-callbacks-abort',
        'created' => date('Y-m-d'),
        'modified' => date('Y-m-d'),
      ));

        $this->assertFalse($Article->save($article));
    }

    public function testSaveCallbackAbort()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
        ->setMethods(['beforeSave', 'afterSave'])
        ->setConstructorArgs([$Article])
        ->getMock();

        $behavior->expects($this->once())
     ->method('beforeSave')
       ->willReturn(false);

        $behavior->expects($this->never())
       ->method('afterSave');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviors->set('BehaviorTester', $behavior);
        $Article->behaviors->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->newEntity(array(
       'user_id' => 3, 'title' => 'testSaveCallbacksAbort',
       'body' => 'testSaveCallbacksAbort',
       'slug' => 'test-save-callbacks-abor',
       'created' => date('Y-m-d'),
       'modified' => date('Y-m-d'),
     ));

        $this->assertFalse($Article->save($article));
    }

    public function testSaveCallbacks()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeSave', 'afterSave'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeSave')
         ->willReturn($this->returnArgument(0));

        $behavior->expects($this->once())
        ->method('afterSave')
          ->willReturn($this->returnArgument(0), $this->returnArgument(1));

        // As we are injecting mock, we need to enable it as well
        $Article->behaviors->set('BehaviorTester', $behavior);
        $Article->behaviors->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->newEntity(array(
          'user_id' => 3, 'title' => 'SaveCallbacks',
          'body' => 'SaveCallbacks',
          'slug' => 'test-save-callbacks',
          'created' => date('Y-m-d'),
          'modified' => date('Y-m-d'),
        ));

        $this->assertTrue($Article->save($article));
    }

    public function testDeleteCallbacksAbort()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeDelete', 'afterDelete'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeDelete')
         ->willReturn(false);

        $behavior->expects($this->never())
        ->method('afterDelete');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviors->set('BehaviorTester', $behavior);
        $Article->behaviors->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $this->assertFalse($Article->delete(5));
    }

    public function testDeleteCallbacks()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeDelete', 'afterDelete'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeDelete')
         ->willReturn($this->returnArgument(0));

        $behavior->expects($this->once())
        ->method('afterDelete');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviors->set('BehaviorTester', $behavior);
        $Article->behaviors->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $this->assertTrue($Article->delete(5));
    }
}
