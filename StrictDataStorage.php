<?php
/**
 * class StrictDataStorage
 *
 * @author Annenkov Yaroslav <ya@annenkov.ru>
 * @link https://github.com/Yannn/php-strict-datastorage
 */
class StrictDataStorage
{
    private $_regexpClass = '/[_\\w\\d]+/';
    /** @var bool strict check */
    private $_optionPhpDocNotRequired = false;
    /** @var bool strict check */
    private $_optionStrictNumberTypeCheck = false;
    /** @var array [class1=> phpDoc1, class2=> phpDoc2...] */
    private static $_phpDoc;
    /** @var array types of properties [class1=> [property1=> [type1,type2...], property2=> [type1,type2...],..],..] */
    private static $_properties;
    /** @var array possible values of properties [class1=> [property1=> ['values'=> [value1,value2...], 'isArray'=>true],..],..] */
    private static $_enums;
    /** @var array functions for check types. is_callable() - dangerous function! use type Closure */
    private static $_functions = ['string' => 'is_string', 'array' => 'is_array', 'object' => 'is_object',
        'null' => 'is_null', 'resource' => 'is_resource', 'boolean' => 'is_bool', 'bool' => 'is_bool',
        'int' => '_isInteger', 'integer' => '_isInteger', 'float' => '_isFloat', 'double' => '_isFloat'];

    function __construct()
    {
        $options = $this->_getPhpDocItems('options');
        // var_dump($options);
        if (in_array('StrictNumberTypeCheck', $options)) {
            $this->_optionStrictNumberTypeCheck = true;
        }
        if (in_array('PhpDocNotRequired', $options)) {
            $this->_optionPhpDocNotRequired = true;
        }
        //$this->_strictMode = $strictMode;
    }

    /**
     * @param $name
     * @return mixed
     */
    function __get($name)
    {
        if (!$this->_existDescription() || !$this->_existPropertyDescription($name)) {
            // lines "@property" and "@enum" not exists in phpdoc
            if (!$this->_optionPhpDocNotRequired || !isset($this->$name)) {
                $this->handleNotExist($name);
            } else {
                return $this->$name;
            }
        } else {
            return $this->$name;
        }
    }

    /**
     * @param $name
     * @param $value
     */
    function __set($name, $value)
    {
        if (!$this->_existDescription() || !$this->_existPropertyDescription($name)) {
            // lines "@property" and "@enum" not exists in phpdoc
            if (!$this->_optionPhpDocNotRequired) {
                $this->handleNotExist($name);
            } else {
                $this->$name = $value;
            }
        } else {
            if (!$this->_checkByTypes($value, $this->_getPropertyTypes($name))) {
                $this->handleTypeInvalid($name);
            } elseif (!$this->_checkByValues($value, $this->_getPropertyValues($name))) {
                $this->handleValueInvalid($name);
            } else {
                $this->$name = $value;
            }
        }
    }

    /**
     * Action execute if property not defined in phpdoc
     *
     * @param $name
     */
    protected function handleNotExist($name)
    {
        $this->throwException('Property ' . $name . ' not exist');
    }

    /**
     * Action execute if type value of property does not match phpdoc
     *
     * @param $name
     */
    protected function handleTypeInvalid($name)
    {
        $this->throwException('Invalid type of value for property ' . $name);
    }

    /**
     * Action execute if value of property does not match phpdoc
     *
     * @param $name
     */
    protected function handleValueInvalid($name)
    {
        $this->throwException('Invalid value for property ' . $name);
    }

    /**
     * @param string $message
     * @throws Exception
     */
    public function throwException($message)
    {
        throw new Exception($message);
    }

    /**
     * @return bool
     */
    private function _existDescription()
    {
        return $this->_getAllowedTypes() || $this->_getAllowedValues() || false;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function _existPropertyDescription($name)
    {
        return $this->_getPropertyTypes($name) || $this->_getPropertyValues($name) || false;
    }

    /**
     * Get array types of property
     * or null if property is not defined in phpdoc,
     * or [] if lines "@property" not exists in phpdoc.
     *
     * @param $name
     * @return array|null
     */
    private function _getPropertyTypes($name)
    {
        $types = $this->_getAllowedTypes();
        return isset($types[$name]) ? $types[$name] : [];
    }

    /**
     * Get array possible values of property
     * or null if enums for property is not defined in phpdoc,
     * or [] if lines "@enums" not exists in phpdoc.
     *
     * @param $name
     * @return array|null
     */
    private function _getPropertyValues($name)
    {
        $enums = $this->_getAllowedValues();
        return isset($enums[$name]) ? $enums[$name] : [];
    }

    /**
     * Parse "@property" items form phpdoc and build array types of properties.
     * Types cached to {@link $_properties}.
     *
     * @return array return [] if lines "@property" not exists in phpdoc
     */
    private function _getAllowedTypes()
    {
        $class = get_class($this);
        if (!isset(self::$_properties[$class])) {
            $typesRaw = $this->_getPhpDocItems('property');
            if ($typesRaw) {
                $types = [];
                foreach ($typesRaw as $property => $type) {
                    $types[$property] = explode('|', $type);
                }
                self::$_properties[$class] = $types;
            } else {
                self::$_properties[$class] = [];
            }
        }
        return self::$_properties[$class];
    }

    /**
     * Parse "@enum" items form phpdoc and build array enums of properties.
     * Enums cached to {@link $_enums}.
     *
     * @return array return [] if lines "@enum" not exists in phpdoc
     */
    private function _getAllowedValues()
    {
        $class = get_class($this);
        if (!isset(self::$_enums[$class])) {
            $enumsRaw = $this->_getPhpDocItems('enum');
            if ($enumsRaw) {
                $enums = [];
                foreach ($enumsRaw as $property => $enum) {
                    if (substr($enum, -2) == '[]' && $enum != '[]') {
                        $enum = substr($enum, 0, -2);
                        $isArray = true;
                    } else {
                        $isArray = false;
                    }
                    if (substr($enum, 0, 1) == '[' && substr($enum, -1) == ']') {
                        $values = json_decode($enum, true);
                    } elseif (preg_match($this->_regexpClass, $enum)) {
                        /** @var  IEnumArrayable $object */
                        $object = new $enum;
                        if ($object instanceof IEnumArrayable) {
                            $values = $object->getEnumArray();
                        } else {
                            $this->throwException('Invalid enum ' . $enum);
                        }
                    } else {
                        $this->throwException('Invalid enum ' . $enum);
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
     * Checks the value against one of the types.
     *
     * @param mixed $value
     * @param array $types
     * @return bool
     */
    private function _checkByTypes($value, array $types)
    {
        if (empty($types)) {
            return true;
        }
        $functions = self::$_functions;
        if ($this->_optionStrictNumberTypeCheck) {
            $functions['int'] = $functions['integer'] = 'is_int';
            $functions['float'] = $functions['double'] = 'is_float';
        }
        foreach ($types as $type) {
            if ($type == 'mixed') {
                return true;
            }
            if (substr($type, -2) == '[]') {
                $type = substr($type, 0, -2);
                $isArray = true;
            } else {
                $isArray = false;
            }
            // prepare functions and params
            if (isset($functions[$type])) {
                $fn = $functions[$type];
                if (strpos($fn, '_') === 0) {
                    if (method_exists($this, $fn)) {
                        $fn = [$this, $fn];
                    } else {
                        $this->throwException('Not found method ' . $fn);
                    }
                }
                $params = [];
            } elseif (preg_match($this->_regexpClass, $type)) {
                $fn = function ($value, $class) {
                    return $value instanceof $class;
                };
                $params = [$type];
            } else {
                $this->throwException('Not found handler for ' . $type);
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
     * Checks the value against one of the types.
     *
     * @param mixed $value
     * @param array $values
     * @return bool
     */
    private function _checkByValues($value, array $values)
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
     * @param string $class
     * @return string
     */
    private function _getPhpDoc($class)
    {
        if (!isset(self::$_phpDoc[$class])) {
            $reflection = new ReflectionClass($this);
            self::$_phpDoc[$class] = $reflection->getDocComment();
        }
        return self::$_phpDoc[$class];
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function _getPhpDocItems($name)
    {
        $phpDoc = $this->_getPhpDoc(get_class($this));
        switch ($name) {
            case 'options':
                preg_match('/@options\s*([\w\|]+)/m', $phpDoc, $matches);
                if (isset($matches[1])) {
                    $items = explode('|', $matches[1]);
                } else {
                    $items = [];
                }
                break;
            case 'enum':
                preg_match_all('/@enum\s*([\w\[\]\|]+|\[.*\])\s*\$(\w*)/m', $phpDoc, $matches);
                if ($matches) {
                    $items = array_combine($matches[2], $matches[1]);
                } else {
                    $items = [];
                }
                break;
            case 'property':
                preg_match_all('/@property\s*([\w\[\]\|]*)\s*\$(\w*)/m', $phpDoc, $matches);
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

    /**
     * @param mixed $value
     * @return bool
     */
    private function _isInteger($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function _isFloat($value)
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
}
