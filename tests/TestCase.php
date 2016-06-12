<?php
namespace Sleavely\Datadiff\Tests;

use Elasticsearch\Client as ElasticsearchClient;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra {
  
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
    $index = $app['config']->get('datadiff::elasticsearch.index', 'datadiffs');
    $app['config']->set('datadiff::elasticsearch.index', $index.'test');
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

  public static function tearDownAfterClass()
  {
    // remove the elasticsearchindex
    $hosts = \Config::get('datadiff::elasticsearch.hosts');
    $client = new ElasticsearchClient(['hosts' => $hosts]);

    $params = [
      'index' => \Config::get('datadiff::elasticsearch.index')
    ];
    $client->indices()->delete($params);

    // Now let PHPUnit and the other frameworks do their thing(s)
    parent::tearDownAfterClass();
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
