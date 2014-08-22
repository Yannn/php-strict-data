<?php
/**
 * @property int             $integer
 * @property Closure         $callback
 *
 * @property integer[]       $integers
 * @enum     SampleEnum[]    $integers
 *
 * @property float           $enumFloat
 * @enum     ["1.8","7","9"] $enumFloat
 *
 * @property string[]        $enumArray
 * @enum     ["one","two"][] $enumArray
 *
 * @options  PhpDocNotRequired|StrictNumberTypeCheck
 */
class SampleDataStorage extends StrictDataStorage
{
}