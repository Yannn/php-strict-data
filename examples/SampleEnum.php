<?php
namespace PhpStrictData\Examples;

class SampleEnum implements \PhpStrictData\EnumArrayableInterface
{
    public function getEnumValues()
    {
        return [1, 3, 5, 7, 9];
    }
} 