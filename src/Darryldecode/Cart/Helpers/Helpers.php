<?php namespace Darryldecode\Cart\Helpers;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/15/2015
 * Time: 8:09 PM
 */

class Helpers {

    const ROUND_MODE_HALF_UP = PHP_ROUND_HALF_UP;
    const ROUND_MODE_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    const ROUND_MODE_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    const ROUND_MODE_HALF_ODD = PHP_ROUND_HALF_ODD;

    const DEFAULT_ROUND_MODE = self::ROUND_MODE_HALF_UP;

    /**
     * normalize price
     *
     * @param $price
     * @return int
     */
    public static function normalizePrice($price)
    {
        return (is_string($price)) ? intval($price) : $price;
    }

	/**
     * @param $value
     * @param int $mode
     */
    public static function intval($value, $mode = self::DEFAULT_ROUND_MODE) {
        return self::round($value, 0, $mode);
    }

	/**
     * @param $value
     * @param int $precision
     * @param int $mode
     */
    public static function round($value, $precision = 0, $mode = self::DEFAULT_ROUND_MODE) {
        return round($value, $precision, $mode);
    }

    /**
     * normalize percentage
     *
     * @param $val
     * @return float
     */
    public static function normalizePercentage($val)
    {
        return (is_string($val)) ? floatval($val) : $val;
    }

    /**
     * check if array is multi dimensional array
     * This will only check the first element of the array if it is still an array
     * to decide that it is a multi dimensional, if you want to check the array strictly
     * with all on its element, flag the second argument as true
     *
     * @param $array
     * @param bool $recursive
     * @return bool
     */
    public static function isMultiArray($array, $recursive = false)
    {
        if( $recursive )
        {
            return (count($array) == count($array, COUNT_RECURSIVE)) ? false : true;
        }
        else
        {
            foreach ($array as $k => $v)
            {
                if (is_array($v))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }

        }
    }

    /**
     * check if variable is set and has value, return a default value
     *
     * @param $var
     * @param bool|mixed $default
     * @return bool|mixed
     */
    public static function issetAndHasValueOrAssignDefault(&$var, $default = false)
    {
        if( (isset($var)) && ($var!='') ) return $var;

        return $default;
    }
}