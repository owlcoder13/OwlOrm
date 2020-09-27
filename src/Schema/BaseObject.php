<?php

namespace Owlcoder\OwlOrm\Schema;

class BaseObject
{
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            $this->$key = $value;
        }
    }
}