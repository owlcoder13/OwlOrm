<?php

namespace Owlcoder\OwlOrm\Schema;

class LaravelSchemaGenerator extends SchemaGenerator
{
    /** @var Database */
    public $database;
    public $path;

    public $output = '';

    // Касательно каждой таблицы
    public $aboutTable = [];

    // Общие запросы
    public $commonQuery = '';

    public function renderTemplate($variables)
    {
        if (empty($this->path)) {
            $this->output .= $variables['code'] . "\n";
            return;
        }

        $micro_date = microtime();
        $date_array = explode(" ", $micro_date);
        $time = gmdate('ymd_His_') . substr($date_array[0], 2);

        extract($variables);
        $classname = 'm' . $time . '_' . $name;

        $templatePath = __DIR__ . '/../../templates/yii2/Migration.php';
        ob_start();

        require $templatePath;

        $result = ob_get_clean();
        $fileName = $classname . '.php';

        if ( ! empty($this->path)) {
            file_put_contents($this->path . '/' . $fileName, $result);
        }

        $this->output .= $result . "\n";
    }

    public function ColumnDefinition(Column $column)
    {
        $length = empty($column->length) ? '' : $column->length;

        $funcMode = true;

        $funcMapping = [
            'datetime' => "\$this->dateTime($length)",
            'timestamp' => "\$this->timestamp($length)",
            'time' => "\$this->time($length)",
            'varchar' => "\$this->string($length)",
            'text' => "\$this->text($length)",
            'int' => "\$this->integer($length)",
            'tinyint' => "\$this->boolean()",
        ];

        if ( ! isset($funcMapping[$column->dbType])) {
            $funcMode = false;
            $def = [];
            $def[] = $column->dbType;
        } else {
            $def = $funcMapping[$column->dbType];
        }

        // Если не в виде функций
        if ( ! $funcMode) {
            if ($column->notNull) {
                $def[] = 'not null';
            } else {
                $def[] = 'null';
            }

            if ($column->default !== null) {
                $def[] = "default $column->default";
            }

            if ($column->extra) {
                $def[] = "$column->extra";
            }

            return '"' . join(' ', $def) . '"';
        }

        // В виде функций
        if ($column->notNull) {
            $def .= '->notNull()';
        } else {
            $def .= '->null()';
        }

        if ($column->default !== null) {
            if ($column->default === 'CURRENT_TIMESTAMP') {
                $def .= "->defaultExpression('{$column->default}')";
            } else {
                $def .= "->defaultValue('{$column->default}')";
            }

        }

        if ($column->extra) {
            $def .= " . ' $column->extra'";
        }

        return $def;
    }

    public function DropColumn(Column $one)
    {
        $this->renderTemplate([
            'name' => 'drop_column',
            'code' => "\$this->dropColumn('{$one->table->name}', '{$one->name}');",
        ]);
    }

    protected function createAboutTable($table)
    {
//        if ( ! isset($this->aboutTable[$one->table])) {
//            $this->aboutTable[$one->table] = [];
//        }
    }

    public function DropFk(ForeignKey $one)
    {
        $fkName = $this->database->getFkNameFromDb($one);

        $this->renderTemplate([
            'name' => 'drop_fk',
            'code' => "\$this->dropForeignKey('$fkName', '{$one->table}');",
        ]);
    }

    public function DropTable(Table $one)
    {
        $this->renderTemplate([
            'name' => 'drop_table',
            'code' => "\$this->dropTable('$one->name');",
        ]);
    }

    public function AlterColumn(Column $one)
    {
        $columnDefinition = $this->ColumnDefinition($one);

        $this->renderTemplate([
            'name' => 'alter_column',
            'code' => "\$this->alterColumn('{$one->table->name}', '{$one->name}', {$columnDefinition});",
        ]);
    }

    public function AddColumn(Column $one)
    {
        $this->createAboutTable($one->table);

//        if($one->typ)
    }

    public function CreateTable(Table $one)
    {
        $colDefinitions = '';


        foreach ($one->columns as $column) {

//            if ($column->name == 'id') continue;

            $columnDefinition = $this->ColumnDefinition($column);

            // todo: set PK
            if (is_array($one->pk) && in_array($column->name, $one->pk)) {
                $columnDefinition .= ' . " PRIMARY KEY"';
            }

            $colDefinitions .= "\t\t\t'{$column->name}' => $columnDefinition,\n";

        }

        $this->renderTemplate([
            'name' => 'create_table_' . $one->name,
            'code' => "\$this->createTable('{$one->name}', [\n $colDefinitions \n\t\t]);",
        ]);
    }

    public function CreateFk(ForeignKey $one)
    {
        $this->createAboutTable($one->table);
        $this->aboutTable[$one->table] = "\table->foreign('$one->column')->references('$one->refColumn')->on('$one->refTable')";
    }

    public function migrate($execute = false)
    {
        ob_start();
        foreach ($this->aboutTable as $table => $definitions) {
            ?>

            Schema::table('<?=$table?>', function(Blueprint $table){
            <?=$definitions?>
            });

            <?php
        }

        $tableDefitions = ob_get_clean();
    }
}