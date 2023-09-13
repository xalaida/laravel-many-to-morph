<?php

namespace Nevadskiy\MorphAny;

use Illuminate\Database\Eloquent\Model;

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
			query: $this->newQuery(),
			parent: $this,
			table: 'page_sections',
			foreignPivotKey: $foreignPivotKey ?? $this->getForeignKey(),
			parentKey: $parentKey ?? $this->getKeyName(),
			morphTypeKey: 'page_section_type',
			morphForeignKey: 'page_section_id',
		);
	}
}
