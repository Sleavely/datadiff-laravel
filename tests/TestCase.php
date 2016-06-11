<?php
namespace Sleavely\Datadiff\Tests;

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

        //TODO: create elasticsearchindex
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

    protected function tearDown()
    {
        //TODO: remove elasticsearchindex
    }

}
