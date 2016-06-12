<?php
namespace Sleavely\Datadiff\Tests\Models;

use Sleavely\Datadiff\DatadiffTrait;
use Sleavely\Datadiff\DatadiffObserver;

class ModelWithTraitAndBootObserver extends Model {
  use DatadiffTrait;

  public static function boot()
  {
    static::observe(new DatadiffObserver);
    return parent::boot();
  }
}
