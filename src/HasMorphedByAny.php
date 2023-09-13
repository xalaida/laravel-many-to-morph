<?php

namespace Nevadskiy\MorphAny;

trait HasMorphedByAny
{
    protected function morphedByAny(): MorphAny
    {
        return $this->newMorphAny();
    }

    protected function newMorphAny(): MorphAny
    {
        return new MorphAny(
            query: $this->newQuery(),
            parent: $this,
            pivotTable: 'page_sections',
            foreignPivotKey: $foreignPivotKey ?: $this->getForeignKey(),
            parentKey: $parentKey ?: $this->getKeyName(),
            morphTypeKey: 'page_section_type',
            morphForeignKey: 'page_section_id',
        );
    }
}
