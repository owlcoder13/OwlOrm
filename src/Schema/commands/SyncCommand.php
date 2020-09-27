<?php

namespace Owlcoder\OwlOrm\Schema\commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Owlcoder\OwlOrm\Schema\providers\MysqlSchemaProvider;
use \Owlcoder\OwlOrm\Schema\providers\YamlSchemaProvider;
use \Owlcoder\OwlOrm\Schema\MysqlSchemaGenerator;
use \Owlcoder\OwlOrm\Schema\Yii1SchemaGenerator;
use \Owlcoder\OwlOrm\Schema\Yii2SchemaGenerator;
use \Owlcoder\OwlOrm\Schema\SchemaCompare;

/**
 * Sync yml schema to db
 * Class SyncCommand
 * @package Owlcoder\OwlOrm\Schema\commands
 */
class SyncCommand extends DbCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('sync');
        $this->addOption('generator', 'g', InputOption::VALUE_OPTIONAL, 'which generator use: yii1,yii2,raw', 'raw');
        $this->addOption('dpath', null, InputOption::VALUE_OPTIONAL, 'where to save generated files', '');
        $this->addOption('execute', 'e', InputOption::VALUE_OPTIONAL, 'execute if generator is raw', false);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $output->writeln("Get schema from db");
        $provider = new MysqlSchemaProvider(['database' => $this->db]);
        $schema1 = $provider->getSchema();

        $output->writeln("Get schema yml db");
        $schema2 = new YamlSchemaProvider(['path' => $this->path]);
        $schema2 = $schema2->getSchema();

        $g = $input->getOption('generator');
        $dpath = $input->getOption('dpath');

        $generator = null;

        if ($g === 'raw') {
            $generator = new MysqlSchemaGenerator([
                'database' => $this->db
            ]);
        }
        if ($g === 'yii1') {
            $generator = new Yii1SchemaGenerator([
                'database' => $this->db,
                'path' => $dpath
            ]);
        }

        if ($g === 'yii2') {
            $generator = new Yii2SchemaGenerator([
                'database' => $this->db,
                'path' => $dpath
            ]);
        }

        $compare = new SchemaCompare([
            'schema1' => $schema1,
            'schema2' => $schema2,
            'generator' => $generator,
            'database' => $this->db,
        ]);

        $output->writeln("compare");
        $generator = $compare->compare();

        $output->writeln("migrate");
        $generator->migrate($input->getOption('execute'));

        $output->writeln("done");
    }
}