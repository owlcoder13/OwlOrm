<?php

namespace Owlcoder\OwlOrm\Helpers;

class ArrHelper
{
    /**
     * @param $arr
     * @return bool
     */
    public static function isAssoc($arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}