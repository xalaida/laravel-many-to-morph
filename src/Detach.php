<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin ManyToMorph
 */
trait Detach
{
	/**
	 * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany::detach()
	 */
	public function detach(Model $model): void
	{
		$this->newMorphPivotQuery()
			->where([
				$this->qualifyPivotColumn($this->pivotForeignKeyName) => $this->getParent()->getAttribute($this->parentKeyName),
			])
			->where([
				$this->qualifyPivotColumn($this->pivotMorphTypeName) => $model->getMorphClass(),
				$this->qualifyPivotColumn($this->pivotMorphKeyName) => $model->getKey(),
			])
			->delete();
	}
}
