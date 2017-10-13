<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:17
 */

namespace DBOperate;


abstract class Operate
{
    protected $table;

    /**
     * DBOperateInterface constructor.
     *
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    function __toString()
    {
        return json_encode([$this->prepareStr(), $this->prepareValues()]);
    }

    public function toTestSql()
    {
        $preStr    = $this->prepareStr();
        $preValues = $this->prepareValues();
        $preStr    = str_replace('?', '\'%s\'', $preStr);
        return vsprintf($preStr, $preValues);
    }

    public abstract function prepareStr();

    public abstract function prepareValues();
}