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

	protected MorphPivot $morphPivot;

	protected string $foreignKeyColumn;

	protected string $morphTypeColumn;

	protected string $morphKeyColumn;

	protected string $parentKeyColumn;

	protected string $accessor = 'pivot';

	protected string $using;

	public function __construct(
		Model $parent,
		MorphPivot $morphPivot,
		string $foreignKeyColumn,
		string $morphTypeColumn,
		string $morphKeyColumn,
		string $parentKeyColumn
	) {
		$this->morphPivot = $morphPivot;
		$this->foreignKeyColumn = $foreignKeyColumn;
		$this->morphTypeColumn = $morphTypeColumn;
		$this->morphKeyColumn = $morphKeyColumn;
		$this->parentKeyColumn = $parentKeyColumn;

		parent::__construct($this->newMorphPivotQuery(), $parent);
	}

	protected function newMorphPivotQuery(): Builder
	{
		return $this->morphPivot->newQuery();
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
			$this->morphPivot->qualifyColumn($this->foreignKeyColumn) => $this->parent->getAttribute($this->parentKeyColumn)
		]);
	}

	/**
	 * @see BelongsToMany::getResults()
	 */
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
		$morphType = $pivotModel->getAttribute($this->morphTypeColumn);
		$morphKey = $pivotModel->getAttribute($this->morphKeyColumn);

		$model = $modelsByMorphType[$morphType][$morphKey];

		$model->setRelation($this->accessor, $pivotModel);

		return $model;
	}

	/**
	 * @see BelongsToMany::addEagerConstraints()
	 */
	public function addEagerConstraints(array $models): void
	{
		$whereInMethod = $this->whereInMethod($this->parent, $this->parentKeyColumn);

		$this->query->{$whereInMethod}(
			$this->morphPivot->qualifyColumn($this->foreignKeyColumn),
			$this->getKeys($models, $this->parentKeyColumn)
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
			$dictionaryKey = $model->getAttribute($this->parentKeyColumn);

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
			$dictionaryKey = $result->getAttribute($this->accessor)->getAttribute($this->foreignKeyColumn);

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
	 * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::attach()
	 */
	public function attach(Model $model, array $pivot = []): void
	{
		$this->newMorphPivotQuery()
			->insert(array_merge([
				$this->foreignKeyColumn => $this->getParent()->getAttribute($this->parentKeyColumn),
				$this->morphTypeColumn => $model->getMorphClass(),
				$this->morphKeyColumn => $model->getKey(),
			], $pivot));
	}

	/**
	 * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::updateExistingPivot()
	 */
	public function updateExistingPivot(Model $model, array $pivot): void
	{
		$this->newMorphPivotQuery()
			->where([
				$this->morphPivot->qualifyColumn($this->foreignKeyColumn) => $this->getParent()->getAttribute($this->parentKeyColumn),
				$this->morphPivot->qualifyColumn($this->morphTypeColumn) => $model->getMorphClass(),
				$this->morphPivot->qualifyColumn($this->morphKeyColumn) => $model->getKey(),
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
				$this->morphPivot->qualifyColumn($this->foreignKeyColumn) => $this->getParent()->getAttribute($this->parentKeyColumn),
				$this->morphPivot->qualifyColumn($this->morphTypeColumn) => $model->getMorphClass(),
				$this->morphPivot->qualifyColumn($this->morphKeyColumn) => $model->getKey(),
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
