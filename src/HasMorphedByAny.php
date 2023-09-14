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

	protected function newMorphAny(): MorphAny
	{
		return new MorphAny(
			query: $this->newQuery(),
			parent: $this,
			pivotTable: 'page_sections',
			pivotForeignKeyColumn: $this->getForeignKey(),
			pivotMorphTypeColumn: 'page_section_type',
			pivotMorphForeignKeyColumn: 'page_section_id',
			parentKeyColumn: $this->getKeyName(),
		);
	}
}
