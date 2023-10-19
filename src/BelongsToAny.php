<?php

namespace Nevadskiy\ManyToAny;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Relation;

class BelongsToAny extends Relation
{
	use InteractsWithDictionary;
	use LazyLoading;
	use EagerLoading;
	use Attach;

	protected $pivotTable;
	protected $pivotForeignKeyName;
	protected $pivotMorphTypeName;
	protected $pivotMorphKeyName;
	protected $parentKeyName;

	/**
	 * @see BelongsToMany::$accessor
	 */
	protected $accessor = 'pivot';

	protected $using;

	public function __construct(
		Builder $query,
		Model   $parent,
		string  $pivotTable,
		string  $pivotForeignKeyName,
		string  $pivotMorphTypeName,
		string  $pivotMorphKeyName,
		string  $parentKeyName,
	)
	{
		$this->pivotTable = $pivotTable;
		$this->pivotForeignKeyName = $pivotForeignKeyName;
		$this->pivotMorphTypeName = $pivotMorphTypeName;
		$this->pivotMorphKeyName = $pivotMorphKeyName;
		$this->parentKeyName = $parentKeyName;

		parent::__construct($this->buildPivotQuery($query), $parent);
	}

	protected function buildPivotQuery(Builder $query): Builder
	{
		$pivot = new MorphPivot();

		$pivot->setConnection($query->getConnection()->getName());

		$pivot->setTable($this->pivotTable);

		return $pivot->newQuery();
	}

	/**
	 * @todo ability to configure collection class.
	 */
	protected function newCollection(array $models = []): Collection
	{
		return new Collection($models);
	}
}
