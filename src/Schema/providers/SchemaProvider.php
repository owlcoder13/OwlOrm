<?php

namespace Owlcoder\OwlOrm\Schema\providers;

use Owlcoder\OwlOrm\Schema\BaseObject;
use Owlcoder\OwlOrm\Schema\Database;
use Owlcoder\OwlOrm\Schema\Schema;

abstract class SchemaProvider
{
    /**
     * @var Database
     */
    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Parse string. For example: article(id) return ['article', 'id']
     * @param $type
     * @return array
     */
    public function parseFuncString($type)
    {
        preg_match("/([A-z_0-9,]*?)\\((.*?)\\)/", $type, $matches);
        if (count($matches) === 3) {
            return [$matches[1], $matches[2]];
        }
        return null;
    }

    /** @return Schema */
    public function getSchema()
    {
        $this->schema = new Schema();
        $this->prepareSchema();
        $this->checkSchema();
        return $this->schema;
    }

    /** @var Schema */
    public $schema;

    /**
     * Create db schema
     * must not return some value
     */
    abstract public function prepareSchema();

    /**
     * Check schema integrity constraints
     */
    public function checkSchema()
    {
        $schema = $this->schema;

        foreach ($schema->tables as &$table) {
            foreach ($table->fks as $fk) {

                $refTable = $schema->getTable($fk->refTable);

                if ($refTable == null) {
                    echo "Нарушены условия FK - не найдена искомая таблица $fk->refTable у зависимости {$fk->table}($fk->column)\n";
                    continue;
                }

                $refColumn = $refTable->getColumn($fk->refColumn);

                if ($refColumn == null) {
                    echo "Нарушены условия FK - не найдена искомая таблица $fk->refTable у зависимости {$fk->table}($fk->column)\n";
                    continue;
                }
            }
        }
    }

    abstract public function getTable($name);
}