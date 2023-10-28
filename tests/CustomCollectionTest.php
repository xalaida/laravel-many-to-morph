<?php

namespace Nevadskiy\ManyToMorph\Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Nevadskiy\ManyToMorph\HasManyToMorph;
use Nevadskiy\ManyToMorph\ManyToMorph;

class CustomCollectionTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		Capsule::schema()->create('tags', function (Blueprint $table) {
			$table->id();
			$table->timestamps();
		});

		Capsule::schema()->create('taggables', function (Blueprint $table) {
			$table->id();
			$table->foreignId('tag_id')->constrained('pages');
			$table->morphs('taggable');
		});

		Capsule::schema()->create('posts', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->timestamps();
		});
	}

	/**
	 * @test
	 */
	public function it_uses_custom_collection(): void
	{
		$tag = TagForCustomCollection::create();

		$tag->taggables()->attach(
			PostForCustomCollection::create([
				'name' => 'Rayfield Caliburn'
			])
		);

		static::assertCount(1, $tag->taggables);
		static::assertInstanceOf(CustomCollection::class, $tag->taggables);
		static::assertInstanceOf(PostForCustomCollection::class, $tag->taggables[0]);
	}

	protected function tearDown(): void
	{
		Capsule::schema()->drop('posts');
		Capsule::schema()->drop('taggables');
		Capsule::schema()->drop('tags');

		parent::tearDown();
	}
}

class TagForCustomCollection extends Model
{
	use HasManyToMorph;

	protected $table = 'tags';

	public function taggables(): ManyToMorph
	{
		return $this->manyToMorph('taggable', 'taggables', 'taggable_type', 'taggable_id', 'tag_id')
			->collectUsing(CustomCollection::class);
	}
}

class PostForCustomCollection extends Model
{
	protected $table = 'posts';
}

class CustomCollection extends Collection
{
}
