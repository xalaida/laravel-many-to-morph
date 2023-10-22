<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin ManyToMorph
 */
trait Attach
{
	/**
	 * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::attach()
	 */
	public function attach(Model $model, array $pivot = []): void
	{
		$this->newMorphPivotQuery()
			->insert(array_merge([
				$this->pivotForeignKeyName => $this->getParent()->getAttribute($this->parentKeyName),
				$this->pivotMorphTypeName => $model->getMorphClass(),
				$this->pivotMorphKeyName => $model->getKey(),
			], $pivot));
	}
}
