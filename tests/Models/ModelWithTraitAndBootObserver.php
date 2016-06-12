<?php
namespace Sleavely\Datadiff\Tests\Models;

use Sleavely\Datadiff\DatadiffTrait;
use Sleavely\Datadiff\DatadiffObserver;

class ModelWithTraitAndBootObserver extends Model {
  use DatadiffTrait;

  public static function boot()
  {
    // Normally you'd use this method, but because during unit tests
    // boot() is only called for the first test and not
    // subsequent tests we moved this bit to TestCase.php
    //static::observe(new DatadiffObserver);

    return parent::boot();
  }
}
