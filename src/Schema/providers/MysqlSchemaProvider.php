<?php

namespace Owlcoder\OwlOrm\Schema\providers;

use Owlcoder\OwlOrm\Schema\Database;
use Owlcoder\OwlOrm\Schema\Table;
use Owlcoder\OwlOrm\Schema\Column;
use Owlcoder\OwlOrm\Schema\ForeignKey;

class MysqlSchemaProvider extends SchemaProvider
{

    /** @var Database */
    public $database;
    public $typeConfig = 'mysql';

    /**
     * @param $columnInfo
     * @return Column
     */
    public function prepareColumn($columnInfo)
    {
        $length = null;
        $columnModel = new Column;

        $columnModel->name = $columnInfo['Field'];

        $dbtype = $this->parseFuncString($columnInfo['Type']);

        if ($dbtype != null) {
            list($dbtype, $length) = $dbtype;
        } else {
            $dbtype = $columnInfo['Type'];
        }
        if (strpos($columnInfo['Type'], 'unsigned') !== false) {
            $columnModel->unsigned = true;
        }

        $columnModel->dbType = $dbtype;
        $columnModel->length = $length != null ? $length : null;
        $columnModel->notNull = $columnInfo['Null'] == 'NO';
        $columnModel->default = $columnInfo['Default'];

        $columnModel->extra = $columnInfo['Extra'];

        return $columnModel;
    }

    /**
     * @param $tableName
     * @return Table|bool
     */
    public function prepareTable($tableName)
    {
        $tableModel = new Table();
        $tableModel->name = $tableName;
        $columnsInfo = $this->getTableInfo($tableName);

        if ($columnsInfo === false) {
            return false;
        }

        foreach ($columnsInfo as $columnInfo) {
            $columnModel = $this->prepareColumn($columnInfo);
            $columnModel->table = $tableModel;
            $tableModel->addColumn($columnModel);
            $tableModel->columnNames = $columnModel->name;
        }

        return $tableModel;
    }

    public function getTable($name)
    {
        return $this->prepareTable($name);
    }

    public function prepareSchema()
    {
        foreach ($this->getTableNames() as $tableName) {

            $tableModel = $this->prepareTable($tableName);
            if ($tableModel !== false) {

                // Обратная ссылка на схему
                $tableModel->schema = $this->schema;

                $this->schema->addTable($tableModel);
            }
        }

        $this->fetchFkConstraints();
    }

    public function fetchFkConstraints($table = null)
    {
        $rows = $this->getFkConstraints($table);

        foreach ($rows as $row) {

            $tableModel = $this->schema->getTable($row['TABLE_NAME']);

            if ($tableModel == null) {
                throw new \Exception('table can not be null if constraint exists');
            }

            if ($row['CONSTRAINT_NAME'] == 'PRIMARY') {
                $tableModel->pk = [$row['COLUMN_NAME']];
            } else {
                if (isset($row['TABLE_NAME'])
                    && isset($row['COLUMN_NAME'])
                    && isset($row['REFERENCED_TABLE_NAME'])
                    && isset($row['REFERENCED_COLUMN_NAME'])
                ) {

                    $col = $tableModel->getColumn($row['COLUMN_NAME']);

                    $fk = new ForeignKey([
                        'table' => $row['TABLE_NAME'],
                        'column' => $row['COLUMN_NAME'],
                        'refTable' => $row['REFERENCED_TABLE_NAME'],
                        'refColumn' => $row['REFERENCED_COLUMN_NAME'],
                        'onDelete' => strtolower($row['DELETE_RULE']),
                        'onUpdate' => strtolower($row['UPDATE_RULE']),
                    ]);

                    $col->fks[] = $fk;
                    $tableModel->fks[] = $fk;
                }

            }
        }
    }

    public function fetchIndexes()
    {

    }

    public function getTableNames()
    {
        $q = "show full tables where Table_Type = 'BASE TABLE'";
        $result = $this->connection->column($q);

        return $result;
    }

    public function getTableInfo($table)
    {
        return $this->connection->all('describe `' . $table . '`');
    }

    public function getFkNameFromDb(ForeignKey $fk)
    {
        $q = "
         SELECT CONSTRAINT_NAME
FROM
  INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
  TABLE_SCHEMA = '{$this->dbConnectionParams->database}' AND
  TABLE_NAME = '{$fk->table}' 
  and TABLE_NAME = '{$fk->table}' 
  and COLUMN_NAME = '{$fk->column}' 
  and REFERENCED_TABLE_NAME='{$fk->refTable}' 
  and REFERENCED_COLUMN_NAME='{$fk->refColumn}';";

        $res = mysqli_query($this->conn, $q);
        $field = mysqli_fetch_row($res);
        return $field[0];
    }

    public function getFkConstraints($table = null)
    {
        $q = "SELECT 
  kcu.TABLE_NAME,kcu.COLUMN_NAME,kcu.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME,kcu.REFERENCED_COLUMN_NAME, rc.UPDATE_RULE, rc.DELETE_RULE
FROM
  INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
  left join information_schema.REFERENTIAL_CONSTRAINTS rc 
  on rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME and rc.CONSTRAINT_SCHEMA =  '{$this->dbConnectionParams->database}' 
  WHERE
  kcu.TABLE_SCHEMA = '{$this->dbConnectionParams->database}'";

        if ( ! empty($table)) {
            $q .= " and table = `${table}`";
        }

        $res = mysqli_query($this->conn, $q);
        return $this->fetchAllAssoc($res);
    }

    public function getForeignKeys($table)
    {
        $q = "
         SELECT 
  kcu.TABLE_NAME,kcu.COLUMN_NAME,kcu.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME,kcu.REFERENCED_COLUMN_NAME, rc.UPDATE_RULE, rc.DELETE_RULE
FROM
  INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
  left join information_schema.REFERENTIAL_CONSTRAINTS rc on rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
WHERE
  kcu.TABLE_SCHEMA = '{$this->dbConnectionParams->database}' AND
  kcu.TABLE_NAME = '{$table}';
        ";

        $res = mysqli_query($this->conn, $q);
        return $this->fetchAllAssoc($res);
    }

    public function query($command)
    {
        $stm = mysqli_query($this->conn, $command);
        $rows = [];

        while (($row = mysqli_fetch_assoc($stm)) != null) {
            $rows[] = $row;
        }

        return $rows;
    }

}