<?php

namespace Nevadskiy\MorphAny;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * 1st query: join pivot.
 * Next in loop query for each pivot morph type.
 *
 * SELECT * FROM pages
 * JOIN page_sections ON pages.id = page_sections.page_id
 */
class MorphAny extends Relation
{
	use InteractsWithDictionary;
	use GetResults;

	/**
	 * @example "page_sections"
	 */
	protected $table;

	/**
	 * @example page_sections.page_id
	 *
	 * @todo rename to "foreignPivotKey"
	 */
	protected $foreignPivotKey;

	/**
	 * @example pages.id
	 *
	 * @todo rename to "parentKey".
	 */
	protected $parentKey;

	/**
	 * @example page_sections.page_section_type
	 */
	protected $morphTypeKey;

	/**
	 * @example page_sections.page_section_id
	 */
	protected $morphForeignKey;

	protected $dictionary = [];

	/**
	 * @see BelongsToMany::$accessor
	 */
	protected $accessor = 'pivot';

	/**
	 * The class name of the custom pivot model to use for the relationship.
	 *
	 * @var string
	 */
	protected $using;

	/**
	 * Make a new relation instance.
	 */
	public function __construct(
		Builder $query,
		Model   $parent,
		string  $table,
				$foreignPivotKey,
				$parentKey,
				$morphTypeKey,
				$morphForeignKey,
	)
	{
		$this->table = $table;
		$this->foreignPivotKey = $foreignPivotKey;
		$this->parentKey = $parentKey;
		$this->morphTypeKey = $morphTypeKey;
		$this->morphForeignKey = $morphForeignKey;

		parent::__construct($query, $parent);
	}

	/**
	 * @see BelongsToMany::addConstraints
	 */
	public function addConstraints(): void
	{
		if (static::$constraints) {
			$this->addWhereConstraints();
		}
	}

	/**
	 * @see BelongsToMany::addWhereConstraints
	 */
	protected function addWhereConstraints(): void
	{
		$this->query->where([
			$this->getQualifiedForeignPivotKeyName() => $this->parent->getAttribute($this->parentKey)
		]);
	}

	/**
	 * @see BelongsToMany::getQualifiedForeignPivotKeyName
	 */
	public function getQualifiedForeignPivotKeyName(): string
	{
		return $this->qualifyPivotColumn($this->foreignPivotKey);
	}

	/**
	 * @see BelongsToMany::qualifyPivotColumn
	 */
	public function qualifyPivotColumn(string $column): string
	{
		return str_contains($column, '.')
			? $column
			: "{$this->table}.{$column}";
	}

	public function addEagerConstraints(array $models)
	{
		// TODO: Implement addEagerConstraints() method.
	}

	public function initRelation(array $models, $relation)
	{
		// TODO: Implement initRelation() method.
	}

	public function match(array $models, Collection $results, $relation)
	{
		// TODO: Implement match() method.
	}

	/**
	 * @todo use columns dictionary with morph types: [FaqSection::class => ['id', 'heading'], HeroSection::class => ['id', ['heading']]
	 */
	public function get($columns = ['*']): Collection
	{
		return $this->getResults();
	}
}
