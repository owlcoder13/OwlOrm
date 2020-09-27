<?php

namespace Owlcoder\OwlOrm\Schema;

class Yii1SchemaGenerator extends SchemaGenerator
{
    /** @var Database */
    public $database;
    public $path;

    public $output;

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

        $templatePath = __DIR__ . '/../../templates/yii1/Migration.php';
        ob_start();

        require $templatePath;

        $result = ob_get_clean();
        $fileName = $classname . '.php';

        if ( ! empty($this->path)) {
            file_put_contents($this->path . '/' . $fileName, $result);
        }

        $this->output .= $result . "\n";
    }

    /**
     * @inheritdoc
     */
    public function ColumnDefinition(Column $column)
    {
        $definition = [];

        $dbType = $column->dbType;
        if ($column->length) {
            $dbType .= "({$column->length})";
        }
        $definition[] = $dbType;

        $notNull = $column->notNull ? 'not null' : 'null';
        $definition[] = $notNull;

        $default = $column->default ? "default '$column->default'" : '';
        if(!empty($default)){
            $definition[] = $default;
        }

        $extra = $column->extra;
        if(!empty($extra)){
            $definition[] = $extra;
        }

        return join(' ', $definition);
    }

    public function DropColumn(Column $one)
    {
        $this->renderTemplate([
            'name' => "drop_column_{$one->name}_from_{$one->table->name}",
            'code' => "\$this->dropColumn('{$one->table->name}', '{$one->name}');",
        ]);
    }

    public function DropFk(ForeignKey $one)
    {
        $fkName = $this->database->getFkNameFromDb($one);

        $this->renderTemplate([
            'name' => "drop_fk_{$one->table}_{$one->column}",
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
            'code' => "\$this->alterColumn('{$one->table->name}', '{$one->name}', '{$columnDefinition}');",
        ]);
    }

    public function AddColumn(Column $one)
    {
        $columnDefinition = $this->ColumnDefinition($one);

        $this->renderTemplate([
            'name' => "add_column_{$one->name}_to_{$one->table->name}",
            'code' => "\$this->addColumn('{$one->table->name}', '{$one->name}', '{$columnDefinition}');",
        ]);
    }

    public function CreateTable(Table $one)
    {
        $colDefinitions = '';

        if ( ! empty($one->pk)) {
            $colDefinitions .= "\t\t\t'id' => 'pk',\n";
        }

        foreach ($one->columns as $column) {

            if ($column->name == 'id') continue;

            $columnDefinition = $this->ColumnDefinition($column);
            $colDefinitions .= "\t\t\t'{$column->name}' => \"$columnDefinition\",\n";
        }

        $this->renderTemplate([
            'name' => 'create_table_' . $one->name,
            'code' => "\$this->createTable('{$one->name}', [\n $colDefinitions \n\t\t]);",
        ]);
    }

    public function CreateFk(ForeignKey $one)
    {

        $fkName = $this->CreateFkName($one);
        $this->renderTemplate([
            'name' => "add_fk_{$one->table}_{$one->column}",
            'code' => "\$this->addForeignKey('{$fkName}', '{$one->table}', '{$one->column}', '{$one->refTable}', '{$one->refColumn}', '{$one->onDelete}', '{$one->onUpdate}');",
        ]);
    }

    public function migrate($execute = false)
    {
        // TODO: Implement migrate() method.
    }
}