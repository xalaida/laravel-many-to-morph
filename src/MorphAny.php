<?php

namespace Nevadskiy\MorphAny;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithPivotTable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * 1st query: join pivot.
 * Next in loop query for each pivot morph type.
 *
 * SELECT * FROM pages
 * JOIN page_sections ON pages.id = page_sections.page_id
 */
class MorphAny extends Relation
{
    use InteractsWithDictionary;
    use InteractsWithPivotTable;

    /**
     * @example "page_sections"
     */
    protected $pivotTable;

    /**
     * @example page_sections.page_id
     *
     * @todo rename to "foreignPivotKey"
     */
    protected $foreignPivotKey;

    /**
     * @example pages.id
     *
     * @todo rename to "parentKey".
     */
    protected $parentKey;

    /**
     * @example page_sections.page_section_type
     */
    protected $morphTypeKey;

    /**
     * @example page_sections.page_section_id
     */
    protected $morphForeignKey;

    protected $dictionary = [];

    /**
     * @see BelongsToMany::$accessor
     */
    protected $accessor = 'pivot';

    /**
     * The class name of the custom pivot model to use for the relationship.
     *
     * @var string
     */
    protected $using;

    /**
     * Make a new relation instance.
     */
    public function __construct(
        Builder $query,
        Model $parent,
                $pivotTable,
                $foreignPivotKey,
                $parentKey,
                $morphTypeKey,
                $morphForeignKey,
    ) {
        $this->pivotTable = $pivotTable;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->parentKey = $parentKey;

        $this->morphTypeKey = $morphTypeKey;
        $this->morphForeignKey = $morphForeignKey;

        // @todo start new pivot query here...

        parent::__construct($query, $parent);
    }

    /**
     * @see BelongsToMany::addConstraints
     */
    public function addConstraints(): void
    {
        // @todo use no join, just plain pivot query...

        // $this->performJoin();

        if (static::$constraints) {
            $this->addWhereConstraints();
        }
    }

    protected function performJoin($query = null): static
    {
        $query = $query ?: $this->query;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
        $query->join(
            $this->pivotTable,
            $this->getQualifiedParentKeyName(),
            '=',
            $this->getQualifiedForeignPivotKeyName()
        );

        return $this;
    }

    /**
     * @see BelongsToMany::addWhereConstraints
     */
    protected function addWhereConstraints()
    {
        $this->query->where(
            $this->getQualifiedForeignPivotKeyName(), '=', $this->parent->{$this->parentKey}
        );

        return $this;
    }

    /**
     * @see BelongsToMany::getQualifiedForeignPivotKeyName
     */
    public function getQualifiedForeignPivotKeyName()
    {
        return $this->qualifyPivotColumn($this->foreignPivotKey);
    }

    /**
     * @see BelongsToMany::qualifyPivotColumn
     */
    public function qualifyPivotColumn($column)
    {
        return str_contains($column, '.')
            ? $column
            : $this->pivotTable.'.'.$column;
    }

    public function addEagerConstraints(array $models)
    {
        // TODO: Implement addEagerConstraints() method.
    }

    public function initRelation(array $models, $relation)
    {
        // TODO: Implement initRelation() method.
    }

    public function match(array $models, Collection $results, $relation)
    {
        // TODO: Implement match() method.
    }

    /**
     * @see BelongsToMany::get
     */
    public function getResults()
    {
        // @todo probably use pivot model query here...
        $pivotResults = $this->query->get();

        // @todo no need to build this dictionary...
        $this->buildDictionary($pivotResults);

        $morphsDictionary = $this->getMorphResults();

        $models = [];

        foreach ($pivotResults as $pivotResult) {
            // find record
            $morphTypeKey = $this->getDictionaryKey($pivotResult->{$this->morphTypeKey});
            $foreignKeyKey = $this->getDictionaryKey($pivotResult->{$this->morphForeignKey});
            $model = $morphsDictionary[$morphTypeKey][$foreignKeyKey]; // @todo handle missing model...

            $this->hydratePivotModel($model, $pivotResult);

            $models[] = $model;
        }

        return new Collection($models);
    }

    /**
     * @see MorphTo::buildDictionary
     */
    protected function buildDictionary(Collection $pivotResults): void
    {
        foreach ($pivotResults as $pivotResult) {
            if ($pivotResult->{$this->morphTypeKey}) {
                $morphTypeKey = $this->getDictionaryKey($pivotResult->{$this->morphTypeKey});
                $foreignKeyKey = $this->getDictionaryKey($pivotResult->{$this->morphForeignKey});

                $this->dictionary[$morphTypeKey][$foreignKeyKey][] = $pivotResult;
            }
        }
    }

    /**
     * @see MorphTo::getEager
     */
    protected function getMorphResults(): array
    {
        $morphs = [];

        foreach (array_keys($this->dictionary) as $type) {
            $morphs[$type] = $this->getResultsByType($type)->getDictionary();
        }

        return $morphs;
    }

    /**
     * @see MorphTo::getResultsByType
     */
    protected function getResultsByType($type)
    {
        /** @var Model $instance */
        $instance = $this->createModelByType($type);

        $ownerKey = $this->ownerKey ?? $instance->getKeyName();

        $query = $instance->newQuery()
            // ->mergeConstraintsFrom($this->getQuery())
            // @todo add hook to modify relation.
            ->with(array_merge(
                $this->getQuery()->getEagerLoads(),
                (array) ($this->morphableEagerLoads[get_class($instance)] ?? [])
            ))
            ->withCount(
                (array) ($this->morphableEagerLoadCounts[get_class($instance)] ?? [])
            );

        if ($callback = ($this->morphableConstraints[get_class($instance)] ?? null)) {
            $callback($query);
        }

        $whereIn = $this->whereInMethod($instance, $ownerKey);

        return $query->{$whereIn}(
            $instance->getTable().'.'.$ownerKey, $this->gatherKeysByType($type, $instance->getKeyType())
        )->get();
    }

    /**
     * @see MorphTo::createModelByType
     */
    public function createModelByType($type)
    {
        $class = Model::getActualClassNameForMorph($type);

        return tap(new $class, function ($instance) {
            if (! $instance->getConnectionName()) {
                $instance->setConnection($this->getConnection()->getName());
            }
        });
    }

    /**
     * @see MorphTo::createModelByType
     */
    protected function gatherKeysByType($type, $keyType): array
    {
        return $keyType !== 'string'
            ? array_keys($this->dictionary[$type])
            : array_map(function ($modelId) {
                return (string) $modelId;
            }, array_filter(array_keys($this->dictionary[$type])));
    }

    protected function hydratePivotModel($model, $pivotResult)
    {
        $model->setRelation($this->accessor, $this->parent->newPivot(
            $this->parent, $pivotResult->getAttributes(), $this->pivotTable, true, $this->using
        ));
    }
}
