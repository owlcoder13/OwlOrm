<?php

namespace Owlcoder\OwlOrm\Schema;

use Owlcoder\OwlOrm\Schema\providers\SchemaProvider;

class Schema
{
    /** @var Table[] */
    public $tables = [];

    /** @var SchemaProvider */
    public $schemaProvider;

    /**
     * Schema constructor.
     * @param SchemaProvider $schemaProvider
     */
    public function __construct($schemaProvider)
    {
        $this->schemaProvider = $schemaProvider;
    }

    /**
     * Найти таблицу
     * @param $name
     * @return null|Table
     */
    public function getTable($name)
    {
        if (isset($this->tables[$name])) {
            return $this->tables[$name];
        }

        $this->tables[$name] = $this->schemaProvider->getTable($name);
        return $this->tables[$name];
    }

    public function getTableNames()
    {
        return $this->schemaProvider->getTableNames();
    }

    /**
     * Добавить таблицу в схему
     * @param Table $table
     * @throws \Exception
     */
    public function addTable(Table $table)
    {
        if (empty($table->name)) {
            throw new \Exception('Table name can not be empty');
        }

        $this->tables[$table->name] = $table;
    }

    /**
     * Получить информацию о зависимости колонки
     * @param Column $column
     * @return ForeignKey[]
     */
    public function getColumnDependency(Column $column)
    {

        $output = [];

        foreach ($this->tables as $table) {
            foreach ($table->fks as $fk) {
                if ($fk->refTable == $column->table->name && $fk->refColumn == $column->name) {
                    $output[] = $fk;
                }
            }
        }

        return $output;
    }
}