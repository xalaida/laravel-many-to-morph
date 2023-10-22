<?php

namespace Nevadskiy\ManyToMorph;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Relation;

class ManyToMorph extends Relation
{
	use InteractsWithDictionary;
	use MorphableConstraints;
	use LazyLoading;
	use EagerLoading;

	protected $pivotConnection;
	protected $pivotTable;
	protected $pivotForeignKeyName;
	protected $pivotMorphTypeName;
	protected $pivotMorphKeyName;
	protected $parentKeyName;

	/**
	 * @see BelongsToMany::$accessor
	 */
	protected $accessor = 'pivot';

	protected $using;

	public function __construct(
		Builder $query,
		Model   $parent,
		string  $pivotTable,
		string  $pivotForeignKeyName,
		string  $pivotMorphTypeName,
		string  $pivotMorphKeyName,
		string  $parentKeyName,
	)
	{
		$this->pivotConnection = $query->getConnection()->getName();
		$this->pivotTable = $pivotTable;
		$this->pivotForeignKeyName = $pivotForeignKeyName;
		$this->pivotMorphTypeName = $pivotMorphTypeName;
		$this->pivotMorphKeyName = $pivotMorphKeyName;
		$this->parentKeyName = $parentKeyName;

		parent::__construct($this->newMorphPivotQuery(), $parent);
	}

	/**
	 * @todo make pivot in constructor
	 */
	protected function newMorphPivotQuery(): Builder
	{
		return $this->newMorphPivot()->newQuery();
	}

	/**
	 * @todo ability to configure morph pivot class.
	 */
	protected function newMorphPivot(): MorphPivot
	{
		$pivot = new MorphPivot();

		$pivot->setConnection($this->pivotConnection);

		$pivot->setTable($this->pivotTable);

		$pivot->timestamps = false;

		return $pivot;
	}

	/**
	 * @see BelongsToMany::qualifyPivotColumn()
	 */
	public function qualifyPivotColumn(string $column): string
	{
		return str_contains($column, '.')
			? $column
			: "{$this->pivotTable}.{$column}";
	}

	/**
	 * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::attach()
	 */
	public function attach(Model $model, array $pivot = []): void
	{
		$this->newMorphPivotQuery()
			->insert(array_merge([
				$this->pivotForeignKeyName => $this->getParent()->getAttribute($this->parentKeyName),
				$this->pivotMorphTypeName => $model->getMorphClass(),
				$this->pivotMorphKeyName => $model->getKey(),
			], $pivot));
	}

	/**
	 * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::updateExistingPivot()
	 */
	public function updateExistingPivot(Model $model, array $pivot): void
	{
		$this->newMorphPivotQuery()
			->where([
				$this->qualifyColumn($this->pivotForeignKeyName) => $this->getParent()->getAttribute($this->parentKeyName),
				$this->qualifyColumn($this->pivotMorphTypeName) => $model->getMorphClass(),
				$this->qualifyColumn($this->pivotMorphKeyName) => $model->getKey(),
			])
			->update($pivot);
	}

	/**
	 * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::detach()
	 */
	public function detach(Model $model): void
	{
		$this->newMorphPivotQuery()
			->where([
				$this->qualifyPivotColumn($this->pivotForeignKeyName) => $this->getParent()->getAttribute($this->parentKeyName),
				$this->qualifyPivotColumn($this->pivotMorphTypeName) => $model->getMorphClass(),
				$this->qualifyPivotColumn($this->pivotMorphKeyName) => $model->getKey(),
			])
			->delete();
	}

	/**
	 * @todo ability to configure collection class.
	 */
	protected function newCollection(array $models = []): Collection
	{
		return new Collection($models);
	}
}
