<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin BelongsToAny
 */
trait MorphableConstraints
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

	public function applyMorphableConstraints(Builder $query, string $morphClass): void
	{
		$query->with((array) ($this->morphableEagerLoads[$morphClass] ?? []));

		$query->withCount((array) ($this->morphableEagerLoadCounts[$morphClass] ?? []));

		if ($callback = ($this->morphableConstraints[$morphClass] ?? null)) {
			$callback($query);
		}
	}
}
