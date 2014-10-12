<?php

class StrictDataStorageTest extends PHPUnit_Framework_TestCase
{
    private $_propertiesPlain = ['mixed', 'bool', 'integer', 'float', 'string', 'array', 'null', 'callback', 'stdClass', 'resource'];
    private $_propertiesArray = ['mixedArray', 'boolArray', 'integerArray', 'floatArray', 'stringArray', 'arrayArray', 'nullArray', 'callbackArray', 'stdClassArray', 'resourceArray'];
    private $_propertiesTypesSet = ['boolOrInteger', 'boolOrIntegerArray', 'boolOrIntegerArrayOrNull'];
    private $_enums = ['number', 'numbers', 'numberClass', 'numbersClass'];

    private function _testPHPDocItems($phpdocItems, $value, $successCount, $classTest='TestPropertyDataStorage')
    {
        $c = new $classTest();
        $errorCnt = 0;
        $errorItems = [];
        $successItems = [];
        foreach($phpdocItems as $property) {
            try {
                $c->$property = $value;
                $successItems[] = $property;
            } catch(Exception $expected) {
                $errorCnt++;
                $errorItems[] = $property;
            }
        }
        $errorCntExpected = count($phpdocItems) - $successCount;
        $message = 'for value "'.var_export($value, true).'" failed items: ['.join(',', $errorItems).'] success items: ['.join(',', $successItems).']';
        $this->assertEquals($errorCntExpected, $errorCnt, $message);
    }

    /**
     * @dataProvider valuesPlainProvider
     */
    public function testTypesPlain($value, $successCount)
    {
        $this->_testPHPDocItems($this->_propertiesPlain, $value, $successCount);
    }

    /**
     * @dataProvider valuesArrayProvider
     */
    public function testTypesArray($value, $successCount)
    {
        $this->_testPHPDocItems($this->_propertiesArray, $value, $successCount);
    }

    /**
     * @dataProvider valuesTypesSetProvider
     */
    public function testTypesSet($value, $successCount)
    {

        $this->_testPHPDocItems($this->_propertiesTypesSet, $value, $successCount);
    }

    /**
     * @dataProvider valuesEnumsProvider
     */
    public function testEnums($value, $successCount)
    {
        $this->_testPHPDocItems($this->_enums, $value, $successCount, 'TestEnumDataStorage');
    }



    /**
     * @return array [[$value, $countSuccessAssignment],..]
     */
    public function valuesPlainProvider()
    {
        return [
            [null, 2],
            [1, 3],
            ['1', 4],
            [1.2, 2],
            ['1.2', 3],
            ['str', 2],
            [true, 2],
            [[1, 2, 3], 2],
            [new stdClass(), 2],
            [function () {
            }, 2],
        ];
    }

    /**
     * @return array [[$value, $countSuccessAssignment],..]
     */
    public function valuesArrayProvider()
    {
        return [
            [[null], 2],
            [[1, 2], 3],
            [['1', '2'], 4],
            [[1.2, 1.5], 2],
            [['1.2', '1.5'], 3],
            [['str1', 'str2'], 2],
            [[true, false], 2],
            [[[1], [2], [3]], 2],
            [[new stdClass(), new stdClass()], 2],
            [[function () {
            }, function () {
            }], 2],
        ];
    }

    /**
     * @return array [[$value, $countSuccessAssignment],..]
     */
    public function valuesTypesSetProvider()
    {
        return [
            [null, 1],
            [1, 1],
            ['1', 1],
            [1.2, 0],
            ['1.2', 0],
            ['str', 0],
            [true, 3],
            [[1, 2, 3], 2],
            [new stdClass(), 0],
            [function () {
            }, 0],
        ];
    }

    /**
     * @return array [[$value, $countSuccessAssignment],..]
     */
    public function valuesEnumsProvider()
    {
        return [
            ['str', 0],
            ['one', 2],
            ['two', 2],
            [['one','str'], 0],
            [['one','two'] , 2],
            [['two','two','two'] , 2],
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
 * @property mixed[]         $mixedArray
 * @property bool[]          $boolArray
 * @property integer[]       $integerArray
 * @property float[]         $floatArray
 * @property string[]        $stringArray
 * @property array[]         $arrayArray
 * @property null[]          $nullArray
 * @property Closure[]       $callbackArray
 * @property stdClass[]      $stdClassArray
 * @property resource[]      $resourceArray
 *
 * @property bool|integer           $boolOrInteger
 * @property bool|integer[]         $boolOrIntegerArray
 * @property bool|integer[]|null    $boolOrIntegerArrayOrNull
 */
class TestPropertyDataStorage extends StrictDataStorage
{
}

/**
 * @enum     ["one","two"]      $number
 * @enum     ["one","two"][]    $numbers
 * @enum     TestNumbersEnum    $numberClass
 * @enum     TestNumbersEnum[]  $numbersClass
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
        return ['one', 'two'];
    }
}