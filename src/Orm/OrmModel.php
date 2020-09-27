<?php

namespace Owlcoder\OwlOrm\Orm;

use Owlcoder\OwlOrm\Exceptions\PropertyDoesNotExists;
use Owlcoder\OwlOrm\Orm\Relations\HasManyRelation;
use Owlcoder\OwlOrm\Orm\Relations\Relation;
use Owlcoder\OwlOrm\Schema\Column;
use \Owlcoder\OwlOrm\Schema\Database;

class OrmModel
{
    /** @var Database */
    public static $connection;

    /** @var string */
    public $_table;

    /** @var string */
    public $_primaryKey = 'id';

    public $attributes = [];

    public function getPrimaryValue()
    {
        return $this->{$this->_primaryKey};
    }

    public static function instance()
    {
        return new static();
    }

    public static function InitConnection($connectionParams)
    {
        static::$connection = new Database($connectionParams);
    }

    public function getConnection()
    {
        return static::$connection;
    }

    public static function query()
    {
        $builder = new ModelQuery();
        $builder->connection = static::instance()->getConnection();
        $builder->modelClass = static::class;
        $builder->from(self::instance()->_table);

        return $builder;
    }

    public function getAttributesNames()
    {
        return array_map(function (Column $column) {
            return $column->name;
        }, static::$connection->getSchema()->getTable($this->_table)->columns);
    }

    public function getAttributes()
    {
        $out = [];

        foreach ($this->getAttributesNames() as $attributeName) {
            $out[$attributeName] = $this->$attributeName;
        }

        return $out;
    }

    public function __get($property)
    {
        $getterMethod = 'get' . ucfirst($property);

        if (method_exists($this, $getterMethod)) {
            $result = $this->$getterMethod();

            if ($result instanceof HasManyRelation) {
                return $result->get()->all();
            }

            return $result;
        }

        if (isset($this->attributes[$property])) {
            return $this->attributes[$property];
        }
    }

    public function __set($property, $value)
    {
        if (in_array($property, $this->getAttributesNames())) {
            $this->attributes[$property] = $value;
        }
    }

    /**
     * Create hasMany relation
     * @param $className
     * @param $foreignKey
     * @return HasManyRelation
     */
    public function hasMany($className, $foreignKey)
    {
        $relation = new HasManyRelation();
        $relation->foreignKey = $foreignKey;
        $relation->className = $className;
        $relation->parent = $this;

        return $relation;
    }

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    public function getTableSchema()
    {
        return $this->getConnection()->getSchema()->getTable($this->_table);
    }

    public function getAttributeNames()
    {
        $this->getTableSchema()->columnNames;
    }
}