<?php

namespace Owlcoder\OwlOrm\Schema;

use Owlcoder\OwlOrm\Orm\QueryBuilders\MysqlQueryBuilder;
use Owlcoder\OwlOrm\Schema\providers\MysqlSchemaProvider;
use PDO;

class Database
{
    /** @var PDO */
    public $conn;

    /** @var string */
    public $dsn;

    /** @var DbConnectionParams */
    public $dbConnectionParams;

    public function __construct(array $options = [])
    {
        $this->dbConnectionParams = new DbConnectionParams($options);
        $this->dsn = "{$this->dbConnectionParams->driver}:host={$this->dbConnectionParams->host};dbname={$this->dbConnectionParams->database}";

        $this->conn = new PDO($this->dsn, $this->dbConnectionParams->user, $this->dbConnectionParams->password, [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ]);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return new Schema(new MysqlSchemaProvider($this));
    }

    public function execStatement($sql, $params = [])
    {
        $st = $this->conn->prepare($sql);
        $st->execute($params);

        return $st;
    }

    public function first($sql, $params = [])
    {
        $st = $this->execStatement($sql, $params);
        return $st->fetch();
    }

    public function all($sql, $params = [])
    {
        $st = $this->execStatement($sql, $params);
        return $st->fetchAll();
    }

    public function column($sql, $params = [])
    {
        $st = $this->execStatement($sql, $params);
        $items = [];

        while ($item = $st->fetchColumn()) {
            $items[] = $item;
        }

        return $items;
    }

    public function scalar($sql, $params = [])
    {
        $st = $this->execStatement($sql, $params);
        return $st->fetchColumn();
    }

    public function getBuilderClass()
    {
        return [
            'mysql' => MysqlQueryBuilder::class,
        ][$this->dbConnectionParams->driver];
    }

}