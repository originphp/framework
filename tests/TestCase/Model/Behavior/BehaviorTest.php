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

namespace Origin\Test\Model\Behavior;

use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Model\Behavior\Behavior;
use Origin\TestSuite\OriginTestCase;
use ArrayObject;

class Tester extends Model
{
    public function initialize(array $config)
    {
        $this->loadBehavior('Tester', ['className' => 'Origin\Test\Model\Behavior\BehaviorTesterBehavior']);
    }
}

class BehaviorTesterBehavior extends Behavior
{
    /*
    public function beforeFind(ArrayObject $query) : bool
    {
        if (isset($query['return'])) {
            return $query['return'];
        }
        $query['beforeFind'] = true;
        return true;
    }*/

    public function foo($a, $b, $c, $d)
    {
        return 'bar';
    }

    /**
     * Before find callback must return a bool. Returning false will stop the find operation.
     *
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeFind(ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * After find callback
     *
     * @param mixed $results
     * @param ArrayObject $options
     * @return void
     */
    public function afterFind($results, ArrayObject $options) : void
    {
    }

    /**
     * Before Validation takes places, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeValidate(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * After Validation callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterValidate(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
     * Before save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeSave(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * Before create callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeCreate(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * Before update callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeUpdate(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
    * After create callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterCreate(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
    * After update callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterUpdate(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
     * After save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterSave(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
     * Before delete, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return bool
     */
    public function beforeDelete(Entity $entity, ArrayObject $options) : bool
    {
        return true;
    }

    /**
     * After delete callback
     *
     * @param \Origin\Model\Entity $entity
     * @param boolean $sucess wether or not it deleted the record
     * @return void
     */
    public function afterDelete(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
    * After commit callback
    *
    * @param \Origin\Model\Entity $entity
    * @param ArrayObject $options
    * @return bool
    */
    public function afterCommit(Entity $entity, ArrayObject $options) : void
    {
    }

    /**
     * This is callback is called when an exception is caught
     *
     * @param \Exception $exception
     * @return void
     */
    public function onError(\Exception $exception) : void
    {
    }

    /**
    * After rollback callback
    *
    * @param \Origin\Model\Entity $entity
    * @param ArrayObject $options
    * @return void
    */
    public function afterRollback(Entity $entity, ArrayObject $options) : void
    {
    }
}

class BehaviorTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article'];

    /*
    public function testBeforeFind()
    {
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $query = new ArrayObject(['foo' => 'bar']);
        $this->assertTrue($behavior->beforeFind($query));
    }

    public function testAfterFind()
    {
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $results = ['foo' => 'bar'];
        $this->assertNull($behavior->afterFind($results, new ArrayObject()));
    }

    public function testBeforeValidate()
    {
        $entity = new Entity(['name' => 'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertTrue($behavior->beforeValidate($entity, new ArrayObject()));
    }

    public function testAfterValidate()
    {
        $entity = new Entity(['name' => 'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertNull($behavior->afterValidate($entity, new ArrayObject()));
    }

    public function testBeforeSave()
    {
        $entity = new Entity(['name' => 'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertTrue($behavior->beforeSave($entity, new ArrayObject(['option1' => true])));
    }

    public function testAfterSave()
    {
        $entity = new Entity(['name' => 'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertNull($behavior->afterSave($entity, new ArrayObject(['option1' => true])));
    }

    public function testBeforeDelete()
    {
        $entity = new Entity(['name' => 'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertTrue($behavior->beforeDelete($entity, new ArrayObject()));
    }

    public function testAfterDelete()
    {
        $entity = new Entity(['name' => 'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertNull($behavior->afterDelete($entity, new ArrayObject()));
    }
    */

    public function testModel()
    {
        $entity = new Entity(['name' => 'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertInstanceOf(Model::class, $behavior->model());
    }

    public function testFindCallbacks()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
            ->setMethods(['beforeFind', 'afterFind'])
            ->setConstructorArgs([$Article])
            ->getMock();

        $behavior->expects($this->once())
            ->method('beforeFind')
            ->willReturn(true);

        $behavior->expects($this->once())
            ->method('afterFind');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $Article->find('first');
    }

    public function testValidateCallbacks()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
            ->setMethods(['beforeValidate', 'afterValidate'])
            ->setConstructorArgs([$Article])
            ->getMock();

        $behavior->expects($this->once())
            ->method('beforeValidate')->willReturn(true);

        $behavior->expects($this->once())
            ->method('afterValidate');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->new([
            'user_id' => 3, 'title' => 'testValidateCallbacks',
            'body' => 'testValidateCallbacks',
            'slug' => 'test-validate-callbacks',
            'created' => date('Y-m-d'),
            'modified' => date('Y-m-d'),
        ]);

        $this->assertTrue($Article->save($article));
    }

    public function testValidateCallbacksAbort()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);

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
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->new([
            'user_id' => 3, 'title' => 'testValidateCallbacksAbort',
            'body' => 'testValidateCallbacksAbort',
            'slug' => 'test-validate-callbacks-abort',
            'created' => date('Y-m-d'),
            'modified' => date('Y-m-d'),
        ]);

        $this->assertFalse($Article->save($article));
    }

    public function testSaveCallbackAbort()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);

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
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->new([
            'user_id' => 3, 'title' => 'testSaveCallbacksAbort',
            'body' => 'testSaveCallbacksAbort',
            'slug' => 'test-save-callbacks-abor',
            'created' => date('Y-m-d'),
            'modified' => date('Y-m-d'),
        ]);

        $this->assertFalse($Article->save($article));
    }

    public function testSaveCallbacks()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
            ->setMethods(['beforeSave', 'afterSave'])
            ->setConstructorArgs([$Article])
            ->getMock();

        $behavior->expects($this->once())
            ->method('beforeSave')
            ->willReturn(true);

        $behavior->expects($this->once())
            ->method('afterSave')
            ->willReturn(true);

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->new([
            'user_id' => 3, 'title' => 'SaveCallbacks',
            'body' => 'SaveCallbacks',
            'slug' => 'test-save-callbacks',
            'created' => date('Y-m-d'),
            'modified' => date('Y-m-d'),
        ]);

        $this->assertTrue($Article->save($article));
    }

    public function testDeleteCallbacksAbort()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);

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
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');
        $article = $Article->get(1000);
        $this->assertFalse($Article->delete($article));
    }

    public function testDeleteCallbacks()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
            ->setMethods(['beforeDelete', 'afterDelete'])
            ->setConstructorArgs([$Article])
            ->getMock();

        $behavior->expects($this->once())
            ->method('beforeDelete')
            ->willReturn(true);

        $behavior->expects($this->once())
            ->method('afterDelete');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');
        $article = $Article->get(1000);
        $this->assertTrue($Article->delete($article));
    }

    public function testMixin()
    {
        $article = new Model(['name' => 'Post']);
        $behavior = new BehaviorTesterBehavior($article);

        $article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $article->behaviorRegistry()->enable('BehaviorTester');

        $this->assertEquals('bar', $article->foo(1, 2, 3, 4));

        $this->expectException(\Origin\Exception\Exception::class);
        $article->bar();
    }
}
