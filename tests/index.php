<?php



$connection = \Owlcoder\OwlOrm\Tests\Models\BaseModel::$connection;
$schema = $connection->getSchema();
//$userTable = $schema->getTable('tbl_user');

//print '<pre>';
//print_r($schema->getTable('tbl_person'));
//exit();

$query = \Owlcoder\OwlOrm\Tests\Models\User::query()
    ->where(['=', 'email', 'mitch3182@gmail.com']);




print '<pre>';
print_r($query->all()[0]->roles);
exit();

