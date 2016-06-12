<?php
namespace Sleavely\Datadiff\Tests;

use Sleavely\Datadiff\Datadiff;

class BasicTests extends TestCase {

  /**
   * Models with just the trait shouldn't affect anything.
   */
  public function testModelGeneration()
  {
    $model = new Models\ModelWithTrait;
    $model->fill([
      'author_id' => 1337,
      'title' => 'Hello World',
      'body' => 'This is a body.',
      'published' => true,
      'id' => 1
    ]);
    $model->save();
    $this->assertEquals('Hello World', $model->title);
    $this->assertNotNull($model->id);
  }

  /**
   * diff()ing models without an ID shouldnt even query ES, and default to null
   */
  public function testModelDiffBeforeSave()
  {
    $model = new Models\ModelWithTrait;
    $model->fill([
      'author_id' => 1337,
      'title' => 'Hello World',
      'body' => 'This is a body.',
      'published' => true,
      'id' => 2
    ]);
    $diff = $model->diff();
    $this->assertNull($diff);
  }

  /**
   * Attempting to diff() models without DatadiffObserver
   * should result in no diff (null).
   */
  public function testSavedModelDiffWithNoHistory()
  {
    $model = new Models\ModelWithTrait;
    $model->fill([
      'author_id' => 1337,
      'title' => 'Hello World',
      'body' => 'This is a bodega.',
      'published' => true,
      'id' => 3
    ]);
    $model->save();

    $diff = $model->diff();
    $this->assertNull($diff);
  }

  /**
   * When saving a proper Datadiff-enabled model, verify that diff()
   * returns latest data version when called without argument.
   */
  public function testSavedModelDiffWithOneCommit()
  {
    $model = new Models\ModelWithTraitAndBootObserver;
    $model->fill([
      'author_id' => 1337,
      'title' => 'Hello World',
      'body' => 'This is a body.',
      'published' => true,
      'id' => 4
    ]);
    $model->save();

    $diff = $model->diff();
    $this->assertEquals($model->toArray(), $diff['data']);
  }

  /**
   * Verify that diff() goes back to null results after deleting data.
   */
  public function testCreatingAndDeletingModel()
  {
    $model = new Models\ModelWithTraitAndBootObserver;
    $model->fill([
      'author_id' => 1337,
      'title' => 'Hello World!',
      'body' => 'This is the body.',
      'published' => true,
      'id' => 5
    ]);
    $model->save();
    $diff = $model->diff();
    $this->assertEquals($model->toArray(), $diff['data']);

    $model->delete();
    $diff = $model->diff();
    $this->assertNull($diff);
  }

  public function testSaveModelTwiceAndDiff()
  {
    $model = new Models\ModelWithTraitAndBootObserver;
    $model->fill([
      'author_id' => 1337,
      'title' => 'Hello',
      'body' => 'This is a body. :D',
      'published' => true,
      'id' => 6
    ]);
    $model->save();
    $diff = $model->diff();
    $this->assertEquals($model->toArray(), $diff['data']);

    $model->title = 'Hello World';
    $model->save();
    $diff = $model->diff();
    $this->assertEquals($model->toArray(), $diff['data']);
  }
}
