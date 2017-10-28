<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-10-28
 * Time: 下午4:33
 */

namespace DBOperate;

interface Element
{
    public function prepareStr();

    public function prepareValues();
}