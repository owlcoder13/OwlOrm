<?php

require __DIR__ . '/../vendor/autoload.php';

\Owlcoder\OwlOrm\Tests\Models\BaseModel::InitConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'portal',
    'username' => 'root',
    'password' => '1234',
]);