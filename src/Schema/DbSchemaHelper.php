<?php

namespace Owlcoder\OwlOrm\Schema;


class DbSchemaHelper
{
    public $conn;
    public $database = 'portal';
    public $schema;

    public function getTableNames(){
        $q = "show tables";
        $res = mysqli_query($this->conn, $q);
        return array_map(function($item){ return $item[0]; }, mysqli_fetch_all($res));
    }

    protected function fetchAllAssoc($res){
        $output = [];
        while($row = mysqli_fetch_assoc($res)){
            $output[] = $row;
        }
        return $output;
    }

    public function getTableInfo($table){
        $res = mysqli_query($this->conn, 'describe ' . $table);

        if($res === false){
            return false;
//            throw new Exception('false result for table ' . $table);
        }

        return $this->fetchAllAssoc($res);
    }

    public function getForeignKeys($table){
        $q = "
         SELECT 
  TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
FROM
  INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
  TABLE_SCHEMA = '{$this->database}' AND
  TABLE_NAME = '{$table}';
        ";

        $res = mysqli_query($this->conn, $q);
        return $this->fetchAllAssoc($res);
    }
}