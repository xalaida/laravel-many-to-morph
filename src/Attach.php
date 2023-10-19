<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin BelongsToAny
 */
trait Attach
{
	/**
	 * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::attach()
	 */
	public function attach(Model $model, array $pivot = []): void
	{
		$this->getQuery()->insert(array_merge([
			$this->pivotForeignKeyName => $this->getParent()->getAttribute($this->parentKeyName),
			$this->pivotMorphTypeName => $model->getMorphClass(),
			$this->pivotMorphKeyName => $model->getKey(),
		], $pivot));
	}
}
