<?php

namespace Owlcoder\OwlOrm\Orm\QueryBuilders;

use Owlcoder\OwlOrm\Orm\IQuery;

/**
 * Classes build query string for any database
 *
 * Interface IQueryBuilder
 * @package Owlcoder\OwlOrm\Orm\QueryBuilders
 */
interface IQueryBuilder
{
    /**
     * IQueryBuilder constructor.
     * @param IQuery $query
     */
    public function __construct(IQuery $query);

    /**
     * @return string
     */
    public function build();
}