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
use Origin\Model\Exception\QueryBuilderException;
use Origin\TestSuite\TestTrait;

class MockQueryBuilder extends QueryBuilder
{
    use TestTrait;
}

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testTableReference()
    {
        $Builder = new QueryBuilder('user');
        $expected = '`user`';
        $this->assertEquals($expected, $Builder->tableReference());
        $Builder = new QueryBuilder('user', 'User');
        $expected = '`user` AS `User`';
        $this->assertEquals($expected, $Builder->tableReference());
    }

    public function testFieldsToString()
    {
        $Builder = new QueryBuilder('user', 'User');
        $data = ['id', 'User.user_name', 'email', 'COUNT(*)', 'user.photo AS avatar'];
        $expected = '`User`.`id`, `User`.`user_name`, `User`.`email`, COUNT(*), user.photo AS avatar'; //@todo think about adding quotes here
        $this->assertEquals($expected, $Builder->fieldsToString($data));
    }

    public function testJoinsToString()
    {
        $Builder = new QueryBuilder('user', 'User');
        $data = [
      'table' => 'user_roles',
      'alias' => 'UserRole',
      'conditions' => array('UserRole.id => User.role_id'),
    ];
        $expected = 'LEFT JOIN `user_roles` AS `UserRole` ON (`UserRole`.`id` => `User`.`role_id`)';
        $this->assertEquals($expected, $Builder->joinToString($data));

        $Builder = new QueryBuilder('tag', 'Tag');
        $data = [
          'table' => 'articles_tags',
          'alias' => 'ArticlesTag',
          'conditions' => array(
            'ArticlesTag.tag_id = Tag.id',
            'ArticlesTag.article_id' => 1,
          ), ];
        $expected = 'LEFT JOIN `articles_tags` AS `ArticlesTag` ON (`ArticlesTag`.`tag_id` = `Tag`.`id` AND `ArticlesTag`.`article_id` = :t0)';
        $this->assertEquals($expected, $Builder->joinToString($data));
    }

    public function testGroupToString()
    {
        $Builder = new QueryBuilder('user', 'User');
        $data = ['User.role_id', 'User.access'];
        $expected = '`User`.`role_id`, `User`.`access`';
        $this->assertEquals($expected, $Builder->groupToString($data));
    }

    public function testHavingToString()
    {
        $Builder = new QueryBuilder('user', 'User');
        $data = ['COUNT(CustomerID) >' => 5];
        $expected = 'COUNT(CustomerID) > :u0';
        $this->assertEquals($expected, $Builder->havingToString($data));
    }

    public function testOrderToString()
    {
        $Builder = new QueryBuilder('user', 'User');
        $data = ['User.country', 'User.user_name ASC'];
        $expected = '`User`.`country`,User.user_name ASC';
        $this->assertEquals($expected, $Builder->orderToString($data));
        $data = ['User.country' => 'DESC'];
        $expected = '`User`.`country` DESC';
        $this->assertEquals($expected, $Builder->orderToString($data));
    }

    public function testLimitToString()
    {
        $Builder = new QueryBuilder('user', 'User');
        $data = [
      'limit' => 10,
    ];
        $this->assertEquals(10, $Builder->limitToString($data));
        $data = [
      'limit' => 10,
      'page' => 1,
    ];
        $this->assertEquals('10 OFFSET 0', $Builder->limitToString($data));
        $data = [
      'limit' => 10,
      'page' => 2,
    ];
        $this->assertEquals('10 OFFSET 10', $Builder->limitToString($data));
        $data = [
      'limit' => 25,
      'offset' => 50,
    ];
        $this->assertEquals('25 OFFSET 50', $Builder->limitToString($data));
    }

    public function testExpression()
    {
        $Builder = new QueryBuilder('user', 'User');
        //$result = $Builder->expression('COUNT(CustomerID)','>',5);

        $this->assertSame('id = :u0', $Builder->expression('id', '=', 1));
        $this->assertSame('id IN ( :u1, :u2, :u3 )', $Builder->expression('id', '=', array(1, 2, 3)));
        $this->assertSame('id IS NULL', $Builder->expression('id', '=', null));

        $this->assertSame('id != :u4', $Builder->expression('id', '!=', 1));
        $this->assertSame('id NOT IN ( :u5, :u6, :u7 )', $Builder->expression('id', '!=', array(1, 2, 3)));
        $this->assertSame('id IS NOT NULL', $Builder->expression('id', '!=', null));

        $this->assertSame('name LIKE :u8', $Builder->expression('name', 'LIKE', '%dave%'));
        $this->assertSame('name NOT LIKE :u9', $Builder->expression('name', 'NOT LIKE', '%dave%'));

        $this->assertSame('( group_id BETWEEN :u10 AND :u11 )', $Builder->expression('group_id', 'BETWEEN', array(1, 10)));
        $this->assertSame('( group_id NOT BETWEEN :u12 AND :u13 )', $Builder->expression('group_id', 'NOT BETWEEN', array(1, 10)));

        $this->assertSame(
      'category_id IN ( :u14, :u15, :u16 )',
      $Builder->expression('category_id', 'IN', array(100, 200, 300))
    );
        $this->assertSame(
      'category_id NOT IN ( :u17, :u18, :u19 )',
      $Builder->expression('category_id', 'NOT IN', array(101, 201, 301))
    );

        $this->assertSame('User.level > :u20', $Builder->expression('User.level', '>', 1));
        $this->assertSame('User.level >= :u21', $Builder->expression('User.level', '>=', 2));
        $this->assertSame('User.level < :u22', $Builder->expression('User.level', '<', 3));
        $this->assertSame('User.level <= :u23', $Builder->expression('User.level', '<=', 4));
    }

    public function testSelectColumns()
    {
        $Builder = new QueryBuilder('user');
        $Builder->select();

        $this->assertSame('SELECT `user`.`*` FROM `user`', $Builder->write());

        $Builder->select(['id', 'name', 'email']);
        $this->assertSame('SELECT `user`.`id`, `user`.`name`, `user`.`email` FROM `user`', $Builder->write());
    }

    public function testSelectColumnAliases()
    {
        // Test alias
        $Builder = new QueryBuilder('user', 'User');

        $Builder->select(['id', 'name', 'email']);
        $this->assertSame('SELECT `User`.`id`, `User`.`name`, `User`.`email` FROM `user` AS `User`', $Builder->write());

        $Builder->select(['name' => 'userName', 'email_address' => 'email']);
        $this->assertSame('SELECT `User`.`name` AS `userName`, `User`.`email_address` AS `email` FROM `user` AS `User`', $Builder->write());
    }

    public function testSelectWhere()
    {
        $Builder = new QueryBuilder('user', 'User');
        $Builder->select()
      ->where(['id' => 100]);

        $this->assertSame('SELECT `User`.`*` FROM `user` AS `User` WHERE `User`.`id` = :u0', $Builder->write());

        $this->assertEquals(['u0' => '100'], $Builder->getValues());

        $Builder->select()
      ->where(['id' => 100])
      ->group(['role']);

        $expected = 'SELECT `User`.`*` FROM `user` AS `User` WHERE `User`.`id` = :u0 GROUP BY `User`.`role`';
        $this->assertSame($expected, $Builder->write());
    }

    public function testSelectJoins()
    {
        $Builder = new QueryBuilder('user', 'User');
        $Builder->select()
      ->where(['id' => 100])
      ->leftJoin([
        'table' => 'roles',
        'alias' => 'UserRole',
        'conditions' => ['User.role_id = UserRole.id'],
      ]);

        $expected = 'SELECT `User`.`*` FROM `user` AS `User` LEFT JOIN `roles` AS `UserRole` ON (`User`.`role_id` = `UserRole`.`id`) WHERE `User`.`id` = :u0';
        $this->assertSame($expected, $Builder->write());
    }

    public function testConditionsEquals()
    {
        $Builder = new QueryBuilder('articles', 'Article');
        $this->assertEquals('`Article`.`id` = :a0', $Builder->conditions('Article', ['id' => 1]));
        $this->assertEquals('`Article`.`id` IS NULL', $Builder->conditions('Article', ['id' => null]));
        $this->assertEquals('`Article`.`id` IN ( :a1, :a2, :a3 )', $Builder->conditions('Article', ['id' => array(1, 2, 3)]));
    }

    public function testConditionsNotEquals()
    {
        $Builder = new QueryBuilder('articles', 'Article');
        $this->assertEquals('`Article`.`id` != :a0', $Builder->conditions('Article', ['id !=' => 1]));
        $this->assertEquals('`Article`.`id` IS NOT NULL', $Builder->conditions('Article', ['id !=' => null]));
        $this->assertEquals('`Article`.`id` NOT IN ( :a1, :a2, :a3 )', $Builder->conditions('Article', ['id !=' => array(1, 2, 3)]));
    }

    public function testConditionsOthers()
    {
        $Builder = new QueryBuilder('articles', 'Article');
        $this->assertEquals('`Article`.`id` > :a0', $Builder->conditions('Article', ['id >' => 1]));
        $this->assertEquals('`Article`.`id` < :a1', $Builder->conditions('Article', ['id <' => 1]));
        $this->assertEquals('`Article`.`id` <= :a2', $Builder->conditions('Article', ['id <=' => 1]));
        $this->assertEquals('`Article`.`id` >= :a3', $Builder->conditions('Article', ['id >=' => 1]));

        //$this->expectException(InvalidArgumentException::class);
    }

    public function testConditionsBetween()
    {
        $Builder = new QueryBuilder('articles', 'Article');
        $this->assertEquals('( `Article`.`id` BETWEEN :a0 AND :a1 )', $Builder->conditions('Article', ['id BETWEEN' => array(1, 2)]));

        $this->assertEquals('( `Article`.`id` NOT BETWEEN :a2 AND :a3 )', $Builder->conditions('Article', ['id NOT BETWEEN' => array(1, 2)]));

        $this->expectException(QueryBuilderException::class);
        $Builder->conditions('Article', ['Article.id BETWEEN' => null]);
    }

    public function testConditionsLike()
    {
        $Builder = new QueryBuilder('articles', 'Article');
        $this->assertEquals('`Article`.`id` LIKE :a0', $Builder->conditions('Article', ['id LIKE' => '%100_']));

        $this->assertEquals('`Article`.`id` NOT LIKE :a1', $Builder->conditions('Article', ['id NOT LIKE' => '200%']));

        $this->expectException(QueryBuilderException::class);
        $Builder->conditions('Article', ['Article.id LIKE' => null]);
    }

    public function testConditionsIn()
    {
        $Builder = new QueryBuilder('articles', 'Article');

        $this->assertEquals('`Article`.`id` IN ( SELECT STATEMENT )', $Builder->conditions('Article', ['id IN' => 'SELECT STATEMENT']));
        $this->assertEquals('`Article`.`id` NOT IN ( SELECT STATEMENT )', $Builder->conditions('Article', ['id NOT IN' => 'SELECT STATEMENT']));
        $this->assertEquals('`Article`.`id` IN ( :a0, :a1, :a2 )', $Builder->conditions('Article', ['id IN' => array(1, 2, 3)]));
        $this->assertEquals('`Article`.`id` NOT IN ( :a3, :a4, :a5 )', $Builder->conditions('Article', ['id NOT IN' => array(1, 2, 3)]));

        $this->expectException(QueryBuilderException::class);
        $Builder->conditions('Article', ['id IN' => null]);
    }

    public function testOr()
    {
        $Builder = new QueryBuilder('post', 'Posts');
        $conditions = [
            'model' => 'Contact',
            'OR' => [
                ['task'=> 1,'closed'=>0],
                ['task'=> 0,'start_date <='=> '2019-01-29 09:30:00']
            ]];
        $expected = '`Post`.`model` = :p0 AND ((`Post`.`task` = :p1 AND `Post`.`closed` = :p2) OR (`Post`.`task` = :p3 AND `Post`.`start_date` <= :p4))';
        
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));
    }

    public function testConditionsFull()
    {
        $Builder = new QueryBuilder('post', 'Posts');
        $conditions = array('Post.title' => 'This is a post');
        $expected = '`Post`.`title` = :p0';
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));

        $conditions = array('Post.title !=' => 'This is a post');
        $expected = '`Post`.`title` != :p1';
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));

        $conditions = array('Post.title' => 'This is a post', 'Post.author_id' => 1);
        $expected = '`Post`.`title` = :p2 AND `Post`.`author_id` = :p3';
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));

        $conditions = array(
        'Post.title' => array('First post', 'Second post', 'Third post'),
    );
        $expected = '`Post`.`title` IN ( :p4, :p5, :p6 )';
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));

        $conditions = array(
        'NOT' => array(
            'Post.title' => array('First post', 'Second post', 'Third post'),
        ),
    );
        $expected = 'NOT (`Post`.`title` IN ( :p7, :p8, :p9 ))';
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));

        $conditions = array('OR' => array(
          'Post.title' => array('First post', 'Second post', 'Third post'),
          'Post.created >' => date('Y-m-d', strtotime('-2 weeks')),
      ));
        $expected = '(`Post`.`title` IN ( :p10, :p11, :p12 ) OR `Post`.`created` > :p13)';
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));

        $conditions = array(
          'Author.name' => 'Bob',
          'OR' => array(
              'Post.title LIKE' => '%magic%',
              'Post.created >' => date('Y-m-d', strtotime('-2 weeks')),
          ),
      );
        $expected = '`Author`.`name` = :p14 AND (`Post`.`title` LIKE :p15 OR `Post`.`created` > :p16)';
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));

        // Test adding a condition array without key
        $conditions[] = array('Post.appened_field' => 'zigzag');
        $expected = '`Author`.`name` = :p17 AND (`Post`.`title` LIKE :p18 OR `Post`.`created` > :p19) AND `Post`.`appened_field` = :p20';
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));

        // Looks the same but is not, SAME FIELD, extra array
        $conditions = array('OR' => array(
        array('Post.title LIKE' => '%one%'),
        array('Post.title LIKE' => '%two%'),
      ));
        $expected = '(`Post`.`title` LIKE :p21 OR `Post`.`title` LIKE :p22)';
        $this->assertEquals($expected, $Builder->conditions('Post', $conditions));

        $Builder = new QueryBuilder('companies', 'Company');

        $conditions = array(
           'OR' => array(
               array('Company.name' => 'Future Holdings'),
               array('Company.city' => 'CA'),
           ),
             'AND' => array(
               array('Company.status' => 'active'),
               array('Company.type' => array('inactive', 'suspended')),
             ),
         );

        $expected = '(`Company`.`name` = :c0 OR `Company`.`city` = :c1) AND (`Company`.`status` = :c2 AND `Company`.`type` IN ( :c3, :c4 ))';
        $this->assertEquals($expected, $Builder->conditions('Company', $conditions));

        $conditions = array(
            'OR' => array(
                array('Company.name' => 'Future Holdings'),
                array('Company.city' => 'CA'),
            ),
              'AND' => array(
                      'OR' => array(
                          array('Company.status' => 'active'),
                          array('Company.type' => array('inactive', 'suspended')),
                      ),
              ),
          );

        $expected = '(`Company`.`name` = :c5 OR `Company`.`city` = :c6) AND ((`Company`.`status` = :c7 OR `Company`.`type` IN ( :c8, :c9 )))';
        $this->assertEquals($expected, $Builder->conditions('Company', $conditions));

        $conditions = array(
            'OR' => array(
                array('Company.name' => 'Future Holdings'),
                array('Company.city' => 'CA'),
            ),
            'AND' => array(
                array(
                    'OR' => array(
                        array('Company.status' => 'active'),
                        'NOT' => array(
                            array('Company.status' => array('inactive', 'suspended')),
                        ),
                    ),
                ),
            ),
        );
        $expected = '(`Company`.`name` = :c10 OR `Company`.`city` = :c11) AND ((`Company`.`status` = :c12 OR NOT (`Company`.`status` IN ( :c13, :c14 ))))';
        $this->assertEquals($expected, $Builder->conditions('Company', $conditions));
    }

    // move condition functions first, since if this fails then all will fail
    public function testInsertStatement()
    {
        $Builder = new QueryBuilder('user');
        $data = array(
        'name' => 'Amanda Lee',
        'email' => 'amanda@example.com',
        'phone' => '+1 123 4567',
        'description' => ''
      );
        $expected = 'INSERT INTO user ( name, email, phone, description ) VALUES ( :u0, :u1, :u2, :u3 )';
        $this->assertEquals($expected, $Builder->insertStatement(array('data' => $data)));

        $expected = array(
            'u0' => 'Amanda Lee',
            'u1' => 'amanda@example.com',
            'u2' => '+1 123 4567',
            'u3' => null
        );

        $this->assertEquals($expected, $Builder->getValues());

        $this->expectException(QueryBuilderException::class);
        $Builder->insertStatement([]);
    }

    public function testUpdateStatement()
    {
        $Builder = new QueryBuilder('user');
        $data = array(
        'data' => array(
          'id' => 85,
          'name' => 'Amanda Lee',
          'email' => 'amanda@example.com',
          'phone' => '+1 123 4567',
          'description' => ''
        ),
      );
        $expected = 'UPDATE user SET id = :u0, name = :u1, email = :u2, phone = :u3, description = :u4';
        $this->assertEquals($expected, $Builder->updateStatement($data));

        $data = array(
            'data' => array(
              'id' => 85,
              'name' => 'Amanda Lee',
              'email' => 'amanda@example.com',
              'phone' => '+1 123 4567'
            ),
          );

        $expected = array(
        'u0' => 85,
        'u1' => 'Amanda Lee',
        'u2' => 'amanda@example.com',
        'u3' => '+1 123 4567',
      );

        $data['conditions'] = array('id' => 2048);
        $data['order'] = array('id ASC');
        $data['limit'] = 1;

        $expected = 'UPDATE user SET id = :u0, name = :u1, email = :u2, phone = :u3 WHERE `user`.`id` = :u4 ORDER BY id ASC LIMIT 1';
        $this->assertEquals($expected, $Builder->updateStatement($data));
        $expected = array(
        'u0' => 85,
        'u1' => 'Amanda Lee',
        'u2' => 'amanda@example.com',
        'u3' => '+1 123 4567',
        'u4' => 2048,
      );

        $this->assertEquals($expected, $Builder->getValues());

        $this->expectException(QueryBuilderException::class);
        $Builder->updateStatement([]);
    }

    public function testDeleteStatement()
    {
        $Builder = new QueryBuilder('user');

        // Delete entire contents of table
        $data = ['conditions' => []];
        $expected = 'DELETE FROM user';
        $this->assertEquals($expected, $Builder->deleteStatement($data));

        $data = ['conditions' => ['id' => 123456]];
        $expected = 'DELETE FROM user WHERE `user`.`id` = :u0';
        $this->assertEquals($expected, $Builder->deleteStatement($data));

        $data['order'] = array('id ASC');
        $data['limit'] = 1;

        $expected = 'DELETE FROM user WHERE `user`.`id` = :u0 ORDER BY id ASC LIMIT 1';
        $this->assertEquals($expected, $Builder->deleteStatement($data));

        $this->expectException(QueryBuilderException::class);
        $Builder->deleteStatement([]);
    }

    public function testFrom()
    {
        $builder = new MockQueryBuilder('user');
        $builder->from('table', 'alias');
        $this->assertEquals('table', $builder->getProperty('table'));
        $this->assertEquals('alias', $builder->getProperty('alias'));
    }

    public function testWhere()
    {
        $builder = new MockQueryBuilder('user');
        $builder->where(['foo'=>'bar']);
        $query = $builder->getProperty('query');
        $this->assertEquals(['foo'=>'bar'], $query['conditions']);
    }
    public function testGroup()
    {
        $builder = new MockQueryBuilder('user');
        $builder->group(['status']);
        $query = $builder->getProperty('query');
        $this->assertEquals(['status'], $query['group']);
    }
    public function testOrder()
    {
        $builder = new MockQueryBuilder('user');
        $builder->order(['field'=>'ASC']);
        $query = $builder->getProperty('query');
        $this->assertEquals(['field'=>'ASC'], $query['order']);
    }
    public function testLimit()
    {
        $builder = new MockQueryBuilder('user');
        $builder->limit(100, 20);
        $query = $builder->getProperty('query');
        $this->assertEquals(100, $query['limit']);
        $this->assertEquals(20, $query['offset']);
    }
    public function testPage()
    {
        $builder = new MockQueryBuilder('user');
        $builder->page(10);
        $query = $builder->getProperty('query');
        $this->assertEquals(10, $query['page']);
    }

    public function testJoin()
    {
        $builder = new MockQueryBuilder('user');
        $params = [
            'table' => 'users',
            'alias' => null,
            'type' => 'INNER',
            'conditions' => ['active'=>1],
        ];
        $builder->join($params);
        $query = $builder->getProperty('query');
        $params['alias'] = 'users';
        $this->assertEquals($params, $query['joins'][0]);
    }
    public function testLeftJoin()
    {
        $builder = new MockQueryBuilder('user');
        $builder->leftJoin(['table'=>'users','alias'=>'users']);
        $query = $builder->getProperty('query');
        
        $this->assertEquals('LEFT', $query['joins'][0]['type']);
    }
    public function testInnerJoin()
    {
        $builder = new MockQueryBuilder('user');
        $builder->innerJoin(['table'=>'users','alias'=>'users']);
        $query = $builder->getProperty('query');
        
        $this->assertEquals('INNER', $query['joins'][0]['type']);
    }
    public function testRightJoin()
    {
        $builder = new MockQueryBuilder('user');
        $builder->rightJoin(['table'=>'users','alias'=>'users']);
        $query = $builder->getProperty('query');
        
        $this->assertEquals('RIGHT', $query['joins'][0]['type']);
    }
    public function testFullJoin()
    {
        $builder = new MockQueryBuilder('user');
        $builder->fullJoin(['table'=>'users','alias'=>'users']);
        $query = $builder->getProperty('query');
        
        $this->assertEquals('FULL', $query['joins'][0]['type']);
    }
}
