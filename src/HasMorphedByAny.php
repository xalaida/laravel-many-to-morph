<?php

namespace Nevadskiy\MorphAny;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @mixin Model
 */
trait HasMorphedByAny
{
	protected function morphedByAny(): MorphAny
	{
		return $this->newMorphAny();
	}

	protected function newMorphAny($foreignPivotKey = null, $parentKey = null): MorphAny
	{
		return new MorphAny(
			query: $this->newMorphAnyQuery(),
			parent: $this,
			table: 'page_sections',
			foreignPivotKey: $foreignPivotKey ?? $this->getForeignKey(),
			parentKey: $parentKey ?? $this->getKeyName(),
			morphTypeKey: 'page_section_type',
			morphForeignKey: 'page_section_id',
		);
	}

	/**
	 * Get a new query builder for the model's pivot.
	 */
	protected function newMorphAnyQuery(): Builder
	{
		$pivot = new MorphPivot();

		$pivot->setConnection($this->newQuery()->getConnection()->getName());

		$pivot->setTable($this->table);

		return $pivot->newQuery();
	}
}
