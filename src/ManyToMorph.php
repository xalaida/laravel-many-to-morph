<?php

namespace Nevadskiy\ManyToMorph;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ManyToMorph extends Relation
{
	use MorphableConstraints;

	protected Model $pivot;

	protected string $foreignKeyColumn;

	protected string $morphTypeColumn;

	protected string $morphKeyColumn;

	protected string $parentKeyColumn;

	protected string $pivotAccessor = 'pivot';

	protected $collection = Collection::class;

	public function __construct(
		Model $parent,
		Model $pivot,
		string $foreignKeyColumn,
		string $morphTypeColumn,
		string $morphKeyColumn,
		string $parentKeyColumn
	) {
		$this->pivot = $pivot;
		$this->foreignKeyColumn = $foreignKeyColumn;
		$this->morphTypeColumn = $morphTypeColumn;
		$this->morphKeyColumn = $morphKeyColumn;
		$this->parentKeyColumn = $parentKeyColumn;

		parent::__construct($this->pivot->newQuery(), $parent);
	}

	public function as(string $pivotAccessor): ManyToMorph
	{
		$this->pivotAccessor = $pivotAccessor;

		return $this;
	}

	public function withTimestamps(): ManyToMorph
	{
		$this->pivot->timestamps = true;

		return $this;
	}

	public function collectUsing($collection): ManyToMorph
	{
		$this->collection = $collection;

		return $this;
	}

	protected function newCollection(array $models = []): Collection
	{
		if (is_callable($this->collection)) {
			return call_user_func($this->collection, $models);
		}

		return new $this->collection($models);
	}

	public function addConstraints(): void
	{
		if (static::$constraints) {
			$this->addWhereConstraints();
		}
	}

	protected function addWhereConstraints(): void
	{
		$this->query->where([
			$this->pivot->qualifyColumn($this->foreignKeyColumn) => $this->parent->getAttribute($this->parentKeyColumn)
		]);
	}

	public function getResults(): Collection
	{
		if ($this->parent->getAttribute($this->parentKeyColumn) === null) {
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
			$morphType = $pivotModel->getAttribute($this->morphTypeColumn);
			$morphKey = $pivotModel->getAttribute($this->morphKeyColumn);

			$keyMap[$morphType][$morphKey] = true;
		}

		$keysByMorphType = [];

		foreach ($keyMap as $morphType => $keys) {
			$keysByMorphType[$morphType] = array_keys($keys);
		}

		return $keysByMorphType;
	}

	protected function getModelsByMorphType(string $morphType, array $keys): Collection
	{
		$instance = $this->newModelByMorphType($morphType);

		$query = $instance->newQuery();

		$this->applyMorphableConstraints($query, get_class($instance));

		$keyName = $instance->getKeyName();

		return $query->{$this->whereInMethod($instance, $keyName)}($instance->qualifyColumn($keyName), $keys)->get();
	}

	public function newModelByMorphType(string $morphType)
	{
		$class = Model::getActualClassNameForMorph($morphType);

		return tap(new $class, function ($instance) {
			if (! $instance->getConnectionName()) {
				$instance->setConnection($this->getConnection()->getName());
			}
		});
	}

	protected function mapModelForPivot(Model $pivotModel, array $modelsByMorphType): Model
	{
		$morphType = $pivotModel->getAttribute($this->morphTypeColumn);
		$morphKey = $pivotModel->getAttribute($this->morphKeyColumn);

		$model = $modelsByMorphType[$morphType][$morphKey];

		$model->setRelation($this->pivotAccessor, $pivotModel);

		return $model;
	}

	public function addEagerConstraints(array $models): void
	{
		$whereInMethod = $this->whereInMethod($this->parent, $this->parentKeyColumn);

		$this->query->{$whereInMethod}(
			$this->pivot->qualifyColumn($this->foreignKeyColumn),
			$this->getKeys($models, $this->parentKeyColumn)
		);
	}

	public function initRelation(array $models, $relation): array
	{
		foreach ($models as $model) {
			$model->setRelation($relation, $this->newCollection());
		}

		return $models;
	}

	public function match(array $models, Collection $results, $relation): array
	{
		$dictionary = $this->getDictionaryForResults($results);

		foreach ($models as $model) {
			$dictionaryKey = $model->getAttribute($this->parentKeyColumn);

			if (isset($dictionary[$dictionaryKey])) {
				$model->setRelation(
					$relation, $this->newCollection($dictionary[$dictionaryKey])
				);
			}
		}

		return $models;
	}

	protected function getDictionaryForResults(Collection $results): array
	{
		$dictionary = [];

		foreach ($results as $result) {
			$dictionaryKey = $result->getAttribute($this->pivotAccessor)->getAttribute($this->foreignKeyColumn);

			$dictionary[$dictionaryKey][] = $result;
		}

		return $dictionary;
	}

	public function attach(Model $model, array $pivot = []): void
	{
		$this->pivot->newQuery()
			->toBase()
			->insert($this->addTimestamps(array_merge([
				$this->foreignKeyColumn => $this->getParent()->getAttribute($this->parentKeyColumn),
				$this->morphTypeColumn => $model->getMorphClass(),
				$this->morphKeyColumn => $model->getKey(),
			], $pivot)));
	}

	public function updateExistingPivot(Model $model, array $pivot = []): void
	{
		$this->pivot->newQuery()
			->where([
				$this->pivot->qualifyColumn($this->foreignKeyColumn) => $this->getParent()->getAttribute($this->parentKeyColumn),
				$this->pivot->qualifyColumn($this->morphTypeColumn) => $model->getMorphClass(),
				$this->pivot->qualifyColumn($this->morphKeyColumn) => $model->getKey(),
			])
			->update($pivot);
	}

	public function detach(Model $model): void
	{
		$this->pivot->newQuery()
			->where([
				$this->pivot->qualifyColumn($this->foreignKeyColumn) => $this->getParent()->getAttribute($this->parentKeyColumn),
				$this->pivot->qualifyColumn($this->morphTypeColumn) => $model->getMorphClass(),
				$this->pivot->qualifyColumn($this->morphKeyColumn) => $model->getKey(),
			])
			->delete();
	}

	protected function addTimestamps(array $values = []): array
	{
		if (! $this->pivot->usesTimestamps()) {
			return $values;
		}

		$timestamp = $this->pivot->freshTimestampString();

		if (! is_null($this->pivot->getUpdatedAtColumn())) {
			$values = array_merge([$this->pivot->getUpdatedAtColumn() => $timestamp], $values);
		}

		if (! is_null($this->pivot->getCreatedAtColumn())) {
			$values = array_merge([$this->pivot->getCreatedAtColumn() => $timestamp], $values);
		}

		return $values;
	}
}
