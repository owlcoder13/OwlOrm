<?php

namespace Owlcoder\OwlOrm\Orm;

use PDO;

class ModelQuery extends Query implements IQuery
{
    public $modelClass = null;

    public function all()
    {
        $st = $this->connection->execStatement($this->toSql(), $this->params);

        return array_map(function ($data) {
            $model = new $this->modelClass;
            $this->populate($data, $model);
            return $model;
        }, $st->fetchAll(PDO::FETCH_ASSOC));
    }

    public function first()
    {
        $result = $this->all();
        return $result[0] ?? null;
    }

    public function populate($data, &$model)
    {
        $model->setAttributes($data);
        return $model;
    }
}