<?php

namespace Nevadskiy\ManyToMorph;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
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

		return $this->newManyToMorph($table, $morphTypeColumn, $morphKeyColumn, $foreignKeyColumn, $parentKeyColumn);
	}

	protected function newManyToMorph(
		string $table,
		string $morphTypeColumn,
		string $morphKeyColumn,
		string $foreignKeyColumn,
		string $parentKeyColumn
	): ManyToMorph
	{
		$morphPivot = new MorphPivot();

		$morphPivot->setConnection($this->getConnection()->getName());

		$morphPivot->setTable($table);

		$morphPivot->timestamps = false;

		return new ManyToMorph(
			$this,
			$morphPivot,
			$foreignKeyColumn,
			$morphTypeColumn,
			$morphKeyColumn,
			$parentKeyColumn,
		);
	}
}
