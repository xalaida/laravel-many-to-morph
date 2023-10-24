<?php

namespace Nevadskiy\ManyToMorph\Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nevadskiy\ManyToMorph\HasManyToMorph;
use Nevadskiy\ManyToMorph\ManyToMorph;

class ManyToMorphTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Capsule::schema()->create('pages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Capsule::schema()->create('page_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages');
            $table->morphs('page_component');
            $table->integer('position')->unsigned()->default(0);
        });

        Capsule::schema()->create('hero_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->timestamps();
        });

        Capsule::schema()->create('demo_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->timestamps();
        });

        Capsule::schema()->create('faq_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->timestamps();
        });

		Capsule::schema()->create('faq_section_items', function (Blueprint $table) {
			$table->id();
			$table->foreignId('faq_section_id')->constrained('faq_sections');
			$table->string('question');
			$table->string('answer');
			$table->timestamps();
		});

        Model::unguard();
    }

	/**
	 * @test
	 */
	public function it_attaches_belongs_to_any_models(): void
	{
		/** @var Page $page */
		$page = Page::create();

		$heroSection = HeroSection::create([
			'heading' => 'Hero Section'
		]);

		$page->components()->attach($heroSection);

		$pageComponents = Capsule::table('page_components')->get();

		static::assertCount(1, $pageComponents);
		static::assertEquals($page->id, $pageComponents[0]->page_id);
		static::assertEquals($heroSection->id, $pageComponents[0]->page_component_id);
		static::assertEquals($heroSection->getMorphClass(), $pageComponents[0]->page_component_type);
	}

	/**
	 * @test
	 */
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

		$pageComponents = Capsule::table('page_components')->get();

		static::assertCount(1, $pageComponents);
		static::assertEquals($page->id, $pageComponents[0]->page_id);
		static::assertEquals($heroSection->id, $pageComponents[0]->page_component_id);
		static::assertEquals($heroSection->getMorphClass(), $pageComponents[0]->page_component_type);
		static::assertEquals(1337, $pageComponents[0]->position);
	}

	/**
	 * @test
	 */
	public function it_updates_pivot_attributes(): void
	{
		/** @var Page $page */
		$page = Page::create();

		$heroSection = HeroSection::create([
			'heading' => 'Hero Section'
		]);

		$page->components()->attach($heroSection, ['position' => 0]);

		$page->components()->updateExistingPivot($heroSection, ['position' => 1337]);

		$pageComponents = Capsule::table('page_components')->get();

		static::assertCount(1, $pageComponents);
		static::assertEquals($page->id, $pageComponents[0]->page_id);
		static::assertEquals($heroSection->id, $pageComponents[0]->page_component_id);
		static::assertEquals($heroSection->getMorphClass(), $pageComponents[0]->page_component_type);
		static::assertEquals(1337, $pageComponents[0]->position);
	}

	/**
	 * @test
	 */
	public function it_detaches_belongs_to_any_models(): void
	{
		/** @var Page $page */
		$page = Page::create();

		$heroSection = HeroSection::create([
			'heading' => 'Hero Section'
		]);

		$page->components()->attach($heroSection);

		$page->components()->detach($heroSection);

		static::assertCount(0, Capsule::table('page_components')->get());
	}

	/**
	 * @test
	 */
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

	/**
	 * @test
	 */
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

	/**
	 * @test
	 */
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

	/**
	 * @test
	 */
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
		Capsule::schema()->drop('faq_section_items');
		Capsule::schema()->drop('faq_sections');
		Capsule::schema()->drop('hero_sections');
		Capsule::schema()->drop('demo_sections');
        Capsule::schema()->drop('page_components');
		Capsule::schema()->drop('pages');

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
        return $this->morphToMany(Page::class, 'page_component');
    }
}

class DemoSection extends Model
{
    public function pages(): MorphToMany
    {
        return $this->morphToMany(Page::class, 'page_component');
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
        return $this->morphToMany(Page::class, 'page_component');
    }
}

class FaqSectionItem extends Model
{
}
