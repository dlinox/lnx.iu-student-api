<?php 

namespace App\Constants;

class StudentTypeConstants
{
    const STUDENT_TYPE = [
        '1' => 'ESTUDIANTE UNA',
        '3' => 'PARTICULAR',
        '5' => 'EGRESADO UNA',
    ];

    public static function getNameByValue(string $value): ?string
    {
        return self::STUDENT_TYPE[$value] ?? null;
    }

    public static function getValueByName(string $name): ?string
    {
        return array_search($name, self::STUDENT_TYPE, true) ?: null;
    }
}