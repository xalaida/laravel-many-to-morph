<?php

namespace Nevadskiy\MorphAny;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin MorphAny
 */
trait GetResults
{
	/**
	 * Get the results of the relationship.
	 */
	public function getResults(): Collection
	{
		if ($this->parent->getAttribute($this->parentKeyColumn) === null) {
			return $this->newCollection();
		}

		// @todo hydrate pivot model here...
		$pivotModels = $this->query->get();

		$keysByMorphType = $this->gatherKeysByMorphType($pivotModels);

		$morphDictionaries = [];

		foreach ($keysByMorphType as $morphType => $morphKeys) {
			$morphDictionaries[$morphType] = $this->getResultsByMorphType($morphType, array_keys($morphKeys))->getDictionary();
		}

		$results = [];

		foreach ($pivotModels as $pivotModel) {
			$morphType = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphTypeColumn));
			$morphForeignKey = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphForeignKeyColumn));

			$result = $morphDictionaries[$morphType][$morphForeignKey]; // @todo handle missing model...

			// @todo hydrate pivot at the beginning of the method
			$this->hydratePivotModel($result, $pivotModel);

			$results[] = $result;
		}

		return $this->newCollection($results);
	}

	/**
	 * @see MorphTo::buildDictionary
	 */
	protected function gatherKeysByMorphType(Collection $pivotModels): array
	{
		$morphKeys = [];

		// @todo hydrate keys to Pivot model... Pivot::setMorphs() to not build dictionary key again later.
		foreach ($pivotModels as $pivotModel) {
			if ($pivotModel->getAttribute($this->pivotMorphTypeColumn)) {
				$morphTypeKey = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphTypeColumn));
				$morphForeignKey = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphForeignKeyColumn));

				$morphKeys[$morphTypeKey][$morphForeignKey] = true;
			}
		}

		return $morphKeys;
	}

	/**
	 * @see MorphTo::getResultsByType
	 *
	 * @todo constraints
	 * @todo eager loads
	 * @todo eager load counts
	 */
	protected function getResultsByMorphType(string $morphType, array $morphKeys): Collection
	{
		/** @var Model $instance */
		$instance = $this->createModelByMorphType($morphType);

		// @todo rename relation props to have also keyName suffix.
		$keyName = $instance->getKeyName();

		$whereIn = $this->whereInMethod($instance, $keyName);

		return $instance->newQuery()->{$whereIn}(
			$instance->qualifyColumn($keyName), $morphKeys
		)->get();
	}

	/**
	 * @see MorphTo::createModelByType
	 */
	public function createModelByMorphType(string $morphType)
	{
		$modelClass = Model::getActualClassNameForMorph($morphType);

		return tap(new $modelClass, function ($instance) {
			if (! $instance->getConnectionName()) {
				$instance->setConnection($this->getConnection()->getName());
			}
		});
	}

	protected function hydratePivotModel($model, $pivotModel)
	{
		$model->setRelation($this->accessor, $this->parent->newPivot(
			$this->parent, $pivotModel->getAttributes(), $this->pivotTable, true, $this->using
		));
	}

	/**
	 * @todo ability to configure collection class.
	 */
	protected function newCollection(array $models = []): Collection
	{
		return new Collection($models);
	}
}
