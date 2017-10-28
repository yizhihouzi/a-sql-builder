<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-10-28
 * Time: 下午4:41
 */

namespace DBOperate\Element;

use DBOperate\Column;
use DBOperate\Element;

class CaseValue implements Element
{
    private $column;
    private $values    = [];
    private $elseValue = false;

    /**
     * CaseValue constructor.
     *
     * @param Column $column
     * @param        $when
     * @param        $then
     */
    public function __construct(Column $column, $when, $then)
    {
        $this->column   = $column;
        $this->values[] = [$when, $then];
    }

    public function addCase($when, $then)
    {
        $this->values[] = [$when, $then];
    }

    /**
     * @param null $elseValue
     */
    public function setElseValue($elseValue)
    {
        $this->elseValue = $elseValue;
    }

    public function prepareStr()
    {
        $preStr    = "CASE {$this->column}";
        $strValues = [];
        foreach ($this->values as list($when, $then)) {
            if (is_scalar($when)) {
                $when = '?';
            }
            if (is_scalar($then)) {
                $then = '?';
            }
            $strValues[] = "WHEN $when THEN $then";
        }
        $valuesStr = implode(' ', $strValues);
        if ($this->elseValue !== false) {
            $valuesStr = "$valuesStr ELSE " . (is_scalar($this->elseValue) ? '?' : $this->elseValue);
        }
        return "$preStr $valuesStr";
    }

    public function prepareValues()
    {
        $values = [];
        foreach ($this->values as list($when, $then)) {
            if (is_scalar($when)) {
                $values[] = $when;
            }
            if (is_scalar($then)) {
                $values[] = $then;
            }
        }
        if ($this->elseValue !== false && is_scalar($this->elseValue)) {
            $values[] = $this->elseValue;
        }
        return $values;
    }

    function __toString()
    {
        return json_encode([$this->prepareStr(), $this->prepareValues()]);
    }
}
