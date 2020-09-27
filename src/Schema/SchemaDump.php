<?php

namespace Owlcoder\OwlOrm\Schema;

class SchemaDump
{
    public static function ToString(Schema $schema)
    {
        $output = [];

        foreach ($schema->tables as $table) {

            $output[$table->name] = [
                'columns' => []
            ];

            foreach ($table->columns as $columnName => $columnInfo) {

                $definition = [];

                if ( ! is_null($columnInfo->length) && $columnInfo->length != null) {
                    $definition[] = $columnInfo->dbType . "($columnInfo->length)";
                } else {
                    $definition[] = $columnInfo->dbType;
                }

                $definition[] = $columnInfo->notNull ? 'notNull' : 'null';

                if ( ! is_null($columnInfo->default)) {
                    $definition[] = "default($columnInfo->default)";
                }

                if ( ! is_null($columnInfo->extra) && $columnInfo->extra != null) {
                    $definition[] = "extra($columnInfo->extra)";
                }

                if ( ! is_null($columnInfo->unsigned) && $columnInfo->unsigned != null) {
                    $definition[] = 'u';
                }

                if (in_array($columnInfo->name, $table->pk)) {
                    $definition[] = 'pk';
                }

                $output[$table->name]['columns'][$columnInfo->name] = join(':', $definition);
            }


            foreach ($table->fks as $fk) {
                if ( ! isset($output[$table->name]['fks'])) {
                    $output[$table->name]['fks'] = [];
                }
                $output[$table->name]['fks'][$fk->column] = $fk->refTable . '(' . $fk->refColumn . '):' . $fk->onDelete . ':' . $fk->onUpdate;
            }
        }

        return \Symfony\Component\Yaml\Yaml::dump($output, 5);
    }

    public static function Dump(Schema $schema, $path)
    {
        $output = self::ToString($schema);
        file_put_contents($path, $output);
    }
}