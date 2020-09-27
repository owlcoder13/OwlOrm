<?php

namespace Owlcoder\OwlOrm\Schema;


class DbConnectionParams extends BaseObject
{
    public $host = 'localhost';
    public $user = 'root';
    public $password = null;
    public $database = null;
    public $port = 3306;
    public $driver;
}