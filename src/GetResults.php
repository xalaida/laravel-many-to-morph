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

	protected function gatherKeysByMorphType(Collection $pivotModels): array
	{
		$keysByMorphType = [];

		foreach ($pivotModels as $pivotModel) {
			$morphType = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphTypeName));
			$morphKeys = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphForeignKeyName));

			if (! $morphType || ! $morphKeys) {
				continue;
			}

			$keysByMorphType[$morphType][$morphKeys] = true;
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
		$instance = $this->createModelByMorphType($morphType);

		$keyName = $instance->getKeyName();

		return $instance->newQuery()
			->{$this->whereInMethod($instance, $keyName)}(
				$instance->qualifyColumn($keyName), $morphKeys
			)
			->get();
	}

	/**
	 * @see MorphTo::createModelByType
	 */
	public function createModelByMorphType(string $morphType)
	{
		$class = Model::getActualClassNameForMorph($morphType);

		return tap(new $class, function ($instance) {
			if (! $instance->getConnectionName()) {
				$instance->setConnection($this->getConnection()->getName());
			}
		});
	}

	protected function getResultForPivot(MorphPivot $pivotModel): mixed
	{
		$morphType = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphTypeName));
		$morphKeys = $this->getDictionaryKey($pivotModel->getAttribute($this->pivotMorphForeignKeyName));

		$result = $this->morphDictionaries[$morphType][$morphKeys]; // @todo handle missing model...

		$result->setRelation($this->accessor, $pivotModel);

		return $result;
	}

	/**
	 * @todo ability to configure collection class.
	 */
	protected function newCollection(array $models = []): Collection
	{
		return new Collection($models);
	}
}
