<?php

namespace Nevadskiy\MorphAny;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Relation;

class MorphAny extends Relation
{
	use GetResults;
	use InteractsWithDictionary;

	protected $pivotTable;

	/**
	 * @example page_sections.page_id
	 *
	 * @todo rename to "pivotForeignKey"
	 */
	protected $pivotForeignKey;

	/**
	 * @example pages.id
	 *
	 * @todo rename to "parentKey".
	 */
	protected $parentKey;

	/**
	 * @example page_sections.page_section_type
	 */
	protected $pivotMorphTypeKey;

	/**
	 * @example page_sections.page_section_id
	 */
	protected $pivotMorphForeignKey;

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
		string  $pivotTable,
		string  $pivotForeignKey,
		string  $pivotMorphTypeKey,
		string  $pivotMorphForeignKey,
		string  $parentKey,
	)
	{
		$this->pivotTable = $pivotTable;
		$this->pivotForeignKey = $pivotForeignKey;
		$this->pivotMorphTypeKey = $pivotMorphTypeKey;
		$this->pivotMorphForeignKey = $pivotMorphForeignKey;
		$this->parentKey = $parentKey;

		parent::__construct($this->buildPivotQuery($query), $parent);
	}

	/**
	 * Get a new pivot model's query.
	 */
	protected function buildPivotQuery(Builder $query): Builder
	{
		$pivot = new MorphPivot();

		$pivot->setConnection($query->getConnection()->getName());

		$pivot->setTable($this->pivotTable);

		return $pivot->newQuery();
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
		return $this->qualifyPivotColumn($this->pivotForeignKey);
	}

	/**
	 * @see BelongsToMany::qualifyPivotColumn
	 */
	public function qualifyPivotColumn(string $column): string
	{
		return str_contains($column, '.')
			? $column
			: "{$this->pivotTable}.{$column}";
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
