<?php

final class Helper
{
    public static function getPdoParameterType(mixed $value): int
    {
        return match (true) {
            is_int     ($value) => PDO::PARAM_INT ,
            is_bool    ($value) => PDO::PARAM_BOOL,
            is_null    ($value) => PDO::PARAM_NULL,
            is_resource($value) => PDO::PARAM_LOB ,
            default             => PDO::PARAM_STR
        };
    }
}
