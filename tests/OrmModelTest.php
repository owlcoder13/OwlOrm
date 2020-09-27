<?php

use PHPUnit\Framework\TestCase;

class OrmModelTest extends TestCase
{
    public function testFetchAll()
    {
        $model = \Owlcoder\OwlOrm\Tests\Models\User::query()
            ->where(['=', 'email', 'mitch3182@gmail.com'])->all();

        $this->assertTrue(count($model) == 1);
    }

    public function testRelationAndFetchFirst()
    {
        $model = \Owlcoder\OwlOrm\Tests\Models\User::query()
            ->where(['=', 'email', 'mitch3182@gmail.com'])->first();

        $this->assertTrue($model instanceof \Owlcoder\OwlOrm\Tests\Models\User);
        $this->assertTrue($model instanceof \Owlcoder\OwlOrm\Tests\Models\User);
    }

    public function testLimit()
    {
        $model = \Owlcoder\OwlOrm\Tests\Models\User::query()->limit(1)->all();
        $this->assertCount(1, $model);
    }

    public function testOrderAndInCondition()
    {
        $model = \Owlcoder\OwlOrm\Tests\Models\User::query()->orderBy('id desc')->where(['id' => [21, 22, 23]])->all();
        $this->assertCount(3, $model);
        $this->assertTrue($model[0]->id == 23);
    }

    public function testCount()
    {
        $cnt = \Owlcoder\OwlOrm\Tests\Models\User::query()->where(['id' => [21, 22, 23]])->count();
        $this->assertEquals($cnt, 3);
    }
}