<?php

namespace Nevadskiy\ManyToMorph;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait HasManyToMorph
{
	protected function manyToMorph(
		string $morphName,
		string $pivotTable = null,
		string $pivotMorphTypeName = null,
		string $pivotMorphKeyName = null,
		string $pivotForeignKeyName = null,
		string $parentKeyName = null,
	): ManyToMorph
	{
		$pivotTable = $pivotTable ?? Str::plural($morphName);

		$pivotMorphTypeName = $pivotMorphTypeName ?? "{$morphName}_type";

		$pivotMorphKeyName = $pivotMorphKeyName ?? "{$morphName}_id";

		$pivotForeignKeyName = $pivotForeignKeyName ?? $this->getForeignKey();

		$parentKeyName = $parentKeyName ?? $this->getKeyName();

		return $this->newManyToMorph($pivotTable, $pivotMorphTypeName, $pivotMorphKeyName, $pivotForeignKeyName, $parentKeyName);
	}

	protected function newManyToMorph(
		string $pivotTable,
		string $pivotMorphTypeName,
		string $pivotMorphKeyName,
		string $pivotForeignKeyName,
		string $parentKeyName
	): ManyToMorph
	{
		return new ManyToMorph(
			query: $this->newQuery(),
			parent: $this,
			pivotTable: $pivotTable,
			pivotForeignKeyName: $pivotForeignKeyName,
			pivotMorphTypeName: $pivotMorphTypeName,
			pivotMorphKeyName: $pivotMorphKeyName,
			parentKeyName: $parentKeyName,
		);
	}
}
