<?php

namespace Owlcoder\OwlOrm\Schema\providers;

use Owlcoder\OwlOrm\Schema\Table;
use Owlcoder\OwlOrm\Schema\Column;
use Owlcoder\OwlOrm\Schema\ForeignKey;
use function PHPSTORM_META\type;
use Symfony\Component\Yaml\Yaml;

class YamlSchemaProvider extends SchemaProvider
{
    public $path;
    public $str;

    /**
     * Разделяет строку на части с учётом вложенных скобок
     * @param $str
     * @return array
     */
    function explodeColumnInfo($str)
    {
        $nestedBraces = 0;
        $positions = [];

        for ($i = 0; $i < mb_strlen($str); $i++) {
            $c = mb_substr($str, $i, 1);

            if ($c == '(') $nestedBraces++;
            if ($c == ')') $nestedBraces--;

            if ($nestedBraces == 0 && $c == ':') {
                $positions[] = $i;
            }
        }

        $output = [];
        $prevPos = 0;
        foreach ($positions as $position) {
            $output[] = mb_substr($str, $prevPos, $position - $prevPos);
            $prevPos = $position + 1;
        }
        $output[] = mb_substr($str, $prevPos);

        return $output;
    }

    /**
     * @param $columnName
     * @param $columnInfo
     * @param $tableModel
     * @throws \Exception
     * @return Column
     */
    public function createColumn($columnName, $columnInfo, Table &$tableModel)
    {

        // short syntax
        if (is_string($columnInfo)) {
            if ($columnInfo === 'pk') {
                $columnInfo = [
                    'length' => '11',
                    'type' => 'int',
                    'notNull' => true,
                    'extra' => 'auto_increment',
                ];
                $tableModel->pk = ['id'];
            } else {

                $parts = $this->explodeColumnInfo($columnInfo);
                $columnInfo = [];
                foreach ($parts as $key => $part) {

                    if (strpos($part, '(') !== false) {
                        list($t, $l) = $this->parseFuncString($part);

                        switch ($t) {
                            case 'string':
                            case 'varchar':
                                $columnInfo['type'] = 'varchar';
                                $columnInfo['length'] = $l;
                                break;
                            case 'int':
                                $columnInfo['type'] = 'int';
                                $columnInfo['length'] = $l;
                                break;
                            case 'd':
                            case 'default':
                                $columnInfo['default'] = $l;
                                break;
                            case 'extra':
                                $columnInfo['extra'] = $l;
                                break;
                            default:
                                if ($key == 0) { // Тип - обязательно должен быть на первой позиции если он необычный
                                    $columnInfo['type'] = $t;
                                    $columnInfo['length'] = $l;
                                }
                        }
                    } else {
                        switch ($part) {
                            case 'notNull':
                                $columnInfo['notNull'] = true;
                                break;
                            case 'u':
                                $columnInfo['unsigned'] = true;
                                break;
                            case 'string':
                                $columnInfo['type'] = 'varchar';
                                $columnInfo['length'] = 128;
                                break;
                            case 'int':
                                $columnInfo['type'] = 'int';
                                $columnInfo['length'] = 11;
                                break;
                            case 'boolean':
                            case 'bool':
                                $columnInfo['type'] = 'tinyint';
                                $columnInfo['length'] = 1;
                                break;
                            case 'pk':
                                $tableModel->pk[] = $columnName;
                                break;
                            default:
                                if ($key == 0) {
                                    $columnInfo['type'] = $part;
                                }
                        }
                    }

                    // Если у колонки стоит только pk
                    if (empty($columnInfo['type']) && in_array($columnName, $tableModel->pk)) {
                        $columnInfo['type'] = 'int';
                        $columnInfo['length'] = '11';
                        $columnInfo['extra'] = 'auto_increment';
                    }

                    // Проставление типов без length
                    if (in_array($part, ['datetime', 'date', 'timestamp', 'text', 'float', 'double'])) {
                        $columnInfo['type'] = $part;
                    }
                }
            }
        }

        $columnModel = new Column;
        $columnModel->table = $tableModel;

        $columnModel->name = $columnName;
        $columnModel->dbType = isset($columnInfo['type']) ? $columnInfo['type'] : 'unknown_type';
        $columnModel->notNull = isset($columnInfo['notNull']) ? (bool) $columnInfo['notNull'] : false;
        $columnModel->default = isset($columnInfo['default']) ? $columnInfo['default'] : null;

        if ( ! empty($columnInfo['length'])) {
            $columnModel->length = $columnInfo['length'];
        }

        $columnModel->extra = isset($columnInfo['extra']) ? $columnInfo['extra'] : null;

        $columnModel->unsigned = isset($columnInfo['unsigned']) ? $columnInfo['unsigned'] : null;

        /**
         * Check for timestamp column. It can not be timestamp while notNull and not have default value.
         * There are some auto generate extra: on update CURRENT_TIMESTAMP and it can give you bad behavior
         * in second start of comparator
         **/
        if ($columnModel->dbType == 'timestamp' && $columnModel->notNull && empty($columnModel->default)) {
            $coldef = print_r($columnInfo, true);
            throw new \Exception("You must specify default value for notNull timestamp. Table: {$tableModel->name} Column definition: $coldef");
        }

        foreach ($tableModel->columns as $col) {
            if (in_array($col->name, $tableModel->pk) && ! $col->notNull) {
                throw new \Exception('All parts of a PRIMARY KEY must be NOT NULL; table: ' . $tableModel->name);
            }
        }

        return $columnModel;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function prepareSchema()
    {
        if ( ! empty($this->path)) {
            $data = Yaml::parse(file_get_contents($this->path));
        } else if ( ! empty($this->str)) {
            $data = Yaml::parse($this->str);
        } else {
            throw new \Exception('You must specify str or path variable');
        }

        if ( ! is_array($data)) {
            $data = [];
        }

        $all = [];

        foreach ($data as $tableName => $tableInfo) {

//            // pass tables for test
//            if($tableName != 'django_admin_log'){
//                continue;
//            }

            if ($tableName[0] == '_') {
                $all = $tableInfo;
                continue;
            }

            $tableModel = new Table();
            $tableModel->name = $tableName;
            $tableModel->pk = [];

            $columnsInfo = $tableInfo['columns'];

            foreach ($columnsInfo as $columnName => $columnInfo) {
                $columnModel = $this->createColumn($columnName, $columnInfo, $tableModel);
                $tableModel->addColumn($columnModel);
            }

            if (isset($tableInfo['fks'])) {

                foreach ($tableInfo['fks'] as $sourceColname => $keyInfo) {

                    // if no actions find in fk
                    if (strpos($keyInfo, ':') === false) {
                        $onDelete = 'cascade';
                        $onUpdate = 'cascade';
                    } else {
                        list($keyInfo, $onDelete, $onUpdate) = explode(':', $keyInfo);
                    }

                    list($m1, $m2) = $this->parseFuncString($keyInfo);

                    if (isset($m1) && isset($m2)) {

                        $col = $tableModel->getColumn($sourceColname);

                        if ($col == null) {
                            throw new \Exception("FK ERROR: Такой колонки нет {$tableName}:{$sourceColname}");
                        }

                        $fk = new ForeignKey([
                            'table' => $tableName,
                            'column' => $sourceColname,
                            'refTable' => $m1,
                            'refColumn' => $m2,
                            'onDelete' => $onDelete,
                            'onUpdate' => $onUpdate,
                        ]);

                        $col->dependencies[] = $fk;
                        $tableModel->fks[] = $fk;
                    } else {

                    }
                }
            }

            // Обратная ссылка на схему
            $tableModel->schema = $this->schema;

            $this->schema->addTable($tableModel);
        }

        $except = isset($all['except']) ? explode(',', $all['except']) : [];

        if (isset($all['columns'])) {
            foreach ($this->schema->tables as $tableModel) {

                if (in_array($tableModel->name, $except)) {
                    continue;
                }

                foreach ($all['columns'] as $columnName => $columnInfo) {
                    $columnModel = $this->createColumn($columnName, $columnInfo, $tableModel);
                    $tableModel->addColumn($columnModel);
                }

            }
        }

    }
}