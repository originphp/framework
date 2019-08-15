<?php
namespace Origin\Test\Command;

use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class Article extends Model
{
    public function initialize(array $config)
    {
        $this->loadBehavior('Elasticsearch', [
            'connection' => 'test',
        ]);
    }
}

class ElasticsearchIndexCommandTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article'];

    use ConsoleIntegrationTestTrait;

    public function startup()
    {
        ModelRegistry::set('Article', new Article(['datasource' => 'test']));
        ModelRegistry::set('User', new Model(['name' => 'User','datasource' => 'test']));
    }

    public function testMissingRequiredArgument()
    {
        $this->exec('elasticsearch:index');
        $this->assertExitError();
        $this->assertErrorContains('Missing required argument `model`');
    }

    public function testSkippingModel()
    {
        $this->exec('elasticsearch:index User');
        $this->assertExitSuccess();
        $this->assertOutputContains('User does not have Elasticsearch Behavior loaded');
    }

    public function testExecute()
    {
        $this->exec('elasticsearch:index Article');
        $this->assertExitSuccess();
        $this->assertOutputContains('Article index created and 3 record(s) added to index');
    }
}
