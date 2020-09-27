<?php

namespace Owlcoder\OwlOrm\Orm;

use Owlcoder\OwlOrm\Schema\Database;

class Query implements IQuery
{
    /**
     * @var Database
     */
    public $connection;

    protected $select = ['*'];
    protected $from = '';

    public $limit = null;
    public $joins = [];
    public $conditions = [];
    public $order = [];

    public $params = [];

    public function getFrom()
    {
        return $this->from;
    }

    public function getConditions()
    {
        return $this->conditions;
    }

    public function getSelect()
    {
        return $this->select;
    }

    public function select($select)
    {
        if (is_string($select)) {
            $this->select = array_map('trim', explode(',', $select));
        } else {
            $this->select = $select;
        }

        return $this;
    }

    public function from($from)
    {
        $this->from = $from;

        return $this;
    }

    public function where($condition)
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function toSql()
    {
        $builderClass = $this->connection->getBuilderClass();
        $builder = new $builderClass($this);
        $sql = $builder->build();
        $this->params = $builder->params;

        return $sql;
    }

    public function orderBy($condition)
    {
        if (is_string($condition)) {
            $this->order[] = $condition;
        } else {
            $this->order = $condition;
        }

        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function all()
    {
        return $this->connection->all($this->toSql(), $this->params);
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function first()
    {
        return $this->connection->first($this->toSql(), $this->params);
    }

    public function scalar()
    {
        return $this->connection->scalar($this->toSql(), $this->params);
    }

    public function count()
    {
        $q = clone $this;
        return $q->select('count(*)')->scalar();
    }
}