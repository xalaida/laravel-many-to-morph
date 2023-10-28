# The Many-To-Morph relationship for Eloquent

A package that simplifies and enhances Many-To-Many polymorphic relationships in Laravel's Eloquent ORM.

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://stand-with-ukraine.pp.ua)

[![PHPUnit](https://img.shields.io/github/actions/workflow/status/nevadskiy/laravel-many-to-morph/phpunit.yml?branch=master)](https://packagist.org/packages/nevadskiy/laravel-many-to-morph)
[![Code Coverage](https://img.shields.io/codecov/c/github/nevadskiy/laravel-many-to-morph?token=9X6AQQYCPA)](https://packagist.org/packages/nevadskiy/laravel-many-to-morph)
[![Latest Stable Version](https://img.shields.io/packagist/v/nevadskiy/laravel-many-to-morph)](https://packagist.org/packages/nevadskiy/laravel-many-to-morph)
[![License](https://img.shields.io/github/license/nevadskiy/laravel-many-to-morph)](https://packagist.org/packages/nevadskiy/laravel-many-to-morph)

## Introduction
 
One common type of relationship in Laravel's Eloquent ORM is the [Many-To-Many polymorphic relation](https://laravel.com/docs/10.x/eloquent-relationships#many-to-many-polymorphic-relations).
While it works well for most use cases, you might encounter certain challenges that require more elegant solutions:

1. When you have numerous related models, you need to define a separate relation for each type of model.
2. It is hard to retrieve all related models at once.

This package introduces a new Many-To-Morph relationship, inspired by the Directus's [Many-to-Any relation](https://docs.directus.io/app/data-model/relationships.html#many-to-any-m2a) that handles these problems.

## Table of Contents

- [Installation](#-installation)
- [Documentation](#-documentation)
	- [Configuring Relationship](#configuring-relationship)
	- [Retrieving Relationships](#retrieving-relationships)
	- [Ordering Relationships](#ordering-relationships)
	- [Eager Loading Relationships](#eager-loading-relationships)
	- [Attaching Relationships](#attaching-relationships)
	- [Detaching Relationships](#detaching-relationships)
- [License](#-license)

## 🔌 Installation

Install the package via Composer:

```bash
composer require nevadskiy/laravel-many-to-morph
```

## 📄 Documentation

### Configuring Relationship

To configure this relationship, you need to use the `HasManyToMorph` trait, which provides a `manyToMorph` method for defining the relation as follows:

```php
use Nevadskiy\ManyToMorph\HasManyToMorph;
use Nevadskiy\ManyToMorph\ManyToMorph;

class Tag extends Model
{
	use HasManyToMorph;

    public function taggables(): ManyToMorph
    {
        return $this->manyToMorph('taggable');
    }
}
```

### Retrieving Relationships

You can retrieve relationships as shown below:

```php
use App\Models\Tag;
use App\Models\Post;
use App\Models\Video;

$tag = Tag::find(1);

foreach ($tag->taggables as $taggable) {
	if ($taggable instanceof Post) {
		// ...
	} else if ($taggable instanceof Video) {
		// ...
	}
}
```

### Ordering Relationships

To order relationships, you can use the `orderBy` method directly on the `taggables` relation like so:

```php
use App\Models\Tag;

$tag = Tag::find(1);

$tag->taggables()->orderBy('position')->get();
```

### Eager Loading Relationships

Eager loading relationships can be done like this:

```php
use App\Models\Tag;
use App\Models\Post;
use App\Models\Video;

$tags = Tag::query()
	->with(['taggables' => function (ManyToMorph $taggables) {
		$taggables->morphWith([
			Post::class => ['media'],
			Video::class => ['previews'],
		]);
	}])
	->get();
```

### Attaching Relationships

You can attach relationships with the following code:

```php
use App\Models\Tag;
use App\Models\Post;
use App\Models\Video;

$tag = Tag::find(1);

$post = Post::find(1);

$tag->taggables()->attach($post);

$video = Video::find(1);
```

You can also attach a model with pivot attributes:

```php
$tag->taggables()->attach($video, ['score' => 1337]);
```

### Detaching Relationships

To detach relationships, use the following code:

```php
use App\Models\Tag;
use App\Models\Video;

$tag = Tag::find(1);

$video = Video::find(1);

$tag->taggables()->detach($video);
```

## 📜 License

This package is open-source and is released under the MIT License. Please refer to the [LICENSE](LICENSE) file for more information.
