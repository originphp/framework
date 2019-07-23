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

namespace Origin\Core\Test;

use Origin\Model\QueryBuilder;
use Origin\TestSuite\TestTrait;
use Origin\Model\Exception\QueryBuilderException;

class MockQueryBuilder extends QueryBuilder
{
    use TestTrait;
}

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testSelect()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','username','email' => 'user_email']);
       
        $expected = 'SELECT User.id, User.username, User.email AS `user_email` FROM `users` AS `User`';
        $this->assertEquals($expected, $builder->write());
    }

    public function testCount()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['COUNT(*) AS total']);
        $expected = 'SELECT COUNT(*) AS total FROM `users` AS `User`';
        $this->assertEquals($expected, $builder->write());
    }

    public function testConditionsEquals()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['id' => 1000,'name' => ['James','Rossi']]);
        
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id = :u0 AND User.name IN ( :u1, :u2 )';
        $this->assertEquals($expected, $builder->write());
        $this->assertEquals(['u0' => 1000,'u1' => 'James','u2' => 'Rossi'], $builder->getValues());

        $builder->select(['id','name','email'])
            ->where(['id' => null]);
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id IS NULL';
        $this->assertEquals($expected, $builder->write());
    }
    public function testConditionsNotEquals()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['id !=' => 1000,'name !=' => ['James','Rossi']]);
        
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id != :u0 AND User.name NOT IN ( :u1, :u2 )';
        $this->assertEquals($expected, $builder->write());
        $this->assertEquals(['u0' => 1000,'u1' => 'James','u2' => 'Rossi'], $builder->getValues());
        
        $builder->select(['id','name','email'])
            ->where(['id !=' => null]);
        
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id IS NOT NULL';
        $this->assertEquals($expected, $builder->write());
    }

    public function testCondtionsComparing()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['id' => 1000,'created = modified']);
        
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id = :u0 AND created = modified';
        $this->assertEquals($expected, $builder->write());
    }

    public function testCondtionsArithmetic()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['age >' => 18,'age <' => 33,'logins >=' => 10,'logins <=' => 20]);
        
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.age > :u0 AND User.age < :u1 AND User.logins >= :u2 AND User.logins <= :u3';
       
        $this->assertEquals($expected, $builder->write());
        $this->assertEquals(['u0' => 18,'u1' => 33,'u2' => 10,'u3' => 20], $builder->getValues());
    }

    public function testConditionsBetween()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['age BETWEEN' => [18,21],'logins NOT BETWEEN' => [10,20]]);
       
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE ( User.age BETWEEN :u0 AND :u1 ) AND ( User.logins NOT BETWEEN :u2 AND :u3 )';
        $this->assertEquals($expected, $builder->write());
        $this->assertEquals(['u0' => 18,'u1' => 21,'u2' => 10,'u3' => 20], $builder->getValues());

        $this->expectException(QueryBuilderException::class);
        $builder->select(['id','name','email'])
            ->where(['age BETWEEN' => 1]);
        $builder->write();
    }

    public function testConditionsLike()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['name LIKE' => '%A','name NOT LIKE' => '%B']);
       
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.name LIKE :u0 AND User.name NOT LIKE :u1';
        
        $this->assertEquals($expected, $builder->write());
        $this->assertEquals(['u0' => '%A','u1' => '%B'], $builder->getValues());

        $this->expectException(QueryBuilderException::class);
        $builder->select(['id','name','email'])
            ->where(['name LIKE' => ['this','that%']]);
        $builder->write();
    }

    public function testConditionsInvalidOperator()
    {
        //Post.due_date >= NOW()
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['name <o>' => 'invalid operator']);
        $this->expectException(QueryBuilderException::class);
        $builder->write();
    }

    public function testConditionsIn()
    {
        //SELECT STATEMENT
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['id IN' => 'SELECT STATEMENT']);
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id IN ( SELECT STATEMENT )';
        $this->assertEquals($expected, $builder->write());
    }

    public function testConditionsOR()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email','status'])
            ->where(['name' => 'James',['OR' => [
                'email LIKE' => '%company.com',
                'status' => 'verified',
            ]]]);
        $expected = 'SELECT User.id, User.name, User.email, User.status FROM `users` AS `User` WHERE User.name = :u0 AND (User.email LIKE :u1 OR User.status = :u2)';
        $this->assertEquals($expected, $builder->write());
        $this->assertEquals(['u0' => 'James','u1' => '%company.com','u2' => 'verified'], $builder->getValues());
    }

    public function testConditionsORSameField()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email','status'])
            ->where(['name' => 'James',['OR' => [
                ['email LIKE' => 'james%'],
                ['email LIKE' => '%james','status' => 'external'],
            ]]]);
        $expected = 'SELECT User.id, User.name, User.email, User.status FROM `users` AS `User` WHERE User.name = :u0 AND (User.email LIKE :u1 OR (User.email LIKE :u2 AND User.status = :u3))';
        $this->assertEquals($expected, $builder->write());
        $this->assertEquals(['u0' => 'James','u1' => 'james%','u2' => '%james','u3' => 'external'], $builder->getValues());
    }

    public function testConditionsNot()
    {
        //array("NOT" => array("Post.title" => array("First post", "Second post", "Third post")  ))
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email','status'])
            ->where(['NOT' => ['name' => ['Jon','Tony','Amanda'],'created >= NOW()']]);
        $expected = 'SELECT User.id, User.name, User.email, User.status FROM `users` AS `User` WHERE NOT (User.name IN ( :u0, :u1, :u2 ) NOT created >= NOW())';
        $this->assertEquals($expected, $builder->write());
    }
    public function testConditionsMultiple()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email','city','status'])
            ->where([
                'OR' => [
                    'name' => 'Jon',
                    'email LIKE' => 'jon%',
                ],
                'AND' => [
                    'status' => 'New',
                    'city' => 'London',
                ],
            ]);
        $expected = 'SELECT User.id, User.name, User.email, User.city, User.status FROM `users` AS `User` WHERE (User.name = :u0 OR User.email LIKE :u1) AND (User.status = :u2 AND User.city = :u3)';
        $this->assertEquals($expected, $builder->write());
    }

    public function testConditionsMultipleNesting()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email','city','status'])
            ->where([
                'OR' => [
                    'name' => 'Jon',
                    'email LIKE' => 'jon%',
                ],
                'AND' => [
                    'OR' => [
                        'status' => 'New',
                        'city' => 'London',
                    ],
                ],
            ]);
        $expected = 'SELECT User.id, User.name, User.email, User.city, User.status FROM `users` AS `User` WHERE (User.name = :u0 OR User.email LIKE :u1) AND ((User.status = :u2 OR User.city = :u3))';
        $this->assertEquals($expected, $builder->write());
    }

    public function testSelectHaving()
    {
        $builder = new QueryBuilder('orders', 'OrderDetails');
        $builder->select(['id','SUM(quantity) AS items','SUM(price*quantity) AS total'])
            ->group(['id'])
            ->having(['total >' => 1000,'items >' => 25]);
       
        $expected = 'SELECT OrderDetails.id, SUM(quantity) AS items, SUM(price*quantity) AS total FROM `orders` AS `OrderDetails` GROUP BY OrderDetails.id HAVING total > :o0 AND items > :o1';
        $this->assertEquals($expected, $builder->write());
    }

    public function testSelectJoin()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select()
            ->where(['id' => 100])
            ->join([
                'table' => 'roles',
                'conditions' => ['User.role_id = UserRole.id'],
            ]);

        $expected = 'SELECT User.* FROM `users` AS `User` LEFT JOIN `roles` ON (User.role_id = UserRole.id) WHERE User.id = :u0';
        $this->assertSame($expected, $builder->write());
    }

    public function testSelectJoinLeft()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select()
            ->where(['id' => 100])
            ->leftJoin([
                'table' => 'roles',
                'alias' => 'UserRole',
                'conditions' => ['User.role_id = UserRole.id'],
            ]);

        $expected = 'SELECT User.* FROM `users` AS `User` LEFT JOIN `roles` AS `UserRole` ON (User.role_id = UserRole.id) WHERE User.id = :u0';
        $this->assertSame($expected, $builder->write());
    }

    public function testSelectJoinRight()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select()
            ->where(['id' => 100])
            ->rightJoin([
                'table' => 'roles',
                'alias' => 'UserRole',
                'conditions' => ['User.role_id = UserRole.id'],
            ]);

        $expected = 'SELECT User.* FROM `users` AS `User` RIGHT JOIN `roles` AS `UserRole` ON (User.role_id = UserRole.id) WHERE User.id = :u0';
        $this->assertSame($expected, $builder->write());
    }

    public function testSelectJoinInner()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select()
            ->where(['id' => 100])
            ->innerJoin([
                'table' => 'roles',
                'alias' => 'UserRole',
                'conditions' => ['User.role_id = UserRole.id'],
            ]);

        $expected = 'SELECT User.* FROM `users` AS `User` INNER JOIN `roles` AS `UserRole` ON (User.role_id = UserRole.id) WHERE User.id = :u0';
        $this->assertSame($expected, $builder->write());
    }

    public function testSelectJoinFull()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select()
            ->where(['id' => 100])
            ->fullJoin([
                'table' => 'roles',
                'alias' => 'UserRole',
                'conditions' => ['User.role_id = UserRole.id'],
            ]);

        $expected = 'SELECT User.* FROM `users` AS `User` FULL JOIN `roles` AS `UserRole` ON (User.role_id = UserRole.id) WHERE User.id = :u0';
        $this->assertSame($expected, $builder->write());
    }

    public function testSelectGroup()
    {
        $builder = new QueryBuilder('customers', 'Customer');
        $builder->select(['COUNT(customer_id)','country'])
            ->group(['county']);
        $expected = 'SELECT COUNT(customer_id), Customer.country FROM `customers` AS `Customer` GROUP BY Customer.county';
        $this->assertSame($expected, $builder->write());

        $builder = new QueryBuilder('users', 'User');
        $builder->select(['COUNT(*) AS total','country'])
            ->where(['created < NOW()'])
            ->group(['country']);

        $expected = 'SELECT COUNT(*) AS total, User.country FROM `users` AS `User` WHERE created < NOW() GROUP BY User.country';
        $this->assertSame($expected, $builder->write());
    }

    public function testSelectDistinct()
    {
        //DISTINCT (Author.name) AS author_name','title'
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['DISTINCT (name) AS name','created'])
            ->where(['created <' => date('Y-m-d H:i:s')]);
        $expected = 'SELECT DISTINCT (name) AS name, User.created FROM `users` AS `User` WHERE User.created < :u0';
        $this->assertSame($expected, $builder->write());
    }

    public function testSelectOrder()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['User.id' => 1000])
            ->order(['name ASC']);

        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id = :u0 ORDER BY name ASC';
        $this->assertSame($expected, $builder->write());

        $builder->select(['id','name','email'])
            ->where(['User.id' => 1000])
            ->order(['name','created DESC']);

        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id = :u0 ORDER BY User.name,created DESC';
        $this->assertSame($expected, $builder->write());
    }
   
    public function testSelectLimit()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->where(['User.id' => 1000])
            ->limit(5);
      
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id = :u0 LIMIT 5';
        $this->assertSame($expected, $builder->write());

        $builder->select(['id','name','email'])
            ->where(['User.id' => 1000])
            ->limit(10, 5);

        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` WHERE User.id = :u0 LIMIT 10 OFFSET 5';
        $this->assertSame($expected, $builder->write());
    }

    public function testInsert()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->insert(['name' => 'Jon Snow','email' => 'jon@example.com','status' => '']);
        $expected = 'INSERT INTO users ( name, email, status ) VALUES ( :u0, :u1, :u2 )';
        $this->assertSame($expected, $builder->write());
        $this->assertEquals(['u0' => 'Jon Snow','u1' => 'jon@example.com','u2' => null], $builder->getValues());
    }

    public function testUpdate()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->update(['name' => 'Jon Snow','email' => 'jon@example.com','status' => ''])
            ->where(['id' => 100]);

        $expected = 'UPDATE users SET name = :u0, email = :u1, status = :u2 WHERE User.id = :u3';
        $this->assertSame($expected, $builder->write());

        $builder->update(['name' => 'Jon Snow','email' => 'jon@example.com','status' => ''])
            ->where(['id' => 100])
            ->order(['name ASC'])
            ->limit(10);

        $expected = 'UPDATE users SET name = :u0, email = :u1, status = :u2 WHERE User.id = :u3 ORDER BY name ASC LIMIT 10';
        $this->assertSame($expected, $builder->write());
    }

    public function testDelete()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->delete()->where(['id' => 100]);

        $expected = 'DELETE FROM users WHERE User.id = :u0';
        $this->assertSame($expected, $builder->write());

        $builder->delete()->where(['id' => 100])->order(['name ASC'])->limit(1);
        $expected = 'DELETE FROM users WHERE User.id = :u0 ORDER BY name ASC LIMIT 1';
        $this->assertSame($expected, $builder->write());
    }

    public function testPage()
    {
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])
            ->page(3)
            ->limit(10);
        $expected = 'SELECT User.id, User.name, User.email FROM `users` AS `User` LIMIT 10 OFFSET 20';
        $this->assertSame($expected, $builder->write());
    }

    public function testSelectStatementException()
    {
        $this->expectException(QueryBuilderException::class);
        $builder = new QueryBuilder('users', 'User');
        $builder->selectStatement([]);
    }
    public function testInsertStatementException()
    {
        $this->expectException(QueryBuilderException::class);
        $builder = new QueryBuilder('users', 'User');
        $builder->insertStatement([]);
    }
    public function testUpdateStatementException()
    {
        $this->expectException(QueryBuilderException::class);
        $builder = new QueryBuilder('users', 'User');
        $builder->updateStatement([]);
    }
    public function testDeleteStatementException()
    {
        $this->expectException(QueryBuilderException::class);
        $builder = new QueryBuilder('users', 'User');
        $builder->deleteStatement([]);
    }

    public function testWriteException()
    {
        $this->expectException(QueryBuilderException::class);
        $builder = new QueryBuilder('users', 'User');
        $builder->write();
    }

    public function testWriteFormat()
    {
        // ['SELECT', 'FROM', 'WHERE', 'GROUP BY', 'ORDER BY', 'HAVING', 'LIMIT'];
        $builder = new QueryBuilder('users', 'User');
        $builder->select(['id','name','email'])->where(['id' => 1000])->order(['name ASC'])->limit(1);

        $expected = "\nSELECT\n  User.id, User.name, User.email \nFROM\n  `users` AS `User` \nWHERE\n  User.id = :u0 \nORDER BY\n  name ASC \nLIMIT 1";

        $builder = new QueryBuilder('items', 'OrderDetails');
        $builder->select(['id','SUM(quantity) AS items','SUM(price*quantity) AS total'])
            ->group(['id'])
            ->having(['total >' => 1000,'items >' => 25]);
        $expected = "\nSELECT\n  OrderDetails.id, SUM(quantity) AS items, SUM(price*quantity) AS total \nFROM\n  `items` AS `OrderDetails` \nGROUP BY\n  OrderDetails.id \nHAVING\n  total > :i0 AND items > :i1";
        $this->assertEquals($expected, $builder->writeFormatted());
    }
}
