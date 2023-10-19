<?php

namespace Nevadskiy\ManyToAny\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Nevadskiy\ManyToAny\HasBelongsToAny;
use Nevadskiy\ManyToAny\BelongsToAny;
use PHPUnit\Framework\Attributes\Test;

class BelongsToAnyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Parent table
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        // Pivot table
        Schema::create('page_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages');
            $table->morphs('page_component');
            $table->integer('position')->unsigned()->default(0);
        });

        // Related table #1
        Schema::create('hero_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->timestamps();
        });

        // Related table #2
        Schema::create('demo_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->timestamps();
        });

        // Related table #3
        Schema::create('faq_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
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

    protected function tearDown(): void
    {
        // Parent table
        Schema::drop('pages');

        // Pivot table
        Schema::drop('page_components');

        // Related tables
        Schema::drop('hero_sections');
        Schema::drop('demo_sections');
        Schema::drop('faq_sections');

        parent::tearDown();
    }
}

class Page extends Model
{
    use HasBelongsToAny;

    public function components(): BelongsToAny
    {
        return $this->belongsToAny('page_component');
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
    public function pages(): MorphToMany
    {
        return $this->morphToMany(Page::class, 'section');
    }
}
