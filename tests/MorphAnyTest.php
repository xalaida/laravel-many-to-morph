<?php

namespace Nevadskiy\MorphAny\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Nevadskiy\MorphAny\HasMorphedByAny;
use Nevadskiy\MorphAny\MorphAny;
use PHPUnit\Framework\Attributes\Test;

class MorphAnyTest extends TestCase
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
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->index()->constrained('pages');
            $table->morphs('page_section');
            $table->integer('position')->unsigned()->default(0);
            $table->timestamps();
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
    public function it_returns_morphed_by_many_records(): void
    {
        /** @var Page $page */
        $page = Page::create();

        $page->heroSections()->attach(
            $heroSection = HeroSection::create([
                'heading' => 'Hero Section'
            ])
        );

        $page->demoSections()->attach(
            $demoSection = DemoSection::create([
                'heading' => 'Demo Section'
            ])
        );

        $page->faqSections()->attach(
            $faqSection = FaqSection::create([
                'heading' => 'FAQ Section'
            ])
        );

        $sections = $page->sections()->get();

        static::assertCount(3, $sections);
        static::assertTrue($sections[0]->is($heroSection));
        static::assertTrue($sections[1]->is($demoSection));
        static::assertTrue($sections[2]->is($faqSection));
    }

    protected function tearDown(): void
    {
        // Parent table
        Schema::drop('pages');

        // Pivot table
        Schema::drop('page_sections');

        // Related tables
        Schema::drop('hero_sections');
        Schema::drop('demo_sections');
        Schema::drop('faq_sections');

        parent::tearDown();
    }
}

class Page extends Model
{
    use HasMorphedByAny;

    public function sections(): MorphAny
    {
        return $this->morphedByAny();
    }

    public function heroSections(): MorphToMany
    {
        return $this->morphedByMany(HeroSection::class, 'page_section');
    }

    public function demoSections(): MorphToMany
    {
        return $this->morphedByMany(DemoSection::class, 'page_section');
    }

    public function faqSections(): MorphToMany
    {
        return $this->morphedByMany(FaqSection::class, 'page_section');
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
