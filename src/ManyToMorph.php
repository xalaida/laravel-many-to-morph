<?php

namespace Nevadskiy\ManyToMorph;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Relation;

class ManyToMorph extends Relation
{
	use MorphableConstraints;

	/**
	 * @todo remove "pivot" prefix
	 */
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
		Model $parent,
		string $pivotTable,
		string $pivotForeignKeyName,
		string $pivotMorphTypeName,
		string $pivotMorphKeyName,
		string $parentKeyName
	) {
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
	 * @see BelongsToMany::addConstraints()
	 */
	public function addConstraints(): void
	{
		if (static::$constraints) {
			$this->addWhereConstraints();
		}
	}

	/**
	 * @see BelongsToMany::addWhereConstraints()
	 */
	protected function addWhereConstraints(): void
	{
		$this->query->where([
			$this->qualifyPivotColumn($this->pivotForeignKeyName) => $this->parent->getAttribute($this->parentKeyName)
		]);
	}

	/**
	 * @see BelongsToMany::getResults()
	 */
	public function getResults(): Collection
	{
		if ($this->parent->getAttribute($this->parentKeyName) === null) {
			return $this->newCollection();
		}

		return $this->get();
	}

	public function get($columns = ['*']): Collection
	{
		$pivotModels = $this->query->get();

		$keysByMorphType = $this->getKeysByMorphType($pivotModels);

		$modelsByMorphType = [];

		foreach ($keysByMorphType as $morphType => $keys) {
			$modelsByMorphType[$morphType] = $this->getModelsByMorphType($morphType, $keys)->getDictionary();
		}

		$models = [];

		foreach ($pivotModels as $pivotModel) {
			$models[] = $this->mapModelForPivot($pivotModel, $modelsByMorphType);
		}

		return $this->newCollection($models);
	}

	protected function getKeysByMorphType(Collection $pivotModels): array
	{
		$keyMap = [];

		foreach ($pivotModels as $pivotModel) {
			$morphType = $pivotModel->getAttribute($this->pivotMorphTypeName);
			$morphKey = $pivotModel->getAttribute($this->pivotMorphKeyName);

			$keyMap[$morphType][$morphKey] = true;
		}

		$keysByMorphType = [];

		foreach ($keyMap as $morphType => $keys) {
			$keysByMorphType[$morphType] = array_keys($keys);
		}

		return $keysByMorphType;
	}

	/**
	 * @see MorphTo::getResultsByType
	 */
	protected function getModelsByMorphType(string $morphType, array $keys): Collection
	{
		$instance = $this->newModelByMorphType($morphType);

		$query = $instance->newQuery();

		$this->applyMorphableConstraints($query, get_class($instance));

		$keyName = $instance->getKeyName();

		return $query->{$this->whereInMethod($instance, $keyName)}($instance->qualifyColumn($keyName), $keys)->get();
	}

	/**
	 * @see MorphTo::createModelByType
	 */
	public function newModelByMorphType(string $morphType)
	{
		$class = Model::getActualClassNameForMorph($morphType);

		return tap(new $class, function ($instance) {
			if (! $instance->getConnectionName()) {
				$instance->setConnection($this->getConnection()->getName());
			}
		});
	}

	protected function mapModelForPivot(MorphPivot $pivotModel, array $modelsByMorphType): Model
	{
		$morphType = $pivotModel->getAttribute($this->pivotMorphTypeName);
		$morphKey = $pivotModel->getAttribute($this->pivotMorphKeyName);

		$model = $modelsByMorphType[$morphType][$morphKey];

		$model->setRelation($this->accessor, $pivotModel);

		return $model;
	}

	/**
	 * @see BelongsToMany::addEagerConstraints()
	 */
	public function addEagerConstraints(array $models): void
	{
		$whereInMethod = $this->whereInMethod($this->parent, $this->parentKeyName);

		$this->query->{$whereInMethod}(
			$this->qualifyPivotColumn($this->pivotForeignKeyName),
			$this->getKeys($models, $this->parentKeyName)
		);
	}

	/**
	 * @see BelongsToMany::initRelation()
	 */
	public function initRelation(array $models, $relation): array
	{
		foreach ($models as $model) {
			$model->setRelation($relation, $this->newCollection());
		}

		return $models;
	}

	/**
	 * @see BelongsToMany::match()
	 */
	public function match(array $models, Collection $results, $relation): array
	{
		$dictionary = $this->buildDictionary($results);

		foreach ($models as $model) {
			$dictionaryKey = $model->getAttribute($this->parentKeyName);

			if (isset($dictionary[$dictionaryKey])) {
				$model->setRelation(
					$relation, $this->newCollection($dictionary[$dictionaryKey])
				);
			}
		}

		return $models;
	}

	/**
	 * @see BelongsToMany::buildDictionary()
	 */
	protected function buildDictionary(Collection $results): array
	{
		$dictionary = [];

		foreach ($results as $result) {
			$dictionaryKey = $result->getAttribute($this->accessor)->getAttribute($this->pivotForeignKeyName);

			$dictionary[$dictionaryKey][] = $result;
		}

		return $dictionary;
	}

	/**
	 * @see BelongsToMany::getEager()
	 */
	public function getEager(): Collection
	{
		return $this->get();
	}

	/**
	 * @see BelongsToMany::qualifyPivotColumn()
	 */
	public function qualifyPivotColumn(string $column): string
	{
		return "{$this->pivotTable}.{$column}";
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
