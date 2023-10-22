<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait HasBelongsToAny
{
	protected function belongsToAny(
		string $morphName,
		string $pivotTable = null,
		string $pivotMorphTypeName = null,
		string $pivotMorphKeyName = null,
		string $pivotForeignKeyName = null,
		string $parentKeyName = null,
	): ManyToAny
	{
		$pivotTable = $pivotTable ?? Str::plural($morphName);

		$pivotMorphTypeName = $pivotMorphTypeName ?? "{$morphName}_type";

		$pivotMorphKeyName = $pivotMorphKeyName ?? "{$morphName}_id";

		$pivotForeignKeyName = $pivotForeignKeyName ?? $this->getForeignKey();

		$parentKeyName = $parentKeyName ?? $this->getKeyName();

		return $this->newBelongsToAny($pivotTable, $pivotMorphTypeName, $pivotMorphKeyName, $pivotForeignKeyName, $parentKeyName);
	}

	protected function newBelongsToAny(
		string $pivotTable,
		string $pivotMorphTypeName,
		string $pivotMorphKeyName,
		string $pivotForeignKeyName,
		string $parentKeyName
	): ManyToAny
	{
		return new ManyToAny(
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
