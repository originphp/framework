<?php
namespace Origin\Test\Console\Command;

use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class Article extends Model
{
    public function initialize(array $config) : void
    {
        $this->loadBehavior('Elasticsearch', [
            'connection' => 'test',
        ]);
    }
}

class ElasticsearchReindexCommandTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article'];

    use ConsoleIntegrationTestTrait;

    protected function setUp() : void
    {
        parent::setUp();
        if (env('ELASTICSEARCH_HOST') === null) {
            $this->markTestSkipped('Elasticsearch not available');
        }
        
        ModelRegistry::set('Article', new Article(['connection' => 'test']));
        ModelRegistry::set('User', new Model(['name' => 'User','connection' => 'test']));
    }

    public function testMissingRequiredArgument()
    {
        $this->exec('elasticsearch:reindex');
        $this->assertExitError();
        $this->assertErrorContains('Missing required argument `model`');
    }

    public function testSkippingModel()
    {
        $this->exec('elasticsearch:reindex User');
        $this->assertExitSuccess();
        $this->assertOutputContains('User does not have Elasticsearch Behavior loaded');
    }

    public function testExecute()
    {
        $this->exec('elasticsearch:reindex Article');
        $this->assertExitSuccess();
        $this->assertOutputContains('Article index created and 3 record(s) added to index');
    }
}
