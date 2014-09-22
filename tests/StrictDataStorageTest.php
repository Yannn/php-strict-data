<?php

class StrictDataStorageTest extends PHPUnit_Framework_TestCase
{
    private $_propertiesPlain = ['mixed','bool','integer','float','string','array','null','callback','stdClass','resource'];
    private $_propertiesArray = ['mixedArray','boolArray','integerArray','floatArray','stringArray','arrayArray','nullArray','callbackArray','stdClassArray','resourceArray'];
    private $_enums = ['number','numbers','numberClass','numbersClass'];

   /**
     * @dataProvider plainValuesProvider
     */
    public function testValues($value,$successCount)
    {
        $c = new TestPropertyDataStorage();
        $errorCnt = 0;
        $errorProp = [];
        $successProp = [];
        foreach($this->_propertiesPlain as $property) {
            try {
                $c->$property = $value;
                $successProp []= $property;
            } catch(Exception $expected) {
                $errorCnt++;
                $errorProp []= $property;
            }
        }
        $errorCntExpected=count($this->_propertiesPlain)-$successCount;
        $message='for value "'.var_export($value,true).'" failed properties: ['.join(',',$errorProp).'] success properties: ['.join(',',$successProp).']';
        $this->assertEquals($errorCntExpected, $errorCnt, $message);
    }

    public function plainValuesProvider()
    {
        return [
            [null, 2],
            [1, 3],
            ['1', 4],
            [1.2, 2],
            ['1.2', 3],
            ['str', 2],
            [true, 2],
            [[1,2,3],2],
            [new stdClass(),2],
            [function () {
            },2],
        ];
    }

}

/**
 * @property mixed           $mixed
 * @property string          $string
 * @property bool            $bool
 * @property integer         $integer
 * @property float           $float
 * @property array           $array
 * @property null            $null
 * @property Closure         $callback
 * @property stdClass        $stdClass
 * @property resource        $resource
 *
 * @property mixed[]           $mixedArray
 * @property bool[]            $boolArray
 * @property integer[]         $integerArray
 * @property float[]           $floatArray
 * @property string[]          $stringArray
 * @property array[]           $arrayArray
 * @property null[]            $nullArray
 * @property Closure[]         $callbackArray
 * @property stdClass[]        $stdClassArray
 * @property resource[]        $resourceArray
 *
 * @property bool|integer           $boolOrInteger
 * @property bool|integer[]         $boolOrIntegerArray
 * @property bool|integer[]|null    $boolOrIntegerOrNull
 */
class TestPropertyDataStorage extends StrictDataStorage
{
}
/**
 * @enum     ['one','two']      $number
 * @enum     ['one','two'][]    $numbers
 * @enum     TextEnum           $numberClass
 * @enum     TextEnum[]         $numbersClass
 */
class TestEnumDataStorage extends StrictDataStorage
{
}

/**
 *
 * @property integer                $integer
 *
 * @options  PhpDocNotRequired,StrictNumberTypeCheck
 */
class TestOptionsDataStorage extends StrictDataStorage
{
}

class TestNumbersEnum implements EnumArrayableInterface
{
    public function getEnumValues()
    {
        return ['one','two'];
    }
}