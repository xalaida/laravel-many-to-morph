<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin BelongsToAny
 */
trait Attach
{
	/**
	 * @todo add extra pivot attributes
	 */
	public function attach(Model $model): void
	{
		$this->getQuery()->insert([
			$this->pivotForeignKeyName => $this->getParent()->getAttribute($this->parentKeyName),
			$this->pivotMorphTypeName => $model->getMorphClass(),
			$this->pivotMorphForeignKeyName => $model->getKey(),
		]);
	}
}
