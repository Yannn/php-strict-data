<?php
namespace PhpStrictData;

/**
 * class StrictDataStorage
 *
 * @author Annenkov Yaroslav <ya@annenkov.ru>
 * @link https://github.com/Yannn/php-strict-datastorage
 */
class StrictDataStorage
{
    /** @var array cache PHPDoc by class [class1=> phpDoc1, class2=> phpDoc2...] */
    private static $_phpDoc;
    /** @var array allowed property types by class [class1=> [property1=> [type1,type2...], property2=> [type1,type2...],..],..] */
    private static $_types;
    /** @var array allowed property values by class [class1=> [property1=> ['values'=> [value1,value2...], 'isArray'=>true],..],..] */
    private static $_enums;
    /** @var array assigned property values array */
    private $properties = [];
    /** @var bool */
    private $optionPhpDocNotRequired = false;
    /** @var bool */
    private $optionStrictNumberTypeCheck = false;
    /** @var string check class name */
    private $regexpClass = '/[_\\w\\d]+/';
    /** @var array functions for check types. <b>is_callable()</b> - dangerous function! use check class Closure */
    private static $functionsCheck = [
        'string' => 'is_string',
        'array' => 'is_array',
        'object' => 'is_object',
        'null' => 'is_null',
        'resource' => 'is_resource',
        'boolean' => 'is_bool',
        'bool' => 'is_bool',
        'int' => 'checkIsInteger',
        'integer' => 'checkIsInteger',
        'float' => 'checkIsFloat',
        'double' => 'checkIsFloat'
    ];

    function __construct()
    {
        $options = $this->getPhpDocItems('options');
        // var_dump($options);
        if (in_array('StrictNumberTypeCheck', $options)) {
            $this->optionStrictNumberTypeCheck = true;
        }
        if (in_array('PhpDocNotRequired', $options)) {
            $this->optionPhpDocNotRequired = true;
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    function __get($name)
    {
        if (!$this->existDescription() || !$this->existPropertyDescription($name)) {
            if (!$this->optionPhpDocNotRequired || !array_key_exists($name, $this->properties)) {
                $this->handleNotExist($name);

                return null;
            } else {
                return $this->properties[$name];
            }
        } else {
            return $this->properties[$name];
        }
    }

    /**
     * @param $name
     * @param $value
     */
    function __set($name, $value)
    {
        if (!$this->existDescription() || !$this->existPropertyDescription($name)) {
            if (!$this->optionPhpDocNotRequired) {
                $this->handleNotExist($name);
            } else {
                $this->properties[$name] = $value;
            }
        } else {
            if (!$this->checkValueByAllowedTypes($value, $this->getPropertyTypes($name))) {
                $this->handleTypeInvalid($name);
            } elseif (!$this->checkValueByAllowedValues($value, $this->getPropertyValues($name))) {
                $this->handleValueInvalid($name);
            } else {
                $this->properties[$name] = $value;
            }
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    function __isset($name)
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Action execute if property not defined in PHPDoc or property not exist by PhpDocNotRequired=true
     *
     * @param string $property
     */
    protected function handleNotExist($property)
    {
        $this->handleError('Property '.$property.' not exist');
    }

    /**
     * Action execute if type value of property does not match "@property"
     *
     * @param string $property
     */
    protected function handleTypeInvalid($property)
    {
        $this->handleError('Invalid type of value for property '.$property);
    }

    /**
     * Action execute if value of property does not match "@enum"
     *
     * @param string $property
     */
    protected function handleValueInvalid($property)
    {
        $this->handleError('Invalid value for property '.$property);
    }

    /**
     * Action execute if value of property does not match "@enum"
     *
     * @param string $text
     */
    protected function handlePHPDocInvalid($text)
    {
        $this->handleError('Error in PHPDoc: '.$text);
    }

    /**
     * @param string $message
     * @throws \Exception
     */
    protected function handleError($message)
    {
        throw new \Exception($message);
    }

    /**
     * Get values from enum class
     *
     * @param string $enum
     * @return  array|null
     */
    protected function getEnumValues($enum)
    {
        /** @var  EnumArrayableInterface $object */
        $object = new $enum;
        if ($object instanceof EnumArrayableInterface) {
            return $object->getEnumValues();
        } else {
            return null;
        }
    }

    /**
     * Get functions for type check
     *
     * @return array
     * @see $_functions
     */
    protected function getCheckTypeFunctions()
    {
        $functions = self::$functionsCheck;
        if ($this->optionStrictNumberTypeCheck) {
            $functions['int'] = $functions['integer'] = 'is_int';
            $functions['float'] = $functions['double'] = 'is_float';
        }

        return $functions;
    }

    /**
     * Check value for integer type
     *
     * @param mixed $value
     * @return bool
     */
    protected function checkIsInteger($value)
    {
        return is_bool($value) ? false : filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Check value for float type
     *
     * @param mixed $value
     * @return bool
     */
    protected function checkIsFloat($value)
    {
        return is_bool($value) ? false : filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Exist PHPDoc items (@property,@enum) in class
     *
     * @return bool
     */
    private function existDescription()
    {
        return $this->getAllowedTypes() || $this->getAllowedValues() || false;
    }

    /**
     * Exist PHPDoc items (@property,@enum) for property
     *
     * @param string $property
     * @return bool
     */
    private function existPropertyDescription($property)
    {
        return $this->getPropertyTypes($property) || $this->getPropertyValues($property) || false;
    }

    /**
     * Get array types of property
     * or null if property is not defined in PHPDoc,
     * or [] if lines "@property" not exists in PHPDoc.
     *
     * @param $property
     * @return array|null
     */
    private function getPropertyTypes($property)
    {
        $types = $this->getAllowedTypes();

        return isset($types[$property]) ? $types[$property] : [];
    }

    /**
     * Get array possible values of property
     * or null if enums for property is not defined in PHPDoc,
     * or [] if lines "@enums" not exists in PHPDoc.
     *
     * @param $property
     * @return array|null
     */
    private function getPropertyValues($property)
    {
        $enums = $this->getAllowedValues();

        return isset($enums[$property]) ? $enums[$property] : [];
    }

    /**
     * Parse "@property" items form PHPDoc and build array types of properties.
     * Types cached to {@link $_types}.
     *
     * @return array return [property1=> [type1, type2],...]  or [] if lines "@property" not exists in PHPDoc
     */
    private function getAllowedTypes()
    {
        $class = get_class($this);
        if (!isset(self::$_types[$class])) {
            $typesRaw = $this->getPhpDocItems('property');
            if ($typesRaw) {
                $types = [];
                foreach ($typesRaw as $property => $type) {
                    $types[$property] = explode('|', $type);
                }
                self::$_types[$class] = $types;
            } else {
                self::$_types[$class] = [];
            }
        }

        return self::$_types[$class];
    }

    /**
     * Parse "@enum" items form PHPDoc and build array enums of properties.
     * Enums cached to {@link $_enums}.
     *
     * @return array return [property1=> ['values' => [value1, value2], 'isArray' => false],...] or [] if lines "@enum" not exists in PHPDoc
     */
    private function getAllowedValues()
    {
        $class = get_class($this);
        if (!isset(self::$_enums[$class])) {
            $enumsRaw = $this->getPhpDocItems('enum');
            if ($enumsRaw) {
                $enums = [];
                foreach ($enumsRaw as $property => $enum) {
                    if (substr($enum, -2) == '[]' && $enum != '[]') {
                        $enum = substr($enum, 0, -2);
                        $isArray = true;
                    } else {
                        $isArray = false;
                    }
                    $values = [];
                    if (substr($enum, 0, 1) == '[' && substr($enum, -1) == ']') {
                        // enum from json
                        $values = json_decode($enum, true);
                        if ($values === null) {
                            $this->handlePHPDocInvalid('Invalid description of enum '.$enum." - ");
                        }
                    } elseif (preg_match($this->regexpClass, $enum)) {
                        // enum from class
                        $values = $this->getEnumValues($enum);
                        if (!$values) {
                            $this->handlePHPDocInvalid('Invalid enum class '.$enum);
                        }
                    } else {
                        $this->handlePHPDocInvalid('Invalid enum '.$enum);
                    }
                    $enums[$property]['values'] = $values;
                    $enums[$property]['isArray'] = $isArray;

                }
                self::$_enums[$class] = $enums;
            } else {
                self::$_enums[$class] = [];
            }
        }

        return self::$_enums[$class];
    }

    /**
     * Checks value matching one of allowing types
     *
     * @param mixed $value
     * @param array $types see in
     *                      {@link getAllowedTypes()}
     * @return bool
     */
    private function checkValueByAllowedTypes($value, array $types)
    {
        if (empty($types)) {
            return true;
        }
        $functions = $this->getCheckTypeFunctions();
        foreach ($types as $type) {
            if (substr($type, -2) == '[]') {
                // array of values
                $type = substr($type, 0, -2);
                $isArray = true;
            } else {
                // one value
                $isArray = false;
            }
            if ($type == 'mixed') {
                if ($isArray == false || is_array($value)) {
                    return true;
                } else {
                    return false;
                }
            }
            $fn = null;
            $params = [];
            // prepare functions and params
            if (isset($functions[$type])) {
                $fn = $functions[$type];
                // inner functions
                if (strpos($fn, 'checkIs') === 0) {
                    if (method_exists($this, $fn)) {
                        $fn = [$this, $fn];
                    } else {
                        $this->handleError('Not found method '.$fn);
                    }
                }
                $params = [];
            } elseif (preg_match($this->regexpClass, $type)) {
                $fn = function ($value, $class) {
                    return $value instanceof $class;
                };
                $params = [$type];
            } else {
                $this->handleError('Not found handler for type '.$type);
            }
            // check
            if ($isArray) {
                $valid = false;
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $valid = call_user_func_array($fn, array_merge([$item], $params));
                        if (!$valid) {
                            break;
                        }
                    }
                }
            } else {
                $valid = call_user_func_array($fn, array_merge([$value], $params));
            }
            if ($valid) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Checks value matching one of the allowing $values
     *
     * @param mixed $value
     * @param array $values see in
     *                      {@link getAllowedValues()}
     * @return bool
     */
    private function checkValueByAllowedValues($value, array $values)
    {
        if (empty($values['values'])) {
            return true;
        }
        if ($values['isArray']) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (!in_array($item, $values['values'])) {
                        return false;
                    }
                }

                return true;
            } else {
                return false;
            }
        } else {
            return in_array($value, $values['values']);
        }
    }

    /**
     * Get and caching text of PHPDoc
     *
     * @param string $class
     * @return string
     */
    private function getPhpDoc($class)
    {
        if (!isset(self::$_phpDoc[$class])) {
            $reflection = new \ReflectionClass($this);
            self::$_phpDoc[$class] = $reflection->getDocComment();
        }

        return self::$_phpDoc[$class];
    }

    /**
     * Get values of PHPDoc items by name
     *
     * @param string $name
     * @return mixed
     */
    private function getPhpDocItems($name)
    {
        $phpDoc = $this->getPhpDoc(get_class($this));
        switch ($name) {
            case 'options':
                preg_match('/@options\s*([\w\,]+)/m', $phpDoc, $matches);
                if (isset($matches[1])) {
                    $items = explode(',', $matches[1]);
                } else {
                    $items = [];
                }
                break;
            case 'enum':
                preg_match_all('/@enum\s*([\w\[\]\|\\\]+|\[.*\])\s*\$(\w*)/m', $phpDoc, $matches);
                if ($matches) {
                    $items = array_combine($matches[2], $matches[1]);
                } else {
                    $items = [];
                }
                break;
            case 'property':
                preg_match_all('/@property\s*([\w\[\]\|\\\]*)\s*\$(\w*)/m', $phpDoc, $matches);
                if ($matches) {
                    $items = array_combine($matches[2], $matches[1]);
                } else {
                    $items = [];
                }
                break;
            default:
                $items = [];
        }

        return $items;
    }
}