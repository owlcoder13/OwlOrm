<?php

namespace Owlcoder\OwlOrm\Schema;

use Owlcoder\OwlOrm\Schema\providers\MysqlSchemaProvider;

class SchemaCompare extends BaseObject
{
    /** @var DbConnectionParams */
    public $dbConnectionParams;

    /** @var Database */
    public $database;

    /** @var Schema */
    public $schema1;

    /** @var Schema */
    public $schema2;

    /** @var Table[] */
    public $dropTables = [];
    /** @var Table[] */
    public $createTables = [];

    /** @var Column[] */
    public $createColumns = [];
    /** @var Column[] */
    public $dropColumns = [];
    /** @var Column */
    public $alterColumns = [];

    /** @var MysqlSchemaProvider */
    public $sqlProvider;

    public $addFks = [];
    public $dropFks = [];

    public $queries = [];

    /** @var SchemaGenerator */
    public $generator;

    public function array_swap(array &$array, $key, $key2)
    {
        if (isset($array[$key]) && isset($array[$key2])) {
            list($array[$key], $array[$key2]) = array($array[$key2], $array[$key]);
            return true;
        }

        return false;
    }

    /**
     * Clear comparator
     */
    public function clear()
    {
        $this->createColumns = [];
        $this->dropColumns = [];
        $this->alterColumns = [];

        $this->createTables = [];
        $this->dropTables = [];

        $this->dropFks = [];
        $this->addFks = [];
    }

    /** @return SchemaGenerator */
    public function compare()
    {

        // Проверка таблиц из первой схемы
        foreach ($this->schema1->tables as $table1) {

            $table2 = $this->schema2->getTable($table1->name);

            if ($table2 == null) {
                // во второй схеме нет таблицы, нужно удалить её из первой базы
                $table1->delete = true;
                $this->dropTables[] = $table1;
                continue;
            }

            // наличие колонки во второй таблице
            foreach ($table1->columns as $column1) {
                $column2 = $table2->getColumn($column1->name);

                if ($column2 == null) {
                    $column1->delete = true;
                    $this->dropColumns[] = $column1; // Колонки больше нет - удалить

                } else if ( ! $column1->compare($column2)) {
                    // Колонки не одинаковые пересоздать
                    $this->alterColumns[] = $column2;

                    $len = count($this->alterColumns);
                    for ($i = 0; $i < $len - 1; $i++) {
                        // Если колонка участвует в FK, то необходимо удалить fk и добавить заново
                        $fk = $column2->isDependOf($this->alterColumns[$i]);
                        if ($fk !== false) {
                            $fk->name = $this->database->getFkNameFromDb($fk);
                            $this->dropFks[] = $fk;
                            $this->addFks[] = $fk;
                        }
                    }

                }
            }

            // новые колонки
            foreach ($table2->columns as $column2) {
                if ($table1->getColumn($column2->name) == null) {
                    // нужно создать новую колонку в исходной базе и проверить, нужно ли создавать fk
                    $this->createColumns[] = $column2;
                }
            }
        }

        // проверка таблиц из второй схемы
        foreach ($this->schema2->tables as $table2) {
            $table1 = $this->schema1->getTable($table2->name);
            if ($table1 == null) {
                $this->createTables[] = $table2; // Таблица новая - создать
                continue;
            }
        }

        // Удаление старых ключей
        foreach ($this->schema1->tables as $table1) {
            if ( ! $table1->delete) { // зачем второй раз проходиться
                $table2 = $this->schema2->getTable($table1->name);
                foreach ($table1->fks as &$fk1) {
                    if ($table2->getFk($fk1) === false) {
                        $fk1->name = $this->database->getFkNameFromDb($fk1);
                        $this->dropFks[] = $fk1;
                    }
                }
            }
        }

        // Создание новых ключей
        foreach ($this->schema2->tables as $table2) {
            if ( ! $table2->delete) { // зачем второй раз проходиться

                $table1 = $this->schema1->getTable($table2->name);

                foreach ($table2->fks as $_fk1) {

                    if ($table1 == null) {
                        $this->addFks[] = $_fk1;
                        continue;
                    }

                    $otherFk = $table1->getFk($_fk1);

                    if ($otherFk === false || $table1 == null) {
                        $this->addFks[] = $_fk1;
                    } else if ( ! $otherFk->compare($_fk1)) {
                        // recreate constraint
                        $otherFk->name = $this->database->getFkNameFromDb($otherFk);
                        $this->dropFks[] = $otherFk;
                        $this->addFks[] = $_fk1;
                    }
                }
            }
        }

        $this->run();
        $this->prepareStatement();

        return $this->generator;
    }

    public function prepareStatement()
    {

        foreach ($this->dropFks as $one) {
            $this->generator->DropFk($one);
        }

        foreach ($this->dropColumns as $one) {
            $this->generator->DropColumn($one);
        }

        foreach ($this->dropTables as $one) {
            $this->generator->DropTable($one);
        }

        foreach ($this->alterColumns as $one) {
            $this->generator->AlterColumn($one);
        }

        foreach ($this->createColumns as $one) {
            $this->generator->AddColumn($one);
        }

        foreach ($this->createTables as $one) {
            $this->generator->CreateTable($one);
        }

        foreach ($this->addFks as $one) {
            $this->generator->CreateFk($one);
        }
    }

    public function run()
    {

        /**
         * При удалении таблицы, нужно посмотреть не зависят ли от неё другие.
         * при этом, если находятся зависимые колонки в неудалённой таблице, они должны быть помечены удалёнными или
         * сама таблица должена быть помечена удалённой.
         * Если хотя-бы одна колонка не помечена, как удалённая, то выбросить ошибку
         *
         * При выполнении скрипта, сначала удаляются колонки, при этом идёт свап зависимых колонок между собой
         * Далее удаляются таблицы, также обеспечивая свап между собой.
         *
         * Добавляются новые колонки существующих таблиц
         * добавляются новые таблицы
         * Добавляются ключи
         */

        $changed = true;
        while ($changed) {
            $changed = false;

            // Удаляем таблицы к чертям
            foreach ($this->dropTables as $k1 => $table) {
                foreach ($table->columns as $column) {
                    $colDependencies = $this->schema1->getColumnDependency($column);
                    foreach ($colDependencies as $dependencyColumn) {

                        $t1 = $this->schema1->getTable($dependencyColumn->table);

                        // А теперь делаем правильный порядок удаления
                        $k1 = $this->searchArrayKey($this->dropTables, $table);
                        $k2 = $this->searchArrayKey($this->dropTables, $t1);
                        if ($k1 != -1 && $k2 != -1 && $k1 < $k2) {
                            $changed = true;
                            $this->array_swap($this->dropTables, $k1, $k2);
                            break 2;
                        }
                    }
                }
            }
        }


        // Создаём новые таблички
        foreach ($this->createTables as $k1 => $table) {
            foreach ($table->columns as $column) {
                $colDependencies = $this->schema1->getColumnDependency($column);
                foreach ($colDependencies as $dependencyColumn) {

                    $t1 = $this->schema2->getTable($dependencyColumn->table);

                    // А теперь делаем правильный порядок удаления
                    $k1 = $this->searchArrayKey($this->createTables, $table);
                    $k2 = $this->searchArrayKey($this->createTables, $t1);
                    if ($k1 != -1 && $k2 != -1 && $k1 < $k2) {
                        $this->array_swap($this->createTables, $k1, $k2);
                    }
                }
            }
        }
    }

    public function searchArrayKey($arr, $obj)
    {
        foreach ($arr as $key => $item) {
            if ($item->name == $obj->name) {
                return $key;
            }
        }
        return -1;
    }
}