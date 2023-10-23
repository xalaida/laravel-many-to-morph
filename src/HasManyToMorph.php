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
		string $table = null,
		string $morphTypeColumn = null,
		string $morphKeyColumn = null,
		string $foreignKeyColumn = null,
		string $parentKeyColumn = null
	): ManyToMorph
	{
		$table = $table ?? Str::plural($morphName);

		$morphTypeColumn = $morphTypeColumn ?? "{$morphName}_type";

		$morphKeyColumn = $morphKeyColumn ?? "{$morphName}_id";

		$foreignKeyColumn = $foreignKeyColumn ?? $this->getForeignKey();

		$parentKeyColumn = $parentKeyColumn ?? $this->getKeyName();

		return new ManyToMorph(
			$this->newQuery(),
			$this,
			$table,
			$foreignKeyColumn,
			$morphTypeColumn,
			$morphKeyColumn,
			$parentKeyColumn,
		);
	}
}
