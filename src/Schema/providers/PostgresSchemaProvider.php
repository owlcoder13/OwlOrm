<?php

namespace Owlcoder\OwlOrm\Schema\providers;

use Owlcoder\OwlOrm\Schema\Database;
use Owlcoder\OwlOrm\Schema\Table;
use Owlcoder\OwlOrm\Schema\Column;
use Owlcoder\OwlOrm\Schema\ForeignKey;

class PostgresSchemaProvider extends SchemaProvider
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
        if (strpos($dbtype, 'unsigned') !== false) {
            $dbtype = trim(str_replace('unsigned', '', $dbtype));
            $columnModel->unsigned = true;
        }

        $columnModel->dbType = $dbtype;
        $columnModel->length = $length != null ? $length : null;
        $columnModel->notNull = $columnInfo['Null'] == 'NO';
        $columnModel->default = $columnInfo['Default'];
//        if($columnModel->default !== null){
//            echo var_dump($columnModel->default);
//        }

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
        $columnsInfo = $this->database->getTableInfo($tableName);

        if ($columnsInfo === false) {
            return false;
        }

        foreach ($columnsInfo as $columnInfo) {
            $columnModel = $this->prepareColumn($columnInfo);
            $columnModel->table = $tableModel;
            $tableModel->addColumn($columnModel);
        }

        return $tableModel;
    }

    public function prepareSchema()
    {
        foreach ($this->database->getTableNames() as $tableName) {

            $tableModel = $this->prepareTable($tableName);
            if ($tableModel !== false) {

                // Обратная ссылка на схему
                $tableModel->schema = $this->schema;

                $this->schema->addTable($tableModel);
            }
        }

        $this->fetchFkConstraints();
    }

    public function fetchFkConstraints()
    {
        $rows = $this->database->fetchFkConstraints();

        foreach ($rows as $row) {

            $tableModel = $this->schema->getTable($row['TABLE_NAME']);

            if($tableModel == null){
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

}