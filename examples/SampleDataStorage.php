<?php
namespace PhpStrictData\Examples;

/**
 * @property integer                                $integer
 * @property \Closure                               $callback
 * @property mixed                                  $mixed
 *
 * @property integer[]                              $integers
 * @enum     PhpStrictData\Examples\SampleEnum[]    $integers
 *
 * @property float|string                           $enumFloat
 * @enum     ["1.8","7","9"]                        $enumFloat
 *
 * @property string[]                               $enumArray
 * @enum     ["one","two"][]                        $enumArray
 *
 * @options  PhpDocNotRequired,StrictNumberTypeCheck
 */
class SampleDataStorage extends \PhpStrictData\StrictDataStorage
{
}