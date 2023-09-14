<?php

namespace Nevadskiy\MorphAny;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @mixin MorphAny
 */
trait GetResults
{
	protected array $morphDictionaries = [];

	public function getResults(): Collection
	{
		if ($this->parent->getAttribute($this->parentKeyName) === null) {
			return $this->newCollection();
		}

		$pivotModels = $this->query->get();

		$this->buildMorphDictionaries($pivotModels);

		$results = [];

		foreach ($pivotModels as $pivotModel) {
			$results[] = $this->getResultForPivot($pivotModel);
		}

		return $this->newCollection($results);
	}

	protected function buildMorphDictionaries(Collection $pivotModels): void
	{
		$keysByMorphType = $this->gatherKeysByMorphType($pivotModels);

		foreach ($keysByMorphType as $morphType => $morphKeys) {
			$this->morphDictionaries[$morphType] = $this->getResultsByMorphType($morphType, $morphKeys)->getDictionary();
		}
	}

	/**
	 * @see MorphTo::buildDictionary
	 */
	protected function gatherKeysByMorphType(Collection $pivotModels): array
	{
		$keysByMorphType = [];

		foreach ($pivotModels as $pivotModel) {
			if ($pivotModel->getAttribute($this->pivotMorphTypeName)) {
				$morphTypeKey = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphTypeName));
				$morphForeignKey = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphForeignKeyName));

				$keysByMorphType[$morphTypeKey][$morphForeignKey] = true;
			}
		}

		foreach ($keysByMorphType as $morphType => $morphKeys) {
			$keysByMorphType[$morphType] = array_unique($morphKeys);
		}

		return $keysByMorphType;
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

	protected function getResultForPivot(MorphPivot $pivotModel): mixed
	{
		$morphType = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphTypeName));
		$morphForeignKey = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphForeignKeyName));

		$result = $this->morphDictionaries[$morphType][$morphForeignKey]; // @todo handle missing model...

		// @todo hydrate pivot at the beginning of the method
		$this->hydratePivotModel($result, $pivotModel);

		return $result;
	}
}
