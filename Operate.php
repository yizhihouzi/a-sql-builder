<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:17
 */

namespace DBOperate;

use DBOperate\Exception\DBOperateException;

abstract class Operate implements Element
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

    /**
     * @return string
     * @throws DBOperateException
     */
    public function __toString()
    {
        return json_encode([$this->prepareStr(), $this->prepareValues()]);
    }

    /**
     * @return string
     * @throws DBOperateException
     */
    public abstract function prepareStr();

    /**
     * @return array
     * @throws DBOperateException
     */
    public abstract function prepareValues();

    /**
     * @return string
     * @throws DBOperateException
     */
    public function toTestSql()
    {
        $preStr    = $this->prepareStr();
        $preValues = $this->prepareValues();
        $preStr    = str_replace('?', '\'%s\'', $preStr);
        return vsprintf($preStr, $preValues);
    }
}