<?php

namespace Owlcoder\OwlOrm\Schema;


class ForeignKey extends BaseObject
{
    public $table;
    public $column;
    public $refTable;
    public $refColumn;

    public $onDelete = null;
    public $onUpdate = null;

    public $name;

    public function compare(ForeignKey $otherFk){
        $fields = ['table', 'column', 'refTable', 'refColumn', 'onDelete', 'onUpdate'];
        foreach($fields as $field){
            $eq = $this->$field == $otherFk->$field;
            if (!$eq){
                echo "compare field {$otherFk->name} mismatch $field\n";
                return false;
            }
        }
        return true;
    }
}