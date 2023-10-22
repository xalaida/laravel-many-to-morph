<?php

namespace Nevadskiy\ManyToAny\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Nevadskiy\ManyToAny\HasManyToMorph;
use Nevadskiy\ManyToAny\ManyToMorph;
use PHPUnit\Framework\Attributes\Test;

class ManyToMorphTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('page_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages');
            $table->morphs('page_component');
            $table->integer('position')->unsigned()->default(0);
        });

        Schema::create('hero_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->timestamps();
        });

        Schema::create('demo_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->timestamps();
        });

        Schema::create('faq_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->timestamps();
        });

		Schema::create('faq_section_items', function (Blueprint $table) {
			$table->id();
			$table->foreignId('faq_section_id')->constrained('faq_sections');
			$table->string('question');
			$table->string('answer');
			$table->timestamps();
		});

        Model::unguard();
    }

	#[Test]
	public function it_attaches_belongs_to_any_models(): void
	{
		/** @var Page $page */
		$page = Page::create();

		$heroSection = HeroSection::create([
			'heading' => 'Hero Section'
		]);

		$page->components()->attach($heroSection);

		$this->assertDatabaseHas('page_components', [
			'page_id' => $page->id,
			'page_component_id' => $heroSection->id,
			'page_component_type' => $heroSection->getMorphClass(),
		]);
	}

	#[Test]
	public function it_attaches_belongs_to_any_models_with_pivot_attributes(): void
	{
		/** @var Page $page */
		$page = Page::create();

		$heroSection = HeroSection::create([
			'heading' => 'Hero Section'
		]);

		$page->components()->attach($heroSection, [
			'position' => 1337,
		]);

		$this->assertDatabaseHas('page_components', [
			'page_id' => $page->id,
			'page_component_id' => $heroSection->id,
			'page_component_type' => $heroSection->getMorphClass(),
			'position' => 1337,
		]);
	}

	#[Test]
	public function it_updates_pivot_attributes(): void
	{
		/** @var Page $page */
		$page = Page::create();

		$heroSection = HeroSection::create([
			'heading' => 'Hero Section'
		]);

		$page->components()->attach($heroSection, ['position' => 0]);

		$page->components()->updateExistingPivot($heroSection, ['position' => 1337]);

		$this->assertDatabaseHas('page_components', [
			'page_id' => $page->id,
			'page_component_id' => $heroSection->id,
			'page_component_type' => $heroSection->getMorphClass(),
			'position' => 1337,
		]);
	}

	#[Test]
	public function it_detaches_belongs_to_any_models(): void
	{
		/** @var Page $page */
		$page = Page::create();

		$heroSection = HeroSection::create([
			'heading' => 'Hero Section'
		]);

		$page->components()->attach($heroSection);

		$page->components()->detach($heroSection);

		$this->assertDatabaseCount('page_components', 0);
	}

	#[Test]
    public function it_gets_belongs_to_any_models(): void
    {
        /** @var Page $page */
        $page = Page::create();

        $page->components()->attach(
            $heroSection = HeroSection::create([
                'heading' => 'Hero Section'
            ])
        );

        $page->components()->attach(
            $demoSection = DemoSection::create([
                'heading' => 'Demo Section'
            ])
        );

        $page->components()->attach(
            $faqSection = FaqSection::create([
                'heading' => 'FAQ Section'
            ])
        );

        $components = $page->components()->get();

        static::assertCount(3, $components);
        static::assertTrue($components[0]->is($heroSection));
        static::assertTrue($components[1]->is($demoSection));
        static::assertTrue($components[2]->is($faqSection));
    }

	#[Test]
	public function it_sorts_models_by_pivot_attribute(): void
	{
		/** @var Page $page */
		$page = Page::create();

		$page->components()->attach(
			$faqSection = FaqSection::create([
				'heading' => 'FAQ Section'
			]),
			['position' => 2]
		);

		$page->components()->attach(
			$demoSection = DemoSection::create([
				'heading' => 'Demo Section'
			]),
			['position' => 1]
		);

		$page->components()->attach(
			$heroSection = HeroSection::create([
				'heading' => 'Hero Section'
			]),
			['position' => 0]
		);

		$components = $page->components()->orderBy('position')->get();

		static::assertCount(3, $components);
		static::assertTrue($components[0]->is($heroSection));
		static::assertTrue($components[1]->is($demoSection));
		static::assertTrue($components[2]->is($faqSection));
	}

	#[Test]
	public function it_eager_loads_belongs_to_any_models(): void
	{
		/** @var Page $page */
		$page = Page::create();

		$page->components()->attach(
			FaqSection::create([
				'heading' => 'FAQ Section'
			])
		);

		$pages = Page::query()->with('components')->get();

		static::assertCount(1, $pages);
		static::assertTrue($pages[0]->relationLoaded('components'));
		static::assertCount(1, $pages[0]->components);
	}

	#[Test]
	public function it_eager_loads_belongs_to_any_nested_models(): void
	{
		/** @var Page $page */
		$page = Page::create();

		/** @var FaqSection $faqSection */
		$faqSection = FaqSection::create([
			'heading' => 'FAQ Section'
		]);

		$faqSection->items()->createMany([
			[
				'question' => 'First question',
				'answer' => 'First answer',
			],
			[
				'question' => 'Second question',
				'answer' => 'Second answer',
			],
			[
				'question' => 'Third question',
				'answer' => 'Third answer',
			]
		]);

		$page->components()->attach($faqSection);

		$pages = Page::query()
			->with(['components' => function (ManyToMorph $relation) {
				$relation->morphWith([
					FaqSection::class => ['items'],
				]);
			}])
			->get();

		static::assertCount(1, $pages);
		static::assertTrue($pages[0]->relationLoaded('components'));
		static::assertCount(1, $pages[0]->components);
		static::assertTrue($pages[0]->components[0]->relationLoaded('items'));
		static::assertCount(3, $pages[0]->components[0]->items);
	}

    protected function tearDown(): void
    {
		Schema::drop('faq_section_items');
		Schema::drop('faq_sections');
		Schema::drop('hero_sections');
		Schema::drop('demo_sections');
        Schema::drop('page_components');
		Schema::drop('pages');

        parent::tearDown();
    }
}

class Page extends Model
{
    use HasManyToMorph;

    public function components(): ManyToMorph
    {
        return $this->manyToMorph('page_component');
    }
}

class HeroSection extends Model
{
    public function pages(): MorphToMany
    {
        return $this->morphToMany(Page::class, 'section');
    }
}

class DemoSection extends Model
{
    public function pages(): MorphToMany
    {
        return $this->morphToMany(Page::class, 'section');
    }
}

class FaqSection extends Model
{
	public function items(): HasMany
	{
		return $this->hasMany(FaqSectionItem::class);
	}

    public function pages(): MorphToMany
    {
        return $this->morphToMany(Page::class, 'section');
    }
}

class FaqSectionItem extends Model
{
}
