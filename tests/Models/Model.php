<?php
namespace Sleavely\Datadiff\Tests\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel {

  protected $table = 'models';

  protected $guarded = ['id'];
}
