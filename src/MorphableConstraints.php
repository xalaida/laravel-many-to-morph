<?php

namespace Nevadskiy\ManyToMorph;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin ManyToMorph
 */
trait MorphableConstraints
{
	protected array $morphableEagerLoads = [];

	protected array $morphableEagerLoadCounts = [];

	protected array $morphableConstraints = [];

	public function morphWith(array $with): self
	{
		$this->morphableEagerLoads = array_merge(
			$this->morphableEagerLoads,
			$with
		);

		return $this;
	}

	public function morphWithCount(array $withCount): self
	{
		$this->morphableEagerLoadCounts = array_merge(
			$this->morphableEagerLoadCounts,
			$withCount
		);

		return $this;
	}

	public function constrain(array $callbacks): self
	{
		$this->morphableConstraints = array_merge(
			$this->morphableConstraints,
			$callbacks
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
