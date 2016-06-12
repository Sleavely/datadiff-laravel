<?php
namespace Sleavely\Datadiff\Tests;

use Sleavely\Datadiff\Datadiff;

class BasicTests extends TestCase {

    public function testModelGeneration()
    {
        $model = new Models\ModelWithTrait;
        $model->fill([
            'author_id' => 1337,
            'title' => 'Hello World',
            'body' => 'This is a body.',
            'published' => true
        ]);
        $model->save();
        $this->assertEquals('Hello World', $model->title);
    }

    public function testModelDiffBeforeSave()
    {
        $model = new Models\ModelWithTrait;
        $model->fill([
            'author_id' => 1337,
            'title' => 'Hello World',
            'body' => 'This is a body.',
            'published' => true
        ]);
        $diff = $model->diff();
        $this->assertNull($diff);
    }

    /**
     * Saving a model without an observer will result in a 404 when attempting to diff
     */
    public function testSavedModelDiffWithNoHistory()
    {
        $model = new Models\ModelWithTrait;
        $model->fill([
            'author_id' => 1337,
            'title' => 'Hello World',
            'body' => 'This is a bodega.',
            'published' => true
        ]);
        $model->save();

        $diff = $model->diff();
        $this->assertNull($diff);
    }

    public function testSavedModelDiffWithOneCommit()
    {
        $model = new Models\ModelWithTraitAndBootObserver;
        $model->fill([
            'author_id' => 1337,
            'title' => 'Hello World',
            'body' => 'This is a body.',
            'published' => true
        ]);
        $model->save();

        $diff = $model->diff();
        $this->assertEquals($diff['data'], $model->toArray());
    }

    public function testCreatingAndDeletingModel()
    {
        $model = new Models\ModelWithTraitAndBootObserver;
        $model->fill([
            'author_id' => 1337,
            'title' => 'Hello World!',
            'body' => 'This is the body.',
            'published' => true
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
          'published' => true
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
