<?php

namespace Nevadskiy\ManyToMorph\Tests;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Nevadskiy\ManyToMorph\HasManyToMorph;
use Nevadskiy\ManyToMorph\ManyToMorph;

class TimestampsTest extends TestCase
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
			$table->timestamps();
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
	public function it_uses_custom_pivot_model(): void
	{
		Carbon::setTestNow($now = Carbon::now()->startOfSecond());

		$tag = TagForTimestamps::create();

		$tag->taggables()->attach(
			PostsForTimestamps::create([
				'name' => 'Rayfield Caliburn'
			]),
		);

		static::assertCount(1, $tag->taggables);
		static::assertEquals($now, $tag->taggables[0]->pivot->updated_at);
		static::assertEquals($now, $tag->taggables[0]->pivot->created_at);
	}

	protected function tearDown(): void
	{
		Capsule::schema()->drop('posts');
		Capsule::schema()->drop('taggables');
		Capsule::schema()->drop('tags');

		parent::tearDown();
	}
}

class TagForTimestamps extends Model
{
	use HasManyToMorph;

	protected $table = 'tags';

	public function taggables(): ManyToMorph
	{
		return $this->manyToMorph('taggable', 'taggables', 'taggable_type', 'taggable_id', 'tag_id')
			->withTimestamps();
	}
}

class PostsForTimestamps extends Model
{
	protected $table = 'posts';
}
