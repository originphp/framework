<?php
namespace Origin\Test\Console\Command;

use Origin\Model\Concern\Elasticsearch;
use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class Article extends Model
{
    use Elasticsearch;
    protected $elasticsearchConnection = 'test';
}

class ElasticsearchReindexCommandTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article'];

    use ConsoleIntegrationTestTrait;

    protected function setUp() : void
    {
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
        $this->assertOutputContains('User does not implement the Elasticsearch Concern');
    }

    public function testExecute()
    {
        $this->exec('elasticsearch:reindex Article');
        $this->assertExitSuccess();
        $this->assertOutputContains('Article index created and 3 record(s) added to index');
    }
}
