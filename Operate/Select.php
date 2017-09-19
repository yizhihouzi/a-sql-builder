<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:29
 */

namespace DBOperate\Operate;


use DBOperate\Column;
use DBOperate\Condition;
use DBOperate\DBOperateInterface;
use DBOperate\Table;

class Select extends DBOperateInterface
{
    private $cols = [];

    public function fetchCols(Column ...$cols)
    {
        $this->cols = array_merge($this->cols, $cols);
        return $this;
    }

    public function join(Table $table, Condition $condition, string $joinDirection = 'left')
    {
        // TODO: Implement join() method.
    }

    public function groupBy(Column $col)
    {
        // TODO: Implement groupBy() method.
    }

    public function where(Condition $condition)
    {
        // TODO: Implement where() method.
    }

    public function perform()
    {
        // TODO: Implement perform() method.
    }
}