<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @mixin BelongsToAny
 */
trait GetResults
{
	public function getResults(): Collection
	{
		if ($this->parent->getAttribute($this->parentKeyName) === null) {
			return $this->newCollection();
		}

		$pivotModels = $this->query->get();

		$keysByMorphType = $this->getKeysByMorphType($pivotModels);

		$modelsByMorphType = [];

		foreach ($keysByMorphType as $morphType => $keys) {
			$modelsByMorphType[$morphType] = $this->getModelsByMorphType($morphType, $keys)->getDictionary();
		}

		$models = [];

		foreach ($pivotModels as $pivotModel) {
			$models[] = $this->getModelForPivot($pivotModel, $modelsByMorphType);
		}

		return $this->newCollection($models);
	}

	protected function getKeysByMorphType(Collection $pivotModels): array
	{
		$keysByMorphType = [];

		foreach ($pivotModels as $pivotModel) {
			$morphType = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphTypeName));
			$morphKey = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphKeyName));

			if (! $morphType || ! $morphKey) {
				continue;
			}

			$keysByMorphType[$morphType][$morphKey] = true;
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
	protected function getModelsByMorphType(string $morphType, array $keys): Collection
	{
		$instance = $this->newModelByMorphType($morphType);

		$keyName = $instance->getKeyName();

		return $instance->newQuery()
			->{$this->whereInMethod($instance, $keyName)}(
				$instance->qualifyColumn($keyName), $keys
			)
			->get();
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

	protected function getModelForPivot(MorphPivot $pivotModel, array $modelsByMorphType): Model
	{
		$morphType = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphTypeName));
		$morphKey = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphKeyName));

		$model = $modelsByMorphType[$morphType][$morphKey]; // @todo handle missing model...

		$model->setRelation($this->accessor, $pivotModel);

		return $model;
	}

	/**
	 * @todo ability to configure collection class.
	 */
	protected function newCollection(array $models = []): Collection
	{
		return new Collection($models);
	}
}
