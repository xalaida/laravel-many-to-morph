<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Database\Eloquent\Collection;

/**
 * @mixin BelongsToAny
 */
trait EagerLoading
{
	protected array $morphableEagerLoads = [];

	protected array $morphableEagerLoadCounts = [];

	protected array $morphableConstraints = [];

	public function morphWith(array $with): static
	{
		$this->morphableEagerLoads = array_merge(
			$this->morphableEagerLoads, $with
		);

		return $this;
	}

	public function morphWithCount(array $withCount): static
	{
		$this->morphableEagerLoadCounts = array_merge(
			$this->morphableEagerLoadCounts, $withCount
		);

		return $this;
	}

	public function constrain(array $callbacks): static
	{
		$this->morphableConstraints = array_merge(
			$this->morphableConstraints, $callbacks
		);

		return $this;
	}

	/**
	 * @see BelongsToMany::addEagerConstraints()
	 */
	public function addEagerConstraints(array $models): void
	{
		$whereIn = $this->whereInMethod($this->parent, $this->parentKeyName);

		$this->whereInEager(
			$whereIn,
			$this->getQualifiedForeignPivotKeyName(),
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
			$dictionaryKey = $this->getDictionaryKey($model->getAttribute($this->parentKeyName));

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
			$dictionaryKey = $this->getDictionaryKey(
				$result->getAttribute($this->accessor)->getAttribute($this->pivotForeignKeyName)
			);

			$dictionary[$dictionaryKey][] = $result;
		}

		return $dictionary;
	}

	/**
	 * @see BelongsToMany::getEager()
	 */
	public function getEager(): Collection
	{
		return $this->eagerKeysWereEmpty
			? $this->newCollection()
			: $this->get();
	}
}
