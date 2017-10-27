<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-9-27
 * Time: 下午5:49
 */

namespace DBOperate;

class ArrayHelper
{
    /**
     * Flattens a multidimensional array. If you pass shallow, the array will only be flattened a single level.
     *
     * __::flatten([1, 2, [3, [4]]], [flatten]);
     *      >> [1, 2, 3, 4]
     *
     * @param      $array
     * @param bool $shallow
     *
     * @return array
     *
     */
    public static function flatten($array, $shallow = false)
    {
        $output = [];
        foreach ($array as $value) {
            if (is_array($value)) {
                if (!$shallow) {
                    $value = self::flatten($value, $shallow);
                }
                foreach ($value as $valItem) {
                    $output[] = $valItem;
                }
            } else {
                $output[] = $value;
            }
        }
        return $output;
    }
}
