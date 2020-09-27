<?php

namespace Owlcoder\OwlOrm\Orm\Relations;

class HasManyRelation extends Relation
{
    public $parent = null;
    public $foreignKey = null;
    public $className = null;

    public function get()
    {
        $relationModelClassName = $this->className;

        $query = $relationModelClassName::query()->where([
            $this->foreignKey => $this->parent->id
        ]);

        return $query;
    }
}