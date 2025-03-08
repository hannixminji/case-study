<?php

final class Helper
{
    public static function getPdoParameterType(mixed $value): int
    {
        return match (true) {
            $value === null                    => PDO::PARAM_NULL,
            is_bool($value)                    => PDO::PARAM_BOOL,
            is_int($value)                     => PDO::PARAM_INT ,
            preg_match('/^[1-9]\d*$/', $value) => PDO::PARAM_INT ,
            is_resource($value)                => PDO::PARAM_LOB ,
            default                            => PDO::PARAM_STR
        };
    }
}
