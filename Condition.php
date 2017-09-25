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

    public function __construct(Column $column, $value, $relation = '=', $groupName = 'e')
    {

        if (is_scalar($value) && $relation == 'in') {
            if (!is_array($value)) {
                throw new \Exception('$value must be array type while $isScalarValue==true and $relation=="in"');
            }
        }
        $this->groupName = $groupName;
        $this->relation  = $relation;
        $this->column    = $column;
        $this->value     = $value;
    }

    public function getValue()
    {
        if (is_scalar($this->value)) {
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
        $relation = $this->relation;
        if (is_scalar($this->value)) {
            if ($this->relation == 'in') {
                $valueHolder = str_repeat('?,', count($this->value));
                $valueHolder = rtrim($valueHolder, ',');
                $v           = "($valueHolder)";
            } else {
                $v = '?';
            }
        } else {
            if ($this->value instanceof Select) {
                $v = $this->value->prepareStr();
                $v = "($v)";
            } elseif (is_null($this->value)) {
                $v = '';
                if ($this->relation == '=') {
                    $relation = ' is null';
                } else {
                    $relation = ' is not null';
                }
            } else {
                $v = $this->value;
            }
        }
        return sprintf("%s%s%s", (string)$this->column, $relation, $v);
    }
}