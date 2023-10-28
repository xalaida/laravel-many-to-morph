<?php

namespace Nevadskiy\ManyToMorph\Tests;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Nevadskiy\ManyToMorph\HasManyToMorph;
use Nevadskiy\ManyToMorph\ManyToMorph;

class CustomPivotTest extends TestCase
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
			$table->integer('score')->unsigned()->default(0);
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

		$tag = TagForCustomPivot::create();

		$tag->taggables()->attach(
			PostsForCustomPivot::create([
				'name' => 'Rayfield Caliburn'
			]),
			['score' => 1337],
		);

		static::assertCount(1, $tag->taggables);
		static::assertInstanceOf(TaggableForCustomPivot::class, $tag->taggables[0]->pivot);
		static::assertEquals(1337, $tag->taggables[0]->pivot->score);
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

class TagForCustomPivot extends Model
{
	use HasManyToMorph;

	protected $table = 'tags';

	public function taggables(): ManyToMorph
	{
		return $this->manyToMorph('taggable', TaggableForCustomPivot::class, 'taggable_type', 'taggable_id', 'tag_id');
	}
}

class TaggableForCustomPivot extends Model
{
	protected $table = 'taggables';
}

class PostsForCustomPivot extends Model
{
	protected $table = 'posts';
}
