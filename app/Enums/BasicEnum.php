<?php
/**
 * Created by Leonel E.
 * User: leonelngande
 * Date: 1/9/19
 * Time: 12:09 PM
 */

namespace App\Enums;

use ReflectionClass;
use ReflectionException;

abstract class BasicEnum
{
    private static $constCacheArray = null;

    /**
     * @return mixed
     */
    public static function getConstants()
    {
        if (self::$constCacheArray === null) {
            self::$constCacheArray = [];
        }
        $calledClass = static::class;
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    /**
     * @param $name
     * @param bool $strict
     * @return bool
     * @throws ReflectionException
     */
    public static function isValidName($name, $strict = false): bool
    {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    /**
     * @param $value
     * @param bool $strict
     * @return bool
     * @throws ReflectionException
     */
    public static function isValidValue($value, $strict = true)
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict);
    }
}
