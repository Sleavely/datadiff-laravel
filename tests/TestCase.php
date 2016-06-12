<?php
namespace Sleavely\Datadiff\Tests;

use Elasticsearch\Client as ElasticsearchClient;
use Orchestra\Testbench\TestCase as Orchestra;
use Zumba\PHPUnit\Extensions\ElasticSearch\TestTrait as ElasticTestTrait;
use Zumba\PHPUnit\Extensions\ElasticSearch\Client\Connector as ElasticTestConnector;
use Zumba\PHPUnit\Extensions\ElasticSearch\DataSet\DataSet as ElasticTestDataSet;

abstract class TestCase extends Orchestra {

  use ElasticTestTrait;

  /**
  * Setup the test environment.
  *
  * @return void
  */
  public function setUp()
  {
    parent::setUp();

    // Create an artisan object for calling migrations
    $artisan = $this->app->make('artisan');

    // Call migrations specific to our tests, e.g. to seed the db
    $artisan->call('migrate', [
      '--database' => 'testbench',
      '--path' => '../tests/Migrations'
    ]);

    Models\ModelWithTraitAndBootObserver::observe(new \Sleavely\Datadiff\DatadiffObserver);
  }

  /**
  * Define environment setup.
  *
  * @param  \Illuminate\Foundation\Application $app
  * @return void
  */
  protected function getEnvironmentSetUp($app)
  {
    // reset base path to point to our package's src directory
    $app['path.base'] = __DIR__ . '/../src';

    // set up database configuration
    $app['config']->set('database.default', 'testbench');
    $app['config']->set('database.connections.testbench', [
      'driver' => 'sqlite',
      'database' => ':memory:',
      'prefix' => '',
    ]);
    // Avoid crushing an existing index
    $app['datadiff']->setEsTestClient($this->getElasticSearchConnector()->getConnection());
  }

  /**
  * Get the ElasticSearch connection for this test.
  *
  * @return Zumba\PHPUnit\Extensions\ElasticSearch\Client\Connector
  */
  public function getElasticSearchConnector() {
    if (empty($this->connection)) {
      $this->connection = new ElasticTestConnector(
        new ElasticsearchClient()
      );
    }
    return $this->connection;
  }

  /**
  * Get the dataset to be used for this test.
  *
  * @return Zumba\PHPUnit\Extensions\ElasticSearch\DataSet\DataSet
  */
  public function getElasticSearchDataSet() {
    $dataset = new ElasticTestDataSet($this->getElasticSearchConnector());
    $dataset->setFixture([
      'datadifftests' => [
        'models' => [
          [
            'author_id' => 1,
            'title' => 'Dummy filler',
            'body' => 'Some content.',
            'published' => false,
            'id' => 9999
          ]
        ]
      ]
    ]);
    return $dataset;
  }

  /**
  * Load Datadiff package provider
  *
  * @return array
  */
  protected function getPackageProviders()
  {
    return array('Sleavely\Datadiff\DatadiffServiceProvider');
  }

  /**
  * Load Datadiff alias facade
  *
  * @return array
  */
  protected function getPackageAliases()
  {
    return array(
      'Datadiff' => 'Sleavely\Datadiff\Facades\DatadiffFacade'
    );
  }

  /**
  * Helper for debugging tests
  */
  public function out($var)
  {
    if(is_array($var) || is_object($var))
    {
      print print_r($var, TRUE).PHP_EOL;
      return;
    }
    print var_export($var, TRUE).PHP_EOL;
  }

}
