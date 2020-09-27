<?php

namespace Owlcoder\OwlOrm\Orm\QueryBuilders;

use Owlcoder\OwlOrm\Exceptions\WrongConditionException;
use Owlcoder\OwlOrm\Helpers\ArrHelper;
use Owlcoder\OwlOrm\Orm\IQuery;

class MysqlQueryBuilder implements IQueryBuilder
{
    /** @var Query */
    public $query;
    public $params = [];
    public $paramCounter = 0;

    public function __construct(IQuery $query)
    {
        $this->query = $query;
    }

    public function escapeParameter($value)
    {
        $i = $this->paramCounter++;
        $key = ':param' . $i;
        $this->params[$key] = $value;
        return $key;
    }

    /**
     * @param $conditions
     * @return string
     * @throws WrongConditionException
     */
    public function buildConditions($conditions)
    {
        $out = [];

        foreach ($conditions as $condition) {
            if (is_array($condition)) {
                if (ArrHelper::isAssoc($condition)) {
                    // handle key => value condition
                    foreach ($condition as $key => $value) {
                        if (is_array($value)) {
                            if (count($value) > 0) {
                                $value = join(', ', $value);
                                $out[] = "{$key} in ($value)";
                            }
                        } else {
                            $out[] = "{$key} = $value";
                        }
                    }
                } else {
                    switch ($condition[0]) {
                        case 'like':
                            $escapedValue = $this->escapeParameter($condition[2]);
                            $out[] = "{$condition[1]} like {$escapedValue}";
                            break;
                        case '=':
                            $escapedValue = $this->escapeParameter($condition[2]);
                            $out[] = "{$condition[1]} = {$escapedValue}";
                            break;
                        case '!=':
                            $escapedValue = $this->escapeParameter($condition[2]);
                            $out[] = "{$condition[1]} != {$escapedValue}";
                            break;
                        case 'in':
                            if (count($condition[2]) > 0) {
                                $escapedValues = array_map(function ($item) {
                                    return $this->escapeParameter($item);
                                }, []);

                                $joined = join(', ', $escapedValues);
                                $out[] = "{$condition[1]} in ({$joined})";
                            }
                            break;
                        default:
                            throw new WrongConditionException('Bad condition error found: ' . print_r($condition, true));
                    }
                }
            }
        }

        return count($out) > 0 ? ' WHERE ' . join(' AND ', $out) : '';
    }

    public function buildSelect()
    {
        return join(',', $this->query->getSelect());
    }

    /**
     * @return string
     * @throws WrongConditionException
     */
    public function build()
    {
        $this->params = [];
        $this->paramCounter = 0;

        $from = $this->query->getFrom();
        $select = $this->buildSelect();
        $conditions = $this->buildConditions($this->query->getConditions());
        $order = $this->buildOrder($this->query->getOrder());
        $limit = $this->buildLimit();

        return "select {$select} from {$from}{$conditions}{$order}{$limit}";
    }

    public function buildOrder($orders)
    {
        if (count($orders) > 0) {
            $joined = join(', ', $orders);
            return " order by $joined";
        }

        return '';
    }

    public function buildLimit()
    {
        return $this->query->limit ? ' limit ' . $this->query->limit : '';
    }
}