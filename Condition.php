<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2017/9/17
 * Time: 上午11:19
 */

namespace DBOperate;


use DBOperate\Operate\Select;

class Condition
{
    /**
     * @var string 条件组，同一组内是and关系，不同组为或关系
     */
    private $groupName;
    /**
     * @var string 关系 > < <> =
     */
    private $relation;
    /**
     * @var Column 列名
     */
    private $column;
    /**
     * @var mixed 与$col列值进行比较的值
     */
    private $value;
    /**
     * @var bool 标识$value是否为标量值，即是否会被单引号包裹
     */
    private $isScalarValue;

    public function __construct(Column $column, $value, $relation = '=', $isScalarValue = true, $groupName = 'e')
    {
        if ($isScalarValue && $relation == 'in') {
            if (!is_array($value)) {
                throw new \Exception('$value must be array type while $isScalarValue==true and $relation=="in"');
            }
        }
        $this->groupName     = $groupName;
        $this->relation      = $relation;
        $this->column        = $column;
        $this->value         = $value;
        $this->isScalarValue = $isScalarValue;
    }

    public function isScalarValue()
    {
        return $this->isScalarValue;
    }

    public function getValue()
    {
        if ($this->isScalarValue()) {
            return $this->value;
        } else {
            if ($this->value instanceof Select) {
                return $this->value->prepareValues();
            }
            return false;
        }
    }

    /**
     * @return string
     */
    public function getGroupName(): string
    {
        return $this->groupName;
    }

    public function __toString()
    {
        if ($this->isScalarValue) {
            if ($this->relation == 'in') {
                $valueHolder = str_repeat('?,', count($this->value));
                $valueHolder = rtrim($valueHolder, ',');
                $v           = "($valueHolder)";
            } else {
                $v = ' ? ';
            }
        } else {
            if ($this->value instanceof Select) {
                $v = $this->value->prepareStr();
                $v = "($v)";
            } else {
                $v = $this->value;
            }
        }
        return sprintf(" %s %s %s ", (string)$this->column, $this->relation, $v);
    }
}