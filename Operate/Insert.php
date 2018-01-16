<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-9-19
 * Time: 下午6:26
 */

namespace DBOperate\Operate;

use DBOperate\Operate;

class Insert extends Operate
{
    private $insertInfo           = [];
    private $onDuplicateKeyUpdate = [];
    private $replaceInstead       = false;

    /**
     * @param array $cols         由Column列对象组成的数组
     * @param array ...$valuesArr $valuesArr要么为一个Select对象，要么包含与$cols列对应的值数组，如：
     *                            [[$col1v1,$col2v1],[$col1v2,$col2v2]...]
     *
     * @return $this
     */
    public function setInsertValues(array $cols, ...$valuesArr)
    {
        $this->insertInfo = [$cols, $valuesArr];
        return $this;
    }

    public function replaceInstead($replace = true)
    {
        $this->replaceInstead = $replace;
    }

    public function onDuplicateKeyUpdate(array $cols, array $values)
    {
        $this->onDuplicateKeyUpdate = [$cols, $values];
    }

    private function createInsertColStr()
    {
        if (empty($this->insertInfo)) {
            return false;
        }
        list($cols, $valuesArr) = $this->insertInfo;
        $colStr = implode(',', $cols);
        $colStr = "($colStr)";
        if (!empty($valuesArr[0])) {
            $tmp = $valuesArr[0];
            if ($tmp instanceof Select) {
                $selectStr = $tmp->prepareStr();
                return "$colStr $selectStr";
            }
        }
        foreach ($valuesArr as $key => $values) {
            foreach ($values as $k => $v) {
                if (is_scalar($v)) {
                    $values[$k] = '?';
                } elseif (is_null($v)) {
                    $values[$k] = 'null';
                } else {
                    $values[$k] = "$v";
                }
            }
            $valueStr        = implode(',', $values);
            $valuesArr[$key] = "($valueStr)";
        }
        $valuesStr = implode(',', $valuesArr);
        return "$colStr VALUES $valuesStr";
    }

    private function createInsertColValues()
    {
        if (empty($this->insertInfo)) {
            return false;
        }
        list(, $valuesArr) = $this->insertInfo;
        if (!empty($valuesArr[0])) {
            $tmp = $valuesArr[0];
            if ($tmp instanceof Select) {
                return $tmp->prepareValues();
            }
        }
        $prepareValues = [];
        foreach ($valuesArr as $key => $values) {
            foreach ($values as $k => $v) {
                if (is_scalar($v)) {
                    $prepareValues[] = $v;
                }
            }
        }
        return $prepareValues;
    }

    private function createOnDuplicateKeyUpdateStr()
    {
        if (empty($this->onDuplicateKeyUpdate)) {
            return false;
        }
        $keyPairArr = [];
        list($cols, $values) = $this->onDuplicateKeyUpdate;
        foreach ($values as $v) {
            $col = array_shift($cols);
            if (is_scalar($v)) {
                $keyPairArr[] = "$col=?";
            } elseif (is_null($v)) {
                $keyPairArr[] = "$col=null";
            } else {
                $keyPairArr[] = "$col=$v";
            }
        }
        return 'ON DUPLICATE KEY UPDATE ' . implode(',', $keyPairArr);
    }

    private function createOnDuplicateKeyUpdateValues()
    {
        if (empty($this->onDuplicateKeyUpdate)) {
            return false;
        }
        $prepareValues = [];
        list(, $values) = $this->onDuplicateKeyUpdate;
        foreach ($values as $v) {
            if (is_scalar($v)) {
                $prepareValues[] = $v;
            }
        }
        return $prepareValues;
    }

    public function prepareStr()
    {
        $table                   = (string)$this->table;
        $insertColStr            = $this->createInsertColStr();
        $onDuplicateKeyUpdateStr = $this->createOnDuplicateKeyUpdateStr();
        $operator                = $this->replaceInstead ? 'REPLACE INTO' : 'INSERT INTO';
        return "$operator $table $insertColStr $onDuplicateKeyUpdateStr";
    }

    public function prepareValues()
    {
        $insertPrepareValues        = $this->createInsertColValues();
        $onDuplicateKeyUpdateValues = $this->createOnDuplicateKeyUpdateValues();
        return array_merge($insertPrepareValues, $onDuplicateKeyUpdateValues ?: []);
    }
}
