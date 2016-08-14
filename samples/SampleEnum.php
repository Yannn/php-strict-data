<?php
namespace PhpStrictData;

class SampleEnum implements EnumArrayableInterface
{
    public function getEnumValues()
    {
        return [1, 3, 5, 7, 9];
    }
} 