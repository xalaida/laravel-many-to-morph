<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Support\Str;

trait HasBelongsToAny
{
	protected function belongsToAny(
		string $morphName,
		string $pivotTable = null,
		string $pivotMorphTypeName = null,
		string $pivotMorphForeignKeyName = null,
		string $pivotForeignKeyName = null,
		string $parentKeyName = null,
	): BelongsToAny
	{
		$pivotTable = $pivotTable ?? Str::plural($morphName);

		$pivotMorphTypeName = $pivotMorphTypeName ?? "{$morphName}_type";

		$pivotMorphForeignKeyName = $pivotMorphForeignKeyName ?? "{$morphName}_id";

		$pivotForeignKeyName = $pivotForeignKeyName ?? $this->getForeignKey();

		$parentKeyName = $parentKeyName ?? $this->getKeyName();

		return $this->newMorphAny($pivotTable, $pivotMorphTypeName, $pivotMorphForeignKeyName, $pivotForeignKeyName, $parentKeyName);
	}

	protected function newMorphAny(
		string $pivotTable,
		string $pivotMorphTypeName,
		string $pivotMorphForeignKeyName,
		string $pivotForeignKeyName,
		string $parentKeyName
	): BelongsToAny
	{
		return new BelongsToAny(
			query: $this->newQuery(),
			parent: $this,
			pivotTable: $pivotTable,
			pivotForeignKeyName: $pivotForeignKeyName,
			pivotMorphTypeName: $pivotMorphTypeName,
			pivotMorphForeignKeyName: $pivotMorphForeignKeyName,
			parentKeyName: $parentKeyName,
		);
	}
}
