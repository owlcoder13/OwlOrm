<?php

namespace Owlcoder\OwlOrm\Schema;

class Column
{
    /** @var Table */
    public $table;
    public $name;
    public $dbType;
    public $comment;
    public $notNull;
    public $extra;
    public $default;
    public $length;
    public $unsigned;

    public $delete = false;

    public $fks = [];
    /** @var Column[] */
    public $dependencies = [];

//    public $mappingDbTypes = [
//        'integer' => 'int(11)';
//    ];

    public function compare($otherColumn)
    {
        $fields = ['name', 'dbType', 'comment', 'notNull', 'extra', 'default', 'length', 'unsigned'];
        foreach ($fields as $field) {
            $eq = $this->$field == $otherColumn->$field;
            if ( ! $eq) {
//                echo "compare field {$otherColumn->name} mismatch $field \n";
//                echo "{$otherColumn->table->name} : {$otherColumn->$field} != {$this->$field}\n";
                return false;
            }
        }
        return true;
    }

    public function isColumnDependsOfMe(Column $column)
    {
        foreach ($column->table->fks as $fk) {
            if (
                $fk->refTable == $this->table->name &&
                $fk->refColumn == $this->name) {
                return true;
            }
        }

        return false;
    }

    /** @return ForeignKey|bool */
    public function isDependOf(Column $column)
    {
        foreach ($this->table->fks as $fk) {
            if (
                $fk->refTable == $column->table->name &&
                $fk->column == $this->name &&
                $fk->refColumn == $column->name
            ) {
                return $fk;
            }
        }

        return false;
    }
}