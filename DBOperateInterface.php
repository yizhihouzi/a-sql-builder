<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:17
 */

namespace DBOperate;


abstract class DBOperateInterface
{
    protected $table;

    /**
     * DBOperateInterface constructor.
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public abstract function join(Table $table, Condition $condition, string $joinDirection = 'left');

    public abstract function groupBy(Column $col);

    public abstract function where(Condition $condition);

    public abstract function perform();
}