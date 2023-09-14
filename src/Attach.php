<?php

namespace Nevadskiy\MorphAny;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin MorphAny
 */
trait Attach
{
	public function attach(Model $model): void
	{
		$this->getQuery()->create([
			$this->pivotForeignKeyName => $this->getParent()->getAttribute($this->parentKeyName),
			$this->pivotMorphTypeName => $model->getMorphClass(),
			$this->pivotMorphForeignKeyName => $model->getKey(),
		]);
	}
}
