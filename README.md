# datadiff-laravel

[![Build Status](https://travis-ci.org/Sleavely/datadiff-laravel.svg?branch=master)](https://travis-ci.org/Sleavely/datadiff-laravel)

Save a history of your model edits. Storage format inspired by, and probably compatible with, [makasim/datadiff](https://github.com/makasim/datadiff).

## Requirements

datadiff-laravel was developed for Laravel 4.2 in mind. Your mileage with Laravel 5+ may vary.

This package uses ElasticSearch as a storage solution. We recommend setting up a separate index to hold the diffs since their format may conflict with things like [Elasticquent](https://github.com/elasticquent/Elasticquent).

## Installation

```
composer require sleavely/datadiff-laravel
```

### Configuration
Next, we'll need to tell Laravel we exist. In your `config/app.php`:

```php
<?php

return array(
  // ...
  'providers' => array(
    // ...
    'Sleavely\Datadiff\Providers\DatadiffServiceProvider',
  ),

  // ...
  'aliases' => array(
    // ...
    'Datadiff' => 'Sleavely\Datadiff\Facades\DatadiffFacade',
  ),
);
```

Next, we'll configure storage. Running `php artisan config:publish sleavely/datadiff` will place the following in `app/config/packages/sleavely/datadiff/config.php`:

```php
<?php

return array(
  'storage' => 'elasticsearch',

  'elasticsearch' => array(
    'hosts' => array(
      'localhost:9200'
    ),
    'index' => 'datadiffs',
  ),
);
```

### Eloquent

In your `app/start/global.php`, add the following, for each model you wish to store diffs for:

```php
<?php
// ...

MyEloquentPostModel::observe(new \Sleavely\Datadiff\DatadiffObserver);
MyEloquentCommentModel::observe(new \Sleavely\Datadiff\DatadiffObserver);
```

And in the model:

```php
<?php

class MyEloquentPostModel extends Eloquent {
  use \Sleavely\Datadiff\DatadiffTrait;
}
```

## Usage

### Viewing diffs

Because we added the DatadiffTrait to our model, we can do cool stuff like:

```php

$post = Post::find(123);
$version = $post->diff_version; // 3, there are two older versions
$latestCommit = $post->diff(); // Returns latest version commit
$firstCommit = $post->diff(1); // Fetch version 1

$newPost = new Post;
$newPost->diff_version; // null
$newPost->diff(); // null
```

A commit object looks like:

```json
{
  "data": {
    "id": 123,
    "title": "my post",
    "body": "foo",
    "created_at": "2015-07-21T02:56:47+00:00",
    "updated_at": "2015-07-21T02:56:47+00:00"
  },
  "diff": [],
  "meta": {
    "my_custom_property": "hello world!"
  }
}
```

### Appending metadata

You can append data by setting the `diff_meta` property on an Eloquent model before saving:

```php
<?php

$post = Post::find(123);
$post->diff_meta = [
  'my_custom_property' => 'hello world!',
  'author_id' => Auth::user()->id,
];
$post->save();
```

If you dont want to set diff_meta every time, you can declare your own [accessor](https://laravel.com/docs/4.2/eloquent#accessors-and-mutators) to automatically set the metadata for the commit:

```php
<?php

class MyEloquentPostModel extends Eloquent {
  use \Sleavely\Datadiff\DatadiffTrait;

  public function getDiffMetaAttribute($value)
  {
    return [
      'my_custom_property' => 'hello world!',
      'author_id' => Auth::user()->id,
    ];
  }
}
```

### Comparing objects

You can generate a diff for two arrays with the static `compare` method provided by the Datadiff facade.  
Objects that implement [ArrayableInterface](https://laravel.com/api/4.2/Illuminate/Support/Contracts/ArrayableInterface.html) are okay too. :)

```php
$obj1 = Post::find(111);
$obj2 = Post::find(222);
$diff = Datadiff::compare($obj1, $obj2);
```

The comparison is made with [mikemccabe/json-patch-php](https://github.com/mikemccabe/json-patch-php) and returns an array with JSON Patch operations.

### Visualizing diffs in the browser

I recommend the autonomous package [benjamine/jsondiffpatch](https://github.com/benjamine/jsondiffpatch) for showing diffs. At it's core, this is all you'll have to do:

```js
var commits = "Datadiff::getCommits($model->getTable(), $model->getAttribute($model->getKeyName())) in PHP";
var i = commits.length - 1;
var diffdelta = jsondiffpatch.diff(
  commits[i - 1].data,
  commits[i].data
);

var left = undefined;
// left is optional, if specified unchanged values will be visible too
left = commits[i - 1].data;

document.getElementById('diff').innerHTML = jsondiffpatch.formatters.html.format(diffdelta, left);
```

![](http://i.imgur.com/5iJAq1f.png)

## Contributing

Pull requests are appreciated and encouraged!

```
composer update --dev
```

### Tests

All tests extend `Sleavely\Datadiff\Tests\TestCase`. To run the suite:

```
vendor/bin/phpunit
```
