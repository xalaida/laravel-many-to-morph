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

	protected function newMorphAny($pivotForeignKey = null, $parentKey = null): MorphAny
	{
		return new MorphAny(
			query: $this->newQuery(),
			parent: $this,
			pivotTable: 'page_sections',
			pivotForeignKey: $pivotForeignKey ?? $this->getForeignKey(),
			pivotMorphTypeKey: 'page_section_type',
			pivotMorphForeignKey: 'page_section_id',
			parentKey: $parentKey ?? $this->getKeyName(),
		);
	}
}
