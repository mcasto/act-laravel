<?php

namespace App\Helpers;

class RefId
{
    public static function ref_id($id)
    {
        return preg_replace('/^(.{4})(.{4})(.+)$/', '$1-$2-$3', base_convert(uniqid() . dechex($id), 16, 36));
    }
}
