<?php

namespace Owlcoder\OwlOrm\Schema\commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use \Owlcoder\OwlOrm\Schema\providers\MysqlSchemaProvider;
use \Owlcoder\OwlOrm\Schema\SchemaDump;

/**
 * Create yml from db
 * Class InspectDbCommand
 * @package Owlcoder\OwlOrm\Schema\commands
 */
class InspectDbCommand extends DbCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('inspectdb');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $provider = new MysqlSchemaProvider(['database' => $this->db]);

        $output->writeln("Write schema to file");
        $schema = $provider->getSchema();

        $output->writeln("write schema to file: " . $this->path);
        SchemaDump::Dump($schema, $this->path);

        $output->writeln("done");
    }
}