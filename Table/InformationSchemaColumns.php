<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-9-19
 * Time: 下午5:23
 */

namespace DBOperate\Table;

use DBOperate\Column;
use DBOperate\Condition;
use DBOperate\Connection;
use DBOperate\Operate\Select;
use DBOperate\Table;

class InformationSchemaColumns
{
    /**
     * Returns an array of values belonging to a given property of each item in a collection.
     *
     * @param array $collection array
     * @param string $property property name
     *
     * @return array
     */
    private static function pluck(array $collection, $property)
    {
        return \array_map(function ($value) use ($property) {
            if (isset($value[$property])) {
                return $value[$property];
            }

            foreach (\explode('.', $property) as $segment) {
                if (\is_object($value)) {
                    if (isset($value->{$segment})) {
                        $value = $value->{$segment};
                    } else {
                        return null;
                    }
                } else {
                    if (isset($value[$segment])) {
                        $value = $value[$segment];
                    } else {
                        return null;
                    }
                }
            }

            return $value;
        }, (array)$collection);
    }

    public static function getTableCols($tableName, $schemaName = null)
    {
        if (empty($schemaName)) {
            $schemaName = Connection::getSchemaName();
        }
        $table = new Table('Information_schema`.`columns');
        $select = new Select($table);
        $select->fetchCols(new Column('COLUMN_NAME', $table));
        $condition1 = new Condition(new Column('table_schema', $table), $schemaName);
        $condition2 = new Condition(new Column('table_name', $table), $tableName);
        $select->where($condition1, $condition2);
        $rows = Connection::select(...$select->prepare());
        return self::pluck($rows, 'COLUMN_NAME');
    }
}
